<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/forms.php';
$pageTitle = 'Partnerships & Support';
$pageDesc = 'Partner with or support Gulu University Convocation: collaboration, sponsorship, mentorship and approved initiatives.';
handle_enquiry_post('partnerships.php');
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Partnerships &amp; Support</h1><p>Work with GUCO</p></div>
<p class="lead">GUCO seeks to strengthen connections among alumni, Gulu University and the wider community. Partnerships can help advance alumni engagement, University development, academic interests and community service in line with the Association's objectives.</p>

<section class="section">
  <?= dinner_figure(14, '', DINNER_ALBUM . ': celebrating excellence, strengthening partnerships and investing in the future of Gulu University') ?>
</section>

<section class="section grid two">
  <div class="card"><h2>Ways to collaborate</h2><ul class="checks">
    <li>Institutional and corporate partnerships.</li>
    <li>Alumni mentorship and knowledge-sharing opportunities.</li>
    <li>Support for approved student and community initiatives.</li>
    <li>Research, innovation and academic collaboration.</li>
    <li>Sponsorship of approved events and recognition programmes.</li>
    <li>Fundraising and in-kind contributions for authorised projects.</li></ul></div>
  <div class="card"><h2>Support GUCO</h2>
    <p>Alumni, organisations and friends who wish to support GUCO or Gulu University initiatives are invited to contact the Secretariat to discuss approved priorities and contribution procedures. All contributions must be handled through officially authorised channels and accounted for under the Association's financial procedures.</p></div>
</section>

<section class="section">
  <h2>Discuss a partnership or enquire about support</h2>
  <?php enquiry_form(in_array($_GET['category'] ?? '', ['Partnerships', 'Support GUCO'], true) ? $_GET['category'] : 'Partnerships'); ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
