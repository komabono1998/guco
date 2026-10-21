<?php
$pageTitle = 'Programmes & Impact';
$pageDesc = 'GUCO programme areas: alumni networking, mentorship, university development, community service, student support, awards and partnerships.';
require __DIR__ . '/includes/header.php';
$reports = q('SELECT * FROM programmes WHERE is_public = 1 ORDER BY id DESC')->fetchAll();
$areaPhoto = ['Alumni Networking and Engagement' => 7, 'Awards and Recognition' => 4, 'Partnerships and Fundraising' => 3];
?>
<div class="page-head"><h1>Programmes &amp; Impact</h1><p>Our constitutional objectives in action</p></div>
<div class="lead"><?= cr('programmes.intro') ?></div>
<div class="grid">
  <?php foreach (c_pairs('programmes.areas') as [$t, $d]): ?>
    <div class="card accent"><?= isset($areaPhoto[$t]) ? dinner_figure($areaPhoto[$t]) : '' ?><h3><?= e($t) ?></h3><p><?= e($d) ?></p></div>
  <?php endforeach; ?>
</div>

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
<?php require __DIR__ . '/includes/footer.php'; ?>
