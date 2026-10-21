<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Verify a membership card';
$pageDesc = 'Check whether a GUCO membership card is valid.';
$id = trim($_GET['id'] ?? '');
$m = null;
if ($id !== '') {
    $m = q("SELECT p.*, u.full_name, u.status FROM member_profiles p JOIN users u ON u.id = p.user_id WHERE p.membership_id = ?", [$id])->fetch();
}
$card = $m ? card_state($m) : null;
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Verify a membership card</h1><p>Enter the membership ID printed on the card, or scan its QR code</p></div>
<form method="get" class="filters">
  <label>Membership ID <input name="id" value="<?= e($id) ?>" placeholder="GUCO/ORD/2026/000001" required style="min-width:280px"></label>
  <button>Verify</button>
</form>
<?php if ($id !== ''): ?>
  <?php if (!$m || $card['state'] === 'none'): ?>
    <div class="verify bad"><h2>Not found</h2><p>No active GUCO membership matches this ID. Check the ID and try again, or <a href="<?= url('contact.php?category=Membership') ?>">contact the Secretariat</a>.</p></div>
  <?php else: ?>
    <div class="verify <?= $card['state'] === 'valid' ? 'ok' : 'bad' ?>">
      <h2><?= $card['state'] === 'valid' ? 'Valid membership' : e($card['label']) ?></h2>
      <p><strong><?= e($m['full_name']) ?></strong><br><?= e(CATEGORY_LABELS[$m['category']]) ?></p>
      <p>Membership ID: <span class="mono"><?= e($m['membership_id']) ?></span><br>
        Member since <?= e(fmt_date($m['approved_at'], 'F Y')) ?><br>
        Valid until: <?= $card['until'] ? e(fmt_date($card['until'])) : 'Lifetime' ?></p>
      <?php if ($card['state'] === 'revoked'): ?><p><strong>This card has been revoked and must not be accepted.</strong></p><?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
