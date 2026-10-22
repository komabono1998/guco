<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth_ui.php';
if (current_user()) {
    redirect('member/');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$u, $err] = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($err) {
        flash($err, 'error');
        redirect('member/login.php');
    }
    redirect('member/');
}
$features = [
    ['id-card', 'Membership card and ID', 'View, print and share your card with a QR code.'],
    ['wallet', 'Payments and renewal', 'Upload proof of payment and keep your subscription active.'],
    ['calendar', 'Events and registration', 'Register for the Dinner, AGM and GUCO League with reminders.'],
    ['map-pin', 'Chapters', 'Join your regional chapter, from Northern Uganda to the Diaspora.'],
    ['target', 'Community projects', 'Propose a project, have it approved and lead it.'],
    ['file', 'Documents and reports', 'Open reports and member-only documents.'],
    ['users', "Members' directory", 'Find and connect with fellow alumni who opted in.'],
    ['life-buoy', 'Support', 'Ask the Secretariat for help or a profile correction.'],
];
auth_page_open('member', 'Member login');
?>
<div class="mem-wrap">
  <section class="mem-show">
    <div class="mem-brand"><img src="<?= e(logo_url()) ?>" alt=""><div><strong><?= e(setting('site_name', 'Gulu University Convocation')) ?></strong><span><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></span></div></div>
    <h2>Your GUCO membership, all in one place</h2>
    <p class="lead">Sign in to your Member Portal to manage your membership and take part in everything GUCO offers.</p>
    <div class="feat-grid">
      <?php foreach ($features as [$ic, $t, $d]): ?>
        <div class="feat"><span class="fi"><?= icon($ic, 20) ?></span><div><b><?= e($t) ?></b><small><?= e($d) ?></small></div></div>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="mem-form">
    <span class="pill"><?= icon('id-card', 14) ?> Member Portal</span>
    <h1>Member login</h1>
    <p class="sub">Welcome back. Enter your details to open your dashboard.</p>
    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <label>Email address <input type="email" name="email" required autofocus autocomplete="username" placeholder="you@example.com"></label>
      <label>Password
        <span class="pw"><input type="password" name="password" id="pw" required autocomplete="current-password"><button type="button" class="eye" onclick="var i=document.getElementById('pw');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'Show':'Hide'">Show</button></span>
      </label>
      <button class="go">Log in to my portal</button>
    </form>
    <div class="or">New to GUCO?</div>
    <a class="create" href="<?= url('register.php') ?>">Create a member account</a>
    <div class="small"><a href="<?= url('forgot.php') ?>">Forgot your password?</a><a href="<?= url() ?>">&larr; Back to the website</a></div>
    <p class="note">Accounts can also be created for you by the Secretariat. If you were given a temporary password, log in and change it under <strong>My profile</strong>. Staff sign in at <a href="<?= url('admin/login.php') ?>">Admin login</a>.</p>
  </section>
</div>
<?php auth_page_close(); ?>
