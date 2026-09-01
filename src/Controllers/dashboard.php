<?php

/*
|--------------------------------------------------------------------------
| Halaman Dashboard: kartu statistik, grafik, dan tabel ringkas.
|--------------------------------------------------------------------------
*/

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../Services/Auth/auth.php';
require __DIR__ . '/../Services/Employee/data-karyawan.php';
require_once __DIR__ . '/../Services/Settings/pengaturan-publik.php';

// Semua peran boleh melihat dashboard, tetapi wajib login.
wajibLogin();
$pengaturanDashboard = ambilPengaturanPublik($conn);
$kartuDashboardAktif = json_decode((string) ($pengaturanDashboard["kartu_dashboard"] ?? ""), true);
if (!is_array($kartuDashboardAktif)) $kartuDashboardAktif = ["total_karyawan", "total_departemen", "rata_performa"];

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
$labelPosisi = $grafik["labelPosisi"];
$jumlahPosisi = $grafik["jumlahPosisi"];
$labelGender = $grafik["labelGender"];
$jumlahGender = $grafik["jumlahGender"];

// Tabel ringkas: 5 baris per halaman.
$data = ambilDataKaryawan($conn, [5], 5, false);
$hasil = $data["hasil"];
$jumlahData = $data["jumlahData"];
$totalCocok = $data["totalCocok"];
$kataKunci = $data["kataKunci"];
$sort = $data["sort"];
$arah = $data["arah"];
$batas = $data["batas"];
$batasDiizinkan = $data["batasDiizinkan"];
$tanpaBatas = $data["tanpaBatas"];
$izinkanSemua = $data["izinkanSemua"];
$halaman = $data["halaman"];
$totalHalaman = $data["totalHalaman"];
$offset = $data["offset"];

$roleDashboard = rolePengguna();
$labelRoleDashboard = labelRole($roleDashboard);

if ($roleDashboard !== "") {
    $judulHalaman = "Dashboard " . $labelRoleDashboard;
    $subjudulHalaman = in_array($roleDashboard, ["manager", "direktur"], true)
        ? "Ringkasan dan pantauan data karyawan departemen Anda."
        : "Ringkasan dan pantauan data karyawan perusahaan.";
} else {
    $judulHalaman = "Dashboard Admin";
    $subjudulHalaman = "Ringkasan dan pantauan data karyawan perusahaan.";
}
$halamanAktif = "dashboard";

require __DIR__ . '/../../resources/views/layouts/atas.php';

?>
    <section class="statistics" style="--dashboard-start: <?= htmlspecialchars($pengaturanDashboard["warna_dashboard_awal"] ?? "#1e3a8a"); ?>; --dashboard-end: <?= htmlspecialchars($pengaturanDashboard["warna_dashboard_akhir"] ?? "#2563eb"); ?>;">

        <?php if (in_array("total_karyawan", $kartuDashboardAktif, true)): ?><div class="stat-card">
            <span>Total Karyawan</span>

            <h3>
                <?= number_format($totalKaryawan); ?>
            </h3>

            <p>
                Seluruh data dalam database
            </p>
        </div><?php endif; ?>

        <?php if (in_array("total_departemen", $kartuDashboardAktif, true)): ?><div class="stat-card">
            <span>Total Departemen</span>

            <h3>
                <?= number_format($totalDepartemen); ?>
            </h3>

            <p>
                Departemen yang tersedia
            </p>
        </div><?php endif; ?>

        <?php if (in_array("rata_performa", $kartuDashboardAktif, true)): ?><div class="stat-card">
            <span>Rata-rata Performa</span>

            <h3>
                <?= $rataRataPerforma === null
                    ? "Belum dinilai"
                    : number_format($rataRataPerforma, 1, ",", "."); ?>
            </h3>

            <p>
                Rata-rata skor performa karyawan
            </p>
        </div><?php endif; ?>

    </section>

<?php
require __DIR__ . '/../../resources/views/partials/grafik.php';
require __DIR__ . '/../../resources/views/partials/tabel.php';
require __DIR__ . '/../../resources/views/layouts/bawah.php';
