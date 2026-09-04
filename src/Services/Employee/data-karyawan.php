<?php

require_once __DIR__ . '/performa-karyawan.php';

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
    $totalKaryawanAktif = 0;
    $totalDepartemen = 0;
    $rataRataPerforma = null;

    $queryKaryawan = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM karyawan
         WHERE LOWER(TRIM(employment_status)) IN ('active', 'aktif')" . (roleOperasional() ? " AND department_id = " . (int) (departmentIdPengguna() ?? 0) : "")
    );

    if ($queryKaryawan) {
        $totalKaryawanAktif = (int) (
            mysqli_fetch_assoc($queryKaryawan)["total"] ?? 0
        );
    }

    $queryDepartemen = mysqli_query(
        $conn,
        "SELECT COUNT(DISTINCT department) AS total
         FROM karyawan
         WHERE department IS NOT NULL
           AND department != ''" . (roleOperasional() ? " AND department_id = " . (int) (departmentIdPengguna() ?? 0) : "")
    );

    if ($queryDepartemen) {
        $totalDepartemen = (int) (
            mysqli_fetch_assoc($queryDepartemen)["total"] ?? 0
        );
    }

    $queryPerforma = mysqli_query(
        $conn,
        "SELECT AVG(CAST(NULLIF(TRIM(performance_score), '') AS DECIMAL(10,2))) AS rata_rata
         FROM karyawan
         WHERE performance_score IS NOT NULL
           AND TRIM(performance_score) <> ''
           AND CAST(performance_score AS DECIMAL(10,2)) > 0" . (roleOperasional() ? " AND department_id = " . (int) (departmentIdPengguna() ?? 0) : "")
    );

    if ($queryPerforma) {
        $nilaiRataPerforma = mysqli_fetch_assoc($queryPerforma)["rata_rata"] ?? null;
        $rataRataPerforma = $nilaiRataPerforma === null ? null : (float) $nilaiRataPerforma;
    }

    return [
        "totalKaryawanAktif" => $totalKaryawanAktif,
        "totalDepartemen" => $totalDepartemen,
        "rataRataPerforma" => $rataRataPerforma,
    ];
}

/**
 * Data untuk grafik departemen dan performa.
 */
function ambilDataGrafik(mysqli $conn): array
{
    $cakupan = roleOperasional() ? " AND department_id = " . (int) (departmentIdPengguna() ?? 0) : "";
    $labelDepartemen = [];
    $jumlahDepartemen = [];
    $labelPerforma = [];
    $jumlahPerforma = [];
    $labelPosisi = [];
    $jumlahPosisi = [];

    $queryDepartemen = mysqli_query(
        $conn,
        "SELECT
            department,
            COUNT(*) AS jumlah
         FROM karyawan
         WHERE department IS NOT NULL
           AND department != ''" . $cakupan . "
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
           AND TRIM(performance_score) <> ''
           AND CAST(performance_score AS DECIMAL(10,2)) > 0" . $cakupan . "
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

    $queryPosisi = mysqli_query($conn, "SELECT position, COUNT(*) AS jumlah FROM karyawan WHERE position IS NOT NULL AND position != ''" . $cakupan . " GROUP BY position ORDER BY jumlah DESC");
    if ($queryPosisi) while ($row = mysqli_fetch_assoc($queryPosisi)) { $labelPosisi[] = $row["position"]; $jumlahPosisi[] = (int) $row["jumlah"]; }

    $labelGender = ["Laki-laki", "Perempuan"];
    $jumlahGender = [0, 0];
    $queryGender = mysqli_query($conn, "SELECT gender, COUNT(*) AS jumlah FROM karyawan WHERE gender IN ('M', 'F')" . $cakupan . " GROUP BY gender");
    if ($queryGender) while ($row = mysqli_fetch_assoc($queryGender)) $jumlahGender[$row["gender"] === "F" ? 1 : 0] = (int) $row["jumlah"];

    return [
        "labelDepartemen" => $labelDepartemen,
        "jumlahDepartemen" => $jumlahDepartemen,
        "labelPerforma" => $labelPerforma,
        "jumlahPerforma" => $jumlahPerforma,
        "labelPosisi" => $labelPosisi,
        "jumlahPosisi" => $jumlahPosisi,
        "labelGender" => $labelGender,
        "jumlahGender" => $jumlahGender,
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
    $cakupan = roleOperasional() ? "department_id = " . (int) (departmentIdPengguna() ?? 0) : "1=1";
    $kataKunci = trim($_GET["cari"] ?? "");
    $departemenDipilih = trim((string) ($_GET['departemen'] ?? ''));
    $posisiDipilih = trim((string) ($_GET['posisi'] ?? ''));

    // Daftar filter dibatasi pada data yang memang boleh dilihat pengguna.
    $filterDepartemen = [];
    $filterPosisi = [];
    $hasilPilihanFilter = mysqli_query($conn, "SELECT DISTINCT department, position FROM karyawan WHERE " . $cakupan . " AND TRIM(COALESCE(department, '')) <> '' ORDER BY department ASC, position ASC");
    if ($hasilPilihanFilter) {
        while ($barisFilter = mysqli_fetch_assoc($hasilPilihanFilter)) {
            $namaDepartemen = trim((string) ($barisFilter['department'] ?? ''));
            $namaPosisi = trim((string) ($barisFilter['position'] ?? ''));
            if ($namaDepartemen === '') continue;
            $filterDepartemen[$namaDepartemen] = true;
            if ($namaPosisi !== '') $filterPosisi[$namaDepartemen][$namaPosisi] = true;
        }
        mysqli_free_result($hasilPilihanFilter);
    }
    $filterDepartemen = array_keys($filterDepartemen);
    $filterPosisi = array_map(static fn (array $daftar): array => array_keys($daftar), $filterPosisi);
    if (!in_array($departemenDipilih, $filterDepartemen, true)) $departemenDipilih = '';
    if ($departemenDipilih === '' || !in_array($posisiDipilih, $filterPosisi[$departemenDipilih] ?? [], true)) $posisiDipilih = '';
    $filterKolom = (string) ($_GET["filter"] ?? "nama");
    $sortPilihan = [
        "emp_id" => "emp_id", "nama" => "employee_name",
        "posisi" => "position", "departemen" => "department", "gaji" => "CAST(salary AS DECIMAL(15,2))",
        "tanggal_masuk" => "date_of_hire", "status_kerja" => "employment_status",
        "performa" => "CAST(NULLIF(TRIM(performance_score), '') AS DECIMAL(10,2))",
    ];
    $sort = (string) ($_GET["sort"] ?? "emp_id");
    if (!isset($sortPilihan[$sort])) $sort = "emp_id";
    $arah = strtoupper((string) ($_GET["arah"] ?? "ASC"));
    if (!in_array($arah, ["ASC", "DESC"], true)) $arah = "ASC";
    $klausaUrut = $sortPilihan[$sort] . " " . $arah . ", id ASC";
    $kolomFilter = [
        "semua" => "employee_name LIKE ?",
        "nama" => "employee_name LIKE ?",
    ];
    if (!isset($kolomFilter[$filterKolom])) $filterKolom = "nama";
    $kondisiFilter = $kolomFilter[$filterKolom];

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

    $kondisiTambahan = [];
    $parameter = [];
    if ($kataKunci !== '') { $kondisiTambahan[] = '(' . $kondisiFilter . ')'; $parameter[] = '%' . $kataKunci . '%'; }
    if ($departemenDipilih !== '') { $kondisiTambahan[] = 'department = ?'; $parameter[] = $departemenDipilih; }
    if ($posisiDipilih !== '') { $kondisiTambahan[] = 'position = ?'; $parameter[] = $posisiDipilih; }
    $klausaFilter = $kondisiTambahan === [] ? '' : ' AND ' . implode(' AND ', $kondisiTambahan);
    $jalankanQuery = static function (string $sql) use ($conn, $parameter) {
        if ($parameter === []) return mysqli_query($conn, $sql);
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) die('Query data gagal disiapkan: ' . mysqli_error($conn));
        $bind = [$stmt, str_repeat('s', count($parameter))];
        foreach ($parameter as $k => &$nilai) $bind[] = &$nilai;
        call_user_func_array('mysqli_stmt_bind_param', $bind);
        mysqli_stmt_execute($stmt);
        return mysqli_stmt_get_result($stmt);
    };

    // 1. Menghitung total data yang cocok lebih dulu (dasar pagination).
    $queryTotal = $jalankanQuery('SELECT COUNT(*) AS total FROM karyawan WHERE ' . $cakupan . $klausaFilter);
    $totalCocok = (int) (mysqli_fetch_assoc($queryTotal)['total'] ?? 0);

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
    $hasil = $jalankanQuery('SELECT * FROM karyawan WHERE ' . $cakupan . $klausaFilter . ' ORDER BY ' . $klausaUrut . $klausaLimit);
    if (!$hasil) die('Query data gagal: ' . mysqli_error($conn));

    return [
        "hasil" => $hasil,
        "jumlahData" => mysqli_num_rows($hasil),
        "totalCocok" => $totalCocok,
        "kataKunci" => $kataKunci,
        'departemenDipilih' => $departemenDipilih,
        'posisiDipilih' => $posisiDipilih,
        'filterDepartemen' => $filterDepartemen,
        'filterPosisi' => $filterPosisi,
        "filterKolom" => $filterKolom,
        "sort" => $sort,
        "arah" => $arah,
        "batas" => $batas,
        "batasDiizinkan" => $batasDiizinkan,
        "tanpaBatas" => $tanpaBatas,
        "izinkanSemua" => $izinkanSemua,
        "halaman" => $halaman,
        "totalHalaman" => $totalHalaman,
        "offset" => $offset,
    ];
}
