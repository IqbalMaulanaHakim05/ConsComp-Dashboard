<?php

declare(strict_types=1);

require __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/auth.php';

function siapkanPengaturanProfil(mysqli $conn): void
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pengaturan_profil (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        judul VARCHAR(150) NOT NULL DEFAULT 'Profil Internal',
        teks_pembuka VARCHAR(255) NOT NULL DEFAULT '',
        warna_awal CHAR(7) NOT NULL DEFAULT '#1e3a8a',
        warna_akhir CHAR(7) NOT NULL DEFAULT '#2563eb',
        tampil_foto TINYINT(1) NOT NULL DEFAULT 1,
        tampil_status TINYINT(1) NOT NULL DEFAULT 1,
        tampil_dokumen TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "INSERT IGNORE INTO pengaturan_profil (id) VALUES (1)");
}

siapkanPengaturanProfil($conn);

function ambilPengaturanProfil(mysqli $conn): array
{
    $hasil = mysqli_query($conn, 'SELECT * FROM pengaturan_profil WHERE id = 1 LIMIT 1');
    return $hasil ? (mysqli_fetch_assoc($hasil) ?: []) : [];
}

function simpanPengaturanProfil(mysqli $conn, array $data): bool
{
    $stmt = mysqli_prepare($conn, 'UPDATE pengaturan_profil SET judul = ?, teks_pembuka = ?, warna_awal = ?, warna_akhir = ?, tampil_foto = ?, tampil_status = ?, tampil_dokumen = ? WHERE id = 1');
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'ssssiii', $data['judul'], $data['teks_pembuka'], $data['warna_awal'], $data['warna_akhir'], $data['tampil_foto'], $data['tampil_status'], $data['tampil_dokumen']);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
