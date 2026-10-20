<?php
require_once __DIR__ . '/includes/functions.php';
$t = (string)($_GET['t'] ?? '');
if (!empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t)) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    flash('success', 'You have been logged out.');
}
redirect('index.php');
