<?php
require_once __DIR__ . '/../includes/portal.php';
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
        redirect('member/profile.php');
    }
    $year = (int) ($_POST['graduation_year'] ?? 0);
    $emp = $_POST['employment_status'] ?? '';
    $photo = null;
    if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $photo = save_private_file($_FILES['photo'], 'photo');
        if (!$photo) {
            flash('Your photo must be a JPG, PNG or WebP image under 3MB.', 'error');
            redirect('member/profile.php');
        }
    }
    $own = q('SELECT member_type FROM member_profiles WHERE user_id = ?', [$me['id']])->fetchColumn();
    if ($own === 'staff') {
        // Staff do not edit academic details here; keep whatever is already stored.
        $keep = q('SELECT college, programme, qualification_level, graduation_year, employment_status, employer, profession FROM member_profiles WHERE user_id = ?', [$me['id']])->fetch();
        $_POST['employment_status'] = $keep['employment_status'] ?? '';
        $emp = (string) ($keep['employment_status'] ?? '');
        $_POST['employer'] = $keep['employer'] ?? '';
        $_POST['profession'] = $keep['profession'] ?? '';
        $_POST['college'] = $keep['college'];
        $_POST['programme'] = $keep['programme'];
        $_POST['qualification_level'] = $keep['qualification_level'];
        $year = (int) $keep['graduation_year'];
        $joinedTs = strtotime(trim($_POST['staff_joined_on'] ?? ''));
        if (trim($_POST['staff_position'] ?? '') === '' || trim($_POST['staff_department'] ?? '') === '' || !$joinedTs || $joinedTs > time()) {
            flash('Staff details need the date you joined, your position and your department or unit.', 'error');
            redirect('member/profile.php');
        }
        q('UPDATE member_profiles SET staff_joined_on=?, staff_position=?, staff_department=?, staff_no=?, staff_terms=?, staff_status=? WHERE user_id=?', [
            date('Y-m-d', $joinedTs), trim($_POST['staff_position']), trim($_POST['staff_department']), trim($_POST['staff_no'] ?? '') ?: null,
            isset(STAFF_TERMS[$_POST['staff_terms'] ?? '']) ? $_POST['staff_terms'] : null,
            isset(STAFF_STATUS[$_POST['staff_status'] ?? '']) ? $_POST['staff_status'] : 'current', $me['id'],
        ]);
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
    redirect('member/profile.php');
}
$p = q('SELECT * FROM member_profiles WHERE user_id = ?', [$me['id']])->fetch() ?: [];
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
$v = fn($k) => e($p[$k] ?? '');
$mine = array_map('trim', explode(',', $p['interests'] ?? ''));
portal_header('My profile');
?>
<p class="meta"><?= e($me['full_name']) ?> &middot; <?= e($me['email']) ?><?= !empty($p['membership_id']) ? ' &middot; <span class="mono">' . e($p['membership_id']) . '</span>' : '' ?>. To change your name, email, date of birth or membership category, <a href="support.php?topic=Profile+correction">send a profile-correction request</a>.</p>
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
  <?php if (($p['member_type'] ?? 'alumni') === 'staff'): ?>
  <fieldset><legend>Gulu University staff details</legend>
    <div class="cols">
      <label>Date you joined Gulu University * <input type="date" name="staff_joined_on" required max="<?= date('Y-m-d') ?>" value="<?= $v('staff_joined_on') ?>"></label>
      <label>Position / job title * <input name="staff_position" required value="<?= $v('staff_position') ?>"></label>
    </div>
    <div class="cols">
      <label>Department, faculty or unit * <input name="staff_department" required value="<?= $v('staff_department') ?>"></label>
      <label>Staff number <input name="staff_no" value="<?= $v('staff_no') ?>"></label>
    </div>
    <div class="cols">
      <label>Terms of service
        <select name="staff_terms"><option value="">Select</option>
          <?php foreach (STAFF_TERMS as $k => $l): ?><option value="<?= $k ?>"<?= ($p['staff_terms'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Current status
        <select name="staff_status"><?php foreach (STAFF_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= ($p['staff_status'] ?? 'current') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    </div>
  </fieldset>
  <?php endif; ?>
  <?php if (($p['member_type'] ?? 'alumni') !== 'staff'): ?>
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
  <?php endif; ?>
  <fieldset><legend>Employment and interests</legend>
    <?php if (($p['member_type'] ?? 'alumni') !== 'staff'): ?>
    <div class="cols">
      <label>Employment status
        <select name="employment_status"><option value="">Select</option>
          <?php foreach (EMPLOYMENT_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= ($p['employment_status'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Employer / organisation <input name="employer" value="<?= $v('employer') ?>"></label>
      <label>Job title / profession <input name="profession" value="<?= $v('profession') ?>"></label>
    </div>
    <?php endif; ?>
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
<?php portal_footer(); ?>
