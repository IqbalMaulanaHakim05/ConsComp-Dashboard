<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Settings/master-data.php';
require_once __DIR__ . '/../Services/Employee/promosi-karyawan.php';

wajibRole('admin', 'superadmin');

$karyawanId = (int) ($_POST['karyawan_id'] ?? 0);
$kembali = URL_DASAR . 'profil-karyawan.php?id=' . $karyawanId . '&edit=1';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode permintaan tidak diizinkan.');
}
if (!csrfValid($_POST['csrf_token'] ?? null)) {
    header('Location: ' . $kembali . '&error_promosi=' . rawurlencode('Token keamanan tidak valid.') . '#promosi');
    exit;
}
if (!siapkanTabelHistoriJabatan($conn)) {
    http_response_code(500);
    exit('Penyimpanan histori jabatan tidak dapat disiapkan.');
}

try {
    $hasil = promosikanKaryawan(
        $conn,
        $karyawanId,
        (int) ($_POST['department_baru_id'] ?? 0),
        (int) ($_POST['posisi_baru_id'] ?? 0),
        trim((string) ($_POST['tanggal_perubahan'] ?? '')),
        trim((string) ($_POST['tanggal_mulai_jabatan'] ?? '')),
        (int) ($_SESSION['user']['id'] ?? 0),
        roleEfektif(rolePengguna())
    );

    catatAktivitas(
        $conn,
        sprintf(
            'Memindahkan jabatan karyawan ID %d dari %s - %s ke %s - %s.',
            $karyawanId,
            $hasil['departemen_lama'],
            $hasil['posisi_lama'],
            $hasil['departemen_baru'],
            $hasil['posisi_baru']
        )
    );
    header(
        'Location: ' . URL_DASAR . 'profil-karyawan.php?id=' . $karyawanId
        . '&pesan=' . rawurlencode('Promosi atau perpindahan posisi berhasil disimpan.')
        . '#promosi'
    );
    exit;
} catch (InvalidArgumentException | RuntimeException $exception) {
    header('Location: ' . $kembali . '&error_promosi=' . rawurlencode($exception->getMessage()) . '#promosi');
    exit;
} catch (Throwable $exception) {
    error_log('Promosi karyawan gagal: ' . $exception->getMessage());
    header('Location: ' . $kembali . '&error_promosi=' . rawurlencode('Promosi atau perpindahan posisi gagal disimpan.') . '#promosi');
    exit;
}
