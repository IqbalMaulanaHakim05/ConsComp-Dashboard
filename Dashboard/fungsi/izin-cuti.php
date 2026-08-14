<?php

declare(strict_types=1);

/**
 * Menyiapkan penyimpanan izin cuti berbasis hari kalender.
 */
function siapkanTabelIzinCuti(mysqli $conn): bool
{
    return mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS izin_cuti (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            karyawan_id INT NOT NULL,
            department_id INT UNSIGNED NOT NULL,
            tanggal_mulai DATE NOT NULL,
            tanggal_selesai DATE NOT NULL,
            jenis_cuti ENUM('harian', 'setengah_hari') NOT NULL DEFAULT 'harian',
            periode_setengah_hari ENUM('pagi', 'siang') NULL,
            total_hari DECIMAL(6,1) NOT NULL,
            deskripsi TEXT NOT NULL,
            nomor_kontak VARCHAR(50) NOT NULL,
            karyawan_pengganti_id INT NOT NULL,
            status ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
            dibuat_oleh_user_id INT NOT NULL,
            diproses_oleh_user_id INT NULL,
            catatan_persetujuan TEXT NULL,
            diproses_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cuti_karyawan (karyawan_id),
            INDEX idx_cuti_department (department_id),
            INDEX idx_cuti_status (status),
            INDEX idx_cuti_pengganti (karyawan_pengganti_id),
            CONSTRAINT fk_cuti_karyawan
                FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE RESTRICT,
            CONSTRAINT fk_cuti_department
                FOREIGN KEY (department_id) REFERENCES master_departemen(id) ON DELETE RESTRICT,
            CONSTRAINT fk_cuti_pengganti
                FOREIGN KEY (karyawan_pengganti_id) REFERENCES karyawan(id) ON DELETE RESTRICT,
            CONSTRAINT fk_cuti_pembuat
                FOREIGN KEY (dibuat_oleh_user_id) REFERENCES users(id) ON DELETE RESTRICT,
            CONSTRAINT fk_cuti_pemroses
                FOREIGN KEY (diproses_oleh_user_id) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT chk_cuti_rentang
                CHECK (tanggal_selesai >= tanggal_mulai),
            CONSTRAINT chk_cuti_jenis
                CHECK (
                    (jenis_cuti = 'harian' AND periode_setengah_hari IS NULL AND total_hari >= 1)
                    OR
                    (jenis_cuti = 'setengah_hari' AND tanggal_selesai = tanggal_mulai
                        AND periode_setengah_hari IN ('pagi', 'siang') AND total_hari = 0.5)
                )
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) !== false;
}
