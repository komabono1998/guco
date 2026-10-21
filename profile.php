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
    $emp = $_POST['employment_status'] ?? '';
    $photo = null;
    if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $photo = save_private_file($_FILES['photo'], 'photo');
        if (!$photo) {
            flash('Your photo must be a JPG, PNG or WebP image under 3MB.', 'error');
            redirect('profile.php');
        }
    }
    q('UPDATE member_profiles SET phone=?, address=?, location=?, country=?, nationality=?, college=?, programme=?, qualification_level=?,
        graduation_year=?, employment_status=?, employer=?, profession=?, interests=?, bio=?, chapter_id=?, directory_consent=?' . ($photo ? ', photo=?' : '') . ' WHERE user_id=?',
        array_merge([
            trim($_POST['phone'] ?? ''), trim($_POST['address'] ?? ''), trim($_POST['location'] ?? ''), trim($_POST['country'] ?? ''),
            trim($_POST['nationality'] ?? ''), trim($_POST['college'] ?? ''), trim($_POST['programme'] ?? ''), trim($_POST['qualification_level'] ?? ''),
            $year ?: null, isset(EMPLOYMENT_LABELS[$emp]) ? $emp : null, trim($_POST['employer'] ?? ''), trim($_POST['profession'] ?? ''),
            implode(', ', array_intersect($_POST['interests'] ?? [], MEMBER_INTERESTS)), trim($_POST['bio'] ?? ''),
            (int) ($_POST['chapter_id'] ?? 0) ?: null, empty($_POST['directory_consent']) ? 0 : 1,
        ], $photo ? [$photo['name']] : [], [$me['id']]));
    flash('Profile saved.');
    redirect('profile.php');
}
$p = q('SELECT * FROM member_profiles WHERE user_id = ?', [$me['id']])->fetch() ?: [];
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
$v = fn($k) => e($p[$k] ?? '');
$mine = array_map('trim', explode(',', $p['interests'] ?? ''));
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>My profile</h1><p><?= e($me['full_name']) ?> &middot; <?= e($me['email']) ?><?= !empty($p['membership_id']) ? ' &middot; <span class="mono">' . e($p['membership_id']) . '</span>' : '' ?></p></div>
<p class="meta">To change your name, email, date of birth or membership category, send a <a href="<?= url('contact.php?category=Profile+correction') ?>">profile-correction request</a>.</p>
<form method="post" enctype="multipart/form-data" class="stack wide">
  <?= csrf_field() ?>
  <fieldset><legend>Contact and photo</legend>
    <div class="cols">
      <label>Phone <input name="phone" value="<?= $v('phone') ?>"></label>
      <label>Nationality <input name="nationality" value="<?= $v('nationality') ?>"></label>
    </div>
    <label>Residential address <input name="address" value="<?= $v('address') ?>"></label>
    <div class="cols">
      <label>City / district <input name="location" value="<?= $v('location') ?>"></label>
      <label>Country <input name="country" value="<?= $v('country') ?>"></label>
    </div>
    <div style="display:flex;gap:14px;align-items:center"><?= member_avatar($p + ['user_id' => $me['id'], 'full_name' => $me['full_name']], 'avatar') ?>
      <label style="flex:1">Card photo <span class="hint">(JPG, PNG or WebP, up to 3MB)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label></div>
  </fieldset>
  <fieldset><legend>Academic</legend>
    <div class="cols">
      <label>College / faculty <input name="college" value="<?= $v('college') ?>"></label>
      <label>Course / programme attained <input name="programme" value="<?= $v('programme') ?>"></label>
    </div>
    <div class="cols">
      <label>Level of qualification
        <select name="qualification_level"><option value="">Select</option>
          <?php foreach (QUALIFICATION_LEVELS as $l): ?><option<?= ($p['qualification_level'] ?? '') === $l ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Year of graduation <input type="number" name="graduation_year" value="<?= $v('graduation_year') ?>"></label>
    </div>
  </fieldset>
  <fieldset><legend>Employment and interests</legend>
    <div class="cols">
      <label>Employment status
        <select name="employment_status"><option value="">Select</option>
          <?php foreach (EMPLOYMENT_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= ($p['employment_status'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Employer / organisation <input name="employer" value="<?= $v('employer') ?>"></label>
      <label>Job title / profession <input name="profession" value="<?= $v('profession') ?>"></label>
    </div>
    <label>Chapter
      <select name="chapter_id"><option value="">None</option>
        <?php foreach ($chapters as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) ($p['chapter_id'] ?? 0) === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></label>
    <div><strong class="small">Ways you would like to contribute</strong>
      <div class="checks-grid"><?php foreach (MEMBER_INTERESTS as $i): ?>
        <label class="check"><input type="checkbox" name="interests[]" value="<?= e($i) ?>"<?= in_array($i, $mine, true) ? ' checked' : '' ?>> <?= e($i) ?></label>
      <?php endforeach; ?></div></div>
    <label>About me <textarea name="bio" rows="4"><?= $v('bio') ?></textarea></label>
    <label class="check"><input type="checkbox" name="directory_consent" <?= !empty($p['directory_consent']) ? 'checked' : '' ?>> Show my name, programme, year, profession and location in the members' directory. Your phone and email are never shown.</label>
  </fieldset>
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
