<?php
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$news = q("SELECT title, slug, category, summary, body, published_at FROM news WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll();
$events = q("SELECT * FROM events WHERE is_public = 1 AND (starts_at >= NOW() OR starts_at IS NULL) ORDER BY starts_at IS NULL, starts_at LIMIT 3")->fetchAll();
// Spotlight: the soonest confirmed event flagged as featured, else the soonest with a countdown.
$spot = q("SELECT * FROM events WHERE is_public = 1 AND status = 'scheduled' AND starts_at >= NOW() AND (featured = 1 OR show_countdown = 1) ORDER BY featured DESC, starts_at LIMIT 1")->fetch();
$newsletter = q("SELECT title, slug, summary, body, published_at FROM news WHERE status='published' AND category='Newsletter' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 1")->fetch();
$awards = q("SELECT name FROM awards WHERE is_public = 1 ORDER BY id LIMIT 4")->fetchAll(PDO::FETCH_COLUMN);
?>
<section class="hero">
  <img src="<?= url('assets/img/logo.webp') ?>" alt="Gulu University Convocation emblem">
  <div>
    <p class="tagline">Community Transformation Actualized</p>
    <h1>Together for Alumni Excellence and Community Transformation</h1>
    <p>Gulu University Convocation (GUCO) brings together Gulu University alumni, eligible university community members and partners to strengthen alumni networks, support the University's development and contribute knowledge, talent and resources to the wider community.</p>
    <div class="actions">
      <a class="btn" href="<?= url('register.php') ?>">JOIN GUCO</a>
      <a class="btn outline" href="<?= url('programmes.php') ?>">EXPLORE OUR WORK</a>
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

<section class="section">
  <h2>Welcome</h2>
  <p class="lead">Welcome to the official website of Gulu University Convocation. GUCO is the umbrella association established to mobilise, coordinate and inspire alumni and the wider University community to contribute to intellectual development, university advancement and community service. This platform connects alumni with one another and with opportunities to participate in the life and development of Gulu University.</p>
</section>

<section class="grid">
  <a class="card accent" href="<?= url('about.php') ?>"><h3>About GUCO</h3><p>Our vision, mission, values and objectives, and the Constitution that guides the Association.</p></a>
  <a class="card accent" href="<?= url('membership.php') ?>"><h3>Become a Member</h3><p>Founder, Ordinary, Honorary and Life membership. Check eligibility and apply online.</p></a>
  <a class="card accent" href="<?= url('chapters.php') ?>"><h3>Connect Through Chapters</h3><p>Find a recognised alumni chapter or help establish one.</p></a>
  <a class="card accent" href="<?= url('partnerships.php') ?>"><h3>Partner With GUCO</h3><p>Institutions, organisations and individuals are invited to collaborate with us.</p></a>
</section>

<section class="section">
  <h2>News and stories</h2>
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

<section class="section">
  <h2>Upcoming events</h2>
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

<section class="grid two">
  <div class="card">
    <h3>Awards and recognition</h3>
    <p>GUCO supports recognition of outstanding achievement and distinguished service by alumni.</p>
    <ul class="checks"><?php foreach ($awards as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul>
    <a href="<?= url('awards.php') ?>">About our awards &rarr;</a>
  </div>
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
</section>

<section class="section">
  <h2>Moments from our community</h2>
  <div class="photo-strip">
    <?= dinner_figure(3) ?><?= dinner_figure(7) ?><?= dinner_figure(4) ?>
  </div>
  <p class="meta"><?= e(DINNER_ALBUM) ?> &middot; <a href="<?= url('gallery.php') ?>">See the full gallery &rarr;</a></p>
</section>

<section class="cta">
  <h2 style="color:#fff;margin-top:0">Your connection with Gulu University continues after graduation</h2>
  <p>Join the Convocation, reconnect with fellow alumni and contribute to a stronger University and more empowered communities.</p>
  <a class="btn" href="<?= url('register.php') ?>">JOIN GUCO</a>
  <a class="btn secondary" href="<?= url('contact.php') ?>">Contact the Secretariat</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
