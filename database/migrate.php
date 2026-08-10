<?php

declare(strict_types=1);

if (PHP_SAPI !== "cli") {
    exit("Migrasi hanya dapat dijalankan melalui CLI.\n");
}

require __DIR__ . "/../Dashboard/koneksi.php";

$directory = __DIR__ . "/migrations";
$files = glob($directory . "/*.sql") ?: [];
sort($files, SORT_STRING);

if ($files === []) {
    exit("Tidak ada file migrasi.\n");
}

mysqli_query(
    $conn,
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(20) NOT NULL PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if (mysqli_errno($conn) !== 0) {
    throw new RuntimeException("Tabel schema_migrations gagal disiapkan: " . mysqli_error($conn));
}

$hasil = mysqli_query($conn, "SELECT version FROM schema_migrations ORDER BY version");
$terpasang = [];
while ($baris = mysqli_fetch_assoc($hasil)) {
    $terpasang[(string) $baris["version"]] = true;
}

foreach ($files as $file) {
    $namaFile = basename($file, ".sql");
    if (!preg_match('/^(\d+)_([a-z0-9_]+)$/', $namaFile, $cocok)) {
        throw new RuntimeException("Nama file migrasi tidak valid: " . basename($file));
    }

    $version = str_pad($cocok[1], 3, "0", STR_PAD_LEFT);
    $name = $cocok[2];
    if (isset($terpasang[$version])) {
        echo "Lewati {$version}_{$name}\n";
        continue;
    }

    $sql = trim((string) file_get_contents($file));
    if ($sql === "") {
        throw new RuntimeException("File migrasi kosong: " . basename($file));
    }

    mysqli_begin_transaction($conn);
    try {
        if (!mysqli_multi_query($conn, $sql)) {
            throw new RuntimeException(mysqli_error($conn));
        }
        while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
            if ($error = mysqli_error($conn)) {
                throw new RuntimeException($error);
            }
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO schema_migrations (version, name) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $version, $name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_commit($conn);
        echo "Berhasil {$version}_{$name}\n";
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        throw new RuntimeException("Migrasi {$version}_{$name} gagal: " . $exception->getMessage(), 0, $exception);
    }
}

echo "Migrasi selesai.\n";
