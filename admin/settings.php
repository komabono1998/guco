<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('settings');
$fields = [
    'Contact details (publish only verified details)' => [
        'contact_email' => ['Official GUCO email', 'text'], 'contact_phone' => ['Official telephone number', 'text'],
        'office_hours' => ['Secretariat office hours', 'text'], 'membership_contact' => ['Membership support contact', 'text'],
        'chapter_contact' => ['Chapter enquiries contact', 'text'],
    ],
    'Official social media (leave blank until confirmed)' => [
        'social_facebook' => ['Facebook URL', 'text'], 'social_x' => ['X (Twitter) URL', 'text'],
        'social_linkedin' => ['LinkedIn URL', 'text'], 'social_youtube' => ['YouTube URL', 'text'],
    ],
    'Membership' => [
        'applications_open' => ['Accept online membership applications', 'check'],
        'require_payment_proof' => ['Require proof of payment on the membership application form', 'check'],
        'show_fees' => ['Show membership fees on the Membership page (only after the Secretariat confirms them)', 'check'],
        'fee_founder' => ['Founder Member one-time fee (UGX)', 'text'], 'fee_ordinary' => ['Ordinary Member annual subscription (UGX)', 'text'],
        'fee_honorary' => ['Honorary Member one-time fee (UGX)', 'text'], 'fee_life' => ['Life Member one-time fee (UGX)', 'text'],
        'payment_instructions' => ['Payment instructions (shown only to logged-in applicants; add once formally authorised)', 'textarea'],
    ],
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $changed = [];
    foreach ($fields as $group) {
        foreach ($group as $k => [$label, $type]) {
            $v = $type === 'check' ? (isset($_POST[$k]) ? '1' : '0') : trim($_POST[$k] ?? '');
            if ($v !== setting($k) && !($v === '' && setting($k) === '')) {
                $changed[] = $k;
            }
            q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
        }
    }
    audit('update_settings', implode(', ', $changed) ?: 'no changes');
    flash('Settings saved.');
    redirect('admin/settings.php');
}
admin_header('Site settings');
?>
<form method="post" class="stack wide">
  <?= csrf_field() ?>
  <?php foreach ($fields as $group => $items): ?>
    <h2><?= e($group) ?></h2>
    <?php foreach ($items as $k => [$label, $type]): ?>
      <?php if ($type === 'check'): ?>
        <label class="check"><input type="checkbox" name="<?= $k ?>" <?= setting($k) === '1' ? 'checked' : '' ?>> <?= e($label) ?></label>
      <?php elseif ($type === 'textarea'): ?>
        <label><?= e($label) ?><textarea name="<?= $k ?>" rows="4"><?= e(setting($k)) ?></textarea></label>
      <?php else: ?>
        <label><?= e($label) ?><input name="<?= $k ?>" value="<?= e(setting($k)) ?>"></label>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <button>Save settings</button>
</form>
<?php admin_footer(); ?>
