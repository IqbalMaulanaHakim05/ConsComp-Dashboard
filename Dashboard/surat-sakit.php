<?php
declare(strict_types=1);
require __DIR__ . '/koneksi.php';
require_once __DIR__ . '/fungsi/auth.php';
wajibLogin();
$namaFile = basename((string) ($_GET['file'] ?? ''));
if ($namaFile === '') { http_response_code(404); exit('File tidak ditemukan.'); }
$stmt = mysqli_prepare($conn, "SELECT karyawan_id FROM izin_sakit WHERE surat_sakit_file = ? OR surat_sakit_file = CONCAT('uploads/surat-sakit/', ?) LIMIT 1");
mysqli_stmt_bind_param($stmt, 'ss', $namaFile, $namaFile); mysqli_stmt_execute($stmt); $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
if (!$data || !karyawanDalamCakupan($conn, (int) $data['karyawan_id'])) { http_response_code(403); exit('Akses file ditolak.'); }
$path = __DIR__ . '/uploads/surat-sakit/' . $namaFile;
if (!is_file($path)) { http_response_code(404); exit('File tidak ditemukan.'); }
header('Content-Type: application/pdf'); header('Content-Length: ' . (string) filesize($path)); header('Content-Disposition: inline; filename="' . str_replace('"', '', $namaFile) . '"'); header('X-Content-Type-Options: nosniff'); readfile($path);
