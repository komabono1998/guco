<?php
$pageTitle = 'Events Calendar';
$pageDesc = 'Upcoming GUCO events: the GUCO Dinner, Annual General Meeting, GUCO League and more, with dates, venues and registration.';
require __DIR__ . '/includes/header.php';
$cat = in_array($_GET['category'] ?? '', EVENT_CATEGORIES, true) ? $_GET['category'] : '';
$catSql = $cat ? ' AND category = ?' : '';
$args = $cat ? [$cat] : [];
// Confirmed dates first (soonest first), then events whose date is still to be announced.
$upcoming = q("SELECT * FROM events WHERE is_public = 1 AND (starts_at >= NOW() OR starts_at IS NULL)$catSql ORDER BY starts_at IS NULL, starts_at", $args)->fetchAll();
$past = q("SELECT * FROM events WHERE is_public = 1 AND starts_at < NOW()$catSql ORDER BY starts_at DESC LIMIT 12", $args)->fetchAll();
?>
<div class="page-head"><h1>Events Calendar</h1><p>Dates are published once confirmed by the Secretariat</p></div>
<form method="get" class="filters">
  <label>Category <select name="category"><option value="">All events</option>
    <?php foreach (EVENT_CATEGORIES as $c): ?><option<?= $c === $cat ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
  <button>Filter</button>
</form>

<h2>Upcoming events</h2>
<div class="grid">
<?php foreach ($upcoming as $ev): $fl = event_flyer_url($ev); ?>
  <article class="card event-card">
    <?php if ($fl): ?><a href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>"><img class="flyer" src="<?= e($fl) ?>" alt="Flyer for <?= e($ev['title']) ?>" loading="lazy"></a><?php endif; ?>
    <span class="badge"><?= e($ev['category']) ?></span> <?= event_status_badge($ev) ?>
    <h3><a href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>"><?= e($ev['title']) ?></a></h3>
    <p class="meta"><?= e(event_when($ev)) ?><?= event_where($ev) ? ' &middot; ' . e(event_where($ev)) : '' ?></p>
    <?= event_countdown($ev, 'small') ?>
    <p><?= e(excerpt((string) $ev['description'], 140)) ?></p>
    <p><a class="btn secondary" href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>">Details</a>
      <?php if ($ev['registration_open'] && $ev['status'] === 'scheduled'): ?><a class="btn" href="<?= url('event_view.php?id=' . (int) $ev['id'] . '#register') ?>">Register</a><?php endif; ?></p>
  </article>
<?php endforeach; ?>
</div>
<?php if (!$upcoming): ?><p>No confirmed upcoming events at the moment.</p><?php endif; ?>

<section class="section notice">
  <h2 style="margin-top:0">Annual General Meeting</h2>
  <?= cr('events.agm') ?>
  <p><a href="<?= url('news.php?category=Notices') ?>">View official notices &rarr;</a></p>
</section>

<h2>Event highlights</h2>
<p><strong><?= e(DINNER_ALBUM) ?></strong> &mdash; celebrating excellence, strengthening partnerships and investing in the future of Gulu University.</p>
<div class="photo-strip">
  <?= dinner_figure(14) ?><?= dinner_figure(4) ?><?= dinner_figure(7) ?><?= dinner_figure(13) ?>
</div>
<p><a href="<?= url('gallery.php') ?>">See the full gallery &rarr;</a></p>

<h2>Past events</h2>
<?php foreach ($past as $ev): ?>
  <p><span class="badge"><?= e($ev['category']) ?></span> <a href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>"><strong><?= e($ev['title']) ?></strong></a> <span class="meta">&middot; <?= e(fmt_date($ev['starts_at'])) ?></span> <?= event_status_badge($ev) ?></p>
<?php endforeach; if (!$past): ?><p>None yet.</p><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
