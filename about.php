<?php
$pageTitle = 'About GUCO';
$pageDesc = 'Overview, vision, mission, core values and objectives of Gulu University Convocation (GUCO).';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>About GUCO</h1><p>Community Transformation Actualized</p></div>

<section id="overview" class="section">
  <h2>Overview</h2>
  <p>Gulu University Convocation (GUCO) is the umbrella association for alumni of Gulu University and other eligible members recognised under its Constitution. The Constitution describes GUCO as a platform for mobilisation, coordination and inspiration, bringing alumni and the University closer together in pursuit of intellectual development, institutional advancement and service to the community.</p>
</section>

<section id="vision" class="section grid two">
  <div class="card accent"><h2>Vision</h2><p>To be an umbrella association for the alumni of Gulu University, mobilising, coordinating and inspiring alumni to participate in the development of enhanced intellectualism.</p></div>
  <div class="card accent"><h2>Mission</h2><p>To build a vibrant association that safeguards the best interests of its members and gives them a platform to use their talents and resources to support Gulu University in achieving international distinction in quality teaching, research and service to the community.</p></div>
</section>

<section class="section">
  <h2>Core values</h2>
  <div class="grid">
    <?php foreach (['Integrity', 'Gender responsiveness', 'Professionalism', 'Teamwork', 'Excellence', 'Inclusiveness'] as $v): ?>
      <div class="card"><strong><?= e($v) ?></strong></div>
    <?php endforeach; ?>
  </div>
</section>

<section id="objectives" class="section">
  <h2>Our objectives</h2>
  <ul class="checks">
    <li>Develop alumni programmes that promote effective networks among members.</li>
    <li>Promote the academic, physical, moral and spiritual growth of members of Gulu University.</li>
    <li>Support the University's socio-cultural and co-curricular activities.</li>
    <li>Involve alumni in matters relating to University governance, administration and academic interests.</li>
    <li>Promote and support the formation of local and international alumni chapters.</li>
    <li>Enhance Gulu University's involvement in community service.</li>
    <li>Initiate, organise and coordinate fundraising activities for Gulu University.</li>
    <li>Support University programmes and awards, including outstanding achievement and distinguished service recognition.</li>
    <li>Advance the interests and welfare of members.</li>
    <li>Build relationships with alumni associations at other institutions locally and internationally.</li>
    <li>Project a positive image of Gulu University, coordinate chapter activities and create local and international partnerships.</li>
  </ul>
</section>

<section class="section">
  <h2>Our mandate</h2>
  <p>GUCO's objectives include strengthening alumni relationships, supporting University development, promoting community service, mobilising resources and creating opportunities for alumni participation. The Association is non-political under Article 4 of the Constitution.</p>
</section>

<section class="section card">
  <h2 style="margin-top:0">The Constitution</h2>
  <p>The Constitution is GUCO's foundational governance document. It sets out the Association's objectives, membership requirements, member rights and obligations, leadership structures, elections, meetings, financial controls and amendment procedures.</p>
  <a class="btn" href="<?= url('resources.php?q=constitution') ?>">READ THE GUCO CONSTITUTION (2023)</a>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
