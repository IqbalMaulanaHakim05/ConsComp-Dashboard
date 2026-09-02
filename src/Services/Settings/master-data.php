<?php
declare(strict_types=1);

function siapkanMasterData(mysqli $conn): void
{
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_aturan_denda (
        tipe VARCHAR(30) NOT NULL PRIMARY KEY,
        toleransi_menit SMALLINT UNSIGNED NOT NULL,
        batas_tingkat_1 SMALLINT UNSIGNED NOT NULL,
        batas_tingkat_2 SMALLINT UNSIGNED NOT NULL,
        pengali_tingkat_1 DECIMAL(6,2) NOT NULL,
        pengali_tingkat_2 DECIMAL(6,2) NOT NULL,
        pembagi_jam_bulanan DECIMAL(8,2) NOT NULL DEFAULT 178.00
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "INSERT IGNORE INTO master_aturan_denda
        (tipe, toleransi_menit, batas_tingkat_1, batas_tingkat_2, pengali_tingkat_1, pengali_tingkat_2, pembagi_jam_bulanan)
        VALUES ('terlambat', 10, 11, 16, 1, 2, 178), ('pulang_lebih_awal', 5, 6, 11, 1, 2, 178)");
    $kolomTipeKerja = mysqli_query($conn, "SHOW COLUMNS FROM karyawan LIKE 'tipe_kerja'");
    $punyaKolomTipeKerja = $kolomTipeKerja && mysqli_num_rows($kolomTipeKerja) > 0;
    if ($kolomTipeKerja) mysqli_free_result($kolomTipeKerja);
    if (!$punyaKolomTipeKerja) mysqli_query($conn, "ALTER TABLE karyawan ADD COLUMN tipe_kerja VARCHAR(20) NULL DEFAULT NULL AFTER employment_status");

    // Pisahkan nilai lama Kontrak/Harian/PKWT/Magang menjadi tipe kerja dan
    // normalisasi status kerja agar hanya Aktif atau Nonaktif.
    mysqli_query($conn, "UPDATE karyawan SET tipe_kerja = CASE LOWER(TRIM(employment_status)) WHEN 'harian' THEN 'Harian' WHEN 'kontrak' THEN 'Harian' WHEN 'pkwt' THEN 'PKWT' WHEN 'magang' THEN 'Magang' ELSE 'Harian' END WHERE tipe_kerja IS NULL OR TRIM(tipe_kerja) = ''");
    mysqli_query($conn, "UPDATE karyawan SET employment_status = CASE WHEN LOWER(TRIM(employment_status)) IN ('nonaktif', 'inactive') THEN 'Nonaktif' ELSE 'Aktif' END");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_departemen (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_posisi (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(120) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_posisi_departemen (posisi_id INT UNSIGNED NOT NULL, department_id INT UNSIGNED NOT NULL, PRIMARY KEY (posisi_id, department_id), FOREIGN KEY (posisi_id) REFERENCES master_posisi(id) ON DELETE CASCADE, FOREIGN KEY (department_id) REFERENCES master_departemen(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_status_kerja (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_agama (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS master_shift (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(80) NOT NULL UNIQUE, jam_mulai TIME NOT NULL, jam_selesai TIME NOT NULL, hari VARCHAR(60) NOT NULL DEFAULT 'Senin-Jumat') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Normalisasi istilah lama sekali secara idempoten. Jadwal lama tetap
    // dipertahankan pada karyawan; master hanya menyisakan pilihan "Shift".
    $kolomShiftKaryawan = mysqli_query($conn, "SHOW COLUMNS FROM karyawan LIKE 'shift_nama'");
    $punyaKolomShiftKaryawan = $kolomShiftKaryawan && mysqli_num_rows($kolomShiftKaryawan) > 0;
    if ($kolomShiftKaryawan) mysqli_free_result($kolomShiftKaryawan);
    if ($punyaKolomShiftKaryawan) {
        mysqli_query($conn, "UPDATE karyawan SET shift_nama = 'Shift' WHERE LOWER(TRIM(shift_nama)) IN ('shift pagi', 'shift malam')");
    }

    $shiftUtama = mysqli_query($conn, "SELECT id FROM master_shift WHERE LOWER(TRIM(nama)) = 'shift' LIMIT 1");
    $adaShiftUtama = $shiftUtama && mysqli_num_rows($shiftUtama) > 0;
    if ($shiftUtama) mysqli_free_result($shiftUtama);
    if (!$adaShiftUtama) {
        mysqli_query($conn, "UPDATE master_shift SET nama = 'Shift' WHERE LOWER(TRIM(nama)) = 'shift pagi' LIMIT 1");
        $shiftUtama = mysqli_query($conn, "SELECT id FROM master_shift WHERE LOWER(TRIM(nama)) = 'shift' LIMIT 1");
        $adaShiftUtama = $shiftUtama && mysqli_num_rows($shiftUtama) > 0;
        if ($shiftUtama) mysqli_free_result($shiftUtama);
    }
    if (!$adaShiftUtama) {
        mysqli_query($conn, "UPDATE master_shift SET nama = 'Shift' WHERE LOWER(TRIM(nama)) = 'shift malam' LIMIT 1");
    }
    mysqli_query($conn, "DELETE FROM master_shift WHERE LOWER(TRIM(nama)) IN ('shift pagi', 'shift malam')");
    $jumlahShift = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM master_shift"));
    if ((int) ($jumlahShift[0] ?? 0) === 0) {
        mysqli_query($conn, "INSERT INTO master_shift (nama, jam_mulai, jam_selesai, hari) VALUES ('Shift', '08:00:00', '16:30:00', 'Senin-Jumat')");
    }
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

function ambilMasterShift(mysqli $conn): array
{
    siapkanMasterData($conn);
    $hasil = mysqli_query($conn, "SELECT id, nama, TIME_FORMAT(jam_mulai, '%H:%i') AS jam_mulai, TIME_FORMAT(jam_selesai, '%H:%i') AS jam_selesai, hari FROM master_shift ORDER BY nama");
    $items = [];
    while ($hasil && ($item = mysqli_fetch_assoc($hasil))) $items[] = $item;
    return $items;
}

function ambilMasterShiftBerdasarkanNama(mysqli $conn, string $nama): ?array
{
    $stmt = mysqli_prepare($conn, "SELECT nama, TIME_FORMAT(jam_mulai, '%H:%i') AS jam_mulai, TIME_FORMAT(jam_selesai, '%H:%i') AS jam_selesai, hari FROM master_shift WHERE nama = ? LIMIT 1");
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 's', $nama);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return $data;
}

function ambilAturanDenda(mysqli $conn): array
{
    siapkanMasterData($conn);
    $hasil = mysqli_query($conn, 'SELECT * FROM master_aturan_denda ORDER BY tipe');
    $aturan = [];
    while ($hasil && ($baris = mysqli_fetch_assoc($hasil))) {
        $aturan[(string) $baris['tipe']] = $baris;
    }
    return $aturan;
}

function pilihanStatusKerja(): array
{
    return ['Aktif', 'Nonaktif'];
}

function pilihanTipeKerja(): array
{
    return ['Harian', 'Kontrak', 'PKWT', 'Magang'];
}

function daftarHariKerja(): array
{
    return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
}

function hariKerjaJadwalTerpilih(string $hari): array
{
    $hari = trim($hari);
    if ($hari === 'Senin-Jumat') return array_slice(daftarHariKerja(), 0, 5);
    $tersimpan = array_map('trim', preg_split('/,\\s*/', $hari) ?: []);
    return array_values(array_filter(daftarHariKerja(), static fn (string $namaHari): bool => in_array($namaHari, $tersimpan, true)));
}

function formatHariKerjaJadwal(string $hari): string
{
    $terpilih = hariKerjaJadwalTerpilih($hari);
    if ($terpilih === []) return '-';

    $kelompok = [];
    $awal = $terpilih[0];
    $sebelumnya = $terpilih[0];
    $indeksSebelumnya = array_search($sebelumnya, daftarHariKerja(), true);
    foreach (array_slice($terpilih, 1) as $namaHari) {
        $indeks = array_search($namaHari, daftarHariKerja(), true);
        if ($indeks === $indeksSebelumnya + 1) {
            $sebelumnya = $namaHari;
            $indeksSebelumnya = $indeks;
            continue;
        }
        $kelompok[] = $awal === $sebelumnya ? $awal : $awal . '-' . $sebelumnya;
        $awal = $namaHari;
        $sebelumnya = $namaHari;
        $indeksSebelumnya = $indeks;
    }
    $kelompok[] = $awal === $sebelumnya ? $awal : $awal . '-' . $sebelumnya;
    return implode(', ', $kelompok);
}

function jumlahHariKerjaJadwal(string $hari): int
{
    return count(hariKerjaJadwalTerpilih($hari));
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

    return $items;
}

function posisiValidUntukDepartemen(mysqli $conn, string $departement, string $posisi): bool
{
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM master_posisi_departemen r INNER JOIN master_posisi p ON p.id = r.posisi_id INNER JOIN master_departemen d ON d.id = r.department_id WHERE d.nama = ? AND p.nama = ? LIMIT 1");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, "ss", $departement, $posisi);
    mysqli_stmt_execute($stmt);
    $valid = mysqli_stmt_get_result($stmt)->num_rows > 0;
    mysqli_stmt_close($stmt);
    return $valid;
}

function ambilPosisiPerNamaDepartemen(mysqli $conn): array
{
    $hasil = [];
    $query = mysqli_query($conn, "SELECT d.nama AS departemen, p.nama AS posisi FROM master_posisi_departemen r INNER JOIN master_posisi p ON p.id = r.posisi_id INNER JOIN master_departemen d ON d.id = r.department_id ORDER BY d.nama, p.nama");
    while ($query && ($row = mysqli_fetch_assoc($query))) $hasil[(string) $row["departemen"]][] = (string) $row["posisi"];
    return $hasil;
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
    $result = mysqli_query($conn, "SELECT emp_id FROM karyawan WHERE TRIM(COALESCE(emp_id, '')) <> ''");
    $pola = [];

    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $id = trim((string) $row["emp_id"]);
        if (!preg_match('/^(.*?)(\d+)$/', $id, $cocok)) {
            continue;
        }

        $awalan = $cocok[1];
        $nomor = (int) $cocok[2];
        $lebarNomor = strlen($cocok[2]);
        if (!isset($pola[$awalan])) {
            $pola[$awalan] = ["jumlah" => 0, "maksimum" => 0, "lebar" => $lebarNomor];
        }

        $pola[$awalan]["jumlah"]++;
        $pola[$awalan]["maksimum"] = max($pola[$awalan]["maksimum"], $nomor);
        $pola[$awalan]["lebar"] = max($pola[$awalan]["lebar"], $lebarNomor);
    }
    if ($result) {
        mysqli_free_result($result);
    }

    if ($pola === []) {
        return "EMP001";
    }

    uasort(
        $pola,
        static fn (array $a, array $b): int => $b["jumlah"] <=> $a["jumlah"]
            ?: $b["maksimum"] <=> $a["maksimum"]
    );
    $awalan = (string) array_key_first($pola);
    $dataPola = $pola[$awalan];
    return $awalan . str_pad((string) ($dataPola["maksimum"] + 1), $dataPola["lebar"], "0", STR_PAD_LEFT);
}
