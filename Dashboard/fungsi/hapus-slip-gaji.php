<?php

declare(strict_types=1);

require dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/slip-gaji.php';

wajibRole('admin', 'superadmin');

$karyawanId = (int) ($_POST['karyawan_id'] ?? 0);
$slipId = (int) ($_POST['slip_id'] ?? 0);
$kembali = '../profil-karyawan.php?id=' . $karyawanId . '&edit=1#slip-gaji';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode permintaan tidak diizinkan.');
}

if (!csrfValid($_POST['csrf_token'] ?? null)) {
    header('Location: ' . $kembali . '&error_slip=' . rawurlencode('Token keamanan tidak valid.'));
    exit;
}

if ($karyawanId <= 0 || $slipId <= 0) {
    header('Location: ' . $kembali . '&error_slip=' . rawurlencode('Data slip gaji yang akan dihapus tidak valid.'));
    exit;
}

try {
    $slip = hapusSlipGaji($conn, $slipId, $karyawanId);

    catatAktivitas(
        $conn,
        sprintf(
            'Menghapus slip gaji periode %s %d untuk karyawan %s (ID: %d, versi: %d).',
            namaBulanSlipGaji((int) $slip['bulan']),
            (int) $slip['tahun'],
            (string) ($slip['nama_snapshot'] ?? ('Karyawan #' . $karyawanId)),
            $karyawanId,
            (int) ($slip['versi'] ?? 1)
        )
    );

    header('Location: ' . $kembali . '&pesan=' . rawurlencode('Slip gaji berhasil dihapus.'));
    exit;
} catch (Throwable $e) {
    header('Location: ' . $kembali . '&error_slip=' . rawurlencode($e->getMessage()));
    exit;
}
