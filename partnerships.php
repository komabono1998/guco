<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/forms.php';
$pageTitle = 'Partnerships & Support';
$pageDesc = 'Partner with or support Gulu University Convocation: collaboration, sponsorship, mentorship and approved initiatives.';
handle_enquiry_post('partnerships.php');
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Partnerships &amp; Support</h1><p>Work with GUCO</p></div>
<div class="lead"><?= cr('partnerships.intro') ?></div>

<section class="section">
  <?= dinner_figure(14, '', DINNER_ALBUM . ': celebrating excellence, strengthening partnerships and investing in the future of Gulu University') ?>
</section>

<?php
$ptWays = c_lines('partnerships.ways');
$ptTones = ['#14532d', '#1d4ed8', '#be123c', '#0f766e', '#b45309', '#7e22ce'];
$ptIcon = function (string $text): string {
    $map = ['institution' => 'briefcase', 'corporate' => 'briefcase', 'mentor' => 'target', 'knowledge' => 'target', 'student' => 'heart', 'community' => 'heart', 'research' => 'graduation', 'academic' => 'graduation', 'sponsor' => 'award', 'recognition' => 'award', 'fundrais' => 'wallet', 'in-kind' => 'wallet'];
    foreach ($map as $k => $ic) {
        if (stripos($text, $k) !== false) {
            return $ic;
        }
    }
    return 'link';
};
?>
<section class="section pt" id="pt">
  <div class="pt-head"><span class="eyebrow-s">Work with GUCO</span><h2><?= ct('partnerships.ways_title') ?></h2><p><?= ct('partnerships.ways_intro') ?></p></div>
  <div class="pt-grid">
    <?php foreach ($ptWays as $i => $w): ?>
      <button type="button" class="pt-tile" aria-pressed="false" data-way="<?= e(rtrim($w, '.')) ?>" style="--c:<?= $ptTones[$i % 6] ?>;--d:<?= $i * 70 ?>ms">
        <span class="pt-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
        <span class="pt-ic"><?= icon($ptIcon($w), 30) ?></span>
        <span class="pt-text"><?= e($w) ?></span>
        <span class="pt-check" aria-hidden="true"><?= icon('check', 16) ?></span>
      </button>
    <?php endforeach; ?>
  </div>
  <div class="pt-bar" id="ptBar" hidden>
    <div><strong id="ptCount">0</strong> <span id="ptWord">area selected</span><small id="ptList"></small></div>
    <div class="pt-bar-btns"><button type="button" class="btn secondary" id="ptClear">Clear</button><a class="btn" href="#partner-form" id="ptGo">Start the conversation &darr;</a></div>
  </div>
</section>

<section class="section pt-support">
  <div class="pt-support-card">
    <span class="pt-support-ic"><?= icon('heart', 34) ?></span>
    <div class="pt-support-body"><h2>Support GUCO</h2><?= cr('partnerships.support') ?></div>
    <div class="pt-support-btns">
      <a class="btn gold" href="<?= url('donate.php') ?>"><?= icon('heart', 16) ?> Give now</a>
      <a class="btn outline" href="#partner-form">Contact the Secretariat</a>
    </div>
  </div>
</section>

<section class="section" id="partner-form">
  <h2>Discuss a partnership or enquire about support</h2>
  <?php enquiry_form(in_array($_GET['category'] ?? '', ['Partnerships', 'Support GUCO'], true) ? $_GET['category'] : 'Partnerships'); ?>
</section>
<script>
(function () {
  var box = document.getElementById('pt'); if (!box) return;
  var tiles = [].slice.call(box.querySelectorAll('.pt-tile')), bar = document.getElementById('ptBar');
  var count = document.getElementById('ptCount'), word = document.getElementById('ptWord'), list = document.getElementById('ptList');
  function picked() { return tiles.filter(function (t) { return t.classList.contains('on'); }).map(function (t) { return t.getAttribute('data-way'); }); }
  function refresh() {
    var p = picked();
    bar.hidden = p.length === 0;
    count.textContent = p.length;
    word.textContent = p.length === 1 ? 'area selected' : 'areas selected';
    list.textContent = p.join(' · ');
  }
  tiles.forEach(function (t) {
    t.addEventListener('click', function () {
      var on = t.classList.toggle('on');
      t.setAttribute('aria-pressed', on ? 'true' : 'false');
      refresh();
    });
  });
  document.getElementById('ptClear').addEventListener('click', function () {
    tiles.forEach(function (t) { t.classList.remove('on'); t.setAttribute('aria-pressed', 'false'); });
    refresh();
  });
  document.getElementById('ptGo').addEventListener('click', function () {
    var p = picked(), form = document.getElementById('partner-form');
    if (!form) return;
    var subject = form.querySelector('input[name="subject"]'), body = form.querySelector('textarea[name="body"]');
    if (subject && !subject.value) subject.value = 'Partnership enquiry: ' + (p.length > 1 ? p.length + ' areas of collaboration' : p[0]);
    if (body && !body.value) body.value = 'We are interested in collaborating with GUCO on:\n- ' + p.join('\n- ') + '\n\nPlease tell us how to take this forward.';
  });
  var hash = location.hash;
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
