<?php

/*
|--------------------------------------------------------------------------
| Halaman Analisis: grafik yang sama dengan panel dashboard.
|--------------------------------------------------------------------------
*/

require __DIR__ . "/koneksi.php";
require __DIR__ . "/fungsi/auth.php";
require __DIR__ . "/fungsi/data-karyawan.php";
require_once __DIR__ . "/fungsi/pengaturan-publik.php";

wajibLogin();
$pengaturanDashboard = ambilPengaturanPublik($conn);

$grafik = ambilDataGrafik($conn);
$labelDepartemen = $grafik["labelDepartemen"];
$jumlahDepartemen = $grafik["jumlahDepartemen"];
$labelPerforma = $grafik["labelPerforma"];
$jumlahPerforma = $grafik["jumlahPerforma"];
$labelGender = $grafik["labelGender"];
$jumlahGender = $grafik["jumlahGender"];

$judulHalaman = "Analisis";
$subjudulHalaman = "Visualisasi data karyawan perusahaan.";
$halamanAktif = "analisis";

require __DIR__ . "/partials/atas.php";
require __DIR__ . "/partials/grafik.php";
require __DIR__ . "/partials/bawah.php";
