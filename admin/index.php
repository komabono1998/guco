<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_login();
if (!is_staff()) {
    http_response_code(403);
    exit('Staff only.');
}
$count = fn($sql) => (int) db()->query($sql)->fetchColumn();
$cards = [
    'members' => ['members.php', 'Members', $count("SELECT COUNT(*) FROM users WHERE status='pending'") . ' applications pending'],
    'news' => ['news.php', 'News', $count("SELECT COUNT(*) FROM news WHERE status='pending'") . ' awaiting review, ' . $count("SELECT COUNT(*) FROM news WHERE status='published'") . ' published'],
    'events' => ['events.php', 'Events', $count('SELECT COUNT(*) FROM events WHERE starts_at >= NOW()') . ' upcoming'],
    'chapters' => ['chapters.php', 'Chapters', $count('SELECT COUNT(*) FROM chapters') . ' chapters'],
    'leaders' => ['leaders.php', 'Leadership', $count('SELECT COUNT(*) FROM leaders WHERE is_public = 1') . ' published profiles'],
    'programmes' => ['programmes.php', 'Programmes', $count('SELECT COUNT(*) FROM programmes') . ' reports'],
    'awards' => ['awards.php', 'Awards', $count("SELECT COUNT(*) FROM awards WHERE nomination_status='open'") . ' open for nominations'],
    'resources' => ['resources.php', 'Resources', $count('SELECT COUNT(*) FROM resources') . ' documents'],
    'gallery' => ['gallery.php', 'Gallery', $count('SELECT COUNT(*) FROM gallery') . ' photos'],
    'messages' => ['messages.php', 'Messages', $count("SELECT COUNT(*) FROM messages WHERE status='new'") . ' new'],
    'subscribers' => ['subscribers.php', 'Subscribers', $count('SELECT COUNT(*) FROM subscribers') . ' subscribers'],
    'settings' => ['settings.php', 'Settings', 'Contacts, fees, applications'],
    'users' => ['users.php', 'Users & roles', $count("SELECT COUNT(*) FROM users WHERE role <> 'member'") . ' staff accounts'],
    'audit' => ['audit.php', 'Audit log', 'Sensitive changes'],
];
admin_header('Dashboard');
?>
<p>Signed in as <strong><?= e($me['full_name']) ?></strong> (<?= e(ROLE_LABELS[$me['role']]) ?>).</p>
<div class="grid">
  <?php foreach ($cards as $perm => [$file, $label, $info]): if (!can($perm)) continue; ?>
    <a class="card accent" href="<?= e($file) ?>"><h3><?= e($label) ?></h3><p class="meta"><?= e($info) ?></p></a>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
