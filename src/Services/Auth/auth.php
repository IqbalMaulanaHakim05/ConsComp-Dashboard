<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Autentikasi & otorisasi.
| Login berbasis tabel `users` dengan peran sesuai kebutuhan aplikasi.
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
    $controllerDir = str_replace('\\', '/', dirname(__DIR__, 2) . '/Controllers');
    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));

    $base = '/';
    if ($docRoot !== '' && strpos($controllerDir, $docRoot) === 0) {
        $base = substr($controllerDir, strlen($docRoot));
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
    return isset($_SESSION['user']['id'])
        && in_array(
            roleEfektif((string) ($_SESSION['user']['role'] ?? '')),
            ['admin', 'superadmin', 'pic', 'koordinator', 'manager'],
            true
        );
}

/**
 * Direktur memiliki hak akses yang sama dengan Manager, tetapi identitas
 * role aslinya tetap dipertahankan untuk tampilan dan audit.
 */
function roleEfektif(string $role): string
{
    return $role === 'direktur' ? 'manager' : $role;
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

function labelRole(string $role): string
{
    return match ($role) {
        'admin' => 'Admin HRGA',
        'superadmin' => 'Superadmin',
        'pic' => 'PIC',
        'koordinator' => 'Koordinator',
        'manager' => 'Manager',
        'direktur' => 'Direktur',
        'viewer' => 'Viewer',
        default => ucfirst($role),
    };
}

function labelAktivitas(string $aktivitas): string
{
    return preg_replace('/\badmin(?:\s+HRGA)?\b/i', 'Admin', $aktivitas) ?? $aktivitas;
}

function fieldKaryawanAdminHrga(): array
{
    return [
        'alamat',
        'tanggal_lahir',
        'agama',
        'gender',
        'marital_status',
        'kontak',
        'email',
        'biografi',
        'keahlian',
        'riwayat_pendidikan',
        'tanggal_riwayat_pendidikan',
        'riwayat_pekerjaan',
        'tanggal_riwayat_pekerjaan',
    ];
}

function adminHrgaBolehEditFieldKaryawan(string $field): bool
{
    return in_array($field, fieldKaryawanAdminHrga(), true);
}

function fieldRequestEditKaryawanAdminHrga(): array
{
    return array_merge(
        fieldKaryawanAdminHrga(),
        ['pendidikan', 'pekerjaan', 'shift_nama', 'shift_mulai', 'shift_selesai', 'shift_hari', 'return_to_profile', 'csrf_token']
    );
}

function fieldTerlarangEditKaryawanAdminHrga(array $post, array $files, array $dataKaryawan): array
{
    $fieldDiizinkan = fieldRequestEditKaryawanAdminHrga();
    $fileDiizinkan = ['foto_profil', 'file_cv', 'file_ijazah', 'file_mcu'];
    $fieldTerlarang = [];

    foreach ($post as $field => $nilai) {
        if (in_array($field, $fieldDiizinkan, true)) {
            continue;
        }

        $nilaiBerubah = is_array($nilai)
            || !array_key_exists($field, $dataKaryawan)
            || trim((string) $nilai) !== trim((string) ($dataKaryawan[$field] ?? ''));

        if ($nilaiBerubah) {
            $fieldTerlarang[] = (string) $field;
        }
    }

    foreach ($files as $field => $file) {
        if (
            !in_array((string) $field, $fileDiizinkan, true)
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ) {
            $fieldTerlarang[] = (string) $field;
        }
    }

    return array_values(array_unique($fieldTerlarang));
}

function departmentIdPengguna(): ?int
{
    $nilai = $_SESSION['user']['department_id'] ?? null;
    return $nilai === null || $nilai === '' ? null : (int) $nilai;
}

function roleOperasional(): bool
{
    return in_array(roleEfektif(rolePengguna()), ['pic', 'koordinator', 'manager'], true);
}

function karyawanDalamCakupan(mysqli $conn, int $id): ?array
{
    $sql = "SELECT * FROM karyawan WHERE id = ?";
    if (roleOperasional()) {
        $sql .= " AND department_id = ?";
    }
    $sql .= " LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    if (roleOperasional()) {
        $departmentId = (int) (departmentIdPengguna() ?? 0);
        mysqli_stmt_bind_param($stmt, "ii", $id, $departmentId);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $id);
    }
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return $data;
}

/**
 * Memeriksa apakah pengguna aktif memiliki salah satu peran yang diberikan.
 */
function punyaRole(string ...$roles): bool
{
    return sudahLogin() && in_array(roleEfektif(rolePengguna()), $roles, true);
}

/**
 * PIC hanya boleh membuka halaman Lembur, Izin, dan halaman pendukungnya.
 */
function halamanDiizinkanUntukPic(string $namaFile): bool
{
    return in_array(
        basename($namaFile),
        [
            'lembur.php',
            'denda.php',
            'absensi.php',
            'izin-karyawan.php',
            'izin-cuti.php',
            'izin-cuti-khusus.php',
            'izin-sakit.php',
            'surat-sakit.php',
            'notifikasi-izin-sakit.php',
            'export_izin_sakit.php',
            'notifikasi-denda.php',
            'export_denda.php',
            'notifikasi.php',
            'notifikasi-izin-karyawan.php',
            'notifikasi-izin-cuti.php',
            'notifikasi-izin-cuti-khusus.php',
            'export_lembur.php',
            'export_izin_karyawan.php',
            'export_izin_cuti.php',
            'export_izin_cuti_khusus.php',
            'logout.php',
        ],
        true
    );
}

function batasiAksesPic(): void
{
    if (rolePengguna() !== 'pic') {
        return;
    }

    $namaFile = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (halamanDiizinkanUntukPic($namaFile)) {
        return;
    }

    header('Location: ' . URL_DASAR . 'lembur.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Penjaga akses halaman
|--------------------------------------------------------------------------
*/

function wajibLogin(): void
{
    if (sudahLogin()) {
        batasiAksesPic();
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
        "SELECT id, username, password, nama, role, department_id, is_active
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

    if (!$data || (int) $data['is_active'] !== 1 || !password_verify($password, $data['password'])) {
        return false;
    }

    if (in_array(roleEfektif((string) $data['role']), ['pic', 'koordinator', 'manager'], true) && empty($data['department_id'])) {
        return false;
    }

    // Cegah session fixation dengan memperbarui ID sesi.
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $data['id'],
        'username' => (string) $data['username'],
        'nama' => (string) $data['nama'],
        'role' => (string) $data['role'],
        'department_id' => $data['department_id'] === null ? null : (int) $data['department_id'],
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
