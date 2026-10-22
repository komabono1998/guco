<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_login();
if (!is_staff()) {
    http_response_code(403);
    exit('Staff only.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['password'])) {
        if (!password_verify($_POST['current'] ?? '', $me['password_hash'])) {
            flash('Your current password is incorrect.', 'error');
        } elseif (strlen($_POST['new'] ?? '') < 8) {
            flash('The new password must be at least 8 characters.', 'error');
        } elseif (($_POST['new'] ?? '') !== ($_POST['confirm'] ?? '')) {
            flash('The new passwords do not match.', 'error');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $me['id']]);
            audit('change_password', $me['email']);
            flash('Password changed.');
        }
    } else {
        $name = trim($_POST['full_name'] ?? '');
        if ($name === '') {
            flash('Enter your name.', 'error');
        } else {
            q('UPDATE users SET full_name = ? WHERE id = ?', [$name, $me['id']]);
            flash('Account updated.');
        }
    }
    redirect('admin/account.php');
}
admin_header('My account', 'Your sign-in details and password');
?>
<div class="dgrid">
  <form method="post" class="stack wide w6" style="grid-column:span 6">
    <?= csrf_field() ?>
    <h3 style="margin:0">Profile</h3>
    <label>Full name <input name="full_name" required value="<?= e($me['full_name']) ?>"></label>
    <label>Email <input value="<?= e($me['email']) ?>" disabled></label>
    <label>Role <input value="<?= e(ROLE_LABELS[$me['role']]) ?>" disabled></label>
    <p class="hint">Your email and role are managed by a Super Administrator under Users &amp; roles.</p>
    <button>Save profile</button>
  </form>
  <form method="post" class="stack wide" style="grid-column:span 6">
    <?= csrf_field() ?><input type="hidden" name="password" value="1">
    <h3 style="margin:0">Change password</h3>
    <label>Current password <input type="password" name="current" required autocomplete="current-password"></label>
    <label>New password <span class="hint">(at least 8 characters)</span> <input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
    <label>Confirm new password <input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
    <button>Change password</button>
  </form>
</div>
<?php admin_footer(); ?>
