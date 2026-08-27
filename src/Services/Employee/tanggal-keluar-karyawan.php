<?php

declare(strict_types=1);

/** Menyiapkan kolom tanggal keluar untuk kebutuhan riwayat dan analisis karyawan. */
function siapkanTanggalKeluarKaryawan(mysqli $conn): void
{
    $hasil = mysqli_query($conn, "SHOW COLUMNS FROM karyawan LIKE 'date_of_exit'");
    if ($hasil && mysqli_num_rows($hasil) > 0) {
        mysqli_free_result($hasil);
        return;
    }
    if ($hasil) mysqli_free_result($hasil);

    if (!mysqli_query($conn, "ALTER TABLE karyawan ADD COLUMN date_of_exit DATE NULL AFTER date_of_hire")) {
        throw new RuntimeException("Kolom tanggal keluar gagal disiapkan: " . mysqli_error($conn));
    }
}
