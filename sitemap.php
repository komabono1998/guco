<?php
// XML sitemap for search engines. Served at /sitemap.xml (see .htaccess).
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$base = site_origin() . rtrim(url(''), '/');
$urls = [];
$add = function (string $path, ?string $mod = null, string $freq = 'monthly', string $prio = '0.6') use (&$urls, $base) {
    $urls[] = ['loc' => $base . '/' . ltrim($path, '/'), 'mod' => $mod ? date('Y-m-d', strtotime($mod)) : null, 'freq' => $freq, 'prio' => $prio];
};
$add('', null, 'weekly', '1.0');
foreach (['about.php', 'leadership.php', 'membership.php', 'programmes.php', 'projects.php', 'chapters.php', 'partnerships.php', 'awards.php', 'hall-of-fame.php', 'careers.php', 'resources.php', 'anthems.php', 'contact.php', 'donate.php'] as $p) {
    if (is_file(__DIR__ . '/' . $p)) { $add($p, null, 'monthly', '0.7'); }
}
foreach (['news.php' => 'daily', 'events.php' => 'daily', 'gallery.php' => 'weekly', 'newsletters.php' => 'monthly'] as $p => $f) {
    $add($p, null, $f, '0.8');
}
$rows = function (string $sql) { try { return q($sql)->fetchAll(); } catch (Throwable $e) { return []; } };
foreach ($rows("SELECT slug, published_at FROM news WHERE status = 'published' AND published_at <= NOW() AND source_type = 'article' ORDER BY published_at DESC LIMIT 2000") as $r) { $add('news_view.php?slug=' . rawurlencode($r['slug']), $r['published_at'], 'monthly', '0.7'); }
foreach ($rows("SELECT id, starts_at FROM events WHERE status <> 'draft' ORDER BY id DESC LIMIT 1000") as $r) { $add('event_view.php?id=' . (int) $r['id'], $r['starts_at'], 'weekly', '0.6'); }
foreach ($rows("SELECT slug FROM pages WHERE is_published = 1") as $r) { $add('page.php?slug=' . rawurlencode($r['slug']), null, 'monthly', '0.5'); }
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>' . ($u['mod'] ? '<lastmod>' . $u['mod'] . '</lastmod>' : '') . '<changefreq>' . $u['freq'] . '</changefreq><priority>' . $u['prio'] . "</priority></url>\n";
}
echo "</urlset>\n";
