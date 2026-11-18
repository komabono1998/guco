<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Give';
$pageDesc = 'Support Gulu University and its students: scholarships, infrastructure, research and community development through GUCO.';
$me = current_user();
$campaigns = q("SELECT * FROM campaigns WHERE status = 'active' AND is_public = 1 AND (ends_on IS NULL OR ends_on >= CURDATE()) ORDER BY id DESC")->fetchAll();
$byId = array_column($campaigns, null, 'id');
$sel = (int) ($_GET['c'] ?? $_POST['campaign_id'] ?? 0);
$camp = $byId[$sel] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['donor_name'] ?? '');
    $email = strtolower(trim($_POST['donor_email'] ?? ''));
    $amount = (int) preg_replace('/\D/', '', $_POST['amount'] ?? '');
    $method = $_POST['method'] ?? '';
    $ref = trim($_POST['reference'] ?? '');
    $cat = $camp ? $camp['category'] : (in_array($_POST['category'] ?? '', DONATION_CATEGORIES, true) ? $_POST['category'] : 'General');
    $rec = isset(DONATION_RECURRING[$_POST['recurring'] ?? '']) ? $_POST['recurring'] : 'none';
    $err = null;
    if (is_spam()) {
        $err = 'Please wait a few seconds and submit again.';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Enter your name and a valid email so we can send your receipt.';
    } elseif ($amount < 1000 || $amount > 1000000000) {
        $err = 'Enter an amount of at least UGX 1,000.';
    } elseif (!isset(DONATION_METHODS[$method]) || $method === 'card') {
        $err = 'Choose how you paid.';
    } elseif (strlen($ref) < 4) {
        $err = 'Enter the transaction ID or reference from your payment message or bank slip.';
    } elseif (q('SELECT 1 FROM donations WHERE method = ? AND reference = ?', [$method, $ref])->fetch()) {
        $err = 'That transaction reference has already been submitted.';
    } elseif (empty($_POST['consent'])) {
        $err = 'Please confirm that we may record your gift and contact you about it.';
    }
    if ($err) {
        flash($err, 'error');
    } else {
        q('INSERT INTO donations (campaign_id, user_id, donor_name, donor_email, donor_phone, amount, method, reference, category, is_anonymous, recurring, message, access_token)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $camp['id'] ?? null, $me['id'] ?? null, mb_substr($name, 0, 150), $email, mb_substr(trim($_POST['donor_phone'] ?? ''), 0, 40), $amount, $method, mb_substr($ref, 0, 120),
            $cat, !empty($_POST['is_anonymous']) ? 1 : 0, $rec, mb_substr(trim($_POST['message'] ?? ''), 0, 400), bin2hex(random_bytes(16)),
        ]);
        flash('Thank you. Your gift has been recorded and will be confirmed once the Finance Officer has matched it to the payment received. You will get a receipt by email.');
        redirect('donate.php' . ($camp ? '?c=' . (int) $camp['id'] : ''));
    }
}
$accounts = array_filter([
    'MTN Mobile Money' => setting('mtn_momo_number'), 'Airtel Money' => setting('airtel_money_number'), 'Bank' => setting('bank_details'),
]);
$recent = q("SELECT donor_name, amount, is_anonymous, campaign_id, verified_at FROM donations WHERE status = 'verified' ORDER BY verified_at DESC LIMIT 8")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Give to GUCO</h1><p>Your gift supports Gulu University students, projects and community development</p></div>

<?php if ($campaigns): ?>
<section class="grid two" style="margin-bottom:26px">
  <?php foreach ($campaigns as $cp): $raised = donations_raised((int) $cp['id']); $pct = $cp['goal_amount'] > 0 ? min(100, (int) round($raised / $cp['goal_amount'] * 100)) : 0; ?>
    <article class="card accent">
      <span class="badge"><?= e($cp['category']) ?></span>
      <h2 style="margin:6px 0"><a href="?c=<?= (int) $cp['id'] ?>#give" style="text-decoration:none"><?= e($cp['title']) ?></a></h2>
      <p><?= e($cp['summary']) ?></p>
      <?php if ($cp['goal_amount'] > 0): ?>
        <div class="meter" style="height:8px;background:#e3eae5;border-radius:99px;overflow:hidden"><i style="display:block;height:100%;width:<?= $pct ?>%;background:var(--green)"></i></div>
        <p class="meta">UGX <?= e(number_format($raised)) ?> raised of UGX <?= e(number_format((int) $cp['goal_amount'])) ?> (<?= $pct ?>%)<?= $cp['ends_on'] ? ' &middot; ends ' . e(fmt_date($cp['ends_on'])) : '' ?></p>
      <?php endif; ?>
      <a class="btn<?= $camp && $camp['id'] == $cp['id'] ? ' secondary' : '' ?>" href="?c=<?= (int) $cp['id'] ?>#give">Give to this campaign</a>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<div class="grid two" id="give">
  <div>
    <h2><?= $camp ? 'Give to: ' . e($camp['title']) : 'Make a gift' ?></h2>
    <?php if ($camp && $camp['description']): ?><div class="lead" style="font-size:1rem"><?= render_text($camp['description']) ?></div><?php endif; ?>
    <?php if (!$accounts): ?>
      <div class="notice">Online giving details have not been published yet. Please <a href="<?= url('contact.php') ?>">contact the Secretariat</a> to arrange your gift.</div>
    <?php else: ?>
      <ol class="steps">
        <li>Pay using one of the official accounts shown on this page.</li>
        <li>Fill in the form with the transaction ID or reference from your payment.</li>
        <li>The Finance Officer matches it to the money received, then sends your receipt.</li>
      </ol>
      <form method="post" class="stack">
        <?= csrf_field() ?><?= spam_fields() ?>
        <input type="hidden" name="campaign_id" value="<?= (int) ($camp['id'] ?? 0) ?>">
        <div class="cols">
          <label>Your name * <input name="donor_name" required value="<?= e($_POST['donor_name'] ?? ($me['full_name'] ?? '')) ?>" autocomplete="name"></label>
          <label>Email * <input type="email" name="donor_email" required value="<?= e($_POST['donor_email'] ?? ($me['email'] ?? '')) ?>" autocomplete="email"></label>
        </div>
        <div class="cols">
          <label>Telephone <input name="donor_phone" value="<?= e($_POST['donor_phone'] ?? '') ?>" autocomplete="tel"></label>
          <label>Amount (UGX) * <input name="amount" inputmode="numeric" required value="<?= e($_POST['amount'] ?? '') ?>" placeholder="e.g. 50000"></label>
        </div>
        <div class="cols">
          <label>How did you pay? * <select name="method" required><option value="">Select</option><?php foreach (DONATION_METHODS as $k => $l): if ($k === 'card') continue; ?><option value="<?= $k ?>"<?= ($_POST['method'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
          <label>Transaction ID / reference * <input name="reference" required value="<?= e($_POST['reference'] ?? '') ?>"></label>
        </div>
        <?php if (!$camp): ?>
          <label>Support <select name="category"><?php foreach (DONATION_CATEGORIES as $k): ?><option<?= ($_POST['category'] ?? '') === $k ? ' selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?></select></label>
        <?php endif; ?>
        <label>Is this a regular gift? <select name="recurring"><?php foreach (DONATION_RECURRING as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select>
          <span class="hint">Recurring gifts are tracked as a pledge: you repeat the payment each period and submit the new reference.</span></label>
        <label>Message (optional) <textarea name="message" rows="2" maxlength="400"><?= e($_POST['message'] ?? '') ?></textarea></label>
        <label class="check"><input type="checkbox" name="is_anonymous"> Keep my name off the public donor list</label>
        <label class="check"><input type="checkbox" name="consent" required> I confirm the details are correct and agree that GUCO may record my gift and email my receipt.</label>
        <button>Submit my gift</button>
      </form>
    <?php endif; ?>
  </div>
  <div>
    <?php if ($accounts): ?>
      <h2>Where to pay</h2>
      <div class="card accent">
        <?php foreach ($accounts as $l => $v): ?><p><strong><?= e($l) ?>:</strong><br><?= nl2br(e($v)) ?></p><?php endforeach; ?>
        <?php if (setting('donation_note')): ?><p class="meta"><?= nl2br(e(setting('donation_note'))) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>
    <h2>Recent donors</h2>
    <div class="card">
      <?php foreach ($recent as $r): ?>
        <p style="margin:.4em 0"><strong><?= $r['is_anonymous'] ? 'Anonymous donor' : e($r['donor_name']) ?></strong> &middot; UGX <?= e(number_format((int) $r['amount'])) ?><br><span class="meta"><?= e(fmt_date($r['verified_at'])) ?></span></p>
      <?php endforeach; if (!$recent): ?><p class="meta">Verified gifts will be recognised here. Be the first.</p><?php endif; ?>
    </div>
    <p class="meta">Total verified giving to date: <strong>UGX <?= e(number_format(donations_raised())) ?></strong>. Every gift is recorded, verified against the payment received and receipted.</p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
