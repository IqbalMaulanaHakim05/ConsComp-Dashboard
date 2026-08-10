CREATE TABLE IF NOT EXISTS profil_gaji (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    gaji_pokok DECIMAL(15,2) NOT NULL DEFAULT 0,
    uang_makan DECIMAL(15,2) NOT NULL DEFAULT 0,
    berlaku_mulai DATE NOT NULL,
    berlaku_sampai DATE NULL,
    created_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_profil_gaji_karyawan (karyawan_id),
    CONSTRAINT fk_profil_gaji_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE,
    CONSTRAINT fk_profil_gaji_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jenis_komponen_gaji (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode VARCHAR(50) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    kategori ENUM('pendapatan', 'potongan') NOT NULL,
    metode ENUM('nominal', 'persentase') NOT NULL DEFAULT 'nominal',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jenis_komponen_gaji_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS komponen_gaji_karyawan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    profil_gaji_id INT UNSIGNED NOT NULL,
    jenis_komponen_id INT UNSIGNED NOT NULL,
    nilai DECIMAL(15,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_komponen_gaji_karyawan (profil_gaji_id, jenis_komponen_id),
    CONSTRAINT fk_komponen_gaji_profil FOREIGN KEY (profil_gaji_id) REFERENCES profil_gaji (id) ON DELETE CASCADE,
    CONSTRAINT fk_komponen_gaji_jenis FOREIGN KEY (jenis_komponen_id) REFERENCES jenis_komponen_gaji (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO profil_gaji (karyawan_id, gaji_pokok, berlaku_mulai)
SELECT id, COALESCE(salary, 0), COALESCE(date_of_hire, CURRENT_DATE)
FROM karyawan k
WHERE NOT EXISTS (
    SELECT 1 FROM profil_gaji pg WHERE pg.karyawan_id = k.id
);
