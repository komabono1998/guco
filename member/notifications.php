<?php
require_once __DIR__ . '/../includes/portal.php';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['read_all'])) {
        q('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$me['id']]);
    } elseif (isset($_POST['open'])) {
        $n = q('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [(int) $_POST['open'], $me['id']])->fetch();
        if ($n) {
            q('UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = ?', [$n['id']]);
            // Links are always inside this site.
            if ($n['link'] && !preg_match('#^[a-z]+:|^//#i', $n['link'])) {
                redirect($n['link']);
            }
        }
    }
    redirect('member/notifications.php');
}
$rows = q('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100', [$me['id']])->fetchAll();
portal_header('Notifications');
?>
<section class="panel">
  <h2>Your notifications
    <?php if (unread_notifications((int) $me['id'])): ?><form method="post" style="display:inline"><?= csrf_field() ?><button class="secondary" name="read_all" value="1">Mark all as read</button></form><?php endif; ?></h2>
  <?php foreach ($rows as $n): ?>
    <form method="post" class="ev-mini" style="<?= $n['read_at'] ? 'opacity:.65' : '' ?>"><?= csrf_field() ?>
      <div><strong><?= e($n['title']) ?></strong><?= $n['read_at'] ? '' : ' <span class="badge warn">new</span>' ?><br><span class="meta"><?= e($n['body']) ?> &middot; <?= e(fmt_date($n['created_at'], 'j M Y, g:ia')) ?></span></div>
      <button class="secondary" name="open" value="<?= (int) $n['id'] ?>" style="margin-left:auto"><?= $n['link'] ? 'Open' : 'Mark read' ?></button>
    </form>
  <?php endforeach; if (!$rows): ?><p class="meta">Nothing yet. Approvals, receipts, event tickets and application updates will appear here.</p><?php endif; ?>
</section>
<?php portal_footer(); ?>
