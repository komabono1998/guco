<?php
require_once __DIR__ . '/../includes/admin.php';
$admin = require_perm('members');
$chapters = ['' => 'No chapter yet'] + q('SELECT id, name FROM chapters ORDER BY sort_order, name')->fetchAll(PDO::FETCH_KEY_PAIR);

$created = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $cat = $_POST['category'] ?? '';
    $pass = trim($_POST['password'] ?? '');
    $generate = $pass === '';
    $year = (int) ($_POST['graduation_year'] ?? 0);
    $error = null;
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    if ($error) {
        flash($error, 'error');
    } else {
        $pass = $generate ? temp_password() : $pass;
        $pdo = db();
        $pdo->beginTransaction();
        q('INSERT INTO users (full_name, email, password_hash) VALUES (?,?,?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = (int) $pdo->lastInsertId();
        $paidUntil = ($_POST['paid_until'] ?? '') ?: null;
        q('INSERT INTO member_profiles (user_id, member_no, category, subscription_status, paid_until, phone, college, programme, graduation_year, chapter_id, directory_consent, admin_notes)
           VALUES (?,?,?,?,?,?,?,?,?,?,0,?)', [
            $uid, next_member_no(), $cat, $paidUntil ? 'active' : 'unpaid', $paidUntil, trim($_POST['phone'] ?? ''), trim($_POST['college'] ?? ''),
            trim($_POST['programme'] ?? ''), $year ?: null, (int) ($_POST['chapter_id'] ?? 0) ?: null, 'Account created by ' . $admin['full_name'] . '.',
        ]);
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
            $emailed = send_mail($email, 'Your GUCO Member Portal account',
                "Dear $name,\n\nThe Secretariat has created a Member Portal account for you.\n\nLog in: " . absolute_url('member/login.php') . "\nEmail: $email\nTemporary password: $pass\n\nPlease change your password under My profile after you log in.\n\nGulu University Convocation");
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
    <p class="hint">The member can change their password and complete their profile (photo, address, employment) from the Member Portal.</p>
  </fieldset>
  <button>Create member account</button>
</form>
<?php admin_footer(); ?>
