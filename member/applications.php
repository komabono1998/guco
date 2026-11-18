<?php
require_once __DIR__ . '/../includes/portal.php';
require_once __DIR__ . '/../includes/careers.php';
$me = require_login();
$rows = q('SELECT a.*, j.title, j.organisation, j.kind, j.id AS job_id FROM job_applications a JOIN jobs j ON j.id = a.job_id WHERE a.user_id = ? ORDER BY a.created_at DESC', [$me['id']])->fetchAll();
$open = is_member() ? q("SELECT id, title, organisation, kind, deadline FROM jobs WHERE is_public = 1 AND (deadline IS NULL OR deadline >= CURDATE()) ORDER BY id DESC LIMIT 5")->fetchAll() : [];
$tone = ['submitted' => 'warn', 'shortlisted' => '', 'successful' => '', 'rejected' => 'bad'];
portal_header('Careers');
?>
<section class="panel">
  <h2>My applications <a href="<?= e(url('careers.php')) ?>">All opportunities &rarr;</a></h2>
  <?php foreach ($rows as $r): ?>
    <div class="ev-mini"><div><a href="<?= e(url('careers.php?id=' . (int) $r['job_id'])) ?>"><strong><?= e($r['title']) ?></strong></a><br><span class="meta"><?= e($r['organisation']) ?> &middot; <?= e(JOB_KINDS[$r['kind']]) ?> &middot; applied <?= e(fmt_date($r['created_at'])) ?></span></div>
      <span class="badge <?= $tone[$r['status']] ?>" style="margin-left:auto"><?= e(JOB_APPLICATION_STATUS[$r['status']]) ?></span></div>
  <?php endforeach; if (!$rows): ?><p class="meta">You have not applied for anything through GUCO yet.</p><?php endif; ?>
</section>
<?php if ($open): ?>
<section class="panel">
  <h2>Latest opportunities</h2>
  <?php foreach ($open as $o): ?><p style="margin:.4em 0"><a href="<?= e(url('careers.php?id=' . (int) $o['id'])) ?>"><?= e($o['title']) ?></a><br><span class="meta"><?= e($o['organisation']) ?> &middot; <?= e(JOB_KINDS[$o['kind']]) ?><?= $o['deadline'] ? ' &middot; closes ' . e(fmt_date($o['deadline'])) : '' ?></span></p><?php endforeach; ?>
</section>
<?php endif; ?>
<?php portal_footer(); ?>
