<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Join GUCO';
$pageDesc = 'Apply for membership of Gulu University Convocation: personal, academic and employment details, proof of payment and a membership card on approval.';
$open = setting('applications_open') === '1';
$needProof = setting('require_payment_proof', '1') === '1';
$showFees = setting('show_fees') === '1';
$allowLater = setting('allow_pay_later', '1') === '1';
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
// An existing GUCO member who found their record on claim.php registers under their existing number.
$claim = legacy_pending_claim();
$dupes = [];
$pre = $claim ? [
    'full_name' => $claim['full_name'],
    'gender' => ['F' => 'female', 'M' => 'male'][$claim['sex'] ?? ''] ?? '',
    'member_type' => $claim['designation'] === 'staff' ? 'staff' : 'alumni',
    'eligibility_details' => 'Existing GUCO member, number ' . $claim['guco_no'] . ' (' . $claim['source'] . ').',
    'programme' => (string) ($claim['course'] ?? ''),
    'graduation_series' => (string) ($claim['graduation_series'] ?? ''),
    'student_reg_no' => (string) ($claim['student_reg_no'] ?? ''),
    'phone' => (string) ($claim['phone'] ?? ''),
    'college' => !empty($claim['faculty']) && empty($claim['faculty_suggested']) ? (string) $claim['faculty'] : '',
] : [];
$f = fn($k) => e($_POST[$k] ?? ($pre[$k] ?? ''));
$sel = fn($k, $v) => ($_POST[$k] ?? ($pre[$k] ?? ($k === 'category' ? ($_GET['category'] ?? '') : ''))) === (string) $v ? ' selected' : '';
$fees = ['founder' => 'fee_founder', 'ordinary' => 'fee_ordinary', 'honorary' => 'fee_honorary', 'life' => 'fee_life'];

if ($open && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $cat = $_POST['category'] ?? '';
    $year = (int) ($_POST['graduation_year'] ?? 0);
    $dob = $_POST['date_of_birth'] ?? '';
    $emp = ($_POST['member_type'] ?? '') === 'staff' ? 'employed' : ($_POST['employment_status'] ?? '');
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
    $dupes = [];

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

    $dupes = [];
    if (!$error && !$claim && empty($_POST['confirm_new']) && empty($_POST['dupe_ok'])) {
        $dupes = dedupe_candidates($name, trim($_POST['phone'] ?? ''));
        if ($dupes) {
            $error = 'We found a record that looks like yours. Please check it below before you continue.';
        }
    }

    $proof = $photo = null;
    if (!$error && $hasProof && !$payLater) {
        $proof = save_private_file($_FILES['proof'], 'proof');
        $error = $proof ? null : 'Proof of payment must be a JPG, PNG, WebP or PDF file under 5MB.';
    }
    if (!$error && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $photo = save_private_file($_FILES['photo'], 'photo');
        $error = $photo ? null : 'Your photo could not be saved. ' . upload_problem();
    }

    if ($error) {
        flash($error, 'error');
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        q('INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = (int) $pdo->lastInsertId();
        $claimNow = $claim ? legacy_pending_claim() : null;
        $memNo = next_member_no();
        if ($claimNow && !q('SELECT 1 FROM member_profiles WHERE member_no = ?', [legacy_number($claimNow)])->fetch()) {
            $memNo = legacy_number($claimNow);
        }
        $insertProfile = fn(int $memNo) => q('INSERT INTO member_profiles (user_id, member_no, category, gender, date_of_birth, nationality, phone, address, location, country,
              college, programme, qualification_level, student_reg_no, graduation_year, eligibility_details,
              employment_status, employer, profession, interests, photo, chapter_id, directory_consent,
              member_type, staff_joined_on, staff_position, staff_department, staff_no, staff_terms, staff_status, pay_later)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $uid, $memNo, $cat, $gender, date('Y-m-d', $dobTs), trim($_POST['nationality']), trim($_POST['phone']),
            trim($_POST['address'] ?? ''), trim($_POST['location'] ?? ''), trim($_POST['country'] ?? ''),
            $type === 'staff' ? '' : trim($_POST['college'] ?? ''), $type === 'staff' ? '' : trim($_POST['programme'] ?? ''), $type === 'staff' ? '' : trim($_POST['qualification_level'] ?? ''),
            $type === 'staff' ? '' : trim($_POST['student_reg_no'] ?? ''), $type === 'staff' ? null : ($year ?: null), trim($_POST['eligibility_details']),
            $emp, trim($_POST['employer'] ?? '') ?: ($type === 'staff' ? 'Gulu University' : ''), trim($_POST['profession'] ?? '') ?: ($type === 'staff' ? trim($_POST['staff_position']) : ''),
            implode(', ', array_intersect($_POST['interests'] ?? [], MEMBER_INTERESTS)),
            $photo['name'] ?? null, (int) ($_POST['chapter_id'] ?? 0) ?: null, empty($_POST['directory_consent']) ? 0 : 1,
            $type, $type === 'staff' ? date('Y-m-d', $joinedTs) : null, $type === 'staff' ? trim($_POST['staff_position']) : null,
            $type === 'staff' ? trim($_POST['staff_department']) : null, $type === 'staff' ? (trim($_POST['staff_no'] ?? '') ?: null) : null,
            $type === 'staff' ? $terms : null, $type === 'staff' ? $staffStatus : null, $payLater ? 1 : 0,
        ]);
        for ($try = 0; ; $try++) {
            try {
                $insertProfile($memNo);
                break;
            } catch (PDOException $e) {
                // Two people registering at the same moment: take the next free number.
                if ($claimNow || $try >= 5 || $e->getCode() !== '23000') {
                    throw $e;
                }
                $memNo = next_member_no();
            }
        }
        $gucoNo = '';
        if (!$claimNow) {
            $gucoNo = assign_guco_number($uid, $memNo, ['full_name' => $name, 'gender' => $gender, 'member_type' => $type, 'programme' => $type === 'staff' ? '' : trim($_POST['programme'] ?? ''),
                'college' => $type === 'staff' ? '' : trim($_POST['college'] ?? ''), 'graduation_series' => $type === 'alumni' ? ((int) ($_POST['graduation_series'] ?? 0) ?: null) : null,
                'student_reg_no' => $type === 'staff' ? '' : trim($_POST['student_reg_no'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')]);
            // The applicant said none of the similar records is theirs: leave a note for the Secretariat.
            $similar = dedupe_candidates($name, trim($_POST['phone'] ?? ''), $uid);
            if ($similar) {
                q('UPDATE member_profiles SET admin_notes = CONCAT(COALESCE(admin_notes, \'\'), ?) WHERE user_id = ?', [
                    'POSSIBLE DUPLICATE: the applicant confirmed they had not registered before, but similar records exist: ' . dedupe_note($similar) . ". Please check before approving.\n", $uid]);
            }
        }
        if ($claimNow) {
            // Link the register entry to this account; only one account can ever claim it.
            $linked = q("UPDATE legacy_members SET claimed_user_id = ?, claimed_at = NOW(), status = 'claimed' WHERE id = ? AND status = 'unclaimed' AND claimed_user_id IS NULL", [$uid, $claimNow['id']])->rowCount();
            if (!$linked) {
                $pdo->rollBack();
                unset($_SESSION['legacy_claim']);
                flash('That GUCO number has just been claimed by another account. Please contact the Secretariat.', 'error');
                redirect('claim.php');
            }
            q('UPDATE member_profiles SET legacy_guco_no = ? WHERE user_id = ?', [$claimNow['guco_no'], $uid]);
            // Already on a subscribed-members list: record that period, so renewal shows as due once it has passed.
            if (!empty($claimNow['subscribed_year'])) {
                $subYear = (int) $claimNow['subscribed_year'];
                q('UPDATE member_profiles SET subscription_status = ?, paid_until = ?, admin_notes = CONCAT(COALESCE(admin_notes, \'\'), ?) WHERE user_id = ?', [
                    $subYear >= (int) date('Y') ? 'active' : 'arrears', $subYear . '-12-31',
                    'Subscribed for ' . $subYear . " (subscribed-members list). Renewal needed once that period has passed.\n", $uid,
                ]);
            }
        }
        q('UPDATE member_profiles SET graduation_series = ? WHERE user_id = ?', [$type === 'alumni' ? ((int) ($_POST['graduation_series'] ?? 0) ?: null) : null, $uid]);
        if (!$payLater && ($proof || $amount > 0)) {
            q('INSERT INTO member_payments (user_id, purpose, method, amount, reference, paid_on, proof_file, proof_original) VALUES (?,?,?,?,?,?,?,?)', [
                $uid, 'registration', in_array($_POST['method'] ?? '', PAYMENT_METHODS, true) ? $_POST['method'] : 'Other', $amount,
                trim($_POST['reference'] ?? ''), ($_POST['paid_on'] ?? '') ?: null, $proof['name'] ?? null, $proof['original'] ?? null,
            ]);
        }
        $pdo->commit();
        unset($_SESSION['legacy_claim']);
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        $memberNo = (int) q('SELECT member_no FROM member_profiles WHERE user_id = ?', [$uid])->fetchColumn();
        notify($uid, 'Welcome to GUCO', 'Your application was received. Track it, your payments and everything else from this portal.', 'member/');
        portal_welcome_mail([
            'mode' => 'applied', 'name' => $name, 'email' => $email,
            'facts' => array_filter([
                'GUCO No.' => $claimNow ? $claimNow['guco_no'] . ' (your existing number, kept)' : $gucoNo . ' (your new number)',
                'Member no.' => member_no_label($memberNo), 'Member type' => MEMBER_TYPES[$type] ?? '', 'Category' => CATEGORY_LABELS[$cat] ?? '', 'Status' => 'Pending approval',
            ]),
            'next' => [
                $payLater ? 'Pay through the approved channel, then upload your proof of payment in the portal under Payments: ' . absolute_url('member/payments.php#renew') : ($proof || $amount > 0 ? 'Your proof of payment was received and is waiting for verification.' : 'Your payment has not been received yet. Upload your proof of payment in the portal under Payments.'),
                'The Secretariat checks your details and payment, then approves your membership.',
                'Your Membership ID and card are issued when you are approved. We will email you.',
            ],
        ]);
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
<?php if ($claim): ?>
  <div class="notice"><strong>Existing GUCO member: <?= e(legacy_display_name($claim)) ?> (<?= e($claim['guco_no']) ?>).</strong> Check and complete the details below. Your GUCO number stays the same, and the Secretariat confirms your membership after you submit. <a href="<?= url('claim.php?cancel=1') ?>">Not you?</a></div>
<?php else: ?>
<div class="reg-existing">
  <a class="re-card" href="<?= url('claim.php') ?>">
    <span class="re-ic"><?= icon('id-card', 26) ?></span>
    <span class="re-tx"><b>Already hold a GUCO number?</b><small>Find your record by number or name, confirm your details and renew.</small></span>
    <span class="re-go">Find your record <?= icon('arrow-right', 16) ?></span>
  </a>
  <a class="re-card alt" href="<?= url('member/login.php') ?>">
    <span class="re-ic"><?= icon('user', 26) ?></span>
    <span class="re-tx"><b>Already registered?</b><small>Log in to your Member Portal to track your membership.</small></span>
    <span class="re-go">Log in to your portal <?= icon('arrow-right', 16) ?></span>
  </a>
</div>
<?php endif; ?>
<ol class="steps">
  <li><strong>Apply</strong> Fill in this form. Upload your proof of payment now, or choose to pay later.</li>
  <li><strong>Verification</strong> The Secretariat checks your details and payment.</li>
  <li><strong>Approval</strong> Your membership ID is issued and your card activated.</li>
  <li><strong>Your card</strong> View and print it from the Member Portal.</li>
</ol>
<p>Membership is open to eligible persons above 18 years under Article 5 of the Constitution, and takes effect on registration and full payment of the applicable fees. See the <a href="<?= url('membership.php#categories') ?>">membership categories</a> to choose yours. Fields marked * are required.</p>

<form method="post" enctype="multipart/form-data" class="stack wide">
  <?= csrf_field() ?><?= spam_fields() ?>
  <?php if (!empty($dupes)): ?>
  <div class="dupe-box" id="dupeBox" role="alert">
    <h2>Have you registered with GUCO before?</h2>
    <p>These records look like yours. Please check them so that you do not end up with two records.</p>
    <ul><?php foreach ($dupes as $d): ?><li><strong><?= e($d['name']) ?></strong><?= $d['guco_no'] ? ' (' . e($d['guco_no']) . ')' : '' ?><?= $d['detail'] ? ', ' . e($d['detail']) : '' ?>: <?= $d['kind'] === 'register' && $d['status'] === 'unclaimed' ? 'on the GUCO register, not yet claimed' : 'already has an account' . ($d['email_hint'] ? ' (' . e($d['email_hint']) . ')' : '') ?></li><?php endforeach; ?></ul>
    <p>If one of these is you, <a href="<?= url('claim.php') ?>">find your record</a> or <a href="<?= url('member/login.php') ?>">sign in</a>. Otherwise tick the box below and submit again. Please choose your photo and proof of payment again.</p>
    <label class="check"><input type="checkbox" name="confirm_new" value="1"> I confirm that I have not registered with GUCO before and none of these records is me.</label>
  </div>
  <?php endif; ?>

  <fieldset><legend>Account</legend>
    <div class="cols">
      <label>Email address * <input type="email" name="email" required autocomplete="email" value="<?= $f('email') ?>"></label>
      <label>Password * <span class="hint">(at least 8 characters)</span> <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    </div>
  </fieldset>

  <fieldset><legend>Member type</legend>
    <p class="meta" style="margin-top:0">GUCO brings together Gulu University alumni and staff. Choose the one that describes you.</p>
    <div class="type-pick" role="radiogroup" aria-label="Member type">
      <?php $mt = $_POST['member_type'] ?? ($pre['member_type'] ?? ($_GET['type'] ?? 'alumni')); foreach (MEMBER_TYPES as $k => $l): ?>
        <label class="type-card"><input type="radio" name="member_type" value="<?= $k ?>"<?= $mt === $k ? ' checked' : '' ?>><span class="ic"><?= icon($k === 'staff' ? 'briefcase' : 'graduation', 24) ?></span><b><?= e($l) ?></b>
          <small><?= $k === 'staff' ? 'Academic, administrative and support staff, serving, retired or former.' : 'Graduates and former students of the University.' ?></small></label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <fieldset><legend>Personal details</legend>
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

  <fieldset id="fsStaff"><legend>Gulu University staff details</legend>
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

  <fieldset id="fsAcademic"><legend>Academic details</legend>
    <div class="cols">
      <label>College / faculty <input name="college" value="<?= $f('college') ?>"></label>
      <label>Course / programme attained * <input name="programme" value="<?= $f('programme') ?>"></label>
    </div>
    <div class="cols">
      <label>Level of qualification
        <select name="qualification_level"><option value="">Select</option>
          <?php foreach (QUALIFICATION_LEVELS as $l): ?><option<?= $sel('qualification_level', $l) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Year of graduation * <input type="number" name="graduation_year" min="1990" max="<?= (int) date('Y') + 1 ?>" value="<?= $f('graduation_year') ?>"></label>
      <label>Graduation series <span class="hint">(e.g. 21 for the 21st graduation)</span> <input type="number" name="graduation_series" min="1" max="60" value="<?= $f('graduation_series') ?>"></label>
      <label>Student registration number <span class="hint">(optional)</span> <input name="student_reg_no" value="<?= $f('student_reg_no') ?>"></label>
    </div>
  </fieldset>

  <fieldset id="fsEmployment"><legend>Employment</legend>
    <div class="cols">
      <label>Employment status *
        <select name="employment_status" required><option value="">Select</option>
          <?php foreach (EMPLOYMENT_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= $sel('employment_status', $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Employer / organisation <input name="employer" value="<?= $f('employer') ?>"></label>
      <label>Job title / profession <input name="profession" value="<?= $f('profession') ?>"></label>
    </div>
  </fieldset>

  <fieldset><legend>Membership</legend>
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

  <fieldset><legend>Payment<?= $needProof ? ' *' : '' ?></legend>
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

  <fieldset><legend>Declarations</legend>
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
  var academic = document.getElementById('fsAcademic'), employment = document.getElementById('fsEmployment');
  var need = ['staff_joined_on', 'staff_position', 'staff_department'];
  function type() { var c = f.querySelector('input[name=member_type]:checked'); return c ? c.value : 'alumni'; }
  function sync() {
    var st = type() === 'staff';
    staff.hidden = !st; academic.hidden = st;
    employment.hidden = st;
    [].forEach.call(academic.querySelectorAll('input, select'), function (el) { el.disabled = st; });
    [].forEach.call(employment.querySelectorAll('input, select'), function (el) { el.disabled = st; });
    need.forEach(function (n) { var el = f.elements[n]; if (el) el.required = st; });
  }
  [].forEach.call(types, function (r) { r.addEventListener('change', sync); });
  var pn = document.getElementById('payNow'), pays = f.querySelectorAll('input[name=pay_when]');
  function pay() { var c = f.querySelector('input[name=pay_when]:checked'); if (pn) pn.hidden = !!c && c.value === 'later'; }
  [].forEach.call(pays, function (r) { r.addEventListener('change', pay); });
  sync(); pay();
})();
</script>
<div class="dq" id="dupeModal" role="dialog" aria-modal="true" aria-labelledby="dqTitle" hidden>
  <div class="dq-card">
    <div class="dq-head"><span class="dq-ic"><?= icon('users', 26) ?></span><div><h2 id="dqTitle">Have you registered with GUCO before?</h2><p>We found records that look like yours. Please check them first, so that you do not end up with two records.</p></div></div>
    <ul class="dq-list" id="dqList"></ul>
    <div class="dq-actions">
      <button type="button" class="dq-new" id="dqNew">No, none of these is me. Continue as a new member</button>
      <button type="button" class="dq-back" id="dqBack">Go back and edit my form</button>
    </div>
    <p class="dq-note">If you continue as new, the Secretariat is told that similar records exist, so they can check for a duplicate before approving you.</p>
  </div>
</div>
<script>
(function () {
  var f = document.querySelector('form.wide'); if (!f) return;
  var modal = document.getElementById('dupeModal'), list = document.getElementById('dqList');
  var csrf = f.querySelector('input[name=csrf]').value, url = <?= json_encode(url('register_check.php')) ?>;
  var loginUrl = <?= json_encode(url('member/login.php')) ?>, forgotUrl = <?= json_encode(url('forgot.php')) ?>;
  var skip = <?= json_encode((bool) $claim) ?>, busy = false, lastFocus = null;
  var okInput = document.createElement('input'); okInput.type = 'hidden'; okInput.name = 'dupe_ok'; okInput.value = ''; f.appendChild(okInput);
  function post(data) {
    var fd = new FormData(); fd.append('csrf', csrf); Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text) e.textContent = text; return e; }
  function render(matches) {
    list.innerHTML = '';
    matches.forEach(function (m) {
      var li = el('li', 'dq-item');
      var top = el('div', 'dq-top');
      top.appendChild(el('span', 'dq-av', (m.name || '?').replace(/^(dr|prof|mr|mrs|ms|counsel|hon|eng)\.?\s+/i, '').charAt(0).toUpperCase()));
      var who = el('div', 'dq-who'); who.appendChild(el('b', '', m.name));
      var meta = el('small', '', [m.guco_no, m.detail].filter(Boolean).join(' · ')); who.appendChild(meta);
      top.appendChild(who);
      top.appendChild(el('span', 'dq-chip ' + (m.claimable ? 'free' : 'taken'), m.claimable ? 'On the GUCO register' : 'Already has an account'));
      li.appendChild(top);
      if (m.email_hint) li.appendChild(el('p', 'dq-hint', 'Account email: ' + m.email_hint));
      var act = el('div', 'dq-act');
      if (m.claimable) {
        var b = el('button', 'dq-me', 'Yes, this is me. Use my existing GUCO number'); b.type = 'button';
        b.addEventListener('click', function () {
          b.disabled = true; b.textContent = 'One moment...';
          post({ action: 'claim', id: m.id }).then(function (d) { if (d && d.ok) { window.location.href = d.redirect; } else { b.disabled = false; b.textContent = 'Yes, this is me. Use my existing GUCO number'; alert((d && d.error) || 'Please try again.'); } })
            .catch(function () { b.disabled = false; b.textContent = 'Yes, this is me. Use my existing GUCO number'; });
        });
        act.appendChild(b);
      } else {
        var a = el('a', 'dq-me', 'Yes, this is me. Sign in'); a.href = loginUrl; act.appendChild(a);
        var g = el('a', 'dq-alt', 'Forgot my password'); g.href = forgotUrl; act.appendChild(g);
      }
      li.appendChild(act); list.appendChild(li);
    });
  }
  function open(matches) { lastFocus = document.activeElement; render(matches); modal.hidden = false; document.documentElement.classList.add('dq-lock'); var first = modal.querySelector('button, a'); if (first) first.focus(); }
  function close() { modal.hidden = true; document.documentElement.classList.remove('dq-lock'); if (lastFocus && lastFocus.focus) lastFocus.focus(); }
  document.getElementById('dqBack').addEventListener('click', close);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
  document.getElementById('dqNew').addEventListener('click', function () { okInput.value = '1'; close(); HTMLFormElement.prototype.submit.call(f); });
  f.addEventListener('submit', function (e) {
    if (skip || okInput.value === '1' || busy) return;
    var name = (f.elements.full_name ? f.elements.full_name.value : '').trim(); if (name.length < 3) return;
    if (!f.checkValidity()) return; // let the browser show its own messages first
    e.preventDefault(); busy = true;
    var btn = f.querySelector('button:not([type]), button[type=submit]'); var label = btn ? btn.textContent : ''; if (btn) { btn.disabled = true; btn.textContent = 'Checking your details...'; }
    post({ action: 'check', full_name: name, phone: f.elements.phone ? f.elements.phone.value : '' }).then(function (d) {
      busy = false; if (btn) { btn.disabled = false; btn.textContent = label; }
      if (d && d.ok && d.matches && d.matches.length) { open(d.matches); } else { HTMLFormElement.prototype.submit.call(f); }
    }).catch(function () { busy = false; if (btn) { btn.disabled = false; btn.textContent = label; } HTMLFormElement.prototype.submit.call(f); });
  });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
