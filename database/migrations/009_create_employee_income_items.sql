CREATE TABLE IF NOT EXISTS pendapatan_tambahan_karyawan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    nama VARCHAR(150) NOT NULL,
    nilai DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pendapatan_tambahan_karyawan (karyawan_id),
    CONSTRAINT fk_pendapatan_tambahan_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
