<?php
declare(strict_types=1);
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
wajibLogin();

$jenis = (string) ($_GET["jenis"] ?? "");
$namaFile = basename((string) ($_GET["file"] ?? ""));
$konfigurasi = [
    "foto" => ["kolom" => "foto_profil", "folder" => "foto", "mime" => ["image/jpeg", "image/png"]],
    "cv" => ["kolom" => "file_cv", "folder" => "cv", "mime" => ["application/pdf"]],
    "ijazah" => ["kolom" => "file_ijazah", "folder" => "ijazah", "mime" => ["application/pdf"]],
    "mcu" => ["kolom" => "file_mcu", "folder" => "mcu", "mime" => ["application/pdf"]],
];
if ($namaFile === "" || !isset($konfigurasi[$jenis])) { http_response_code(404); exit("File tidak ditemukan."); }
$kolom = $konfigurasi[$jenis]["kolom"];
$kolomQuery = $jenis === "cv" ? "(file_cv = ? OR file_cv_generated = ?)" : "$kolom = ?";
$stmt = mysqli_prepare($conn, "SELECT id, $kolom FROM karyawan WHERE $kolomQuery LIMIT 1");
if ($jenis === "cv") mysqli_stmt_bind_param($stmt, "ss", $namaFile, $namaFile);
else mysqli_stmt_bind_param($stmt, "s", $namaFile);
mysqli_stmt_execute($stmt);
$karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
if (!$karyawan || !karyawanDalamCakupan($conn, (int) $karyawan["id"])) { http_response_code(403); exit("Akses file ditolak."); }
$path = __DIR__ . '/../../storage/uploads/' . $konfigurasi[$jenis]["folder"] . "/" . $namaFile;
if (!is_file($path)) { http_response_code(404); exit("File tidak ditemukan."); }
$mime = function_exists("mime_content_type") ? (string) mime_content_type($path) : "application/octet-stream";
if (!in_array($mime, $konfigurasi[$jenis]["mime"], true)) { http_response_code(415); exit("Format file tidak diizinkan."); }
header("Content-Type: " . $mime); header("Content-Length: " . (string) filesize($path)); header("Content-Disposition: inline; filename=\"" . str_replace('"', '', $namaFile) . "\""); header("X-Content-Type-Options: nosniff"); readfile($path);
