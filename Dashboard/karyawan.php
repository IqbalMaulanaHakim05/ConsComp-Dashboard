<?php

/*
|--------------------------------------------------------------------------
| Halaman Karyawan: tabel data karyawan lengkap.
| Batas awal 50 baris, opsi 100, 150, dan Semua.
|--------------------------------------------------------------------------
*/

require __DIR__ . "/koneksi.php";
require __DIR__ . "/fungsi/auth.php";
require __DIR__ . "/fungsi/data-karyawan.php";

wajibLogin();

$data = ambilDataKaryawan($conn, [50, 100, 150], 50, true);
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

$judulHalaman = "Data Karyawan";
$subjudulHalaman = "Seluruh data karyawan perusahaan.";
$halamanAktif = "karyawan";

// Action bar tambah/import/export hanya tampil pada tab Karyawan,
// dan hanya untuk peran yang boleh mengubah data.
if (punyaRole("admin", "superadmin", "manager")) {
    $aksiTopbar = __DIR__ . "/partials/aksi-karyawan.php";
}

require __DIR__ . "/partials/atas.php";
require __DIR__ . "/partials/tabel.php";
require __DIR__ . "/partials/bawah.php";
