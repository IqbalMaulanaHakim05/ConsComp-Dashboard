ALTER TABLE master_departemen
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER nama;

ALTER TABLE karyawan
    ADD COLUMN department_id INT UNSIGNED NULL AFTER department;

UPDATE karyawan k
INNER JOIN master_departemen d ON d.nama = k.department
SET k.department_id = d.id
WHERE k.department_id IS NULL;

CREATE TABLE IF NOT EXISTS riwayat_pendidikan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    institusi VARCHAR(200) NOT NULL,
    jenjang VARCHAR(100) NULL,
    jurusan VARCHAR(150) NULL,
    tanggal_mulai DATE NULL,
    tanggal_selesai DATE NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_riwayat_pendidikan_karyawan (karyawan_id),
    CONSTRAINT fk_riwayat_pendidikan_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS riwayat_pekerjaan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    karyawan_id INT NOT NULL,
    nama_perusahaan VARCHAR(200) NOT NULL,
    posisi VARCHAR(150) NULL,
    departemen VARCHAR(150) NULL,
    tanggal_mulai DATE NULL,
    tanggal_selesai DATE NULL,
    deskripsi TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_riwayat_pekerjaan_karyawan (karyawan_id),
    CONSTRAINT fk_riwayat_pekerjaan_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO riwayat_pendidikan (karyawan_id, institusi, tanggal_selesai)
SELECT id, TRIM(riwayat_pendidikan), tanggal_riwayat_pendidikan
FROM karyawan
WHERE COALESCE(TRIM(riwayat_pendidikan), '') <> ''
  AND NOT EXISTS (
      SELECT 1 FROM riwayat_pendidikan rp
      WHERE rp.karyawan_id = karyawan.id
  );

INSERT INTO riwayat_pekerjaan (karyawan_id, nama_perusahaan, tanggal_selesai)
SELECT id, TRIM(riwayat_pekerjaan), tanggal_riwayat_pekerjaan
FROM karyawan
WHERE COALESCE(TRIM(riwayat_pekerjaan), '') <> ''
  AND NOT EXISTS (
      SELECT 1 FROM riwayat_pekerjaan rp
      WHERE rp.karyawan_id = karyawan.id
  );

ALTER TABLE karyawan
    ADD CONSTRAINT fk_karyawan_department FOREIGN KEY (department_id) REFERENCES master_departemen (id) ON DELETE RESTRICT;
