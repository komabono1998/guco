<?php
require_once __DIR__ . '/../includes/admin.php';
require_perm('newsletters');
$nl = q("SELECT * FROM newsletters WHERE id = ? AND status = 'published'", [(int) ($_GET['id'] ?? $_POST['id'] ?? 0)])->fetch();
if (!$nl) {
    http_response_code(404);
    exit('Publish the newsletter before emailing it.');
}
$subs = q('SELECT email FROM subscribers ORDER BY email')->fetchAll(PDO::FETCH_COLUMN);
$members = q("SELECT u.email FROM users u WHERE u.role = 'member' AND u.status = 'approved' ORDER BY u.email")->fetchAll(PDO::FETCH_COLUMN);
$link = absolute_url('newsletter_view.php?slug=' . urlencode($nl['slug']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $to = [];
    if (!empty($_POST['subscribers'])) {
        $to = array_merge($to, $subs);
    }
    if (!empty($_POST['members'])) {
        $to = array_merge($to, $members);
    }
    $to = valid_emails(array_merge($to, preg_split('/[\s,;]+/', (string) ($_POST['extra'] ?? ''))));
    if (!$to) {
        flash('Choose at least one audience or add an email address.', 'error');
    } else {
        $text = trim($_POST['message'] ?? '') . "\n\nRead online: $link\n" . ($nl['pdf'] ? 'PDF: ' . absolute_url('file.php?type=newsletter&id=' . (int) $nl['id']) . "\n" : '') . "\nGulu University Convocation";
        $html = '<p>' . nl2br(e(trim($_POST['message'] ?? ''))) . '</p><p><a href="' . e($link) . '">Read the newsletter online</a></p>';
        $sent = 0;
        foreach ($to as $addr) {
            $sent += deliver_mail($addr, $nl['title'], $text, ['html' => $html, 'context' => 'newsletter:' . $nl['id']]) ? 1 : 0;
        }
        q('UPDATE newsletters SET emailed_at = NOW() WHERE id = ?', [$nl['id']]);
        audit('email_newsletter', "newsletter {$nl['id']}: " . count($to) . " recipients, $sent delivered");
        flash(count($to) . " email(s) processed: $sent delivered" . ($sent < count($to) ? ', ' . (count($to) - $sent) . ' failed. See the Mail log for details.' : '.'), $sent === count($to) ? 'success' : 'error');
    }
    redirect('admin/newsletter_send.php?id=' . $nl['id']);
}
admin_header('Email newsletter: ' . $nl['title']);
?>
<p><a href="<?= url('admin/newsletters.php') ?>">&larr; Newsletters</a></p>
<?php if ($nl['emailed_at']): ?><p class="notice">Last emailed <?= e(fmt_date($nl['emailed_at'], 'j M Y, g:ia')) ?>.</p><?php endif; ?>
<form method="post" class="stack wide card">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $nl['id'] ?>">
  <h3 style="margin:0">Who should receive it?</h3>
  <label class="check"><input type="checkbox" name="subscribers" checked> Newsletter subscribers (<?= count($subs) ?>)</label>
  <label class="check"><input type="checkbox" name="members"> All approved members (<?= count($members) ?>)</label>
  <label>Other email addresses <span class="hint">(separated by commas or new lines)</span><textarea name="extra" rows="2"></textarea></label>
  <label>Message <textarea name="message" rows="5"><?= e("Dear friend,\n\n" . ($nl['summary'] ?: 'The latest GUCO newsletter is now available.')) ?></textarea></label>
  <p class="meta">The email links to <?= e($link) ?><?= $nl['pdf'] ? ' and the PDF' : '' ?>. Delivery results are recorded in the Mail log.</p>
  <button onclick="return confirm('Send this newsletter now?')">Send newsletter</button>
</form>
<?php admin_footer(); ?>
