<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
$mine = q('SELECT p.*, c.name AS chapter FROM projects p LEFT JOIN chapters c ON c.id = p.chapter_id WHERE p.user_id = ? ORDER BY p.updated_at DESC', [$me['id']])->fetchAll();
$leading = array_filter($mine, fn($p) => in_array($p['status'], PROJECT_ACTIVE, true));
$community = q("SELECT p.id, p.title, p.theme, p.status, p.budget, u.full_name FROM projects p JOIN users u ON u.id = p.user_id WHERE p.is_public = 1 AND p.status IN ('approved','in_progress','completed') AND p.user_id <> ? ORDER BY p.updated_at DESC LIMIT 6", [$me['id']])->fetchAll();
portal_header('Projects');
?>
<section class="welcome" style="margin-bottom:18px">
  <div><h2>Lead a project that changes lives</h2><p>Propose a project, have it reviewed by the Executive Committee and, once approved, lead its implementation and mobilise resources.</p></div>
  <div class="actions"><?php if (is_member()): ?><a class="btn gold" href="project_form.php">Propose a project</a><?php endif; ?><a class="btn outline" href="<?= e(url('projects.php')) ?>">See community projects</a></div>
</section>
<?php if (!is_member()): ?><div class="notice">Project proposals open once your membership is approved.</div><?php endif; ?>

<?php if ($leading): ?>
<section class="panel">
  <h2>Projects I am leading</h2>
  <?php foreach ($leading as $p): $raised = project_raised((int) $p['id']); $pct = $p['budget'] > 0 ? min(100, round($raised / $p['budget'] * 100)) : 0; ?>
    <div class="ev-mini" style="align-items:flex-start">
      <div style="flex:1"><a href="project.php?id=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a> <?= project_badge($p['status']) ?>
        <div class="meter"><i style="width:<?= $pct ?>%"></i></div>
        <span class="meta">UGX <?= e(number_format($raised)) ?> verified of UGX <?= e(number_format((float) $p['budget'])) ?> (<?= $pct ?>%)</span></div>
      <a class="btn" href="project.php?id=<?= (int) $p['id'] ?>#updates">Post update</a>
    </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<section class="panel">
  <h2>My proposals</h2>
  <div class="table-wrap"><table>
    <tr><th>Project</th><th>Theme</th><th>Budget (UGX)</th><th>Status</th><th>Updated</th><th></th></tr>
    <?php foreach ($mine as $p): ?>
      <tr><td><a href="project.php?id=<?= (int) $p['id'] ?>"><strong><?= e($p['title']) ?></strong></a><br><span class="meta"><?= e($p['chapter']) ?></span></td>
        <td><?= e($p['theme']) ?></td><td><?= e(number_format((float) $p['budget'])) ?></td><td><?= project_badge($p['status']) ?></td>
        <td><?= e(fmt_date($p['updated_at'])) ?></td>
        <td><?= in_array($p['status'], PROJECT_EDITABLE, true) ? '<a class="btn secondary" href="project_form.php?id=' . (int) $p['id'] . '">Edit</a>' : '<a class="btn secondary" href="project.php?id=' . (int) $p['id'] . '">Open</a>' ?></td></tr>
    <?php endforeach; if (!$mine): ?><tr><td colspan="6">You have not proposed a project yet.</td></tr><?php endif; ?>
  </table></div>
</section>

<?php if ($community): ?>
<section class="panel">
  <h2>Community projects <a href="<?= e(url('projects.php')) ?>">View all &rarr;</a></h2>
  <?php foreach ($community as $p): ?><div class="ev-mini"><div><a href="<?= e(url('project_view.php?id=' . (int) $p['id'])) ?>"><?= e($p['title']) ?></a><br><span class="meta"><?= e($p['theme']) ?> &middot; led by <?= e(short_name($p['full_name'])) ?></span></div><span style="margin-left:auto"><?= project_badge($p['status']) ?></span></div><?php endforeach; ?>
</section>
<?php endif; ?>
<?php portal_footer(); ?>
