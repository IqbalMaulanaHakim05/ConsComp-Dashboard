<?php

/**
 * SQL adalah sumber data utama.
 * File CSV lokal hanya merupakan salinan (mirror) dari tabel karyawan.
 */

function headerDatasetKaryawan(): array
{
    return [
        "Employee_Name",
        "EmpID",
        "Position",
        "Department",
        "Salary",
        "Sex",
        "MaritalDesc",
        "DateofHire",
        "EmploymentStatus",
        "PerformanceScore",
    ];
}

function ambilDatasetDariSql(mysqli $conn): array
{
    $sql = "SELECT
                employee_name,
                emp_id,
                position,
                department,
                salary,
                gender,
                marital_status,
                date_of_hire,
                employment_status,
                performance_score
            FROM karyawan
            ORDER BY id ASC";

    $hasil = mysqli_query($conn, $sql);

    if (!$hasil) {
        throw new RuntimeException(
            "Data SQL gagal dibaca: " . mysqli_error($conn)
        );
    }

    $data = [];

    while ($row = mysqli_fetch_assoc($hasil)) {
        $data[] = [
            (string) ($row["employee_name"] ?? ""),
            (string) ($row["emp_id"] ?? ""),
            (string) ($row["position"] ?? ""),
            (string) ($row["department"] ?? ""),
            $row["salary"] === null ? "" : (string) $row["salary"],
            (string) ($row["gender"] ?? ""),
            (string) ($row["marital_status"] ?? ""),
            (string) ($row["date_of_hire"] ?? ""),
            (string) ($row["employment_status"] ?? ""),
            (string) ($row["performance_score"] ?? ""),
        ];
    }

    return $data;
}

function buatCsvSementara(array $data, string $folder): string
{
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        throw new RuntimeException("Folder data tidak dapat dibuat.");
    }

    $lokasiSementara = tempnam($folder, "sync_");

    if ($lokasiSementara === false) {
        throw new RuntimeException("File sementara tidak dapat dibuat.");
    }

    $file = fopen($lokasiSementara, "wb");

    if ($file === false) {
        @unlink($lokasiSementara);
        throw new RuntimeException("File sementara tidak dapat dibuka.");
    }

    // BOM membantu Excel membaca UTF-8 dengan benar.
    fwrite($file, "\xEF\xBB\xBF");
    fputcsv($file, headerDatasetKaryawan());

    foreach ($data as $baris) {
        fputcsv($file, $baris);
    }

    fflush($file);
    fclose($file);

    return $lokasiSementara;
}

function sinkronkanFileDataset(mysqli $conn, string $lokasiTujuan): array
{
    $folder = dirname($lokasiTujuan);
    $dataSql = ambilDatasetDariSql($conn);
    $lokasiSementara = buatCsvSementara($dataSql, $folder);

    $berubah = true;

    if (is_file($lokasiTujuan)) {
        $hashLama = hash_file("sha256", $lokasiTujuan);
        $hashBaru = hash_file("sha256", $lokasiSementara);
        $berubah = $hashLama !== $hashBaru;
    }

    if ($berubah) {
        if (!@rename($lokasiSementara, $lokasiTujuan)) {
            if (!@copy($lokasiSementara, $lokasiTujuan)) {
                @unlink($lokasiSementara);
                throw new RuntimeException(
                    "Dataset lokal gagal diperbarui: " . basename($lokasiTujuan)
                );
            }
            @unlink($lokasiSementara);
        }
    } else {
        @unlink($lokasiSementara);
    }

    return [
        "file" => basename($lokasiTujuan),
        "jumlah_data" => count($dataSql),
        "berubah" => $berubah,
    ];
}

function sinkronkanSemuaDataset(mysqli $conn, ?string $folderData = null): array
{
    // File ini berada di dalam folder "fungsi", sedangkan folder "data"
    // berada satu tingkat di atasnya (root aplikasi).
    $folderData = $folderData ?? (dirname(__DIR__) . DIRECTORY_SEPARATOR . "data");

    if (!is_dir($folderData) && !mkdir($folderData, 0755, true) && !is_dir($folderData)) {
        throw new RuntimeException("Folder data tidak dapat dibuat.");
    }

    $daftarFile = glob($folderData . DIRECTORY_SEPARATOR . "*.csv") ?: [];

    // Jika belum ada file lokal, buat satu file mirror standar.
    if ($daftarFile === []) {
        $daftarFile[] = $folderData . DIRECTORY_SEPARATOR . "karyawan.csv";
    }

    $hasil = [];

    foreach ($daftarFile as $lokasiFile) {
        if (is_dir($lokasiFile)) {
            continue;
        }

        $hasil[] = sinkronkanFileDataset($conn, $lokasiFile);
    }

    return $hasil;
}
