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
<div class="page-head"><h1>Contact GUCO</h1><?= cr('contact.intro') ?></div>
<div class="grid two">
  <div>
    <h2>Send us a message</h2>
    <?php enquiry_form(in_array($_GET['category'] ?? '', ENQUIRY_CATEGORIES, true) ? $_GET['category'] : 'General'); ?>
  </div>
  <div>
    <h2>Official address</h2>
    <address class="card contact-card"><span class="ic-circle"><?= icon('map-pin', 24) ?></span><div><?= implode('<br>', array_map('e', c_lines('contact.address'))) ?></div></address>
    <?php $any = array_filter($details); if ($any): ?>
      <h2>Contact details</h2>
      <div class="card contact-list2"><?php $ci = ['Email' => 'mail', 'Telephone' => 'phone', 'Office hours' => 'clock', 'Membership support' => 'users', 'Chapter enquiries' => 'map-pin']; foreach ($any as $label => $v): ?>
        <div class="contact-row"><span class="ic-circle"><?= icon($ci[$label] ?? 'mail', 22) ?></span><div><small><?= e($label) ?></small><?= $label === 'Email' ? '<a href="mailto:' . e($v) . '">' . e($v) . '</a>' : e($v) ?></div></div>
      <?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
