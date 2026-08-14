<?php

declare(strict_types=1);

/**
 * Menyiapkan penyimpanan izin meninggalkan pekerjaan.
 *
 * Tabel dibuat terpisah dari izin cuti karena kebutuhan izin cuti belum
 * ditentukan.
 */
function siapkanTabelIzinKaryawan(mysqli $conn): bool
{
    return mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS izin_meninggalkan_pekerjaan (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            karyawan_id INT NOT NULL,
            department_id INT UNSIGNED NOT NULL,
            jam_mulai TIME NOT NULL,
            durasi_menit SMALLINT UNSIGNED NOT NULL,
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
            INDEX idx_izin_karyawan (karyawan_id),
            INDEX idx_izin_department (department_id),
            INDEX idx_izin_status (status),
            INDEX idx_izin_pengganti (karyawan_pengganti_id),
            CONSTRAINT fk_izin_karyawan
                FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE RESTRICT,
            CONSTRAINT fk_izin_department
                FOREIGN KEY (department_id) REFERENCES master_departemen(id) ON DELETE RESTRICT,
            CONSTRAINT fk_izin_pengganti
                FOREIGN KEY (karyawan_pengganti_id) REFERENCES karyawan(id) ON DELETE RESTRICT,
            CONSTRAINT fk_izin_pembuat
                FOREIGN KEY (dibuat_oleh_user_id) REFERENCES users(id) ON DELETE RESTRICT,
            CONSTRAINT fk_izin_pemroses
                FOREIGN KEY (diproses_oleh_user_id) REFERENCES users(id) ON DELETE SET NULL,
            CONSTRAINT chk_izin_durasi
                CHECK (durasi_menit IN (30, 60, 120))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) !== false;
}
