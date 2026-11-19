<?php
$pageTitle = 'Membership';
$pageDesc = 'Founder, Ordinary, Honorary and Life membership of Gulu University Convocation: eligibility, rights, obligations and how to join.';
require __DIR__ . '/includes/header.php';
$showFees = setting('show_fees') === '1';
$open = setting('applications_open') === '1';
$fmt = fn($k) => 'UGX ' . number_format((int) setting($k));
$joinUrl = fn(string $cat = '') => url('register.php' . ($cat !== '' ? '?category=' . $cat : ''));
// Wording comes from Admin > Site content > Membership; icons, colours and fees stay fixed.
$catMeta = [['founder', 'star', '#b45309'], ['ordinary', 'users', '#14532d'], ['honorary', 'heart', '#be123c'], ['life', 'gem', '#1d4ed8']];
$catFees = [
    'founder' => $showFees ? 'One-time fee of ' . $fmt('fee_founder') : '',
    'ordinary' => $showFees ? 'Annual subscription of ' . $fmt('fee_ordinary') : '',
    'honorary' => $showFees ? 'One-time fee of ' . $fmt('fee_honorary') . '; corporate organisations have an additional listed fee.' : '',
    'life' => $showFees ? 'One-time fee of ' . $fmt('fee_life') : '',
];
$catText = c_pairs('membership.categories');
$categories = [];
foreach ($catMeta as $ci => [$ck, $cic, $ctone]) {
    if (isset($catText[$ci])) {
        $categories[] = [$ck, $catText[$ci][0], $cic, $ctone, $catText[$ci][1], $catFees[$ck]];
    }
}
$stepIcons = ['user-plus', 'credit-card', 'shield', 'id-card'];
$steps = [];
foreach (c_pairs('membership.steps') as $si => [$st, $sd]) {
    $steps[] = [$stepIcons[$si % 4], $st, $sd];
}
$checklist = c_lines('membership.checklist');
$factIcons = ['users', 'id-card', 'user-plus', 'shield'];
$facts = array_slice(c_pairs('membership.facts'), 0, 4);
?>
<div class="mb">

<section class="mb-hero">
  <div class="mb-hero-text">
    <span class="mb-eyebrow"><?= ct('membership.hero_eyebrow') ?></span>
    <h1>Membership</h1>
    <p class="mb-sub"><?= ct('membership.hero_sub') ?></p>
    <div class="mb-intro"><?= cr('membership.intro') ?></div>
    <div class="mb-cta">
      <?php if ($open): ?><a class="btn gold" href="<?= $joinUrl() ?>">Join GUCO</a><?php else: ?><a class="btn gold" href="<?= url('contact.php?category=Membership') ?>">Contact the Secretariat</a><?php endif; ?>
      <a class="btn outline" href="#how">How it works</a>
      <a class="btn outline" href="#verify">Verify a card</a>
    </div>
  </div>
  <div class="mb-hero-card" aria-hidden="true">
    <div class="mb-idcard" data-tilt-mb>
      <div class="mb-id-top"><img src="<?= e(logo_url()) ?>" alt="" width="40" height="40"><div><b>GUCO</b><small>Gulu University Convocation</small></div></div>
      <div class="mb-id-mid"><div class="mb-id-photo"><?= icon('user', 34) ?></div><div><strong>Your name here</strong><span>Ordinary Member</span><em>GUCO/ORD/2026/000001</em></div></div>
      <div class="mb-id-bottom"><div class="mb-qr"><?php for ($i = 0; $i < 49; $i++): ?><i<?= in_array($i, [0, 1, 2, 7, 9, 14, 15, 16, 4, 5, 6, 11, 13, 18, 20, 22, 25, 26, 29, 31, 34, 36, 38, 40, 42, 45, 46, 47, 48, 33, 27, 12, 8], true) ? ' class="on"' : '' ?>></i><?php endfor; ?></div><small>Scan to verify<br>Sample card</small></div>
    </div>
  </div>
</section>

<ul class="mb-facts">
  <?php foreach ($facts as $fi => [$fv, $fl]): ?><li><?= icon($factIcons[$fi % 4], 22) ?><b><?= e($fv) ?></b><span><?= e($fl) ?></span></li><?php endforeach; ?>
</ul>

<section id="how" class="mb-section">
  <div class="mb-head"><span class="mb-eyebrow">Four simple steps</span><h2>How membership works</h2></div>
  <ol class="mb-steps">
    <?php foreach ($steps as $i => [$ic, $t, $d]): ?>
      <li style="--d:<?= $i * 120 ?>ms">
        <span class="mb-step-n"><?= $i + 1 ?></span>
        <span class="mb-step-ic"><?= icon($ic, 28) ?></span>
        <strong><?= e($t) ?></strong>
        <p><?= e($d) ?></p>
      </li>
    <?php endforeach; ?>
  </ol>
  <div class="mb-note"><?= cr('membership.renew_note') ?></div>
</section>

<section id="categories" class="mb-section">
  <div class="mb-head"><span class="mb-eyebrow">Find your place</span><h2>Membership categories</h2></div>
  <div class="mb-cats">
    <?php foreach ($categories as $i => [$key, $name, $ic, $tone, $who, $fee]): ?>
      <article class="mb-cat" style="--c:<?= $tone ?>;--d:<?= $i * 100 ?>ms">
        <span class="mb-cat-ic"><?= icon($ic, 30) ?></span>
        <h3><?= e($name) ?></h3>
        <p class="mb-cat-label">Who may qualify</p>
        <p><?= e($who) ?></p>
        <?php if ($fee): ?><span class="mb-fee"><?= e($fee) ?></span><?php endif; ?>
        <?php if ($open): ?><a class="mb-cat-link" href="<?= $joinUrl($key) ?>">Apply as <?= e(explode(' /', $name)[0]) ?> &rarr;</a><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
  <?php if (!$showFees): ?><p class="notice">Membership fees are confirmed by the Secretariat. Please <a href="<?= url('contact.php') ?>">contact the Secretariat</a> for the current applicable fees and approved payment procedure before making any payment.</p><?php endif; ?>
</section>

<section class="mb-section mb-elig">
  <div>
    <div class="mb-head"><span class="mb-eyebrow">Before you apply</span><h2>Eligibility and registration</h2></div>
    <div class="mb-prose"><?= cr('membership.eligibility') ?></div>
  </div>
  <aside class="mb-check" id="mbCheck">
    <h3>Your application checklist</h3>
    <p class="mb-check-hint">Tick each step as you go. Your progress stays on this page only.</p>
    <div class="mb-meter" aria-hidden="true"><i></i></div>
    <p class="mb-meter-label"><b id="mbDone">0</b> of <?= count($checklist) ?> steps</p>
    <ul>
      <?php foreach ($checklist as $i => $item): ?>
        <li><label><input type="checkbox"><span class="mb-box"><?= icon('check', 14) ?></span><span><?= e($item) ?></span></label></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($open): ?><a class="btn" href="<?= $joinUrl() ?>">Start your application</a><?php else: ?><p class="notice">Online applications are not open at the moment. Please contact the Secretariat.</p><?php endif; ?>
  </aside>
</section>

<section class="mb-section">
  <div class="mb-head"><span class="mb-eyebrow">What membership means</span><h2>Rights and obligations</h2></div>
  <div class="mb-seg" role="tablist" aria-label="Rights or obligations">
    <button type="button" role="tab" class="on" data-p="rights" aria-selected="true">Rights of members</button>
    <button type="button" role="tab" data-p="obl" aria-selected="false">Obligations of members</button>
  </div>
  <div class="mb-ro">
    <ul class="mb-list on" id="mb-p-rights" role="tabpanel">
      <?php foreach (c_lines('membership.rights') as $i => $l): ?><li style="--d:<?= $i * 70 ?>ms"><span class="mb-tick"><?= icon('check', 14) ?></span><span><?= e($l) ?></span></li><?php endforeach; ?>
    </ul>
    <ul class="mb-list" id="mb-p-obl" role="tabpanel">
      <?php foreach (c_lines('membership.obligations') as $i => $l): ?><li style="--d:<?= $i * 70 ?>ms"><span class="mb-tick"><?= icon('check', 14) ?></span><span><?= e($l) ?></span></li><?php endforeach; ?>
    </ul>
  </div>
</section>

<section id="benefits" class="mb-section">
  <div class="mb-benefits">
    <span class="mb-eyebrow light">Why join</span>
    <h2>Membership benefits</h2>
    <div class="mb-prose light"><?= cr('membership.benefits') ?></div>
  </div>
</section>

<section id="verify" class="mb-section">
  <div class="mb-verify">
    <div><span class="mb-eyebrow">Trust and transparency</span><h2>Verify a membership card</h2><p><?= ct('membership.verify_text') ?></p></div>
    <form method="get" action="<?= url('verify.php') ?>">
      <label>Membership ID <input name="id" placeholder="GUCO/ORD/2026/000001" required></label>
      <button><?= icon('search', 16) ?> Verify card</button>
    </form>
  </div>
</section>

<section id="faq" class="mb-section">
  <div class="mb-head"><span class="mb-eyebrow">Quick answers</span><h2>Membership FAQ</h2></div>
  <div class="mb-faq-search"><?= icon('search', 18) ?><input type="search" id="mbFaqQ" placeholder="Search the questions..." aria-label="Search the membership questions"></div>
  <div id="mbFaq">
    <?php foreach (c_pairs('membership.faq') as [$qn, $an]): ?><details class="mb-q"><summary><?= e($qn) ?></summary><div><?= render_text($an) ?></div></details><?php endforeach; ?>
    <p class="mb-empty" hidden>No question matches your search. <a href="<?= url('contact.php?category=Membership') ?>">Ask the Secretariat</a>.</p>
  </div>
</section>

<section class="mb-final">
  <div><h2><?= ct('membership.final_title') ?></h2><p><?= ct('membership.final_text') ?></p></div>
  <div class="mb-final-btns">
    <?php if ($open): ?><a class="btn gold" href="<?= $joinUrl() ?>">Join GUCO</a><?php endif; ?>
    <a class="btn outline" href="<?= url('contact.php?category=Membership') ?>">Ask a question</a>
  </div>
</section>

</div>
<script>
(function () {
  var root = document.querySelector('.mb'); if (!root) return;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  /* reveal on scroll */
  var items = [].slice.call(root.querySelectorAll('.mb-section, .mb-steps li, .mb-cat, .mb-list li, .mb-facts li, .mb-final'));
  items.forEach(function (el) { el.classList.add('mb-rv'); });
  if ('IntersectionObserver' in window && !reduce) {
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }); }, { threshold: .12 });
    items.forEach(function (el) { io.observe(el); });
  } else { items.forEach(function (el) { el.classList.add('in'); }); }
  /* card tilt */
  var card = root.querySelector('[data-tilt-mb]');
  if (card && !reduce && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    var hero = root.querySelector('.mb-hero');
    hero.addEventListener('mousemove', function (e) {
      var r = hero.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      card.style.transform = 'rotateY(' + (x * 16) + 'deg) rotateX(' + (-y * 12) + 'deg) rotateZ(-2deg)';
    });
    hero.addEventListener('mouseleave', function () { card.style.transform = ''; });
  }
  /* checklist */
  var boxes = [].slice.call(root.querySelectorAll('.mb-check input')), bar = root.querySelector('.mb-meter i'), cnt = document.getElementById('mbDone');
  function upd() { var n = boxes.filter(function (b) { return b.checked; }).length; cnt.textContent = n; bar.style.width = (n / boxes.length * 100) + '%'; root.querySelector('.mb-check').classList.toggle('all', n === boxes.length); }
  boxes.forEach(function (b) { b.addEventListener('change', upd); });
  /* rights / obligations switch */
  [].slice.call(root.querySelectorAll('.mb-seg button')).forEach(function (b) {
    b.addEventListener('click', function () {
      [].slice.call(root.querySelectorAll('.mb-seg button')).forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
      [].slice.call(root.querySelectorAll('.mb-list')).forEach(function (l) { l.classList.toggle('on', l.id === 'mb-p-' + b.getAttribute('data-p')); });
    });
  });
  /* FAQ search, one open at a time */
  var qs = [].slice.call(root.querySelectorAll('.mb-q')), input = document.getElementById('mbFaqQ'), empty = root.querySelector('.mb-empty');
  qs.forEach(function (d) { d.addEventListener('toggle', function () { if (d.open) qs.forEach(function (o) { if (o !== d) o.open = false; }); }); });
  input.addEventListener('input', function () {
    var v = input.value.toLowerCase().trim(), shown = 0;
    qs.forEach(function (d) { var ok = !v || d.textContent.toLowerCase().indexOf(v) > -1; d.hidden = !ok; if (ok) shown++; if (v && ok && shown === 1) d.open = true; });
    empty.hidden = shown > 0;
  });
})();
</script>
<?php related_links('Next steps', 'More for members', [
    ['chapters.php', 'map-pin', 'Alumni Chapters', 'Join the chapter for your region'],
    ['careers.php', 'briefcase', 'Scholarships, Jobs and Funding', 'Study, fellowship, grant and job opportunities'],
    ['member/login.php', 'id-card', 'Member Portal', 'Your card, payments and profile'],
    ['donate.php', 'heart', 'Give', 'Support students and community projects'],
]); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
