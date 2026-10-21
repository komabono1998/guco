<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('chapters');

// Coordinators only see their own chapters; the Secretariat and Super Administrator see all.
$all = can('chapters_all');
$allowed = $all
    ? q('SELECT id, name FROM chapters ORDER BY sort_order, name')->fetchAll(PDO::FETCH_KEY_PAIR)
    : q('SELECT id, name FROM chapters WHERE coordinator_user_id = ? ORDER BY sort_order, name', [$me['id']])->fetchAll(PDO::FETCH_KEY_PAIR);
$ids = array_keys($allowed) ?: [0];
$in = implode(',', array_map('intval', $ids));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = q("SELECT m.*, u.email, u.full_name FROM chapter_members m JOIN users u ON u.id = m.user_id WHERE m.id = ? AND m.chapter_id IN ($in)", [(int) ($_POST['id'] ?? 0)])->fetch();
    if ($row) {
        $action = $_POST['action'] ?? '';
        $note = mb_substr(trim($_POST['note'] ?? ''), 0, 300);
        if ($action === 'approve') {
            q("UPDATE chapter_members SET status = 'approved', decision_note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?", [$note ?: null, $me['id'], $row['id']]);
            chapter_activate_primary((int) $row['user_id'], (int) $row['chapter_id']);
            send_mail($row['email'], 'Your GUCO chapter registration is approved', "Dear {$row['full_name']},\n\nYour registration in the {$allowed[$row['chapter_id']]} has been approved. Log in to the Member Portal to see the chapter details.\n\nGulu University Convocation");
            audit('approve_chapter_member', "{$row['email']} -> chapter {$row['chapter_id']}");
            flash('Registration approved.');
        } elseif ($action === 'reject') {
            q("UPDATE chapter_members SET status = 'rejected', role = 'member', decision_note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?", [$note ?: null, $me['id'], $row['id']]);
            chapter_refresh_primary((int) $row['user_id']);
            audit('reject_chapter_member', "{$row['email']} -> chapter {$row['chapter_id']}");
            flash('Registration declined.');
        } elseif ($action === 'remove') {
            q("UPDATE chapter_members SET status = 'left', role = 'member', decision_note = ?, decided_by = ?, decided_at = NOW() WHERE id = ?", [$note ?: null, $me['id'], $row['id']]);
            chapter_refresh_primary((int) $row['user_id']);
            audit('remove_chapter_member', "{$row['email']} <- chapter {$row['chapter_id']}");
            flash('Member removed from the chapter.');
        } elseif ($action === 'role' && $row['status'] === 'approved' && isset(CHAPTER_ROLES[$_POST['role'] ?? ''])) {
            q('UPDATE chapter_members SET role = ? WHERE id = ?', [$_POST['role'], $row['id']]);
            audit('set_chapter_role', "{$row['email']} = {$_POST['role']} in chapter {$row['chapter_id']}");
            flash('Chapter role updated.');
        }
    }
    redirect('admin/chapter_members.php?' . http_build_query(array_filter(['chapter' => $_POST['chapter'] ?? '', 'status' => $_POST['fstatus'] ?? ''])));
}

$chapterF = (int) ($_GET['chapter'] ?? 0);
$statusF = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = "m.chapter_id IN ($in)";
$args = [];
if ($chapterF && isset($allowed[$chapterF])) {
    $where .= ' AND m.chapter_id = ?';
    $args[] = $chapterF;
}
if (in_array($statusF, ['pending', 'approved', 'rejected', 'left'], true)) {
    $where .= ' AND m.status = ?';
    $args[] = $statusF;
}
if ($search !== '') {
    $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR m.district LIKE ?)';
    array_push($args, "%$search%", "%$search%", "%$search%");
}
$rows = q("SELECT m.*, u.full_name, u.email, p.phone, p.member_no, p.membership_id, p.profession, c.name AS chapter
           FROM chapter_members m JOIN users u ON u.id = m.user_id LEFT JOIN member_profiles p ON p.user_id = u.id JOIN chapters c ON c.id = m.chapter_id
           WHERE $where ORDER BY FIELD(m.status,'pending','approved','rejected','left'), m.created_at DESC", $args)->fetchAll();

if (isset($_GET['export'])) {
    audit('export_chapter_members', count($rows) . ' records');
    csv_download('guco-chapter-members-' . date('Ymd') . '.csv', ['Chapter', 'Member no', 'Name', 'Email', 'Phone', 'Status', 'Role', 'District', 'Country', 'Availability', 'Interests', 'Leadership interest', 'Registered'],
        array_map(fn($r) => [$r['chapter'], member_no_label($r['member_no']), $r['full_name'], $r['email'], $r['phone'], $r['status'], CHAPTER_ROLES[$r['role']], $r['district'], $r['country'], $r['availability'], $r['interests'], $r['leadership_interest'] ? 'yes' : 'no', $r['created_at']], $rows));
}
$badge = ['pending' => 'warn', 'approved' => '', 'rejected' => 'bad', 'left' => 'bad'];
admin_header('Chapter members');
?>
<?php if (!$allowed): ?><p class="notice">No chapters are assigned to you yet. Ask the Secretariat to set you as a chapter coordinator.</p><?php endif; ?>
<form method="get" class="filters">
  <label>Chapter <select name="chapter"><option value="">All<?= $all ? '' : ' my chapters' ?></option><?php foreach ($allowed as $k => $n): ?><option value="<?= (int) $k ?>"<?= $k == $chapterF ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
  <label>Status <select name="status"><option value="">All</option><?php foreach (['pending', 'approved', 'rejected', 'left'] as $s): ?><option<?= $s === $statusF ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></label>
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="Name, email, district"></label>
  <button>Filter</button>
  <a class="btn secondary" href="?<?= e(http_build_query(array_filter(['chapter' => $chapterF, 'status' => $statusF, 'q' => $search]) + ['export' => 1])) ?>">Export CSV</a>
</form>
<div class="table-wrap"><table>
  <tr><th>Member</th><th>Chapter</th><th>Where / how they will help</th><th>Status</th><th>Decision</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><strong><?= e($r['full_name']) ?></strong><br><span class="meta"><?= e(member_no_label($r['member_no'])) ?> &middot; <?= e($r['profession']) ?></span><br>
      <span class="meta"><?= e($r['email']) ?><?= $r['phone'] ? ' &middot; ' . e($r['phone']) : '' ?></span><br>
      <span class="meta"><?= $r['share_contact'] ? 'Agreed to share contact with leaders' : 'Contact not shared with chapter' ?></span></td>
    <td><?= e($r['chapter']) ?></td>
    <td><?= e(trim($r['district'] . ($r['country'] ? ', ' . $r['country'] : ''), ', ')) ?><br><span class="meta"><?= e($r['availability']) ?><?= $r['interests'] ? ' &middot; ' . e($r['interests']) : '' ?></span>
      <?= $r['leadership_interest'] ? '<br><span class="badge">Interested in leadership</span>' : '' ?>
      <?php if ($r['motivation']): ?><br><span class="meta">&ldquo;<?= e(excerpt($r['motivation'], 140)) ?>&rdquo;</span><?php endif; ?></td>
    <td><span class="badge <?= $badge[$r['status']] ?>"><?= e($r['status']) ?></span><?= $r['role'] !== 'member' ? '<br><span class="badge">' . e(CHAPTER_ROLES[$r['role']]) . '</span>' : '' ?>
      <?= $r['decision_note'] ? '<br><span class="meta">' . e($r['decision_note']) . '</span>' : '' ?></td>
    <td>
      <form method="post" class="stack" style="gap:6px;min-width:210px">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="chapter" value="<?= $chapterF ?: '' ?>"><input type="hidden" name="fstatus" value="<?= e($statusF) ?>">
        <?php if ($r['status'] === 'approved'): ?>
          <select name="role" aria-label="Chapter role"><?php foreach (CHAPTER_ROLES as $k => $l): ?><option value="<?= $k ?>"<?= $r['role'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
          <div class="row-actions"><button class="secondary" name="action" value="role">Set role</button><button class="danger" name="action" value="remove" onclick="return confirm('Remove this member from the chapter?')">Remove</button></div>
        <?php elseif ($r['status'] === 'pending'): ?>
          <input name="note" placeholder="Note (optional)">
          <div class="row-actions"><button name="action" value="approve">Approve</button><button class="danger" name="action" value="reject">Decline</button></div>
        <?php elseif ($r['status'] === 'rejected'): ?>
          <button class="secondary" name="action" value="approve">Approve instead</button>
        <?php endif; ?>
      </form>
    </td>
  </tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="5">No registrations found.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
