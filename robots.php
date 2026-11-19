<?php
// robots.txt, served at /robots.txt (see .htaccess).
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
$base = rtrim(url(''), '/');
echo "User-agent: *\n";
foreach (['admin/', 'member/', 'portal.php', 'login.php', 'logout.php', 'forgot.php', 'reset.php', 'claim.php', 'profile.php', 'card.php', 'ticket.php', 'receipt.php', 'download.php', 'file.php', 'member_file.php', 'storage/', 'includes/', 'sql/', 'tools/'] as $p) {
    echo 'Disallow: ' . $base . '/' . $p . "\n";
}
echo "\nSitemap: " . site_origin() . $base . "/sitemap.xml\n";
