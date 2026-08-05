<?php
require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";

wajibRole("admin", "superadmin");

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    die("ID tidak valid.");
}

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM karyawan WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    catatAktivitas($conn, "Menghapus data karyawan ID " . $id . ".");
    header("Location: ../index.php");
    exit;
}

die("Data gagal dihapus: " . mysqli_error($conn));