<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = require_admin();
$pageTitle = 'Members';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) $_POST['id'];
    $action = $_POST['action'] ?? '';
    if ($id !== (int) $admin['id']) {
        if (in_array($action, ['approved', 'rejected'], true)) {
            db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$action, $id]);
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        }
    }
    redirect('admin/members.php');
}
$rows = db()->query("SELECT u.*, p.graduation_year, p.faculty FROM users u LEFT JOIN member_profiles p ON p.user_id = u.id
    ORDER BY FIELD(u.status,'pending','approved','rejected'), u.created_at DESC")->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>Members</h1>
<table>
  <tr><th>Name</th><th>Email</th><th>Faculty</th><th>Year</th><th>Status</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['faculty']) ?></td>
    <td><?= e($r['graduation_year']) ?></td><td><?= e($r['status']) ?><?= $r['role'] === 'admin' ? ' (admin)' : '' ?></td>
    <td class="row-actions">
      <?php if ($r['id'] != $admin['id']): foreach (['approved' => 'Approve', 'rejected' => 'Reject', 'delete' => 'Delete'] as $a => $label): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button name="action" value="<?= $a ?>" class="<?= $a === 'delete' ? 'danger' : 'secondary' ?>"
            <?= $a === 'delete' ? 'onclick="return confirm(\'Delete this member?\')"' : '' ?>><?= $label ?></button></form>
      <?php endforeach; endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
