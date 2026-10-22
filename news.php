<?php
require_once __DIR__ . '/includes/bootstrap.php';
$cat = in_array($_GET['category'] ?? '', NEWS_CATEGORIES, true) ? $_GET['category'] : '';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 9;
$pageTitle = $cat ?: 'News & Stories';
$pageDesc = 'Verified updates, alumni stories, notices and links to news about Gulu University and GUCO.';

$where = "status = 'published' AND published_at <= NOW()";
$args = [];
if ($cat) {
    $where .= ' AND category = ?';
    $args[] = $cat;
}
if ($search !== '') {
    $where .= ' AND (title LIKE ? OR summary LIKE ? OR body LIKE ? OR source_name LIKE ?)';
    array_push($args, "%$search%", "%$search%", "%$search%", "%$search%");
}
$total = (int) q("SELECT COUNT(*) FROM news WHERE $where", $args)->fetchColumn();
$items = q("SELECT id, title, slug, category, source_type, external_url, source_name, summary, body, image, published_at FROM news WHERE $where ORDER BY published_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args)->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1><?= e($cat ?: 'News & Stories') ?></h1><p>Updates from GUCO, alumni stories and links to news about Gulu University</p></div>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search news..."></label>
  <label>Category
    <select name="category"><option value="">All categories</option>
      <?php foreach (NEWS_CATEGORIES as $c): ?><option<?= $c === $cat ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
  </label>
  <button>Filter</button>
</form>
<div class="grid">
<?php foreach ($items as $n):
    $external = $n['source_type'] === 'link';
    $href = $external ? $n['external_url'] : url('news_view.php?slug=' . urlencode($n['slug'])); ?>
  <a class="card news-card" href="<?= e($href) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?>>
    <?php if ($n['image']): ?><img src="<?= e(url('uploads/' . rawurlencode($n['image']))) ?>" alt="" loading="lazy" style="width:100%;height:160px;object-fit:cover;border-radius:10px;margin-bottom:8px"><?php endif; ?>
    <div><span class="badge"><?= e($n['category']) ?></span>
      <?php if ($external): ?><span class="badge warn">From <?= e($n['source_name'] ?: parse_url($n['external_url'], PHP_URL_HOST)) ?></span>
      <?php elseif ($n['source_type'] === 'document'): ?><span class="badge warn">Document</span><?php endif; ?></div>
    <h3 style="margin:.4em 0"><?= e($n['title']) ?></h3>
    <p class="meta" style="margin:0"><?= e(fmt_date($n['published_at'])) ?></p>
    <p><?= e($n['summary'] ?: excerpt((string) $n['body'], 150)) ?></p>
    <span class="more" style="color:var(--green);font-weight:600;margin-top:auto"><?= $external ? 'Read at the source &#8599;' : 'Read more &rarr;' ?></span>
  </a>
<?php endforeach; ?>
</div>
<?php if (!$items): ?><p>No articles found.</p><?php endif; ?>
<?= pagination($total, $per, $page, http_build_query(array_filter(['q' => $search, 'category' => $cat]))) ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
