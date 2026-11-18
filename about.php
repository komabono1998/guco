<?php
$pageTitle = 'About GUCO';
$pageDesc = 'Overview, vision, mission, core values and objectives of Gulu University Convocation (GUCO).';
require __DIR__ . '/includes/header.php';
$img = c_image('about.image');
$valueIcons = ['shield', 'users', 'graduation', 'link', 'star', 'heart'];
$objectives = c_lines('about.objectives');
?>
<div class="page-head"><h1>About GUCO</h1><p><?= e(setting('site_tagline', 'Community Transformation Actualized')) ?></p></div>

<section id="overview" class="section split">
  <div>
    <span class="eyebrow-s">Who we are</span>
    <h2 style="margin-top:4px">Overview</h2>
    <?= cr('about.overview') ?>
    <div class="fact-row">
      <div><b>2023</b><span>Constitution in force</span></div>
      <div><b>4</b><span>Membership categories</span></div>
      <div><b>6</b><span>Core values</span></div>
      <div><b><?= count($objectives) ?></b><span>Objectives</span></div>
    </div>
  </div>
  <?php if ($img): ?><figure class="photo"><img src="<?= e($img) ?>" alt="GUCO" loading="lazy"></figure><?php else: ?><?= dinner_figure(19, '', DINNER_ALBUM) ?><?php endif; ?>
</section>

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

<section class="section">
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

<section class="section split">
  <div class="card accent mandate">
    <span class="p-ic dark"><?= icon('shield', 26) ?></span>
    <h2 style="margin-top:8px">Our mandate</h2>
    <?= cr('about.mandate') ?>
  </div>
  <div class="constitution-card">
    <span class="p-ic"><?= icon('book', 28) ?></span>
    <h2>The Constitution</h2>
    <?= cr('about.constitution') ?>
    <a class="btn gold" href="<?= url('resources.php?q=constitution') ?>">READ THE GUCO CONSTITUTION (2023)</a>
  </div>
</section>

<section class="cta">
  <h2 style="margin-top:0">Be part of the story</h2>
  <p>Join the Convocation, take part in chapters and projects, and help build a stronger Gulu University.</p>
  <a class="btn" href="<?= url('register.php') ?>">JOIN GUCO</a>
  <a class="btn secondary" href="<?= url('membership.php') ?>">Membership benefits</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
