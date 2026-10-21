<?php
require_once __DIR__ . '/includes/bootstrap.php';
$p = q("SELECT p.*, u.full_name, c.name AS chapter FROM projects p JOIN users u ON u.id = p.user_id LEFT JOIN chapters c ON c.id = p.chapter_id
        WHERE p.id = ? AND p.is_public = 1 AND p.status IN ('approved','in_progress','completed')", [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$p) {
    http_response_code(404);
}
$pageTitle = $p['title'] ?? 'Project not found';
$pageDesc = $p ? excerpt((string) $p['summary'], 150) : null;
$raised = $p ? project_raised((int) $p['id']) : 0;
$pct = $p && $p['budget'] > 0 ? min(100, round($raised / $p['budget'] * 100)) : 0;
$updates = $p ? q("SELECT * FROM project_updates WHERE project_id = ? AND (kind = 'progress' OR verified = 1) ORDER BY created_at DESC", [$p['id']])->fetchAll() : [];
require __DIR__ . '/includes/header.php';
if (!$p): ?>
  <h1>Project not found</h1><p><a href="<?= url('projects.php') ?>">Back to community projects</a></p>
<?php else: ?>
  <p><a href="<?= url('projects.php') ?>">&larr; Community projects</a></p>
  <div class="page-head">
    <span class="badge"><?= e($p['theme']) ?></span> <?= project_badge($p['status']) ?>
    <h1 style="margin-top:6px"><?= e($p['title']) ?></h1>
    <p><?= e($p['location']) ?><?= $p['chapter'] ? ' &middot; ' . e($p['chapter']) : '' ?> &middot; Led by <?= e(short_name($p['full_name'])) ?></p>
  </div>
  <div class="two-col" style="grid-template-columns:minmax(0,1.5fr) minmax(0,1fr)">
    <div>
      <?php foreach (['summary' => 'About the project', 'problem' => 'The need', 'objectives' => 'Objectives', 'beneficiaries' => 'Who benefits', 'activities' => 'What will happen', 'outcomes' => 'Expected outcomes'] as $k => $label):
          if (trim((string) $p[$k]) !== ''): ?><h2 style="font-size:1.2rem"><?= e($label) ?></h2><p><?= nl2br(e($p[$k])) ?></p><?php endif; endforeach; ?>
      <h2 style="font-size:1.2rem">Progress</h2>
      <?php foreach ($updates as $u): ?>
        <div class="card" style="margin-bottom:10px"><span class="badge <?= $u['kind'] === 'mobilisation' ? 'warn' : '' ?>"><?= $u['kind'] === 'mobilisation' ? 'Resources received' : 'Update' ?></span>
          <?= $u['kind'] === 'mobilisation' ? '<strong>UGX ' . e(number_format((float) $u['amount'])) . '</strong>' : '' ?>
          <p style="margin:.4em 0"><?= nl2br(e($u['body'])) ?></p><span class="meta"><?= e(fmt_date($u['created_at'])) ?></span></div>
      <?php endforeach; if (!$updates): ?><p class="meta">No updates have been posted yet.</p><?php endif; ?>
    </div>
    <aside>
      <div class="card accent">
        <h3 style="margin-top:0">Resources mobilised</h3>
        <div class="meter"><i style="width:<?= $pct ?>%"></i></div>
        <p><strong>UGX <?= e(number_format($raised)) ?></strong> of UGX <?= e(number_format((float) $p['budget'])) ?> (<?= $pct ?>%)<br><span class="meta">Only contributions verified by the Executive Committee are counted.</span></p>
        <?php if ($p['start_date']): ?><p class="meta">Planned: <?= e(fmt_date($p['start_date'])) ?> to <?= e(fmt_date($p['end_date'])) ?></p><?php endif; ?>
        <a class="btn" href="<?= url('partnerships.php?category=Support+GUCO') ?>">Support this project</a>
        <p class="meta" style="margin-top:10px">Contributions are handled through officially authorised channels. Contact the Secretariat to discuss support.</p>
      </div>
    </aside>
  </div>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
