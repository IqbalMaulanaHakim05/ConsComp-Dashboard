<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Payroll/slip-gaji.php';

wajibRole("admin", "superadmin");

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    die("ID tidak valid.");
}

// Ambil info karyawan dan file-file yang terlampir
$stmtAmbil = mysqli_prepare(
    $conn,
    "SELECT id, employee_name, emp_id, foto_profil, file_cv, file_ijazah, file_mcu, file_cv_generated FROM karyawan WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmtAmbil, "i", $id);
mysqli_stmt_execute($stmtAmbil);
$karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtAmbil));
mysqli_stmt_close($stmtAmbil);

if (!$karyawan) {
    header('Location: karyawan.php');
    exit;
}

// Ambil daftar file slip gaji PDF karyawan jika ada
$slipFiles = [];
$stmtSlip = mysqli_prepare(
    $conn,
    "SELECT nama_file FROM slip_gaji WHERE karyawan_id = ? AND nama_file IS NOT NULL AND nama_file <> ''"
);
if ($stmtSlip) {
    mysqli_stmt_bind_param($stmtSlip, "i", $id);
    mysqli_stmt_execute($stmtSlip);
    $resSlip = mysqli_stmt_get_result($stmtSlip);
    while ($row = mysqli_fetch_assoc($resSlip)) {
        if (!empty($row["nama_file"])) {
            $slipFiles[] = (string) $row["nama_file"];
        }
    }
    mysqli_stmt_close($stmtSlip);
}

// Eksekusi penghapusan database dalam transaksi
mysqli_begin_transaction($conn);

try {
    // Bersihkan item antrean batch slip gaji jika ada
    $stmtBatchItem = mysqli_prepare($conn, "DELETE FROM slip_gaji_batch_item WHERE karyawan_id = ?");
    if ($stmtBatchItem) {
        mysqli_stmt_bind_param($stmtBatchItem, "i", $id);
        mysqli_stmt_execute($stmtBatchItem);
        mysqli_stmt_close($stmtBatchItem);
    }

    // Hapus karyawan (seluruh tabel relasi akan ter-cascade secara otomatis)
    $stmt = mysqli_prepare($conn, "DELETE FROM karyawan WHERE id = ?");
    if (!$stmt) {
        throw new RuntimeException("Gagal menyiapkan statement: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    die("Data gagal dihapus: " . htmlspecialchars($e->getMessage()));
}

// Hapus file fisik dari server setelah penghapusan database berhasil
$uploadDir = dirname(__DIR__, 2) . '/storage/uploads';
$hapusFile = static function (string $folder, ?string $namaFile) use ($uploadDir): void {
    if (!$namaFile) {
        return;
    }
    $path = $uploadDir . "/" . $folder . "/" . basename($namaFile);
    if (is_file($path)) {
        @unlink($path);
    }
};

$hapusFile("foto", $karyawan["foto_profil"] ?? null);
$hapusFile("cv", $karyawan["file_cv"] ?? null);
$hapusFile("cv", $karyawan["file_cv_generated"] ?? null);
$hapusFile("ijazah", $karyawan["file_ijazah"] ?? null);
$hapusFile("mcu", $karyawan["file_mcu"] ?? null);

foreach ($slipFiles as $fileSlip) {
    $pathSlip = pathFileSlipGaji($fileSlip);
    if ($pathSlip !== null && is_file($pathSlip)) {
        @unlink($pathSlip);
    }
}

$namaKaryawan = trim((string) ($karyawan["employee_name"] ?? ""));
$empId = trim((string) ($karyawan["emp_id"] ?? ""));
$keterangan = "Menghapus data karyawan " . ($namaKaryawan !== "" ? $namaKaryawan : ("ID " . $id)) . ($empId !== "" ? " ({$empId})" : "") . ".";
catatAktivitas($conn, $keterangan);

header('Location: karyawan.php');
exit;
