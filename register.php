<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Join GUCO';
$pageDesc = 'Apply for membership of Gulu University Convocation.';
$open = setting('applications_open') === '1';
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
$f = fn($k) => e($_POST[$k] ?? '');

if ($open && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $year = (int) ($_POST['graduation_year'] ?? 0);
    $cat = $_POST['category'] ?? '';
    $error = null;
    if (is_spam()) {
        $error = 'Your application could not be submitted. Please try again.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter your name and a valid email.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!isset(CATEGORY_LABELS[$cat])) {
        $error = 'Select a membership category.';
    } elseif ($year && ($year < 1990 || $year > (int) date('Y') + 5)) {
        $error = 'Enter a valid graduation year.';
    } elseif (trim($_POST['eligibility_details'] ?? '') === '') {
        $error = 'Please provide your graduation or eligibility details.';
    } elseif (empty($_POST['over18']) || empty($_POST['consent'])) {
        $error = 'Please confirm you are over 18 and accept the privacy notice.';
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        $error = 'That email is already registered. Try logging in.';
    }
    if ($error) {
        flash($error, 'error');
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        q('INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = $pdo->lastInsertId();
        q('INSERT INTO member_profiles (user_id, category, phone, college, programme, graduation_year, eligibility_details, payment_reference, chapter_id, directory_consent)
           VALUES (?,?,?,?,?,?,?,?,?,?)', [
            $uid, $cat, trim($_POST['phone'] ?? ''), trim($_POST['college'] ?? ''), trim($_POST['programme'] ?? ''),
            $year ?: null, trim($_POST['eligibility_details']), trim($_POST['payment_reference'] ?? ''),
            (int) ($_POST['chapter_id'] ?? 0) ?: null, empty($_POST['directory_consent']) ? 0 : 1,
        ]);
        $pdo->commit();
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        flash('Application received. You can follow its status in the Member Portal. The Secretariat will confirm the applicable fees and payment procedure.');
        redirect('portal.php');
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Join GUCO</h1><p>Membership application</p></div>
<?php if (!$open): ?>
  <p class="notice">Online applications are not open at the moment. Please <a href="<?= url('contact.php?category=Membership') ?>">contact the Secretariat</a>.</p>
<?php else: ?>
<p>Membership is open to eligible persons above 18 years under Article 5 of the Constitution. You become a member once the Secretariat verifies your application and the applicable fees are paid. See <a href="<?= url('membership.php') ?>">membership categories</a> for who qualifies.</p>
<form method="post" class="stack wide">
  <?= csrf_field() ?><?= spam_fields() ?>
  <h2>Account</h2>
  <div class="cols">
    <label>Full name <input name="full_name" required autocomplete="name" value="<?= $f('full_name') ?>"></label>
    <label>Email <input type="email" name="email" required autocomplete="email" value="<?= $f('email') ?>"></label>
  </div>
  <label>Password <span class="hint">(at least 8 characters)</span> <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
  <h2>Membership</h2>
  <label>Membership category
    <select name="category" required>
      <option value="">Select the category you qualify for</option>
      <?php foreach (CATEGORY_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= ($_POST['category'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select>
  </label>
  <label>Graduation or eligibility details <span class="hint">(e.g. programme, year of graduation, registration number, or the office or staff role that makes you eligible)</span>
    <textarea name="eligibility_details" rows="3" required><?= $f('eligibility_details') ?></textarea></label>
  <div class="cols">
    <label>College / faculty <input name="college" value="<?= $f('college') ?>"></label>
    <label>Programme / course <input name="programme" value="<?= $f('programme') ?>"></label>
    <label>Graduation year <input type="number" name="graduation_year" min="1990" max="<?= (int) date('Y') + 5 ?>" value="<?= $f('graduation_year') ?>"></label>
  </div>
  <div class="cols">
    <label>Phone <input name="phone" autocomplete="tel" value="<?= $f('phone') ?>"></label>
    <label>Chapter <span class="hint">(optional)</span>
      <select name="chapter_id"><option value="">None / not sure</option>
        <?php foreach ($chapters as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) ($_POST['chapter_id'] ?? 0) === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></label>
  </div>
  <label>Payment reference <span class="hint">(optional - add it once the Secretariat has confirmed fees and the payment channel)</span> <input name="payment_reference" value="<?= $f('payment_reference') ?>"></label>
  <label class="check"><input type="checkbox" name="directory_consent" <?= !empty($_POST['directory_consent']) ? 'checked' : '' ?>> Show my name, programme, year, profession and location in the members' directory (optional; you can change this later).</label>
  <label class="check"><input type="checkbox" name="over18" required> I am above 18 years of age.</label>
  <label class="check"><input type="checkbox" name="consent" required> I have read the <a href="<?= url('privacy.php') ?>">privacy notice</a> and agree that GUCO may process my details to handle my application.</label>
  <button>Submit application</button>
</form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
