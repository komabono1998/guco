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

<?php
$pjSteps = [
    ['edit', 'Propose', 'A member submits a proposal with objectives, workplan and budget.', ['Approved members', 'From the Member Portal']],
    ['eye', 'Review', 'The Executive Committee reviews it and may ask for changes.', ['Executive Committee', 'Changes may be requested']],
    ['check', 'Approve', 'Approved proposals are published here.', ['Published on this page', 'Open to support']],
    ['target', 'Lead and mobilise', 'The proposer leads implementation and mobilises resources, posting updates.', ['The proposer leads', 'Progress updates posted']],
];
$pjCount = q("SELECT COUNT(*) AS n, COALESCE(SUM(status = 'in_progress'), 0) AS live, COALESCE(SUM(status = 'completed'), 0) AS done FROM projects WHERE is_public = 1 AND status IN ('approved','in_progress','completed')")->fetch();
?>
<section class="pj-flow" id="pjFlow" aria-labelledby="pjTitle">
  <div class="pj-main">
    <span class="eyebrow">From idea to impact</span>
    <h2 id="pjTitle">How a project becomes a GUCO project</h2>
    <div class="pj-track" role="tablist" aria-label="Project steps">
      <i class="pj-line" aria-hidden="true"><b id="pjFill"></b></i>
      <?php foreach ($pjSteps as $i => [$ic, $label]): ?>
        <button type="button" role="tab" class="pj-step<?= $i === 0 ? ' on' : '' ?>" id="pjT<?= $i ?>" aria-controls="pjP<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-i="<?= $i ?>">
          <span class="pj-dot"><?= $i + 1 ?></span><span class="pj-lab"><?= e($label) ?></span>
        </button>
      <?php endforeach; ?>
    </div>
    <div class="pj-stage">
      <?php foreach ($pjSteps as $i => [$ic, $label, $text, $chips]): ?>
        <article class="pj-panel<?= $i === 0 ? ' on' : '' ?>" role="tabpanel" id="pjP<?= $i ?>" aria-labelledby="pjT<?= $i ?>"<?= $i === 0 ? '' : ' hidden' ?>>
          <span class="pj-big" aria-hidden="true"><?= $i + 1 ?></span>
          <span class="pj-ic"><?= icon($ic, 30) ?></span>
          <div class="pj-body">
            <small>Step <?= $i + 1 ?> of <?= count($pjSteps) ?></small>
            <h3><?= e($label) ?></h3>
            <p><?= e($text) ?></p>
            <p class="pj-chips"><?php foreach ($chips as $c): ?><span><?= icon('check', 13) ?> <?= e($c) ?></span><?php endforeach; ?></p>
          </div>
        </article>
      <?php endforeach; ?>
      <div class="pj-nav">
        <button type="button" class="pj-prev" id="pjPrev" aria-label="Previous step"><?= icon('arrow-right', 18) ?></button>
        <button type="button" class="pj-next" id="pjNext" aria-label="Next step"><?= icon('arrow-right', 18) ?></button>
      </div>
    </div>
  </div>
  <aside class="pj-cta">
    <span class="pj-cta-ic"><?= icon('target', 28) ?></span>
    <h3>Have an idea?</h3>
    <p>Approved members can propose a project from the Member Portal.</p>
    <div class="pj-cta-btns">
      <a class="btn gold" href="<?= url('member/project_form.php') ?>">Propose a project <?= icon('arrow-right', 16) ?></a>
      <a class="btn outline" href="<?= url('partnerships.php?category=Support+GUCO') ?>">Support a project</a>
    </div>
    <?php if ((int) $pjCount['n'] > 0): ?>
      <ul class="pj-stats"><li><b><?= (int) $pjCount['n'] ?></b><span>Projects</span></li><li><b><?= (int) $pjCount['live'] ?></b><span>In progress</span></li><li><b><?= (int) $pjCount['done'] ?></b><span>Completed</span></li></ul>
    <?php endif; ?>
  </aside>
</section>
<script>
(function () {
  var box = document.getElementById('pjFlow'); if (!box) return;
  var tabs = [].slice.call(box.querySelectorAll('.pj-step')), panels = [].slice.call(box.querySelectorAll('.pj-panel'));
  var fill = document.getElementById('pjFill'), cur = 0, timer, hover = false;
  function show(i) {
    cur = (i + tabs.length) % tabs.length;
    tabs.forEach(function (t, k) { var on = k === cur; t.classList.toggle('on', on); t.classList.toggle('done', k < cur); t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1; });
    panels.forEach(function (p, k) { p.hidden = k !== cur; p.classList.toggle('on', k === cur); });
    fill.style.width = (cur / (tabs.length - 1) * 100) + '%';
  }
  function stop() { clearInterval(timer); timer = null; }
  tabs.forEach(function (t, k) {
    t.addEventListener('click', function () { stop(); show(k); });
    t.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight') { stop(); show(cur + 1); tabs[cur].focus(); } if (e.key === 'ArrowLeft') { stop(); show(cur - 1); tabs[cur].focus(); } });
  });
  document.getElementById('pjNext').addEventListener('click', function () { stop(); show(cur + 1); });
  document.getElementById('pjPrev').addEventListener('click', function () { stop(); show(cur - 1); });
  box.addEventListener('mouseenter', function () { hover = true; });
  box.addEventListener('mouseleave', function () { hover = false; });
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    timer = setInterval(function () { if (!hover && !document.hidden) show(cur + 1); }, 5500);
  }
  show(0);
})();
</script>

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
  <?php endforeach; if (!$projects): ?><div class="empty-state"><?= icon('target', 36) ?><strong>No approved projects yet</strong><span>Approved member-led projects will appear here.</span></div><?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
