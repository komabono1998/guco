<?php
// Site configuration. Override locally in config.local.php (git-ignored).
// Contact details, fees and payment instructions are managed in Admin > Settings.
$config = [
    'site_name' => 'Gulu University Convocation',
    'short_name' => 'GUCO',
    'base_url' => '/guco',
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'guco',
        'user' => 'root',
        'pass' => '',
    ],
    'upload_dir' => __DIR__ . '/uploads',
    'storage_dir' => __DIR__ . '/storage/resources',
    // When true and mail() is unavailable, password-reset links are shown on screen.
    // Set to false in production.
    'dev_mode' => true,
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}

return $config;
