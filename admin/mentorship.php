<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_perm('mentorship');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $p = q('SELECT * FROM mentorship_profiles WHERE user_id = ?', [$uid])->fetch();
    if ($p && in_array($action, ['remove', 'restore'], true)) {
        q('UPDATE mentorship_profiles SET status = ? WHERE user_id = ?', [$action === 'remove' ? 'removed' : 'open', $uid]);
        if ($action === 'remove') {
            q("UPDATE mentorship_requests SET status = 'ended', responded_at = NOW() WHERE (mentor_id = ? OR mentee_id = ?) AND status IN ('pending','accepted')", [$uid, $uid]);
        }
        audit($action === 'remove' ? 'mentorship_remove' : 'mentorship_restore', "user $uid");
        notify($uid, $action === 'remove' ? 'Mentorship profile removed' : 'Mentorship profile restored', $action === 'remove' ? 'Please contact support if you have questions.' : 'You can take part in the hub again.', 'member/mentorship.php');
        flash($action === 'remove' ? 'Profile removed and active requests closed.' : 'Profile restored.');
    }
    redirect('admin/mentorship.php');
}
$rows = q('SELECT m.*, u.full_name, u.email, pr.programme, pr.graduation_year,
    (SELECT COUNT(*) FROM mentorship_requests r WHERE (r.mentor_id = m.user_id OR r.mentee_id = m.user_id) AND r.status = "accepted") AS pairs
    FROM mentorship_profiles m JOIN users u ON u.id = m.user_id JOIN member_profiles pr ON pr.user_id = u.id ORDER BY m.status = "removed", m.created_at DESC')->fetchAll();
$stat = q("SELECT SUM(role IN ('mentor','both')) mentors, SUM(role IN ('mentee','both')) mentees FROM mentorship_profiles WHERE status <> 'removed'")->fetch();
$req = q("SELECT SUM(status='pending') p, SUM(status='accepted') a, COUNT(*) t FROM mentorship_requests")->fetch();
if (isset($_GET['export'])) {
    audit('export_mentorship', count($rows) . ' profiles');
    csv_download('guco-mentorship-' . date('Ymd') . '.csv', ['Name', 'Email', 'Role', 'Areas', 'Status', 'Active pairings', 'Joined'],
        array_map(fn($r) => [$r['full_name'], $r['email'], $r['role'], $r['areas'], $r['status'], $r['pairs'], $r['created_at']], $rows));
}
admin_header('Mentorship Hub', 'Oversee mentors and mentees; remove a profile if there is a conduct concern');
?>
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:14px">
  <div class="card"><strong><?= (int) $stat['mentors'] ?></strong><br><span class="meta">Mentors</span></div>
  <div class="card"><strong><?= (int) $stat['mentees'] ?></strong><br><span class="meta">Mentees</span></div>
  <div class="card"><strong><?= (int) $req['a'] ?></strong><br><span class="meta">Active pairings (<?= (int) $req['p'] ?> requests pending)</span></div>
</div>
<p><a href="?export=1">Export CSV</a></p>
<div class="table-wrap"><table>
  <tr><th>Member</th><th>Role</th><th>Areas</th><th>Pairings</th><th>Status</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr><td><a href="<?= e(url('admin/members.php?id=' . (int) $r['user_id'])) ?>"><?= e($r['full_name']) ?></a><br><span class="meta"><?= e($r['programme']) ?> <?= $r['graduation_year'] ? '(' . (int) $r['graduation_year'] . ')' : '' ?></span></td>
    <td><?= e(ucfirst($r['role'])) ?></td><td><?= e($r['areas']) ?></td><td><?= (int) $r['pairs'] ?></td>
    <td><span class="badge <?= $r['status'] === 'open' ? '' : ($r['status'] === 'paused' ? 'warn' : 'bad') ?>"><?= e($r['status']) ?></span></td>
    <td><form method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['user_id'] ?>">
      <?php if ($r['status'] === 'removed'): ?><button class="secondary" name="action" value="restore">Restore</button>
      <?php else: ?><button class="danger" name="action" value="remove" onclick="return confirm('Remove this profile and close their active requests?')">Remove</button><?php endif; ?></form></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="6">Nobody has joined yet. Members can join from their portal.</td></tr><?php endif; ?>
</table></div>
<?php admin_footer(); ?>
