<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_ui.php';
if (is_staff()) {
    redirect('admin/');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$u, $err] = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($u && !(in_array($u['role'], STAFF_ROLES, true) && $u['status'] === 'approved')) {
        $_SESSION = [];
        session_regenerate_id(true);
        $u = null;
        $err = 'This sign-in is for staff accounts. Members can sign in to the Member Portal.';
    }
    if ($err) {
        flash($err, 'error');
        redirect('admin/login.php');
    }
    audit('admin_login', $u['email']);
    redirect('admin/');
}
auth_page_open('admin', 'Admin sign in');
?>
<main class="adm-card">
  <div class="brand"><img src="<?= e(logo_url()) ?>" alt=""><div><strong><?= e(config('short_name')) ?> Admin</strong><span><?= e(setting('site_name', 'Gulu University Convocation')) ?></span></div></div>
  <span class="pill"><?= icon('shield', 14) ?> Staff only</span>
  <h1>Admin sign in</h1>
  <p class="sub">Use your staff account to manage the website, members and events.</p>
  <form method="post" autocomplete="on">
    <?= csrf_field() ?>
    <label>Email address <input type="email" name="email" required autofocus autocomplete="username" placeholder="you@example.com"></label>
    <label>Password
      <span class="pw"><input type="password" name="password" id="pw" required autocomplete="current-password"><button type="button" class="eye" onclick="var i=document.getElementById('pw');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Show':'Hide'">Show</button></span>
    </label>
    <button class="go">Sign in to Admin</button>
  </form>
  <div class="links"><a href="<?= url('forgot.php') ?>">Forgot your password?</a><a href="<?= url('member/login.php') ?>">Member login</a></div>
</main>
<p class="adm-foot"><a href="<?= url() ?>">&larr; Back to the website</a></p>
<?php auth_page_close(); ?>
