<?php
require_once __DIR__ . '/auth/init.php';

// Clear remember-me token from DB and delete cookie
if (isset($_COOKIE['remember_me'])) {
    $parts = explode('|', $_COOKIE['remember_me'], 2);
    if (count($parts) === 2 && ctype_digit($parts[0])) {
        getDB()->prepare(
            'UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?'
        )->execute([(int) $parts[0]]);
    }
    setcookie('remember_me', '', time() - 3600, '/', '', false, true);
}

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

header('Location: /login.php');
exit;
