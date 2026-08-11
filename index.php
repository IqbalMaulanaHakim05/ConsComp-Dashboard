<?php

require __DIR__ . "/Dashboard/koneksi.php";
require_once __DIR__ . "/Dashboard/fungsi/pengaturan-publik.php";

// Pengaturan tampilan halaman publik yang dapat diubah superadmin.
$pub = ambilPengaturanPublik($conn);
$namaSitus = $pub["nama_situs"] ?? "Profil Karyawan";
$judulHero = $pub["judul_hero"] ?? "Profil Pekerja Perusahaan";
$deskripsiHero = $pub["deskripsi_hero"] ?? "";
$teksTombol = $pub["teks_tombol"] ?? "Lihat Data Karyawan";
$warnaUtama = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($pub["warna_utama"] ?? ""))
    ? $pub["warna_utama"]
    : "#2563eb";
$warnaHero = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($pub["warna_hero"] ?? ""))
    ? $pub["warna_hero"]
    : "#0f172a";
$kolomPublikAktif = json_decode((string) ($pub["kolom_tabel_publik"] ?? ""), true);
if (!is_array($kolomPublikAktif)) $kolomPublikAktif = ["emp_id", "employee_name", "position", "department", "gender", "date_of_hire", "employment_status", "performance_score"];
$tampilkanKolom = static fn(string $kolom): bool => in_array($kolom, $kolomPublikAktif, true);

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

    <title><?= htmlspecialchars($namaSitus); ?></title>
    <script>const savedTheme = localStorage.getItem('employee-theme'); document.documentElement.dataset.theme = savedTheme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');</script>

    <link rel="stylesheet" href="style/publik.css">

    <style>
        .navbar { background-color: <?= $warnaHero; ?>; }
        .hero { background: linear-gradient(135deg, <?= $warnaHero; ?>, <?= $warnaUtama; ?>); }
        .btn-admin,
        .button-search { background-color: <?= $warnaUtama; ?>; }
        .stat-card h3 { color: <?= $warnaUtama; ?>; }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php" class="logo">
            <?= htmlspecialchars($namaSitus); ?>
        </a>

        <div class="nav-menu">
            <a href="#beranda">Beranda</a>
            <a href="#data">Data Karyawan</a>
            <a href="#tentang">Tentang</a>

            <a href="Dashboard/index.php" class="btn-admin">
                Admin
            </a>
            <button type="button" class="button theme-toggle" onclick="toggleTheme()" aria-label="Ganti tema">🌙 Dark</button>
        </div>
    </div>
</nav>

<section class="hero" id="beranda">
    <div class="hero-content">
        <h1><?= htmlspecialchars($judulHero); ?></h1>

        <p>
            <?= nl2br(htmlspecialchars($deskripsiHero)); ?>
        </p>

        <a href="#data" class="hero-button">
            <?= htmlspecialchars($teksTombol); ?>
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
                <?php if ($tampilkanKolom("emp_id")): ?><th>ID Karyawan</th><?php endif; ?>
                <?php if ($tampilkanKolom("employee_name")): ?><th>Nama</th><?php endif; ?>
                <?php if ($tampilkanKolom("position")): ?><th>Posisi</th><?php endif; ?>
                <?php if ($tampilkanKolom("department")): ?><th>Departemen</th><?php endif; ?>
                <?php if ($tampilkanKolom("gender")): ?><th>Jenis Kelamin</th><?php endif; ?>
                <?php if ($tampilkanKolom("date_of_hire")): ?><th>Tanggal Masuk</th><?php endif; ?>
                <?php if ($tampilkanKolom("employment_status")): ?><th>Status Kerja</th><?php endif; ?>
                <?php if ($tampilkanKolom("performance_score")): ?><th>Performa</th><?php endif; ?>
            </tr>
            </thead>

            <tbody>

            <?php if ($jumlahData > 0): ?>

                <?php $nomor = 1; ?>

                <?php while ($row = mysqli_fetch_assoc($hasil)): ?>
                    <tr>
                        <td><?= $nomor++; ?></td>

                        <?php if ($tampilkanKolom("emp_id")): ?><td>
                            <?= htmlspecialchars(
                                $row["emp_id"] ?? "-"
                            ); ?>
                        </td><?php endif; ?>

                        <?php if ($tampilkanKolom("employee_name")): ?><td>
                            <?= htmlspecialchars(
                                $row["employee_name"] ?? "-"
                            ); ?>
                        </td><?php endif; ?>

                        <?php if ($tampilkanKolom("position")): ?><td>
                            <?= htmlspecialchars(
                                $row["position"] ?? "-"
                            ); ?>
                        </td><?php endif; ?>

                        <?php if ($tampilkanKolom("department")): ?><td>
                            <span class="badge">
                                <?= htmlspecialchars(
                                    $row["department"] ?? "-"
                                ); ?>
                            </span>
                        </td><?php endif; ?>

                        <?php if ($tampilkanKolom("gender")): ?><td>
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
                        </td><?php endif; ?>

                        <?php if ($tampilkanKolom("date_of_hire")): ?><td>
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
                        </td><?php endif; ?>

                        <?php if ($tampilkanKolom("employment_status")): ?><td>
                            <?= htmlspecialchars(
                                $row["employment_status"] ?? "-"
                            ); ?>
                        </td><?php endif; ?>

                        <?php if ($tampilkanKolom("performance_score")): ?><td>
                            <?= htmlspecialchars(
                                $row["performance_score"] ?? "-"
                            ); ?>
                        </td><?php endif; ?>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="<?= 1 + count($kolomPublikAktif); ?>" class="empty-data">
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


<script>
    function toggleTheme() { const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'; document.documentElement.dataset.theme = next; localStorage.setItem('employee-theme', next); document.querySelectorAll('.theme-toggle').forEach(b => b.textContent = next === 'dark' ? '☀️ Light' : '🌙 Dark'); }
    document.querySelectorAll('.theme-toggle').forEach(b => b.textContent = document.documentElement.dataset.theme === 'dark' ? '☀️ Light' : '🌙 Dark');
    document.addEventListener("DOMContentLoaded", () => {
        const animatedElements = document.querySelectorAll(
            ".section-title, .stat-card, .filter-box, .result-info, " +
            ".table-container, .about-content"
        );

        animatedElements.forEach((element) => element.classList.add("reveal"));

        if (!("IntersectionObserver" in window)) {
            animatedElements.forEach((element) => element.classList.add("is-visible"));
            return;
        }

        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    currentObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: "0px 0px -45px 0px"
        });

        animatedElements.forEach((element) => observer.observe(element));
    });
</script>

</body>
</html>
