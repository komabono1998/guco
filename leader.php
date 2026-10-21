<?php
require_once __DIR__ . '/includes/bootstrap.php';
$l = q("SELECT * FROM leaders WHERE id = ? AND is_public = 1 AND name <> ''", [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$l) {
    http_response_code(404);
}
$organs = ['patron' => 'General Assembly', 'board' => 'The Board', 'executive' => 'Executive Committee', 'disciplinary' => 'Disciplinary Committee'];
$pageTitle = $l ? $l['name'] . ', ' . $l['title'] : 'Profile not found';
$pageDesc = $l ? excerpt((string) ($l['bio'] ?: $l['role_description']), 150) : null;
require __DIR__ . '/includes/header.php';
if (!$l): ?>
  <h1>Profile not found</h1><p><a href="<?= url('leadership.php') ?>">Back to Leadership &amp; Governance</a></p>
<?php else: ?>
  <p><a href="<?= url('leadership.php#' . ($l['organ'] === 'patron' ? 'assembly' : $l['organ'])) ?>">&larr; <?= e($organs[$l['organ']]) ?></a></p>
  <article class="profile">
    <div><?= leader_photo($l, 'large') ?></div>
    <div>
      <h1 style="margin-bottom:4px"><?= e($l['name']) ?></h1>
      <p class="lead" style="margin-top:0"><?= e($l['title']) ?><?= $l['term'] ? ' <span class="meta">&middot; ' . e($l['term']) . '</span>' : '' ?></p>
      <?php if ($l['bio']): ?><h2>Profile</h2><p><?= nl2br(e($l['bio'])) ?></p><?php endif; ?>
      <?php if ($l['role_description']): ?><h2>Role</h2><p><?= nl2br(e($l['role_description'])) ?></p><?php endif; ?>
    </div>
  </article>
<?php endif;
require __DIR__ . '/includes/footer.php'; ?>
