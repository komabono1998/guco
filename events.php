<?php
$pageTitle = 'Events Calendar';
$pageDesc = 'Confirmed GUCO events, the Annual General Meeting and registration information.';
require __DIR__ . '/includes/header.php';
$upcoming = q('SELECT * FROM events WHERE is_public = 1 AND starts_at >= NOW() ORDER BY starts_at')->fetchAll();
$past = q('SELECT * FROM events WHERE is_public = 1 AND starts_at < NOW() ORDER BY starts_at DESC LIMIT 10')->fetchAll();
$badge = fn($s) => $s === 'scheduled' ? '' : '<span class="badge bad">' . e(ucfirst($s)) . '</span>';
?>
<div class="page-head"><h1>Events Calendar</h1><p>Only confirmed events are listed</p></div>
<h2>Upcoming events</h2>
<?php foreach ($upcoming as $ev): ?>
  <article class="card" style="margin-bottom:16px">
    <h3><a href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>"><?= e($ev['title']) ?></a> <?= $badge($ev['status']) ?></h3>
    <p class="meta"><?= e(fmt_date($ev['starts_at'], 'l j M Y, g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?></p>
    <p><?= e(excerpt((string) $ev['description'], 200)) ?></p>
    <?php if ($ev['registration_open'] && $ev['status'] === 'scheduled'): ?><a class="btn" href="<?= url('event_view.php?id=' . (int) $ev['id'] . '#register') ?>">Register</a><?php endif; ?>
  </article>
<?php endforeach; if (!$upcoming): ?><p>No confirmed upcoming events at the moment.</p><?php endif; ?>

<section class="section notice">
  <h2 style="margin-top:0">Annual General Meeting</h2>
  <p>The Constitution states that the AGM should be held not later than November each year. Written notice, accompanied by the annual statement of accounts and agenda, is sent at least 21 days before the meeting. Each AGM notice is published here and under <a href="<?= url('news.php?category=Notices') ?>">Notices</a> only after it has been formally issued by the Association.</p>
</section>

<h2>Event highlights</h2>
<p><strong><?= e(DINNER_ALBUM) ?></strong> &mdash; celebrating excellence, strengthening partnerships and investing in the future of Gulu University.</p>
<div class="photo-strip">
  <?= dinner_figure(14) ?><?= dinner_figure(4) ?><?= dinner_figure(7) ?><?= dinner_figure(13) ?>
</div>
<p><a href="<?= url('gallery.php') ?>">See the full gallery &rarr;</a></p>

<h2>Past events</h2>
<?php foreach ($past as $ev): ?>
  <p><a href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>"><strong><?= e($ev['title']) ?></strong></a> <span class="meta">&middot; <?= e(fmt_date($ev['starts_at'])) ?></span> <?= $badge($ev['status']) ?></p>
<?php endforeach; if (!$past): ?><p>None yet.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
