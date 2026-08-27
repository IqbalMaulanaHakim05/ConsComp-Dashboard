<?php
declare(strict_types=1);

function siapkanTabelIzinCutiKhusus(mysqli $conn): bool
{
    $sql = "CREATE TABLE IF NOT EXISTS izin_cuti_khusus (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        karyawan_id INT NOT NULL, department_id INT NOT NULL,
        tanggal_mulai DATE NOT NULL, tanggal_selesai DATE NOT NULL, total_hari DECIMAL(6,1) NOT NULL,
        deskripsi VARCHAR(150) NOT NULL, nomor_kontak VARCHAR(50) NOT NULL, karyawan_pengganti_id INT NULL,
        status ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
        tahap_persetujuan ENUM('pic','koordinator','manager','selesai') NOT NULL DEFAULT 'pic',
        dibuat_oleh_user_id INT NOT NULL, diproses_oleh_user_id INT NULL, catatan_persetujuan TEXT NULL,
        diproses_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_cuti_khusus_department (department_id), KEY idx_cuti_khusus_status (status),
        CONSTRAINT fk_cuti_khusus_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
        CONSTRAINT fk_cuti_khusus_pengganti FOREIGN KEY (karyawan_pengganti_id) REFERENCES karyawan(id) ON DELETE SET NULL,
        CONSTRAINT fk_cuti_khusus_pembuat FOREIGN KEY (dibuat_oleh_user_id) REFERENCES users(id) ON DELETE RESTRICT,
        CONSTRAINT fk_cuti_khusus_pemroses FOREIGN KEY (diproses_oleh_user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (mysqli_query($conn, $sql) !== true) return false;
    $kolom = mysqli_query($conn, "SHOW COLUMNS FROM izin_cuti_khusus LIKE 'karyawan_pengganti_id'");
    if ($kolom && mysqli_num_rows($kolom) === 0 && !mysqli_query($conn, 'ALTER TABLE izin_cuti_khusus ADD COLUMN karyawan_pengganti_id INT NULL AFTER nomor_kontak')) return false;
    if ($kolom) mysqli_free_result($kolom);
    return siapkanTahapPersetujuanIzin($conn, 'izin_cuti_khusus');
}

function hitungHariCutiKhusus(DateTimeImmutable $mulai, DateTimeImmutable $selesai): int
{
    if ($selesai < $mulai) return 0;

    $jumlah = 0;
    for ($tanggal = $mulai; $tanggal <= $selesai; $tanggal = $tanggal->modify('+1 day')) {
        if ((int) $tanggal->format('N') <= 5) $jumlah++;
    }
    return $jumlah;
}
