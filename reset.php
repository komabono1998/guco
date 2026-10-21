<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Choose a new password';
$token = $_GET['token'] ?? '';
$row = q('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()', [hash('sha256', $token)])->fetch();
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pass = $_POST['password'] ?? '';
    if (strlen($pass) < 8) {
        flash('Password must be at least 8 characters.', 'error');
    } else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $row['user_id']]);
        q('UPDATE password_resets SET used = 1 WHERE user_id = ?', [$row['user_id']]);
        flash('Password updated. You can now log in.');
        redirect('login.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Choose a new password</h1></div>
<?php if (!$row): ?>
  <p>This reset link is invalid or has expired. <a href="<?= url('forgot.php') ?>">Request a new one</a>.</p>
<?php else: ?>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>New password <span class="hint">(at least 8 characters)</span> <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <button>Update password</button>
</form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
