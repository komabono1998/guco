<?php
// Event ticket with QR code. The ticket code in the link is the secret that identifies the registration.
require_once __DIR__ . '/includes/bootstrap.php';
$code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($_GET['code'] ?? '')));
$t = $code !== '' ? q('SELECT r.*, e.title, e.location, e.starts_at, e.ends_at, e.online_link, e.status FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.ticket_code = ?', [$code])->fetch() : null;
if (!$t) {
    http_response_code(404);
    exit('Ticket not found.');
}
header('X-Robots-Tag: noindex');
header('Cache-Control: private, no-store');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Ticket: <?= e($t['title']) ?></title>
<style>
body { font-family: "Segoe UI", system-ui, sans-serif; background: #eef3ef; margin: 0; padding: 24px; color: #16251c; }
.ticket { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #d4ddd6; }
.top { background: linear-gradient(110deg, #0f3d22, #14532d); color: #fff; padding: 20px 24px; border-bottom: 5px solid #f2b807; display: flex; gap: 14px; align-items: center; }
.top img { border-radius: 50%; background: #fff; }
.top h1 { font-size: 1.1rem; margin: 0; }
.body { padding: 22px 24px; }
.body h2 { margin: 0 0 4px; font-size: 1.35rem; color: #14532d; }
.row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px dashed #d4ddd6; font-size: .94rem; }
.row span:first-child { color: #5a6b60; }
.qr { text-align: center; padding: 20px 0 6px; }
.code { font-family: ui-monospace, Consolas, monospace; letter-spacing: .12em; font-size: 1.1rem; font-weight: 700; }
.status { text-align: center; margin: 10px 0 0; padding: 8px; border-radius: 8px; font-weight: 600; }
.ok { background: #e6f2ea; color: #14532d; } .used { background: #fff3cd; color: #8a6700; } .bad { background: #fde8ea; color: #b4232f; }
button { display: block; margin: 16px auto 0; background: #14532d; color: #fff; border: 0; padding: 10px 20px; border-radius: 8px; cursor: pointer; font: inherit; }
@media print { body { background: #fff; padding: 0; } button { display: none; } }
</style>
</head>
<body>
<div class="ticket">
  <div class="top"><img src="<?= e(logo_url()) ?>" alt="" width="46" height="46"><h1><?= e(setting('site_name', 'Gulu University Convocation')) ?><br><small>Event ticket</small></h1></div>
  <div class="body">
    <h2><?= e($t['title']) ?></h2>
    <div class="row"><span>Name</span><span><?= e($t['name']) ?></span></div>
    <div class="row"><span>When</span><span><?= $t['starts_at'] ? e(fmt_date($t['starts_at'], 'D j M Y, g:ia')) : 'To be announced' ?></span></div>
    <?php if ($t['location']): ?><div class="row"><span>Where</span><span><?= e($t['location']) ?></span></div><?php endif; ?>
    <?php if ($t['online_link']): ?><div class="row"><span>Join online</span><span><a href="<?= e($t['online_link']) ?>" rel="noopener nofollow">Open link</a></span></div><?php endif; ?>
    <div class="qr"><div id="qrcode" style="display:inline-block" aria-label="QR code for entry"></div><div class="code"><?= e($t['ticket_code']) ?></div></div>
    <?php if ($t['status'] !== 'scheduled'): ?><div class="status bad">This event has been <?= e($t['status']) ?>.</div>
    <?php elseif ($t['checked_in_at']): ?><div class="status used">Checked in <?= e(fmt_date($t['checked_in_at'], 'j M Y, g:ia')) ?></div>
    <?php else: ?><div class="status ok">Valid. Show this at the entrance.</div><?php endif; ?>
    <button onclick="window.print()">Print ticket</button>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
  var box = document.getElementById('qrcode');
  if (window.QRCode) { new QRCode(box, { text: <?= json_encode($t['ticket_code']) ?>, width: 170, height: 170, correctLevel: QRCode.CorrectLevel.M }); }
  else { box.textContent = 'Show the code below at the entrance.'; }
})();
</script>
</body>
</html>
