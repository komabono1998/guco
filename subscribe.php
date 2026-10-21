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
header('Location: ' . (str_starts_with($back, 'http://' . ($_SERVER['HTTP_HOST'] ?? '')) || str_starts_with($back, 'https://' . ($_SERVER['HTTP_HOST'] ?? '')) ? $back : url()));
exit;
