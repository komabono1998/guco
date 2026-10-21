<?php
$pageTitle = 'Membership';
$pageDesc = 'Founder, Ordinary, Honorary and Life membership of Gulu University Convocation: eligibility, rights, obligations and how to join.';
require __DIR__ . '/includes/header.php';
$showFees = setting('show_fees') === '1';
$fmt = fn($k) => 'UGX ' . number_format((int) setting($k));
?>
<div class="page-head"><h1>Membership</h1><p>Become part of the GUCO alumni network</p></div>
<div class="split" style="margin-bottom:8px">
<p class="lead">Membership connects eligible alumni and other qualifying members with a shared platform for networking, participation in Association activities and support for Gulu University. The Constitution provides for Founder, Ordinary, Honorary and Life Membership.</p>
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
  <p>Article 5 states that membership is open to eligible alumni above 18 years, subject to the Constitution's requirements. A person is deemed a member upon registration and full payment of the applicable membership and subscription fees for the relevant category.</p>
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
  <div class="card"><h2>Rights of members</h2><ul class="checks">
    <li>Attend Annual General Meetings.</li>
    <li>Vote in general elections where the member meets the Constitution's active-member requirements.</li>
    <li>Stand for office and nominate or be nominated where eligible.</li>
    <li>Participate in Association functions and activities, subject to applicable rules.</li>
    <li>Appeal and be heard in accordance with the Constitution.</li></ul></div>
  <div class="card"><h2>Obligations of members</h2><ul class="checks">
    <li>Pay the applicable membership fees and annual subscriptions.</li>
    <li>Refrain from using GUCO resources and assets for personal enrichment.</li>
    <li>Observe the Constitution and applicable Association rules.</li></ul></div>
</section>

<section id="benefits" class="section">
  <h2>Membership benefits</h2>
  <p>The Constitution provides for participation in Association activities and, for fully paid-up members whose dues are not in arrears, access to Association journals, bulletins, printed materials or other membership benefits that may apply. GUCO may publish additional benefits only after they have been approved and are actually available.</p>
</section>

<section id="faq" class="section">
  <h2>Membership FAQ</h2>
  <details><summary>Who can apply?</summary><p>People who meet the eligibility criteria in Article 5 of the GUCO Constitution.</p></details>
  <details><summary>Does submitting a form make me a member?</summary><p>The Constitution states that registration and full payment of the applicable fees are required. The Secretariat confirms when an application is approved.</p></details>
  <details><summary>Can members vote?</summary><p>Voting rights depend on the Constitution and active-member requirements.</p></details>
  <details><summary>How do I confirm the current membership fees?</summary><p>Contact the GUCO Secretariat before making payment. Do not rely on an old website amount without confirmation.</p></details>
  <details><summary>Can I join a chapter?</summary><p>Members can explore <a href="<?= url('chapters.php') ?>">recognised chapters</a> and contact the Secretariat about existing or proposed chapters.</p></details>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
