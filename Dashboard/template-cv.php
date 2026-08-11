<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/media-karyawan.php";
require_once __DIR__ . "/fungsi/cv-generator.php";

wajibRole("admin", "superadmin");
siapkanKolomMedia($conn);
siapkanKolomProfil($conn);

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$karyawan = null;

if ($id !== false && $id !== null && $id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM karyawan WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
        mysqli_stmt_close($stmt);
    }
}

if (!$karyawan) {
    http_response_code(404);
    exit("CV tidak dapat ditampilkan karena data karyawan tidak ditemukan.");
}

$riwayatPendidikan = [];
$hasilPendidikan = mysqli_query($conn, "SELECT institusi, jenjang, jurusan, tanggal_mulai, tanggal_selesai, keterangan FROM riwayat_pendidikan WHERE karyawan_id = " . (int) $id . " ORDER BY COALESCE(tanggal_mulai, tanggal_selesai) DESC, id DESC");
if ($hasilPendidikan) while ($item = mysqli_fetch_assoc($hasilPendidikan)) $riwayatPendidikan[] = $item;
$riwayatPekerjaan = [];
$hasilPekerjaan = mysqli_query($conn, "SELECT nama_perusahaan, posisi, departemen, tanggal_mulai, tanggal_selesai, deskripsi FROM riwayat_pekerjaan WHERE karyawan_id = " . (int) $id . " ORDER BY COALESCE(tanggal_mulai, tanggal_selesai) DESC, id DESC");
if ($hasilPekerjaan) while ($item = mysqli_fetch_assoc($hasilPekerjaan)) $riwayatPekerjaan[] = $item;

header("Content-Type: text/html; charset=UTF-8");
header("X-Robots-Tag: noindex, nofollow, noarchive");
echo buatHtmlCv($karyawan, $riwayatPendidikan, $riwayatPekerjaan);
