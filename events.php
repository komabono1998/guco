<?php
$pageTitle = 'Events';
require __DIR__ . '/includes/header.php';
$upcoming = db()->query('SELECT * FROM events WHERE starts_at >= NOW() ORDER BY starts_at')->fetchAll();
$past = db()->query('SELECT * FROM events WHERE starts_at < NOW() ORDER BY starts_at DESC LIMIT 10')->fetchAll();
?>
<h1>Events</h1>
<h2>Upcoming</h2>
<?php foreach ($upcoming as $ev): ?>
  <article class="card" style="margin-bottom:16px">
    <h3><?= e($ev['title']) ?></h3>
    <p class="meta"><?= e(date('l j M Y, g:ia', strtotime($ev['starts_at']))) ?> &middot; <?= e($ev['location']) ?></p>
    <p><?= nl2br(e($ev['description'])) ?></p>
  </article>
<?php endforeach; if (!$upcoming): ?><p>No upcoming events.</p><?php endif; ?>
<h2>Past events</h2>
<?php foreach ($past as $ev): ?>
  <p><strong><?= e($ev['title']) ?></strong> <span class="meta">&middot; <?= e(date('j M Y', strtotime($ev['starts_at']))) ?></span></p>
<?php endforeach; if (!$past): ?><p>None yet.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
