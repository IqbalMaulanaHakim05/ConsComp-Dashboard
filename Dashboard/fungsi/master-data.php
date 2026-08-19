<?php
declare(strict_types=1);

function siapkanMasterData(mysqli $conn): void
{
    // Normalisasi nilai lama agar opsi aktif hanya satu.
    mysqli_query($conn, "UPDATE karyawan SET employment_status = 'Aktif' WHERE LOWER(employment_status) IN ('active', 'aktif')");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_departemen (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_posisi (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_posisi_departemen (posisi_id INT UNSIGNED NOT NULL, department_id INT UNSIGNED NOT NULL, PRIMARY KEY (posisi_id, department_id), FOREIGN KEY (posisi_id) REFERENCES master_posisi(id) ON DELETE CASCADE, FOREIGN KEY (department_id) REFERENCES master_departemen(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_status_kerja (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_agama (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Master data dikelola sepenuhnya dari halaman Master Data. Jangan mengisi
    // ulang nilai bawaan setiap halaman dibuka karena item yang dihapus akan
    // muncul kembali secara otomatis.
    // Hapus label lama lebih dulu agar tidak melanggar UNIQUE saat normalisasi.
    mysqli_query($conn, "DELETE FROM master_status_kerja WHERE LOWER(nama) = 'active'");
    mysqli_query($conn, "UPDATE master_status_kerja SET nama = 'Aktif' WHERE LOWER(nama) = 'aktif' AND nama <> 'Aktif'");
    foreach ([["master_departemen", "department"], ["master_posisi", "position"], ["master_status_kerja", "employment_status"], ["master_agama", "agama"]] as [$table, $column]) {
        $result = mysqli_query($conn, "SELECT DISTINCT `$column` AS nama FROM karyawan WHERE `$column` IS NOT NULL AND `$column` <> ''");
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $safe = mysqli_real_escape_string($conn, (string) $row["nama"]);
            mysqli_query($conn, "INSERT IGNORE INTO `$table` (nama) VALUES ('$safe')");
        }
    }
    // Pulihkan foreign key departemen untuk data lama maupun karyawan baru
    // yang sebelumnya hanya menyimpan nama departemen.
    mysqli_query($conn, "UPDATE karyawan k INNER JOIN master_departemen d ON d.nama = k.department SET k.department_id = d.id WHERE k.department_id IS NULL OR k.department_id <> d.id");
    mysqli_query($conn, "INSERT IGNORE INTO master_posisi_departemen (posisi_id, department_id) SELECT p.id, k.department_id FROM master_posisi p INNER JOIN karyawan k ON k.position = p.nama WHERE k.department_id IS NOT NULL");
}

function ambilMasterData(mysqli $conn, string $jenis): array
{
    siapkanMasterData($conn);
    $tables = ["department" => "master_departemen", "position" => "master_posisi", "employment_status" => "master_status_kerja", "agama" => "master_agama"];
    $table = $tables[$jenis] ?? "master_departemen";
    $result = mysqli_query($conn, "SELECT nama FROM `$table` ORDER BY nama ASC");
    $items = [];
    while ($result && ($row = mysqli_fetch_assoc($result))) $items[] = $row["nama"];
    return $items;
}

function ambilPosisiPerDepartemen(mysqli $conn, ?int $departmentId = null): array
{
    siapkanMasterData($conn);
    $sql = "SELECT r.department_id, p.nama AS posisi FROM master_posisi_departemen r INNER JOIN master_posisi p ON p.id = r.posisi_id INNER JOIN master_departemen d ON d.id = r.department_id WHERE d.is_active = 1";
    if ($departmentId !== null) $sql .= " AND r.department_id = " . (int) $departmentId;
    $sql .= " ORDER BY p.nama";
    $result = mysqli_query($conn, $sql);
    $items = [];
    while ($result && ($row = mysqli_fetch_assoc($result))) $items[(string) $row["department_id"]][] = (string) $row["posisi"];

    // Departemen baru belum memiliki karyawan, sehingga belum punya relasi
    // posisi. Sediakan seluruh posisi aktif sebagai pilihan awal.
    $departemenResult = mysqli_query($conn, "SELECT id FROM master_departemen WHERE is_active = 1" . ($departmentId !== null ? " AND id = " . (int) $departmentId : ""));
    $posisiResult = mysqli_query($conn, "SELECT nama FROM master_posisi ORDER BY nama");
    $semuaPosisi = [];
    while ($posisiResult && ($row = mysqli_fetch_assoc($posisiResult))) $semuaPosisi[] = (string) $row["nama"];
    while ($departemenResult && ($row = mysqli_fetch_assoc($departemenResult))) {
        $idDepartemen = (string) $row["id"];
        if (!isset($items[$idDepartemen])) $items[$idDepartemen] = $semuaPosisi;
    }
    return $items;
}

function ambilDepartemenPilihan(mysqli $conn, ?int $departmentId = null): array
{
    siapkanMasterData($conn);
    $sql = "SELECT id, nama FROM master_departemen WHERE is_active = 1";
    if ($departmentId !== null) $sql .= " AND id = " . (int) $departmentId;
    $sql .= " ORDER BY nama";
    $result = mysqli_query($conn, $sql);
    $items = [];
    while ($result && ($row = mysqli_fetch_assoc($result))) $items[(string) $row["id"]] = (string) $row["nama"];
    return $items;
}

function buatIdKaryawanOtomatis(mysqli $conn): string
{
    $result = mysqli_query($conn, "SELECT emp_id FROM karyawan WHERE emp_id REGEXP '^EMP[0-9]+$'");
    $max = 0;
    while ($result && ($row = mysqli_fetch_assoc($result))) $max = max($max, (int) substr($row["emp_id"], 3));
    return "EMP" . str_pad((string) ($max + 1), 3, "0", STR_PAD_LEFT);
}
