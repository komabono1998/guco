<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_ui.php';
if (is_staff()) {
    redirect('admin/');
}
$u = twofa_pending();
if (!$u) {
    flash('Please sign in again.', 'error');
    redirect('admin/login.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'verify';
    if ($action === 'cancel') {
        unset($_SESSION['2fa']);
        redirect('admin/login.php');
    }
    if ($action === 'resend') {
        if (time() - (int) ($_SESSION['2fa']['sent'] ?? 0) < 60) {
            flash('Please wait a minute before asking for another code.', 'error');
        } else {
            [$ok, $msg] = twofa_send($u);
            $_SESSION['2fa']['sent'] = time();
            flash($msg, $ok ? 'success' : 'error');
        }
        redirect('admin/verify.php');
    }
    [$ok, $how] = twofa_check($u, $_POST['code'] ?? '');
    if (!$ok) {
        flash($how, 'error');
        redirect('admin/verify.php');
    }
    twofa_complete($u, $how);
    if ($how === 'backup') {
        flash('You signed in with a backup code. ' . twofa_backup_remaining((int) $u['id']) . ' left. You can make new ones under My account.');
    }
    redirect('admin/');
}
auth_page_open('admin', 'Check your email');
?>
<main class="adm-card">
  <div class="brand"><img src="<?= e(logo_url()) ?>" alt=""><div><strong><?= e(config('short_name')) ?> Admin</strong><span><?= e(setting('site_name', 'Gulu University Convocation')) ?></span></div></div>
  <span class="pill"><?= icon('shield', 14) ?> Two-step sign-in</span>
  <h1>Enter your sign-in code</h1>
  <p class="sub">We sent a 6-digit code to <strong><?= e(twofa_mask_email($u['email'])) ?></strong>. It works for <?= (int) TWOFA_CODE_MINUTES ?> minutes. You can also enter one of your backup codes.</p>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="verify">
    <label>Code
      <input name="code" required autofocus inputmode="text" autocomplete="one-time-code" maxlength="12" placeholder="123456" style="font-size:1.6rem;letter-spacing:.3em;text-align:center;font-weight:700">
    </label>
    <button class="go">Verify and sign in</button>
  </form>
  <form method="post" style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
    <?= csrf_field() ?>
    <button name="action" value="resend" style="flex:1;background:#fff;color:var(--green);border:2px solid #cfe0d4;box-shadow:none">Send a new code</button>
    <button name="action" value="cancel" style="flex:1;background:#fff;color:#5b665f;border:2px solid #e3e8ef;box-shadow:none">Start again</button>
  </form>
  <p class="adm-foot" style="margin-top:16px;font-size:.82rem;color:#647087">Did not get the email? Look in your spam folder, wait a minute, then choose "Send a new code".</p>
</main>
<?php auth_page_close(); ?>
