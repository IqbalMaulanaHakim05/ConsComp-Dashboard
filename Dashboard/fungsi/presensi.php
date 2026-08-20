<?php

declare(strict_types=1);

require_once __DIR__ . '/audit.php';

/**
 * Menyiapkan struktur tabel presensi_karyawan pada database.
 */
function siapkanTabelPresensi(mysqli $conn): bool
{
    $sql = "CREATE TABLE IF NOT EXISTS presensi_karyawan (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        karyawan_id INT NOT NULL,
        department_id INT UNSIGNED NOT NULL,
        tanggal DATE NOT NULL,
        jam_masuk TIME NULL,
        jam_keluar TIME NULL,
        status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpa') NOT NULL DEFAULT 'hadir',
        keterangan TEXT NULL,
        sumber_data ENUM('manual', 'mesin_fingerprint', 'api_mobile', 'webhook') NOT NULL DEFAULT 'manual',
        external_log_id VARCHAR(100) NULL,
        dibuat_oleh_user_id INT NULL,
        diubah_oleh_user_id INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_karyawan_tanggal (karyawan_id, tanggal),
        INDEX idx_presensi_tanggal (tanggal),
        INDEX idx_presensi_dept (department_id),
        INDEX idx_presensi_status (status),
        CONSTRAINT fk_presensi_karyawan
            FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
        CONSTRAINT fk_presensi_dept
            FOREIGN KEY (department_id) REFERENCES master_departemen(id) ON DELETE RESTRICT,
        CONSTRAINT fk_presensi_pembuat
            FOREIGN KEY (dibuat_oleh_user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_presensi_pengubah
            FOREIGN KEY (diubah_oleh_user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return mysqli_query($conn, $sql) !== false;
}

/**
 * Label status presensi untuk antarmuka pengguna.
 */
function labelStatusPresensi(string $status): string
{
    return match (strtolower($status)) {
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpa' => 'Alpa',
        default => ucfirst($status),
    };
}

/**
 * Mengambil satu data presensi berdasarkan ID.
 */
function ambilPresensiById(mysqli $conn, int $id): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.*, k.emp_id, k.employee_name, k.position, k.department,
                d.nama AS nama_departemen,
                u_buat.nama AS nama_pembuat,
                u_ubah.nama AS nama_pengubah
         FROM presensi_karyawan p
         INNER JOIN karyawan k ON k.id = p.karyawan_id
         INNER JOIN master_departemen d ON d.id = p.department_id
         LEFT JOIN users u_buat ON u_buat.id = p.dibuat_oleh_user_id
         LEFT JOIN users u_ubah ON u_ubah.id = p.diubah_oleh_user_id
         WHERE p.id = ?"
    );

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($hasil) ?: null;
    mysqli_stmt_close($stmt);

    return $data;
}

/**
 * Menyimpan data presensi (Tambah Baru atau Update).
 *
 * @return array{sukses: bool, pesan: string, id?: int}
 */
function simpanPresensi(mysqli $conn, array $data, int $userId): array
{
    $presensiId = (int) ($data['id'] ?? 0);
    $karyawanId = (int) ($data['karyawan_id'] ?? 0);
    $departmentId = (int) ($data['department_id'] ?? 0);
    $tanggal = trim((string) ($data['tanggal'] ?? ''));
    $jamMasuk = trim((string) ($data['jam_masuk'] ?? ''));
    $jamKeluar = trim((string) ($data['jam_keluar'] ?? ''));
    $status = strtolower(trim((string) ($data['status'] ?? 'hadir')));
    $keterangan = trim((string) ($data['keterangan'] ?? ''));
    $sumberData = trim((string) ($data['sumber_data'] ?? 'manual'));
    $externalLogId = trim((string) ($data['external_log_id'] ?? ''));

    if (!in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'], true)) {
        return ['sukses' => false, 'pesan' => 'Status kehadiran tidak valid.'];
    }

    $tanggalObj = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
    if ($tanggalObj === false || $tanggalObj->format('Y-m-d') !== $tanggal) {
        return ['sukses' => false, 'pesan' => 'Format tanggal presensi tidak valid (YYYY-MM-DD).'];
    }

    if ($karyawanId <= 0) {
        return ['sukses' => false, 'pesan' => 'Karyawan wajib dipilih.'];
    }

    // Jika department_id belum disertakan, ambil dari data karyawan
    if ($departmentId <= 0) {
        $stmtDept = mysqli_prepare($conn, "SELECT department_id FROM karyawan WHERE id = ?");
        if ($stmtDept) {
            mysqli_stmt_bind_param($stmtDept, 'i', $karyawanId);
            mysqli_stmt_execute($stmtDept);
            $resDept = mysqli_stmt_get_result($stmtDept);
            $rowDept = mysqli_fetch_assoc($resDept);
            $departmentId = (int) ($rowDept['department_id'] ?? 0);
            mysqli_stmt_close($stmtDept);
        }
    }

    if ($departmentId <= 0) {
        return ['sukses' => false, 'pesan' => 'Departemen karyawan tidak valid atau belum diatur.'];
    }

    // Validasi jam kerja untuk status hadir / terlambat
    $jamMasukSql = null;
    $jamKeluarSql = null;

    if (in_array($status, ['hadir', 'terlambat'], true)) {
        if ($jamMasuk === '') {
            return ['sukses' => false, 'pesan' => 'Jam masuk wajib diisi untuk status ' . labelStatusPresensi($status) . '.'];
        }
        $jamMasukObj = DateTimeImmutable::createFromFormat('H:i', substr($jamMasuk, 0, 5))
            ?: DateTimeImmutable::createFromFormat('H:i:s', $jamMasuk);
        if ($jamMasukObj === false) {
            return ['sukses' => false, 'pesan' => 'Format jam masuk tidak valid (HH:MM).'];
        }
        $jamMasukSql = $jamMasukObj->format('H:i:s');

        if ($jamKeluar !== '') {
            $jamKeluarObj = DateTimeImmutable::createFromFormat('H:i', substr($jamKeluar, 0, 5))
                ?: DateTimeImmutable::createFromFormat('H:i:s', $jamKeluar);
            if ($jamKeluarObj === false) {
                return ['sukses' => false, 'pesan' => 'Format jam keluar tidak valid (HH:MM).'];
            }
            $jamKeluarSql = $jamKeluarObj->format('H:i:s');
        }
    }

    if (!in_array($sumberData, ['manual', 'mesin_fingerprint', 'api_mobile', 'webhook'], true)) {
        $sumberData = 'manual';
    }

    $externalLogIdSql = $externalLogId !== '' ? $externalLogId : null;
    $keteranganSql = $keterangan !== '' ? $keterangan : null;

    // Cek duplikasi presensi karyawan pada tanggal yang sama
    $stmtCek = mysqli_prepare(
        $conn,
        "SELECT id FROM presensi_karyawan WHERE karyawan_id = ? AND tanggal = ? AND id <> ?"
    );
    if ($stmtCek) {
        mysqli_stmt_bind_param($stmtCek, 'isi', $karyawanId, $tanggal, $presensiId);
        mysqli_stmt_execute($stmtCek);
        $hasilCek = mysqli_stmt_get_result($stmtCek);
        if (mysqli_num_rows($hasilCek) > 0) {
            mysqli_stmt_close($stmtCek);
            return [
                'sukses' => false,
                'pesan' => 'Data presensi karyawan tersebut pada tanggal ' . $tanggal . ' sudah ada. Gunakan fungsi Edit jika ingin mengubah.',
            ];
        }
        mysqli_stmt_close($stmtCek);
    }

    if ($presensiId > 0) {
        // Mode Update
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE presensi_karyawan
             SET karyawan_id = ?,
                 department_id = ?,
                 tanggal = ?,
                 jam_masuk = ?,
                 jam_keluar = ?,
                 status = ?,
                 keterangan = ?,
                 sumber_data = ?,
                 external_log_id = ?,
                 diubah_oleh_user_id = ?
             WHERE id = ?"
        );

        if (!$stmt) {
            return ['sukses' => false, 'pesan' => 'Gagal menyiapkan query pembaruan: ' . mysqli_error($conn)];
        }

        mysqli_stmt_bind_param(
            $stmt,
            'iisssssssii',
            $karyawanId,
            $departmentId,
            $tanggal,
            $jamMasukSql,
            $jamKeluarSql,
            $status,
            $keteranganSql,
            $sumberData,
            $externalLogIdSql,
            $userId,
            $presensiId
        );

        if (!mysqli_stmt_execute($stmt)) {
            $err = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return ['sukses' => false, 'pesan' => 'Gagal memperbarui presensi: ' . $err];
        }
        mysqli_stmt_close($stmt);

        catatAktivitas($conn, "Memperbarui data presensi ID {$presensiId} (Karyawan ID: {$karyawanId}, Tanggal: {$tanggal}, Status: {$status}).");

        return ['sukses' => true, 'pesan' => 'Data presensi berhasil diperbarui.', 'id' => $presensiId];
    }

    // Mode Insert Baru
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO presensi_karyawan (
            karyawan_id, department_id, tanggal, jam_masuk, jam_keluar,
            status, keterangan, sumber_data, external_log_id, dibuat_oleh_user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return ['sukses' => false, 'pesan' => 'Gagal menyiapkan query penambahan: ' . mysqli_error($conn)];
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iisssssssi',
        $karyawanId,
        $departmentId,
        $tanggal,
        $jamMasukSql,
        $jamKeluarSql,
        $status,
        $keteranganSql,
        $sumberData,
        $externalLogIdSql,
        $userId
    );

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return ['sukses' => false, 'pesan' => 'Gagal menyimpan presensi: ' . $err];
    }

    $idBaru = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    catatAktivitas($conn, "Menambahkan presensi baru ID {$idBaru} (Karyawan ID: {$karyawanId}, Tanggal: {$tanggal}, Status: {$status}).");

    return ['sukses' => true, 'pesan' => 'Data presensi berhasil disimpan.', 'id' => $idBaru];
}

/**
 * Menghapus data presensi berdasarkan ID.
 */
function hapusPresensi(mysqli $conn, int $id): bool
{
    $stmt = mysqli_prepare($conn, "DELETE FROM presensi_karyawan WHERE id = ?");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    $berhasil = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if ($berhasil) {
        catatAktivitas($conn, "Menghapus data presensi ID {$id}.");
    }

    return $berhasil;
}

/**
 * Mengambil ringkasan statistik kehadiran untuk tanggal/filter tertentu.
 */
function ambilRingkasanPresensi(mysqli $conn, string $whereClause): array
{
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN p.status = 'terlambat' THEN 1 ELSE 0 END) AS terlambat,
                SUM(CASE WHEN p.status IN ('izin', 'sakit') THEN 1 ELSE 0 END) AS izin_sakit,
                SUM(CASE WHEN p.status = 'alpa' THEN 1 ELSE 0 END) AS alpa
            FROM presensi_karyawan p
            INNER JOIN karyawan k ON k.id = p.karyawan_id
            WHERE {$whereClause}";

    $hasil = mysqli_query($conn, $sql);
    if (!$hasil) {
        return ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin_sakit' => 0, 'alpa' => 0];
    }

    $row = mysqli_fetch_assoc($hasil) ?: [];
    return [
        'total' => (int) ($row['total'] ?? 0),
        'hadir' => (int) ($row['hadir'] ?? 0),
        'terlambat' => (int) ($row['terlambat'] ?? 0),
        'izin_sakit' => (int) ($row['izin_sakit'] ?? 0),
        'alpa' => (int) ($row['alpa'] ?? 0),
    ];
}
