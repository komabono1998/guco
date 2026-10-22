<?php
// Sign-in lives in two places: /admin/login.php for staff and /member/login.php for members.
require_once __DIR__ . '/includes/bootstrap.php';
redirect(isset($_GET['staff']) ? 'admin/login.php' : 'member/login.php');
