<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Log in';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = db()->prepare('SELECT * FROM users WHERE email = ?');
    $s->execute([strtolower(trim($_POST['email'] ?? ''))]);
    $u = $s->fetch();
    if (!$u || !password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        flash('Wrong email or password.', 'error');
    } elseif ($u['status'] !== 'approved') {
        flash($u['status'] === 'pending' ? 'Your membership is awaiting approval.' : 'Your registration was not approved.', 'error');
    } else {
        session_regenerate_id(true);
        $_SESSION['uid'] = $u['id'];
        redirect($u['role'] === 'admin' ? 'admin/' : 'profile.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<h1>Log in</h1>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Email <input type="email" name="email" required></label>
  <label>Password <input type="password" name="password" required></label>
  <button>Log in</button>
</form>
<p>Not a member yet? <a href="<?= url('register.php') ?>">Join</a>.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
