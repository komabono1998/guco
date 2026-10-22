<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Join GUCO';
$pageDesc = 'Apply for membership of Gulu University Convocation: personal, academic and employment details, proof of payment and a membership card on approval.';
$open = setting('applications_open') === '1';
$needProof = setting('require_payment_proof', '1') === '1';
$showFees = setting('show_fees') === '1';
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
$f = fn($k) => e($_POST[$k] ?? '');
$sel = fn($k, $v) => ($_POST[$k] ?? '') === (string) $v ? ' selected' : '';
$fees = ['founder' => 'fee_founder', 'ordinary' => 'fee_ordinary', 'honorary' => 'fee_honorary', 'life' => 'fee_life'];

if ($open && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $cat = $_POST['category'] ?? '';
    $year = (int) ($_POST['graduation_year'] ?? 0);
    $dob = $_POST['date_of_birth'] ?? '';
    $emp = $_POST['employment_status'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $amount = (float) ($_POST['amount'] ?? 0);
    $hasProof = ($_FILES['proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $error = null;

    $dobTs = strtotime($dob);
    if (is_spam()) {
        $error = 'Your application could not be submitted. Please try again.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter your full name and a valid email.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!isset(GENDER_LABELS[$gender]) || !$dobTs || $dobTs > strtotime('-18 years')) {
        $error = 'Enter your gender and date of birth. Membership is open to persons above 18 years (Article 5).';
    } elseif (trim($_POST['phone'] ?? '') === '' || trim($_POST['nationality'] ?? '') === '') {
        $error = 'Enter your phone number and nationality.';
    } elseif (!isset(CATEGORY_LABELS[$cat])) {
        $error = 'Select a membership category.';
    } elseif ($cat !== 'honorary' && ($year < 1990 || $year > (int) date('Y') + 1)) {
        $error = 'Enter a valid year of graduation.';
    } elseif (trim($_POST['programme'] ?? '') === '' && $cat !== 'honorary') {
        $error = 'Enter the course or programme you attained.';
    } elseif (!isset(EMPLOYMENT_LABELS[$emp])) {
        $error = 'Select your employment status.';
    } elseif (trim($_POST['eligibility_details'] ?? '') === '') {
        $error = 'Explain how you qualify for the selected category.';
    } elseif ($needProof && (!$hasProof || $amount <= 0 || trim($_POST['reference'] ?? '') === '' || trim($_POST['paid_on'] ?? '') === '')) {
        $error = 'Provide your payment details and upload proof of payment.';
    } elseif (empty($_POST['over18']) || empty($_POST['constitution']) || empty($_POST['consent'])) {
        $error = 'Please tick all the declarations.';
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        $error = 'That email is already registered. Try logging in.';
    }

    $proof = $photo = null;
    if (!$error && $hasProof) {
        $proof = save_private_file($_FILES['proof'], 'proof');
        $error = $proof ? null : 'Proof of payment must be a JPG, PNG, WebP or PDF file under 5MB.';
    }
    if (!$error && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $photo = save_private_file($_FILES['photo'], 'photo');
        $error = $photo ? null : 'Your photo must be a JPG, PNG or WebP image under 3MB.';
    }

    if ($error) {
        flash($error, 'error');
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        q('INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = (int) $pdo->lastInsertId();
        q('INSERT INTO member_profiles (user_id, member_no, category, gender, date_of_birth, nationality, phone, address, location, country,
              college, programme, qualification_level, student_reg_no, graduation_year, eligibility_details,
              employment_status, employer, profession, interests, photo, chapter_id, directory_consent)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $uid, next_member_no(), $cat, $gender, date('Y-m-d', $dobTs), trim($_POST['nationality']), trim($_POST['phone']),
            trim($_POST['address'] ?? ''), trim($_POST['location'] ?? ''), trim($_POST['country'] ?? ''),
            trim($_POST['college'] ?? ''), trim($_POST['programme'] ?? ''), trim($_POST['qualification_level'] ?? ''),
            trim($_POST['student_reg_no'] ?? ''), $year ?: null, trim($_POST['eligibility_details']),
            $emp, trim($_POST['employer'] ?? ''), trim($_POST['profession'] ?? ''),
            implode(', ', array_intersect($_POST['interests'] ?? [], MEMBER_INTERESTS)),
            $photo['name'] ?? null, (int) ($_POST['chapter_id'] ?? 0) ?: null, empty($_POST['directory_consent']) ? 0 : 1,
        ]);
        if ($proof || $amount > 0) {
            q('INSERT INTO member_payments (user_id, purpose, method, amount, reference, paid_on, proof_file, proof_original) VALUES (?,?,?,?,?,?,?,?)', [
                $uid, 'registration', in_array($_POST['method'] ?? '', PAYMENT_METHODS, true) ? $_POST['method'] : 'Other', $amount,
                trim($_POST['reference'] ?? ''), ($_POST['paid_on'] ?? '') ?: null, $proof['name'] ?? null, $proof['original'] ?? null,
            ]);
        }
        $pdo->commit();
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        flash('Application received. The Secretariat will verify your payment and approve your membership; your membership ID is issued on approval.');
        redirect('member/');
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Join GUCO</h1><p>Membership application and proof of payment</p></div>
<?php if (!$open): ?>
  <p class="notice">Online applications are not open at the moment. Please <a href="<?= url('contact.php?category=Membership') ?>">contact the Secretariat</a>.</p>
<?php else: ?>
<p class="meta" style="margin-bottom:16px">Already registered? <a href="<?= url('member/login.php') ?>">Log in to your portal</a>.</p>
<ol class="steps">
  <li><strong>Apply</strong> Fill in this form and upload your proof of payment.</li>
  <li><strong>Verification</strong> The Secretariat checks your details and payment.</li>
  <li><strong>Approval</strong> Your membership ID is issued and your card activated.</li>
  <li><strong>Your card</strong> View and print it from the Member Portal.</li>
</ol>
<p>Membership is open to eligible persons above 18 years under Article 5 of the Constitution, and takes effect on registration and full payment of the applicable fees. See the <a href="<?= url('membership.php#categories') ?>">membership categories</a> to choose yours. Fields marked * are required.</p>

<form method="post" enctype="multipart/form-data" class="stack wide">
  <?= csrf_field() ?><?= spam_fields() ?>

  <fieldset><legend>1. Account</legend>
    <div class="cols">
      <label>Email address * <input type="email" name="email" required autocomplete="email" value="<?= $f('email') ?>"></label>
      <label>Password * <span class="hint">(at least 8 characters)</span> <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    </div>
  </fieldset>

  <fieldset><legend>2. Personal details</legend>
    <label>Full name * <span class="hint">(as it should appear on your card)</span> <input name="full_name" required autocomplete="name" value="<?= $f('full_name') ?>"></label>
    <div class="cols">
      <label>Gender *
        <select name="gender" required><option value="">Select</option>
          <?php foreach (GENDER_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $sel('gender', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Date of birth * <input type="date" name="date_of_birth" required max="<?= date('Y-m-d', strtotime('-18 years')) ?>" value="<?= $f('date_of_birth') ?>"></label>
      <label>Nationality * <input name="nationality" required value="<?= $f('nationality') ?: 'Ugandan' ?>"></label>
    </div>
    <div class="cols">
      <label>Phone number * <input name="phone" required autocomplete="tel" value="<?= $f('phone') ?>"></label>
      <label>Residential address <input name="address" autocomplete="street-address" value="<?= $f('address') ?>"></label>
    </div>
    <div class="cols">
      <label>City / district <input name="location" value="<?= $f('location') ?>"></label>
      <label>Country of residence <input name="country" value="<?= $f('country') ?: 'Uganda' ?>"></label>
    </div>
    <label>Passport-style photo <span class="hint">(for your membership card; JPG, PNG or WebP, up to 3MB)</span>
      <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
  </fieldset>

  <fieldset><legend>3. Academic details</legend>
    <div class="cols">
      <label>College / faculty <input name="college" value="<?= $f('college') ?>"></label>
      <label>Course / programme attained * <input name="programme" value="<?= $f('programme') ?>"></label>
    </div>
    <div class="cols">
      <label>Level of qualification
        <select name="qualification_level"><option value="">Select</option>
          <?php foreach (QUALIFICATION_LEVELS as $l): ?><option<?= $sel('qualification_level', $l) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Year of graduation * <input type="number" name="graduation_year" min="1990" max="<?= (int) date('Y') + 1 ?>" value="<?= $f('graduation_year') ?>"></label>
      <label>Student registration number <span class="hint">(optional)</span> <input name="student_reg_no" value="<?= $f('student_reg_no') ?>"></label>
    </div>
  </fieldset>

  <fieldset><legend>4. Employment</legend>
    <div class="cols">
      <label>Employment status *
        <select name="employment_status" required><option value="">Select</option>
          <?php foreach (EMPLOYMENT_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $sel('employment_status', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Employer / organisation <input name="employer" value="<?= $f('employer') ?>"></label>
      <label>Job title / profession <input name="profession" value="<?= $f('profession') ?>"></label>
    </div>
  </fieldset>

  <fieldset><legend>5. Membership</legend>
    <label>Membership category *
      <select name="category" required>
        <option value="">Select the category you qualify for</option>
        <?php foreach (CATEGORY_LABELS as $k => $l): ?>
          <option value="<?= $k ?>"<?= $sel('category', $k) ?>><?= e($l) . ($showFees ? ' - UGX ' . number_format((int) setting($fees[$k])) : '') ?></option>
        <?php endforeach; ?>
      </select></label>
    <label>How do you qualify for this category? * <span class="hint">(e.g. graduate of Gulu University, staff role, invitation as Honorary Member)</span>
      <textarea name="eligibility_details" rows="3" required><?= $f('eligibility_details') ?></textarea></label>
    <label>Chapter <span class="hint">(optional)</span>
      <select name="chapter_id"><option value="">None / not sure</option>
        <?php foreach ($chapters as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $sel('chapter_id', $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select></label>
    <div><strong class="small">Ways you would like to contribute</strong>
      <div class="checks-grid">
        <?php foreach (MEMBER_INTERESTS as $i): ?>
          <label class="check"><input type="checkbox" name="interests[]" value="<?= e($i) ?>"<?= in_array($i, $_POST['interests'] ?? [], true) ? ' checked' : '' ?>> <?= e($i) ?></label>
        <?php endforeach; ?>
      </div></div>
  </fieldset>

  <fieldset><legend>6. Proof of payment<?= $needProof ? ' *' : '' ?></legend>
    <?php if (setting('payment_instructions')): ?>
      <div class="notice"><strong>Payment instructions</strong><br><?= nl2br(e(setting('payment_instructions'))) ?></div>
    <?php else: ?>
      <p class="notice">Membership fees are set by Article 9 of the Constitution. <a href="<?= url('contact.php?category=Membership') ?>">Ask the Secretariat</a> to confirm the current fee and the approved payment channel before you pay.</p>
    <?php endif; ?>
    <div class="cols">
      <label>Payment method
        <select name="method"><?php foreach (PAYMENT_METHODS as $m): ?><option<?= $sel('method', $m) ?>><?= e($m) ?></option><?php endforeach; ?></select></label>
      <label>Amount paid (UGX) <input type="number" name="amount" min="0" step="any" value="<?= $f('amount') ?>"></label>
    </div>
    <div class="cols">
      <label>Transaction / receipt reference <input name="reference" value="<?= $f('reference') ?>"></label>
      <label>Date paid <input type="date" name="paid_on" max="<?= date('Y-m-d') ?>" value="<?= $f('paid_on') ?>"></label>
    </div>
    <label>Upload proof of payment <span class="hint">(receipt, bank slip or mobile money screenshot; JPG, PNG, WebP or PDF, up to 5MB)</span>
      <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf"></label>
  </fieldset>

  <fieldset><legend>7. Declarations</legend>
    <label class="check"><input type="checkbox" name="over18" required> I am above 18 years of age and the information I have given is true.</label>
    <label class="check"><input type="checkbox" name="constitution" required> I have read the <a href="<?= url('resources.php?q=constitution') ?>">Constitution</a> and agree to observe it and pay the applicable fees and subscriptions.</label>
    <label class="check"><input type="checkbox" name="consent" required> I have read the <a href="<?= url('privacy.php') ?>">privacy notice</a> and agree that GUCO may process my details and documents to handle my application.</label>
    <label class="check"><input type="checkbox" name="directory_consent" <?= !empty($_POST['directory_consent']) ? 'checked' : '' ?>> Optional: list my name, programme, year, profession and location in the members' directory.</label>
  </fieldset>
  <button>Submit application</button>
</form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
