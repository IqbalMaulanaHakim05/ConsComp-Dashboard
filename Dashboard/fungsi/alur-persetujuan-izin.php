<?php

declare(strict_types=1);

function tabelPersetujuanIzinDiizinkan(string $tabel): bool
{
    return in_array($tabel, ['izin_meninggalkan_pekerjaan', 'izin_cuti'], true);
}

function siapkanTahapPersetujuanIzin(mysqli $conn, string $tabel): bool
{
    if (!tabelPersetujuanIzinDiizinkan($tabel)) {
        return false;
    }

    $hasilKolom = mysqli_query($conn, "SHOW COLUMNS FROM `{$tabel}` LIKE 'tahap_persetujuan'");
    if ($hasilKolom === false) {
        return false;
    }

    $kolomSudahAda = mysqli_num_rows($hasilKolom) > 0;
    mysqli_free_result($hasilKolom);

    if (!$kolomSudahAda) {
        $berhasilTambah = mysqli_query(
            $conn,
            "ALTER TABLE `{$tabel}`
             ADD COLUMN tahap_persetujuan
                ENUM('pic', 'koordinator', 'manager', 'selesai')
                NOT NULL DEFAULT 'pic'
             AFTER status"
        );
        if ($berhasilTambah === false) {
            return false;
        }
    }

    return mysqli_query(
        $conn,
        "UPDATE `{$tabel}`
         SET tahap_persetujuan = 'selesai'
         WHERE status IN ('disetujui', 'ditolak')
           AND tahap_persetujuan <> 'selesai'"
    ) !== false;
}

function tahapPersetujuanIzinUntukRole(string $role): ?string
{
    return match ($role) {
        'pic' => 'pic',
        'koordinator' => 'koordinator',
        'manager' => 'manager',
        default => null,
    };
}

function tahapPersetujuanIzinBerikutnya(string $tahap): string
{
    return match ($tahap) {
        'pic' => 'koordinator',
        'koordinator' => 'manager',
        default => 'selesai',
    };
}

function labelTahapPersetujuanIzin(string $tahap): string
{
    return match ($tahap) {
        'pic' => 'PIC',
        'koordinator' => 'Koordinator',
        'manager' => 'Manager',
        default => 'Selesai',
    };
}

function labelStatusPersetujuanIzin(string $status, string $tahap, ?string $rolePemroses = null): string
{
    if ($status === 'disetujui') {
        return $rolePemroses === 'superadmin'
            ? 'Disetujui Superadmin'
            : 'Disetujui Manager';
    }
    if ($status === 'ditolak') {
        return 'Ditolak';
    }

    return 'Menunggu ' . labelTahapPersetujuanIzin($tahap);
}

function prosesKeputusanLangsungSuperadminIzin(
    mysqli $conn,
    string $tabel,
    int $izinId,
    string $role,
    string $keputusan,
    string $catatan,
    int $pemrosesId
): bool {
    if (
        !tabelPersetujuanIzinDiizinkan($tabel)
        || $izinId <= 0
        || $role !== 'superadmin'
        || $pemrosesId <= 0
        || !in_array($keputusan, ['disetujui', 'ditolak'], true)
    ) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE `{$tabel}`
         SET status = ?, tahap_persetujuan = 'selesai', diproses_oleh_user_id = ?,
             catatan_persetujuan = NULLIF(?, ''), diproses_at = CURRENT_TIMESTAMP
         WHERE id = ?
           AND status = 'menunggu'"
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sisi',
        $keputusan,
        $pemrosesId,
        $catatan,
        $izinId
    );
    mysqli_stmt_execute($stmt);
    $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $berhasil;
}

function prosesKeputusanPersetujuanIzin(
    mysqli $conn,
    string $tabel,
    int $izinId,
    int $departmentId,
    string $role,
    string $keputusan,
    string $catatan,
    int $pemrosesId
): bool {
    $tahap = tahapPersetujuanIzinUntukRole($role);
    if (
        !tabelPersetujuanIzinDiizinkan($tabel)
        || $izinId <= 0
        || $departmentId <= 0
        || $pemrosesId <= 0
        || $tahap === null
        || !in_array($keputusan, ['disetujui', 'ditolak'], true)
    ) {
        return false;
    }

    $statusBerikutnya = $keputusan === 'ditolak'
        ? 'ditolak'
        : ($tahap === 'manager' ? 'disetujui' : 'menunggu');
    $tahapBerikutnya = $keputusan === 'ditolak'
        ? 'selesai'
        : tahapPersetujuanIzinBerikutnya($tahap);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE `{$tabel}`
         SET status = ?, tahap_persetujuan = ?, diproses_oleh_user_id = ?,
             catatan_persetujuan = NULLIF(?, ''), diproses_at = CURRENT_TIMESTAMP
         WHERE id = ?
           AND department_id = ?
           AND status = 'menunggu'
           AND tahap_persetujuan = ?"
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssisiis',
        $statusBerikutnya,
        $tahapBerikutnya,
        $pemrosesId,
        $catatan,
        $izinId,
        $departmentId,
        $tahap
    );
    mysqli_stmt_execute($stmt);
    $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $berhasil;
}
