<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Log in';
if (current_user()) {
    redirect('portal.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fails = $_SESSION['login_fails'] ?? [0, 0];
    if ($fails[0] >= 5 && time() - $fails[1] < 300) {
        flash('Too many failed attempts. Please wait a few minutes and try again.', 'error');
    } else {
        $u = q('SELECT * FROM users WHERE email = ?', [strtolower(trim($_POST['email'] ?? ''))])->fetch();
        if (!$u || !password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            $_SESSION['login_fails'] = [$fails[0] + 1, time()];
            flash('Wrong email or password.', 'error');
        } elseif ($u['status'] === 'rejected') {
            flash('Your application was not approved. Please contact the Secretariat.', 'error');
        } else {
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id'];
            unset($_SESSION['login_fails']);
            redirect(in_array($u['role'], STAFF_ROLES, true) && $u['status'] === 'approved' ? 'admin/' : 'portal.php');
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Log in</h1></div>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Email <input type="email" name="email" required autocomplete="username"></label>
  <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
  <button>Log in</button>
</form>
<p><a href="<?= url('forgot.php') ?>">Forgot your password?</a> &middot; Not a member yet? <a href="<?= url('register.php') ?>">Join GUCO</a>.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
