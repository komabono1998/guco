<?php
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$on = fn($k) => setting('home_show_' . $k, '1') === '1';
$news = $on('news') ? q("SELECT title, slug, category, summary, body, published_at FROM news WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll() : [];
$events = $on('events') ? q("SELECT * FROM events WHERE is_public = 1 AND (starts_at >= NOW() OR starts_at IS NULL) ORDER BY starts_at IS NULL, starts_at LIMIT 3")->fetchAll() : [];
// Spotlight: the soonest confirmed event flagged as featured, else the soonest with a countdown.
$spot = q("SELECT * FROM events WHERE is_public = 1 AND status = 'scheduled' AND starts_at >= NOW() AND (featured = 1 OR show_countdown = 1) ORDER BY featured DESC, starts_at LIMIT 1")->fetch();
$newsletter = $on('news') ? q("SELECT title, slug, summary, body, published_at FROM news WHERE status='published' AND category='Newsletter' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 1")->fetch() : null;
$awards = $on('awards') ? q("SELECT name FROM awards WHERE is_public = 1 ORDER BY id LIMIT 4")->fetchAll(PDO::FETCH_COLUMN) : [];
$heroImg = c_image('home.hero_image');
?>
<section class="hero">
  <img src="<?= e($heroImg ?: logo_url()) ?>" alt="<?= e(setting('site_name', 'Gulu University Convocation')) ?>"<?= $heroImg ? ' class="photo-hero"' : '' ?>>
  <div>
    <p class="tagline"><?= ct('home.hero_tagline') ?></p>
    <h1><?= ct('home.hero_title') ?></h1>
    <?= cr('home.hero_text') ?>
    <div class="actions">
      <a class="btn" href="<?= url('register.php') ?>"><?= ct('home.hero_btn1') ?></a>
      <a class="btn outline" href="<?= url('programmes.php') ?>"><?= ct('home.hero_btn2') ?></a>
    </div>
  </div>
</section>

<?php if ($spot): $fl = event_flyer_url($spot); ?>
<section class="event-hero on-dark" style="<?= $fl ? '' : 'grid-template-columns:1fr' ?>" aria-label="Next event">
  <?php if ($fl): ?><a href="<?= url('event_view.php?id=' . (int) $spot['id']) ?>"><img src="<?= e($fl) ?>" alt="Flyer for <?= e($spot['title']) ?>"></a><?php endif; ?>
  <div>
    <span class="badge">Next event &middot; <?= e($spot['category']) ?></span>
    <h2><?= e($spot['title']) ?></h2>
    <p><?= e(event_when($spot, 'l j M Y, g:ia')) ?><?= event_where($spot) ? ' &middot; ' . e(event_where($spot)) : '' ?></p>
    <?= event_countdown($spot) ?>
    <a class="btn" href="<?= url('event_view.php?id=' . (int) $spot['id']) ?>">View details</a>
    <?php if ($spot['registration_open']): ?><a class="btn outline" href="<?= url('event_view.php?id=' . (int) $spot['id'] . '#register') ?>">Register</a><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('welcome')): ?>
<section class="section">
  <h2><?= ct('home.welcome_title') ?></h2>
  <div class="lead"><?= cr('home.welcome_text') ?></div>
</section>
<?php endif; ?>

<?php if ($on('cards')): ?>
<section class="grid">
  <?php foreach (c_pairs('home.cards', 3) as [$t, $d, $href]): ?>
    <a class="card accent" href="<?= e(safe_link($href) ?: url()) ?>"><h3><?= e($t) ?></h3><p><?= e($d) ?></p></a>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($on('news')): ?>
<section class="section">
  <h2><?= ct('home.news_heading') ?></h2>
  <div class="grid">
  <?php foreach ($news as $n): ?>
    <a class="card" href="<?= url('news_view.php?slug=' . urlencode($n['slug'])) ?>">
      <span class="badge"><?= e($n['category']) ?></span>
      <h3><?= e($n['title']) ?></h3>
      <p class="meta"><?= e(fmt_date($n['published_at'])) ?></p>
      <p><?= e($n['summary'] ?: excerpt($n['body'], 130)) ?></p>
    </a>
  <?php endforeach; if (!$news): ?><p>Verified news and stories will be published here soon.</p><?php endif; ?>
  </div>
  <p><a href="<?= url('news.php') ?>">All news &rarr;</a></p>
</section>
<?php endif; ?>

<?php if ($on('events')): ?>
<section class="section">
  <h2><?= ct('home.events_heading') ?></h2>
  <div class="grid">
  <?php foreach ($events as $ev): ?>
    <a class="card" href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>">
      <h3><?= e($ev['title']) ?></h3>
      <span class="badge"><?= e($ev['category']) ?></span> <?= event_status_badge($ev) ?>
      <p class="meta"><?= e(event_when($ev, 'j M Y, g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?></p>
    </a>
  <?php endforeach; if (!$events): ?><p>No confirmed events at the moment. Check back soon.</p><?php endif; ?>
  </div>
  <p><a href="<?= url('events.php') ?>">Events calendar &rarr;</a></p>
</section>
<?php endif; ?>

<?php if ($on('awards') || $on('news')): ?>
<section class="grid two">
  <?php if ($on('awards')): ?>
  <div class="card">
    <h3>Awards and recognition</h3>
    <p>GUCO supports recognition of outstanding achievement and distinguished service by alumni.</p>
    <ul class="checks"><?php foreach ($awards as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul>
    <a href="<?= url('awards.php') ?>">About our awards &rarr;</a>
  </div>
  <?php endif; ?>
  <?php if ($on('news')): ?>
  <div class="card">
    <h3>Latest newsletter</h3>
    <?php if ($newsletter): ?>
      <p><strong><?= e($newsletter['title']) ?></strong> <span class="meta"><?= e(fmt_date($newsletter['published_at'])) ?></span></p>
      <p><?= e($newsletter['summary'] ?: excerpt($newsletter['body'], 140)) ?></p>
      <a href="<?= url('news_view.php?slug=' . urlencode($newsletter['slug'])) ?>">Read more &rarr;</a>
    <?php else: ?>
      <p>The first edition will appear here once published. Subscribe using the form in the footer to receive updates.</p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($on('gallery')): ?>
<section class="section">
  <h2>Moments from our community</h2>
  <div class="photo-strip">
    <?= dinner_figure(3) ?><?= dinner_figure(7) ?><?= dinner_figure(4) ?>
  </div>
  <p class="meta"><?= e(DINNER_ALBUM) ?> &middot; <a href="<?= url('gallery.php') ?>">See the full gallery &rarr;</a></p>
</section>
<?php endif; ?>

<?php if ($on('cta')): ?>
<section class="cta">
  <h2 style="color:#fff;margin-top:0"><?= ct('home.cta_title') ?></h2>
  <?= cr('home.cta_text') ?>
  <a class="btn" href="<?= url('register.php') ?>"><?= ct('home.hero_btn1') ?></a>
  <a class="btn secondary" href="<?= url('contact.php') ?>">Contact the Secretariat</a>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
