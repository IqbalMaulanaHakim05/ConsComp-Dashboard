<?php

/*
|--------------------------------------------------------------------------
| Fungsi pengambilan data yang dipakai bersama antar halaman.
| SQL tetap menjadi sumber data utama.
|--------------------------------------------------------------------------
*/

/**
 * Statistik ringkas untuk kartu dashboard.
 */
function ambilStatistik(mysqli $conn): array
{
    $totalKaryawan = 0;
    $totalDepartemen = 0;
    $rataRataPerforma = 0.0;

    $queryKaryawan = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM karyawan"
    );

    if ($queryKaryawan) {
        $totalKaryawan = (int) (
            mysqli_fetch_assoc($queryKaryawan)["total"] ?? 0
        );
    }

    $queryDepartemen = mysqli_query(
        $conn,
        "SELECT COUNT(DISTINCT department) AS total
         FROM karyawan
         WHERE department IS NOT NULL
           AND department != ''"
    );

    if ($queryDepartemen) {
        $totalDepartemen = (int) (
            mysqli_fetch_assoc($queryDepartemen)["total"] ?? 0
        );
    }

    $queryPerforma = mysqli_query(
        $conn,
        "SELECT AVG(performance_score) AS rata_rata
         FROM karyawan
         WHERE performance_score IS NOT NULL
           AND performance_score != ''"
    );

    if ($queryPerforma) {
        $rataRataPerforma = (float) (
            mysqli_fetch_assoc($queryPerforma)["rata_rata"] ?? 0
        );
    }

    return [
        "totalKaryawan" => $totalKaryawan,
        "totalDepartemen" => $totalDepartemen,
        "rataRataPerforma" => $rataRataPerforma,
    ];
}

/**
 * Data untuk grafik departemen dan performa.
 */
function ambilDataGrafik(mysqli $conn): array
{
    $labelDepartemen = [];
    $jumlahDepartemen = [];
    $labelPerforma = [];
    $jumlahPerforma = [];

    $queryDepartemen = mysqli_query(
        $conn,
        "SELECT
            department,
            COUNT(*) AS jumlah
         FROM karyawan
         WHERE department IS NOT NULL
           AND department != ''
         GROUP BY department
         ORDER BY jumlah DESC"
    );

    if (!$queryDepartemen) {
        die("Query grafik departemen gagal: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($queryDepartemen)) {
        $labelDepartemen[] = $row["department"];
        $jumlahDepartemen[] = (int) $row["jumlah"];
    }

    $queryPerforma = mysqli_query(
        $conn,
        "SELECT
            performance_score,
            COUNT(*) AS jumlah
         FROM karyawan
         WHERE performance_score IS NOT NULL
           AND performance_score != ''
         GROUP BY performance_score
         ORDER BY jumlah DESC"
    );

    if (!$queryPerforma) {
        die("Query grafik performa gagal: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($queryPerforma)) {
        $labelPerforma[] = $row["performance_score"];
        $jumlahPerforma[] = (int) $row["jumlah"];
    }

    return [
        "labelDepartemen" => $labelDepartemen,
        "jumlahDepartemen" => $jumlahDepartemen,
        "labelPerforma" => $labelPerforma,
        "jumlahPerforma" => $jumlahPerforma,
    ];
}

/**
 * Data tabel karyawan dengan pencarian dan pembatasan baris.
 *
 * @param array $batasDiizinkan Daftar pilihan jumlah baris.
 * @param int   $batasDefault   Jumlah baris awal.
 * @param bool  $izinkanSemua   Mengizinkan opsi "Semua" (tanpa LIMIT).
 */
function ambilDataKaryawan(
    mysqli $conn,
    array $batasDiizinkan,
    int $batasDefault,
    bool $izinkanSemua
): array {
    $kataKunci = trim($_GET["cari"] ?? "");

    $batasParam = (string) ($_GET["batas"] ?? $batasDefault);
    $tanpaBatas = ($izinkanSemua && $batasParam === "semua");

    if ($tanpaBatas) {
        $batas = null;
    } else {
        $batas = (int) $batasParam;

        if (!in_array($batas, $batasDiizinkan, true)) {
            $batas = $batasDefault;
        }
    }

    $pencarian = "%" . $kataKunci . "%";

    // 1. Menghitung total data yang cocok lebih dulu (dasar pagination).
    if ($kataKunci !== "") {
        $sqlHitung = "SELECT COUNT(*) AS total
                      FROM karyawan
                      WHERE employee_name LIKE ?
                         OR emp_id LIKE ?
                         OR position LIKE ?
                         OR department LIKE ?
                         OR employment_status LIKE ?
                         OR performance_score LIKE ?";

        $stmtHitung = mysqli_prepare($conn, $sqlHitung);

        if (!$stmtHitung) {
            die("Query hitung pencarian gagal disiapkan: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmtHitung,
            "ssssss",
            $pencarian,
            $pencarian,
            $pencarian,
            $pencarian,
            $pencarian,
            $pencarian
        );

        mysqli_stmt_execute($stmtHitung);
        $totalCocok = (int) (
            mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmtHitung)
            )["total"] ?? 0
        );
    } else {
        $queryTotal = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total FROM karyawan"
        );
        $totalCocok = (int) (
            mysqli_fetch_assoc($queryTotal)["total"] ?? 0
        );
    }

    // 2. Menentukan halaman aktif dan klausa LIMIT/OFFSET.
    $halaman = max(1, (int) ($_GET["hal"] ?? 1));

    if ($tanpaBatas) {
        $totalHalaman = 1;
        $halaman = 1;
        $offset = 0;
        $klausaLimit = "";
    } else {
        $totalHalaman = max(1, (int) ceil($totalCocok / $batas));

        if ($halaman > $totalHalaman) {
            $halaman = $totalHalaman;
        }

        $offset = ($halaman - 1) * $batas;

        // $batas dari whitelist dan $offset berupa integer, aman ditempel.
        $klausaLimit = " LIMIT " . $batas . " OFFSET " . $offset;
    }

    // 3. Mengambil data sesuai halaman.
    if ($kataKunci !== "") {
        $sql = "SELECT *
                FROM karyawan
                WHERE employee_name LIKE ?
                   OR emp_id LIKE ?
                   OR position LIKE ?
                   OR department LIKE ?
                   OR employment_status LIKE ?
                   OR performance_score LIKE ?
                ORDER BY id DESC" . $klausaLimit;

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("Query pencarian gagal disiapkan: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
            $pencarian,
            $pencarian,
            $pencarian,
            $pencarian,
            $pencarian,
            $pencarian
        );

        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
    } else {
        $hasil = mysqli_query(
            $conn,
            "SELECT *
             FROM karyawan
             ORDER BY id DESC" . $klausaLimit
        );

        if (!$hasil) {
            die("Query data gagal: " . mysqli_error($conn));
        }
    }

    return [
        "hasil" => $hasil,
        "jumlahData" => mysqli_num_rows($hasil),
        "totalCocok" => $totalCocok,
        "kataKunci" => $kataKunci,
        "batas" => $batas,
        "batasDiizinkan" => $batasDiizinkan,
        "tanpaBatas" => $tanpaBatas,
        "izinkanSemua" => $izinkanSemua,
        "halaman" => $halaman,
        "totalHalaman" => $totalHalaman,
        "offset" => $offset,
    ];
}
