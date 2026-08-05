<?php
declare(strict_types=1);
function siapkanAudit(mysqli $conn): void { mysqli_query($conn, "CREATE TABLE IF NOT EXISTS audit_aktivitas (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, aktivitas VARCHAR(255) NOT NULL, dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }
function catatAktivitas(mysqli $conn, string $aktivitas): void { siapkanAudit($conn); $userId=(int)($_SESSION['user']['id']??0); $stmt=mysqli_prepare($conn,'INSERT INTO audit_aktivitas (user_id, aktivitas) VALUES (?, ?)'); if($stmt){mysqli_stmt_bind_param($stmt,'is',$userId,$aktivitas);mysqli_stmt_execute($stmt);mysqli_stmt_close($stmt);} }
