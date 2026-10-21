<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pageTitle = 'Inbox';
$msgs = db()->query('SELECT * FROM messages ORDER BY created_at DESC')->fetchAll();
$pledges = db()->query('SELECT * FROM donation_pledges ORDER BY created_at DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>Messages</h1>
<table>
  <?php foreach ($msgs as $m): ?>
  <tr><td><strong><?= e($m['name']) ?></strong><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><br><span class="meta"><?= e($m['created_at']) ?></span></td>
    <td><strong><?= e($m['subject']) ?></strong><br><?= nl2br(e($m['body'])) ?></td></tr>
  <?php endforeach; if (!$msgs): ?><tr><td>No messages.</td></tr><?php endif; ?>
</table>
<h1>Donation pledges</h1>
<table>
  <tr><th>Name</th><th>Email</th><th>Amount (UGX)</th><th>Purpose</th><th>Date</th></tr>
  <?php foreach ($pledges as $p): ?>
  <tr><td><?= e($p['name']) ?></td><td><?= e($p['email']) ?></td><td><?= e(number_format((float) $p['amount'])) ?></td><td><?= e($p['purpose']) ?></td><td><?= e($p['created_at']) ?></td></tr>
  <?php endforeach; if (!$pledges): ?><tr><td colspan="5">No pledges.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
