<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_login();
if (!is_staff()) {
    http_response_code(403);
    exit('Staff only.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'backup_codes') {
        if (!password_verify($_POST['current'] ?? '', $me['password_hash'])) {
            flash('Your password is incorrect, so no backup codes were made.', 'error');
        } else {
            $_SESSION['new_backup_codes'] = twofa_make_backup_codes((int) $me['id']);
            audit('admin_backup_codes', 'new backup codes generated');
            flash('New backup codes were made. Save them now: they are shown only once.');
        }
        redirect('admin/account.php');
    }
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
$newCodes = $_SESSION['new_backup_codes'] ?? null;
unset($_SESSION['new_backup_codes']);
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

<section class="card" style="max-width:860px;margin-top:22px">
  <h3 style="margin-top:0">Two-step sign-in</h3>
  <p><?= twofa_enabled() ? 'Every time you sign in, a 6-digit code is emailed to <strong>' . e(twofa_mask_email($me['email'])) . '</strong> after your password. This protects the admin area even if someone learns your password.' : '<strong>Two-step sign-in is currently switched off for the whole site.</strong> A Super Administrator can switch it on under Settings, Security.' ?></p>
  <p class="meta">Backup codes let you sign in if the email does not arrive. Each works once. You have <strong><?= twofa_backup_remaining((int) $me['id']) ?></strong> unused backup code(s).</p>
  <?php if ($newCodes): ?>
    <div class="notice" style="background:#fff8e1"><strong>Your new backup codes. Save them somewhere safe now: this is the only time they are shown.</strong>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;margin:12px 0;font:700 1.1rem Consolas,monospace"><?php foreach ($newCodes as $c): ?><span style="background:#fff;border:1px dashed #b45309;border-radius:8px;padding:8px 10px;text-align:center"><?= e($c) ?></span><?php endforeach; ?></div>
      <button type="button" class="secondary" onclick="window.print()">Print these codes</button>
    </div>
  <?php endif; ?>
  <form method="post" class="stack" style="max-width:420px">
    <?= csrf_field() ?><input type="hidden" name="action" value="backup_codes">
    <label>Your password <span class="hint">(to confirm it is you)</span> <input type="password" name="current" required autocomplete="current-password"></label>
    <button>Make new backup codes</button>
    <p class="hint">Making new codes cancels all the old ones.</p>
  </form>
</section>
<?php admin_footer(); ?>
