<?php
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$on = fn($k) => setting('home_show_' . $k, '1') === '1';
$count = fn($sql) => (int) db()->query($sql)->fetchColumn();

$news = $on('news') ? q("SELECT title, slug, category, source_type, external_url, source_name, summary, body, published_at FROM news WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll() : [];
$events = $on('events') ? q("SELECT * FROM events WHERE is_public = 1 AND status <> 'cancelled' AND (starts_at >= NOW() OR starts_at IS NULL) ORDER BY starts_at IS NULL, starts_at LIMIT 4")->fetchAll() : [];
// Spotlight: the soonest confirmed event flagged as featured, else the soonest with a countdown.
$spot = q("SELECT * FROM events WHERE is_public = 1 AND status = 'scheduled' AND starts_at >= NOW() AND (featured = 1 OR show_countdown = 1) ORDER BY featured DESC, starts_at LIMIT 1")->fetch();
$newsletter = $on('news') ? q("SELECT title, slug, summary, published_at FROM newsletters WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 1")->fetch() : null;
$spots = $on('campus') ? q('SELECT * FROM campus_spots WHERE is_public = 1 ORDER BY sort_order, id')->fetchAll() : [];
$anthemAudio = c_image('home.anthem_audio');
$anthemVideo = trim(c('home.anthem_video'));
$anthemLyrics = trim(c('home.anthem_lyrics'));
$campusLink = trim(c('home.campus_link'));
$campusVideo = trim(c('home.campus_video'));
$awards = $on('awards') ? q("SELECT name FROM awards WHERE is_public = 1 ORDER BY id LIMIT 4")->fetchAll(PDO::FETCH_COLUMN) : [];
$partners = q('SELECT * FROM partners WHERE is_public = 1 ORDER BY sort_order, id')->fetchAll();
$photoCount = $count('SELECT COUNT(*) FROM gallery');

// Live figures: shown only when there is something real to show.
$live = [
    ['users', $count("SELECT COUNT(*) FROM users WHERE role = 'member' AND status = 'approved'"), 'Members'],
    ['map-pin', $count('SELECT COUNT(*) FROM chapters WHERE is_public = 1'), 'Chapters'],
    ['calendar', $count('SELECT COUNT(*) FROM events WHERE is_public = 1'), 'Events listed'],
    ['book', $count('SELECT COUNT(*) FROM resources WHERE is_public = 1 AND member_only = 0'), 'Public documents'],
    ['award', $count('SELECT COUNT(*) FROM awards WHERE is_public = 1'), 'Award categories'],
    ['heart', $count("SELECT COUNT(*) FROM news WHERE status = 'published'"), 'News posts'],
];
$live = array_values(array_filter($live, fn($l) => $l[1] > 0));

$chaptersHome = q("SELECT c.id, c.name, c.focus, (SELECT COUNT(*) FROM chapter_members m WHERE m.chapter_id = c.id AND m.status = 'approved') AS n FROM chapters c WHERE c.is_public = 1 ORDER BY c.sort_order, c.name")->fetchAll();
$projectsHome = q("SELECT p.id, p.title, p.theme, p.location, p.summary, p.budget, u.full_name,
    (SELECT COALESCE(SUM(x.amount), 0) FROM project_updates x WHERE x.project_id = p.id AND x.kind = 'mobilisation' AND x.verified = 1) AS raised
    FROM projects p JOIN users u ON u.id = p.user_id WHERE p.is_public = 1 AND p.status IN ('approved','in_progress','completed') ORDER BY p.updated_at DESC LIMIT 3")->fetchAll();
$heroImg = c_image('home.hero_image') ?: dinner_src(3);
$progPhotos = [7, 11, 3, 4];
$progIcons = ['users', 'graduation', 'heart', 'award'];
?>
<section class="hero2">
  <div class="hero2-text">
    <p class="eyebrow"><?= ct('home.hero_tagline') ?></p>
    <h1><?= ct('home.hero_title') ?></h1>
    <div class="hero-copy"><?= cr('home.hero_text') ?></div>
    <div class="actions">
      <a class="btn" href="<?= url('register.php') ?>"><?= ct('home.hero_btn1') ?></a>
      <a class="btn outline" href="<?= url('programmes.php') ?>"><?= ct('home.hero_btn2') ?></a>
    </div>
    <div class="hero-stats">
      <?php foreach (c_pairs('home.stats') as [$v, $l]): ?><div><b><?= e($v) ?></b><span><?= e($l) ?></span></div><?php endforeach; ?>
    </div>
  </div>
  <div class="hero2-img">
    <img src="<?= e($heroImg) ?>" alt="Guests at a GUCO event">
    <?php if (count($live) >= 3): ?>
      <div class="hero2-card">
        <?php foreach (array_slice($live, 0, 3) as [$ic, $n, $label]): ?>
          <div><span class="dot"><?= icon($ic, 18) ?></span><span><b><?= $n ?></b><?= e($label) ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($spot): $fl = event_flyer_url($spot); ?>
<section class="event-hero on-dark home-section" style="<?= $fl ? '' : 'grid-template-columns:1fr' ?>" aria-label="Next event">
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

<?php if ($partners): ?>
<section class="partners" aria-label="Partners">
  <h2>Our partners</h2>
  <div class="partner-row">
    <?php foreach ($partners as $p):
        $inner = $p['logo'] ? '<img src="' . e(url('uploads/' . rawurlencode($p['logo']))) . '" alt="' . e($p['name']) . '" loading="lazy">' : '<span>' . e($p['name']) . '</span>';
        echo $p['url'] ? '<a href="' . e($p['url']) . '" target="_blank" rel="noopener">' . $inner . '</a>' : '<div>' . $inner . '</div>';
    endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('welcome')): ?>
<section class="home-section who">
  <div class="collage">
    <figure class="big"><img src="<?= e(dinner_src(19)) ?>" alt="Guests on stage at a GUCO event" loading="lazy"></figure>
    <figure class="sm"><img src="<?= e(dinner_src(9)) ?>" alt="Alumni at a GUCO event" loading="lazy"></figure>
    <figure class="sm"><img src="<?= e(dinner_src(7)) ?>" alt="Guests at the GUCO dinner" loading="lazy"></figure>
    <div class="badge-round"><b>2023</b><span>Constitution in force</span></div>
  </div>
  <div>
    <span class="eyebrow" style="color:var(--green);text-transform:uppercase;letter-spacing:.12em;font-size:.74rem;font-weight:700">About us</span>
    <h2 style="margin:4px 0 10px"><?= ct('home.who_title') ?></h2>
    <div class="lead" style="font-size:1rem;color:var(--muted)"><?= cr('home.who_text') ?></div>
    <div class="feat-row"><span class="ic"><?= icon('target') ?></span><div><h3>Our Mission</h3><p><?= e(excerpt(c('about.mission'), 130)) ?></p></div></div>
    <div class="feat-row"><span class="ic"><?= icon('eye') ?></span><div><h3>Our Vision</h3><p><?= e(excerpt(c('about.vision'), 130)) ?></p></div></div>
    <div class="feat-row"><span class="ic"><?= icon('gem') ?></span><div><h3>Our Values</h3>
      <div class="chips"><?php foreach (c_lines('about.values') as $val): $vt = trim(explode('|', $val)[0]); if ($vt !== ''): ?><span><?= e($vt) ?></span><?php endif; endforeach; ?></div></div></div>
    <a class="btn" href="<?= url('about.php') ?>">Learn more about us</a>
  </div>
</section>
<?php endif; ?>

<?php if ($live): ?>
<section class="band" aria-label="GUCO at a glance">
  <h2>GUCO at a glance</h2>
  <div class="band-grid">
    <?php foreach ($live as [$ic, $n, $label]): ?><div><?= icon($ic, 30) ?><b><?= $n ?></b><span><?= e($label) ?></span></div><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('highlights')): $hl = c_pairs('home.highlights', 3); $tones = ['g', 'r', 'b', 'a', 'p', 't']; ?>
<section class="home-section sec-center highlights">
  <div class="sec-head"><div><span class="eyebrow">Everything in one place</span><h2><?= ct('home.highlights_title') ?></h2></div></div>
  <div class="hl-grid">
    <?php foreach ($hl as $i => [$t, $d, $ic]): ?>
      <article class="hl hl-<?= $tones[$i % 6] ?>">
        <span class="hl-ic"><?= icon($ic ?: 'star', 28) ?></span>
        <h3><?= e($t) ?></h3>
        <p><?= e($d) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
  <p style="margin-top:22px"><a class="btn" href="<?= url('register.php') ?>">Join GUCO today</a> <a class="btn outline" href="<?= url('membership.php') ?>">See membership benefits</a></p>
</section>
<?php endif; ?>

<?php if ($on('welcome')): $areas = array_slice(c_pairs('programmes.areas'), 0, 4); ?>
<section class="home-section sec-center">
  <div class="sec-head"><div><span class="eyebrow">What we do</span><h2><?= ct('home.programmes_title') ?></h2></div></div>
  <div class="prog-grid" style="text-align:left">
    <?php foreach ($areas as $i => [$t, $d]): ?>
      <a class="prog-card" href="<?= url('programmes.php') ?>">
        <img src="<?= e(dinner_src($progPhotos[$i % 4])) ?>" alt="" loading="lazy">
        <div class="prog-body"><span class="ic"><?= icon($progIcons[$i % 4], 20) ?></span><h3><?= e($t) ?></h3><p><?= e(excerpt($d, 90)) ?></p><span class="more">Read more &rarr;</span></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('card')): ?>
<section class="home-section cardshow">
  <div class="cs-text">
    <span class="eyebrow">Membership</span>
    <h2><?= ct('home.card_title') ?></h2>
    <?= cr('home.card_text') ?>
    <p><a class="btn" href="<?= url('register.php') ?>">Apply for your card</a> <a class="btn outline" href="<?= url('verify.php') ?>">Verify a card</a></p>
  </div>
  <div class="cs-card" aria-hidden="true">
    <div class="mc">
      <div class="mc-h"><img src="<?= e(logo_url()) ?>" alt="" width="40" height="40"><div><b><?= e(setting('site_name', 'Gulu University Convocation')) ?></b><small><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></small></div></div>
      <div class="mc-b"><span class="mc-photo"><?= icon('user', 40) ?></span>
        <div class="mc-i"><div class="mc-n">Your Name Here</div><div>Ordinary / Full Member</div><small>MEMBERSHIP ID</small><b>GUCO/ORD/<?= date('Y') ?>/000123</b><small>VALID UNTIL</small><b>Lifetime or subscription date</b></div>
        <span class="mc-qr"><?= icon('grid', 56) ?></span></div>
    </div>
    <div class="mc-badge"><?= icon('shield', 18) ?> Verified online</div>
  </div>
</section>
<?php endif; ?>

<?php if ($on('campus') && ($spots || $campusVideo || $campusLink)): ?>
<section class="home-section campus">
  <div class="sec-head"><div><span class="eyebrow">Where it all began</span><h2><?= ct('home.campus_title') ?></h2></div>
    <?php if ($campusLink): ?><a href="<?= e($campusLink) ?>" target="_blank" rel="noopener"><?= ct('home.campus_link_label') ?> &#8599;</a><?php endif; ?></div>
  <div class="campus-intro"><?= cr('home.campus_text') ?></div>
  <?php if ($campusVideo): ?><div style="max-width:860px;margin:0 auto 22px"><?= video_embed($campusVideo, 'Watch the campus tour') ?></div><?php endif; ?>
  <?php if ($spots): ?>
  <div class="spot-grid">
    <?php foreach ($spots as $sp): $tag = $sp['link'] ? 'a' : 'div'; ?>
      <<?= $tag ?> class="spot"<?= $sp['link'] ? ' href="' . e($sp['link']) . '" target="_blank" rel="noopener"' : '' ?>>
        <?= $sp['image'] ? '<img src="' . e(url('uploads/' . rawurlencode($sp['image']))) . '" alt="" loading="lazy">' : '<div class="ph">' . icon('camera', 36) . '</div>' ?>
        <div class="spot-body"><h3><?= e($sp['title']) ?></h3><?= $sp['description'] ? '<p>' . e($sp['description']) . '</p>' : '' ?></div>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($on('events') && $events): ?>
<section class="home-section">
  <div class="sec-head"><div><span class="eyebrow">Mark your calendar</span><h2><?= ct('home.events_heading') ?></h2></div><a href="<?= url('events.php') ?>">View all events &rarr;</a></div>
  <div class="prog-grid">
    <?php foreach ($events as $ev): $fl = event_flyer_url($ev); ?>
      <a class="ev-card" href="<?= url('event_view.php?id=' . (int) $ev['id']) ?>">
        <div class="ev-media">
          <?= $fl ? '<img src="' . e($fl) . '" alt="" loading="lazy">' : '<div class="ph">' . icon('calendar', 44) . '</div>' ?>
          <?php if ($ev['starts_at']): ?><div class="ev-date"><b><?= e(fmt_date($ev['starts_at'], 'j')) ?></b><span><?= e(fmt_date($ev['starts_at'], 'M')) ?></span></div>
          <?php else: ?><div class="ev-date"><b>TBA</b><span>soon</span></div><?php endif; ?>
        </div>
        <div class="ev-body">
          <span><span class="badge"><?= e($ev['category']) ?></span> <?= event_status_badge($ev) ?></span>
          <h3><?= e($ev['title']) ?></h3>
          <p class="meta"><?= e(event_when($ev, 'D j M, g:ia')) ?><?= $ev['location'] ? ' &middot; ' . e($ev['location']) : '' ?></p>
          <?= event_countdown($ev, 'small') ?>
          <span class="btn"><?= $ev['registration_open'] && $ev['status'] === 'scheduled' ? 'Register now' : 'View details' ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('chapters') && $chaptersHome): ?>
<section class="home-section chapter-strip">
  <div class="sec-head"><div><span class="eyebrow">Across Uganda and beyond</span><h2><?= ct('home.chapters_title') ?></h2></div><a href="<?= url('chapters.php') ?>">All chapters &rarr;</a></div>
  <div class="chip-grid">
    <?php foreach ($chaptersHome as $c): ?>
      <a class="chap" href="<?= url('chapters.php') ?>"><span class="chap-ic"><?= icon($c['focus'] === 'Diaspora chapter' ? 'globe' : 'map-pin', 22) ?></span><span><b><?= e(str_replace(' Chapter', '', $c['name'])) ?></b><small><?= (int) $c['n'] ?> member<?= (int) $c['n'] === 1 ? '' : 's' ?></small></span></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('projects')): ?>
<section class="home-section">
  <div class="sec-head"><div><span class="eyebrow">Ideas that become action</span><h2><?= ct('home.projects_title') ?></h2></div><a href="<?= url('projects.php') ?>">All projects &rarr;</a></div>
  <div class="prog-grid">
    <?php foreach ($projectsHome as $p): $pct = $p['budget'] > 0 ? min(100, round($p['raised'] / $p['budget'] * 100)) : 0; ?>
      <a class="card project-card" href="<?= url('project_view.php?id=' . (int) $p['id']) ?>">
        <span class="badge"><?= e($p['theme']) ?></span>
        <h3 style="margin:.4em 0"><?= e($p['title']) ?></h3>
        <p class="meta" style="margin:0"><?= e($p['location']) ?> &middot; led by <?= e(short_name($p['full_name'])) ?></p>
        <p><?= e(excerpt((string) $p['summary'], 110)) ?></p>
        <div class="meter" style="height:8px;background:#e3eae5;border-radius:99px;overflow:hidden"><i style="display:block;height:100%;width:<?= $pct ?>%;background:var(--green)"></i></div>
        <p class="meta" style="margin:.4em 0 0">UGX <?= e(number_format((float) $p['raised'])) ?> of UGX <?= e(number_format((float) $p['budget'])) ?></p>
      </a>
    <?php endforeach; ?>
    <div class="card propose-card">
      <span class="hl-ic" style="background:#fff3cd;color:#8a6700"><?= icon('target', 28) ?></span>
      <h3>Have an idea for your community?</h3>
      <p>Approved members can propose a project. If the Executive Committee approves it, you lead it and mobilise the resources.</p>
      <a class="btn" href="<?= url('member/project_form.php') ?>">Propose a project</a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($on('cards')): ?>
<section class="home-section sec-center">
  <div class="sec-head"><div><span class="eyebrow">Be part of it</span><h2><?= ct('home.help_title') ?></h2></div></div>
  <div class="help-grid" style="text-align:left">
    <?php $helpIcons = ['user-plus', 'refresh', 'map-pin', 'link']; foreach (c_pairs('home.cards', 3) as $i => [$t, $d, $href]): ?>
      <a class="help-card" href="<?= e(safe_link($href) ?: url()) ?>"><span class="ic"><?= icon($helpIcons[$i % 4], 26) ?></span><div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('news') || $on('awards')): ?>
<section class="home-section two-col">
  <?php if ($on('news')): ?>
  <div>
    <div class="sec-head"><div><span class="eyebrow">Stay informed</span><h2><?= ct('home.news_heading') ?></h2></div><a href="<?= url('news.php') ?>">All news &rarr;</a></div>
    <?php foreach ($news as $n): $ext = $n['source_type'] === 'link'; ?>
      <a class="news-item" href="<?= e($ext ? $n['external_url'] : url('news_view.php?slug=' . urlencode($n['slug']))) ?>"<?= $ext ? ' target="_blank" rel="noopener"' : '' ?>>
        <div class="ph"><?= icon('file', 26) ?></div>
        <div><span class="badge"><?= e($n['category']) ?></span><?= $ext ? ' <span class="badge warn">From ' . e($n['source_name'] ?: parse_url($n['external_url'], PHP_URL_HOST)) . '</span>' : '' ?><h3><?= e($n['title']) ?></h3><p class="meta" style="margin:0"><?= e(fmt_date($n['published_at'])) ?> &middot; <?= e($n['summary'] ?: excerpt($n['body'], 80)) ?></p></div>
      </a>
    <?php endforeach; if (!$news): ?><p>Verified news and stories will be published here soon.</p><?php endif; ?>
    <?php if ($newsletter): ?><p class="meta">Latest newsletter: <a href="<?= url('newsletter_view.php?slug=' . urlencode($newsletter['slug'])) ?>"><?= e($newsletter['title']) ?></a></p><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if ($on('awards')): ?>
  <div>
    <div class="sec-head"><div><span class="eyebrow">Celebrating excellence</span><h2>Awards and recognition</h2></div><a href="<?= url('awards.php') ?>">About our awards &rarr;</a></div>
    <?php foreach ($awards as $a): ?>
      <div class="ev-row"><span class="ic" style="color:var(--gold-dark,#8a6700)"><?= icon('award', 26) ?></span><div class="t"><a href="<?= url('awards.php') ?>"><?= e($a) ?></a></div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($on('anthem')): $hasAnthem = $anthemAudio || $anthemVideo || $anthemLyrics; ?>
<section class="home-section anthem">
  <div class="anthem-card">
    <div class="anthem-text">
      <span class="anthem-ic"><?= icon('music', 30) ?></span>
      <h2><?= ct('home.anthem_title') ?></h2>
      <?= cr('home.anthem_text') ?>
      <?php if ($anthemAudio): ?><audio controls preload="none" src="<?= e($anthemAudio) ?>" class="anthem-audio">Your browser does not support audio playback.</audio>
      <?php elseif (!$hasAnthem): ?><div class="anthem-soon"><span class="play"><?= icon('play', 30) ?></span><div><b>The anthem is coming soon</b><small>Come back to listen and sing along.</small></div></div><?php endif; ?>
      <?php if ($anthemLyrics): ?><details class="anthem-lyrics"><summary>Read the lyrics</summary><div><?= cr('home.anthem_lyrics') ?></div></details><?php endif; ?>
    </div>
    <?php if ($anthemVideo): ?><div class="anthem-video"><?= video_embed($anthemVideo, 'Watch the anthem video') ?></div>
    <?php else: ?><div class="anthem-video anthem-art" aria-hidden="true"><div class="eq"><?php for ($i = 0; $i < 18; $i++): ?><i style="animation-delay:<?= $i * 0.09 ?>s"></i><?php endfor; ?></div><span><?= icon('music', 44) ?></span></div><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('gallery')): $keys = array_slice(array_keys(DINNER_PHOTOS), 0, 6); ?>
<section class="home-section">
  <div class="sec-head"><div><span class="eyebrow"><?= e(DINNER_ALBUM) ?></span><h2>Moments from our community</h2></div><a href="<?= url('gallery.php') ?>">View gallery &rarr;</a></div>
  <div class="moments">
    <?php foreach ($keys as $i => $n): ?>
      <a href="<?= url('gallery.php') ?>"><img src="<?= e(dinner_src($n)) ?>" alt="<?= e(DINNER_PHOTOS[$n]) ?>" loading="lazy"><?php if ($i === 5 && $photoCount > 6): ?><span class="more">+<?= $photoCount - 6 ?><small>more photos</small></span><?php endif; ?></a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('cta')): ?>
<section class="cta">
  <h2 style="margin-top:0"><?= ct('home.cta_title') ?></h2>
  <?= cr('home.cta_text') ?>
  <a class="btn" href="<?= url('register.php') ?>"><?= ct('home.hero_btn1') ?></a>
  <a class="btn secondary" href="<?= url('contact.php') ?>">Contact the Secretariat</a>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
