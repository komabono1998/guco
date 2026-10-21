<?php
$pageTitle = 'Membership';
$pageDesc = 'Founder, Ordinary, Honorary and Life membership of Gulu University Convocation: eligibility, rights, obligations and how to join.';
require __DIR__ . '/includes/header.php';
$showFees = setting('show_fees') === '1';
$fmt = fn($k) => 'UGX ' . number_format((int) setting($k));
?>
<div class="page-head"><h1>Membership</h1><p>Become part of the GUCO alumni network</p></div>
<div class="split" style="margin-bottom:8px">
<div class="lead"><?= cr('membership.intro') ?></div>
<?= dinner_figure(9, '', 'Alumni connecting at the ' . DINNER_ALBUM) ?>
</div>

<section id="how" class="section">
  <h2>How membership works</h2>
  <ol class="steps">
    <li><strong>Apply online</strong> Share your personal, academic and employment details and choose your category.</li>
    <li><strong>Pay and upload proof</strong> Pay the applicable fee through the approved channel and upload your receipt.</li>
    <li><strong>Verification</strong> The Secretariat verifies your details and payment.</li>
    <li><strong>Get your card</strong> On approval you receive a Membership ID and a printable card with a QR code.</li>
  </ol>
  <p>Ordinary members renew their annual subscription from the Member Portal by uploading proof of payment. Cards can be checked at any time on the <a href="<?= url('verify.php') ?>">card verification page</a>, and a card may be revoked by the Secretariat under the Constitution.</p>
  <form method="get" action="<?= url('verify.php') ?>" class="filters">
    <label>Verify a membership card <input name="id" placeholder="GUCO/ORD/2026/000001" required style="min-width:260px"></label>
    <button>Verify</button>
  </form>
</section>

<section id="categories" class="section">
  <h2>Membership categories</h2>
  <div class="table-wrap"><table>
    <tr><th>Category</th><th>Who may qualify</th><?php if ($showFees): ?><th>Fee (Article 9)</th><?php endif; ?></tr>
    <tr><td><strong>Founder Member</strong></td><td>Steering Committee members and the first 1,000 eligible persons under Article 5(a).</td><?php if ($showFees): ?><td>One-time fee of <?= e($fmt('fee_founder')) ?></td><?php endif; ?></tr>
    <tr><td><strong>Ordinary / Full Member</strong></td><td>Gulu University graduates and other eligible persons described in Article 5, including specified University office holders and staff.</td><?php if ($showFees): ?><td>Annual subscription of <?= e($fmt('fee_ordinary')) ?></td><?php endif; ?></tr>
    <tr><td><strong>Honorary Member</strong></td><td>Eligible persons or corporate organisations admitted by invitation and the approval process set out in Article 5.</td><?php if ($showFees): ?><td>One-time fee of <?= e($fmt('fee_honorary')) ?>; corporate organisations have an additional listed fee.</td><?php endif; ?></tr>
    <tr><td><strong>Life Member</strong></td><td>Persons qualifying under Article 5, including specified former University office holders and persons meeting the advance subscription condition.</td><?php if ($showFees): ?><td>One-time fee of <?= e($fmt('fee_life')) ?></td><?php endif; ?></tr>
  </table></div>
  <?php if (!$showFees): ?><p class="notice">Membership fees are confirmed by the Secretariat. Please <a href="<?= url('contact.php') ?>">contact the Secretariat</a> for the current applicable fees and approved payment procedure before making any payment.</p><?php endif; ?>
</section>

<section class="section">
  <h2>Eligibility and registration</h2>
  <?= cr('membership.eligibility') ?>
  <ol>
    <li>Complete the GUCO membership application form.</li>
    <li>Provide the required identity, graduation or eligibility details.</li>
    <li>Select the membership category for which you qualify.</li>
    <li>Receive confirmation of the applicable fees and approved payment procedure from the Secretariat.</li>
    <li>Submit the application and payment evidence through the approved channel.</li>
    <li>Wait for verification and confirmation of membership.</li>
  </ol>
  <?php if (setting('applications_open') === '1'): ?><a class="btn" href="<?= url('register.php') ?>">Join GUCO</a><?php else: ?><p class="notice">Online applications are not open at the moment. Please contact the Secretariat.</p><?php endif; ?>
</section>

<section class="section grid two">
  <div class="card"><h2>Rights of members</h2><ul class="checks"><?php foreach (c_lines('membership.rights') as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul></div>
  <div class="card"><h2>Obligations of members</h2><ul class="checks"><?php foreach (c_lines('membership.obligations') as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul></div>
</section>

<section id="benefits" class="section">
  <h2>Membership benefits</h2>
  <?= cr('membership.benefits') ?>
</section>

<section id="faq" class="section">
  <h2>Membership FAQ</h2>
  <?php foreach (c_pairs('membership.faq') as [$qn, $an]): ?><details><summary><?= e($qn) ?></summary><?= render_text($an) ?></details><?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
