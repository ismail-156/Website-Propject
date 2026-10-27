<?php
require_once __DIR__ . '/../config.php';

// Clear the authenticated session and expire its browser cookie.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
redirect(appUrl('login.php'));
