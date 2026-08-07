<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pengaturan halaman publik.
| Disimpan sebagai satu baris (id = 1) pada tabel pengaturan_publik.
| Tabel & baris default dibuat otomatis saat pertama kali dipakai.
|--------------------------------------------------------------------------
*/

function siapkanPengaturanPublik(mysqli $conn): void
{
    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS pengaturan_publik (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
            nama_situs VARCHAR(150) NOT NULL DEFAULT 'Profil Karyawan',
            judul_hero VARCHAR(255) NOT NULL DEFAULT 'Profil Pekerja Perusahaan',
            deskripsi_hero TEXT NOT NULL,
            teks_tombol VARCHAR(100) NOT NULL DEFAULT 'Lihat Data Karyawan',
            warna_utama CHAR(7) NOT NULL DEFAULT '#2563eb',
            warna_hero CHAR(7) NOT NULL DEFAULT '#0f172a'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    foreach (["warna_dashboard_awal" => "CHAR(7) NOT NULL DEFAULT '#1e3a8a'", "warna_dashboard_akhir" => "CHAR(7) NOT NULL DEFAULT '#2563eb'", "warna_pie_laki" => "CHAR(7) NOT NULL DEFAULT '#2563eb'", "warna_pie_perempuan" => "CHAR(7) NOT NULL DEFAULT '#ec4899'", "warna_bar_awal" => "CHAR(7) NOT NULL DEFAULT '#2563eb'", "warna_bar_akhir" => "CHAR(7) NOT NULL DEFAULT '#93c5fd'"] as $kolom => $tipe) {
        $cek = mysqli_query($conn, "SHOW COLUMNS FROM pengaturan_publik LIKE '$kolom'");
        if ($cek && mysqli_num_rows($cek) === 0) mysqli_query($conn, "ALTER TABLE pengaturan_publik ADD COLUMN `$kolom` $tipe");
    }

    mysqli_query(
        $conn,
        "INSERT IGNORE INTO pengaturan_publik (id, deskripsi_hero)
         VALUES (1, 'Website ini menyajikan informasi profil karyawan berdasarkan dataset Human Resources.')"
    );
}

function ambilPengaturanPublik(mysqli $conn): array
{
    siapkanPengaturanPublik($conn);

    $hasil = mysqli_query(
        $conn,
        "SELECT * FROM pengaturan_publik WHERE id = 1 LIMIT 1"
    );

    return $hasil ? (mysqli_fetch_assoc($hasil) ?: []) : [];
}
