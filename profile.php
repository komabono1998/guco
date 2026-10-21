<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'My profile';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['change_password'])) {
        if (!password_verify($_POST['current'] ?? '', $me['password_hash'])) {
            flash('Your current password is incorrect.', 'error');
        } elseif (strlen($_POST['new'] ?? '') < 8) {
            flash('New password must be at least 8 characters.', 'error');
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $me['id']]);
            flash('Password changed.');
        }
        redirect('profile.php');
    }
    $year = (int) ($_POST['graduation_year'] ?? 0);
    q('UPDATE member_profiles SET phone=?, college=?, programme=?, graduation_year=?, profession=?, location=?, bio=?, chapter_id=?, directory_consent=? WHERE user_id=?', [
        trim($_POST['phone'] ?? ''), trim($_POST['college'] ?? ''), trim($_POST['programme'] ?? ''), $year ?: null,
        trim($_POST['profession'] ?? ''), trim($_POST['location'] ?? ''), trim($_POST['bio'] ?? ''),
        (int) ($_POST['chapter_id'] ?? 0) ?: null, empty($_POST['directory_consent']) ? 0 : 1, $me['id'],
    ]);
    flash('Profile saved.');
    redirect('profile.php');
}
$p = q('SELECT * FROM member_profiles WHERE user_id = ?', [$me['id']])->fetch() ?: [];
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
$v = fn($k) => e($p[$k] ?? '');
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>My profile</h1><p><?= e($me['full_name']) ?> &middot; <?= e($me['email']) ?></p></div>
<p class="meta">To change your name, email or membership category, send a <a href="<?= url('contact.php?category=Profile+correction') ?>">profile-correction request</a>.</p>
<form method="post" class="stack wide">
  <?= csrf_field() ?>
  <div class="cols">
    <label>Phone <input name="phone" value="<?= $v('phone') ?>"></label>
    <label>College / faculty <input name="college" value="<?= $v('college') ?>"></label>
    <label>Programme / course <input name="programme" value="<?= $v('programme') ?>"></label>
    <label>Graduation year <input type="number" name="graduation_year" value="<?= $v('graduation_year') ?>"></label>
    <label>Profession <input name="profession" value="<?= $v('profession') ?>"></label>
    <label>Location <input name="location" value="<?= $v('location') ?>"></label>
  </div>
  <label>Chapter
    <select name="chapter_id"><option value="">None</option>
      <?php foreach ($chapters as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) ($p['chapter_id'] ?? 0) === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
    </select></label>
  <label>About me <textarea name="bio" rows="4"><?= $v('bio') ?></textarea></label>
  <label class="check"><input type="checkbox" name="directory_consent" <?= !empty($p['directory_consent']) ? 'checked' : '' ?>> Show my name, programme, year, profession and location in the members' directory. Your phone and email are never shown.</label>
  <button>Save profile</button>
</form>

<section class="section">
  <h2>Change password</h2>
  <form method="post" class="stack">
    <?= csrf_field() ?><input type="hidden" name="change_password" value="1">
    <label>Current password <input type="password" name="current" required autocomplete="current-password"></label>
    <label>New password <input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
    <button>Change password</button>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
