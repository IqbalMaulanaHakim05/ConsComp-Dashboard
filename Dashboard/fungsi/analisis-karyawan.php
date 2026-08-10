<?php

declare(strict_types=1);

/**
 * Menjalankan query analisis dengan filter yang sudah dibangun.
 */
function queryAnalisis(
    mysqli $conn,
    string $select,
    array $kondisi,
    string $tipe,
    array $parameter,
    string $tambahan = ""
): mysqli_result {
    $sql = $select;
    if ($kondisi !== []) {
        $sql .= " WHERE " . implode(" AND ", $kondisi);
    }
    $sql .= $tambahan;

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new RuntimeException("Query analisis gagal disiapkan: " . mysqli_error($conn));
    }

    if ($tipe !== "") {
        $bind = [$stmt, $tipe];
        foreach ($parameter as $indeks => $nilai) {
            $parameter[$indeks] = $nilai;
            $bind[] = &$parameter[$indeks];
        }
        call_user_func_array("mysqli_stmt_bind_param", $bind);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $pesan = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException("Query analisis gagal dijalankan: " . $pesan);
    }

    $hasil = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if (!$hasil) {
        throw new RuntimeException("Hasil query analisis tidak tersedia.");
    }

    return $hasil;
}

function tanggalFilterValid(string $tanggal): bool
{
    $objek = DateTime::createFromFormat("Y-m-d", $tanggal);
    return $objek !== false && $objek->format("Y-m-d") === $tanggal;
}

function pilihanKolomAnalisis(mysqli $conn, string $kolom): array
{
    $kolomDiizinkan = ["department", "position", "employment_status"];
    if (!in_array($kolom, $kolomDiizinkan, true)) {
        return [];
    }

    $cakupan = roleOperasional()
        ? " AND department_id = " . (int) (departmentIdPengguna() ?? 0)
        : "";
    $hasil = mysqli_query(
        $conn,
        "SELECT DISTINCT `$kolom` AS nilai
         FROM karyawan
         WHERE `$kolom` IS NOT NULL AND TRIM(`$kolom`) <> ''" . $cakupan . "
         ORDER BY `$kolom` ASC"
    );

    $pilihan = [];
    if ($hasil) {
        while ($baris = mysqli_fetch_assoc($hasil)) {
            $pilihan[] = (string) $baris["nilai"];
        }
    }

    return $pilihan;
}

function pasanganGrafik(mysqli_result $hasil, string $kolomLabel, string $kolomNilai): array
{
    $label = [];
    $nilai = [];

    while ($baris = mysqli_fetch_assoc($hasil)) {
        $label[] = (string) $baris[$kolomLabel];
        $nilai[] = (float) $baris[$kolomNilai];
    }

    return ["label" => $label, "nilai" => $nilai];
}

/**
 * Mengambil seluruh agregasi halaman Analisis hanya dari tabel karyawan.
 */
function ambilAnalisisKaryawan(mysqli $conn): array
{
    $filter = [
        "department" => trim((string) ($_GET["department"] ?? "")),
        "position" => trim((string) ($_GET["position"] ?? "")),
        "employment_status" => trim((string) ($_GET["employment_status"] ?? "")),
        "gender" => trim((string) ($_GET["gender"] ?? "")),
        "date_from" => trim((string) ($_GET["date_from"] ?? "")),
        "date_to" => trim((string) ($_GET["date_to"] ?? "")),
    ];

    if (!in_array($filter["gender"], ["", "M", "F"], true)) {
        $filter["gender"] = "";
    }
    if ($filter["date_from"] !== "" && !tanggalFilterValid($filter["date_from"])) {
        $filter["date_from"] = "";
    }
    if ($filter["date_to"] !== "" && !tanggalFilterValid($filter["date_to"])) {
        $filter["date_to"] = "";
    }
    if ($filter["date_from"] !== "" && $filter["date_to"] !== "" && $filter["date_from"] > $filter["date_to"]) {
        [$filter["date_from"], $filter["date_to"]] = [$filter["date_to"], $filter["date_from"]];
    }

    $pilihan = [
        "department" => pilihanKolomAnalisis($conn, "department"),
        "position" => pilihanKolomAnalisis($conn, "position"),
        "employment_status" => pilihanKolomAnalisis($conn, "employment_status"),
    ];
    foreach (["department", "position", "employment_status"] as $kolom) {
        if ($filter[$kolom] !== "" && !in_array($filter[$kolom], $pilihan[$kolom], true)) {
            $filter[$kolom] = "";
        }
    }

    $kondisi = [];
    $parameter = [];
    $tipe = "";
    foreach (["department", "position", "employment_status", "gender"] as $kolom) {
        if ($filter[$kolom] !== "") {
            $kondisi[] = "`$kolom` = ?";
            $parameter[] = $filter[$kolom];
            $tipe .= "s";
        }
    }
    if (roleOperasional()) {
        $kondisi[] = "department_id = ?";
        $parameter[] = (string) (departmentIdPengguna() ?? 0);
        $tipe .= "i";
    }
    if ($filter["date_from"] !== "") {
        $kondisi[] = "date_of_hire >= ?";
        $parameter[] = $filter["date_from"];
        $tipe .= "s";
    }
    if ($filter["date_to"] !== "") {
        $kondisi[] = "date_of_hire <= ?";
        $parameter[] = $filter["date_to"];
        $tipe .= "s";
    }

    $hasilKpi = queryAnalisis(
        $conn,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN gender = 'M' THEN 1 ELSE 0 END) AS laki_laki,
            SUM(CASE WHEN gender = 'F' THEN 1 ELSE 0 END) AS perempuan,
            SUM(CASE WHEN LOWER(TRIM(employment_status)) IN ('active', 'aktif') THEN 1 ELSE 0 END) AS aktif,
            AVG(CAST(NULLIF(REPLACE(salary, ',', ''), '') AS DECIMAL(15,2))) AS rata_gaji,
            AVG(CAST(NULLIF(performance_score, '') AS DECIMAL(10,2))) AS rata_performa
         FROM karyawan",
        $kondisi,
        $tipe,
        $parameter
    );
    $kpi = mysqli_fetch_assoc($hasilKpi) ?: [];

    $departemen = pasanganGrafik(
        queryAnalisis(
            $conn,
            "SELECT department AS label, COUNT(*) AS jumlah FROM karyawan",
            array_merge($kondisi, ["department IS NOT NULL", "TRIM(department) <> ''"]),
            $tipe,
            $parameter,
            " GROUP BY department ORDER BY jumlah DESC, department ASC"
        ),
        "label",
        "jumlah"
    );

    $posisi = pasanganGrafik(
        queryAnalisis(
            $conn,
            "SELECT position AS label, COUNT(*) AS jumlah FROM karyawan",
            array_merge($kondisi, ["position IS NOT NULL", "TRIM(position) <> ''"]),
            $tipe,
            $parameter,
            " GROUP BY position ORDER BY jumlah DESC, position ASC"
        ),
        "label",
        "jumlah"
    );

    $status = pasanganGrafik(
        queryAnalisis(
            $conn,
            "SELECT employment_status AS label, COUNT(*) AS jumlah FROM karyawan",
            array_merge($kondisi, ["employment_status IS NOT NULL", "TRIM(employment_status) <> ''"]),
            $tipe,
            $parameter,
            " GROUP BY employment_status ORDER BY jumlah DESC, employment_status ASC"
        ),
        "label",
        "jumlah"
    );

    $hasilGender = queryAnalisis(
        $conn,
        "SELECT gender, COUNT(*) AS jumlah FROM karyawan",
        array_merge($kondisi, ["gender IN ('M', 'F')"]),
        $tipe,
        $parameter,
        " GROUP BY gender ORDER BY gender ASC"
    );
    $gender = ["label" => [], "nilai" => []];
    while ($baris = mysqli_fetch_assoc($hasilGender)) {
        $gender["label"][] = $baris["gender"] === "F" ? "Perempuan" : "Laki-laki";
        $gender["nilai"][] = (int) $baris["jumlah"];
    }

    $tren = pasanganGrafik(
        queryAnalisis(
            $conn,
            "SELECT DATE_FORMAT(date_of_hire, '%Y-%m') AS label, COUNT(*) AS jumlah FROM karyawan",
            array_merge($kondisi, ["date_of_hire IS NOT NULL"]),
            $tipe,
            $parameter,
            " GROUP BY DATE_FORMAT(date_of_hire, '%Y-%m') ORDER BY label ASC"
        ),
        "label",
        "jumlah"
    );

    $gaji = pasanganGrafik(
        queryAnalisis(
            $conn,
            "SELECT department AS label,
                    AVG(CAST(NULLIF(REPLACE(salary, ',', ''), '') AS DECIMAL(15,2))) AS rata
             FROM karyawan",
            array_merge($kondisi, ["department IS NOT NULL", "TRIM(department) <> ''", "salary IS NOT NULL", "TRIM(salary) <> ''"]),
            $tipe,
            $parameter,
            " GROUP BY department ORDER BY rata DESC, department ASC"
        ),
        "label",
        "rata"
    );

    $performa = pasanganGrafik(
        queryAnalisis(
            $conn,
            "SELECT department AS label,
                    AVG(CAST(NULLIF(performance_score, '') AS DECIMAL(10,2))) AS rata
             FROM karyawan",
            array_merge($kondisi, ["department IS NOT NULL", "TRIM(department) <> ''", "performance_score IS NOT NULL", "TRIM(performance_score) <> ''"]),
            $tipe,
            $parameter,
            " GROUP BY department ORDER BY rata DESC, department ASC"
        ),
        "label",
        "rata"
    );

    $hasilRingkasan = queryAnalisis(
        $conn,
        "SELECT
            department,
            COUNT(*) AS jumlah,
            SUM(CASE WHEN LOWER(TRIM(employment_status)) IN ('active', 'aktif') THEN 1 ELSE 0 END) AS aktif,
            AVG(CAST(NULLIF(REPLACE(salary, ',', ''), '') AS DECIMAL(15,2))) AS rata_gaji,
            AVG(CAST(NULLIF(performance_score, '') AS DECIMAL(10,2))) AS rata_performa
         FROM karyawan",
        array_merge($kondisi, ["department IS NOT NULL", "TRIM(department) <> ''"]),
        $tipe,
        $parameter,
        " GROUP BY department ORDER BY jumlah DESC, department ASC"
    );
    $ringkasan = [];
    while ($baris = mysqli_fetch_assoc($hasilRingkasan)) {
        $ringkasan[] = $baris;
    }

    return [
        "filter" => $filter,
        "pilihan" => $pilihan,
        "kpi" => [
            "total" => (int) ($kpi["total"] ?? 0),
            "laki_laki" => (int) ($kpi["laki_laki"] ?? 0),
            "perempuan" => (int) ($kpi["perempuan"] ?? 0),
            "aktif" => (int) ($kpi["aktif"] ?? 0),
            "rata_gaji" => (float) ($kpi["rata_gaji"] ?? 0),
            "rata_performa" => (float) ($kpi["rata_performa"] ?? 0),
        ],
        "departemen" => $departemen,
        "posisi" => $posisi,
        "status" => $status,
        "gender" => $gender,
        "tren" => $tren,
        "gaji" => $gaji,
        "performa" => $performa,
        "ringkasan" => $ringkasan,
    ];
}
