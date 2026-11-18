<?php
// Applies pending files from sql/migrations in order and records them in schema_migrations.
// Usage:  php tools/migrate.php            apply to the configured database
//         php tools/migrate.php --status   list applied and pending migrations
//         php tools/migrate.php --db=NAME  apply to another database (for example a scratch copy)
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$config = require __DIR__ . '/../config.php';
$db = $config['db'];
foreach ($argv as $a) {
    if (str_starts_with($a, '--db=')) {
        $db['name'] = substr($a, 5);
    }
}
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(120) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');
$done = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(__DIR__ . '/../sql/migrations/*.sql');
sort($files);
$status = in_array('--status', $argv, true);
foreach ($files as $f) {
    $name = basename($f);
    if (in_array($name, $done, true)) {
        echo ($status ? 'applied  ' : 'skip     ') . "$name\n";
        continue;
    }
    if ($status) {
        echo "pending  $name\n";
        continue;
    }
    // Split on statement-ending semicolons at line ends; comments are dropped.
    $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($f));
    foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $stmt) {
        if (trim($stmt) !== '') {
            $pdo->exec($stmt);
        }
    }
    $pdo->prepare('INSERT INTO schema_migrations (name) VALUES (?)')->execute([$name]);
    echo "applied  $name\n";
}
