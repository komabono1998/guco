<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Reset password';
$devLink = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $u = q('SELECT id, email FROM users WHERE email = ?', [$email])->fetch();
    if ($u && !is_spam()) {
        $token = bin2hex(random_bytes(24));
        q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 1 HOUR))', [$u['id'], hash('sha256', $token)]);
        $link = (($_SERVER['HTTPS'] ?? '') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('reset.php?token=' . $token);
        $sent = send_mail($u['email'], 'GUCO password reset', "Use this link within one hour to choose a new password:\n\n$link\n\nIf you did not ask for this, ignore this email.");
        if (!$sent && config('dev_mode')) {
            $devLink = $link;
        }
    }
    if (!$devLink) {
        flash('If that email is registered, a reset link has been sent.');
        redirect('forgot.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Reset your password</h1></div>
<?php if ($devLink): ?>
  <p class="notice"><strong>Development mode:</strong> email is not configured, so here is your reset link: <a href="<?= e($devLink) ?>"><?= e($devLink) ?></a></p>
<?php endif; ?>
<form method="post" class="stack">
  <?= csrf_field() ?><?= spam_fields() ?>
  <label>Email <input type="email" name="email" required autocomplete="email"></label>
  <button>Send reset link</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
