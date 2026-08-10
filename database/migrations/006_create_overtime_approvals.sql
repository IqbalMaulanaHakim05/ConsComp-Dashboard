CREATE TABLE IF NOT EXISTS overtime_approvals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    overtime_id INT UNSIGNED NOT NULL,
    tahap ENUM('koordinator', 'manager') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    approver_user_id INT NULL,
    catatan TEXT NULL,
    decided_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_overtime_approval_stage (overtime_id, tahap),
    CONSTRAINT fk_overtime_approval_report FOREIGN KEY (overtime_id) REFERENCES overtime_reports (id) ON DELETE CASCADE,
    CONSTRAINT fk_overtime_approval_user FOREIGN KEY (approver_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
