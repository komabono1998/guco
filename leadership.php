<?php
$pageTitle = 'Leadership & Governance';
$pageDesc = 'The governance structure of GUCO: General Assembly, Board, Executive Committee, Disciplinary Committee and elections.';
require __DIR__ . '/includes/header.php';

$byOrgan = [];
foreach (q('SELECT * FROM leaders WHERE is_public = 1 ORDER BY sort_order, id')->fetchAll() as $l) {
    $byOrgan[$l['organ']][] = $l;
}

// organ => [anchor, name, role line, article, icon, tone, content block]
$organs = [
    'patron' => ['assembly', 'General Assembly', 'Supreme organ', 'Article 10', 'users', 'gold', 'leadership.assembly'],
    'board' => ['board', 'The Board', 'Advisory organ', 'Article 12', 'briefcase', 'blue', 'leadership.board'],
    'executive' => ['executive', 'Executive Committee', 'Runs the Association', 'Article 14', 'star', 'green', 'leadership.executive'],
    'disciplinary' => ['disciplinary', 'Disciplinary Committee', 'Conduct of members', 'Article 10', 'shield', 'red', 'leadership.disciplinary'],
];
$stat = function (string $organ) use ($byOrgan) {
    $all = $byOrgan[$organ] ?? [];
    return [count(array_filter($all, fn($l) => trim($l['name']) !== '')), count($all)];
};
$totalNamed = $totalAll = 0;
foreach ($organs as $k => $_) {
    [$a, $b] = $stat($k);
    $totalNamed += $a;
    $totalAll += $b;
}

/** One person (or open position) as a card. */
$card = function (array $l, string $tone, bool $featured = false) {
    $filled = trim($l['name']) !== '';
    $tag = $filled ? 'a' : 'div';
    $h = '<' . $tag . ' class="lcard t-' . $tone . ($filled ? '' : ' vacant') . ($featured ? ' featured' : '') . '"' . ($filled ? ' href="' . e(url('leader.php?id=' . (int) $l['id'])) . '"' : '') . '>';
    $h .= '<span class="l-photo">' . leader_photo($l, $featured ? 'large' : '') . '</span>';
    $h .= '<span class="l-body"><span class="l-role">' . e($l['title']) . '</span>';
    $h .= '<strong class="l-name">' . e($filled ? $l['name'] : 'To be announced') . '</strong>';
    if ($filled && $l['term']) {
        $h .= '<span class="l-term">' . icon('clock', 13) . ' ' . e($l['term']) . '</span>';
    }
    if ($l['role_description'] && (!$filled || $featured)) {
        $h .= '<span class="l-duty">' . e(excerpt($l['role_description'], $featured ? 170 : 105)) . '</span>';
    }
    $h .= $filled ? '<span class="l-more">View profile ' . icon('arrow-right', 14) . '</span>' : '<span class="l-open">Position to be filled</span>';
    return $h . '</span></' . $tag . '>';
};
?>
<div class="gov-hero">
  <div class="gov-hero-text">
    <span class="eyebrow-s" style="color:var(--gold)">Governance</span>
    <h1>Leadership &amp; Governance</h1>
    <div class="gov-intro"><?= cr('leadership.intro') ?></div>
  </div>
  <div class="gov-count"><b><?= $totalNamed ?></b><span>of <?= $totalAll ?> positions announced</span>
    <div class="gc-bar"><i style="width:<?= $totalAll ? round($totalNamed / $totalAll * 100) : 0 ?>%"></i></div></div>
</div>

<section class="gov-diagram" aria-label="Governance structure">
  <?php $i = 0; foreach ($organs as $key => [$anchor, $name, $roleLine, $article, $ic, $tone, $block]): [$named, $all] = $stat($key); ?>
    <a class="gd gd-<?= $tone ?>" href="#<?= $anchor ?>">
      <span class="gd-ic"><?= icon($ic, 26) ?></span>
      <b><?= e($name) ?></b>
      <span class="gd-role"><?= e($roleLine) ?></span>
      <span class="gd-count"><?= $named ?> / <?= $all ?> announced</span>
    </a>
    <?php if (++$i < count($organs)): ?><span class="gd-arrow" aria-hidden="true"><?= icon('arrow-right', 22) ?></span><?php endif; ?>
  <?php endforeach; ?>
</section>

<nav class="gov-nav" aria-label="Sections">
  <?php foreach ($organs as [$anchor, $name]): ?><a href="#<?= $anchor ?>"><?= e($name) ?></a><?php endforeach; ?>
  <a href="#elections">Elections</a>
</nav>

<?php foreach ($organs as $key => [$anchor, $name, $roleLine, $article, $ic, $tone, $block]):
    $list = $byOrgan[$key] ?? [];
    [$named, $all] = $stat($key); ?>
<section id="<?= $anchor ?>" class="gov-section gs-<?= $tone ?>">
  <header class="gs-head">
    <span class="gs-ic"><?= icon($ic, 30) ?></span>
    <div>
      <span class="gs-tag"><?= e($article) ?> &middot; <?= e($roleLine) ?></span>
      <h2><?= e($name) ?></h2>
    </div>
    <span class="gs-count"><?= $named ?>/<?= $all ?><small>announced</small></span>
  </header>
  <div class="gs-text"><?= cr($block) ?></div>

  <?php if ($list): ?>
    <?php if ($key === 'executive'):
        $heads = array_values(array_filter($list, fn($l) => in_array($l['title'], ['Chairperson', 'Vice Chairperson'], true)));
        $rest = array_values(array_filter($list, fn($l) => !in_array($l['title'], ['Chairperson', 'Vice Chairperson'], true))); ?>
      <?php if ($heads): ?><div class="lgrid heads"><?php foreach ($heads as $l) echo $card($l, $tone, true); ?></div><?php endif; ?>
      <?php if ($rest): ?><h3 class="gs-sub">Office bearers</h3><div class="lgrid"><?php foreach ($rest as $l) echo $card($l, $tone); ?></div><?php endif; ?>
    <?php elseif ($key === 'patron'): ?>
      <div class="lgrid heads"><?php foreach ($list as $l) echo $card($l, $tone, true); ?></div>
    <?php else: ?>
      <div class="lgrid"><?php foreach ($list as $l) echo $card($l, $tone); ?></div>
    <?php endif; ?>
  <?php else: ?>
    <p class="meta">Members of this body will be announced after formal confirmation.</p>
  <?php endif; ?>
</section>
<?php endforeach; ?>

<section id="elections" class="gov-section gs-purple">
  <header class="gs-head">
    <span class="gs-ic"><?= icon('check-circle', 30) ?></span>
    <div><span class="gs-tag">Article 17 &middot; Accountability</span><h2>Elections and accountability</h2></div>
  </header>
  <div class="gs-text"><?= cr('leadership.elections') ?></div>
  <div class="facts">
    <div class="fact"><span><?= icon('clock', 24) ?></span><b>4-year term</b><p>Executive Committee office bearers, with one further term in the same office.</p></div>
    <div class="fact"><span><?= icon('check-circle', 24) ?></span><b>Secret ballot</b><p>Eligible fully paid-up members vote in person at the General Assembly.</p></div>
    <div class="fact"><span><?= icon('users', 24) ?></span><b>Gender balance</b><p>The Chairperson and Vice Chairperson are of opposite gender.</p></div>
    <div class="fact"><span><?= icon('shield', 24) ?></span><b>Right of appeal</b><p>An elections appeals process protects fairness and accountability.</p></div>
  </div>
  <p><a class="btn" href="<?= url('news.php?category=Notices') ?>">Official notices</a> <a class="btn outline" href="<?= url('events.php') ?>">AGM and events</a></p>
</section>

<div class="notice gov-notice"><strong>Website governance notice:</strong> <?= ct('leadership.notice') ?></div>

<section class="cta">
  <h2 style="margin-top:0">Get involved in the life of GUCO</h2>
  <p>Become a member, vote at the General Assembly and stand for office when you are eligible.</p>
  <a class="btn" href="<?= url('register.php') ?>">JOIN GUCO</a>
  <a class="btn secondary" href="<?= url('contact.php') ?>">Contact the Secretariat</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
