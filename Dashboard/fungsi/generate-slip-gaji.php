<?php

declare(strict_types=1);

require dirname(__DIR__) . '/koneksi.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/slip-gaji.php';

wajibRole('admin', 'superadmin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Permintaan tidak valid.');
}

$karyawanId = (int) ($_POST['id'] ?? 0);
$bulan = max(1, min(12, (int) ($_POST['bulan'] ?? date('n'))));
$tahun = max(2000, min(2100, (int) ($_POST['tahun'] ?? date('Y'))));

try {
    $data = dataSlipGajiKaryawan($conn, $karyawanId, $bulan, $tahun);
    $pdf = buatPdfSlipGaji($data);
} catch (InvalidArgumentException | RuntimeException $exception) {
    http_response_code(422);
    exit($exception->getMessage());
} catch (Throwable) {
    http_response_code(500);
    exit('Slip gaji gagal dibuat.');
}

$namaAman = trim((string) preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $data['karyawan']['emp_id']), '-');
$namaFile = sprintf('slip-gaji-%s-%04d-%02d.pdf', $namaAman !== '' ? $namaAman : $karyawanId, $tahun, $bulan);

header('Content-Type: application/pdf');
header('Content-Length: ' . strlen($pdf));
header('Content-Disposition: inline; filename="' . $namaFile . '"');
header('X-Content-Type-Options: nosniff');
echo $pdf;
