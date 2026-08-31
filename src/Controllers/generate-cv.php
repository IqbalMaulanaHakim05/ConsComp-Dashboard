<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Employee/media-karyawan.php';
require_once __DIR__ . '/../Utils/cv-generator.php';

wajibRole("admin", "superadmin");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Allow: POST");
    exit("Metode permintaan tidak diizinkan.");
}

if (!csrfValid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Token keamanan tidak valid. Muat ulang halaman profil lalu coba kembali.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(422);
    exit("ID karyawan tidak valid.");
}

$stmt = mysqli_prepare($conn, "SELECT * FROM karyawan WHERE id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    exit("Data karyawan tidak dapat dibaca.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
mysqli_stmt_close($stmt);

if (!$karyawan) {
    http_response_code(404);
    exit("Data karyawan tidak ditemukan.");
}

$riwayatPendidikan = [];
$hasilPendidikan = mysqli_query($conn, "SELECT institusi, jenjang, jurusan, tanggal_mulai, tanggal_selesai, keterangan FROM riwayat_pendidikan WHERE karyawan_id = " . (int) $id . " ORDER BY COALESCE(tanggal_mulai, tanggal_selesai) DESC, id DESC");
if ($hasilPendidikan) while ($item = mysqli_fetch_assoc($hasilPendidikan)) $riwayatPendidikan[] = $item;
$riwayatPekerjaan = [];
$hasilPekerjaan = mysqli_query($conn, "SELECT nama_perusahaan, posisi, departemen, tanggal_mulai, tanggal_selesai, deskripsi FROM riwayat_pekerjaan WHERE karyawan_id = " . (int) $id . " ORDER BY COALESCE(tanggal_mulai, tanggal_selesai) DESC, id DESC");
if ($hasilPekerjaan) while ($item = mysqli_fetch_assoc($hasilPekerjaan)) $riwayatPekerjaan[] = $item;

$folderCv = __DIR__ . '/../../storage/uploads' . DIRECTORY_SEPARATOR . "cv";
if (!is_dir($folderCv) && !mkdir($folderCv, 0755, true) && !is_dir($folderCv)) {
    http_response_code(500);
    exit("Folder penyimpanan CV tidak dapat dibuat.");
}

$namaFile = sprintf(
    "cv-generated-%d-%s-%s.pdf",
    $id,
    date("Ymd-His"),
    bin2hex(random_bytes(4))
);
$pathAkhir = $folderCv . DIRECTORY_SEPARATOR . $namaFile;
$pathSementara = $pathAkhir . ".tmp";
$fileCvLama = basename((string) ($karyawan["file_cv_generated"] ?? ""));
$transaksiAktif = false;

try {
    $pdf = buatPdfCv($karyawan, $riwayatPendidikan, $riwayatPekerjaan);
    if (file_put_contents($pathSementara, $pdf, LOCK_EX) === false) {
        throw new RuntimeException("CV sementara gagal disimpan.");
    }

    if (!rename($pathSementara, $pathAkhir)) {
        throw new RuntimeException("CV gagal dipindahkan ke penyimpanan akhir.");
    }

    mysqli_begin_transaction($conn);
    $transaksiAktif = true;
    $update = mysqli_prepare($conn, "UPDATE karyawan SET file_cv_generated = ? WHERE id = ?");
    if (!$update) {
        throw new RuntimeException("Pembaruan profil karyawan gagal disiapkan.");
    }

    mysqli_stmt_bind_param($update, "si", $namaFile, $id);
    if (!mysqli_stmt_execute($update) || mysqli_stmt_affected_rows($update) < 0) {
        mysqli_stmt_close($update);
        throw new RuntimeException("Profil karyawan gagal diperbarui.");
    }
    mysqli_stmt_close($update);
    mysqli_commit($conn);
    $transaksiAktif = false;

    catatAktivitas(
        $conn,
        "Membuat CV PDF untuk " . (string) ($karyawan["employee_name"] ?? "karyawan") . " (" . (string) ($karyawan["emp_id"] ?? $id) . ")."
    );

    if (
        $fileCvLama !== ""
        && $fileCvLama !== $namaFile
        && str_starts_with($fileCvLama, "cv-generated-")
    ) {
        $pathLama = $folderCv . DIRECTORY_SEPARATOR . $fileCvLama;
        if (is_file($pathLama)) {
            @unlink($pathLama);
        }
    }

    // Buka file PDF secara inline di browser. Tanpa header "attachment",
    // browser akan menampilkan preview dan pengguna dapat mengunduhnya
    // sendiri dari toolbar PDF bila diperlukan.
    header("Location: " . URL_DASAR . "file.php?jenis=cv&file=" . rawurlencode($namaFile));
    exit;
} catch (Throwable $exception) {
    if ($transaksiAktif) {
        @mysqli_rollback($conn);
    }
    if (is_file($pathSementara)) {
        @unlink($pathSementara);
    }
    if (is_file($pathAkhir)) {
        @unlink($pathAkhir);
    }

    error_log("Gagal membuat CV karyawan {$id}: " . $exception->getMessage());
    http_response_code(500);
    exit("CV gagal dibuat. Silakan coba kembali atau periksa konfigurasi generator PDF.");
}
