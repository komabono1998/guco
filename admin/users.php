<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('users');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['create'])) {
        $name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($_POST['password'] ?? '') < 8 || !isset(ROLE_LABELS[$role])) {
            flash('Enter a name, valid email, role and a password of at least 8 characters.', 'error');
        } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
            flash('That email already exists.', 'error');
        } else {
            q("INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?,?,?,?,'approved')", [$name, $email, password_hash($_POST['password'], PASSWORD_DEFAULT), $role]);
            q('INSERT INTO member_profiles (user_id) VALUES (?)', [db()->lastInsertId()]);
            audit('create_user', "$email as $role");
            flash('Account created. Ask the user to change the password after first login.');
        }
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $u = q('SELECT * FROM users WHERE id = ?', [$id])->fetch();
        if ($u && $id !== (int) $admin['id'] && isset(ROLE_LABELS[$_POST['role'] ?? ''])) {
            $status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_POST['status'] : $u['status'];
            q('UPDATE users SET role = ?, status = ? WHERE id = ?', [$_POST['role'], $status, $id]);
            audit('update_user', "{$u['email']}: role={$_POST['role']}, status=$status");
            flash('User updated.');
        }
    }
    redirect('admin/users.php');
}
$rows = q("SELECT id, full_name, email, role, status FROM users ORDER BY role = 'member', full_name")->fetchAll();
admin_header('Users & roles');
?>
<h2>Create a staff account</h2>
<form method="post" class="stack">
  <?= csrf_field() ?><input type="hidden" name="create" value="1">
  <div class="cols">
    <label>Full name <input name="full_name" required></label>
    <label>Email <input type="email" name="email" required></label>
  </div>
  <div class="cols">
    <label>Temporary password <input type="text" name="password" required minlength="8"></label>
    <label>Role <select name="role"><?php foreach (ROLE_LABELS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></label>
  </div>
  <button>Create account</button>
</form>
<h2>All accounts</h2>
<div class="table-wrap"><table>
  <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><form method="post" id="u<?= (int) $r['id'] ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"></form><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td>
    <?php if ((int) $r['id'] === (int) $admin['id']): ?>
      <td><?= e(ROLE_LABELS[$r['role']]) ?> (you)</td><td><?= e($r['status']) ?></td><td></td>
    <?php else: ?>
      <td><select name="role" aria-label="Role" form="u<?= (int) $r['id'] ?>"><?php foreach (ROLE_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $r['role'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></td>
      <td><select name="status" aria-label="Status" form="u<?= (int) $r['id'] ?>"><?php foreach (['pending', 'approved', 'rejected'] as $s): ?><option<?= $r['status'] === $s ? ' selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></td>
      <td><button class="secondary" form="u<?= (int) $r['id'] ?>">Save</button></td>
    <?php endif; ?>
  </tr>
  <?php endforeach; ?>
</table></div>
<?php admin_footer(); ?>
