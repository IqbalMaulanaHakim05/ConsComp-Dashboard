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

/**
 * Menentukan aturan waktu lembur menurut hari kerja karyawan. Lembur tidak
 * boleh dimulai saat karyawan sedang menjalani shift. Pada hari kerja, lembur
 * di luar shift berakhir sebelum jam masuk berikutnya; hari non-kerja bebas.
 *
 * @return array{hari_kerja: bool, sedang_bekerja: bool, mulai_shift_aktif?: DateTimeImmutable, akhir_shift?: DateTimeImmutable, mulai_kerja_berikutnya?: DateTimeImmutable}|null
 */
function batasWaktuLemburKaryawan(array $karyawan, DateTimeInterface $mulaiLembur): ?array
{
    $jamMulai = substr(trim((string) ($karyawan['shift_mulai'] ?? '')), 0, 5);
    $jamSelesai = substr(trim((string) ($karyawan['shift_selesai'] ?? '')), 0, 5);
    $hariKerja = hariKerjaJadwalTerpilih((string) ($karyawan['shift_hari'] ?? ''));
    if (
        $hariKerja === []
        || preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $jamMulai) !== 1
        || preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/', $jamSelesai) !== 1
    ) {
        return null;
    }

    $hariIndonesia = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $waktuLembur = DateTimeImmutable::createFromInterface($mulaiLembur);
    $tanggalLembur = $waktuLembur->setTime(0, 0);
    $hariKerjaHariIni = in_array($hariIndonesia[(int) $tanggalLembur->format('N') - 1], $hariKerja, true);

    foreach ([0, 1] as $mundur) {
        $tanggalKerja = $tanggalLembur->modify('-' . $mundur . ' days');
        if (!in_array($hariIndonesia[(int) $tanggalKerja->format('N') - 1], $hariKerja, true)) continue;

        $mulaiShift = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $tanggalKerja->format('Y-m-d') . ' ' . $jamMulai);
        $selesaiShift = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $tanggalKerja->format('Y-m-d') . ' ' . $jamSelesai);
        if (!$mulaiShift || !$selesaiShift) return null;
        if ($selesaiShift <= $mulaiShift) $selesaiShift = $selesaiShift->modify('+1 day');

        if ($waktuLembur >= $mulaiShift && $waktuLembur < $selesaiShift) {
            return [
                'hari_kerja' => $hariKerjaHariIni,
                'sedang_bekerja' => true,
                'mulai_shift_aktif' => $mulaiShift,
                'akhir_shift' => $selesaiShift,
            ];
        }
    }

    if (!$hariKerjaHariIni) {
        return ['hari_kerja' => false, 'sedang_bekerja' => false];
    }

    $akhirShiftTerakhir = null;

    for ($mundur = 0; $mundur <= 14; $mundur++) {
        $tanggalKerja = $tanggalLembur->modify('-' . $mundur . ' days');
        if (!in_array($hariIndonesia[(int) $tanggalKerja->format('N') - 1], $hariKerja, true)) continue;

        $mulaiShift = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $tanggalKerja->format('Y-m-d') . ' ' . $jamMulai);
        $selesaiShift = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $tanggalKerja->format('Y-m-d') . ' ' . $jamSelesai);
        if (!$mulaiShift || !$selesaiShift) return null;
        if ($selesaiShift <= $mulaiShift) $selesaiShift = $selesaiShift->modify('+1 day');

        if ($selesaiShift <= $waktuLembur) {
            $akhirShiftTerakhir = $selesaiShift;
            break;
        }
    }

    if ($akhirShiftTerakhir === null) return null;

    $tanggalBerikutnya = $akhirShiftTerakhir->setTime(0, 0);
    for ($maju = 0; $maju <= 14; $maju++) {
        $tanggalKerja = $tanggalBerikutnya->modify('+' . $maju . ' days');
        if (!in_array($hariIndonesia[(int) $tanggalKerja->format('N') - 1], $hariKerja, true)) continue;

        $mulaiKerjaBerikutnya = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $tanggalKerja->format('Y-m-d') . ' ' . $jamMulai);
        if ($mulaiKerjaBerikutnya && $mulaiKerjaBerikutnya > $akhirShiftTerakhir) {
            return [
                'hari_kerja' => true,
                'sedang_bekerja' => false,
                'akhir_shift' => $akhirShiftTerakhir,
                'mulai_kerja_berikutnya' => $mulaiKerjaBerikutnya,
            ];
        }
    }

    return null;
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
