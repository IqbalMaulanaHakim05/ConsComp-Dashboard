<?php

declare(strict_types=1);

function siapkanTabelHistoriJabatan(mysqli $conn): bool
{
    return mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS histori_jabatan_karyawan (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            karyawan_id INT NOT NULL,
            department_lama_id INT UNSIGNED NULL,
            department_baru_id INT UNSIGNED NULL,
            departemen_lama_snapshot VARCHAR(120) NOT NULL,
            departemen_baru_snapshot VARCHAR(120) NOT NULL,
            posisi_lama_id INT UNSIGNED NULL,
            posisi_baru_id INT UNSIGNED NULL,
            posisi_lama_snapshot VARCHAR(120) NOT NULL,
            posisi_baru_snapshot VARCHAR(120) NOT NULL,
            tanggal_perubahan DATE NOT NULL,
            tanggal_mulai_jabatan DATE NOT NULL,
            diubah_oleh INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_histori_jabatan_karyawan (karyawan_id, tanggal_mulai_jabatan, id),
            KEY idx_histori_jabatan_pengguna (diubah_oleh),
            CONSTRAINT fk_histori_jabatan_karyawan
                FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
            CONSTRAINT fk_histori_jabatan_departemen_lama
                FOREIGN KEY (department_lama_id) REFERENCES master_departemen(id) ON DELETE SET NULL,
            CONSTRAINT fk_histori_jabatan_departemen_baru
                FOREIGN KEY (department_baru_id) REFERENCES master_departemen(id) ON DELETE SET NULL,
            CONSTRAINT fk_histori_jabatan_posisi_lama
                FOREIGN KEY (posisi_lama_id) REFERENCES master_posisi(id) ON DELETE SET NULL,
            CONSTRAINT fk_histori_jabatan_posisi_baru
                FOREIGN KEY (posisi_baru_id) REFERENCES master_posisi(id) ON DELETE SET NULL,
            CONSTRAINT fk_histori_jabatan_pengguna
                FOREIGN KEY (diubah_oleh) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) !== false;
}

function tanggalHistoriJabatanValid(string $tanggal): bool
{
    $tanggalObjek = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
    return $tanggalObjek !== false && $tanggalObjek->format('Y-m-d') === $tanggal;
}

function daftarHistoriJabatanKaryawan(mysqli $conn, int $karyawanId): array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT h.*, u.nama AS nama_pengubah
         FROM histori_jabatan_karyawan h
         INNER JOIN users u ON u.id = h.diubah_oleh
         WHERE h.karyawan_id = ?
         ORDER BY h.tanggal_mulai_jabatan ASC, h.tanggal_perubahan ASC, h.id ASC"
    );
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $riwayat = [];
    while ($row = mysqli_fetch_assoc($hasil)) {
        $riwayat[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $riwayat;
}

function promosikanKaryawan(
    mysqli $conn,
    int $karyawanId,
    int $departmentBaruId,
    int $posisiBaruId,
    string $tanggalPerubahan,
    string $tanggalMulaiJabatan,
    int $penggunaId,
    string $role
): array {
    if (!in_array($role, ['admin', 'superadmin'], true)) {
        throw new RuntimeException('Anda tidak memiliki hak untuk mengubah jabatan karyawan.');
    }
    if ($karyawanId <= 0 || $departmentBaruId <= 0 || $posisiBaruId <= 0 || $penggunaId <= 0) {
        throw new InvalidArgumentException('Data promosi belum lengkap.');
    }
    if (!tanggalHistoriJabatanValid($tanggalPerubahan) || !tanggalHistoriJabatanValid($tanggalMulaiJabatan)) {
        throw new InvalidArgumentException('Tanggal perubahan dan tanggal mulai jabatan wajib valid.');
    }

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, department_id, department, position
             FROM karyawan WHERE id = ? FOR UPDATE"
        );
        mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
        mysqli_stmt_execute($stmt);
        $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$karyawan) {
            throw new RuntimeException('Data karyawan tidak ditemukan.');
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT d.id AS department_id, d.nama AS departemen,
                    p.id AS posisi_id, p.nama AS posisi
             FROM master_posisi_departemen r
             INNER JOIN master_departemen d ON d.id = r.department_id
             INNER JOIN master_posisi p ON p.id = r.posisi_id
             WHERE r.department_id = ? AND r.posisi_id = ? AND d.is_active = 1
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $departmentBaruId, $posisiBaruId);
        mysqli_stmt_execute($stmt);
        $jabatanBaru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$jabatanBaru) {
            throw new InvalidArgumentException('Posisi baru tidak terdaftar pada departemen yang dipilih.');
        }

        $departemenLama = trim((string) ($karyawan['department'] ?? ''));
        $posisiLama = trim((string) ($karyawan['position'] ?? ''));
        if ($departemenLama === '' || $posisiLama === '') {
            throw new RuntimeException('Departemen atau posisi aktif karyawan belum tersedia.');
        }

        $departmentLamaId = $karyawan['department_id'] !== null ? (int) $karyawan['department_id'] : null;
        if ($departmentLamaId === null) {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM master_departemen WHERE nama = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $departemenLama);
            mysqli_stmt_execute($stmt);
            $lama = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            $departmentLamaId = isset($lama['id']) ? (int) $lama['id'] : null;
        }

        $stmt = mysqli_prepare($conn, 'SELECT id FROM master_posisi WHERE nama = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $posisiLama);
        mysqli_stmt_execute($stmt);
        $posisiLamaData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        $posisiLamaId = isset($posisiLamaData['id']) ? (int) $posisiLamaData['id'] : null;

        $departemenBaru = (string) $jabatanBaru['departemen'];
        $posisiBaru = (string) $jabatanBaru['posisi'];
        $departmentSama = $departmentLamaId !== null
            ? $departmentLamaId === $departmentBaruId
            : strcasecmp($departemenLama, $departemenBaru) === 0;
        if ($departmentSama && strcasecmp($posisiLama, $posisiBaru) === 0) {
            throw new InvalidArgumentException('Departemen dan posisi baru harus berbeda dari jabatan aktif.');
        }

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO histori_jabatan_karyawan
                (karyawan_id, department_lama_id, department_baru_id,
                 departemen_lama_snapshot, departemen_baru_snapshot,
                 posisi_lama_id, posisi_baru_id, posisi_lama_snapshot, posisi_baru_snapshot,
                 tanggal_perubahan, tanggal_mulai_jabatan, diubah_oleh)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            'iiissiissssi',
            $karyawanId,
            $departmentLamaId,
            $departmentBaruId,
            $departemenLama,
            $departemenBaru,
            $posisiLamaId,
            $posisiBaruId,
            $posisiLama,
            $posisiBaru,
            $tanggalPerubahan,
            $tanggalMulaiJabatan,
            $penggunaId
        );
        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Histori jabatan gagal disimpan.');
        }
        $historiId = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE karyawan
             SET department_id = ?, department = ?, position = ?
             WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'issi', $departmentBaruId, $departemenBaru, $posisiBaru, $karyawanId);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new RuntimeException('Posisi aktif karyawan gagal diperbarui.');
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        return [
            'histori_id' => $historiId,
            'departemen_lama' => $departemenLama,
            'departemen_baru' => $departemenBaru,
            'posisi_lama' => $posisiLama,
            'posisi_baru' => $posisiBaru,
        ];
    } catch (Throwable $exception) {
        mysqli_rollback($conn);
        throw $exception;
    }
}
