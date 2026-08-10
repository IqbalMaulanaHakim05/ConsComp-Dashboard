ALTER TABLE users
    MODIFY COLUMN role ENUM('superadmin', 'admin', 'pic', 'koordinator', 'manager', 'viewer') NOT NULL DEFAULT 'viewer',
    ADD COLUMN department_id INT UNSIGNED NULL AFTER role,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER department_id;

ALTER TABLE users
    ADD CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES master_departemen (id) ON DELETE RESTRICT;
