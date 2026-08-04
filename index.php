<?php

require __DIR__ . "/Dashboard/koneksi.php";

$kataKunci = trim($_GET["cari"] ?? "");
$departemen = trim($_GET["departemen"] ?? "");

/*
|--------------------------------------------------------------------------
| Mengambil daftar departemen
|--------------------------------------------------------------------------
*/

$queryDepartemen = mysqli_query(
    $conn,
    "SELECT DISTINCT department
     FROM karyawan
     WHERE department IS NOT NULL
       AND department != ''
     ORDER BY department ASC"
);

if (!$queryDepartemen) {
    die("Query departemen gagal: " . mysqli_error($conn));
}

/*
|--------------------------------------------------------------------------
| Mengambil data karyawan
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            id,
            emp_id,
            employee_name,
            position,
            department,
            gender,
            date_of_hire,
            employment_status,
            performance_score
        FROM karyawan
        WHERE 1 = 1";

$parameter = [];
$tipeParameter = "";

if ($kataKunci !== "") {
    $sql .= " AND (
                employee_name LIKE ?
                OR emp_id LIKE ?
                OR position LIKE ?
                OR department LIKE ?
            )";

    $pencarian = "%" . $kataKunci . "%";

    $parameter[] = $pencarian;
    $parameter[] = $pencarian;
    $parameter[] = $pencarian;
    $parameter[] = $pencarian;

    $tipeParameter .= "ssss";
}

if ($departemen !== "") {
    $sql .= " AND department = ?";

    $parameter[] = $departemen;
    $tipeParameter .= "s";
}

$sql .= " ORDER BY employee_name ASC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal disiapkan: " . mysqli_error($conn));
}

if (!empty($parameter)) {
    mysqli_stmt_bind_param(
        $stmt,
        $tipeParameter,
        ...$parameter
    );
}

mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
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

    <title>Profil Karyawan Perusahaan</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f7fb;
            color: #1f2937;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: #0f172a;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .navbar-container {
            width: 92%;
            max-width: 1300px;
            min-height: 70px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            color: #ffffff;
            font-size: 21px;
            font-weight: bold;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-menu a {
            color: #e2e8f0;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-menu a:hover {
            color: #ffffff;
        }

        .btn-admin {
            padding: 9px 15px;
            background-color: #2563eb;
            border-radius: 7px;
        }

        .hero {
            padding: 90px 20px;
            color: #ffffff;
            text-align: center;
            background:
                linear-gradient(
                    135deg,
                    rgba(15, 23, 42, 0.97),
                    rgba(37, 99, 235, 0.9)
                );
        }

        .hero-content {
            max-width: 800px;
            margin: auto;
        }

        .hero h1 {
            margin: 0 0 18px;
            font-size: 44px;
            line-height: 1.2;
        }

        .hero p {
            margin: 0 auto 28px;
            max-width: 650px;
            color: #dbeafe;
            font-size: 18px;
            line-height: 1.7;
        }

        .hero-button {
            display: inline-block;
            padding: 13px 21px;
            color: #1e3a8a;
            background-color: #ffffff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .section {
            width: 92%;
            max-width: 1300px;
            margin: auto;
            padding: 65px 0;
        }

        .section-title {
            margin-bottom: 30px;
            text-align: center;
        }

        .section-title h2 {
            margin: 0 0 10px;
            font-size: 32px;
            color: #0f172a;
        }

        .section-title p {
            margin: 0;
            color: #64748b;
            line-height: 1.6;
        }

        .statistics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            padding: 25px;
            background-color: #ffffff;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
        }

        .stat-card h3 {
            margin: 0 0 8px;
            color: #2563eb;
            font-size: 30px;
        }

        .stat-card p {
            margin: 0;
            color: #64748b;
        }

        .filter-box {
            padding: 22px;
            margin-bottom: 25px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
        }

        .filter-form {
            display: grid;
            grid-template-columns: 1fr 250px auto auto;
            gap: 12px;
        }

        .filter-form input,
        .filter-form select {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border-color: #2563eb;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
        }

        .button-search {
            color: #ffffff;
            background-color: #2563eb;
        }

        .button-reset {
            color: #334155;
            background-color: #e2e8f0;
        }

        .result-info {
            margin-bottom: 15px;
            color: #475569;
            font-size: 14px;
        }

        .table-container {
            overflow-x: auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
        }

        table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        thead {
            color: #ffffff;
            background-color: #0f172a;
        }

        th,
        td {
            padding: 14px 13px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            font-size: 14px;
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
            font-size: 12px;
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .empty-data {
            padding: 35px;
            text-align: center;
            color: #64748b;
        }

        .about {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 35px;
            align-items: center;
        }

        .about-content {
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
        }

        .about-content h2 {
            margin-top: 0;
            color: #0f172a;
        }

        .about-content p {
            color: #64748b;
            line-height: 1.8;
        }

        .about-list {
            margin: 0;
            padding-left: 20px;
            color: #475569;
            line-height: 2;
        }

        footer {
            padding: 28px 20px;
            color: #cbd5e1;
            text-align: center;
            background-color: #0f172a;
        }

        footer p {
            margin: 5px 0;
            font-size: 14px;
        }

        @media screen and (max-width: 900px) {
            .statistics {
                grid-template-columns: 1fr;
            }

            .filter-form {
                grid-template-columns: 1fr;
            }

            .about {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 34px;
            }
        }

        @media screen and (max-width: 650px) {
            .navbar-container {
                padding: 12px 0;
                flex-direction: column;
            }

            .nav-menu {
                gap: 12px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero {
                padding: 65px 20px;
            }

            .hero h1 {
                font-size: 29px;
            }

            .hero p {
                font-size: 16px;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php" class="logo">
            Profil Karyawan
        </a>

        <div class="nav-menu">
            <a href="#beranda">Beranda</a>
            <a href="#data">Data Karyawan</a>
            <a href="#tentang">Tentang</a>

            <a href="Dashboard/index.php" class="btn-admin">
                Admin
            </a>
        </div>
    </div>
</nav>

<section class="hero" id="beranda">
    <div class="hero-content">
        <h1>Profil Pekerja Perusahaan</h1>

        <p>
            Website ini menyajikan informasi profil karyawan berdasarkan
            dataset Human Resources, meliputi posisi, departemen, status kerja,
            dan performa karyawan.
        </p>

        <a href="#data" class="hero-button">
            Lihat Data Karyawan
        </a>
    </div>
</section>

<section class="section" id="data">

    <div class="section-title">
        <h2>Dataset Karyawan</h2>

        <p>
            Cari dan lihat informasi umum karyawan perusahaan.
        </p>
    </div>

    <div class="statistics">
        <div class="stat-card">
            <h3><?= $jumlahData; ?></h3>
            <p>Data ditampilkan</p>
        </div>

        <div class="stat-card">
            <h3><?= mysqli_num_rows($queryDepartemen); ?></h3>
            <p>Departemen tersedia</p>
        </div>

        <div class="stat-card">
            <h3>HR</h3>
            <p>Human Resources Dataset</p>
        </div>
    </div>

    <div class="filter-box">
        <form method="GET" action="#data" class="filter-form">

            <input
                type="text"
                name="cari"
                placeholder="Cari nama, ID, posisi, atau departemen"
                value="<?= htmlspecialchars($kataKunci); ?>"
            >

            <select name="departemen">
                <option value="">Semua Departemen</option>

                <?php
                mysqli_data_seek($queryDepartemen, 0);
                ?>

                <?php while (
                    $rowDepartemen = mysqli_fetch_assoc($queryDepartemen)
                ): ?>
                    <?php
                    $namaDepartemen = $rowDepartemen["department"];
                    ?>

                    <option
                        value="<?= htmlspecialchars($namaDepartemen); ?>"
                        <?= $departemen === $namaDepartemen
                            ? "selected"
                            : ""; ?>
                    >
                        <?= htmlspecialchars($namaDepartemen); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button
                type="submit"
                class="button button-search"
            >
                Cari
            </button>

            <a
                href="index.php#data"
                class="button button-reset"
            >
                Reset
            </a>
        </form>
    </div>

    <div class="result-info">
        Menampilkan <strong><?= $jumlahData; ?></strong> data karyawan.

        <?php if ($kataKunci !== ""): ?>
            Hasil pencarian:
            <strong><?= htmlspecialchars($kataKunci); ?></strong>.
        <?php endif; ?>

        <?php if ($departemen !== ""): ?>
            Departemen:
            <strong><?= htmlspecialchars($departemen); ?></strong>.
        <?php endif; ?>
    </div>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>No.</th>
                <th>ID Karyawan</th>
                <th>Nama</th>
                <th>Posisi</th>
                <th>Departemen</th>
                <th>Jenis Kelamin</th>
                <th>Tanggal Masuk</th>
                <th>Status Kerja</th>
                <th>Performa</th>
            </tr>
            </thead>

            <tbody>

            <?php if ($jumlahData > 0): ?>

                <?php $nomor = 1; ?>

                <?php while ($row = mysqli_fetch_assoc($hasil)): ?>
                    <tr>
                        <td><?= $nomor++; ?></td>

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
                            <?php
                            $gender = trim($row["gender"] ?? "");

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
                                    $gender !== "" ? $gender : "-"
                                );
                            }
                            ?>
                        </td>

                        <td>
                            <?php
                            $tanggalMasuk = $row["date_of_hire"] ?? "";

                            if (
                                $tanggalMasuk !== ""
                                && $tanggalMasuk !== null
                                && strtotime($tanggalMasuk) !== false
                            ) {
                                echo date(
                                    "d-m-Y",
                                    strtotime($tanggalMasuk)
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
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="9" class="empty-data">
                        Data karyawan tidak ditemukan.
                    </td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

</section>

<section class="section" id="tentang">
    <div class="about">

        <div class="about-content">
            <h2>Tentang Website</h2>

            <p>
                Website ini digunakan untuk menampilkan dataset profil
                pekerja perusahaan. Pengunjung hanya dapat melihat dan
                mencari data, sedangkan pengelolaan data dilakukan melalui
                halaman khusus administrator.
            </p>
        </div>

        <div class="about-content">
            <h2>Informasi Dataset</h2>

            <ul class="about-list">
                <li>Profil umum karyawan</li>
                <li>Posisi dan departemen</li>
                <li>Status pekerjaan</li>
                <li>Tanggal bergabung</li>
                <li>Penilaian performa</li>
            </ul>
        </div>

    </div>
</section>

<footer>
    <p>
        Sistem Informasi Profil Karyawan Perusahaan
    </p>

    <p>
        Dataset Human Resources
    </p>
</footer>

</body>
</html>
