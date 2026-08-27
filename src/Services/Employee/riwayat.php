<?php
declare(strict_types=1);
require __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../Auth/auth.php';
wajibRole("admin", "superadmin");
$id = (int) ($_POST["karyawan_id"] ?? 0); $jenis = (string) ($_POST["jenis"] ?? ""); $aksi = (string) ($_POST["aksi"] ?? "");
if (!csrfValid($_POST["csrf_token"] ?? null) || $id < 1 || !in_array($jenis, ["pendidikan", "pekerjaan"], true)) { http_response_code(403); exit("Permintaan tidak valid."); }
if ($aksi === "hapus") {
    $riwayatId = (int) ($_POST["riwayat_id"] ?? 0); $table = $jenis === "pendidikan" ? "riwayat_pendidikan" : "riwayat_pekerjaan";
    $stmt = mysqli_prepare($conn, "DELETE FROM `$table` WHERE id = ? AND karyawan_id = ?"); mysqli_stmt_bind_param($stmt, "ii", $riwayatId, $id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
} elseif ($aksi === "tambah") {
    if ($jenis === "pendidikan") {
        $stmt = mysqli_prepare($conn, "INSERT INTO riwayat_pendidikan (karyawan_id, institusi, jenjang, jurusan, tanggal_mulai, tanggal_selesai, keterangan) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)");
        $institusi = trim((string) ($_POST["institusi"] ?? "")); $jenjang = trim((string) ($_POST["jenjang"] ?? "")); $jurusan = trim((string) ($_POST["jurusan"] ?? "")); $mulai = trim((string) ($_POST["tanggal_mulai"] ?? "")); $selesai = trim((string) ($_POST["tanggal_selesai"] ?? "")); $keterangan = trim((string) ($_POST["keterangan"] ?? ""));
        mysqli_stmt_bind_param($stmt, "issssss", $id, $institusi, $jenjang, $jurusan, $mulai, $selesai, $keterangan);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO riwayat_pekerjaan (karyawan_id, nama_perusahaan, posisi, departemen, tanggal_mulai, tanggal_selesai, deskripsi) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)");
        $perusahaan = trim((string) ($_POST["nama_perusahaan"] ?? "")); $posisi = trim((string) ($_POST["posisi"] ?? "")); $departemen = trim((string) ($_POST["departemen"] ?? "")); $mulai = trim((string) ($_POST["tanggal_mulai"] ?? "")); $selesai = trim((string) ($_POST["tanggal_selesai"] ?? "")); $deskripsi = trim((string) ($_POST["deskripsi"] ?? ""));
        mysqli_stmt_bind_param($stmt, "issssss", $id, $perusahaan, $posisi, $departemen, $mulai, $selesai, $deskripsi);
    }
    mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
}
header("Location: ../profil-karyawan.php?id=" . $id . "&edit=1"); exit;
