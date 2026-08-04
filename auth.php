<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD_HASH = '$2y$12$Npqnv2VE2Ht0KgXeiM2w2OnVV/kDPLtTKVEbW/NFodLOhkkIRY.HC';

function adminSudahLogin(): bool
{
    return isset($_SESSION['admin_login'])
        && $_SESSION['admin_login'] === true
        && isset($_SESSION['admin_username']);
}

function wajibLoginAdmin(): void
{
    if (adminSudahLogin()) {
        return;
    }

    $tujuan = basename((string) ($_SERVER['REQUEST_URI'] ?? 'index.php'));
    header('Location: login.php?next=' . urlencode($tujuan));
    exit;
}

function tokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfValid(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
