<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Join GUCO';
$pageDesc = 'Apply for membership of Gulu University Convocation: personal, academic and employment details, proof of payment and a membership card on approval.';
$open = setting('applications_open') === '1';
$needProof = setting('require_payment_proof', '1') === '1';
$showFees = setting('show_fees') === '1';
$allowLater = setting('allow_pay_later', '1') === '1';
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
$f = fn($k) => e($_POST[$k] ?? '');
$sel = fn($k, $v) => ($_POST[$k] ?? ($k === 'category' ? ($_GET['category'] ?? '') : '')) === (string) $v ? ' selected' : '';
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
    $type = ($_POST['member_type'] ?? '') === 'staff' ? 'staff' : 'alumni';
    $payLater = $allowLater && ($_POST['pay_when'] ?? '') === 'later';
    $joined = trim($_POST['staff_joined_on'] ?? '');
    $joinedTs = $joined !== '' ? strtotime($joined) : false;
    $terms = isset(STAFF_TERMS[$_POST['staff_terms'] ?? '']) ? $_POST['staff_terms'] : null;
    $staffStatus = isset(STAFF_STATUS[$_POST['staff_status'] ?? '']) ? $_POST['staff_status'] : 'current';
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
    } elseif ($type === 'alumni' && $cat !== 'honorary' && ($year < 1990 || $year > (int) date('Y') + 1)) {
        $error = 'Enter a valid year of graduation.';
    } elseif ($type === 'alumni' && trim($_POST['programme'] ?? '') === '' && $cat !== 'honorary') {
        $error = 'Enter the course or programme you attained.';
    } elseif ($type === 'staff' && $year && ($year < 1950 || $year > (int) date('Y') + 1)) {
        $error = 'Enter a valid year of graduation or leave it blank.';
    } elseif ($type === 'staff' && (!$joinedTs || $joinedTs > time() || $joinedTs < strtotime('1950-01-01'))) {
        $error = 'Enter the date you joined Gulu University.';
    } elseif ($type === 'staff' && (trim($_POST['staff_position'] ?? '') === '' || trim($_POST['staff_department'] ?? '') === '')) {
        $error = 'Enter your position and your department, faculty or unit at Gulu University.';
    } elseif (!isset(EMPLOYMENT_LABELS[$emp])) {
        $error = 'Select your employment status.';
    } elseif (trim($_POST['eligibility_details'] ?? '') === '') {
        $error = 'Explain how you qualify for the selected category.';
    } elseif ($needProof && !$payLater && (!$hasProof || $amount <= 0 || trim($_POST['reference'] ?? '') === '' || trim($_POST['paid_on'] ?? '') === '')) {
        $error = 'Provide your payment details and upload proof of payment.';
    } elseif (empty($_POST['over18']) || empty($_POST['constitution']) || empty($_POST['consent'])) {
        $error = 'Please tick all the declarations.';
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        $error = 'That email is already registered. Try logging in.';
    }

    $proof = $photo = null;
    if (!$error && $hasProof && !$payLater) {
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
              employment_status, employer, profession, interests, photo, chapter_id, directory_consent,
              member_type, staff_joined_on, staff_position, staff_department, staff_no, staff_terms, staff_status, pay_later)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $uid, next_member_no(), $cat, $gender, date('Y-m-d', $dobTs), trim($_POST['nationality']), trim($_POST['phone']),
            trim($_POST['address'] ?? ''), trim($_POST['location'] ?? ''), trim($_POST['country'] ?? ''),
            trim($_POST['college'] ?? ''), trim($_POST['programme'] ?? ''), trim($_POST['qualification_level'] ?? ''),
            trim($_POST['student_reg_no'] ?? ''), $year ?: null, trim($_POST['eligibility_details']),
            $emp, trim($_POST['employer'] ?? '') ?: ($type === 'staff' ? 'Gulu University' : ''), trim($_POST['profession'] ?? '') ?: ($type === 'staff' ? trim($_POST['staff_position']) : ''),
            implode(', ', array_intersect($_POST['interests'] ?? [], MEMBER_INTERESTS)),
            $photo['name'] ?? null, (int) ($_POST['chapter_id'] ?? 0) ?: null, empty($_POST['directory_consent']) ? 0 : 1,
            $type, $type === 'staff' ? date('Y-m-d', $joinedTs) : null, $type === 'staff' ? trim($_POST['staff_position']) : null,
            $type === 'staff' ? trim($_POST['staff_department']) : null, $type === 'staff' ? (trim($_POST['staff_no'] ?? '') ?: null) : null,
            $type === 'staff' ? $terms : null, $type === 'staff' ? $staffStatus : null, $payLater ? 1 : 0,
        ]);
        if (!$payLater && ($proof || $amount > 0)) {
            q('INSERT INTO member_payments (user_id, purpose, method, amount, reference, paid_on, proof_file, proof_original) VALUES (?,?,?,?,?,?,?,?)', [
                $uid, 'registration', in_array($_POST['method'] ?? '', PAYMENT_METHODS, true) ? $_POST['method'] : 'Other', $amount,
                trim($_POST['reference'] ?? ''), ($_POST['paid_on'] ?? '') ?: null, $proof['name'] ?? null, $proof['original'] ?? null,
            ]);
        }
        $pdo->commit();
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        flash($payLater
            ? 'Application received. Your payment is still to come: pay through the approved channel and upload your proof under Payments in your portal. Your membership is approved once the payment is verified.'
            : 'Application received. The Secretariat will verify your payment and approve your membership; your membership ID is issued on approval.');
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
  <li><strong>Apply</strong> Fill in this form. Upload your proof of payment now, or choose to pay later.</li>
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

  <fieldset><legend>2. Member type</legend>
    <p class="meta" style="margin-top:0">GUCO brings together Gulu University alumni and staff. Choose the one that describes you. Staff who are also graduates can add their academic details too.</p>
    <div class="type-pick" role="radiogroup" aria-label="Member type">
      <?php $mt = $_POST['member_type'] ?? ($_GET['type'] ?? 'alumni'); foreach (MEMBER_TYPES as $k => $l): ?>
        <label class="type-card"><input type="radio" name="member_type" value="<?= $k ?>"<?= $mt === $k ? ' checked' : '' ?>><span class="ic"><?= icon($k === 'staff' ? 'briefcase' : 'graduation', 24) ?></span><b><?= e($l) ?></b>
          <small><?= $k === 'staff' ? 'Academic, administrative and support staff, serving, retired or former.' : 'Graduates and former students of the University.' ?></small></label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <fieldset><legend>3. Personal details</legend>
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

  <fieldset id="fsStaff"><legend>4. Gulu University staff details</legend>
    <div class="cols">
      <label>Date you joined Gulu University * <input type="date" name="staff_joined_on" max="<?= date('Y-m-d') ?>" value="<?= $f('staff_joined_on') ?>"></label>
      <label>Position / job title * <input name="staff_position" placeholder="e.g. Lecturer, Accountant, Librarian" value="<?= $f('staff_position') ?>"></label>
    </div>
    <div class="cols">
      <label>Department, faculty or unit * <input name="staff_department" value="<?= $f('staff_department') ?>"></label>
      <label>Staff number <span class="hint">(optional)</span> <input name="staff_no" value="<?= $f('staff_no') ?>"></label>
    </div>
    <div class="cols">
      <label>Terms of service
        <select name="staff_terms"><option value="">Select</option>
          <?php foreach (STAFF_TERMS as $k => $l): ?><option value="<?= $k ?>"<?= $sel('staff_terms', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Current status
        <select name="staff_status"><?php foreach (STAFF_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= $sel('staff_status', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    </div>
  </fieldset>

  <fieldset id="fsAcademic"><legend><span id="acLegend">4. Academic details</span></legend>
    <p class="meta" id="acHint" hidden style="margin-top:0">Fill this in only if you are also a graduate of Gulu University.</p>
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

  <fieldset><legend>5. Employment</legend>
    <div class="cols">
      <label>Employment status *
        <select name="employment_status" required><option value="">Select</option>
          <?php foreach (EMPLOYMENT_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $sel('employment_status', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Employer / organisation <input name="employer" value="<?= $f('employer') ?>"></label>
      <label>Job title / profession <input name="profession" value="<?= $f('profession') ?>"></label>
    </div>
  </fieldset>

  <fieldset><legend>6. Membership</legend>
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

  <fieldset><legend>7. Payment<?= $needProof ? ' *' : '' ?></legend>
    <?php if ($allowLater): ?>
    <div class="type-pick pay-pick" role="radiogroup" aria-label="When will you pay">
      <?php $pw = $_POST['pay_when'] ?? 'now'; ?>
      <label class="type-card"><input type="radio" name="pay_when" value="now"<?= $pw !== 'later' ? ' checked' : '' ?>><span class="ic"><?= icon('wallet', 24) ?></span><b>I have paid</b><small>Enter your payment details and upload the proof now.</small></label>
      <label class="type-card"><input type="radio" name="pay_when" value="later"<?= $pw === 'later' ? ' checked' : '' ?>><span class="ic"><?= icon('clock', 24) ?></span><b>Register now, pay later</b><small>Submit the application today and upload your proof from your portal when you have paid. Approval waits for the verified payment.</small></label>
    </div>
    <?php endif; ?>
    <div id="payNow">
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
    </div>
  </fieldset>

  <fieldset><legend>8. Declarations</legend>
    <label class="check"><input type="checkbox" name="over18" required> I am above 18 years of age and the information I have given is true.</label>
    <label class="check"><input type="checkbox" name="constitution" required> I have read the <a href="<?= url('resources.php?q=constitution') ?>">Constitution</a> and agree to observe it and pay the applicable fees and subscriptions.</label>
    <label class="check"><input type="checkbox" name="consent" required> I have read the <a href="<?= url('privacy.php') ?>">privacy notice</a> and agree that GUCO may process my details and documents to handle my application.</label>
    <label class="check"><input type="checkbox" name="directory_consent" <?= !empty($_POST['directory_consent']) ? 'checked' : '' ?>> Optional: list my name, programme, year, profession and location in the members' directory.</label>
  </fieldset>
  <button>Submit application</button>
</form>
<script>
(function () {
  var f = document.querySelector('form.wide'); if (!f) return;
  var types = f.querySelectorAll('input[name=member_type]'), staff = document.getElementById('fsStaff');
  var acLegend = document.getElementById('acLegend'), acHint = document.getElementById('acHint');
  var need = ['staff_joined_on', 'staff_position', 'staff_department'];
  function type() { var c = f.querySelector('input[name=member_type]:checked'); return c ? c.value : 'alumni'; }
  function sync() {
    var st = type() === 'staff';
    staff.hidden = !st; acHint.hidden = !st;
    acLegend.textContent = st ? '5. Academic details (optional for staff)' : '4. Academic details';
    need.forEach(function (n) { var el = f.elements[n]; if (el) el.required = st; });
  }
  [].forEach.call(types, function (r) { r.addEventListener('change', sync); });
  var pn = document.getElementById('payNow'), pays = f.querySelectorAll('input[name=pay_when]');
  function pay() { var c = f.querySelector('input[name=pay_when]:checked'); if (pn) pn.hidden = !!c && c.value === 'later'; }
  [].forEach.call(pays, function (r) { r.addEventListener('change', pay); });
  sync(); pay();
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
