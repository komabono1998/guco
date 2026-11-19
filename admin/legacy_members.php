<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('members');

if (isset($_GET['export'])) {
    $rows = q("SELECT l.guco_no, l.title, l.full_name, l.sex, l.designation, l.status, l.note, l.source, u.email, l.claimed_at FROM legacy_members l LEFT JOIN users u ON u.id = l.claimed_user_id ORDER BY l.guco_no, l.full_name")->fetchAll();
    audit('export_existing_members', count($rows) . ' records');
    csv_download('guco-existing-members-' . date('Ymd') . '.csv', ['GUCO No', 'Title', 'Name', 'Sex', 'Designation', 'Status', 'Note', 'Source', 'Portal email', 'Claimed'], $rows);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'import') {
        $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024 || strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'csv') {
            flash('Choose a CSV file under 2MB with the columns guco_no, title, full_name, sex, designation, note.', 'error');
        } else {
            [$added, $skipped, $disputed, $errors, $other] = legacy_import_csv($f['tmp_name'], trim($_POST['source'] ?? '') ?: 'Imported register');
            audit('import_existing_members', "added $added, skipped $skipped, disputed $disputed");
            flash("Imported: $added added, $skipped already present, $other already present under a different number (noted on their record), $disputed marked as needing the Secretariat.". ($errors ? ' Problems: ' . implode(' ', array_slice($errors, 0, 3)) : ''), $errors ? 'error' : 'success');
        }
        redirect('admin/legacy_members.php');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $row = q('SELECT * FROM legacy_members WHERE id = ?', [$id])->fetch();
    if ($row) {
        if ($action === 'save') {
            $no = legacy_norm_no($_POST['guco_no'] ?? '');
            $des = isset(LEGACY_DESIGNATIONS[$_POST['designation'] ?? '']) ? $_POST['designation'] : $row['designation'];
            $st = in_array($_POST['status'] ?? '', ['unclaimed', 'disputed', 'withdrawn'], true) ? $_POST['status'] : $row['status'];
            if ($row['status'] === 'claimed') {
                $st = 'claimed';
            }
            $name = trim($_POST['full_name'] ?? '');
            if (!$no || $name === '') {
                flash('Enter a valid GUCO number and a name.', 'error');
            } elseif (q('SELECT 1 FROM legacy_members WHERE guco_no = ? AND full_name = ? AND id <> ?', [$no, $name, $id])->fetch()) {
                flash('Another record already has that number and name.', 'error');
            } else {
                q('UPDATE legacy_members SET guco_no=?, title=?, full_name=?, sex=?, designation=?, status=?, note=? WHERE id=?', [
                    $no, mb_substr(trim($_POST['title'] ?? ''), 0, 30), mb_substr($name, 0, 200), in_array($_POST['sex'] ?? '', ['F', 'M'], true) ? $_POST['sex'] : null,
                    $des, $st, mb_substr(trim($_POST['note'] ?? ''), 0, 300) ?: null, $id,
                ]);
                audit('update_existing_member', "{$row['guco_no']} -> $no");
                flash('Record saved.');
            }
        } elseif ($action === 'release' && $row['status'] === 'claimed') {
            q("UPDATE legacy_members SET status = 'unclaimed', claimed_user_id = NULL, claimed_at = NULL WHERE id = ?", [$id]);
            q('UPDATE member_profiles SET legacy_guco_no = NULL WHERE user_id = ?', [$row['claimed_user_id']]);
            audit('release_existing_member', $row['guco_no']);
            flash('The record was released and can be claimed again.');
        } elseif ($action === 'link' && $row['status'] !== 'claimed') {
            $u = q("SELECT u.id FROM users u JOIN member_profiles p ON p.user_id = u.id WHERE u.email = ? AND p.legacy_guco_no IS NULL", [strtolower(trim($_POST['email'] ?? ''))])->fetch();
            if (!$u) {
                flash('No member account with that email, or it is already linked to a GUCO number.', 'error');
            } elseif (q('SELECT 1 FROM member_profiles WHERE member_no = ? AND user_id <> ?', [legacy_number($row), $u['id']])->fetch()) {
                flash('That member number is already used by another account. Change the record number first.', 'error');
            } else {
                q("UPDATE legacy_members SET status = 'claimed', claimed_user_id = ?, claimed_at = NOW() WHERE id = ?", [$u['id'], $id]);
                q('UPDATE member_profiles SET legacy_guco_no = ?, member_no = ? WHERE user_id = ?', [$row['guco_no'], legacy_number($row), $u['id']]);
                audit('link_existing_member', "{$row['guco_no']} -> user {$u['id']}");
                flash('The record was linked to that member account.');
            }
        } elseif ($action === 'delete' && $row['status'] !== 'claimed') {
            q('DELETE FROM legacy_members WHERE id = ?', [$id]);
            audit('delete_existing_member', $row['guco_no']);
            flash('Record deleted.');
        }
    }
    redirect('admin/legacy_members.php' . (!empty($_POST['back']) ? '?' . $_POST['back'] : ''));
}

$statusF = isset(LEGACY_STATUS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$desF = isset(LEGACY_DESIGNATIONS[$_GET['designation'] ?? '']) ? $_GET['designation'] : '';
$search = trim($_GET['q'] ?? '');
$where = '1=1';
$args = [];
if ($statusF) { $where .= ' AND l.status = ?'; $args[] = $statusF; }
if ($desF) { $where .= ' AND l.designation = ?'; $args[] = $desF; }
if ($search !== '') { $where .= ' AND (l.full_name LIKE ? OR l.guco_no LIKE ?)'; array_push($args, "%$search%", "%$search%"); }
$rows = q("SELECT l.*, u.email, u.full_name AS user_name, u.status AS user_status, p.subscription_status, p.paid_until FROM legacy_members l
    LEFT JOIN users u ON u.id = l.claimed_user_id LEFT JOIN member_profiles p ON p.user_id = u.id WHERE $where ORDER BY l.guco_no, l.full_name", $args)->fetchAll();
$counts = q('SELECT status, COUNT(*) FROM legacy_members GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);
$back = http_build_query(array_filter(['status' => $statusF, 'designation' => $desF, 'q' => $search]));
admin_header('Existing members', 'The register of members who already hold a GUCO number');
?>
<div class="tiles" style="margin-bottom:18px">
  <div class="tile"><div><small>On the register</small><b><?= (int) $total ?></b></div></div>
  <div class="tile"><div><small>Portal account created</small><b><?= (int) ($counts['claimed'] ?? 0) ?></b></div></div>
  <div class="tile"><div><small>Not yet claimed</small><b><?= (int) ($counts['unclaimed'] ?? 0) ?></b></div></div>
  <div class="tile"><div><small>Needs the Secretariat</small><b><?= (int) ($counts['disputed'] ?? 0) ?></b></div></div>
</div>
<p class="meta">Members on this register look up their GUCO number on <a href="<?= url('claim.php') ?>" target="_blank" rel="noopener">the Find your record page</a>, confirm their details, renew their subscription and get a portal account that keeps the same number. Approve them from <a href="members.php">Members</a> once their payment is verified, or with a waiver.</p>

<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="Name or GUCO number"></label>
  <label>Status <select name="status"><option value="">All</option><?php foreach (LEGACY_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= $k === $statusF ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
  <label>Designation <select name="designation"><option value="">All</option><?php foreach (LEGACY_DESIGNATIONS as $k => $l): ?><option value="<?= $k ?>"<?= $k === $desF ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
  <button>Filter</button>
  <a class="btn secondary" href="?export=1">Export CSV</a>
</form>

<div class="table-wrap"><table>
  <tr><th>GUCO No.</th><th>Name</th><th>Designation</th><th>Status</th><th>Portal account</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td class="mono"><?= e($r['guco_no']) ?></td>
    <td><?= e(legacy_display_name($r)) ?><?= $r['sex'] ? ' <span class="meta">(' . e($r['sex']) . ')</span>' : '' ?><?= $r['note'] ? '<br><span class="meta">' . e($r['note']) . '</span>' : '' ?></td>
    <td><?= e(LEGACY_DESIGNATIONS[$r['designation']]) ?></td>
    <td><span class="badge <?= $r['status'] === 'claimed' ? '' : ($r['status'] === 'unclaimed' ? 'warn' : 'bad') ?>"><?= e(LEGACY_STATUS[$r['status']]) ?></span></td>
    <td><?php if ($r['claimed_user_id']): ?><a href="members.php?id=<?= (int) $r['claimed_user_id'] ?>"><?= e($r['email']) ?></a><br><span class="meta"><?= e($r['user_status']) ?> &middot; subscription <?= e($r['subscription_status'] ?? 'unpaid') ?><?= $r['paid_until'] ? ' to ' . e(fmt_date($r['paid_until'])) : '' ?></span><?php else: ?><span class="meta">none yet</span><?php endif; ?></td>
    <td>
      <details><summary class="btn secondary" style="display:inline-block">Edit</summary>
        <form method="post" class="stack" style="min-width:260px;margin-top:8px">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
          <label>GUCO No. <input name="guco_no" value="<?= e($r['guco_no']) ?>"></label>
          <label>Title <input name="title" value="<?= e($r['title']) ?>"></label>
          <label>Name <input name="full_name" value="<?= e($r['full_name']) ?>"></label>
          <label>Sex <select name="sex"><option value="">Not stated</option><option value="F"<?= $r['sex'] === 'F' ? ' selected' : '' ?>>F</option><option value="M"<?= $r['sex'] === 'M' ? ' selected' : '' ?>>M</option></select></label>
          <label>Designation <select name="designation"><?php foreach (LEGACY_DESIGNATIONS as $k => $l): ?><option value="<?= $k ?>"<?= $r['designation'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
          <?php if ($r['status'] !== 'claimed'): ?><label>Status <select name="status"><?php foreach (['unclaimed', 'disputed', 'withdrawn'] as $k): ?><option value="<?= $k ?>"<?= $r['status'] === $k ? ' selected' : '' ?>><?= e(LEGACY_STATUS[$k]) ?></option><?php endforeach; ?></select></label><?php endif; ?>
          <label>Note <input name="note" value="<?= e($r['note']) ?>"></label>
          <div class="row-actions">
            <button name="action" value="save">Save</button>
            <?php if ($r['status'] === 'claimed'): ?><button class="secondary" name="action" value="release" onclick="return confirm('Release this record? The member keeps their account but loses the link to this GUCO number.')">Release claim</button>
            <?php else: ?><button class="danger" name="action" value="delete" onclick="return confirm('Delete this record?')">Delete</button><?php endif; ?>
          </div>
          <?php if ($r['status'] !== 'claimed'): ?>
            <label>Link to an existing member account <span class="hint">(by email)</span><input type="email" name="email" placeholder="member@example.com"></label>
            <button class="secondary" name="action" value="link">Link to account</button>
          <?php endif; ?>
        </form>
      </details>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6">No records<?= $total ? ' match those filters' : ' yet. Import a register below' ?>.</td></tr><?php endif; ?>
</table></div>

<h2 style="margin-top:32px">Import a register</h2>
<form method="post" enctype="multipart/form-data" class="stack card" style="max-width:640px">
  <?= csrf_field() ?><input type="hidden" name="action" value="import">
  <p class="meta" style="margin:0">A CSV file with the columns <code>guco_no, title, full_name, sex, designation, note</code> (designation: alumnus, staff or alumnus_staff). People already on the register are skipped, and a number held by more than one person is marked as needing the Secretariat. Registers hold personal data: keep the file private.</p>
  <label>CSV file <input type="file" name="file" accept=".csv" required></label>
  <label>Source label <input name="source" value="Voters Register 2025"></label>
  <button>Import</button>
</form>
<?php admin_footer(); ?>
