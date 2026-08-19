<?php

declare(strict_types=1);

require_once __DIR__ . '/performa-karyawan.php';

function siapkanTabelPenilaianPerformaKaryawan(mysqli $conn): bool
{
    return mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS penilaian_performa_karyawan (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            karyawan_id INT NOT NULL,
            produktivitas TINYINT UNSIGNED NULL,
            kualitas TINYINT UNSIGNED NULL,
            ketepatan_waktu TINYINT UNSIGNED NULL,
            efisiensi TINYINT UNSIGNED NULL,
            dinilai_oleh INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_penilaian_performa_karyawan (karyawan_id),
            KEY idx_penilaian_performa_penilai (dinilai_oleh),
            CONSTRAINT fk_penilaian_performa_karyawan
                FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
            CONSTRAINT fk_penilaian_performa_penilai
                FOREIGN KEY (dinilai_oleh) REFERENCES users(id) ON DELETE RESTRICT,
            CONSTRAINT chk_penilaian_produktivitas CHECK (produktivitas IS NULL OR produktivitas BETWEEN 1 AND 100),
            CONSTRAINT chk_penilaian_kualitas CHECK (kualitas IS NULL OR kualitas BETWEEN 1 AND 100),
            CONSTRAINT chk_penilaian_ketepatan CHECK (ketepatan_waktu IS NULL OR ketepatan_waktu BETWEEN 1 AND 100),
            CONSTRAINT chk_penilaian_efisiensi CHECK (efisiensi IS NULL OR efisiensi BETWEEN 1 AND 100)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) !== false;
}

function indikatorPenilaianPerforma(): array
{
    return [
        'produktivitas' => [
            'label' => 'Produktivitas',
            'pedoman' => 'Menilai jumlah dan konsistensi hasil kerja terhadap target. Nilai tinggi menunjukkan target tercapai secara konsisten.',
        ],
        'kualitas' => [
            'label' => 'Kualitas',
            'pedoman' => 'Menilai ketelitian, kesesuaian hasil dengan standar, dan rendahnya kebutuhan perbaikan ulang.',
        ],
        'ketepatan_waktu' => [
            'label' => 'Ketepatan Waktu',
            'pedoman' => 'Menilai kemampuan menyelesaikan pekerjaan sesuai tenggat dan menjaga kedisiplinan waktu.',
        ],
        'efisiensi' => [
            'label' => 'Efisiensi',
            'pedoman' => 'Menilai penggunaan waktu, biaya, dan sumber daya secara tepat tanpa mengurangi kualitas hasil.',
        ],
    ];
}

function normalisasiIndikatorPenilaianPerforma(array $input): array
{
    $hasil = [];
    foreach (indikatorPenilaianPerforma() as $kolom => $konfigurasi) {
        try {
            $hasil[$kolom] = normalisasiSkorPerforma($input[$kolom] ?? null);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException(
                $konfigurasi['label'] . ' harus berupa bilangan bulat 1 sampai 100, atau 0/kosong jika belum dinilai.'
            );
        }
    }
    return $hasil;
}

function rataRataPenilaianPerforma(array $nilai): ?float
{
    $terisi = [];
    foreach (array_keys(indikatorPenilaianPerforma()) as $kolom) {
        $item = $nilai[$kolom] ?? null;
        if ($item !== null && (int) $item > 0) {
            $terisi[] = (int) $item;
        }
    }
    if ($terisi === []) {
        return null;
    }
    return array_sum($terisi) / count($terisi);
}

function ambilPenilaianPerformaKaryawan(mysqli $conn, int $karyawanId): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT p.*, u.nama AS nama_penilai
         FROM penilaian_performa_karyawan p
         INNER JOIN users u ON u.id = p.dinilai_oleh
         WHERE p.karyawan_id = ? LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    mysqli_stmt_close($stmt);
    return $data;
}

function simpanPenilaianPerformaKaryawan(mysqli $conn, int $karyawanId, array $nilai, int $penilaiId): bool
{
    if ($karyawanId <= 0 || $penilaiId <= 0) {
        return false;
    }
    $nilai = normalisasiIndikatorPenilaianPerforma($nilai);
    $produktivitas = $nilai['produktivitas'];
    $kualitas = $nilai['kualitas'];
    $ketepatanWaktu = $nilai['ketepatan_waktu'];
    $efisiensi = $nilai['efisiensi'];

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO penilaian_performa_karyawan
            (karyawan_id, produktivitas, kualitas, ketepatan_waktu, efisiensi, dinilai_oleh)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            produktivitas = VALUES(produktivitas),
            kualitas = VALUES(kualitas),
            ketepatan_waktu = VALUES(ketepatan_waktu),
            efisiensi = VALUES(efisiensi),
            dinilai_oleh = VALUES(dinilai_oleh),
            updated_at = CURRENT_TIMESTAMP"
    );
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param(
        $stmt,
        'iiiiii',
        $karyawanId,
        $produktivitas,
        $kualitas,
        $ketepatanWaktu,
        $efisiensi,
        $penilaiId
    );
    mysqli_stmt_execute($stmt);
    $berhasil = mysqli_stmt_affected_rows($stmt) >= 0;
    mysqli_stmt_close($stmt);
    return $berhasil;
}
