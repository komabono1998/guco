<?php
$pageTitle = 'Programmes & Impact';
$pageDesc = 'GUCO programme areas: alumni networking, mentorship, university development, community service, student support, awards and partnerships.';
require __DIR__ . '/includes/header.php';
$reports = q('SELECT * FROM programmes WHERE is_public = 1 ORDER BY id DESC')->fetchAll();
$areaPhoto = ['Alumni Networking and Engagement' => 7, 'Awards and Recognition' => 4, 'Partnerships and Fundraising' => 3];
?>
<div class="page-head"><h1>Programmes &amp; Impact</h1><p>Our constitutional objectives in action</p></div>
<div class="lead"><?= cr('programmes.intro') ?></div>
<?php
$areas = c_pairs('programmes.areas');
$n = max(1, count($areas));
$icons = ['users', 'target', 'graduation', 'heart', 'award', 'globe', 'book'];
$pick = function (string $title, int $i) use ($icons): string {
    $map = ['network' => 'users', 'mentor' => 'target', 'universit' => 'graduation', 'academic' => 'graduation', 'community' => 'heart', 'student' => 'book', 'scholar' => 'book', 'award' => 'award', 'partner' => 'globe', 'fundrais' => 'globe'];
    foreach ($map as $k => $ic) {
        if (stripos($title, $k) !== false) {
            return $ic;
        }
    }
    return $icons[$i % count($icons)];
};
$tones = ['#14532d', '#b45309', '#1d4ed8', '#be123c', '#0f766e', '#7e22ce', '#c2410c'];
?>
<section class="pw" aria-label="GUCO programme areas">
  <div class="pw-wheel" id="pw" style="--n:<?= $n ?>">
    <?php foreach ($areas as $i => [$t, $d]): $a = round(-90 + $i * 360 / $n, 2); ?>
      <i class="pw-spoke" data-i="<?= $i ?>" style="--a:<?= $a ?>deg;--c:<?= $tones[$i % count($tones)] ?>"></i>
    <?php endforeach; ?>
    <div class="pw-hub" aria-live="polite">
      <div class="pw-hub-default">
        <img src="<?= e(logo_url()) ?>" alt="" width="64" height="64">
        <strong>What GUCO does</strong>
        <span>Hover over or tap a circle to explore a programme area.</span>
      </div>
      <?php foreach ($areas as $i => [$t, $d]): ?>
        <div class="pw-hub-item" data-i="<?= $i ?>" style="--c:<?= $tones[$i % count($tones)] ?>">
          <span class="pw-hub-ic"><?= icon($pick($t, $i), 30) ?></span>
          <strong><?= e($t) ?></strong>
          <span><?= e($d) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php foreach ($areas as $i => [$t, $d]): $a = round(-90 + $i * 360 / $n, 2); ?>
      <button type="button" class="pw-node" data-i="<?= $i ?>" style="--a:<?= $a ?>deg;--c:<?= $tones[$i % count($tones)] ?>" aria-label="<?= e($t) ?>">
        <span class="pw-ic"><?= icon($pick($t, $i), 30) ?></span>
        <span class="pw-label"><?= e($t) ?></span>
      </button>
    <?php endforeach; ?>
  </div>
</section>
<script>
(function () {
  var wheel = document.getElementById('pw'); if (!wheel) return;
  var nodes = [].slice.call(wheel.querySelectorAll('.pw-node')), items = [].slice.call(wheel.querySelectorAll('.pw-hub-item')), spokes = [].slice.call(wheel.querySelectorAll('.pw-spoke'));
  var cur = -1, timer = null, touched = false;
  function show(i) {
    cur = i;
    wheel.classList.toggle('has-active', i >= 0);
    nodes.forEach(function (n, k) { n.classList.toggle('on', k === i); n.setAttribute('aria-pressed', k === i ? 'true' : 'false'); });
    items.forEach(function (n, k) { n.classList.toggle('on', k === i); });
    spokes.forEach(function (n, k) { n.classList.toggle('on', k === i); });
  }
  function auto() {
    if (touched || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    timer = setInterval(function () { show((cur + 1) % nodes.length); }, 3800);
  }
  function stop() { touched = true; clearInterval(timer); }
  nodes.forEach(function (n, k) {
    n.addEventListener('mouseenter', function () { stop(); show(k); });
    n.addEventListener('focus', function () { stop(); show(k); });
    n.addEventListener('click', function () { stop(); show(k); });
  });
  show(0); auto();
})();
</script>

<section class="section split">
  <div>
    <h2 style="margin-top:0">Member-led community projects</h2>
    <p>Members can propose projects. The Executive Committee reviews each proposal and, once it is approved, the member who proposed it leads implementation and mobilises resources with GUCO's support.</p>
    <p><a class="btn" href="<?= url('projects.php') ?>">See community projects</a> <a class="btn secondary" href="<?= url('member/project_form.php') ?>">Propose a project</a></p>
  </div>
  <div class="card accent"><strong>How it works</strong><ol style="margin:.5em 0 0;padding-left:1.2em"><li>Propose with a workplan and budget</li><li>Executive review</li><li>Approval and publication</li><li>Proposer leads and mobilises resources</li></ol></div>
</section>

<section class="section">
  <h2>Impact reports</h2>
  <?php foreach ($reports as $p): ?>
    <article class="card" style="margin-bottom:16px">
      <h3><?= e($p['title']) ?></h3>
      <?php if ($p['area']): ?><span class="badge"><?= e($p['area']) ?></span><?php endif; ?>
      <dl>
        <?php foreach (['date_location' => 'Date and location', 'objective' => 'Objective', 'target_group' => 'Target group', 'reach' => 'Number reached', 'partners' => 'Partners and contributors', 'outcomes' => 'Outputs and outcomes', 'next_steps' => 'Next steps'] as $k => $label):
            if ($p[$k]): ?><dt><strong><?= e($label) ?></strong></dt><dd><?= nl2br(e($p[$k])) ?></dd><?php endif; endforeach; ?>
      </dl>
    </article>
  <?php endforeach; if (!$reports): ?><p>Approved programme reports will be published here.</p><?php endif; ?>
</section>
<?php related_links('Get involved', 'More ways to take part', [
    ['projects.php', 'target', 'Community Projects', 'Member-led projects in action'],
    ['awards.php', 'award', 'Awards and Hall of Fame', 'Honouring outstanding alumni'],
    ['partnerships.php', 'link', 'Partnerships and Support', 'Collaborate with or support GUCO'],
    ['chapters.php', 'map-pin', 'Alumni Chapters', 'Programmes close to where you live'],
]); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
