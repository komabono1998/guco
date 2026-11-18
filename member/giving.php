<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
$rows = q('SELECT d.*, c.title AS campaign FROM donations d LEFT JOIN campaigns c ON c.id = d.campaign_id WHERE d.user_id = ? OR d.donor_email = ? ORDER BY d.created_at DESC', [$me['id'], $me['email']])->fetchAll();
$badge = ['pending' => ['Awaiting verification', 'warn'], 'verified' => ['Verified', ''], 'rejected' => ['Not matched', 'bad']];
$total = array_sum(array_map(fn($r) => $r['status'] === 'verified' ? (int) $r['amount'] : 0, $rows));
portal_header('My giving');
?>
<section class="panel">
  <h2>Your contributions <a href="<?= e(url('donate.php')) ?>">Give now &rarr;</a></h2>
  <p>Total verified giving: <strong>UGX <?= e(number_format($total)) ?></strong></p>
  <?php foreach ($rows as $r): [$bl, $bc] = $badge[$r['status']]; ?>
    <div class="ev-mini">
      <div><strong>UGX <?= e(number_format((int) $r['amount'])) ?></strong> &middot; <?= e($r['campaign'] ?: $r['category']) ?><br>
        <span class="meta"><?= e(fmt_date($r['created_at'])) ?> &middot; <?= e(DONATION_METHODS[$r['method']] ?? $r['method']) ?> &middot; <?= e($r['reference']) ?><?= $r['reject_reason'] ? ' &middot; ' . e($r['reject_reason']) : '' ?></span></div>
      <span style="margin-left:auto;text-align:right"><span class="badge <?= $bc ?>"><?= e($bl) ?></span>
        <?php if ($r['status'] === 'verified'): ?><br><a href="<?= e(url('receipt.php?id=' . (int) $r['id'])) ?>" target="_blank" rel="noopener">Receipt <?= e($r['receipt_no']) ?></a><?php endif; ?></span>
    </div>
  <?php endforeach; if (!$rows): ?><p class="meta">You have not made a contribution yet. <a href="<?= e(url('donate.php')) ?>">See the current campaigns</a>.</p><?php endif; ?>
</section>
<?php portal_footer(); ?>
