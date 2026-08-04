<?php

/*
|--------------------------------------------------------------------------
| Halaman Analisis: grafik yang sama dengan panel dashboard.
|--------------------------------------------------------------------------
*/

require __DIR__ . "/koneksi.php";
require __DIR__ . "/fungsi/auth.php";
require __DIR__ . "/fungsi/data-karyawan.php";

wajibLogin();

$grafik = ambilDataGrafik($conn);
$labelDepartemen = $grafik["labelDepartemen"];
$jumlahDepartemen = $grafik["jumlahDepartemen"];
$labelPerforma = $grafik["labelPerforma"];
$jumlahPerforma = $grafik["jumlahPerforma"];

$judulHalaman = "Analisis";
$subjudulHalaman = "Visualisasi data karyawan perusahaan.";
$halamanAktif = "analisis";

require __DIR__ . "/partials/atas.php";
require __DIR__ . "/partials/grafik.php";
require __DIR__ . "/partials/bawah.php";
