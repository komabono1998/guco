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

function enquiry_form(string $defaultCategory = 'General'): void
{
    $me = current_user();
    $sel = $_POST['category'] ?? $defaultCategory;
    ?>
<form method="post" class="stack">
  <?= csrf_field() ?><?= spam_fields() ?>
  <div class="cols">
    <label>Full name <input name="name" required autocomplete="name" value="<?= e($_POST['name'] ?? ($me['full_name'] ?? '')) ?>"></label>
    <label>Email address <input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? ($me['email'] ?? '')) ?>"></label>
  </div>
  <div class="cols">
    <label>Telephone number <span class="hint">(optional)</span> <input name="phone" autocomplete="tel" value="<?= e($_POST['phone'] ?? '') ?>"></label>
    <label>Enquiry category
      <select name="category"><?php foreach (ENQUIRY_CATEGORIES as $c): ?><option<?= $c === $sel ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select>
    </label>
  </div>
  <label>Subject <input name="subject" value="<?= e($_POST['subject'] ?? '') ?>"></label>
  <label>Message <textarea name="body" rows="6" required><?= e($_POST['body'] ?? '') ?></textarea></label>
  <label class="check"><input type="checkbox" name="consent" required> I agree to be contacted about this enquiry and have read the <a href="<?= url('privacy.php') ?>">privacy notice</a>.</label>
  <p class="meta">Information submitted through this form will be used to respond to your enquiry and handled according to GUCO's published privacy notice.</p>
  <button>Send message</button>
</form>
<?php
}
