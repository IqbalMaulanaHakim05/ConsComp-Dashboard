<?php

declare(strict_types=1);

require_once __DIR__ . '/alur-persetujuan-izin.php';

/**
 * Menyiapkan penyimpanan izin cuti berbasis hari kerja Senin-Jumat.
 */
function siapkanTabelIzinCuti(mysqli $conn): bool
{
    $tabelSiap = mysqli_query(
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
            tahap_persetujuan ENUM('pic', 'koordinator', 'manager', 'selesai') NOT NULL DEFAULT 'pic',
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

    return $tabelSiap && siapkanTahapPersetujuanIzin($conn, 'izin_cuti');
}

/**
 * Menentukan apakah tanggal berada pada hari kerja Senin-Jumat.
 * Hari libur nasional belum dikecualikan karena aplikasi belum memiliki
 * kalender hari libur.
 */
function tanggalKerjaCuti(DateTimeImmutable $tanggal): bool
{
    return (int) $tanggal->format("N") <= 5;
}

/**
 * Menghitung jumlah hari kerja secara inklusif pada suatu rentang tanggal.
 */
function hitungHariKerjaCuti(DateTimeImmutable $tanggalMulai, DateTimeImmutable $tanggalSelesai): int
{
    if ($tanggalSelesai < $tanggalMulai) {
        return 0;
    }

    $jumlahHariKalender = (int) $tanggalMulai->diff($tanggalSelesai)->days + 1;
    $totalHariKerja = intdiv($jumlahHariKalender, 7) * 5;
    $sisaHari = $jumlahHariKalender % 7;
    $hariAwal = (int) $tanggalMulai->format("N");

    for ($offset = 0; $offset < $sisaHari; $offset++) {
        $hariDalamMinggu = (($hariAwal + $offset - 1) % 7) + 1;
        if ($hariDalamMinggu <= 5) {
            $totalHariKerja++;
        }
    }

    return $totalHariKerja;
}
