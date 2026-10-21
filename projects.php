<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Community Projects';
$pageDesc = 'Member-led community projects approved by the GUCO Executive Committee, with progress and resource mobilisation.';
$theme = in_array($_GET['theme'] ?? '', PROJECT_THEMES, true) ? $_GET['theme'] : '';
$chapterId = (int) ($_GET['chapter'] ?? 0);
$where = "p.is_public = 1 AND p.status IN ('approved','in_progress','completed')";
$args = [];
if ($theme) {
    $where .= ' AND p.theme = ?';
    $args[] = $theme;
}
if ($chapterId) {
    $where .= ' AND p.chapter_id = ?';
    $args[] = $chapterId;
}
$projects = q("SELECT p.*, u.full_name, c.name AS chapter,
    (SELECT COALESCE(SUM(amount),0) FROM project_updates x WHERE x.project_id = p.id AND x.kind = 'mobilisation' AND x.verified = 1) AS raised
    FROM projects p JOIN users u ON u.id = p.user_id LEFT JOIN chapters c ON c.id = p.chapter_id WHERE $where ORDER BY FIELD(p.status,'in_progress','approved','completed'), p.updated_at DESC", $args)->fetchAll();
$chapters = q('SELECT id, name FROM chapters WHERE is_public = 1 ORDER BY sort_order, name')->fetchAll(PDO::FETCH_KEY_PAIR);
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Community Projects</h1><p>Projects proposed by members, approved by the Executive Committee and led by the members who proposed them</p></div>

<section class="section split" style="align-items:stretch">
  <div>
    <h2 style="margin-top:0">How a project becomes a GUCO project</h2>
    <ol class="steps" style="grid-template-columns:1fr 1fr">
      <li><strong>Propose</strong> A member submits a proposal with objectives, workplan and budget.</li>
      <li><strong>Review</strong> The Executive Committee reviews it and may ask for changes.</li>
      <li><strong>Approve</strong> Approved proposals are published here.</li>
      <li><strong>Lead and mobilise</strong> The proposer leads implementation and mobilises resources, posting updates.</li>
    </ol>
  </div>
  <div class="card accent" style="display:flex;flex-direction:column;justify-content:center;gap:10px">
    <h3 style="margin:0">Have an idea?</h3>
    <p style="margin:0">Approved members can propose a project from the Member Portal.</p>
    <p style="margin:0"><a class="btn" href="<?= url('member/project_form.php') ?>">Propose a project</a>
      <a class="btn secondary" href="<?= url('partnerships.php?category=Support+GUCO') ?>">Support a project</a></p>
  </div>
</section>

<form method="get" class="filters">
  <label>Theme <select name="theme"><option value="">All themes</option><?php foreach (PROJECT_THEMES as $t): ?><option<?= $t === $theme ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></label>
  <label>Chapter <select name="chapter"><option value="">All chapters</option><?php foreach ($chapters as $k => $n): ?><option value="<?= (int) $k ?>"<?= $k == $chapterId ? ' selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?></select></label>
  <button>Filter</button>
</form>

<div class="grid">
  <?php foreach ($projects as $p): $pct = $p['budget'] > 0 ? min(100, round($p['raised'] / $p['budget'] * 100)) : 0; ?>
    <a class="card project-card" href="<?= url('project_view.php?id=' . (int) $p['id']) ?>">
      <div><span class="badge"><?= e($p['theme']) ?></span> <?= project_badge($p['status']) ?></div>
      <h3 style="margin:.4em 0"><?= e($p['title']) ?></h3>
      <p class="meta" style="margin:0"><?= e($p['location']) ?><?= $p['chapter'] ? ' &middot; ' . e($p['chapter']) : '' ?></p>
      <p><?= e(excerpt((string) $p['summary'], 140)) ?></p>
      <div class="meter" aria-label="Funds mobilised"><i style="width:<?= $pct ?>%"></i></div>
      <p class="meta" style="margin:0">UGX <?= e(number_format((float) $p['raised'])) ?> of UGX <?= e(number_format((float) $p['budget'])) ?> (<?= $pct ?>%)</p>
      <p class="meta" style="margin:.4em 0 0">Led by <?= e(short_name($p['full_name'])) ?></p>
    </a>
  <?php endforeach; if (!$projects): ?><p>No approved projects to show yet. Approved member-led projects will appear here.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
