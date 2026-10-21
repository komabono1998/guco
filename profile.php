<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'My profile';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $year = (int) ($_POST['graduation_year'] ?? 0);
    db()->prepare('INSERT INTO member_profiles (user_id, phone, faculty, course, graduation_year, occupation, location, bio, show_in_directory)
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE phone=VALUES(phone), faculty=VALUES(faculty), course=VALUES(course),
        graduation_year=VALUES(graduation_year), occupation=VALUES(occupation), location=VALUES(location),
        bio=VALUES(bio), show_in_directory=VALUES(show_in_directory)')
        ->execute([
            $me['id'], trim($_POST['phone'] ?? ''), trim($_POST['faculty'] ?? ''), trim($_POST['course'] ?? ''),
            $year ?: null, trim($_POST['occupation'] ?? ''), trim($_POST['location'] ?? ''),
            trim($_POST['bio'] ?? ''), isset($_POST['show_in_directory']) ? 1 : 0,
        ]);
    flash('Profile saved.');
    redirect('profile.php');
}
$s = db()->prepare('SELECT * FROM member_profiles WHERE user_id = ?');
$s->execute([$me['id']]);
$p = $s->fetch() ?: ['show_in_directory' => 1];
$v = fn($k) => e($p[$k] ?? '');
require __DIR__ . '/includes/header.php';
?>
<h1>My profile</h1>
<p><?= e($me['full_name']) ?> &middot; <?= e($me['email']) ?></p>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label>Phone <input name="phone" value="<?= $v('phone') ?>"></label>
  <label>Faculty <input name="faculty" value="<?= $v('faculty') ?>"></label>
  <label>Course <input name="course" value="<?= $v('course') ?>"></label>
  <label>Graduation year <input type="number" name="graduation_year" value="<?= $v('graduation_year') ?>"></label>
  <label>Occupation <input name="occupation" value="<?= $v('occupation') ?>"></label>
  <label>Location <input name="location" value="<?= $v('location') ?>"></label>
  <label>About me <textarea name="bio" rows="4"><?= $v('bio') ?></textarea></label>
  <label class="check"><input type="checkbox" name="show_in_directory" <?= !empty($p['show_in_directory']) ? 'checked' : '' ?>> Show me in the alumni directory</label>
  <button>Save</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
