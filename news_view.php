<?php
require_once __DIR__ . '/includes/bootstrap.php';
$s = db()->prepare('SELECT * FROM news WHERE slug = ?');
$s->execute([$_GET['slug'] ?? '']);
$n = $s->fetch();
if (!$n) {
    http_response_code(404);
}
$pageTitle = $n['title'] ?? 'Not found';
require __DIR__ . '/includes/header.php';
if (!$n): ?>
  <h1>Article not found</h1>
<?php else: ?>
  <h1><?= e($n['title']) ?></h1>
  <p class="meta"><?= e(date('j M Y', strtotime($n['published_at']))) ?></p>
  <div><?= nl2br(e($n['body'])) ?></div>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
