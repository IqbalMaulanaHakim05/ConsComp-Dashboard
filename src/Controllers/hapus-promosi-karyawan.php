<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Employee/promosi-karyawan.php';

wajibRole('admin', 'superadmin');

$karyawanId = (int) ($_POST['karyawan_id'] ?? 0);
$historiId = (int) ($_POST['histori_id'] ?? 0);
$kembali = '../profil-karyawan.php?id=' . $karyawanId . '&edit=1#promosi';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode permintaan tidak diizinkan.');
}

if (!csrfValid($_POST['csrf_token'] ?? null)) {
    header('Location: ' . $kembali . '&error_promosi=' . rawurlencode('Token keamanan tidak valid.'));
    exit;
}

if ($karyawanId <= 0 || $historiId <= 0) {
    header('Location: ' . $kembali . '&error_promosi=' . rawurlencode('Data promosi yang akan dihapus tidak valid.'));
    exit;
}

try {
    $stmtCek = mysqli_prepare(
        $conn,
        "SELECT departemen_lama_snapshot, posisi_lama_snapshot, departemen_baru_snapshot, posisi_baru_snapshot
         FROM histori_jabatan_karyawan
         WHERE id = ? AND karyawan_id = ?"
    );
    if (!$stmtCek) {
        throw new RuntimeException('Gagal menyiapkan query pengecekan data promosi.');
    }
    mysqli_stmt_bind_param($stmtCek, 'ii', $historiId, $karyawanId);
    mysqli_stmt_execute($stmtCek);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCek));
    mysqli_stmt_close($stmtCek);

    if (!$data) {
        throw new RuntimeException('Riwayat promosi tidak ditemukan atau sudah dihapus.');
    }

    if (!hapusHistoriJabatan($conn, $historiId, $karyawanId)) {
        throw new RuntimeException('Gagal menghapus riwayat promosi.');
    }

    catatAktivitas(
        $conn,
        sprintf(
            'Menghapus riwayat jabatan karyawan ID %d (%s - %s ke %s - %s).',
            $karyawanId,
            (string) $data['departemen_lama_snapshot'],
            (string) $data['posisi_lama_snapshot'],
            (string) $data['departemen_baru_snapshot'],
            (string) $data['posisi_baru_snapshot']
        )
    );

    header('Location: ' . $kembali . '&pesan=' . rawurlencode('Riwayat promosi berhasil dihapus.'));
    exit;
} catch (Throwable $e) {
    header('Location: ' . $kembali . '&error_promosi=' . rawurlencode($e->getMessage()));
    exit;
}
