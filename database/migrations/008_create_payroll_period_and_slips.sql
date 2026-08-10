CREATE TABLE IF NOT EXISTS periode_gaji (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tahun SMALLINT UNSIGNED NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL,
    status ENUM('draft', 'dikunci') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_periode_gaji_tahun_bulan (tahun, bulan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS slip_gaji (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    periode_gaji_id INT UNSIGNED NOT NULL,
    karyawan_id INT NOT NULL,
    employee_id_snapshot VARCHAR(30) NOT NULL,
    nama_snapshot VARCHAR(150) NOT NULL,
    posisi_snapshot VARCHAR(100) NULL,
    departemen_snapshot VARCHAR(100) NULL,
    total_pendapatan DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_potongan DECIMAL(15,2) NOT NULL DEFAULT 0,
    gaji_bersih DECIMAL(15,2) NOT NULL DEFAULT 0,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slip_gaji_periode_karyawan (periode_gaji_id, karyawan_id),
    CONSTRAINT fk_slip_gaji_periode FOREIGN KEY (periode_gaji_id) REFERENCES periode_gaji (id) ON DELETE RESTRICT,
    CONSTRAINT fk_slip_gaji_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE RESTRICT,
    CONSTRAINT fk_slip_gaji_generator FOREIGN KEY (generated_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS slip_gaji_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slip_gaji_id INT UNSIGNED NOT NULL,
    kategori ENUM('pendapatan', 'potongan') NOT NULL,
    kode VARCHAR(50) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL DEFAULT 0,
    sumber_reference VARCHAR(100) NULL,
    PRIMARY KEY (id),
    KEY idx_slip_gaji_items_slip (slip_gaji_id),
    CONSTRAINT fk_slip_gaji_items_slip FOREIGN KEY (slip_gaji_id) REFERENCES slip_gaji (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
