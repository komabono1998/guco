<?php
require_once __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (is_spam() || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Please enter a valid email address.', 'error');
    } else {
        q('INSERT IGNORE INTO subscribers (email) VALUES (?)', [$email]);
        flash('Thank you for subscribing to the GUCO newsletter.');
    }
}
$back = $_SERVER['HTTP_REFERER'] ?? url();
$backHost = parse_url($back, PHP_URL_HOST);
$ownHost = parse_url(site_origin(), PHP_URL_HOST);
header('Location: ' . ($backHost && $backHost === $ownHost && preg_match('#^https?://#', $back) ? $back : url()));
exit;
