<?php

declare(strict_types=1);

function nikKaryawanSudahDigunakan(mysqli $conn, string $nik, ?int $karyawanIdDikecualikan = null): bool
{
    $nik = trim($nik);
    if ($nik === "") return false;

    if ($karyawanIdDikecualikan === null) {
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM karyawan WHERE nik = ? LIMIT 1");
        if (!$stmt) throw new RuntimeException("Pengecekan NIK gagal disiapkan.");
        mysqli_stmt_bind_param($stmt, "s", $nik);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT 1 FROM karyawan WHERE nik = ? AND id <> ? LIMIT 1");
        if (!$stmt) throw new RuntimeException("Pengecekan NIK gagal disiapkan.");
        mysqli_stmt_bind_param($stmt, "si", $nik, $karyawanIdDikecualikan);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new RuntimeException("Pengecekan NIK gagal dijalankan.");
    }

    $digunakan = mysqli_stmt_get_result($stmt)->num_rows > 0;
    mysqli_stmt_close($stmt);
    return $digunakan;
}

function pelanggaranIndeksUnikNik(int $kode, string $pesanDatabase): bool
{
    return $kode === 1062 && str_contains($pesanDatabase, "uq_karyawan_nik");
}
