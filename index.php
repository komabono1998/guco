<?php
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';
$on = fn($k) => setting('home_show_' . $k, '1') === '1';
$count = fn($sql) => (int) db()->query($sql)->fetchColumn();

$news = $on('news') ? q("SELECT title, slug, category, source_type, external_url, source_name, summary, body, published_at FROM news WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3")->fetchAll() : [];
$events = $on('events') ? q("SELECT * FROM events WHERE is_public = 1 AND status <> 'cancelled' AND (starts_at >= NOW() OR starts_at IS NULL) ORDER BY featured DESC, starts_at IS NULL, starts_at, id LIMIT 4")->fetchAll() : [];
// Spotlight: the soonest confirmed event flagged as featured, else the soonest with a countdown.
$spot = q("SELECT * FROM events WHERE is_public = 1 AND status = 'scheduled' AND starts_at >= NOW() AND (featured = 1 OR show_countdown = 1) ORDER BY featured DESC, starts_at LIMIT 1")->fetch();
$newsletter = $on('news') ? q("SELECT title, slug, summary, published_at FROM newsletters WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 1")->fetch() : null;
$spots = $on('campus') ? q('SELECT * FROM campus_spots WHERE is_public = 1 ORDER BY sort_order, id')->fetchAll() : [];
// The home page plays the anthem marked "show on the home page" in Admin > Anthems.
$anthemRow = q('SELECT * FROM anthems WHERE show_home = 1 AND is_public = 1 ORDER BY sort_order, id LIMIT 1')->fetch() ?: [];
$anthemAudio = !empty($anthemRow['audio']) ? url('uploads/' . rawurlencode($anthemRow['audio'])) : null;
$anthemVideo = trim((string) ($anthemRow['video_url'] ?? ''));
$anthemLyrics = trim((string) ($anthemRow['lyrics'] ?? ''));
$campusLink = trim(c('home.campus_link'));
$campusVideo = trim(c('home.campus_video'));
$awards = $on('awards') ? q("SELECT name FROM awards WHERE is_public = 1 ORDER BY id LIMIT 4")->fetchAll(PDO::FETCH_COLUMN) : [];
$fame = $on('fame') ? q('SELECT * FROM hall_of_fame WHERE is_public = 1 AND featured = 1 ORDER BY sort_order, inducted_year DESC LIMIT 4')->fetchAll() : [];
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
$heroCustom = c_image('home.hero_image');
$heroImg = $heroCustom ?: asset('assets/img/hero-graduation.webp');
$heroLink = function (string $key): string {
    $u = trim(c($key));
    return preg_match('#^https?://#i', $u) ? $u : url(ltrim($u, '/'));
};
$progPhotos = [7, 11, 3, 4];
$progIcons = ['users', 'graduation', 'heart', 'award'];
?>
<section class="hero2<?= $heroCustom ? '' : ' hero-frame' ?>">
  <div class="hero2-text">
    <p class="eyebrow"><?= ct('home.hero_tagline') ?></p>
    <h1><?= ct('home.hero_title') ?></h1>
    <div class="hero-copy"><?= cr('home.hero_text') ?></div>
    <div class="actions">
      <a class="btn" href="<?= e($heroLink('home.hero_btn1_url')) ?>"><?= ct('home.hero_btn1') ?></a>
      <a class="btn outline" href="<?= e($heroLink('home.hero_btn2_url')) ?>"><?= ct('home.hero_btn2') ?></a>
    </div>
    <div class="hero-stats">
      <?php foreach (c_pairs('home.stats') as [$v, $l]): ?><div><b><?= e($v) ?></b><span><?= e($l) ?></span></div><?php endforeach; ?>
    </div>
  </div>
  <div class="hero2-img">
    <a class="hero2-plus" href="<?= url('programmes.php') ?>" aria-label="Explore our programmes">+</a>
    <img src="<?= e($heroImg) ?>" alt="<?= $heroCustom ? 'GUCO event' : 'A graduate receiving a GUCO Award of Excellence on stage at the 22nd graduation of Gulu University' ?>" width="1600" height="1066" fetchpriority="high" decoding="async">
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
  <div class="orb" id="orb">
    <div class="orb-in">
      <span class="orb-ring" aria-hidden="true"></span><span class="orb-ring r2" aria-hidden="true"></span>
      <button type="button" class="orb-ph p0" aria-label="Show this photo larger"><img src="<?= e(dinner_src(19)) ?>" alt="Guests on stage at a GUCO event" loading="lazy"></button>
      <button type="button" class="orb-ph p1" aria-label="Show this photo larger"><img src="<?= e(dinner_src(9)) ?>" alt="Alumni at a GUCO event" loading="lazy"></button>
      <button type="button" class="orb-ph p2" aria-label="Show this photo larger"><img src="<?= e(dinner_src(7)) ?>" alt="Guests at the GUCO dinner" loading="lazy"></button>
      <div class="orb-badge"><b>2023</b><span>Constitution in force</span></div>
    </div>
  </div>
  <div>
    <span class="eyebrow" style="color:var(--green);text-transform:uppercase;letter-spacing:.12em;font-size:.74rem;font-weight:700">About us</span>
    <h2 style="margin:4px 0 10px"><?= ct('home.who_title') ?></h2>
    <div class="lead" style="font-size:1rem;color:var(--muted)"><?= cr('home.who_text') ?></div>
    <?php
    $mvv = [
        ['mission', 'target', 'Our Mission', '#b45309'],
        ['vision', 'eye', 'Our Vision', '#be123c'],
        ['values', 'gem', 'Our Values', '#14532d'],
    ];
    $valueRows = array_map(fn($l) => array_pad(array_map('trim', explode('|', $l, 2)), 2, ''), c_lines('about.values'));
    ?>
    <div class="mvv" id="mvv">
      <div class="mvv-tabs" role="tablist" aria-label="Mission, vision and values">
        <?php foreach ($mvv as $k => [$id, $ic, $label, $tone]): ?>
          <button type="button" role="tab" class="mvv-tab<?= $k === 0 ? ' on' : '' ?>" id="mvv-t-<?= $id ?>" aria-controls="mvv-p-<?= $id ?>" aria-selected="<?= $k === 0 ? 'true' : 'false' ?>" data-i="<?= $k ?>" style="--c:<?= $tone ?>">
            <span class="mvv-ic"><?= icon($ic, 24) ?></span><span><?= e($label) ?></span>
          </button>
        <?php endforeach; ?>
      </div>
      <div class="mvv-stage">
        <div class="mvv-panel on" role="tabpanel" id="mvv-p-mission" aria-labelledby="mvv-t-mission" style="--c:#b45309">
          <span class="mvv-quote">&ldquo;</span><p><?= e(c('about.mission')) ?></p>
        </div>
        <div class="mvv-panel" role="tabpanel" id="mvv-p-vision" aria-labelledby="mvv-t-vision" style="--c:#be123c">
          <span class="mvv-quote">&ldquo;</span><p><?= e(c('about.vision')) ?></p>
        </div>
        <div class="mvv-panel" role="tabpanel" id="mvv-p-values" aria-labelledby="mvv-t-values" style="--c:#14532d">
          <div class="mvv-values">
            <?php foreach ($valueRows as $vi => [$vt, $vd]): if ($vt === '') continue; ?>
              <button type="button" class="mvv-val" aria-expanded="false" style="--d:<?= $vi * 60 ?>ms">
                <b><?= e($vt) ?></b>
                <?php if ($vd !== ''): ?><span><?= e($vd) ?></span><?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="mvv-dots" aria-hidden="true"><i class="on"></i><i></i><i></i></div>
    </div>
    <a class="btn" href="<?= url('about.php') ?>">Learn more about us</a>
<script>
(function () {
  var box = document.getElementById('mvv'); if (!box) return;
  var tabs = [].slice.call(box.querySelectorAll('.mvv-tab')), panels = [].slice.call(box.querySelectorAll('.mvv-panel')), dots = [].slice.call(box.querySelectorAll('.mvv-dots i'));
  var cur = 0, timer = null, touched = false;
  function show(i) {
    cur = i;
    tabs.forEach(function (t, k) { t.classList.toggle('on', k === i); t.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
    panels.forEach(function (p, k) { p.classList.toggle('on', k === i); });
    dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
  }
  function stop() { touched = true; clearInterval(timer); }
  tabs.forEach(function (t, k) {
    t.addEventListener('click', function () { stop(); show(k); });
    t.addEventListener('mouseenter', function () { if (window.matchMedia('(hover: hover)').matches) { stop(); show(k); } });
    t.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { e.preventDefault(); var n = (k + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length; stop(); show(n); tabs[n].focus(); }
    });
  });
  [].slice.call(box.querySelectorAll('.mvv-val')).forEach(function (v) {
    v.addEventListener('click', function () { var o = v.classList.toggle('open'); v.setAttribute('aria-expanded', o ? 'true' : 'false'); stop(); });
  });
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    timer = setInterval(function () { show((cur + 1) % tabs.length); }, 6500);
  }
})();
</script>
  </div>
  <script>
  (function () {
    var box = document.getElementById('orb'); if (!box) return;
    var els = [].slice.call(box.querySelectorAll('.orb-ph')), pos = [0, 1, 2], paused = false, timer;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function paint() { els.forEach(function (el, i) { el.classList.remove('p0', 'p1', 'p2'); el.classList.add('p' + pos[i]); }); }
    // Bring photo i to the middle; the others move round one place.
    function focusOn(i) {
      if (pos[i] === 0) { i = pos.indexOf(1); }
      var from = pos[i];
      pos = pos.map(function (p, k) { if (k === i) return 0; if (p === 0) return from; return p; });
      paint();
    }
    els.forEach(function (el, i) { el.addEventListener('click', function () { focusOn(i); }); });
    if (!reduce) {
      timer = setInterval(function () { if (!paused && !document.hidden) focusOn(pos.indexOf(1)); }, 5200);
      box.addEventListener('mouseenter', function () { paused = true; });
      box.addEventListener('mouseleave', function () { paused = false; box.style.setProperty('--mx', 0); box.style.setProperty('--my', 0); });
      box.addEventListener('focusin', function () { paused = true; });
      box.addEventListener('focusout', function () { paused = false; });
      box.addEventListener('mousemove', function (e) {
        var r = box.getBoundingClientRect();
        box.style.setProperty('--mx', ((e.clientX - r.left) / r.width - .5).toFixed(3));
        box.style.setProperty('--my', ((e.clientY - r.top) / r.height - .5).toFixed(3));
      });
    }
  })();
  </script>
</section>
<?php endif; ?>

<?php if ($on('chair')):
    $chair = q("SELECT id, name, title FROM leaders WHERE organ = 'executive' AND title = 'Chairperson' AND is_public = 1")->fetch() ?: [];
    $chairName = trim((string) ($chair['name'] ?? ''));
    $chairImg = c_image('home.chair_image') ?: asset('assets/img/chair-message.webp');
?>
<section class="home-section cm" id="chair" aria-labelledby="cmTitle">
  <div class="cm-photo">
    <span class="cm-orb o1" aria-hidden="true"></span><span class="cm-orb o2" aria-hidden="true"></span>
    <div class="cm-arch"><img src="<?= e($chairImg) ?>" alt="<?= e($chairName !== '' ? $chairName . ', Chairperson of GUCO' : 'The Chairperson of GUCO') ?>" loading="lazy"></div>
    <div class="cm-badge">
      <?php if ($chairName !== ''): ?><b><?= e($chairName) ?></b><?php endif; ?>
      <span>Chairperson, Gulu University Convocation</span>
      <?php if (!empty($chair['id']) && $chairName !== ''): ?><a href="<?= e(url('leader.php?id=' . (int) $chair['id'])) ?>">View profile <?= icon('arrow-right', 14) ?></a><?php endif; ?>
    </div>
  </div>
  <div class="cm-body">
    <span class="eyebrow"><?= ct('home.chair_eyebrow') ?></span>
    <h2 id="cmTitle"><?= ct('home.chair_title') ?></h2>
    <div class="cm-text" id="cmText"><?= cr('home.chair_text') ?></div>
    <button type="button" class="cm-more" id="cmMore" aria-expanded="true" aria-controls="cmText" hidden>Read the full message</button>
    <figure class="cm-sign">
      <blockquote><?= ct('home.chair_quote') ?></blockquote>
      <?php if ($chairName !== ''): ?><figcaption><b><?= e($chairName) ?></b><span>Chairperson</span></figcaption><?php endif; ?>
    </figure>
  </div>
</section>
<script>
(function () {
  var t = document.getElementById('cmText'), b = document.getElementById('cmMore'); if (!t || !b) return;
  t.classList.add('clamp'); b.hidden = false; b.setAttribute('aria-expanded', 'false');
  b.addEventListener('click', function () {
    var open = t.classList.toggle('clamp') === false;
    b.setAttribute('aria-expanded', open ? 'true' : 'false'); b.textContent = open ? 'Show less' : 'Read the full message';
  });
})();
</script>
<?php endif; ?>

<?php if ($live):
$glLinks = ['Members' => 'membership.php', 'Chapters' => 'chapters.php', 'Events listed' => 'events.php', 'Public documents' => 'resources.php', 'Award categories' => 'awards.php', 'News posts' => 'news.php'];
$glOne = ['Members' => 'Member', 'Chapters' => 'Chapter', 'Events listed' => 'Event listed', 'Public documents' => 'Public document', 'Award categories' => 'Award category', 'News posts' => 'News post'];
$glHint = ['Members' => 'Approved alumni and staff', 'Chapters' => 'Across Uganda and beyond', 'Events listed' => 'Upcoming and past', 'Public documents' => 'Open to everyone', 'Award categories' => 'Recognising excellence', 'News posts' => 'Stories and updates'];
$glTone = ['gold', 'teal', 'blue', 'rose', 'violet', 'orange'];
?>
<section class="home-section gl2" id="glance" aria-labelledby="glTitle">
  <div class="gl2-card">
    <div class="gl2-head">
      <div>
        <span class="gl2-eyebrow"><?= ct('home.glance_eyebrow') ?></span>
        <h2 id="glTitle"><?= ct('home.glance_title') ?></h2>
      </div>
      <span class="gl2-live"><i></i> Counted live from the website</span>
    </div>
    <ul class="gl2-grid" role="list">
      <?php foreach ($live as $i => [$ic, $n, $label]): $n = (int) $n; ?>
      <li class="gl2-cell tone-<?= $glTone[$i % 6] ?>">
        <a class="gl2-item" href="<?= url($glLinks[$label] ?? 'index.php') ?>" aria-label="<?= $n ?> <?= e($n === 1 ? ($glOne[$label] ?? $label) : $label) ?>. Open the page.">
          <span class="gl2-ic"><?= icon($ic, 24) ?></span>
          <b class="gl2-num" data-n="<?= $n ?>"><?= $n ?></b>
          <span class="gl2-label"><?= e($n === 1 ? ($glOne[$label] ?? $label) : $label) ?></span>
          <small><?= e($glHint[$label] ?? '') ?></small>
          <span class="gl2-go" aria-hidden="true">&rarr;</span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <p class="gl2-cta"><span>Be part of the numbers.</span> <a href="<?= url('register.php') ?>">Join GUCO &rarr;</a></p>
  </div>
</section>
<script>
(function () {
  var nums = document.querySelectorAll('#glance .gl2-num'); if (!nums.length) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduce || !('IntersectionObserver' in window)) return;
  function run(el) {
    var n = +el.getAttribute('data-n'), t0 = null, dur = 1100 + Math.min(900, n * 8);
    function step(t) { if (!t0) t0 = t; var p = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - p, 3); el.textContent = Math.round(n * e); if (p < 1) requestAnimationFrame(step); }
    el.textContent = '0'; requestAnimationFrame(step);
  }
  var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } }); }, { threshold: .6 });
  nums.forEach(function (n) { io.observe(n); });
})();
</script>
<?php endif; ?>

<?php if ($on('highlights')): $hl = c_pairs('home.highlights', 3); $tones = ['g', 'r', 'b', 'a', 'p', 't']; ?>
<section class="home-section sec-center highlights">
  <div class="sec-head"><div><span class="eyebrow">Everything in one place</span><h2><?= ct('home.highlights_title') ?></h2></div></div>
  <div class="hl-grid">
    <?php
    $hlPages = ['card' => 'verify.php', 'verif' => 'verify.php', 'chapter' => 'chapters.php', 'event' => 'events.php', 'project' => 'projects.php', 'award' => 'awards.php', 'recogni' => 'awards.php', 'news' => 'news.php', 'newsletter' => 'newsletters.php', 'career' => 'careers.php', 'give' => 'donate.php', 'donat' => 'donate.php', 'member' => 'membership.php'];
    foreach ($hl as $i => [$t, $d, $ic]):
        $hlHref = 'membership.php';
        foreach ($hlPages as $hk => $hp) {
            if (stripos($t, $hk) !== false) {
                $hlHref = $hp;
                break;
            }
        } ?>
      <a class="hl hl-<?= $tones[$i % 6] ?>" href="<?= url($hlHref) ?>">
        <span class="hl-go" aria-hidden="true">&rarr;</span>
        <span class="hl-ic"><?= icon($ic ?: 'star', 26) ?></span>
        <h3><?= e($t) ?></h3>
        <p><?= e($d) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
  <p style="margin-top:22px"><a class="btn" href="<?= url('register.php') ?>"><?= ct('home.highlights_btn1') ?></a> <a class="btn outline" href="<?= url('membership.php') ?>"><?= ct('home.highlights_btn2') ?></a></p>
</section>
<?php endif; ?>

<?php if ($on('welcome')): $areas = array_slice(c_pairs('programmes.areas'), 0, 7);
$poPhotos = [7, 11, 3, 4, 9, 14, 19];
$poIcons = ['users', 'target', 'graduation', 'heart', 'book', 'award', 'globe'];
$poTones = ['#14532d', '#be123c', '#b45309', '#4d7c0f', '#1d4ed8', '#7e22ce', '#0f766e'];
$poN = max(1, count($areas));
?>
<section class="home-section po-sec" aria-label="<?= e(ct('home.programmes_title')) ?>">
  <div class="sec-head" style="justify-content:center;text-align:center"><div><span class="eyebrow"><?= ct('home.ring_eyebrow') ?></span><h2><?= ct('home.programmes_title') ?></h2></div></div>
  <div class="po" id="po" style="--n:<?= $poN ?>">
    <div class="po-stage">
      <div class="po-orbit" aria-hidden="true"></div>
      <div class="po-ring">
        <?php foreach ($areas as $i => [$t, $d]): $a = round($i * 360 / $poN - 90, 2); ?>
          <div class="po-slot" style="--a:<?= $a ?>deg;--c:<?= $poTones[$i % 7] ?>">
            <button type="button" class="po-node" data-i="<?= $i ?>" aria-label="<?= e($t) ?>" aria-pressed="false" style="background-image:url('<?= e(dinner_src($poPhotos[$i % 7])) ?>')">
              <span class="po-badge"><?= icon($poIcons[$i % 7], 18) ?></span>
              <span class="po-tip"><?= e($t) ?></span>
            </button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="po-hub" id="poToggle" aria-label="Pause or resume the rotation">
        <img src="<?= e(logo_url()) ?>" alt="" width="64" height="64">
        <span class="po-hub-t">Our programmes</span>
        <span class="po-hub-s" id="poState">Rotating &middot; tap to pause</span>
      </button>
    </div>
    <div class="po-detail" aria-live="polite">
      <div class="po-card on" data-i="-1">
        <span class="po-kicker">Explore</span>
        <h3><?= ct('home.ring_intro_title') ?></h3>
        <p><?= ct('home.ring_intro_text') ?></p>
        <a class="btn" href="<?= url('programmes.php') ?>">See all programmes &rarr;</a>
      </div>
      <?php foreach ($areas as $i => [$t, $d]): ?>
        <article class="po-card" data-i="<?= $i ?>" style="--c:<?= $poTones[$i % 7] ?>">
          <div class="po-photo" style="background-image:url('<?= e(dinner_src($poPhotos[$i % 7])) ?>')"><span class="po-badge lg"><?= icon($poIcons[$i % 7], 26) ?></span></div>
          <span class="po-kicker">Programme area <?= $i + 1 ?> of <?= $poN ?></span>
          <h3><?= e($t) ?></h3>
          <p><?= e($d) ?></p>
          <div class="po-actions">
            <a class="btn" href="<?= url('programmes.php') ?>">Read more &rarr;</a>
            <button type="button" class="btn secondary po-next">Next area</button>
            <button type="button" class="btn secondary po-close">Close</button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<script>
(function () {
  var po = document.getElementById('po'); if (!po) return;
  var nodes = [].slice.call(po.querySelectorAll('.po-node')), cards = [].slice.call(po.querySelectorAll('.po-card'));
  var toggle = document.getElementById('poToggle'), state = document.getElementById('poState');
  var cur = -1, userPaused = false;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function setPaused(p) { po.classList.toggle('paused', p); }
  function select(i) {
    cur = i;
    nodes.forEach(function (n, k) { n.classList.toggle('on', k === i); n.setAttribute('aria-pressed', k === i ? 'true' : 'false'); });
    cards.forEach(function (c) { c.classList.toggle('on', parseInt(c.getAttribute('data-i'), 10) === i); });
    po.classList.toggle('sel', i >= 0);
    setPaused(i >= 0 || userPaused);
    syncLabel();
  }
  function syncLabel() { state.innerHTML = reduce ? 'Tap a circle for details' : (po.classList.contains('paused') ? 'Paused &middot; tap to rotate' : 'Rotating &middot; tap to pause'); }
  nodes.forEach(function (n, k) {
    n.addEventListener('click', function () { select(cur === k ? -1 : k); });
  });
  cards.forEach(function (c) {
    var nx = c.querySelector('.po-next'), cl = c.querySelector('.po-close');
    if (nx) nx.addEventListener('click', function () { select((cur + 1) % nodes.length); });
    if (cl) cl.addEventListener('click', function () { select(-1); });
  });
  toggle.addEventListener('click', function () { userPaused = !userPaused; if (cur < 0) setPaused(userPaused); syncLabel(); });
  po.querySelector('.po-stage').addEventListener('mouseenter', function () { po.classList.add('hover'); });
  po.querySelector('.po-stage').addEventListener('mouseleave', function () { po.classList.remove('hover'); });
  if (reduce) { po.classList.add('paused'); }
  syncLabel();
})();
</script>
<?php endif; ?>

<?php if ($on('card')): ?>
<?php
// A decorative sample QR code (not a real code): three finder squares and a fixed pseudo-random pattern.
$mockQr = (function () {
    $n = 25; $cells = []; $seed = 20241119;
    $finder = fn($x, $y) => ($x < 7 && $y < 7) || ($x >= $n - 7 && $y < 7) || ($x < 7 && $y >= $n - 7);
    $path = '';
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            if ($finder($x, $y)) {
                $ox = $x >= $n - 7 ? $x - ($n - 7) : $x; $oy = $y >= $n - 7 ? $y - ($n - 7) : $y;
                $on = ($ox === 0 || $ox === 6 || $oy === 0 || $oy === 6) || ($ox >= 2 && $ox <= 4 && $oy >= 2 && $oy <= 4);
            } else {
                $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                $on = (($seed >> 16) & 3) !== 0;
                if (($x === 7 || $y === 7 || $x === $n - 8 || $y === $n - 8) && ($x < 8 || $y < 8 || $x >= $n - 8 || $y >= $n - 8)) { $on = false; }
            }
            if ($on) { $path .= 'M' . $x . ' ' . $y . 'h1v1h-1z'; }
        }
    }
    return '<svg viewBox="0 0 ' . $n . ' ' . $n . '" aria-hidden="true" focusable="false" shape-rendering="crispEdges"><path d="' . $path . '" fill="currentColor"/></svg>';
})();
$csYear = date('Y');
?>
<section class="home-section cardshow cs2" id="cardShow">
  <div class="cs-text">
    <span class="eyebrow">Membership</span>
    <h2><?= ct('home.card_title') ?></h2>
    <?= cr('home.card_text') ?>
    <ol class="cs-steps" aria-label="How to get your card">
      <li><b>1</b><span>Apply online</span></li>
      <li><b>2</b><span>Get approved</span></li>
      <li><b>3</b><span>Show &amp; verify</span></li>
    </ol>
    <p class="cs-cta"><a class="btn" href="<?= url('register.php') ?>">Apply for your card</a> <a class="btn outline" href="<?= url('verify.php') ?>">Verify a card</a></p>
  </div>

  <div class="cs-stage">
    <div class="cs-flip" id="csFlip" data-year="<?= e($csYear) ?>">
      <div class="cs-tilt" id="csTilt">
        <div class="cs-inner" id="csInner">
          <div class="cs-face cs-front" role="group" aria-label="Sample membership card, front">
            <div class="cs-head"><img src="<?= e(logo_url()) ?>" alt="" width="42" height="42"><div><b><?= e(setting('site_name', 'Gulu University Convocation')) ?></b><small><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></small></div></div>
            <div class="cs-body">
              <span class="cs-photo" id="csPhotoBox"><?= icon('user', 38) ?></span>
              <div class="cs-info">
                <div class="cs-name" id="csName">Your Name Here</div>
                <div class="cs-cat" id="csCat">Ordinary / Full Member</div>
                <div class="cs-row cs-wide"><span>Membership ID</span><b class="cs-mono" id="csId">GUCO/ORD/<?= e($csYear) ?>/000123</b></div>
                <div class="cs-row"><span>Member since</span><b><?= e(date('M Y')) ?></b></div>
                <div class="cs-row"><span>Valid until</span><b id="csUntil">Renew yearly</b></div>
              </div>
              <div class="cs-qr"><?= $mockQr ?><small>Scan to verify</small></div>
            </div>
            <span class="cs-sample" aria-hidden="true">SAMPLE</span>
          </div>
          <div class="cs-face cs-back" role="group" aria-label="Sample membership card, back">
            <div class="cs-back-in">
              <div class="cs-bigqr"><?= $mockQr ?></div>
              <div class="cs-back-t">
                <h3>Verified online</h3>
                <p>Anyone can scan the code or type the Membership ID on the verification page to confirm a card is genuine.</p>
                <ul>
                  <li>Show it at events and meetings</li>
                  <li>Renew from your Member Portal</li>
                  <li>Updated automatically when you renew</li>
                </ul>
              </div>
            </div>
            <small class="cs-back-foot"><?= e(config('short_name')) ?> &middot; Gulu University Convocation</small>
          </div>
        </div>
      </div>
      <span class="cs-badge"><?= icon('shield', 16) ?> Verified online</span>
    </div>

    <div class="cs-tools">
      <div class="cs-cats" role="group" aria-label="Choose a membership category">
        <button type="button" data-c="founder" data-l="Founder Member" data-u="Lifetime" data-code="FDR">Founder</button>
        <button type="button" class="on" data-c="ordinary" data-l="Ordinary / Full Member" data-u="Renew yearly" data-code="ORD" aria-pressed="true">Ordinary</button>
        <button type="button" data-c="honorary" data-l="Honorary Member" data-u="Lifetime" data-code="HON">Honorary</button>
        <button type="button" data-c="life" data-l="Life Member" data-u="Lifetime" data-code="LIF">Life</button>
      </div>
      <div class="cs-fields">
        <label class="cs-field"><span>Preview with your name</span><input type="text" id="csInput" maxlength="34" placeholder="Type your name" autocomplete="off"></label>
        <label class="cs-btn cs-file"><?= icon('camera', 16) ?> Add photo<input type="file" id="csFile" accept="image/jpeg,image/png,image/webp" hidden></label>
        <button type="button" class="cs-btn" id="csFlipBtn"><?= icon('arrow-right', 16) ?> Flip card</button>
      </div>
      <p class="cs-note">A sample only: what you type or choose stays in your browser. Your real card is issued when your membership is approved.</p>
    </div>
  </div>
</section>
<script>
(function () {
  var root = document.getElementById('cardShow'); if (!root) return;
  var flip = document.getElementById('csFlip'), inner = document.getElementById('csInner'), tilt = document.getElementById('csTilt');
  var year = flip.getAttribute('data-year'), flipped = false;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function setFlip(v) { flipped = v; inner.classList.toggle('is-flipped', v); document.getElementById('csFlipBtn').setAttribute('aria-pressed', v ? 'true' : 'false'); }
  document.getElementById('csFlipBtn').addEventListener('click', function () { setFlip(!flipped); });
  inner.addEventListener('click', function () { setFlip(!flipped); });
  inner.setAttribute('tabindex', '0'); inner.setAttribute('role', 'button'); inner.setAttribute('aria-label', 'Sample card. Press to flip it over.');
  inner.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setFlip(!flipped); } });
  // Gentle 3D tilt that follows the pointer (not on touch screens or with reduced motion).
  if (!reduce && window.matchMedia && matchMedia('(hover: hover)').matches) {
    flip.addEventListener('pointermove', function (e) {
      var r = flip.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      tilt.style.transform = 'rotateY(' + (x * 12).toFixed(2) + 'deg) rotateX(' + (-y * 10).toFixed(2) + 'deg)';
    });
    flip.addEventListener('pointerleave', function () { tilt.style.transform = ''; });
  }
  // Live preview.
  var nameEl = document.getElementById('csName'), input = document.getElementById('csInput');
  input.addEventListener('input', function () { nameEl.textContent = input.value.trim() || 'Your Name Here'; });
  var cat = document.getElementById('csCat'), idEl = document.getElementById('csId'), until = document.getElementById('csUntil');
  root.querySelectorAll('.cs-cats button').forEach(function (b) {
    b.addEventListener('click', function () {
      root.querySelectorAll('.cs-cats button').forEach(function (x) { x.classList.remove('on'); x.removeAttribute('aria-pressed'); });
      b.classList.add('on'); b.setAttribute('aria-pressed', 'true');
      cat.textContent = b.getAttribute('data-l'); until.textContent = b.getAttribute('data-u');
      idEl.textContent = 'GUCO/' + b.getAttribute('data-code') + '/' + year + '/000123';
      setFlip(false);
    });
  });
  var file = document.getElementById('csFile'), box = document.getElementById('csPhotoBox'), url = null;
  file.addEventListener('change', function () {
    var f = file.files && file.files[0]; if (!f || !/^image\//.test(f.type)) return;
    if (url) URL.revokeObjectURL(url);
    url = URL.createObjectURL(f);
    box.innerHTML = ''; var img = document.createElement('img'); img.src = url; img.alt = 'Your photo (preview only)'; box.appendChild(img);
  });
})();
</script>
<?php endif; ?>

<?php if ($on('campus') && ($spots || $campusVideo || $campusLink)): ?>
<section class="home-section campus cp2" id="campus">
  <div class="cp-hero">
    <svg class="cp-skyline" viewBox="0 0 1200 220" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
      <g fill="rgba(255,255,255,.07)">
        <path d="M0 220V150l60-22 60 22v70zM150 220V110h70v110zM250 220V135l55-30 55 30v85zM390 220V90h38V60h24v30h38v130zM540 220V120l70-45 70 45v100zM710 220V140h60v-24h40v24h60v80zM900 220V100l50-36 50 36v120zM1030 220V132h70v88zM1110 220V160l45-20 45 20v60z"/>
      </g>
      <g fill="rgba(242,184,7,.16)"><path d="M0 220c120-26 220-26 360 0s250 26 380 0 300-30 460 0v20H0z"/></g>
    </svg>
    <span class="cp-orb" aria-hidden="true"></span>
    <div class="cp-text">
      <span class="eyebrow">Where it all began</span>
      <h2><?= ct('home.campus_title') ?></h2>
      <div class="cp-intro"><?= cr('home.campus_text') ?></div>
      <div class="cp-actions">
        <?php if ($campusLink): ?><a class="cp-btn cp-primary" href="<?= e($campusLink) ?>" target="_blank" rel="noopener"><?= ct('home.campus_link_label') ?> <span aria-hidden="true">&#8599;</span></a><?php endif; ?>
      </div>
    </div>
    <ul class="cp-links" aria-label="Plan your visit">
      <li><a href="<?= url('contact.php#gmap') ?>"><span class="cp-ic"><?= icon('map-pin', 22) ?></span><span><b>Find your way</b><small>Map of Gulu with live directions</small></span><i aria-hidden="true">&rarr;</i></a></li>
      <li><a href="<?= url('membership.php') ?>"><span class="cp-ic"><?= icon('id-card', 22) ?></span><span><b>Join the alumni family</b><small>Membership categories and your card</small></span><i aria-hidden="true">&rarr;</i></a></li>
      <li><a href="<?= url('contact.php') ?>"><span class="cp-ic"><?= icon('mail', 22) ?></span><span><b>Visiting as alumni?</b><small>Ask the Secretariat to help</small></span><i aria-hidden="true">&rarr;</i></a></li>
    </ul>
  </div>

  <?php if ($campusVideo): ?><div class="cp-video"><?= video_embed($campusVideo, 'Watch the campus tour') ?></div><?php endif; ?>

  <?php if ($spots): ?>
  <div class="cp-head"><h3>Places to explore</h3><span class="cp-swipe">Swipe or scroll &rarr;</span></div>
  <div class="cp-rail">
    <?php foreach ($spots as $sp): $tag = $sp['link'] ? 'a' : 'div'; ?>
      <<?= $tag ?> class="cp-spot"<?= $sp['link'] ? ' href="' . e($sp['link']) . '" target="_blank" rel="noopener"' : '' ?>>
        <?= $sp['image'] ? '<img src="' . e(url('uploads/' . rawurlencode($sp['image']))) . '" alt="" loading="lazy">' : '<div class="ph">' . icon('camera', 36) . '</div>' ?>
        <div class="cp-spot-b"><h3><?= e($sp['title']) ?></h3><?= $sp['description'] ? '<p>' . e($sp['description']) . '</p>' : '' ?><?= $sp['link'] ? '<span class="cp-more">Learn more &#8599;</span>' : '' ?></div>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
  <?php elseif (is_staff()): ?>
  <div class="cp-empty"><?= icon('camera', 22) ?><div><b>Show off the campus</b><span>Add places with photos in <a href="<?= url('admin/campus.php?new=1') ?>">Admin &rarr; Campus</a> and they appear here as a swipeable gallery. Only staff see this note.</span></div></div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($on('events') && $events): ?>
<?php
$ueTone = ['Dinner' => 'gold', 'AGM' => 'green', 'Sports' => 'teal', 'Networking' => 'blue', 'Awards' => 'rose', 'Community Service' => 'violet', 'Chapter Event' => 'slate', 'Other' => 'green'];
$ueIcon = ['Dinner' => 'users', 'AGM' => 'file', 'Sports' => 'target', 'Networking' => 'link', 'Awards' => 'award', 'Community Service' => 'heart', 'Chapter Event' => 'map-pin', 'Other' => 'calendar'];
$ueCats = array_values(array_unique(array_column($events, 'category')));
$ueCard = function (array $ev, bool $big) use ($ueTone, $ueIcon) {
    $tone = $ueTone[$ev['category']] ?? 'green';
    $ic = $ueIcon[$ev['category']] ?? 'calendar';
    $fl = event_flyer_url($ev);
    $href = url('event_view.php?id=' . (int) $ev['id']);
    $open = $ev['registration_open'] && $ev['status'] === 'scheduled';
    $dated = !empty($ev['starts_at']);
    ob_start(); ?>
    <article class="ue-card tone-<?= e($tone) ?><?= $big ? ' ue-big' : '' ?>" data-cat="<?= e($ev['category']) ?>">
      <div class="ue-media">
        <?= $fl ? '<img src="' . e($fl) . '" alt="" loading="lazy">' : '<span class="ue-art" aria-hidden="true">' . icon($ic, $big ? 96 : 64) . '</span>' ?>
        <div class="ue-date<?= $dated ? '' : ' tba' ?>"><?php if ($dated): ?><b><?= e(fmt_date($ev['starts_at'], 'j')) ?></b><span><?= e(fmt_date($ev['starts_at'], 'M')) ?></span><small><?= e(fmt_date($ev['starts_at'], 'Y')) ?></small><?php else: ?><b>TBA</b><span>soon</span><?php endif; ?></div>
        <span class="ue-cat"><?= icon($ic, 14) ?> <?= e($ev['category']) ?></span>
      </div>
      <div class="ue-body">
        <div class="ue-pills">
          <?php if ($open): ?><span class="ue-pill live"><i></i>Registration open</span><?php endif; ?>
          <?php if (!$dated): ?><span class="ue-pill soon">Save the date</span><?php endif; ?>
          <?= event_status_badge($ev) ?>
        </div>
        <h3><a class="ue-link" href="<?= e($href) ?>"><?= e($ev['title']) ?></a></h3>
        <ul class="ue-meta">
          <li><?= icon('clock', 16) ?><span><?= e(event_when($ev, 'D j M Y, g:ia')) ?></span></li>
          <?php if ($ev['location']): ?><li><?= icon('map-pin', 16) ?><span><?= e($ev['location']) ?></span></li><?php endif; ?>
        </ul>
        <?= event_countdown($ev, $big ? '' : 'small') ?>
        <div class="ue-actions">
          <a class="ue-go" href="<?= e($href) ?>"><?= $open ? 'Register now' : 'View details' ?> <span aria-hidden="true">&rarr;</span></a>
          <?php if ($dated): ?><a class="ue-ic" href="<?= e(url('event_ics.php?id=' . (int) $ev['id'])) ?>" title="Add to my calendar" aria-label="Add <?= e($ev['title']) ?> to my calendar"><?= icon('calendar', 18) ?></a><?php endif; ?>
          <button type="button" class="ue-ic ue-share" data-url="<?= e(absolute_url('event_view.php?id=' . (int) $ev['id'])) ?>" data-title="<?= e($ev['title']) ?>" title="Share this event" aria-label="Share <?= e($ev['title']) ?>"><?= icon('link', 18) ?></button>
        </div>
      </div>
    </article>
    <?php return ob_get_clean();
};
?>
<section class="home-section ue" id="events">
  <div class="sec-head"><div><span class="eyebrow">Mark your calendar</span><h2><?= ct('home.events_heading') ?></h2></div><a href="<?= url('events.php') ?>">View all events &rarr;</a></div>
  <?php if (count($ueCats) > 1): ?>
  <div class="ue-chips" role="group" aria-label="Filter events by type">
    <button type="button" class="on" data-c="">All events</button>
    <?php foreach ($ueCats as $c): ?><button type="button" data-c="<?= e($c) ?>"><?= icon($ueIcon[$c] ?? 'calendar', 14) ?> <?= e($c) ?></button><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="ue-grid<?= count($events) === 1 ? ' one' : '' ?>" id="ueGrid">
    <?php foreach ($events as $i => $ev) { echo $ueCard($ev, $i === 0); } ?>
  </div>
  <p class="ue-empty" id="ueEmpty" hidden>No events of that type right now. Try another type.</p>
</section>
<script>
(function () {
  var sec = document.getElementById('events'); if (!sec) return;
  var grid = document.getElementById('ueGrid'), cards = grid.querySelectorAll('.ue-card');
  sec.querySelectorAll('.ue-chips button').forEach(function (b) {
    b.addEventListener('click', function () {
      var c = b.getAttribute('data-c'), shown = 0;
      sec.querySelectorAll('.ue-chips button').forEach(function (x) { x.classList.toggle('on', x === b); });
      cards.forEach(function (card) { var hit = !c || card.getAttribute('data-cat') === c; card.hidden = !hit; if (hit) shown++; });
      grid.classList.toggle('filtered', !!c);
      document.getElementById('ueEmpty').hidden = shown > 0;
    });
  });
  sec.querySelectorAll('.ue-share').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault();
      var url = b.getAttribute('data-url'), title = b.getAttribute('data-title');
      var done = function () { b.classList.add('ok'); b.setAttribute('title', 'Link copied'); setTimeout(function () { b.classList.remove('ok'); b.setAttribute('title', 'Share this event'); }, 1800); };
      if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); return; }
      if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(url).then(done, function () { window.prompt('Copy this link', url); }); }
      else { window.prompt('Copy this link', url); }
    });
  });
  // Soft spotlight that follows the pointer over each card (desktop only).
  if (window.matchMedia && matchMedia('(hover: hover)').matches && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    cards.forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        var r = card.getBoundingClientRect();
        card.style.setProperty('--mx', (e.clientX - r.left) + 'px'); card.style.setProperty('--my', (e.clientY - r.top) + 'px');
      });
    });
  }
})();
</script>
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

<?php if ($fame): ?>
<section class="home-section sec-center" aria-label="Alumni Hall of Fame">
  <div class="sec-head"><div><span class="eyebrow">Alumni Hall of Fame</span><h2>Honouring our outstanding alumni</h2></div><a href="<?= url('awards.php#hall') ?>">Meet them all &rarr;</a></div>
  <div class="grid two" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr))">
    <?php foreach ($fame as $h): $ph = $h['photo'] ? url('uploads/' . rawurlencode($h['photo'])) : ''; ?>
      <a class="card accent" href="<?= url('hall-of-fame.php?id=' . (int) $h['id']) ?>" style="text-decoration:none;color:inherit">
        <?php if ($ph): ?><img src="<?= e($ph) ?>" alt="" loading="lazy" width="96" height="96" style="width:96px;height:96px;border-radius:50%;object-fit:cover;display:block;margin:0 auto 8px">
        <?php else: ?><div style="width:96px;height:96px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;margin:0 auto 8px"><?= e(mb_strtoupper(mb_substr($h['name'], 0, 1))) ?></div><?php endif; ?>
        <strong><?= e($h['name']) ?></strong><br><span class="meta"><?= e($h['title'] ?: $h['category']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($on('anthem')):
$hasAnthem = $anthemAudio || $anthemVideo || $anthemLyrics;
$anthemList = q("SELECT slug, title, kicker, credit, lyrics, audio FROM anthems WHERE is_public = 1 AND audio IS NOT NULL AND audio <> '' ORDER BY (id = ?) DESC, sort_order, id", [(int) ($anthemRow['id'] ?? 0)])->fetchAll();
?>
<?php if ($anthemList): ?>
<?php
$anthemData = array_map(fn($a) => [
    'slug' => $a['slug'], 'title' => $a['title'], 'kicker' => (string) $a['kicker'], 'credit' => (string) $a['credit'],
    'lyrics' => trim((string) $a['lyrics']), 'audio' => url('uploads/' . rawurlencode($a['audio'])),
], $anthemList);
$short = fn($t) => trim(preg_replace('/\b(anthem|the)\b/i', '', $t)) ?: $t;
?>
<section class="home-section anthem an2" id="anthemShow" data-anthems="<?= e(json_encode($anthemData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
  <div class="an2-card" id="an2Card">
    <svg class="an2-staff" viewBox="0 0 800 200" preserveAspectRatio="none" aria-hidden="true" focusable="false"><g stroke="rgba(255,255,255,.07)" stroke-width="1.4" fill="none"><path d="M0 40h800M0 60h800M0 80h800M0 100h800M0 120h800"/><path d="M0 40c200-30 400 30 800-10M0 60c200-30 400 30 800-10M0 80c200-30 400 30 800-10M0 100c200-30 400 30 800-10M0 120c200-30 400 30 800-10" opacity=".6"/></g></svg>
    <span class="an2-note n1" aria-hidden="true">&#9834;</span><span class="an2-note n2" aria-hidden="true">&#9835;</span><span class="an2-note n3" aria-hidden="true">&#9833;</span>
    <button type="button" class="an2-close" id="an2Close" aria-label="Close full screen" hidden>&times;</button>

    <div class="an2-stage">
      <div class="an2-disc-wrap" id="an2Wrap">
        <canvas id="an2Viz" aria-hidden="true"></canvas>
        <div class="an2-disc" id="an2Disc"><span class="an2-grooves"></span><span class="an2-label"><img src="<?= e(logo_url()) ?>" alt="" width="120" height="120"></span></div>
        <button type="button" class="an2-bigplay" id="an2Big" aria-label="Play the anthem"><svg viewBox="0 0 24 24" width="34" height="34" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></button>
      </div>
      <div class="an2-now" aria-live="polite"><span class="an2-state" id="an2State">Ready to play</span><b id="an2Title"><?= e($anthemData[0]['title']) ?></b><small id="an2Kicker"><?= e($anthemData[0]['kicker']) ?></small></div>
    </div>

    <div class="an2-main">
      <span class="an2-eyebrow"><i></i>Sing with us</span>
      <h2><?= ct('home.anthem_title') ?></h2>
      <div class="an2-intro"><?= cr('home.anthem_text') ?></div>

      <?php if (count($anthemData) > 1): ?>
      <div class="an2-tabs" role="tablist" aria-label="Choose an anthem">
        <?php foreach ($anthemData as $i => $a): ?><button type="button" role="tab" class="<?= $i === 0 ? 'on' : '' ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-i="<?= $i ?>"><?= e($short($a['title'])) ?></button><?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="an2-player">
        <div class="an2-row">
          <button type="button" class="an2-play" id="an2Play" aria-label="Play the anthem"><svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor" aria-hidden="true" id="an2PlayIc"><path d="M8 5v14l11-7z"/></svg></button>
          <div class="an2-prog">
            <input type="range" id="an2Seek" min="0" max="1000" value="0" step="1" aria-label="Seek through the anthem">
            <div class="an2-times"><span id="an2Cur">0:00</span><span id="an2Dur">0:00</span></div>
          </div>
        </div>
        <div class="an2-ctrls">
          <button type="button" id="an2Back" aria-label="Back 10 seconds" title="Back 10 seconds"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg><span>10</span></button>
          <button type="button" id="an2Fwd" aria-label="Forward 10 seconds" title="Forward 10 seconds"><span>10</span><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg></button>
          <button type="button" id="an2Mute" aria-pressed="false" aria-label="Mute" title="Mute"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7" id="an2Wave"/></svg></button>
          <button type="button" id="an2Loop" aria-pressed="false" aria-label="Repeat" title="Repeat"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m17 2 4 4-4 4"/><path d="M3 11V9a3 3 0 0 1 3-3h15"/><path d="m7 22-4-4 4-4"/><path d="M21 13v2a3 3 0 0 1-3 3H3"/></svg></button>
          <span class="an2-sp"></span>
          <button type="button" class="an2-txt" id="an2LyrBtn" aria-pressed="false" hidden><?= icon('file', 16) ?> Lyrics</button>
          <button type="button" class="an2-txt" id="an2Full" aria-pressed="false"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M16 21h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg> Stand &amp; sing</button>
        </div>
      </div>

      <div class="an2-lyrics" id="an2Lyrics" hidden>
        <div class="an2-lyr-head"><b>Follow the words</b><small>The highlight moves with the song. Tap a verse to jump to it.</small></div>
        <div class="an2-verses" id="an2Verses"></div>
      </div>

      <p class="an2-more"><a class="btn" href="<?= url('anthems.php') ?>">All anthems: Gulu University, Uganda and East Africa &rarr;</a></p>
    </div>
    <audio id="an2Audio" preload="metadata" playsinline></audio>
  </div>
</section>
<script>
(function () {
  var root = document.getElementById('anthemShow'); if (!root) return;
  var list = []; try { list = JSON.parse(root.getAttribute('data-anthems')); } catch (e) {} if (!list.length) return;
  var $ = function (id) { return document.getElementById(id); };
  var card = $('an2Card'), audio = $('an2Audio'), play = $('an2Play'), big = $('an2Big'), seek = $('an2Seek'), cur = $('an2Cur'), dur = $('an2Dur');
  var state = $('an2State'), titleEl = $('an2Title'), kick = $('an2Kicker'), lyrBox = $('an2Lyrics'), verses = $('an2Verses'), lyrBtn = $('an2LyrBtn');
  var PLAY = '<path d="M8 5v14l11-7z"/>', PAUSE = '<rect x="6" y="5" width="4" height="14" rx="1.2"/><rect x="14" y="5" width="4" height="14" rx="1.2"/>';
  var idx = 0, seeking = false, vEls = [], activeV = -1, reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function fmt(s) { if (!isFinite(s)) return '0:00'; s = Math.max(0, Math.round(s)); return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }
  function icon(p) { $('an2PlayIc').innerHTML = p; big.querySelector('svg').innerHTML = p; }
  function setPlaying(on) {
    card.classList.toggle('playing', on); icon(on ? PAUSE : PLAY);
    var l = on ? 'Pause the anthem' : 'Play the anthem'; play.setAttribute('aria-label', l); big.setAttribute('aria-label', l);
    state.textContent = on ? 'Now playing' : (audio.currentTime > 1 && !audio.ended ? 'Paused' : 'Ready to play');
    if (on) startViz(); else drawViz(true);
  }
  function renderLyrics(text) {
    verses.innerHTML = ''; vEls = []; activeV = -1;
    if (!text) { lyrBtn.hidden = true; lyrBox.hidden = true; lyrBtn.setAttribute('aria-pressed', 'false'); card.classList.remove('with-lyrics'); return; }
    lyrBtn.hidden = false;
    text.split(/\r?\n\s*\r?\n/).forEach(function (v, i) {
      var d = document.createElement('div'); d.className = 'an2-v'; d.tabIndex = 0;
      v.split(/\r?\n/).forEach(function (line, j) { if (j) d.appendChild(document.createElement('br')); d.appendChild(document.createTextNode(line.replace(/^[-*]\s+/, '').replace(/\*\*/g, ''))); });
      d.addEventListener('click', function () { jump(i); });
      d.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); jump(i); } });
      verses.appendChild(d); vEls.push(d);
    });
    lyrBox.hidden = lyrBtn.getAttribute('aria-pressed') !== 'true'; card.classList.toggle('with-lyrics', !lyrBox.hidden);
  }
  function jump(i) { if (audio.duration) { audio.currentTime = (i / vEls.length) * audio.duration; if (audio.paused) audio.play(); } }
  function load(i, autoplay) {
    idx = i; var a = list[i];
    audio.src = a.audio; audio.load();
    titleEl.textContent = a.title; kick.textContent = a.kicker || ''; seek.value = 0; seek.style.setProperty('--p', '0%'); cur.textContent = '0:00'; dur.textContent = '0:00';
    root.querySelectorAll('.an2-tabs button').forEach(function (b) { var on = +b.getAttribute('data-i') === i; b.classList.toggle('on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
    renderLyrics(a.lyrics); setPlaying(false); state.textContent = 'Ready to play';
    if ('mediaSession' in navigator) { try { navigator.mediaSession.metadata = new MediaMetadata({ title: a.title, artist: 'Gulu University Convocation', album: 'Anthems' }); } catch (e) {} }
    if (autoplay) togglePlay();
  }
  function togglePlay() { if (audio.paused) { ensureAudio(); var p = audio.play(); if (p && p.catch) p.catch(function () { state.textContent = 'Press play to start'; }); } else { audio.pause(); } }
  play.addEventListener('click', togglePlay); big.addEventListener('click', togglePlay);
  audio.addEventListener('play', function () { setPlaying(true); }); audio.addEventListener('pause', function () { setPlaying(false); });
  audio.addEventListener('ended', function () { setPlaying(false); state.textContent = 'Finished. Play again?'; });
  audio.addEventListener('loadedmetadata', function () { dur.textContent = fmt(audio.duration); });
  audio.addEventListener('timeupdate', function () {
    if (!seeking && audio.duration) { seek.value = Math.round(audio.currentTime / audio.duration * 1000); seek.style.setProperty('--p', seek.value / 10 + '%'); }
    cur.textContent = fmt(audio.currentTime);
    if (vEls.length && audio.duration) {
      var v = Math.min(vEls.length - 1, Math.floor(audio.currentTime / audio.duration * vEls.length));
      if (v !== activeV) {
        if (vEls[activeV]) vEls[activeV].classList.remove('on'); activeV = v; vEls[v].classList.add('on');
        if (!lyrBox.hidden) { var box = verses, el = vEls[v]; box.scrollTo({ top: el.offsetTop - box.clientHeight / 3, behavior: reduce ? 'auto' : 'smooth' }); }
      }
    }
  });
  seek.addEventListener('input', function () { seeking = true; seek.style.setProperty('--p', seek.value / 10 + '%'); if (audio.duration) cur.textContent = fmt(seek.value / 1000 * audio.duration); });
  seek.addEventListener('change', function () { if (audio.duration) audio.currentTime = seek.value / 1000 * audio.duration; seeking = false; });
  $('an2Back').addEventListener('click', function () { audio.currentTime = Math.max(0, audio.currentTime - 10); });
  $('an2Fwd').addEventListener('click', function () { audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 10); });
  $('an2Mute').addEventListener('click', function () { audio.muted = !audio.muted; this.setAttribute('aria-pressed', audio.muted ? 'true' : 'false'); $('an2Wave').style.display = audio.muted ? 'none' : ''; });
  $('an2Loop').addEventListener('click', function () { audio.loop = !audio.loop; this.setAttribute('aria-pressed', audio.loop ? 'true' : 'false'); });
  lyrBtn.addEventListener('click', function () { var on = lyrBtn.getAttribute('aria-pressed') !== 'true'; lyrBtn.setAttribute('aria-pressed', on ? 'true' : 'false'); lyrBox.hidden = !on; card.classList.toggle('with-lyrics', on); });
  root.querySelectorAll('.an2-tabs button').forEach(function (b) { b.addEventListener('click', function () { var i = +b.getAttribute('data-i'); if (i === idx) return; var was = !audio.paused; load(i, was); }); });
  if ('mediaSession' in navigator) { try { navigator.mediaSession.setActionHandler('play', function () { audio.play(); }); navigator.mediaSession.setActionHandler('pause', function () { audio.pause(); }); } catch (e) {} }

  // Stand & sing: a full-screen view of the same player.
  var fullBtn = $('an2Full'), closeBtn = $('an2Close');
  function isFull() { return card.classList.contains('an2-full'); }
  function setFull(on) {
    card.classList.toggle('an2-full', on); document.documentElement.classList.toggle('an2-lock', on); closeBtn.hidden = !on; fullBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
    if (on && lyrBtn.getAttribute('aria-pressed') !== 'true' && !lyrBtn.hidden) lyrBtn.click();
    try { if (on && card.requestFullscreen) card.requestFullscreen(); else if (!on && document.fullscreenElement) document.exitFullscreen(); } catch (e) {}
    sizeViz(); drawViz(true);
  }
  fullBtn.addEventListener('click', function () { setFull(!isFull()); }); closeBtn.addEventListener('click', function () { setFull(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && isFull()) setFull(false); });
  document.addEventListener('fullscreenchange', function () { if (!document.fullscreenElement && isFull()) setFull(false); });

  // Circular visualiser around the disc.
  var cv = $('an2Viz'), ctx = cv.getContext('2d'), wrap = $('an2Wrap'), ac = null, an = null, data = null, raf = 0, t0 = performance.now(), live = false;
  function ensureAudio() {
    if (ac || reduce) return;
    try {
      var AC = window.AudioContext || window.webkitAudioContext; if (!AC) return;
      ac = new AC(); an = ac.createAnalyser(); an.fftSize = 128; an.smoothingTimeConstant = .82;
      var src = ac.createMediaElementSource(audio); src.connect(an); an.connect(ac.destination); data = new Uint8Array(an.frequencyBinCount); live = true;
    } catch (e) { ac = null; an = null; live = false; }
    if (ac && ac.state === 'suspended') ac.resume();
  }
  function sizeViz() {
    var r = wrap.getBoundingClientRect(), d = window.devicePixelRatio || 1;
    cv.width = Math.round(r.width * d); cv.height = Math.round(r.height * d); ctx.setTransform(d, 0, 0, d, 0, 0);
  }
  function drawViz(idle) {
    var w = cv.width / (window.devicePixelRatio || 1), h = cv.height / (window.devicePixelRatio || 1), cx = w / 2, cy = h / 2;
    var inner = Math.min(w, h) * .385, max = Math.min(w, h) * .115, n = 72, t = (performance.now() - t0) / 1000;
    ctx.clearRect(0, 0, w, h);
    var vals = null; if (!idle && live && an) { an.getByteFrequencyData(data); var sum = 0; for (var k = 0; k < data.length; k++) sum += data[k]; if (sum > 0) vals = data; }
    for (var i = 0; i < n; i++) {
      var a = (i / n) * Math.PI * 2 - Math.PI / 2, v;
      if (idle) v = .06; else if (vals) { var bi = Math.floor(Math.abs(Math.sin(i / n * Math.PI)) * (vals.length * .7)); v = vals[bi] / 255; } else { v = .25 + .25 * Math.sin(t * 3 + i * .5) * Math.sin(t * 1.3 + i * .21); }
      var len = 4 + v * max, x1 = cx + Math.cos(a) * inner, y1 = cy + Math.sin(a) * inner, x2 = cx + Math.cos(a) * (inner + len), y2 = cy + Math.sin(a) * (inner + len);
      ctx.strokeStyle = i % 3 === 0 ? 'rgba(242,184,7,.95)' : 'rgba(255,255,255,.55)'; ctx.lineWidth = Math.max(2, w / 150); ctx.lineCap = 'round';
      ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
    }
  }
  function frame() { drawViz(false); raf = audio.paused ? 0 : requestAnimationFrame(frame); }
  function startViz() { if (!raf) raf = requestAnimationFrame(frame); }
  sizeViz(); drawViz(true); window.addEventListener('resize', function () { sizeViz(); drawViz(audio.paused); });
  load(0, false);
})();
</script>
<?php else: ?>
<section class="home-section anthem">
  <div class="anthem-card">
    <div class="anthem-text">
      <span class="anthem-ic"><?= icon('music', 30) ?></span>
      <h2><?= ct('home.anthem_title') ?></h2>
      <?= cr('home.anthem_text') ?>
      <?php if (!$hasAnthem): ?><div class="anthem-soon"><span class="play"><?= icon('play', 30) ?></span><div><b>The anthem is coming soon</b><small>Come back to listen and sing along.</small></div></div><?php endif; ?>
      <p style="margin-top:16px"><a class="btn outline" href="<?= url('anthems.php') ?>">All anthems: Gulu University, Uganda and East Africa &rarr;</a></p>
      <?php if ($anthemLyrics && $anthemVideo): ?><details class="anthem-lyrics" open><summary>Lyrics</summary><div><?= render_text($anthemLyrics) ?></div></details><?php endif; ?>
    </div>
    <?php if ($anthemVideo): ?><div class="anthem-video"><?= video_embed($anthemVideo, 'Watch the anthem video') ?></div>
    <?php elseif ($anthemLyrics): ?><div class="anthem-video anthem-sheet"><h3><?= icon('music', 18) ?> Lyrics</h3><div class="lyr"><?= render_text($anthemLyrics) ?></div></div>
    <?php else: ?><div class="anthem-video anthem-art" aria-hidden="true"><div class="eq"><?php for ($i = 0; $i < 18; $i++): ?><i style="animation-delay:<?= $i * 0.09 ?>s"></i><?php endfor; ?></div><span><?= icon('music', 44) ?></span></div><?php endif; ?>
  </div>
</section>
<?php endif; ?>
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
