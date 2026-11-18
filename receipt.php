<?php
// Printable donation receipt. Opened with the secret link emailed to the donor, or by the donor / Finance Officer when logged in.
require_once __DIR__ . '/includes/bootstrap.php';
$d = q("SELECT d.*, c.title AS campaign FROM donations d LEFT JOIN campaigns c ON c.id = d.campaign_id WHERE d.id = ? AND d.status = 'verified'", [(int) ($_GET['id'] ?? 0)])->fetch();
$me = current_user();
$ok = $d && (
    (!empty($_GET['t']) && hash_equals((string) $d['access_token'], (string) $_GET['t']))
    || ($me && ($d['user_id'] == $me['id'] || can('donations')))
);
if (!$ok) {
    http_response_code(404);
    exit('Receipt not found.');
}
header('X-Robots-Tag: noindex');
header('Cache-Control: private, no-store');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Receipt <?= e($d['receipt_no']) ?></title>
<style>
body { font-family: "Segoe UI", system-ui, sans-serif; color: #16251c; background: #eef3ef; margin: 0; padding: 24px; }
.receipt { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #d4ddd6; border-top: 8px solid #14532d; border-radius: 10px; padding: 32px 36px; }
.head { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #f2b807; padding-bottom: 14px; margin-bottom: 18px; }
.head img { border-radius: 50%; }
h1 { font-size: 1.3rem; margin: 0; color: #14532d; }
table { width: 100%; border-collapse: collapse; }
td { padding: 8px 0; border-bottom: 1px solid #e4ebe6; vertical-align: top; }
td:first-child { color: #5a6b60; width: 38%; }
.amt { font-size: 1.5rem; font-weight: 700; color: #14532d; }
.foot { margin-top: 20px; font-size: .85rem; color: #5a6b60; }
button { margin-top: 18px; background: #14532d; color: #fff; border: 0; padding: 10px 20px; border-radius: 8px; cursor: pointer; font: inherit; }
@media print { body { background: #fff; padding: 0; } .receipt { border: 0; } button { display: none; } }
</style>
</head>
<body>
<div class="receipt">
  <div class="head"><img src="<?= e(logo_url()) ?>" alt="" width="56" height="56"><div><h1><?= e(setting('site_name', 'Gulu University Convocation')) ?></h1><div>Official donation receipt</div></div></div>
  <table>
    <tr><td>Receipt number</td><td><strong><?= e($d['receipt_no']) ?></strong></td></tr>
    <tr><td>Received from</td><td><?= e($d['donor_name']) ?><?= $d['is_anonymous'] ? ' (listed publicly as anonymous)' : '' ?></td></tr>
    <tr><td>Amount</td><td class="amt"><?= e($d['currency']) ?> <?= e(number_format((int) $d['amount'])) ?></td></tr>
    <tr><td>For</td><td><?= e($d['campaign'] ?: $d['category']) ?></td></tr>
    <tr><td>Payment method</td><td><?= e(DONATION_METHODS[$d['method']] ?? $d['method']) ?> &middot; ref <?= e($d['reference']) ?></td></tr>
    <tr><td>Date verified</td><td><?= e(fmt_date($d['verified_at'], 'j F Y')) ?></td></tr>
    <?php if ($d['recurring'] !== 'none'): ?><tr><td>Giving pattern</td><td><?= e(DONATION_RECURRING[$d['recurring']]) ?> pledge</td></tr><?php endif; ?>
  </table>
  <p class="foot">Thank you for supporting Gulu University and its students. This receipt was issued after the payment was matched to funds received. Keep it for your records.</p>
  <button onclick="window.print()">Print receipt</button>
</div>
</body>
</html>
