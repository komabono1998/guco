<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('members');
$chapters = ['' => 'No chapter yet'] + q('SELECT id, name FROM chapters ORDER BY sort_order, name')->fetchAll(PDO::FETCH_KEY_PAIR);

$created = null;
$showDupeBox = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $cat = $_POST['category'] ?? '';
    $pass = trim($_POST['password'] ?? '');
    $generate = $pass === '';
    $year = (int) ($_POST['graduation_year'] ?? 0);
    $type = ($_POST['member_type'] ?? '') === 'staff' ? 'staff' : 'alumni';
    $joined = trim($_POST['staff_joined_on'] ?? '');
    $joinedTs = $joined !== '' ? strtotime($joined) : false;
    $error = null;
    if ($type === 'staff' && (!$joinedTs || $joinedTs > time() || $joinedTs < strtotime('1950-01-01'))) {
        $error = 'Enter the date the staff member joined Gulu University.';
    } elseif ($type === 'staff' && (trim($_POST['staff_position'] ?? '') === '' || trim($_POST['staff_department'] ?? '') === '')) {
        $error = 'Enter the staff member\'s position and department, faculty or unit.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter the member\'s full name and a valid email.';
    } elseif (!isset(CATEGORY_LABELS[$cat])) {
        $error = 'Choose a membership category.';
    } elseif (!$generate && strlen($pass) < 8) {
        $error = 'A password you type must be at least 8 characters, or leave it blank to generate one.';
    } elseif ($year && ($year < 1990 || $year > (int) date('Y') + 1)) {
        $error = 'Enter a valid graduation year or leave it blank.';
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
        $error = 'An account with that email already exists.';
    }
    if (!$error && empty($_POST['confirm_dupe'])) {
        $similar = dedupe_candidates($name, trim($_POST['phone'] ?? ''));
        if ($similar) {
            $error = 'Similar records already exist: ' . dedupe_note($similar) . '. If this is a different person, tick "Add anyway" and save again.';
            $showDupeBox = true;
        }
    }
    if ($error) {
        flash($error, 'error');
    } else {
        $pass = $generate ? temp_password() : $pass;
        $pdo = db();
        $pdo->beginTransaction();
        q('INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = (int) $pdo->lastInsertId();
        $paidUntil = ($_POST['paid_until'] ?? '') ?: null;
        $staff = $type === 'staff';
        q('INSERT INTO member_profiles (user_id, member_no, category, member_type, subscription_status, paid_until, phone, college, programme, graduation_year, chapter_id, directory_consent, admin_notes,
              staff_joined_on, staff_position, staff_department, staff_no, staff_terms, staff_status, employer, profession)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?,?)', [
            $uid, next_member_no(), $cat, $type, $paidUntil ? 'active' : 'unpaid', $paidUntil, trim($_POST['phone'] ?? ''), trim($_POST['college'] ?? ''),
            trim($_POST['programme'] ?? ''), $year ?: null, (int) ($_POST['chapter_id'] ?? 0) ?: null, 'Account created by ' . $admin['full_name'] . '.',
            $staff ? date('Y-m-d', $joinedTs) : null, $staff ? trim($_POST['staff_position']) : null, $staff ? trim($_POST['staff_department']) : null,
            $staff ? (trim($_POST['staff_no'] ?? '') ?: null) : null, $staff && isset(STAFF_TERMS[$_POST['staff_terms'] ?? '']) ? $_POST['staff_terms'] : null,
            $staff ? (isset(STAFF_STATUS[$_POST['staff_status'] ?? '']) ? $_POST['staff_status'] : 'current') : null,
            $staff ? 'Gulu University' : null, $staff ? trim($_POST['staff_position']) : null,
        ]);
        $newNo = (int) q('SELECT member_no FROM member_profiles WHERE user_id = ?', [$uid])->fetchColumn();
        assign_guco_number($uid, $newNo, ['full_name' => $name, 'gender' => '', 'member_type' => $type, 'programme' => trim($_POST['programme'] ?? ''), 'college' => trim($_POST['college'] ?? ''), 'graduation_series' => null, 'student_reg_no' => '', 'phone' => trim($_POST['phone'] ?? '')]);
        $cid = (int) ($_POST['chapter_id'] ?? 0);
        if ($cid && isset($chapters[$cid])) {
            q("INSERT INTO chapter_members (chapter_id, user_id, status, decided_by, decided_at) VALUES (?,?,'approved',?,NOW())", [$cid, $uid, $admin['id']]);
        }
        $pdo->commit();
        audit('admin_create_member', "$email as $cat");
        $approvedNow = !empty($_POST['approve']);
        $mid = null;
        if ($approvedNow) {
            approve_member($uid, true);
            $mid = q('SELECT membership_id FROM member_profiles WHERE user_id = ?', [$uid])->fetchColumn();
        }
        $emailed = false;
        if (!empty($_POST['send_email'])) {
            $emailed = portal_welcome_mail([
                'mode' => 'created', 'name' => $name, 'email' => $email, 'password' => $pass,
                'facts' => array_filter(['Member type' => MEMBER_TYPES[$type] ?? '', 'Category' => CATEGORY_LABELS[$cat] ?? '', 'Membership ID' => (string) $mid]),
                'next' => [$mid ? 'Your membership is approved. You can view your card in the portal.' : 'The Secretariat will confirm your membership and issue your Membership ID.'],
            ]);
        }
        $created = ['id' => $uid, 'name' => $name, 'email' => $email, 'password' => $pass, 'generated' => $generate, 'mid' => $mid, 'emailed' => $emailed, 'sent' => !empty($_POST['send_email'])];
    }
}
admin_header('Add a member', 'Create a member account on behalf of someone, with or without immediate approval');
?>
<p><a href="<?= url('admin/members.php') ?>">&larr; All members</a></p>
<?php if ($created): ?>
  <div class="card accent" style="max-width:720px;margin-bottom:20px">
    <h3 style="margin-top:0">Account created for <?= e($created['name']) ?></h3>
    <p><strong>Login email:</strong> <?= e($created['email']) ?><br>
       <strong>Password:</strong> <code style="font-size:1.1rem;background:#f3f6fa;padding:2px 8px;border-radius:6px"><?= e($created['password']) ?></code> <span class="meta">(shown once, so note it down)</span><br>
       <strong>Member portal:</strong> <?= e(absolute_url('member/login.php')) ?><?= $created['mid'] ? '<br><strong>Membership ID:</strong> <span class="mono">' . e($created['mid']) . '</span>' : '<br><span class="meta">Application is pending: review it to approve and issue the ID.</span>' ?></p>
    <?php if ($created['sent']): ?><p class="meta"><?= $created['emailed'] ? 'A welcome email was sent.' : 'The welcome email could not be delivered (see the Mail log). Share the details above with the member directly.' ?></p><?php endif; ?>
    <p><a class="btn" href="members.php?id=<?= (int) $created['id'] ?>">Open member record</a> <a class="btn secondary" href="member_add.php">Add another member</a></p>
  </div>
<?php endif; ?>
<form method="post" class="stack wide">
  <?= csrf_field() ?>
  <fieldset><legend>Account</legend>
    <div class="cols">
      <label>Full name * <input name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></label>
      <label>Email address * <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
    </div>
    <div class="cols">
      <label>Phone <input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
      <label>Password <span class="hint">(leave blank to generate a temporary one)</span><input type="text" name="password" autocomplete="off" value=""></label>
    </div>
  </fieldset>
  <fieldset><legend>Membership</legend>
    <label>Member type *
      <select name="member_type" id="mtype"><?php foreach (MEMBER_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= ($_POST['member_type'] ?? 'alumni') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <div id="staffBox" class="stack" hidden>
      <div class="cols">
        <label>Date joined Gulu University * <input type="date" name="staff_joined_on" max="<?= date('Y-m-d') ?>" value="<?= e($_POST['staff_joined_on'] ?? '') ?>"></label>
        <label>Position / job title * <input name="staff_position" value="<?= e($_POST['staff_position'] ?? '') ?>"></label>
      </div>
      <div class="cols">
        <label>Department, faculty or unit * <input name="staff_department" value="<?= e($_POST['staff_department'] ?? '') ?>"></label>
        <label>Staff number <input name="staff_no" value="<?= e($_POST['staff_no'] ?? '') ?>"></label>
      </div>
      <div class="cols">
        <label>Terms of service <select name="staff_terms"><option value="">Select</option><?php foreach (STAFF_TERMS as $k => $l): ?><option value="<?= $k ?>"<?= ($_POST['staff_terms'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
        <label>Current status <select name="staff_status"><?php foreach (STAFF_STATUS as $k => $l): ?><option value="<?= $k ?>"<?= ($_POST['staff_status'] ?? 'current') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      </div>
    </div>
    <div class="cols">
      <label>Category *<select name="category" required><option value="">Select</option><?php foreach (CATEGORY_LABELS as $k => $l): ?><option value="<?= $k ?>"<?= ($_POST['category'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label>Chapter<select name="chapter_id"><?php foreach ($chapters as $k => $n): ?><option value="<?= e($k) ?>"><?= e($n) ?></option><?php endforeach; ?></select></label>
    </div>
    <div class="cols">
      <label>College / faculty <input name="college" value="<?= e($_POST['college'] ?? '') ?>"></label>
      <label>Course / programme <input name="programme" value="<?= e($_POST['programme'] ?? '') ?>"></label>
      <label>Graduation year <input type="number" name="graduation_year" min="1990" max="<?= (int) date('Y') + 1 ?>" value="<?= e($_POST['graduation_year'] ?? '') ?>"></label>
    </div>
    <label>Subscription paid up to <span class="hint">(optional; marks the subscription active, for ordinary members)</span><input type="date" name="paid_until"></label>
  </fieldset>
  <fieldset><legend>Options</legend>
    <label class="check"><input type="checkbox" name="approve" checked> Approve now and issue the Membership ID (recorded as approved without a payment on file)</label>
    <label class="check"><input type="checkbox" name="send_email" checked> Email the login details to the member</label>
    <?php if (!empty($showDupeBox)): ?><label class="check" style="background:#fff8e1;border:2px solid #f2b807;border-radius:12px;padding:10px 14px"><input type="checkbox" name="confirm_dupe" value="1"> <strong>Add anyway:</strong> this is a different person from the similar records listed above</label><?php endif; ?>
    <p class="hint">The member can change their password and complete their profile (photo, address, employment) from the Member Portal.</p>
  </fieldset>
  <button>Create member account</button>
</form>
<script>
(function () {
  var t = document.getElementById('mtype'), box = document.getElementById('staffBox'); if (!t || !box) return;
  function sync() {
    var st = t.value === 'staff'; box.hidden = !st; box.style.display = st ? '' : 'none';
    ['staff_joined_on', 'staff_position', 'staff_department'].forEach(function (n) { var el = t.form.elements[n]; if (el) el.required = st; });
  }
  t.addEventListener('change', sync); sync();
})();
</script>
<?php admin_footer(); ?>
