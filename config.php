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
    // Optional fixed public address used in emailed links, e.g. https://guco.example.org
    'public_url' => '',
    // Development only: shows PHP errors and, when mail() is unavailable, password-reset links on screen.
    // Keep false in production; switch it on in config.local.php on a development machine.
    'dev_mode' => false,
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}

return $config;
