<?php

declare(strict_types=1);

function siapkanJadwalDanCutiKaryawan(mysqli $conn): void
{
    $kolom = [
        'shift_nama' => "VARCHAR(40) NULL DEFAULT NULL",
        'shift_mulai' => "TIME NULL DEFAULT NULL",
        'shift_selesai' => "TIME NULL DEFAULT NULL",
        'shift_hari' => "VARCHAR(60) NOT NULL DEFAULT 'Senin-Jumat'",
        'kuota_cuti_tahunan' => "DECIMAL(6,1) NOT NULL DEFAULT 12.0",
    ];
    foreach ($kolom as $nama => $definisi) {
        $cek = mysqli_query($conn, "SHOW COLUMNS FROM karyawan LIKE '{$nama}'");
        $ada = $cek && mysqli_num_rows($cek) > 0;
        if ($cek) mysqli_free_result($cek);
        if (!$ada) mysqli_query($conn, "ALTER TABLE karyawan ADD COLUMN {$nama} {$definisi}");
    }
}

function labelShiftKaryawan(array $karyawan): string
{
    $mulai = substr((string) ($karyawan['shift_mulai'] ?? ''), 0, 5);
    $selesai = substr((string) ($karyawan['shift_selesai'] ?? ''), 0, 5);
    return $mulai === '' || $selesai === '' ? '-' : $mulai . '–' . $selesai;
}

function labelHariKerjaKaryawan(array $karyawan): string
{
    $hari = trim((string) ($karyawan['shift_hari'] ?? ''));
    if ($hari === '') return '-';
    return formatHariKerjaJadwal($hari) . ' — ' . jumlahHariKerjaJadwal($hari) . ' hari kerja';
}

function sisaCutiKaryawan(mysqli $conn, int $karyawanId, ?int $tahun = null): float
{
    $tahun ??= (int) date('Y');
    $stmt = mysqli_prepare($conn, "SELECT k.kuota_cuti_tahunan,
            COALESCE(SUM(CASE WHEN c.id IS NULL THEN 0 ELSE c.total_hari END), 0) AS terpakai
        FROM karyawan k LEFT JOIN izin_cuti c ON c.karyawan_id = k.id
          AND YEAR(c.tanggal_mulai) = ? AND c.status IN ('menunggu', 'disetujui')
        WHERE k.id = ? GROUP BY k.id, k.kuota_cuti_tahunan");
    if (!$stmt) {
        $hasil = mysqli_query($conn, "SELECT kuota_cuti_tahunan FROM karyawan WHERE id = " . $karyawanId);
        $data = $hasil ? (mysqli_fetch_assoc($hasil) ?: []) : [];
        if ($hasil) mysqli_free_result($hasil);
        return $data === [] ? 0.0 : max(0.0, (float) ($data['kuota_cuti_tahunan'] ?? 12.0));
    }
    mysqli_stmt_bind_param($stmt, 'ii', $tahun, $karyawanId);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: [];
    mysqli_stmt_close($stmt);
    return max(0.0, (float) ($data['kuota_cuti_tahunan'] ?? 12.0) - (float) ($data['terpakai'] ?? 0));
}
