<?php
$pageTitle = 'News';
require __DIR__ . '/includes/header.php';
$items = db()->query('SELECT title, slug, body, published_at FROM news ORDER BY published_at DESC')->fetchAll();
?>
<h1>News</h1>
<?php foreach ($items as $n): ?>
  <article class="card" style="margin-bottom:16px">
    <h3><a href="<?= url('news_view.php?slug=' . urlencode($n['slug'])) ?>"><?= e($n['title']) ?></a></h3>
    <p class="meta"><?= e(date('j M Y', strtotime($n['published_at']))) ?></p>
    <p><?= e(mb_strimwidth(strip_tags($n['body']), 0, 220, '…')) ?></p>
  </article>
<?php endforeach; if (!$items): ?><p>No news yet.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
