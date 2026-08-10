CREATE TABLE IF NOT EXISTS overtime_compensations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    overtime_id INT UNSIGNED NOT NULL,
    metode_perhitungan ENUM('per_jam', 'nominal_final') NOT NULL,
    tarif_per_jam DECIMAL(15,2) NULL,
    jumlah_upah DECIMAL(15,2) NOT NULL,
    dimasukkan_oleh_pic INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_overtime_compensation_report (overtime_id),
    CONSTRAINT fk_overtime_compensation_report FOREIGN KEY (overtime_id) REFERENCES overtime_reports (id) ON DELETE CASCADE,
    CONSTRAINT fk_overtime_compensation_pic FOREIGN KEY (dimasukkan_oleh_pic) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
