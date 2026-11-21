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
$glTones = ['#f2b807', '#5eead4', '#93c5fd', '#fda4af', '#c4b5fd', '#fdba74'];
?>
<section class="band gl" aria-label="GUCO at a glance">
  <div class="gl-head">
    <span class="gl-eyebrow"><?= ct('home.glance_eyebrow') ?></span>
    <h2><?= ct('home.glance_title') ?></h2>
    <span class="gl-live"><i></i> Counted live from the website</span>
  </div>
  <div class="gl-grid">
    <?php foreach ($live as $i => [$ic, $n, $label]): ?>
      <a class="gl-item" href="<?= url($glLinks[$label] ?? 'index.php') ?>" style="--c:<?= $glTones[$i % 6] ?>;--d:<?= $i * 140 ?>ms" aria-label="<?= (int) $n ?> <?= e($label) ?>: open">
        <span class="gl-ring">
          <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="gl-track" cx="60" cy="60" r="52"/><circle class="gl-run" cx="60" cy="60" r="52"/></svg>
          <span class="gl-ic"><?= icon($ic, 26) ?></span>
        </span>
        <b><?= (int) $n ?></b>
        <span class="gl-label"><?= e($label) ?></span>
        <span class="gl-go">View &rarr;</span>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="gl-ticker" aria-hidden="true">
    <div class="gl-roll">
      <?php for ($k = 0; $k < 4; $k++): foreach ($live as [$ic, $n, $label]): ?><span><b><?= (int) $n ?></b> <?= e($label) ?></span><i></i><?php endforeach; endfor; ?>
    </div>
  </div>
</section>
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

<?php if ($on('anthem')): $hasAnthem = $anthemAudio || $anthemVideo || $anthemLyrics; ?>
<section class="home-section anthem">
  <div class="anthem-card">
    <div class="anthem-text">
      <span class="anthem-ic"><?= icon('music', 30) ?></span>
      <h2><?= ct('home.anthem_title') ?></h2>
      <?= cr('home.anthem_text') ?>
      <?php if ($anthemAudio): ?>
        <div class="anthem-player">
          <button type="button" class="play" aria-label="Play the anthem"><?= icon('play', 30) ?></button>
          <div><b>Play the anthem</b><small>Press play and sing along with the lyrics.</small></div>
          <?php if (str_ends_with((string) ($anthemRow['audio'] ?? ''), '.mp4')): ?><video preload="metadata" playsinline controls class="anthem-audio anthem-mp4" src="<?= e($anthemAudio) ?>"></video>
          <?php else: ?><audio preload="none" src="<?= e($anthemAudio) ?>" controls class="anthem-audio">Your browser does not support audio playback.</audio><?php endif; ?>
        </div>
      <?php elseif (!$hasAnthem): ?><div class="anthem-soon"><span class="play"><?= icon('play', 30) ?></span><div><b>The anthem is coming soon</b><small>Come back to listen and sing along.</small></div></div><?php endif; ?>
      <p style="margin-top:16px"><a class="btn outline" href="<?= url('anthems.php') ?>">All anthems: Gulu University, Uganda and East Africa &rarr;</a></p>
      <?php if ($anthemLyrics && $anthemVideo): ?><details class="anthem-lyrics" open><summary>Lyrics</summary><div><?= render_text($anthemLyrics) ?></div></details><?php endif; ?>
    </div>
    <?php if ($anthemVideo): ?><div class="anthem-video"><?= video_embed($anthemVideo, 'Watch the anthem video') ?></div>
    <?php elseif ($anthemLyrics): ?><div class="anthem-video anthem-sheet"><h3><?= icon('music', 18) ?> Lyrics</h3><div class="lyr"><?= render_text($anthemLyrics) ?></div></div>
    <?php else: ?><div class="anthem-video anthem-art" aria-hidden="true"><div class="eq"><?php for ($i = 0; $i < 18; $i++): ?><i style="animation-delay:<?= $i * 0.09 ?>s"></i><?php endfor; ?></div><span><?= icon('music', 44) ?></span></div><?php endif; ?>
  </div>
</section>
<?php if ($anthemAudio): ?>
<script>
(function () {
  var box = document.querySelector('.anthem-player'); if (!box) return;
  var a = box.querySelector('audio, video'), b = box.querySelector('.play'), t = box.querySelector('b');
  var play = '<?= str_replace(["\r", "\n", "'"], ['', '', "\\'"], icon('play', 30)) ?>', pause = '<svg class="icon" width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>';
  b.addEventListener('click', function () { a.paused ? a.play() : a.pause(); });
  a.addEventListener('play', function () { b.innerHTML = pause; t.textContent = 'Now playing'; });
  a.addEventListener('pause', function () { b.innerHTML = play; t.textContent = 'Play the anthem'; });
  a.addEventListener('ended', function () { b.innerHTML = play; t.textContent = 'Play the anthem'; });
})();
</script>
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
