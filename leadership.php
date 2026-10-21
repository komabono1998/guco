<?php
$pageTitle = 'Leadership & Governance';
$pageDesc = 'The governance structure of GUCO: General Assembly, Board, Executive Committee, Disciplinary Committee and elections.';
require __DIR__ . '/includes/header.php';
$byOrgan = [];
foreach (q('SELECT * FROM leaders WHERE is_public = 1 ORDER BY sort_order, id')->fetchAll() as $l) {
    $byOrgan[$l['organ']][] = $l;
}

/** Cards for one governing body; unfilled positions show a placeholder. */
$people = function (string $organ) use ($byOrgan) {
    if (empty($byOrgan[$organ])) {
        return;
    }
    echo '<div class="people">';
    foreach ($byOrgan[$organ] as $l) {
        $filled = trim($l['name']) !== '';
        $tag = $filled ? 'a' : 'div';
        echo '<' . $tag . ' class="card person' . ($filled ? '' : ' vacant') . '"' . ($filled ? ' href="' . e(url('leader.php?id=' . (int) $l['id'])) . '"' : '') . '>';
        echo leader_photo($l);
        echo '<strong>' . e($filled ? $l['name'] : 'To be announced') . '</strong>';
        echo '<span class="role">' . e($l['title']) . '</span>';
        if ($l['term'] && $filled) {
            echo '<span class="meta">' . e($l['term']) . '</span>';
        }
        if ($l['role_description']) {
            echo '<span class="duty">' . e(excerpt($l['role_description'], 110)) . '</span>';
        }
        if ($filled) {
            echo '<span class="more">View profile &rarr;</span>';
        }
        echo '</' . $tag . '>';
    }
    echo '</div>';
};
?>
<div class="page-head"><h1>Leadership &amp; Governance</h1><p>How GUCO is governed</p></div>

<section class="section">
  <h2>Governance structure</h2>
  <?= cr('leadership.intro') ?>
</section>

<section id="assembly" class="section">
  <h2>General Assembly</h2>
  <?= cr('leadership.assembly') ?>
  <?php $people('patron'); ?>
</section>

<section id="board" class="section">
  <h2>The Board</h2>
  <?= cr('leadership.board') ?>
  <?php $people('board'); ?>
</section>

<section id="executive" class="section">
  <h2>Executive Committee</h2>
  <?= cr('leadership.executive') ?>
  <?php $people('executive'); ?>
</section>

<section id="disciplinary" class="section">
  <h2>Disciplinary Committee</h2>
  <?= cr('leadership.disciplinary') ?>
  <?php $people('disciplinary'); ?>
</section>

<section id="elections" class="section">
  <h2>Elections and accountability</h2>
  <?= cr('leadership.elections') ?>
  <p><a href="<?= url('news.php?category=Notices') ?>">View official notices &rarr;</a></p>
</section>

<div class="notice"><strong>Website governance notice:</strong> <?= ct('leadership.notice') ?></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
