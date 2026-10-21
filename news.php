<?php
require_once __DIR__ . '/includes/bootstrap.php';
$cat = in_array($_GET['category'] ?? '', NEWS_CATEGORIES, true) ? $_GET['category'] : '';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 8;
$pageTitle = $cat ?: 'News & Stories';
$pageDesc = 'Verified updates, alumni stories, notices and newsletters from Gulu University Convocation.';

$where = "status = 'published' AND published_at <= NOW()";
$args = [];
if ($cat) {
    $where .= ' AND category = ?';
    $args[] = $cat;
}
if ($search !== '') {
    $where .= ' AND (title LIKE ? OR summary LIKE ? OR body LIKE ?)';
    array_push($args, "%$search%", "%$search%", "%$search%");
}
$total = (int) q("SELECT COUNT(*) FROM news WHERE $where", $args)->fetchColumn();
$items = q("SELECT title, slug, category, summary, body, published_at FROM news WHERE $where ORDER BY published_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args)->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1><?= e($cat ?: 'News & Stories') ?></h1><p>Accurate, timely updates about the Association, its members and Gulu University</p></div>
<form method="get" class="filters">
  <label>Search <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search news…"></label>
  <label>Category
    <select name="category"><option value="">All categories</option>
      <?php foreach (NEWS_CATEGORIES as $c): ?><option<?= $c === $cat ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
  </label>
  <button>Filter</button>
</form>
<?php foreach ($items as $n): ?>
  <article class="card" style="margin-bottom:16px">
    <span class="badge"><?= e($n['category']) ?></span>
    <h2 style="margin:.3em 0"><a href="<?= url('news_view.php?slug=' . urlencode($n['slug'])) ?>"><?= e($n['title']) ?></a></h2>
    <p class="meta"><?= e(fmt_date($n['published_at'])) ?></p>
    <p><?= e($n['summary'] ?: excerpt($n['body'], 220)) ?></p>
  </article>
<?php endforeach; if (!$items): ?><p>No articles found.</p><?php endif; ?>
<?= pagination($total, $per, $page, http_build_query(array_filter(['q' => $search, 'category' => $cat]))) ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
