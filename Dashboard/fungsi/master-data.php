<?php
declare(strict_types=1);

function siapkanMasterData(mysqli $conn): void
{
    // Normalisasi nilai lama agar opsi aktif hanya satu.
    mysqli_query($conn, "UPDATE karyawan SET employment_status = 'Aktif' WHERE LOWER(employment_status) IN ('active', 'aktif')");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_departemen (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_posisi (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_status_kerja (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $defaults = [
        "master_departemen" => ["Teknologi Informasi", "Sumber Daya Manusia", "Keuangan", "Pemasaran", "Operasional"],
        "master_posisi" => ["Software Engineer", "HR Specialist", "Finance Analyst", "Marketing Executive", "Project Manager"],
        "master_status_kerja" => ["Aktif", "Kontrak", "Nonaktif"],
    ];
    foreach ($defaults as $table => $items) foreach ($items as $item) {
        $safe = mysqli_real_escape_string($conn, $item);
        mysqli_query($conn, "INSERT IGNORE INTO `$table` (nama) VALUES ('$safe')");
    }
    // Hapus label lama lebih dulu agar tidak melanggar UNIQUE saat normalisasi.
    mysqli_query($conn, "DELETE FROM master_status_kerja WHERE LOWER(nama) = 'active'");
    mysqli_query($conn, "UPDATE master_status_kerja SET nama = 'Aktif' WHERE LOWER(nama) = 'aktif' AND nama <> 'Aktif'");
    foreach ([["master_departemen", "department"], ["master_posisi", "position"], ["master_status_kerja", "employment_status"]] as [$table, $column]) {
        $result = mysqli_query($conn, "SELECT DISTINCT `$column` AS nama FROM karyawan WHERE `$column` IS NOT NULL AND `$column` <> ''");
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $safe = mysqli_real_escape_string($conn, (string) $row["nama"]);
            mysqli_query($conn, "INSERT IGNORE INTO `$table` (nama) VALUES ('$safe')");
        }
    }
}

function ambilMasterData(mysqli $conn, string $jenis): array
{
    siapkanMasterData($conn);
    $tables = ["department" => "master_departemen", "position" => "master_posisi", "employment_status" => "master_status_kerja"];
    $table = $tables[$jenis] ?? "master_departemen";
    $result = mysqli_query($conn, "SELECT nama FROM `$table` ORDER BY nama ASC");
    $items = [];
    while ($result && ($row = mysqli_fetch_assoc($result))) $items[] = $row["nama"];
    return $items;
}

function buatIdKaryawanOtomatis(mysqli $conn): string
{
    $result = mysqli_query($conn, "SELECT emp_id FROM karyawan WHERE emp_id REGEXP '^EMP[0-9]+$'");
    $max = 0;
    while ($result && ($row = mysqli_fetch_assoc($result))) $max = max($max, (int) substr($row["emp_id"], 3));
    return "EMP" . str_pad((string) ($max + 1), 3, "0", STR_PAD_LEFT);
}
