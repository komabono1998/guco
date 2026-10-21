<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login();
$m = q('SELECT p.*, u.full_name, u.status, c.name AS chapter_name FROM member_profiles p JOIN users u ON u.id = p.user_id LEFT JOIN chapters c ON c.id = p.chapter_id WHERE p.user_id = ?', [$me['id']])->fetch();
$card = $m ? card_state($m) : ['state' => 'none'];
if (!$m || $card['state'] === 'none' || $card['state'] === 'revoked') {
    flash($card['state'] === 'revoked' ? 'Your membership card has been revoked.' : 'Your card is issued when your membership is approved.', 'error');
    redirect('portal.php');
}
$pageTitle = 'Membership card';
$verifyUrl = (($_SERVER['HTTPS'] ?? '') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('verify.php?id=' . urlencode($m['membership_id']));
require __DIR__ . '/includes/header.php';
?>
<div class="page-head noprint"><h1>My membership card</h1><p>Present the card, or let others scan the code to verify it</p></div>
<?php if ($card['state'] === 'expired'): ?><p class="notice noprint"><strong>Your subscription has expired.</strong> The card shows as expired until your renewal payment is verified. <a href="<?= url('portal.php#renew') ?>">Renew now</a></p><?php endif; ?>

<div class="idcard<?= $card['state'] === 'expired' ? ' expired' : '' ?>" id="idcard">
  <div class="idcard-head">
    <img src="<?= url('assets/img/logo.webp') ?>" alt="" width="46" height="46">
    <div><strong>Gulu University Convocation</strong><small>Community Transformation Actualized</small></div>
  </div>
  <div class="idcard-body">
    <?= member_avatar($m, 'idphoto') ?>
    <div class="idcard-info">
      <div class="idname"><?= e($m['full_name']) ?></div>
      <div><?= e(CATEGORY_LABELS[$m['category']]) ?></div>
      <div class="idrow"><span>Membership ID</span><b class="mono"><?= e($m['membership_id']) ?></b></div>
      <div class="idrow"><span>Member no.</span><b><?= e(member_no_label($m['member_no'])) ?></b></div>
      <div class="idrow"><span>Member since</span><b><?= e(fmt_date($m['approved_at'], 'M Y')) ?></b></div>
      <div class="idrow"><span>Valid until</span><b><?= $card['until'] ? e(fmt_date($card['until'])) : 'Lifetime' ?></b></div>
    </div>
    <div class="idqr"><div id="qrcode" aria-label="QR code linking to card verification"></div><small>Scan to verify</small></div>
  </div>
  <?php if ($card['state'] === 'expired'): ?><div class="idstamp">EXPIRED</div><?php endif; ?>
</div>

<p class="noprint" style="margin-top:20px"><button type="button" onclick="window.print()">Print card</button>
  <a class="btn secondary" href="<?= e($verifyUrl) ?>">Open verification page</a></p>
<p class="meta noprint">Verification link: <span class="mono"><?= e($verifyUrl) ?></span></p>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  (function () {
    var box = document.getElementById('qrcode');
    if (window.QRCode) {
      new QRCode(box, { text: <?= json_encode($verifyUrl) ?>, width: 96, height: 96, correctLevel: QRCode.CorrectLevel.M });
    } else {
      box.textContent = 'QR unavailable offline';
    }
  })();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
