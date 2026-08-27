<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Nilai aman untuk template CV.
 */
function nilaiCv(array $karyawan, string $kolom, string $fallback = "-"): string
{
    $nilai = trim((string) ($karyawan[$kolom] ?? ""));
    return $nilai !== "" ? $nilai : $fallback;
}

function tanggalCv(array $karyawan, string $kolom): string
{
    $nilai = trim((string) ($karyawan[$kolom] ?? ""));
    $waktu = $nilai !== "" ? strtotime($nilai) : false;

    return $waktu !== false ? date("d-m-Y", $waktu) : "-";
}

function genderCv(array $karyawan): string
{
    $gender = trim((string) ($karyawan["gender"] ?? ""));

    if ($gender === "M" || strcasecmp($gender, "Male") === 0) {
        return "Laki-laki";
    }

    if ($gender === "F" || strcasecmp($gender, "Female") === 0) {
        return "Perempuan";
    }

    return $gender !== "" ? $gender : "-";
}

function escapeCv(string $nilai): string
{
    return htmlspecialchars($nilai, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

/**
 * Mengubah foto lokal menjadi data URI agar hasil Dompdf tidak bergantung URL.
 */
function fotoDataUriCv(array $karyawan): ?string
{
    $namaFile = basename((string) ($karyawan["foto_profil"] ?? ""));
    if ($namaFile === "") {
        return null;
    }

    $path = __DIR__ . '/../../storage/uploads' . DIRECTORY_SEPARATOR . "foto" . DIRECTORY_SEPARATOR . $namaFile;
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $mime = function_exists("mime_content_type") ? (string) mime_content_type($path) : "image/jpeg";
    if (!in_array($mime, ["image/jpeg", "image/png"], true)) {
        return null;
    }

    $isi = file_get_contents($path);
    return $isi === false ? null : "data:" . $mime . ";base64," . base64_encode($isi);
}

/**
 * HTML mandiri yang dipakai bersama oleh halaman template tersembunyi dan PDF.
 */
function buatHtmlCv(array $karyawan, array $riwayatPendidikan = [], array $riwayatPekerjaan = []): string
{
    $nama = nilaiCv($karyawan, "employee_name", "Karyawan");
    $foto = fotoDataUriCv($karyawan);
    $inisial = function_exists("mb_substr") ? mb_strtoupper(mb_substr($nama, 0, 1)) : strtoupper(substr($nama, 0, 1));

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV <?= escapeCv($nama); ?></title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eef3f9; color: #172033; font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10px; line-height: 0; }
        .cv-page { width: 210mm; margin: 0 auto; background: #fff; line-height: 1.45; }
        .cv-header { padding: 10mm 13mm 9mm; color: #121b30; background: #b8d2f7; border-bottom: 3px solid #31368f; }
        .cv-header-table, .cv-main-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .cv-photo-cell { width: 34%; vertical-align: middle; text-align: center; }
        .cv-title-cell { width: 66%; vertical-align: middle; text-align: center; }
        .cv-photo, .cv-photo-empty { width: 34mm; height: 34mm; border: 4px solid #fff; border-radius: 50%; }
        .cv-photo { object-fit: cover; }
        .cv-photo-empty { margin: 0 auto; color: #31368f; background: #fff; font-size: 38px; line-height: 31mm; font-weight: 700; }
        .cv-name { margin: 0; padding-bottom: 3mm; border-bottom: 1px solid rgba(49, 54, 143, .35); font-size: 27px; line-height: 1.15; font-weight: 500; letter-spacing: .4px; text-transform: uppercase; }
        .cv-main-table td { vertical-align: top; }
        .cv-sidebar { width: 32%; padding: 8mm 6mm 8mm 10mm; background: #f6f9fd; border-right: 1px dashed #7c89a5; }
        .cv-content { width: 68%; padding: 8mm 10mm 8mm 8mm; }
        .section { margin: 0 0 5mm; page-break-inside: avoid; }
        .section:last-child { margin-bottom: 0; }
        .section-title { margin: 0 0 4mm; color: #252d3e; font-size: 17px; line-height: 1.2; font-weight: 400; letter-spacing: .3px; text-transform: uppercase; }
        .sidebar-title { text-align: center; font-size: 16px; }
        .contact-row { margin-bottom: 3mm; overflow-wrap: anywhere; }
        .contact-label, .meta-label { display: block; margin-bottom: .6mm; color: #68738b; font-size: 8px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
        .contact-value { font-size: 9px; }
        .meta-row { padding: 2.5mm 0; border-bottom: 1px solid #dce5f0; }
        .meta-row:last-child { border-bottom: 0; }
        .meta-value { color: #172033; font-size: 9px; font-weight: 700; overflow-wrap: anywhere; }
        .summary { margin: 0; color: #3d4659; text-align: justify; white-space: pre-line; }
        .timeline-item { position: relative; margin: 0 0 4mm; padding: 0 0 0 7mm; page-break-inside: avoid; }
        .timeline-item:before { position: absolute; top: 1mm; left: 0; width: 4mm; height: 4mm; content: ""; border-radius: 50%; background: #b8d2f7; border: 1px solid #31368f; }
        .timeline-date { margin-bottom: 1mm; color: #31368f; font-size: 9px; font-weight: 700; }
        .timeline-text { margin: 0; color: #253047; font-size: 11px; font-weight: 700; white-space: pre-line; }
        .timeline-description { margin: 1mm 0 0; color: #3d4659; font-size: 9px; white-space: pre-line; }
        @media screen { .cv-page { margin: 20px auto; box-shadow: 0 12px 40px rgba(22, 34, 59, .18); } }
        @media print { html, body { background: #fff; } .cv-page { margin: 0; box-shadow: none; } }
    </style>
</head>
<body>
<main class="cv-page">
    <header class="cv-header">
        <table class="cv-header-table" role="presentation">
            <tr>
                <td class="cv-photo-cell">
                    <?php if ($foto !== null): ?>
                        <img class="cv-photo" src="<?= escapeCv($foto); ?>" alt="Foto <?= escapeCv($nama); ?>">
                    <?php else: ?>
                        <div class="cv-photo-empty"><?= escapeCv($inisial); ?></div>
                    <?php endif; ?>
                </td>
                <td class="cv-title-cell">
                    <h1 class="cv-name"><?= escapeCv($nama); ?></h1>
                </td>
            </tr>
        </table>
    </header>

    <table class="cv-main-table" role="presentation">
        <tr>
            <td class="cv-sidebar">
                <section class="section">
                    <h2 class="section-title sidebar-title">Kontak</h2>
                    <div class="contact-row"><span class="contact-label">Telepon</span><span class="contact-value"><?= escapeCv(nilaiCv($karyawan, "kontak")); ?></span></div>
                    <div class="contact-row"><span class="contact-label">Email</span><span class="contact-value"><?= escapeCv(nilaiCv($karyawan, "email")); ?></span></div>
                    <div class="contact-row"><span class="contact-label">Alamat</span><span class="contact-value"><?= nl2br(escapeCv(nilaiCv($karyawan, "alamat"))); ?></span></div>
                </section>

                <section class="section">
                    <h2 class="section-title sidebar-title">Data Diri</h2>
                    <div class="meta-row"><span class="meta-label">Tanggal Lahir</span><span class="meta-value"><?= escapeCv(tanggalCv($karyawan, "tanggal_lahir")); ?></span></div>
                    <div class="meta-row"><span class="meta-label">Jenis Kelamin</span><span class="meta-value"><?= escapeCv(genderCv($karyawan)); ?></span></div>
                    <div class="meta-row"><span class="meta-label">Agama</span><span class="meta-value"><?= escapeCv(nilaiCv($karyawan, "agama")); ?></span></div>
                    <div class="meta-row"><span class="meta-label">Status Pernikahan</span><span class="meta-value"><?= escapeCv(nilaiCv($karyawan, "marital_status")); ?></span></div>
                    <div class="meta-row"><span class="meta-label">MCU Terakhir</span><span class="meta-value"><?= escapeCv(tanggalCv($karyawan, "tanggal_mcu_terakhir")); ?></span></div>
                </section>

            </td>

            <td class="cv-content">
                <section class="section">
                    <h2 class="section-title">Profil Profesional</h2>
                    <p class="summary"><?= nl2br(escapeCv(nilaiCv($karyawan, "biografi", "Belum ada ringkasan profil profesional."))); ?></p>
                </section>

                <section class="section">
                    <h2 class="section-title">Keahlian</h2>
                    <p class="summary"><?= nl2br(escapeCv(nilaiCv($karyawan, "keahlian", "Belum ada data keahlian."))); ?></p>
                </section>

                <section class="section">
                    <h2 class="section-title">Pendidikan</h2>
                    <?php if ($riwayatPendidikan === []): ?><div class="timeline-item"><div class="timeline-date">-</div><p class="timeline-text">Belum ada data pendidikan.</p></div><?php else: ?>
                        <?php foreach ($riwayatPendidikan as $item): ?><div class="timeline-item"><div class="timeline-date"><?= escapeCv(tanggalCv($item, "tanggal_mulai")); ?> s/d <?= escapeCv(tanggalCv($item, "tanggal_selesai")); ?></div><p class="timeline-text"><?= escapeCv(trim((string) ($item["institusi"] ?? "") . " - " . (string) ($item["jenjang"] ?? "") . " " . (string) ($item["jurusan"] ?? ""))); ?></p><?php if (trim((string) ($item["keterangan"] ?? "")) !== ""): ?><p class="timeline-description"><?= nl2br(escapeCv((string) $item["keterangan"])); ?></p><?php endif; ?></div><?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <section class="section">
                    <h2 class="section-title">Pengalaman Kerja</h2>
                    <?php if ($riwayatPekerjaan === []): ?><div class="timeline-item"><div class="timeline-date">-</div><p class="timeline-text">Belum ada data pengalaman kerja.</p></div><?php else: ?>
                        <?php foreach ($riwayatPekerjaan as $item): ?><div class="timeline-item"><div class="timeline-date"><?= escapeCv(tanggalCv($item, "tanggal_mulai")); ?> s/d <?= escapeCv(tanggalCv($item, "tanggal_selesai")); ?></div><p class="timeline-text"><?= escapeCv(trim((string) ($item["nama_perusahaan"] ?? "") . " - " . (string) ($item["posisi"] ?? ""))); ?></p><?php if (trim((string) ($item["deskripsi"] ?? "")) !== ""): ?><p class="timeline-description"><?= nl2br(escapeCv((string) $item["deskripsi"])); ?></p><?php endif; ?></div><?php endforeach; ?>
                    <?php endif; ?>
                </section>

            </td>
        </tr>
    </table>
</main>
</body>
</html>
    <?php

    return (string) ob_get_clean();
}

/**
 * Menghasilkan byte PDF A4 dari data karyawan.
 */
function buatPdfCv(array $karyawan, array $riwayatPendidikan = [], array $riwayatPekerjaan = []): string
{
    $autoload = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";
    if (!is_file($autoload)) {
        throw new RuntimeException("Dependensi PDF belum terpasang. Jalankan composer install.");
    }

    require_once $autoload;

    $options = new Options();
    $options->set("isRemoteEnabled", false);
    $options->set("isHtml5ParserEnabled", true);
    $options->set("defaultFont", "DejaVu Sans");
    $options->set("defaultMediaType", "print");

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml(buatHtmlCv($karyawan, $riwayatPendidikan, $riwayatPekerjaan), "UTF-8");
    $dompdf->setPaper("A4", "portrait");
    $dompdf->render();

    $pdf = $dompdf->output();
    if (!is_string($pdf) || !str_starts_with($pdf, "%PDF")) {
        throw new RuntimeException("Generator tidak menghasilkan dokumen PDF yang valid.");
    }

    return $pdf;
}
