<?php
const ENQUIRY_CATEGORIES = [
    'General', 'Membership', 'Alumni Chapters', 'Events', 'Partnerships', 'Support GUCO',
    'Profile correction', 'Other',
];

/** Handles the shared enquiry form. Returns true if a message was stored. */
function handle_enquiry_post(string $redirectTo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    csrf_check();
    if (is_spam()) {
        flash('Your message could not be sent. Please try again.', 'error');
        redirect($redirectTo);
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $cat = in_array($_POST['category'] ?? '', ENQUIRY_CATEGORIES, true) ? $_POST['category'] : 'General';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $body === '' || empty($_POST['consent'])) {
        flash('Please fill in your name, a valid email and a message, and accept the privacy notice.', 'error');
        return;
    }
    $me = current_user();
    q('INSERT INTO messages (user_id, name, email, phone, category, subject, body) VALUES (?,?,?,?,?,?,?)',
        [$me['id'] ?? null, $name, $email, trim($_POST['phone'] ?? ''), $cat, trim($_POST['subject'] ?? ''), $body]);
    flash('Thank you. Your message has been sent to the Secretariat.');
    redirect($redirectTo);
}

const ENQUIRY_ICONS = [
    'General' => 'mail', 'Membership' => 'id-card', 'Alumni Chapters' => 'map-pin', 'Events' => 'calendar', 'Partnerships' => 'users',
    'Support GUCO' => 'heart', 'Profile correction' => 'edit', 'Other' => 'file',
];

/** The shared enquiry form: topic pills, floating labels, live checks, a progress bar and a clear consent switch. */
function enquiry_form(string $defaultCategory = 'General'): void
{
    $me = current_user();
    $sel = in_array($_POST['category'] ?? $defaultCategory, ENQUIRY_CATEGORIES, true) ? ($_POST['category'] ?? $defaultCategory) : 'General';
    $v = fn($k, $d = '') => e($_POST[$k] ?? $d);
    ?>
<form method="post" class="enq" id="enq" novalidate>
  <?= csrf_field() ?><?= spam_fields() ?>
  <div class="enq-prog" aria-hidden="true"><i id="enqBar"></i></div>
  <p class="enq-prog-t" id="enqProg" aria-live="polite">0 of 4 required fields done</p>

  <fieldset class="enq-cats">
    <legend>What is your message about?</legend>
    <div class="enq-pills">
      <?php foreach (ENQUIRY_CATEGORIES as $c): ?>
        <label class="enq-pill"><input type="radio" name="category" value="<?= e($c) ?>"<?= $c === $sel ? ' checked' : '' ?>><span><?= icon(ENQUIRY_ICONS[$c] ?? 'mail', 16) ?> <?= e($c) ?></span></label>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="enq-grid">
    <div class="enq-f" data-req><input id="enq-name" name="name" required autocomplete="name" placeholder=" " value="<?= $v('name', $me['full_name'] ?? '') ?>"><label for="enq-name">Full name</label><small class="enq-err">Please enter your name.</small></div>
    <div class="enq-f" data-req data-type="email"><input id="enq-email" type="email" name="email" required autocomplete="email" placeholder=" " value="<?= $v('email', $me['email'] ?? '') ?>"><label for="enq-email">Email address</label><small class="enq-err">Enter a valid email address.</small></div>
    <div class="enq-f"><input id="enq-phone" name="phone" autocomplete="tel" placeholder=" " value="<?= $v('phone') ?>"><label for="enq-phone">Telephone <em>(optional)</em></label></div>
    <div class="enq-f"><input id="enq-subject" name="subject" placeholder=" " value="<?= $v('subject') ?>"><label for="enq-subject">Subject <em>(optional)</em></label></div>
  </div>

  <div class="enq-f enq-msg" data-req>
    <textarea id="enq-body" name="body" required rows="6" maxlength="4000" placeholder=" "><?= $v('body') ?></textarea><label for="enq-body">Your message</label>
    <small class="enq-err">Please write your message.</small>
    <span class="enq-count" id="enqCount" aria-hidden="true">0 / 4000</span>
  </div>

  <label class="enq-consent" data-req-check>
    <input type="checkbox" name="consent" required>
    <span class="enq-switch" aria-hidden="true"></span>
    <span class="enq-ctext">I agree to be contacted about this enquiry and have read the <a href="<?= url('privacy.php') ?>">privacy notice</a>.</span>
  </label>
  <small class="enq-err enq-err-c" id="enqConsentErr">Please tick the box so we can reply to you.</small>

  <div class="enq-foot">
    <button class="enq-send" id="enqSend" type="submit"><span class="enq-send-t">Send message</span> <?= icon('arrow-right', 18) ?></button>
    <p class="enq-note">Your details are used only to answer this enquiry and are handled under GUCO's published privacy notice.</p>
  </div>
</form>
<script src="<?= asset('assets/js/enquiry.js') ?>" defer></script>
<?php
}
