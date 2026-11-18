<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Alumni Hall of Fame';
$pageDesc = 'Outstanding Gulu University graduates, entrepreneurs, public servants and innovators honoured by the Convocation.';
$id = (int) ($_GET['id'] ?? 0);
$cat = $_GET['category'] ?? '';
$one = $id ? q('SELECT * FROM hall_of_fame WHERE id = ? AND is_public = 1', [$id])->fetch() : null;
if ($id && !$one) {
    http_response_code(404);
}
$where = 'is_public = 1' . (in_array($cat, FAME_CATEGORIES, true) ? ' AND category = ?' : '');
$all = q("SELECT * FROM hall_of_fame WHERE $where ORDER BY featured DESC, sort_order, inducted_year DESC, name", in_array($cat, FAME_CATEGORIES, true) ? [$cat] : [])->fetchAll();
$photo = fn(array $h) => $h['photo'] ? url('uploads/' . rawurlencode($h['photo'])) : '';
$initials = fn(string $n) => mb_strtoupper(mb_substr(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($n)), 0, 2))), 0, 2));
if ($one) {
    $pageTitle = $one['name'];
}
if (!$id) {
    redirect('awards.php' . (in_array($cat, FAME_CATEGORIES, true) ? '' : '') . '#hall');
}
require __DIR__ . '/includes/header.php';
if ($id && !$one): ?>
  <h1>Not found</h1><p><a href="<?= url('awards.php#hall') ?>">&larr; Awards &amp; Hall of Fame</a></p>
<?php elseif ($one): ?>
  <p><a href="<?= url('awards.php#hall') ?>">&larr; Awards &amp; Hall of Fame</a></p>
  <article class="card accent" style="display:grid;grid-template-columns:200px 1fr;gap:26px;align-items:start">
    <?php if ($photo($one)): ?><img src="<?= e($photo($one)) ?>" alt="<?= e($one['name']) ?>" style="width:100%;border-radius:14px;object-fit:cover;aspect-ratio:1/1.1">
    <?php else: ?><div style="aspect-ratio:1/1.1;border-radius:14px;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:700"><?= e($initials($one['name'])) ?></div><?php endif; ?>
    <div>
      <span class="badge"><?= e($one['category']) ?></span>
      <h1 style="margin:6px 0 2px"><?= e($one['name']) ?></h1>
      <p class="meta"><?= e($one['title']) ?></p>
      <p class="meta"><?= e($one['programme']) ?><?= $one['graduation_year'] ? ' &middot; Class of ' . (int) $one['graduation_year'] : '' ?><?= $one['inducted_year'] ? ' &middot; Inducted ' . (int) $one['inducted_year'] : '' ?></p>
      <?= render_text((string) $one['citation']) ?>
    </div>
  </article>
<?php else: ?>
  <div class="page-head"><h1>Alumni Hall of Fame</h1><p>Recognising outstanding graduates, entrepreneurs, public servants, innovators and distinguished professionals</p></div>
  <form method="get" class="filters"><label>Category <select name="category" onchange="this.form.submit()"><option value="">All</option><?php foreach (FAME_CATEGORIES as $c): ?><option<?= $c === $cat ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label><noscript><button>Show</button></noscript></form>
  <div class="grid two" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
  <?php foreach ($all as $h): ?>
    <a class="card accent" href="?id=<?= (int) $h['id'] ?>" style="text-decoration:none;color:inherit;text-align:center">
      <?php if ($photo($h)): ?><img src="<?= e($photo($h)) ?>" alt="" loading="lazy" style="width:120px;height:120px;border-radius:50%;object-fit:cover;margin:0 auto 10px;display:block">
      <?php else: ?><div style="width:120px;height:120px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;margin:0 auto 10px"><?= e($initials($h['name'])) ?></div><?php endif; ?>
      <strong><?= e($h['name']) ?></strong><br><span class="meta"><?= e($h['title']) ?></span><br>
      <span class="badge" style="margin-top:6px"><?= e($h['category']) ?></span>
      <p class="meta" style="margin:8px 0 0"><?= e(excerpt((string) $h['citation'], 110)) ?></p>
    </a>
  <?php endforeach; ?>
  </div>
  <?php if (!$all): ?><div class="notice">The Hall of Fame will honour its first inductees soon. Know an outstanding alumnus? <a href="<?= url('awards.php') ?>">See the awards and nominations</a> or <a href="<?= url('contact.php') ?>">contact the Secretariat</a>.</div><?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
