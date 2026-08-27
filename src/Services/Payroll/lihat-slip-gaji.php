<?php

declare(strict_types=1);

require __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../Auth/auth.php';
require_once __DIR__ . '/slip-gaji.php';

wajibRole('admin', 'superadmin');

if (!siapkanPenyimpananSlipGaji($conn)) {
    http_response_code(500);
    exit('Penyimpanan slip gaji tidak tersedia.');
}

$slipId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$slipId || $slipId <= 0) {
    http_response_code(404);
    exit('Slip gaji tidak ditemukan.');
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT s.id, s.karyawan_id, s.nama_file, s.employee_id_snapshot, s.versi,
            p.bulan, p.tahun
     FROM slip_gaji s
     INNER JOIN periode_gaji p ON p.id = s.periode_gaji_id
     WHERE s.id = ? AND s.status = 'berhasil'
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, 'i', $slipId);
mysqli_stmt_execute($stmt);
$slip = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$slip) {
    http_response_code(404);
    exit('Slip gaji tidak ditemukan.');
}
if (!karyawanDalamCakupan($conn, (int) $slip['karyawan_id'])) {
    http_response_code(403);
    exit('Anda tidak memiliki akses ke slip gaji karyawan ini.');
}

$namaFile = basename((string) $slip['nama_file']);
$path = __DIR__ . '/../../../storage/uploads/slip/' . $namaFile;
if ($namaFile === '' || !is_file($path)) {
    http_response_code(404);
    exit('File slip gaji tidak ditemukan.');
}
$header = file_get_contents($path, false, null, 0, 4);
if ($header !== '%PDF') {
    http_response_code(415);
    exit('Format file slip tidak valid.');
}

$namaUnduh = sprintf(
    'slip-gaji-%s-%04d-%02d-v%d.pdf',
    trim((string) preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $slip['employee_id_snapshot']), '-'),
    (int) $slip['tahun'],
    (int) $slip['bulan'],
    (int) $slip['versi']
);
header('Content-Type: application/pdf');
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: inline; filename="' . $namaUnduh . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
