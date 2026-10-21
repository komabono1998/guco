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
<?php require __DIR__ . '/includes/footer.php'; ?>
