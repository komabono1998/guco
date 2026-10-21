<?php
// Usage: php tools/create_admin.php "Full Name" email@example.com password
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}
if ($argc < 4) {
    exit("Usage: php tools/create_admin.php \"Full Name\" email password\n");
}
require __DIR__ . '/../includes/bootstrap.php';
db()->prepare("INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?,?,?,'super_admin','approved')")
    ->execute([$argv[1], strtolower($argv[2]), password_hash($argv[3], PASSWORD_DEFAULT)]);
db()->prepare('INSERT INTO member_profiles (user_id) VALUES (?)')->execute([db()->lastInsertId()]);
echo "Super administrator created.\n";
