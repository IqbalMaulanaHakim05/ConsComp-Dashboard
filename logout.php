<?php

declare(strict_types=1);

require_once 'auth.php';

// Kosongkan seluruh data sesi.
$_SESSION = [];

// Hapus cookie sesi dari browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        (bool) $params['secure'],
        (bool) $params['httponly']
    );
}

session_destroy();

header('Location: login.php?pesan=logout-berhasil');
exit;