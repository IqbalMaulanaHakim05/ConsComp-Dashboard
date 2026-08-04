<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Autentikasi & otorisasi.
| Login berbasis tabel `users` dengan tiga peran: superadmin, admin, viewer.
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| URL dasar aplikasi.
| File ini berada di dalam folder "fungsi", jadi root aplikasi adalah
| folder di atasnya. URL_DASAR dipakai agar redirect tetap benar dari
| halaman root maupun dari halaman di dalam folder "fungsi".
|--------------------------------------------------------------------------
*/

if (!defined('URL_DASAR')) {
    $appDir = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));

    $base = '/';
    if ($docRoot !== '' && strpos($appDir, $docRoot) === 0) {
        $base = substr($appDir, strlen($docRoot));
        $base = '/' . trim($base, '/');
        $base = $base === '/' ? '/' : $base . '/';
    }

    define('URL_DASAR', $base);
}

/*
|--------------------------------------------------------------------------
| Status login & data pengguna aktif
|--------------------------------------------------------------------------
*/

function sudahLogin(): bool
{
    return isset($_SESSION['user']['id']);
}

function penggunaAktif(): ?array
{
    return $_SESSION['user'] ?? null;
}

function namaPengguna(): string
{
    return (string) ($_SESSION['user']['nama'] ?? '');
}

function rolePengguna(): string
{
    return (string) ($_SESSION['user']['role'] ?? '');
}

/**
 * Memeriksa apakah pengguna aktif memiliki salah satu peran yang diberikan.
 */
function punyaRole(string ...$roles): bool
{
    return sudahLogin() && in_array(rolePengguna(), $roles, true);
}

/*
|--------------------------------------------------------------------------
| Penjaga akses halaman
|--------------------------------------------------------------------------
*/

function wajibLogin(): void
{
    if (sudahLogin()) {
        return;
    }

    $tujuan = basename((string) ($_SERVER['REQUEST_URI'] ?? ''));
    header('Location: ' . URL_DASAR . 'login.php?next=' . urlencode($tujuan));
    exit;
}

/**
 * Halaman hanya boleh diakses oleh peran tertentu.
 */
function wajibRole(string ...$roles): void
{
    wajibLogin();

    if (!punyaRole(...$roles)) {
        http_response_code(403);
        die('403 - Anda tidak memiliki hak akses untuk tindakan ini.');
    }
}

/*
|--------------------------------------------------------------------------
| Proses login & logout
|--------------------------------------------------------------------------
*/

/**
 * Memverifikasi kredensial ke tabel users. Mengembalikan true bila berhasil.
 */
function loginPengguna(mysqli $conn, string $username, string $password): bool
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, username, password, nama, role
         FROM users
         WHERE username = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$data || !password_verify($password, $data['password'])) {
        return false;
    }

    // Cegah session fixation dengan memperbarui ID sesi.
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $data['id'],
        'username' => (string) $data['username'],
        'nama' => (string) $data['nama'],
        'role' => (string) $data['role'],
    ];
    $_SESSION['waktu_login'] = time();

    return true;
}

function logoutPengguna(): void
{
    $_SESSION = [];

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
}

/*
|--------------------------------------------------------------------------
| Proteksi CSRF (dipakai form login)
|--------------------------------------------------------------------------
*/

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
