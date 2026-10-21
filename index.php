<?php
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$news = db()->query('SELECT title, slug, body, published_at FROM news ORDER BY published_at DESC LIMIT 3')->fetchAll();
$events = db()->query('SELECT title, location, starts_at FROM events WHERE starts_at >= NOW() ORDER BY starts_at LIMIT 3')->fetchAll();
?>
<section class="hero">
  <h1>Gulu University Convocation</h1>
  <p>The alumni body of Gulu University. Reconnect with classmates, support the university, and keep growing together.</p>
  <a class="btn" href="<?= url('register.php') ?>">Join the alumni network</a>
</section>

<h2>Latest news</h2>
<div class="grid">
<?php foreach ($news as $n): ?>
  <article class="card">
    <h3><a href="<?= url('news_view.php?slug=' . urlencode($n['slug'])) ?>"><?= e($n['title']) ?></a></h3>
    <p class="meta"><?= e(date('j M Y', strtotime($n['published_at']))) ?></p>
    <p><?= e(mb_strimwidth(strip_tags($n['body']), 0, 140, '…')) ?></p>
  </article>
<?php endforeach; if (!$news): ?><p>No news yet.</p><?php endif; ?>
</div>

<h2>Upcoming events</h2>
<div class="grid">
<?php foreach ($events as $ev): ?>
  <article class="card">
    <h3><?= e($ev['title']) ?></h3>
    <p class="meta"><?= e(date('j M Y, g:ia', strtotime($ev['starts_at']))) ?> &middot; <?= e($ev['location']) ?></p>
  </article>
<?php endforeach; if (!$events): ?><p>No upcoming events.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
