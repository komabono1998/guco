<?php
$pageTitle = 'Alumni Chapters';
$pageDesc = 'Join or establish a recognised GUCO alumni chapter by region, profession, college or graduation year.';
require __DIR__ . '/includes/header.php';
$chapters = q('SELECT * FROM chapters WHERE is_public = 1 ORDER BY name')->fetchAll();
?>
<div class="page-head"><h1>Alumni Chapters</h1><p>Stay connected through shared disciplines, regions and interests</p></div>
<p class="lead">Alumni chapters help members stay connected through shared disciplines, colleges, faculties, departments, graduation years, geographical areas or other criteria recognised by GUCO. The Constitution places chapters under the umbrella of the Association and requires them to be registered.</p>

<section class="section">
  <h2>Recognised chapters</h2>
  <div class="grid">
    <?php foreach ($chapters as $c): ?>
      <div class="card">
        <h3><?= e($c['name']) ?></h3>
        <?php if ($c['focus']): ?><span class="badge"><?= e($c['focus']) ?></span><?php endif; ?>
        <p><?= nl2br(e($c['description'])) ?></p>
        <?php if ($c['contact_name'] || $c['contact_email']): ?>
          <p class="meta">Contact: <?= e($c['contact_name']) ?><?= $c['contact_email'] ? ' &middot; <a href="mailto:' . e($c['contact_email']) . '">' . e($c['contact_email']) . '</a>' : '' ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; if (!$chapters): ?>
      <p>The list of recognised chapters will be published here. Contact the Secretariat for the current list.</p>
    <?php endif; ?>
  </div>
</section>

<section class="section grid two">
  <div class="card"><h2>What chapters do</h2><ul class="checks">
    <li>Recruit members and promote participation in GUCO.</li>
    <li>Plan meetings, networking activities and events.</li>
    <li>Build relationships between alumni and current students.</li>
    <li>Support approved scholarships, student activities, faculty awards and research.</li>
    <li>Volunteer in University programmes and act as ambassadors for Gulu University.</li>
    <li>Submit updated membership lists and required activity reports to the Association.</li>
    <li>Coordinate chapter activities with GUCO and maintain contact with the Secretariat and Executive Committee.</li></ul></div>
  <div class="card"><h2>Chapter leadership</h2>
    <p>The Constitution lists a Chapter Chairperson, Vice Chairperson, Secretary, Treasurer, Organising Secretary and ten members for a Chapter Executive Committee. Chapter leaders are expected to file names, submit quarterly activity reports and submit meeting minutes within the period set out in the Constitution.</p></div>
</section>

<section class="cta">
  <h2 style="color:#fff;margin-top:0">Find or establish a chapter</h2>
  <p>Interested in joining a chapter or organising alumni in your region, profession or academic community? Contact the Secretariat for the approved registration procedure.</p>
  <a class="btn" href="<?= url('contact.php?category=Alumni+Chapters') ?>">ENQUIRE ABOUT ALUMNI CHAPTERS</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
