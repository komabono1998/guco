<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('members');

const IMPORT_COLUMNS = ['full_name', 'email', 'phone', 'category', 'college', 'programme', 'graduation_year', 'chapter', 'paid_until'];
$chapters = q('SELECT id, name FROM chapters')->fetchAll(PDO::FETCH_KEY_PAIR);
$chapterByName = array_change_key_case(array_flip($chapters), CASE_LOWER);

if (isset($_GET['template'])) {
    csv_download('guco-members-template.csv', IMPORT_COLUMNS, [['Jane Akello', 'jane@example.com', '0700000000', 'ordinary', 'Faculty of Science', 'BSc Computer Science', '2019', 'Northern Uganda', '']]);
}
if (isset($_GET['export'])) {
    $status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : '';
    $rows = q("SELECT p.member_no, p.membership_id, u.full_name, u.email, u.status, p.category, p.phone, p.college, p.programme, p.graduation_year, p.employment_status, p.profession, c.name AS chapter, p.subscription_status, p.paid_until, u.created_at
        FROM users u JOIN member_profiles p ON p.user_id = u.id LEFT JOIN chapters c ON c.id = p.chapter_id WHERE u.role = 'member'" . ($status ? ' AND u.status = ?' : '') . ' ORDER BY p.member_no', $status ? [$status] : [])->fetchAll();
    audit('export_members', count($rows) . ' records' . ($status ? " ($status)" : ''));
    csv_download('guco-members-' . date('Ymd') . '.csv', ['Member no', 'Membership ID', 'Name', 'Email', 'Application', 'Category', 'Phone', 'College', 'Programme', 'Graduation year', 'Employment', 'Profession', 'Chapter', 'Subscription', 'Paid until', 'Registered'],
        array_map(fn($r) => [member_no_label($r['member_no']), $r['membership_id'], $r['full_name'], $r['email'], $r['status'], $r['category'], $r['phone'], $r['college'], $r['programme'], $r['graduation_year'], $r['employment_status'], $r['profession'], $r['chapter'], $r['subscription_status'], $r['paid_until'], $r['created_at']], $rows));
}

$report = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = $_FILES['file'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024 || strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'csv') {
        flash('Choose a CSV file under 2MB. In Excel use Save As, CSV (Comma delimited).', 'error');
        redirect('admin/members_import.php');
    }
    $dry = !empty($_POST['dry_run']);
    $approve = !empty($_POST['approve']);
    $h = fopen($f['tmp_name'], 'r');
    $head = fgetcsv($h);
    $head = array_map(fn($c) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c))), $head ?: []);
    if (!in_array('email', $head, true) || !in_array('full_name', $head, true)) {
        flash('The first row must contain the column names full_name and email. Download the template to see the layout.', 'error');
        redirect('admin/members_import.php');
    }
    $report = ['added' => 0, 'duplicates' => [], 'invalid' => [], 'possible' => [], 'created' => [], 'emailed' => 0, 'email_failed' => []];
    $sendMail = !empty($_POST['send_email']);
    $n = 0;
    $seen = [];
    while (($raw = fgetcsv($h)) !== false && $n < 2000) {
        $n++;
        if (count(array_filter($raw, fn($x) => trim((string) $x) !== '')) === 0) {
            continue;
        }
        $r = array_combine($head, array_pad(array_slice($raw, 0, count($head)), count($head), ''));
        $name = trim($r['full_name'] ?? '');
        $email = strtolower(trim($r['email'] ?? ''));
        $cat = strtolower(trim($r['category'] ?? '')) ?: 'ordinary';
        $year = (int) ($r['graduation_year'] ?? 0);
        $line = $n + 1;
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $report['invalid'][] = "Row $line: name or email missing or invalid";
            continue;
        }
        if (!isset(CATEGORY_LABELS[$cat])) {
            $report['invalid'][] = "Row $line ($email): category must be founder, ordinary, honorary or life";
            continue;
        }
        if (isset($seen[$email]) || q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
            $report['duplicates'][] = "Row $line: $email already exists" . (isset($seen[$email]) ? ' earlier in this file' : '');
            continue;
        }
        $seen[$email] = true;
        // Same name and graduation year under another email is flagged, not blocked.
        $dup = q('SELECT u.email FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE LOWER(u.full_name) = LOWER(?) AND (? = 0 OR p.graduation_year = ?) LIMIT 1', [$name, $year, $year])->fetchColumn();
        if ($dup) {
            $report['possible'][] = "Row $line: $name may already exist as $dup";
        }
        if ($dry) {
            $report['added']++;
            continue;
        }
        $pass = temp_password();
        $pdo = db();
        $pdo->beginTransaction();
        q('INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)', [mb_substr($name, 0, 150), $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = (int) $pdo->lastInsertId();
        $cn = strtolower(trim($r['chapter'] ?? ''));
        $cid = $chapterByName[$cn] ?? $chapterByName[$cn . ' chapter'] ?? null;
        $paid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $r['paid_until'] ?? '') ? $r['paid_until'] : null;
        q('INSERT INTO member_profiles (user_id, member_no, category, subscription_status, paid_until, phone, college, programme, graduation_year, chapter_id, directory_consent, admin_notes) VALUES (?,?,?,?,?,?,?,?,?,?,0,?)',
            [$uid, next_member_no(), $cat, $paid ? 'active' : 'unpaid', $paid, mb_substr(trim($r['phone'] ?? ''), 0, 40), mb_substr(trim($r['college'] ?? ''), 0, 150), mb_substr(trim($r['programme'] ?? ''), 0, 150), $year ?: null, $cid, 'Imported from CSV by ' . $me['full_name'] . '.']);
        if ($cid) {
            q("INSERT INTO chapter_members (chapter_id, user_id, status, decided_by, decided_at) VALUES (?,?,'approved',?,NOW())", [$cid, $uid, $me['id']]);
        }
        $pdo->commit();
        if ($approve) {
            approve_member($uid, true);
        }
        $report['added']++;
        $report['created'][] = [$name, $email, $pass];
        if ($sendMail) {
            $mid = (string) q('SELECT membership_id FROM member_profiles WHERE user_id = ?', [$uid])->fetchColumn();
            $ok = portal_welcome_mail([
                'mode' => 'created', 'name' => $name, 'email' => $email, 'password' => $pass,
                'facts' => array_filter(['Category' => CATEGORY_LABELS[$cat] ?? '', 'Membership ID' => $mid]),
                'next' => [$mid ? 'Your membership is approved. You can view your card in the portal.' : 'The Secretariat will confirm your membership and issue your Membership ID.'],
            ]);
            $ok ? $report['emailed']++ : $report['email_failed'][] = $email;
        }
    }
    fclose($h);
    if (!$dry) {
        audit('import_members', $report['added'] . ' added, ' . count($report['duplicates']) . ' duplicates, ' . count($report['invalid']) . ' invalid');
    }
    $report['dry'] = $dry;
}
admin_header('Import and export members', 'Bulk add alumni from a spreadsheet, and download the member list');
?>
<div class="grid two">
  <div>
    <h2>Import from CSV</h2>
    <ol class="steps"><li><a href="?template=1">Download the template</a> and fill one alumnus per row.</li><li>Keep "Test run" ticked first to see duplicates and errors without saving anything.</li><li>Untick it to import. Each member gets a temporary password, shown once below.</li></ol>
    <form method="post" enctype="multipart/form-data" class="stack"><?= csrf_field() ?>
      <label>CSV file <input type="file" name="file" accept=".csv,text/csv" required></label>
      <label class="check"><input type="checkbox" name="dry_run" checked> Test run only (do not save)</label>
      <label class="check"><input type="checkbox" name="send_email" checked> Email each new member a link to the portal and how to sign in (with their temporary password)</label>
      <label class="check"><input type="checkbox" name="approve"> Approve imported members now and issue membership IDs (recorded as approved without a payment)</label>
      <button>Run import</button>
    </form>
    <p class="hint">Existing emails are never overwritten. Up to 2,000 rows per file. Categories: founder, ordinary, honorary, life.</p>
  </div>
  <div>
    <h2>Export members</h2>
    <p><a class="btn" href="?export=1">All members (CSV)</a> <a class="btn secondary" href="?export=1&status=approved">Approved only</a> <a class="btn secondary" href="?export=1&status=pending">Pending only</a></p>
    <p class="meta">Exports are recorded in the audit log. Handle the file as confidential personal data.</p>
    <h2>Possible duplicates already in the system</h2>
    <?php $dups = q("SELECT LOWER(u.full_name) n, COUNT(*) c, GROUP_CONCAT(u.email SEPARATOR ', ') e FROM users u WHERE u.role = 'member' GROUP BY LOWER(u.full_name) HAVING c > 1 LIMIT 20")->fetchAll(); ?>
    <?php foreach ($dups as $d): ?><p style="margin:.3em 0"><strong><?= e($d['n']) ?></strong> (<?= (int) $d['c'] ?>): <span class="meta"><?= e($d['e']) ?></span></p><?php endforeach; if (!$dups): ?><p class="meta">None found: no two members share a name.</p><?php endif; ?>
  </div>
</div>
<?php if ($report): ?>
  <h2><?= $report['dry'] ? 'Test run result (nothing saved)' : 'Import result' ?></h2>
  <div class="notice"><strong><?= (int) $report['added'] ?></strong> <?= $report['dry'] ? 'row(s) would be added' : 'member(s) added' ?>, <strong><?= count($report['duplicates']) ?></strong> duplicate(s) skipped, <strong><?= count($report['invalid']) ?></strong> invalid row(s).</div>
  <?php foreach (['invalid' => 'Invalid rows', 'duplicates' => 'Skipped duplicates', 'possible' => 'Possible duplicates to review (imported anyway)'] as $k => $label): if ($report[$k]): ?>
    <h3><?= e($label) ?></h3><ul><?php foreach (array_slice($report[$k], 0, 100) as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
  <?php endif; endforeach; ?>
  <?php if (!$report['dry'] && $report['created']): ?><div class="notice">Login emails: <strong><?= (int) $report['emailed'] ?></strong> sent<?= $report['email_failed'] ? ', <strong>' . count($report['email_failed']) . '</strong> could not be delivered (see the Mail log): ' . e(implode(', ', array_slice($report['email_failed'], 0, 20))) : '' ?>. The temporary passwords are also listed below so you can share them another way.</div><?php endif; ?>
  <?php if ($report['created']): ?>
    <h3>Temporary passwords (shown once, copy them now)</h3>
    <div class="table-wrap"><table><tr><th>Name</th><th>Email</th><th>Temporary password</th></tr>
      <?php foreach ($report['created'] as [$cn, $ce, $cp]): ?><tr><td><?= e($cn) ?></td><td><?= e($ce) ?></td><td class="mono"><?= e($cp) ?></td></tr><?php endforeach; ?></table></div>
  <?php endif; ?>
<?php endif; ?>
<?php admin_footer(); ?>
