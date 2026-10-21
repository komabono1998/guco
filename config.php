<?php
// Site configuration. Override locally in config.local.php (git-ignored).
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
    'contact_email' => 'info@guco.example',
    'donation' => [
        'mobile_money' => 'Add MTN/Airtel number here',
        'bank' => 'Add bank name, account name and number here',
    ],
    'upload_dir' => __DIR__ . '/uploads',
];

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}

return $config;
