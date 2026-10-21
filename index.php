<?php
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$news = q("SELECT title, slug, category, summary, body, published_at FROM news WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll();
$events = q("SELECT id, title, location, starts_at, status FROM events WHERE is_public = 1 AND starts_at >= NOW() ORDER BY starts_at LIMIT 3")->fetchAll();
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
      <p class="meta"><?= e(fmt_date($ev['starts_at'], 'j M Y, g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?></p>
      <?php if ($ev['status'] !== 'scheduled'): ?><span class="badge bad"><?= e(ucfirst($ev['status'])) ?></span><?php endif; ?>
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

<section class="cta">
  <h2 style="color:#fff;margin-top:0">Your connection with Gulu University continues after graduation</h2>
  <p>Join the Convocation, reconnect with fellow alumni and contribute to a stronger University and more empowered communities.</p>
  <a class="btn" href="<?= url('register.php') ?>">JOIN GUCO</a>
  <a class="btn secondary" href="<?= url('contact.php') ?>">Contact the Secretariat</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
