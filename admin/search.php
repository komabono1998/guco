<?php
require_once __DIR__ . '/../includes/admin.php';
$me = require_login();
if (!is_staff()) {
    http_response_code(403);
    exit('Staff only.');
}
$term = trim($_GET['q'] ?? '');
$like = '%' . $term . '%';
$groups = [];
if (mb_strlen($term) >= 2) {
    $add = function (string $perm, string $label, string $icon, string $sql, array $args, callable $map) use (&$groups) {
        if (!can($perm)) {
            return;
        }
        $rows = q($sql . ' LIMIT 8', $args)->fetchAll();
        if ($rows) {
            $groups[$label] = [$icon, array_map($map, $rows)];
        }
    };
    $no = (int) ltrim(preg_replace('/\D/', '', $term), '0');
    $add('members', 'Members', 'id-card',
        "SELECT u.id, u.full_name, u.email, u.status, p.membership_id, p.member_no FROM users u LEFT JOIN member_profiles p ON p.user_id = u.id WHERE u.role = 'member' AND (u.full_name LIKE ? OR u.email LIKE ? OR p.membership_id LIKE ? OR p.programme LIKE ? OR p.member_no = ?) ORDER BY u.full_name",
        [$like, $like, $like, $like, $no], fn($r) => [$r['full_name'], $r['email'] . ' · ' . ($r['membership_id'] ?: 'no ID yet') . ' · ' . $r['status'], 'members.php?id=' . $r['id']]);
    $add('payments', 'Payments', 'credit-card',
        'SELECT m.id, m.reference, m.amount, m.status, u.full_name, u.id uid FROM member_payments m JOIN users u ON u.id = m.user_id WHERE m.reference LIKE ? OR u.full_name LIKE ? ORDER BY m.created_at DESC',
        [$like, $like], fn($r) => [$r['full_name'] . ' · ' . $r['reference'], 'UGX ' . number_format((float) $r['amount']) . ' · ' . $r['status'], 'members.php?id=' . $r['uid']]);
    $add('news', 'News', 'newspaper', 'SELECT id, title, status, category FROM news WHERE title LIKE ? OR summary LIKE ? ORDER BY created_at DESC',
        [$like, $like], fn($r) => [$r['title'], $r['category'] . ' · ' . $r['status'], 'news.php?id=' . $r['id']]);
    $add('newsletters', 'Newsletters', 'mail', 'SELECT id, title, issue_label, status FROM newsletters WHERE title LIKE ? OR issue_label LIKE ? ORDER BY created_at DESC',
        [$like, $like], fn($r) => [$r['title'], trim($r['issue_label'] . ' · ' . $r['status'], ' ·'), 'newsletters.php?id=' . $r['id']]);
    $add('events', 'Events', 'calendar', 'SELECT id, title, location, starts_at FROM events WHERE title LIKE ? OR location LIKE ? ORDER BY starts_at DESC',
        [$like, $like], fn($r) => [$r['title'], event_when($r) . ($r['location'] ? ' · ' . $r['location'] : ''), 'events.php?id=' . $r['id']]);
    $add('resources', 'Reports & documents', 'folder', 'SELECT id, title, category FROM resources WHERE title LIKE ? OR description LIKE ? ORDER BY published_at DESC',
        [$like, $like], fn($r) => [$r['title'], $r['category'], 'resources.php?id=' . $r['id']]);
    $add('pages', 'Custom pages', 'file', 'SELECT id, title, slug FROM pages WHERE title LIKE ? OR body LIKE ?',
        [$like, $like], fn($r) => [$r['title'], 'page.php?slug=' . $r['slug'], 'pages.php?id=' . $r['id']]);
    $add('messages', 'Messages', 'inbox', 'SELECT id, name, subject, category, status FROM messages WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR body LIKE ? ORDER BY created_at DESC',
        [$like, $like, $like, $like], fn($r) => [($r['subject'] ?: $r['category']) . ' · ' . $r['name'], $r['status'], 'messages.php']);
    $add('projects', 'Projects', 'target', 'SELECT p.id, p.title, p.status, u.full_name FROM projects p JOIN users u ON u.id = p.user_id WHERE p.status <> "draft" AND (p.title LIKE ? OR u.full_name LIKE ?) ORDER BY p.updated_at DESC',
        [$like, $like], fn($r) => [$r['title'], $r['full_name'] . ' · ' . PROJECT_STATUS[$r['status']][0], 'projects.php?id=' . $r['id']]);
    $add('chapters', 'Chapters', 'map-pin', 'SELECT id, name FROM chapters WHERE name LIKE ? OR districts LIKE ?',
        [$like, $like], fn($r) => [$r['name'], 'Chapter', 'chapters.php?id=' . $r['id']]);
    $add('leaders', 'Leadership', 'users', "SELECT id, name, title FROM leaders WHERE name LIKE ? OR title LIKE ?",
        [$like, $like], fn($r) => [($r['name'] ?: 'To be announced') . ' · ' . $r['title'], 'Leadership profile', 'leaders.php?id=' . $r['id']]);
    $add('gallery', 'Photo albums', 'image', 'SELECT id, title FROM gallery_albums WHERE title LIKE ?',
        [$like], fn($r) => [$r['title'], 'Album', 'gallery_photos.php?album=' . $r['id']]);
}
admin_header('Search', $term !== '' ? 'Results for "' . $term . '"' : 'Search members, news, events, documents and more');
?>
<form method="get" class="filters" style="max-width:640px;margin-bottom:20px">
  <label style="flex:1">Search <input type="search" name="q" value="<?= e($term) ?>" autofocus placeholder="Name, email, membership ID, title..." style="width:100%"></label>
  <button>Search</button>
</form>
<?php if ($term !== '' && mb_strlen($term) < 2): ?><p class="notice">Type at least two characters.</p><?php endif; ?>
<?php foreach ($groups as $label => [$ic, $hits]): ?>
  <section class="search-group">
    <h3><?= icon($ic, 14) ?> <?= e($label) ?> (<?= count($hits) ?>)</h3>
    <?php foreach ($hits as [$t, $sub, $href]): ?>
      <a class="search-hit" href="<?= e(url('admin/' . $href)) ?>"><div><strong><?= e($t) ?></strong><small><?= e($sub) ?></small></div><span class="go">Open &rarr;</span></a>
    <?php endforeach; ?>
  </section>
<?php endforeach; ?>
<?php if ($term !== '' && mb_strlen($term) >= 2 && !$groups): ?><p>Nothing found for "<?= e($term) ?>". Try a different spelling or fewer words.</p><?php endif; ?>
<?php admin_footer(); ?>
