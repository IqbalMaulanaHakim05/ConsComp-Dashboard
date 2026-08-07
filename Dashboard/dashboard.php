<?php

/*
|--------------------------------------------------------------------------
| Halaman Dashboard: kartu statistik, grafik, dan tabel ringkas.
|--------------------------------------------------------------------------
*/

require __DIR__ . "/koneksi.php";
require __DIR__ . "/fungsi/auth.php";
require __DIR__ . "/fungsi/data-karyawan.php";
require_once __DIR__ . "/fungsi/pengaturan-publik.php";

// Semua peran boleh melihat dashboard, tetapi wajib login.
wajibLogin();
$pengaturanDashboard = ambilPengaturanPublik($conn);

// Statistik untuk kartu atas.
$statistik = ambilStatistik($conn);
$totalKaryawan = $statistik["totalKaryawan"];
$totalDepartemen = $statistik["totalDepartemen"];
$rataRataPerforma = $statistik["rataRataPerforma"];

// Data grafik departemen & performa.
$grafik = ambilDataGrafik($conn);
$labelDepartemen = $grafik["labelDepartemen"];
$jumlahDepartemen = $grafik["jumlahDepartemen"];
$labelPerforma = $grafik["labelPerforma"];
$jumlahPerforma = $grafik["jumlahPerforma"];
$labelGender = $grafik["labelGender"];
$jumlahGender = $grafik["jumlahGender"];

// Tabel ringkas: 15 baris, opsi 30 dan 50.
$data = ambilDataKaryawan($conn, [15, 30, 50], 15, false);
$hasil = $data["hasil"];
$jumlahData = $data["jumlahData"];
$totalCocok = $data["totalCocok"];
$kataKunci = $data["kataKunci"];
$batas = $data["batas"];
$batasDiizinkan = $data["batasDiizinkan"];
$tanpaBatas = $data["tanpaBatas"];
$izinkanSemua = $data["izinkanSemua"];
$halaman = $data["halaman"];
$totalHalaman = $data["totalHalaman"];
$offset = $data["offset"];

$judulHalaman = "Dashboard Admin";
$subjudulHalaman = "Ringkasan dan pantauan data karyawan perusahaan.";
$halamanAktif = "dashboard";

require __DIR__ . "/partials/atas.php";

?>
    <section class="statistics" style="--dashboard-start: <?= htmlspecialchars($pengaturanDashboard["warna_dashboard_awal"] ?? "#1e3a8a"); ?>; --dashboard-end: <?= htmlspecialchars($pengaturanDashboard["warna_dashboard_akhir"] ?? "#2563eb"); ?>;">

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

<?php
require __DIR__ . "/partials/grafik.php";
require __DIR__ . "/partials/tabel.php";
require __DIR__ . "/partials/bawah.php";
