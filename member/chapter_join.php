<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
if (!is_member()) {
    flash('Chapter registration is open to approved members. Your application is still being processed.', 'error');
    redirect('member/');
}
$chapters = q('SELECT * FROM chapters WHERE is_public = 1 AND accepting_members = 1 ORDER BY sort_order, name')->fetchAll();
$id = (int) ($_GET['id'] ?? $_POST['chapter_id'] ?? 0);
$chapter = null;
foreach ($chapters as $c) {
    if ((int) $c['id'] === $id) {
        $chapter = $c;
    }
}
$p = q('SELECT * FROM member_profiles WHERE user_id = ?', [$me['id']])->fetch() ?: [];
$existing = $chapter ? q('SELECT * FROM chapter_members WHERE chapter_id = ? AND user_id = ?', [$chapter['id'], $me['id']])->fetch() : null;
$f = fn($k, $d = '') => e($_POST[$k] ?? $d);

if ($chapter && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $isDiaspora = $chapter['focus'] === 'Diaspora chapter';
    $district = trim($_POST['district'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $motivation = trim($_POST['motivation'] ?? '');
    $avail = in_array($_POST['availability'] ?? '', AVAILABILITY_OPTIONS, true) ? $_POST['availability'] : '';
    $error = null;
    if ($existing && in_array($existing['status'], ['pending', 'approved'], true)) {
        $error = 'You are already registered in this chapter.';
    } elseif ($district === '' || ($isDiaspora && $country === '')) {
        $error = $isDiaspora ? 'Enter your country and city of residence.' : 'Enter the district or town where you live.';
    } elseif ($motivation === '') {
        $error = 'Tell the chapter why you would like to join.';
    } elseif ($avail === '') {
        $error = 'Select when you are usually available.';
    } elseif (empty($_POST['declare'])) {
        $error = 'Please accept the declaration.';
    }
    if ($error) {
        flash($error, 'error');
    } else {
        $status = $chapter['requires_approval'] ? 'pending' : 'approved';
        $interests = implode(', ', array_intersect($_POST['interests'] ?? [], MEMBER_INTERESTS));
        if ($existing) { // re-applying after leaving or being declined
            q('UPDATE chapter_members SET status = ?, role = "member", district = ?, country = ?, availability = ?, interests = ?, leadership_interest = ?, motivation = ?, share_contact = ?, decision_note = NULL, decided_at = NULL, created_at = NOW() WHERE id = ?',
                [$status, $district, $country, $avail, $interests, empty($_POST['leadership_interest']) ? 0 : 1, $motivation, empty($_POST['share_contact']) ? 0 : 1, $existing['id']]);
        } else {
            q('INSERT INTO chapter_members (chapter_id, user_id, status, district, country, availability, interests, leadership_interest, motivation, share_contact) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$chapter['id'], $me['id'], $status, $district, $country, $avail, $interests, empty($_POST['leadership_interest']) ? 0 : 1, $motivation, empty($_POST['share_contact']) ? 0 : 1]);
        }
        if ($status === 'approved') {
            chapter_activate_primary((int) $me['id'], (int) $chapter['id']);
        }
        flash($status === 'approved' ? 'Welcome to the ' . $chapter['name'] . '!' : 'Your registration was sent to the ' . $chapter['name'] . ' leadership for approval.');
        redirect('member/chapters.php');
    }
}
portal_header($chapter ? 'Register: ' . $chapter['name'] : 'Register in a chapter');
?>
<?php if (!$chapter): ?>
  <p>Choose the chapter you would like to register in.</p>
  <div class="grid">
    <?php foreach ($chapters as $c): ?><a class="card accent" href="chapter_join.php?id=<?= (int) $c['id'] ?>"><h3><?= e($c['name']) ?></h3><p class="meta"><?= e(excerpt((string) $c['description'], 110)) ?></p></a><?php endforeach; ?>
    <?php if (!$chapters): ?><p>No chapters are accepting members right now.</p><?php endif; ?>
  </div>
<?php else: ?>
  <?php if ($existing && in_array($existing['status'], ['pending', 'approved'], true)): ?>
    <div class="notice">You are already registered in this chapter (<?= $existing['status'] === 'approved' ? 'approved' : 'awaiting approval' ?>). <a href="chapters.php">Back to my chapters</a></div>
  <?php else: ?>
  <div class="card" style="margin-bottom:18px">
    <h3 style="margin-top:0"><?= e($chapter['name']) ?></h3>
    <p><?= nl2br(e($chapter['description'])) ?></p>
    <?php if (trim((string) $chapter['districts']) !== ''): ?><p class="meta"><strong>Covers:</strong> <?= e(preg_replace('/\s+/', ' ', $chapter['districts'])) ?></p><?php endif; ?>
    <p class="meta"><?= $chapter['requires_approval'] ? 'New members are approved by the chapter leadership.' : 'You will be added to this chapter straight away.' ?></p>
  </div>
  <form method="post" class="stack wide">
    <?= csrf_field() ?><input type="hidden" name="chapter_id" value="<?= (int) $chapter['id'] ?>">
    <fieldset><legend>Your details</legend>
      <div class="cols">
        <label>Name <input value="<?= e($me['full_name']) ?>" disabled></label>
        <label>Membership ID <input value="<?= e($p['membership_id'] ?? '') ?>" disabled></label>
      </div>
      <div class="cols">
        <label>Email <input value="<?= e($me['email']) ?>" disabled></label>
        <label>Phone <input value="<?= e($p['phone'] ?? '') ?>" disabled></label>
      </div>
      <p class="hint">Details come from your profile. Update them in <a href="profile.php">My profile</a>.</p>
    </fieldset>
    <fieldset><legend>Where you live</legend>
      <div class="cols">
        <?php if ($chapter['focus'] === 'Diaspora chapter'): ?>
          <label>Country of residence * <input name="country" value="<?= $f('country', $p['country'] ?? '') ?>"></label>
          <label>City / state * <input name="district" value="<?= $f('district', $p['location'] ?? '') ?>"></label>
        <?php else: ?>
          <label>District / town * <input name="district" value="<?= $f('district', $p['location'] ?? '') ?>"></label>
          <label>Country <input name="country" value="<?= $f('country', $p['country'] ?? 'Uganda') ?>"></label>
        <?php endif; ?>
      </div>
    </fieldset>
    <fieldset><legend>How you will take part</legend>
      <label>Why do you want to join this chapter? *<textarea name="motivation" rows="4" required><?= $f('motivation') ?></textarea></label>
      <div><strong class="small">Ways you would like to contribute</strong>
        <div class="checks-grid"><?php foreach (MEMBER_INTERESTS as $i): ?>
          <label class="check"><input type="checkbox" name="interests[]" value="<?= e($i) ?>"<?= in_array($i, $_POST['interests'] ?? [], true) ? ' checked' : '' ?>> <?= e($i) ?></label>
        <?php endforeach; ?></div></div>
      <div class="cols">
        <label>When are you usually available? *
          <select name="availability" required><option value="">Select</option><?php foreach (AVAILABILITY_OPTIONS as $a): ?><option<?= ($_POST['availability'] ?? '') === $a ? ' selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></label>
      </div>
      <label class="check"><input type="checkbox" name="leadership_interest" <?= !empty($_POST['leadership_interest']) ? 'checked' : '' ?>> I am interested in serving on the chapter executive committee.</label>
    </fieldset>
    <fieldset><legend>Declaration</legend>
      <label class="check"><input type="checkbox" name="share_contact" <?= !empty($_POST['share_contact']) ? 'checked' : '' ?>> Share my phone number and email with this chapter's leaders so they can contact me about chapter activities.</label>
      <label class="check"><input type="checkbox" name="declare" required> I confirm the details above are correct and I agree to take part in line with the Constitution.</label>
    </fieldset>
    <button><?= $chapter['requires_approval'] ? 'Submit registration' : 'Join chapter' ?></button>
  </form>
  <?php endif; ?>
<?php endif; ?>
<?php portal_footer(); ?>
