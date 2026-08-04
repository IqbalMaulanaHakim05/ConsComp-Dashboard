<?php

require "koneksi.php";

/*
|--------------------------------------------------------------------------
| Mengambil parameter pencarian
|--------------------------------------------------------------------------
*/

$kataKunci = trim($_GET["cari"] ?? "");
$pesan = trim($_GET["pesan"] ?? "");

/*
|--------------------------------------------------------------------------
| Statistik dashboard
|--------------------------------------------------------------------------
*/

$queryTotalKaryawan = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM karyawan"
);

$dataTotalKaryawan = mysqli_fetch_assoc($queryTotalKaryawan);
$totalKaryawan = (int) ($dataTotalKaryawan["total"] ?? 0);


$queryTotalDepartemen = mysqli_query(
    $conn,
    "SELECT COUNT(DISTINCT department) AS total
     FROM karyawan
     WHERE department IS NOT NULL
       AND department != ''"
);

$dataTotalDepartemen = mysqli_fetch_assoc($queryTotalDepartemen);
$totalDepartemen = (int) ($dataTotalDepartemen["total"] ?? 0);


$queryRataPerforma = mysqli_query(
    $conn,
    "SELECT AVG(performance_score) AS rata_rata
     FROM karyawan
     WHERE performance_score IS NOT NULL
       AND performance_score != ''"
);

$dataRataPerforma = mysqli_fetch_assoc($queryRataPerforma);
$rataRataPerforma = (float) ($dataRataPerforma["rata_rata"] ?? 0);

/*
|--------------------------------------------------------------------------
| Grafik jumlah karyawan per departemen
|--------------------------------------------------------------------------
*/

$queryGrafikDepartemen = mysqli_query(
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

if (!$queryGrafikDepartemen) {
    die(
        "Query grafik departemen gagal: "
        . mysqli_error($conn)
    );
}

$labelDepartemen = [];
$jumlahDepartemen = [];

while (
    $grafikDepartemen = mysqli_fetch_assoc(
        $queryGrafikDepartemen
    )
) {
    $labelDepartemen[] =
        $grafikDepartemen["department"];

    $jumlahDepartemen[] =
        (int) $grafikDepartemen["jumlah"];
}

/*
|--------------------------------------------------------------------------
| Grafik performa karyawan
|--------------------------------------------------------------------------
*/

$queryGrafikPerforma = mysqli_query(
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

if (!$queryGrafikPerforma) {
    die(
        "Query grafik performa gagal: "
        . mysqli_error($conn)
    );
}

$labelPerforma = [];
$jumlahPerforma = [];

while (
    $grafikPerforma = mysqli_fetch_assoc(
        $queryGrafikPerforma
    )
) {
    $labelPerforma[] =
        $grafikPerforma["performance_score"];

    $jumlahPerforma[] =
        (int) $grafikPerforma["jumlah"];
}

/*
|--------------------------------------------------------------------------
| Mengambil data karyawan
|--------------------------------------------------------------------------
*/

// Pilihan pembatasan jumlah baris yang diizinkan.
$batasDiizinkan = [15, 30, 50];
$batas = (int) ($_GET["batas"] ?? 15);

if (!in_array($batas, $batasDiizinkan, true)) {
    $batas = 15;
}

if ($kataKunci !== "") {
    $pencarian = "%" . $kataKunci . "%";

    // Menghitung total kecocokan sebelum pembatasan baris.
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
        die(
            "Query hitung pencarian gagal disiapkan: "
            . mysqli_error($conn)
        );
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
    $dataCocok = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmtHitung)
    );
    $totalCocok = (int) ($dataCocok["total"] ?? 0);

    // Mengambil data yang dibatasi. $batas berasal dari whitelist.
    $sql = "SELECT *
            FROM karyawan
            WHERE employee_name LIKE ?
               OR emp_id LIKE ?
               OR position LIKE ?
               OR department LIKE ?
               OR employment_status LIKE ?
               OR performance_score LIKE ?
            ORDER BY id DESC
            LIMIT " . $batas;

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die(
            "Query pencarian gagal disiapkan: "
            . mysqli_error($conn)
        );
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
    $totalCocok = $totalKaryawan;

    $hasil = mysqli_query(
        $conn,
        "SELECT *
         FROM karyawan
         ORDER BY id DESC
         LIMIT " . $batas
    );

    if (!$hasil) {
        die(
            "Query data gagal: "
            . mysqli_error($conn)
        );
    }
}

$jumlahData = mysqli_num_rows($hasil);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Admin Karyawan</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            padding: 25px 18px;
            background-color: #0f172a;
            color: #ffffff;
            overflow-y: auto;
        }

        .sidebar-brand {
            margin-bottom: 35px;
        }

        .sidebar-brand h2 {
            margin: 0;
            font-size: 22px;
        }

        .sidebar-brand p {
            margin: 7px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 14px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: #ffffff;
            background-color: #2563eb;
        }

        .main-content {
            margin-left: 240px;
            min-height: 100vh;
            padding: 30px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .topbar-title h1 {
            margin: 0;
            font-size: 29px;
            color: #0f172a;
        }

        .topbar-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .topbar-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover {
            opacity: 0.86;
        }

        .btn-primary {
            color: #ffffff;
            background-color: #2563eb;
        }

        .btn-success {
            color: #ffffff;
            background-color: #16a34a;
        }

        .btn-warning {
            color: #ffffff;
            background-color: #f59e0b;
        }

        .btn-danger {
            color: #ffffff;
            background-color: #dc2626;
        }

        .btn-secondary {
            color: #334155;
            background-color: #e2e8f0;
        }

        .alert {
            padding: 13px 16px;
            margin-bottom: 22px;
            border-radius: 8px;
            border: 1px solid #86efac;
            color: #166534;
            background-color: #dcfce7;
        }

        .statistics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            padding: 22px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.07);
        }

        .stat-card span {
            display: block;
            margin-bottom: 10px;
            color: #64748b;
            font-size: 13px;
        }

        .stat-card h3 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
        }

        .stat-card p {
            margin: 8px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .dashboard-chart {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-card {
            min-width: 0;
            padding: 22px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.07);
        }

        .chart-card h2 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
        }

        .chart-card p {
            margin: 6px 0 20px;
            color: #64748b;
            font-size: 13px;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 320px;
        }

        .data-card {
            padding: 22px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.07);
        }

        .data-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .data-card-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 19px;
        }

        .search-form {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .search-form input {
            width: 280px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }

        .search-form input:focus {
            border-color: #2563eb;
        }

        .search-form select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            background-color: #ffffff;
            color: #1e293b;
            cursor: pointer;
            outline: none;
        }

        .search-form select:focus {
            border-color: #2563eb;
        }

        .result-info {
            margin-bottom: 14px;
            color: #64748b;
            font-size: 13px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
    width: 100%;
    min-width: 1450px;
    border-collapse: collapse;
}

th:last-child,
td:last-child {
    position: sticky;
    right: 0;
    z-index: 2;
}

th:last-child {
    background-color: #0f172a;
}

td:last-child {
    background-color: #ffffff;
}

tbody tr:nth-child(even) td:last-child {
    background-color: #f8fafc;
}

tbody tr:hover td:last-child {
    background-color: #eff6ff;
}

        thead {
            color: #ffffff;
            background-color: #0f172a;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
            font-size: 13px;
        }

        th {
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tbody tr:hover {
            background-color: #eff6ff;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            color: #1d4ed8;
            background-color: #dbeafe;
            font-size: 11px;
        }

        .action-buttons {
            display: flex;
            gap: 7px;
            white-space: nowrap;
        }

        .action-buttons .btn {
            padding: 7px 10px;
            font-size: 12px;
        }

        .empty-data {
            padding: 30px;
            text-align: center;
            color: #64748b;
        }

        .mobile-menu {
            display: none;
            margin-bottom: 20px;
        }

        @media screen and (max-width: 1200px) {
            .statistics {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-chart {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 800px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                display: none;
            }

            .sidebar.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 18px;
            }

            .mobile-menu {
                display: inline-block;
            }

            .statistics {
                grid-template-columns: 1fr;
            }

            .search-form {
                width: 100%;
            }

            .search-form input {
                width: 100%;
            }

            .data-card-header {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2>Admin Karyawan</h2>

        <p>
            Sistem pengelolaan dataset
        </p>
    </div>

    <nav class="sidebar-menu">
        <a href="index.php" class="active">
            Dashboard
        </a>

        <a href="tambah.php">
            Tambah Data
        </a>

        <a href="import_excel.php">
            Import Excel
        </a>

        <a href="../index.php">
            Halaman Publik
        </a>
    </nav>
</aside>

<main class="main-content">

    <button
        type="button"
        class="btn btn-primary mobile-menu"
        onclick="toggleSidebar()"
    >
        Menu Admin
    </button>

    <div class="topbar">
        <div class="topbar-title">
            <h1>Dashboard Admin</h1>

            <p>
                Kelola dan pantau data karyawan perusahaan.
            </p>
        </div>

    </div>

    <?php if ($pesan === "tambah-berhasil"): ?>
        <div class="alert">
            Data karyawan berhasil ditambahkan.
        </div>
    <?php elseif ($pesan === "edit-berhasil"): ?>
        <div class="alert">
            Data karyawan berhasil diperbarui.
        </div>
    <?php elseif ($pesan === "hapus-berhasil"): ?>
        <div class="alert">
            Data karyawan berhasil dihapus.
        </div>
    <?php elseif ($pesan === "import-excel-berhasil"): ?>
        <div class="alert">
            Import Excel berhasil.
            <strong><?= (int) ($_GET["jumlah"] ?? 0); ?></strong>
            data karyawan telah menggantikan data SQL sebelumnya.
        </div>
    <?php endif; ?>

    <section class="statistics">

        <div class="stat-card">
            <span>Total Karyawan</span>

            <h3>
                <?= number_format($totalKaryawan); ?>
            </h3>

            <p>
                Seluruh data dalam database
            </p>
        </div>

        <div class="stat-card">
            <span>Total Departemen</span>

            <h3>
                <?= number_format($totalDepartemen); ?>
            </h3>

            <p>
                Departemen yang tersedia
            </p>
        </div>

        <div class="stat-card">
            <span>Total Performa</span>

            <h3>
                <?= number_format(
                    $rataRataPerforma,
                    1,
                    ",",
                    "."
                ); ?>
            </h3>

            <p>
                Rata-rata skor performa karyawan
            </p>
        </div>

    </section>

    <section class="dashboard-chart">

        <div class="chart-card">
            <h2>Jumlah Karyawan per Departemen</h2>

            <p>
                Perbandingan jumlah karyawan pada setiap departemen.
            </p>

            <div class="chart-wrapper">
                <canvas id="grafikDepartemen"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h2>Komposisi Performa</h2>

            <p>
                Jumlah karyawan berdasarkan skor performa.
            </p>

            <div class="chart-wrapper">
                <canvas id="grafikPerforma"></canvas>
            </div>
        </div>

    </section>

    <section class="data-card">

        <div class="data-card-header">
            <h2>Data Karyawan</h2>

            <form method="GET" class="search-form">
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari nama, ID, posisi, atau departemen"
                    value="<?= htmlspecialchars($kataKunci); ?>"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Cari
                </button>

                <select
                    name="batas"
                    onchange="this.form.submit()"
                    title="Jumlah baris yang ditampilkan"
                >
                    <?php foreach ($batasDiizinkan as $opsiBatas): ?>
                        <option
                            value="<?= $opsiBatas; ?>"
                            <?= $batas === $opsiBatas ? "selected" : ""; ?>
                        >
                            <?= $opsiBatas; ?> baris
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($kataKunci !== ""): ?>
                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="result-info">
            <?php if ($kataKunci !== ""): ?>
                Ditemukan
                <strong><?= $totalCocok; ?></strong>
                data untuk pencarian
                <strong><?= htmlspecialchars($kataKunci); ?></strong>,
                menampilkan
                <strong><?= $jumlahData; ?></strong>
                baris.
            <?php else: ?>
                Menampilkan
                <strong><?= $jumlahData; ?></strong>
                dari
                <strong><?= $totalCocok; ?></strong>
                data karyawan.
            <?php endif; ?>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>No.</th>
                    <th>ID Karyawan</th>
                    <th>Nama</th>
                    <th>Posisi</th>
                    <th>Departemen</th>
                    <th>Gaji</th>
                    <th>Jenis Kelamin</th>
                    <th>Status Pernikahan</th>
                    <th>Tanggal Masuk</th>
                    <th>Status Kerja</th>
                    <th>Performa</th>
                    <th>Aksi</th>
                </tr>
                </thead>

                <tbody>

                <?php if ($jumlahData > 0): ?>

                    <?php $nomor = 1; ?>

                    <?php while (
                        $row = mysqli_fetch_assoc($hasil)
                    ): ?>
                        <tr>
                            <td>
                                <?= $nomor++; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["emp_id"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["employee_name"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["position"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <span class="badge">
                                    <?= htmlspecialchars(
                                        $row["department"] ?? "-"
                                    ); ?>
                                </span>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) (
                                        $row["salary"] ?? 0
                                    ),
                                    2,
                                    ",",
                                    "."
                                ); ?>
                            </td>

                            <td>
                                <?php
                                $gender = trim(
                                    $row["gender"] ?? ""
                                );

                                if (
                                    $gender === "M"
                                    || $gender === "Male"
                                ) {
                                    echo "Laki-laki";
                                } elseif (
                                    $gender === "F"
                                    || $gender === "Female"
                                ) {
                                    echo "Perempuan";
                                } else {
                                    echo htmlspecialchars(
                                        $gender !== ""
                                            ? $gender
                                            : "-"
                                    );
                                }
                                ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["marital_status"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <?php
                                $tanggalMasuk =
                                    $row["date_of_hire"] ?? "";

                                if (
                                    $tanggalMasuk !== ""
                                    && $tanggalMasuk !== null
                                    && strtotime(
                                        $tanggalMasuk
                                    ) !== false
                                ) {
                                    echo date(
                                        "d-m-Y",
                                        strtotime(
                                            $tanggalMasuk
                                        )
                                    );
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["employment_status"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row["performance_score"] ?? "-"
                                ); ?>
                            </td>

                            <td>
                                <div class="action-buttons">
                                    <a
                                        href="edit.php?id=<?= (int) $row["id"]; ?>"
                                        class="btn btn-warning"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="hapus.php?id=<?= (int) $row["id"]; ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm(
                                            'Yakin ingin menghapus data karyawan ini?'
                                        );"
                                    >
                                        Hapus
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>
                    <tr>
                        <td
                            colspan="12"
                            class="empty-data"
                        >
                            <?php if ($kataKunci !== ""): ?>
                                Data yang dicari tidak ditemukan.
                            <?php else: ?>
                                Data karyawan belum tersedia.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

</main>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("show");
    }

    const labelDepartemen =
        <?= json_encode(
            $labelDepartemen,
            JSON_UNESCAPED_UNICODE
        ); ?>;

    const jumlahDepartemen =
        <?= json_encode(
            $jumlahDepartemen
        ); ?>;

    const labelPerforma =
        <?= json_encode(
            $labelPerforma,
            JSON_UNESCAPED_UNICODE
        ); ?>;

    const jumlahPerforma =
        <?= json_encode(
            $jumlahPerforma
        ); ?>;

    const elemenGrafikDepartemen =
        document.getElementById(
            "grafikDepartemen"
        );

    if (
        elemenGrafikDepartemen
        && labelDepartemen.length > 0
    ) {
        new Chart(
            elemenGrafikDepartemen,
            {
                type: "bar",

                data: {
                    labels: labelDepartemen,

                    datasets: [
                        {
                            label: "Jumlah Karyawan",
                            data: jumlahDepartemen,
                            backgroundColor:
                                "rgba(37, 99, 235, 0.75)",
                            borderColor:
                                "rgb(37, 99, 235)",
                            borderWidth: 1,
                            borderRadius: 6
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return (
                                        context.raw
                                        + " karyawan"
                                    );
                                }
                            }
                        }
                    },

                    scales: {
                        y: {
                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            title: {
                                display: true,
                                text: "Jumlah Karyawan"
                            }
                        },

                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 0
                            }
                        }
                    }
                }
            }
        );
    }

    const elemenGrafikPerforma =
        document.getElementById(
            "grafikPerforma"
        );

    if (
        elemenGrafikPerforma
        && labelPerforma.length > 0
    ) {
        new Chart(
            elemenGrafikPerforma,
            {
                type: "doughnut",

                data: {
                    labels: labelPerforma,

                    datasets: [
                        {
                            label: "Jumlah Karyawan",
                            data: jumlahPerforma,

                            backgroundColor: [
                                "#2563eb",
                                "#16a34a",
                                "#f59e0b",
                                "#dc2626",
                                "#7c3aed",
                                "#0891b2",
                                "#ea580c"
                            ],

                            borderColor: "#ffffff",
                            borderWidth: 2
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "58%",

                    plugins: {
                        legend: {
                            position: "bottom"
                        },

                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return (
                                        context.label
                                        + ": "
                                        + context.raw
                                        + " karyawan"
                                    );
                                }
                            }
                        }
                    }
                }
            }
        );
    }
</script>

</body>
</html>