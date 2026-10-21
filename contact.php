<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/forms.php';
$pageTitle = 'Contact';
$pageDesc = 'Contact the GUCO Secretariat about membership, alumni chapters, events or partnerships.';
handle_enquiry_post('contact.php');
require __DIR__ . '/includes/header.php';
$details = [
    'Email' => setting('contact_email'), 'Telephone' => setting('contact_phone'), 'Office hours' => setting('office_hours'),
    'Membership support' => setting('membership_contact'), 'Chapter enquiries' => setting('chapter_contact'),
];
?>
<div class="page-head"><h1>Contact GUCO</h1><p>Questions about membership, alumni chapters, events or working with GUCO?</p></div>
<div class="grid two">
  <div>
    <h2>Send us a message</h2>
    <?php enquiry_form(in_array($_GET['category'] ?? '', ENQUIRY_CATEGORIES, true) ? $_GET['category'] : 'General'); ?>
  </div>
  <div>
    <h2>Official address</h2>
    <address class="card">Gulu University Convocation (GUCO)<br>Gulu University<br>P.O. Box 166<br>Laroo Road, Gulu City, Uganda</address>
    <?php $any = array_filter($details); if ($any): ?>
      <h2>Contact details</h2>
      <div class="card"><?php foreach ($any as $label => $v): ?><p><strong><?= e($label) ?>:</strong> <?= e($v) ?></p><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
