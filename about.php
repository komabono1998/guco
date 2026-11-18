<?php
$pageTitle = 'About GUCO';
$pageDesc = 'Overview, vision, mission, core values and objectives of Gulu University Convocation (GUCO).';
require __DIR__ . '/includes/header.php';
$img = c_image('about.image');
$valueIcons = ['shield', 'users', 'graduation', 'link', 'star', 'heart'];
$objectives = c_lines('about.objectives');
?>
<div class="page-head"><h1>About GUCO</h1><p><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p></div>

<?php
$ovSlides = [];
if ($img) {
    $ovSlides[] = [$img, 'Gulu University Convocation'];
}
foreach ([19, 4, 7, 14, 11, 16] as $ovN) {
    $ovSlides[] = [dinner_src($ovN), DINNER_PHOTOS[$ovN]];
}
$ovFacts = [
    ['2023', 'Constitution in force', '#foundation', '#b45309'],
    ['4', 'Membership categories', url('membership.php#categories'), '#14532d'],
    ['6', 'Core values', '#values', '#1d4ed8'],
    [(string) count($objectives), 'Objectives', '#objectives', '#be123c'],
];
?>
<section id="overview" class="section ov">
  <div class="ov-text">
    <span class="eyebrow-s">Who we are</span>
    <h2 style="margin-top:4px">Overview</h2>
    <div class="ov-copy"><?= cr('about.overview') ?></div>
    <div class="ov-facts">
      <?php foreach ($ovFacts as $fi => [$fv, $fl, $fh, $fc]): ?>
        <a class="ov-fact" href="<?= e($fh) ?>" style="--c:<?= $fc ?>;--d:<?= $fi * 90 ?>ms">
          <b><?= e($fv) ?></b><span><?= e($fl) ?></span><i aria-hidden="true">&rarr;</i>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="ov-media" id="ovMedia">
    <span class="ov-chip one"><?= icon('users', 18) ?> Umbrella body for Gulu University alumni</span>
    <span class="ov-chip two"><?= icon('book', 18) ?> Constitution in force since 2023</span>
    <div class="ov-frame">
      <div class="ov-slides">
        <?php foreach ($ovSlides as $si => [$src, $alt]): ?>
          <img class="<?= $si === 0 ? 'on' : '' ?>" src="<?= e($src) ?>" alt="<?= e($alt) ?>" data-cap="<?= e($alt) ?>"<?= $si === 0 ? '' : ' loading="lazy"' ?>>
        <?php endforeach; ?>
      </div>
      <button type="button" class="ov-arrow prev" aria-label="Previous photo">&lsaquo;</button>
      <button type="button" class="ov-arrow next" aria-label="Next photo">&rsaquo;</button>
      <div class="ov-cap"><span id="ovCap"><?= e($ovSlides[0][1]) ?></span><div class="ov-dots"><?php foreach ($ovSlides as $si => $_): ?><button type="button" class="<?= $si === 0 ? 'on' : '' ?>" aria-label="Show photo <?= $si + 1 ?>"></button><?php endforeach; ?></div></div>
    </div>
  </div>
</section>
<script>
(function () {
  var box = document.getElementById('ovMedia'); if (!box) return;
  var imgs = [].slice.call(box.querySelectorAll('.ov-slides img')), dots = [].slice.call(box.querySelectorAll('.ov-dots button')), cap = document.getElementById('ovCap');
  var cur = 0, timer = null, reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function show(i) {
    cur = (i + imgs.length) % imgs.length;
    imgs.forEach(function (m, k) { m.classList.toggle('on', k === cur); });
    dots.forEach(function (d, k) { d.classList.toggle('on', k === cur); });
    cap.textContent = imgs[cur].getAttribute('data-cap');
  }
  function start() { if (!reduce) { stop(); timer = setInterval(function () { show(cur + 1); }, 5200); } }
  function stop() { clearInterval(timer); }
  dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); start(); }); });
  box.querySelector('.prev').addEventListener('click', function () { show(cur - 1); start(); });
  box.querySelector('.next').addEventListener('click', function () { show(cur + 1); start(); });
  box.addEventListener('mouseenter', stop); box.addEventListener('mouseleave', start); box.addEventListener('focusin', stop); box.addEventListener('focusout', start);
  var frame = box.querySelector('.ov-frame');
  if (!reduce && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    box.addEventListener('mousemove', function (e) {
      var r = box.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      frame.style.transform = 'rotateY(' + (x * 8) + 'deg) rotateX(' + (-y * 6) + 'deg)';
    });
    box.addEventListener('mouseleave', function () { frame.style.transform = ''; });
  }
  start();
})();
</script>

<section id="vision" class="section">
  <div class="purpose">
    <article class="p-card vision">
      <span class="p-ic"><?= icon('eye', 30) ?></span>
      <h2>Our Vision</h2>
      <div class="p-text"><?= cr('about.vision') ?></div>
      <span class="p-mark"><?= icon('eye', 150) ?></span>
    </article>
    <article class="p-card mission">
      <span class="p-ic"><?= icon('target', 30) ?></span>
      <h2>Our Mission</h2>
      <div class="p-text"><?= cr('about.mission') ?></div>
      <span class="p-mark"><?= icon('target', 150) ?></span>
    </article>
  </div>
</section>

<section id="values" class="section">
  <div class="sec-head"><div><span class="eyebrow-s">What guides us</span><h2>Our core values</h2></div></div>
  <?php
  $cvRows = c_pairs('about.values');
  $cvTones = ['#14532d', '#b91c1c', '#1d4ed8', '#b45309', '#7e22ce', '#0f766e'];
  ?>
  <div class="cv" id="cv" role="group" aria-label="Our core values">
    <div class="cv-list" role="tablist" aria-orientation="vertical">
      <?php foreach ($cvRows as $i => [$name, $desc]): ?>
        <button type="button" role="tab" class="cv-pill<?= $i === 0 ? ' on' : '' ?>" id="cv-t-<?= $i ?>" aria-controls="cv-p-<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-i="<?= $i ?>" style="--c:<?= $cvTones[$i % 6] ?>">
          <span class="cv-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="cv-ic"><?= icon($valueIcons[$i % 6], 22) ?></span>
          <span class="cv-name"><?= e($name) ?></span>
        </button>
      <?php endforeach; ?>
    </div>
    <div class="cv-stage">
      <?php foreach ($cvRows as $i => [$name, $desc]): ?>
        <article class="cv-panel<?= $i === 0 ? ' on' : '' ?>" role="tabpanel" id="cv-p-<?= $i ?>" aria-labelledby="cv-t-<?= $i ?>" style="--c:<?= $cvTones[$i % 6] ?>">
          <span class="cv-big"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="cv-medal"><?= icon($valueIcons[$i % 6], 44) ?></span>
          <h3><?= e($name) ?></h3>
          <?php if ($desc): ?><p><?= e($desc) ?></p><?php endif; ?>
          <span class="cv-count">Value <?= $i + 1 ?> of <?= count($cvRows) ?></span>
        </article>
      <?php endforeach; ?>
      <div class="cv-bar" aria-hidden="true"><i></i></div>
    </div>
  </div>
  <script>
  (function () {
    var box = document.getElementById('cv'); if (!box) return;
    var pills = [].slice.call(box.querySelectorAll('.cv-pill')), panels = [].slice.call(box.querySelectorAll('.cv-panel')), bar = box.querySelector('.cv-bar i');
    var cur = 0, timer = null, touched = false, reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function restartBar() { if (!bar) return; bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = touched || reduce ? 'none' : ''; }
    function show(i) {
      cur = i;
      pills.forEach(function (p, k) { p.classList.toggle('on', k === i); p.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
      panels.forEach(function (p, k) { p.classList.toggle('on', k === i); });
      box.style.setProperty('--cur', pills[i].style.getPropertyValue('--c'));
      restartBar();
    }
    function stop() { touched = true; clearInterval(timer); if (bar) { bar.style.animation = 'none'; bar.style.width = '0'; } }
    pills.forEach(function (p, k) {
      p.addEventListener('click', function () { stop(); show(k); });
      p.addEventListener('mouseenter', function () { if (window.matchMedia('(hover: hover)').matches) { stop(); show(k); } });
      p.addEventListener('keydown', function (e) {
        var d = e.key === 'ArrowDown' || e.key === 'ArrowRight' ? 1 : (e.key === 'ArrowUp' || e.key === 'ArrowLeft' ? -1 : 0);
        if (d) { e.preventDefault(); var n = (k + d + pills.length) % pills.length; stop(); show(n); pills[n].focus(); }
      });
    });
    show(0);
    if (!reduce) { timer = setInterval(function () { show((cur + 1) % pills.length); }, 5500); }
  })();
  </script>
</section>

<section id="objectives" class="section">
  <div class="sec-head"><div><span class="eyebrow-s">What we aim to achieve</span><h2>Our objectives</h2></div></div>
  <?php
  $objTones = ['#14532d', '#b45309', '#1d4ed8', '#be123c', '#0f766e', '#7e22ce'];
  $objIcon = function (string $text): string {
      $map = ['network' => 'users', 'academic' => 'graduation', 'growth' => 'graduation', 'socio' => 'star', 'cultural' => 'star', 'governance' => 'briefcase', 'chapter' => 'map-pin',
          'community' => 'heart', 'fundrais' => 'wallet', 'award' => 'award', 'recognition' => 'award', 'welfare' => 'shield', 'relationship' => 'link', 'partnership' => 'globe', 'image' => 'globe'];
      foreach ($map as $k => $ic) {
          if (stripos($text, $k) !== false) {
              return $ic;
          }
      }
      return 'target';
  };
  $objTotal = count($objectives);
  ?>
  <?php if ($objTotal): ?>
  <div class="ob" id="ob" aria-label="Our objectives">
    <div class="ob-track" role="tablist" aria-label="Objectives">
      <?php foreach ($objectives as $i => $o): ?>
        <button type="button" role="tab" class="ob-step<?= $i === 0 ? ' on' : '' ?>" id="ob-t-<?= $i ?>" aria-controls="ob-p-<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-i="<?= $i ?>" style="--c:<?= $objTones[$i % 6] ?>" title="<?= e(excerpt($o, 70)) ?>">
          <?= $i + 1 ?>
        </button>
      <?php endforeach; ?>
    </div>
    <div class="ob-stage">
      <?php foreach ($objectives as $i => $o): ?>
        <article class="ob-panel<?= $i === 0 ? ' on' : '' ?>" role="tabpanel" id="ob-p-<?= $i ?>" aria-labelledby="ob-t-<?= $i ?>" style="--c:<?= $objTones[$i % 6] ?>">
          <span class="ob-big"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="ob-medal"><?= icon($objIcon($o), 34) ?></span>
          <div class="ob-body">
            <span class="ob-count">Objective <?= $i + 1 ?> of <?= $objTotal ?></span>
            <p><?= e($o) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
      <div class="ob-nav">
        <button type="button" class="ob-prev" aria-label="Previous objective">&larr;</button>
        <div class="ob-bar" aria-hidden="true"><i></i></div>
        <button type="button" class="ob-next" aria-label="Next objective">&rarr;</button>
      </div>
    </div>
  </div>
  <script>
  (function () {
    var box = document.getElementById('ob'); if (!box) return;
    var steps = [].slice.call(box.querySelectorAll('.ob-step')), panels = [].slice.call(box.querySelectorAll('.ob-panel')), bar = box.querySelector('.ob-bar i');
    var cur = 0, timer = null, touched = false, reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function show(i) {
      cur = (i + steps.length) % steps.length;
      steps.forEach(function (s, k) { s.classList.toggle('on', k === cur); s.classList.toggle('done', k < cur); s.setAttribute('aria-selected', k === cur ? 'true' : 'false'); });
      panels.forEach(function (p, k) { p.classList.toggle('on', k === cur); });
      box.style.setProperty('--cur', steps[cur].style.getPropertyValue('--c'));
      var track = box.querySelector('.ob-track'), s = steps[cur];
      if (track.scrollWidth > track.clientWidth) { track.scrollTo({ left: s.offsetLeft - track.clientWidth / 2 + s.offsetWidth / 2, behavior: reduce ? 'auto' : 'smooth' }); }
      if (bar) { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = touched || reduce ? 'none' : ''; bar.style.width = touched || reduce ? ((cur + 1) / steps.length * 100) + '%' : ''; }
    }
    function stop() { touched = true; clearInterval(timer); }
    steps.forEach(function (s, k) {
      s.addEventListener('click', function () { stop(); show(k); });
      s.addEventListener('mouseenter', function () { if (window.matchMedia('(hover: hover)').matches) { stop(); show(k); } });
      s.addEventListener('keydown', function (e) {
        var d = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : (e.key === 'ArrowLeft' || e.key === 'ArrowUp' ? -1 : 0);
        if (d) { e.preventDefault(); stop(); show(k + d); steps[cur].focus(); }
      });
    });
    box.querySelector('.ob-prev').addEventListener('click', function () { stop(); show(cur - 1); });
    box.querySelector('.ob-next').addEventListener('click', function () { stop(); show(cur + 1); });
    show(0);
    if (!reduce) { timer = setInterval(function () { show(cur + 1); }, 6000); }
  })();
  </script>
  <?php endif; ?>
</section>

<?php
$mandateText = trim(str_replace('**', '', strip_tags(c('about.mandate'))));
$mandateLines = array_values(array_filter(array_map('trim', preg_split('/(?<=[.!?])\s+/', $mandateText))));
$constRes = q("SELECT id FROM resources WHERE category = 'Constitution' AND is_public = 1 AND member_only = 0 AND (filename IS NOT NULL OR external_url IS NOT NULL) ORDER BY id LIMIT 1")->fetchColumn();
$constHref = $constRes ? url('download.php?id=' . (int) $constRes) : url('resources.php?q=constitution');
$constStats = array_slice(c_pairs('home.stats'), 0, 4);
?>
<section class="section fd" id="foundation" aria-label="Our mandate and the Constitution">
  <div class="sec-head"><div><span class="eyebrow-s">Our foundation</span><h2>Mandate and Constitution</h2></div></div>
  <div class="fd-grid">
    <article class="fd-card fd-mandate" data-tilt>
      <span class="fd-glare" aria-hidden="true"></span>
      <span class="fd-ic"><?= icon('shield', 28) ?></span>
      <h3>Our mandate</h3>
      <ol class="fd-lines">
        <?php foreach ($mandateLines as $i => $line): ?>
          <li style="--d:<?= $i * 110 ?>ms"><span class="fd-tick"><?= icon('check', 14) ?></span><span><?= e($line) ?></span></li>
        <?php endforeach; ?>
      </ol>
      <div class="fd-actions fd-foot">
        <a class="btn" href="#objectives">See our objectives</a>
        <a class="btn secondary" href="<?= url('register.php') ?>">Join GUCO</a>
      </div>
    </article>
    <article class="fd-card fd-const" data-tilt>
      <span class="fd-glare" aria-hidden="true"></span>
      <div class="fd-doc" aria-hidden="true"><i></i><i></i><b>THE CONSTITUTION</b><small>Gulu University Convocation</small><em>2023</em></div>
      <div class="fd-const-body">
        <span class="fd-ic gold"><?= icon('book', 26) ?></span>
        <h3>The Constitution</h3>
        <p><?= e(trim(str_replace('**', '', strip_tags(c('about.constitution'))))) ?></p>
        <?php if ($constStats): ?>
          <div class="fd-stats">
            <?php foreach ($constStats as [$v, $l]): ?><div><b data-count="<?= e($v) ?>"><?= e($v) ?></b><span><?= e($l) ?></span></div><?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="fd-actions">
          <a class="btn gold" href="<?= e($constHref) ?>"<?= $constRes ? ' target="_blank" rel="noopener"' : '' ?>><?= icon('book', 16) ?> Read the Constitution (2023)</a>
          <a class="btn outline" href="<?= url('resources.php') ?>">All official documents</a>
        </div>
      </div>
    </article>
  </div>
</section>
<script>
(function () {
  var sec = document.getElementById('foundation'); if (!sec) return;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  if (fine && !reduce) {
    [].slice.call(sec.querySelectorAll('[data-tilt]')).forEach(function (c) {
      c.addEventListener('mousemove', function (e) {
        var r = c.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
        c.style.setProperty('--ry', ((x - .5) * 7).toFixed(2) + 'deg'); c.style.setProperty('--rx', ((.5 - y) * 7).toFixed(2) + 'deg');
        c.style.setProperty('--gx', (x * 100).toFixed(1) + '%'); c.style.setProperty('--gy', (y * 100).toFixed(1) + '%');
      });
      c.addEventListener('mouseleave', function () { c.style.setProperty('--rx', '0deg'); c.style.setProperty('--ry', '0deg'); });
    });
  }
  function run() {
    sec.classList.add('in');
    [].slice.call(sec.querySelectorAll('[data-count]')).forEach(function (el) {
      var raw = el.getAttribute('data-count'), m = raw.match(/^(\D*)(\d[\d,]*)(\D*)$/);
      if (!m || reduce) return;
      var num = parseInt(m[2].replace(/,/g, ''), 10);
      if (num >= 1900 && num <= 2100) return;
      var target = parseInt(m[2].replace(/,/g, ''), 10), start = null;
      function step(t) {
        if (start === null) start = t;
        var p = Math.min(1, (t - start) / 1100), v = Math.round(target * (1 - Math.pow(1 - p, 3)));
        el.textContent = m[1] + v + m[3];
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
      setTimeout(function () { el.textContent = raw; }, 1600);
    });
  }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) { if (es[0].isIntersecting) { run(); io.disconnect(); } }, { threshold: .25 });
    io.observe(sec);
  } else { run(); }
})();
</script>

<section class="cta">
  <h2 style="margin-top:0">Be part of the story</h2>
  <p>Join the Convocation, take part in chapters and projects, and help build a stronger Gulu University.</p>
  <a class="btn" href="<?= url('register.php') ?>">JOIN GUCO</a>
  <a class="btn secondary" href="<?= url('membership.php') ?>">Membership benefits</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
