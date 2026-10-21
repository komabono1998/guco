<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Member Portal';
$me = require_login();

// Renewal / additional payment submission with proof.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment'])) {
    csrf_check();
    $amount = (float) ($_POST['amount'] ?? 0);
    $proof = ($_FILES['proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? save_private_file($_FILES['proof'], 'proof') : null;
    if ($amount <= 0 || trim($_POST['reference'] ?? '') === '' || !$proof) {
        flash('Enter the amount and reference and upload a proof of payment (JPG, PNG, WebP or PDF, up to 5MB).', 'error');
    } else {
        q('INSERT INTO member_payments (user_id, purpose, method, amount, reference, paid_on, proof_file, proof_original) VALUES (?,?,?,?,?,?,?,?)', [
            $me['id'], in_array($_POST['purpose'] ?? '', ['renewal', 'other'], true) ? $_POST['purpose'] : 'renewal',
            in_array($_POST['method'] ?? '', PAYMENT_METHODS, true) ? $_POST['method'] : 'Other', $amount,
            trim($_POST['reference']), ($_POST['paid_on'] ?? '') ?: null, $proof['name'], $proof['original'],
        ]);
        flash('Payment submitted. The Secretariat will verify it and update your membership.');
    }
    redirect('portal.php#payments');
}

$p = q('SELECT p.*, c.name AS chapter_name FROM member_profiles p LEFT JOIN chapters c ON c.id = p.chapter_id WHERE p.user_id = ?', [$me['id']])->fetch() ?: [];
$card = card_state($p + ['status' => $me['status']]);
$payments = q('SELECT * FROM member_payments WHERE user_id = ? ORDER BY created_at DESC', [$me['id']])->fetchAll();
$regs = q('SELECT e.id, e.title, e.starts_at FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? AND e.starts_at >= NOW() ORDER BY e.starts_at', [$me['id']])->fetchAll();
$approved = $me['status'] === 'approved';
$member = is_member();
$announcements = $member ? q("SELECT title, slug, published_at FROM news WHERE status='published' AND category IN ('GUCO Announcements','Notices') AND published_at <= NOW() ORDER BY published_at DESC LIMIT 4")->fetchAll() : [];
$hasVerified = (bool) array_filter($payments, fn($x) => $x['status'] === 'verified');
$hasPending = (bool) array_filter($payments, fn($x) => $x['status'] === 'pending');
$stepDone = ['Application submitted' => true, 'Payment verified' => $hasVerified, 'Membership approved' => $approved && !empty($p['membership_id']), 'Card issued' => ($p['card_status'] ?? '') === 'active' || ($p['card_status'] ?? '') === 'revoked'];
$renewDue = $card['state'] === 'expired' || ($card['until'] && $card['until'] <= date('Y-m-d', strtotime('+30 days')));
$payBadge = ['pending' => ['Awaiting verification', 'warn'], 'verified' => ['Verified', ''], 'rejected' => ['Rejected', 'bad']];
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Member Portal</h1><p>Welcome, <?= e($me['full_name']) ?><?= !empty($p['member_no']) ? ' &middot; Member no. ' . e(member_no_label($p['member_no'])) : '' ?></p></div>

<?php if ($card['state'] === 'revoked'): ?>
  <div class="notice bad"><strong>Your membership card has been revoked.</strong> <?= e($p['card_revoke_reason'] ?? '') ?> Member-only features are unavailable. Please <a href="<?= url('contact.php?category=Membership') ?>">contact the Secretariat</a>.</div>
<?php elseif (!$approved): ?>
  <div class="notice"><strong>Application status: <?= $me['status'] === 'pending' ? 'pending' : e($me['status']) ?>.</strong> The Secretariat is verifying your details and proof of payment. Your membership ID and card are issued on approval.</div>
<?php elseif ($renewDue): ?>
  <div class="notice"><strong>Subscription renewal <?= $card['state'] === 'expired' ? 'overdue' : 'due soon' ?>.</strong> Submit your payment below to keep your card valid.</div>
<?php endif; ?>
<?php if (is_staff()): ?>
  <p class="staff-bar"><strong>Staff account.</strong> <a class="btn" href="<?= url('admin/') ?>">Open the admin dashboard</a></p>
<?php endif; ?>

<ol class="timeline" aria-label="Application progress">
  <?php foreach ($stepDone as $label => $done): ?><li class="<?= $done ? 'done' : '' ?>"><?= e($label) ?></li><?php endforeach; ?>
</ol>

<div class="grid">
  <div class="card">
    <h3>Membership</h3>
    <p><strong><?= e(CATEGORY_LABELS[$p['category'] ?? 'ordinary']) ?></strong><br>
      <span class="badge <?= $approved ? '' : 'warn' ?>"><?= $approved ? 'Approved' : 'Application ' . e($me['status']) ?></span></p>
    <?php if (!empty($p['membership_id'])): ?><p>Membership ID<br><strong class="mono"><?= e($p['membership_id']) ?></strong></p><?php endif; ?>
    <p>Subscription: <span class="badge <?= ['unpaid' => 'warn', 'active' => '', 'arrears' => 'bad'][$p['subscription_status'] ?? 'unpaid'] ?>"><?= e(['unpaid' => 'Awaiting payment confirmation', 'active' => 'Active', 'arrears' => 'In arrears'][$p['subscription_status'] ?? 'unpaid']) ?></span>
      <?= !empty($p['paid_until']) ? '<br><span class="meta">Paid up to ' . e(fmt_date($p['paid_until'])) . '</span>' : '' ?></p>
    <?php if ($card['state'] !== 'none'): ?>
      <p>Card: <span class="badge <?= $card['state'] === 'valid' ? '' : 'bad' ?>"><?= e($card['label']) ?></span></p>
      <?php if ($card['state'] !== 'revoked'): ?><a class="btn" href="<?= url('card.php') ?>">View &amp; print my card</a><?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>My profile</h3>
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:8px"><?= member_avatar($p + ['full_name' => $me['full_name']], 'avatar') ?>
      <div><?= e($p['programme'] ?? '') ?> <?= !empty($p['graduation_year']) ? '(' . (int) $p['graduation_year'] . ')' : '' ?><br>
        <span class="meta"><?= e($p['college'] ?? '') ?></span></div></div>
    <p>Chapter: <?= e($p['chapter_name'] ?? 'Not set') ?><br>Directory: <?= !empty($p['directory_consent']) ? 'visible' : 'hidden' ?></p>
    <a class="btn secondary" href="<?= url('profile.php') ?>">Update my profile</a>
  </div>
  <?php if ($member): ?>
  <div class="card">
    <h3>Member tools</h3>
    <ul class="plain">
      <li><a href="<?= url('directory.php') ?>">Members' directory</a></li>
      <li><a href="<?= url('resources.php') ?>">Documents &amp; newsletters</a></li>
      <li><a href="<?= url('events.php') ?>">Events &amp; registration</a></li>
      <li><a href="<?= url('chapters.php') ?>">Chapters</a></li>
    </ul>
  </div>
  <?php endif; ?>
</div>

<section id="payments" class="section">
  <h2>My payments</h2>
  <div class="table-wrap"><table>
    <tr><th>Date</th><th>Purpose</th><th>Method</th><th>Amount (UGX)</th><th>Reference</th><th>Status</th><th>Proof</th></tr>
    <?php foreach ($payments as $x): [$pl, $pc] = $payBadge[$x['status']]; ?>
      <tr><td><?= e(fmt_date($x['paid_on'] ?: $x['created_at'])) ?></td><td><?= e(ucfirst($x['purpose'])) ?></td><td><?= e($x['method']) ?></td>
        <td><?= e(number_format((float) $x['amount'])) ?></td><td><?= e($x['reference']) ?></td>
        <td><span class="badge <?= $pc ?>"><?= e($pl) ?></span><?= $x['admin_note'] ? '<br><span class="meta">' . e($x['admin_note']) . '</span>' : '' ?></td>
        <td><?= $x['proof_file'] ? '<a href="' . e(member_file_url('proof', (int) $x['id'])) . '" target="_blank" rel="noopener">View</a>' : '' ?></td></tr>
    <?php endforeach; if (!$payments): ?><tr><td colspan="7">No payments recorded yet.</td></tr><?php endif; ?>
  </table></div>

  <h3 id="renew" style="margin-top:28px">Renew or submit another payment</h3>
  <p class="meta">Ordinary members pay an annual subscription under Article 9. Confirm the current amount and payment channel with the Secretariat, pay, then upload your proof here.</p>
  <form method="post" enctype="multipart/form-data" class="stack wide">
    <?= csrf_field() ?><input type="hidden" name="payment" value="1">
    <div class="cols">
      <label>Purpose <select name="purpose"><option value="renewal">Subscription renewal</option><option value="other">Other</option></select></label>
      <label>Payment method <select name="method"><?php foreach (PAYMENT_METHODS as $m): ?><option><?= e($m) ?></option><?php endforeach; ?></select></label>
      <label>Amount paid (UGX) <input type="number" name="amount" min="1" step="any" required></label>
    </div>
    <div class="cols">
      <label>Reference <input name="reference" required></label>
      <label>Date paid <input type="date" name="paid_on" max="<?= date('Y-m-d') ?>"></label>
    </div>
    <label>Proof of payment <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
    <button>Submit payment</button>
  </form>
</section>

<section class="section grid two">
  <div>
    <h2>My event registrations</h2>
    <?php foreach ($regs as $r): ?><p><a href="<?= url('event_view.php?id=' . (int) $r['id']) ?>"><?= e($r['title']) ?></a> <span class="meta">&middot; <?= e(fmt_date($r['starts_at'], 'j M Y, g:ia')) ?></span></p><?php endforeach; if (!$regs): ?><p>No upcoming registrations.</p><?php endif; ?>
  </div>
  <?php if ($member): ?>
  <div>
    <h2>Member announcements</h2>
    <?php foreach ($announcements as $a): ?><p><a href="<?= url('news_view.php?slug=' . urlencode($a['slug'])) ?>"><?= e($a['title']) ?></a> <span class="meta">&middot; <?= e(fmt_date($a['published_at'])) ?></span></p><?php endforeach; if (!$announcements): ?><p>No announcements.</p><?php endif; ?>
  </div>
  <?php endif; ?>
</section>

<p><a href="<?= url('contact.php?category=Profile+correction') ?>">Need a correction or help? Send a support request &rarr;</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
