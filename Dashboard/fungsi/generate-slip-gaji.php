<?php

declare(strict_types=1);
require dirname(__DIR__) . "/koneksi.php";
require_once __DIR__ . "/auth.php";
wajibRole("admin", "superadmin");
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrfValid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Permintaan tidak valid.");
}
$id = (int) ($_POST["id"] ?? 0);
$stmt = mysqli_prepare($conn, "SELECT k.id, k.emp_id, k.employee_name, k.position, k.kontak, k.department_id, pg.id AS profil_id, pg.berlaku_mulai, COALESCE(pg.gaji_pokok, k.salary, 0) AS gaji_pokok, COALESCE(pg.uang_makan, 0) AS uang_makan FROM karyawan k LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE k.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$data) {
    http_response_code(404);
    exit("Data karyawan tidak ditemukan.");
}
if (roleOperasional() && (int) ($data["department_id"] ?? 0) !== (int) (departmentIdPengguna() ?? 0)) {
    http_response_code(403);
    exit("Anda tidak memiliki akses ke slip gaji karyawan dari departemen ini.");
}
$items = [["Gaji Pokok", (float) ($data["gaji_pokok"] ?? 0)], ["Uang Makan", (float) ($data["uang_makan"] ?? 0)]];
$indeksPendapatanSlip = ["gaji pokok" => 0, "uang makan" => 1];
$hasilKomponen = mysqli_query($conn, "SELECT j.nama, j.kategori, c.nilai FROM komponen_gaji_karyawan c INNER JOIN jenis_komponen_gaji j ON j.id = c.jenis_komponen_id WHERE c.profil_gaji_id = " . (int) ($data["profil_id"] ?? 0) . " AND c.nilai > 0 ORDER BY j.kategori, j.nama");
if ($hasilKomponen) while ($komponen = mysqli_fetch_assoc($hasilKomponen)) {
    $nilaiKomponen = (float) $komponen["nilai"];
    if ($nilaiKomponen <= 0) continue;
    if ($komponen["kategori"] === "pendapatan") {
        $namaKunci = mb_strtolower((string) $komponen["nama"]);
        if (isset($indeksPendapatanSlip[$namaKunci])) $items[$indeksPendapatanSlip[$namaKunci]][1] += $nilaiKomponen;
        else {
            $indeksPendapatanSlip[$namaKunci] = count($items);
            $items[] = [(string) $komponen["nama"], $nilaiKomponen];
        }
    }
}
$tambahan = mysqli_query($conn, "SELECT nama, nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id = " . $id . " ORDER BY id ASC");
if ($tambahan) while ($item = mysqli_fetch_assoc($tambahan)) {
    $namaItem = (string) $item["nama"];
    $nilaiItem = (float) $item["nilai"];
    if ($nilaiItem <= 0) continue;
    $namaKunci = mb_strtolower($namaItem);
    if (isset($indeksPendapatanSlip[$namaKunci])) $items[$indeksPendapatanSlip[$namaKunci]][1] += $nilaiItem;
    else {
        $indeksPendapatanSlip[$namaKunci] = count($items);
        $items[] = [$namaItem, $nilaiItem];
    }
}
$total = 0.0;
foreach ($items as $item) $total += $item[1];
$lembur = mysqli_query($conn, "SELECT SUM(oc.jumlah_upah) AS total FROM overtime_reports o INNER JOIN overtime_compensations oc ON oc.overtime_id = o.id WHERE o.karyawan_id = " . $id . " AND o.status IN ('disetujui', 'selesai')");
$totalLembur = (float) ((mysqli_fetch_assoc($lembur)["total"] ?? 0));
if ($totalLembur > 0) {
    $items[] = ["Upah Lembur", $totalLembur];
    $total += $totalLembur;
}
$potonganItems = [];
$totalPotongan = 0.0;
$indeksPotonganSlip = [];
$hasilKomponenPotongan = mysqli_query($conn, "SELECT j.nama, c.nilai FROM komponen_gaji_karyawan c INNER JOIN jenis_komponen_gaji j ON j.id = c.jenis_komponen_id WHERE c.profil_gaji_id = " . (int) ($data["profil_id"] ?? 0) . " AND j.kategori = 'potongan' AND c.nilai > 0 ORDER BY j.nama");
if ($hasilKomponenPotongan) while ($komponen = mysqli_fetch_assoc($hasilKomponenPotongan)) {
    $nilaiKomponen = (float) $komponen["nilai"];
    if ($nilaiKomponen <= 0) continue;
    $namaKunci = mb_strtolower((string) $komponen["nama"]);
    if (isset($indeksPotonganSlip[$namaKunci])) $potonganItems[$indeksPotonganSlip[$namaKunci]][1] += $nilaiKomponen;
    else {
        $indeksPotonganSlip[$namaKunci] = count($potonganItems);
        $potonganItems[] = [(string) $komponen["nama"], $nilaiKomponen];
    }
    $totalPotongan += $nilaiKomponen;
}
$hasilPotongan = mysqli_query($conn, "SELECT nama, nilai FROM potongan_karyawan WHERE karyawan_id = " . $id . " ORDER BY id ASC");
if ($hasilPotongan) while ($itemPotongan = mysqli_fetch_assoc($hasilPotongan)) {
    $nilaiPotongan = (float) $itemPotongan["nilai"];
    if ($nilaiPotongan > 0) {
        $namaKunci = mb_strtolower((string) $itemPotongan["nama"]);
        if (isset($indeksPotonganSlip[$namaKunci])) $potonganItems[$indeksPotonganSlip[$namaKunci]][1] += $nilaiPotongan;
        else {
            $indeksPotonganSlip[$namaKunci] = count($potonganItems);
            $potonganItems[] = [(string) $itemPotongan["nama"], $nilaiPotongan];
        }
        $totalPotongan += $nilaiPotongan;
    }
}
$jumlahDiterima = $total - $totalPotongan;
$autoload = dirname(__DIR__, 2) . "/vendor/autoload.php";
if (!is_file($autoload)) exit("Dependensi PDF belum terpasang.");
require_once $autoload;
$logoPerusahaanPath = dirname(__DIR__) . "/data/ikonlogo_KP_1_S.png";
$logoPerusahaanBinary = is_file($logoPerusahaanPath) ? file_get_contents($logoPerusahaanPath) : false;
if ($logoPerusahaanBinary === false) {
    http_response_code(500);
    exit("Logo perusahaan tidak ditemukan.");
}
$logoPerusahaanSrc = "data:image/png;base64," . base64_encode($logoPerusahaanBinary);
$namaBulan = [1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
$tanggalPeriode = trim((string) ($data["berlaku_mulai"] ?? ""));
$periodeDate = $tanggalPeriode !== "" ? DateTimeImmutable::createFromFormat("!Y-m-d", substr($tanggalPeriode, 0, 10)) : false;
if (!$periodeDate) $periodeDate = new DateTimeImmutable("now");
$tanggalCetak = new DateTimeImmutable("now");
$tanggalCetakIndonesia = $tanggalCetak->format("j") . " " . $namaBulan[(int) $tanggalCetak->format("n")] . " " . $tanggalCetak->format("Y");
$periodeIndonesia = $namaBulan[(int) $periodeDate->format("n")];
$escapeSlip = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, "UTF-8");
$formatNominal = static fn(?float $value): string => $value === null ? "" : number_format($value, 0, ",", ".");
$logoPerusahaanSrcHtml = $escapeSlip($logoPerusahaanSrc);
$periodeHtml = $escapeSlip($periodeIndonesia);
$tanggalCetakHtml = $escapeSlip($tanggalCetakIndonesia);
$empIdHtml = $escapeSlip((string) $data["emp_id"]);
$kontakHtml = $escapeSlip((string) ($data["kontak"] ?: "-"));
$namaKaryawanHtml = $escapeSlip((string) $data["employee_name"]);
$posisiHtml = $escapeSlip((string) $data["position"]);
$totalPendapatanHtml = $formatNominal((float) $total);
$totalPotonganHtml = $formatNominal((float) $totalPotongan);
$jumlahDiterimaHtml = $formatNominal((float) $jumlahDiterima);
$barisSlip = "";
$jumlahBaris = max(count($items), count($potonganItems));
for ($i = 0; $i < $jumlahBaris; $i++) {
    $pendapatan = $items[$i] ?? ["", null];
    $potongan = $potonganItems[$i] ?? ["", null];
    $nilaiPendapatan = $pendapatan[1] === null ? null : (float) $pendapatan[1];
    $nilaiPotongan = $potongan[1] === null ? null : (float) $potongan[1];
    $barisSlip .= '<tr>'
        . '<td class="item-label">' . $escapeSlip((string) $pendapatan[0]) . '</td>'
        . '<td class="currency">' . ($nilaiPendapatan === null ? "" : "Rp") . '</td>'
        . '<td class="amount">' . $formatNominal($nilaiPendapatan) . '</td>'
        . '<td class="item-label">' . $escapeSlip((string) $potongan[0]) . '</td>'
        . '<td class="currency">' . ($nilaiPotongan === null ? "" : "Rp") . '</td>'
        . '<td class="amount">' . $formatNominal($nilaiPotongan) . '</td>'
        . '</tr>';
}
$html = <<<HTML
<style>
    @page { margin: 15mm 14mm 15mm 14mm; }
    body { margin: 0; color: #111; font-size: 10pt; }
    .header { border-bottom: 1.6px solid #111; padding-bottom: 10px; }
    .header-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .header-grid > tbody > tr > td { padding: 0; vertical-align: top; }
    .company-block { width: 33.333%; vertical-align: middle !important; }
    .title-block { width: 33.333%; text-align: center; }
    .header-spacer { width: 33.333%; }
    .company-logo { display: block; width: auto; height: 24px; max-width: 100%; }
    .company-address { margin-top: 3px; font-size: 8pt; }
    .slip-title { margin: 7px 0 4px; font-size: 22pt; line-height: 1; letter-spacing: 2px; font-weight: bold; white-space: nowrap; }
    .period { font-size: 12pt; }
    .meta { width: 100%; border-collapse: collapse; font-size: 8pt; }
    .meta td { padding: 0px 0; vertical-align: top; white-space: nowrap; }
    .meta .meta-label { width: 44%; padding-left: 15px; }
    .meta .meta-colon { width: 7%; padding-left: 23px; padding-right: 1px; text-align: center; }
    .meta .meta-value { width: 49%; text-align: right; }
    .details-grid { width: 100%; margin: 10px 0 11px; border-collapse: collapse; table-layout: fixed; }
    .details-grid > tbody > tr > td { padding: 0; vertical-align: top; }
    .details-identity { width: 100%; }
    .identity { margin: 0; line-height: 1.45; font-size: 11.5pt; }
    .identity-row { margin-bottom: 4px; }
    .salary { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1.4px solid #111; }
    .salary col.label { width: 29%; }
    .salary col.currency { width: 4%; }
    .salary col.amount { width: 17%; }
    .salary th, .salary td { padding: 5px 6px; vertical-align: middle; }
    .salary thead th { border-bottom: 1.4px solid #111; font-size: 13pt; text-align: center; }
    .salary th:nth-child(1), .salary td:nth-child(1), .salary th:nth-child(4), .salary td:nth-child(4) { border-left: 1.4px solid #111; }
    .salary th:nth-child(3), .salary td:nth-child(3), .salary th:nth-child(6), .salary td:nth-child(6) { border-right: 1.4px solid #111; }
    .salary thead th:first-child { border-right: 1.4px solid #111; }
    .salary tbody td:nth-child(4) { border-left: 1.4px solid #111; }
    .salary tbody tr:last-child td { border-top: 1.4px solid #111; }
    .salary .item-label { text-align: left; }
    .salary .currency { text-align: right; padding-left: 0; padding-right: 1px; }
    .salary .amount { text-align: right; white-space: nowrap; padding-left: 1px; }
    .salary .total { font-weight: bold; font-size: 11pt; white-space: nowrap; }
    .received { width: 100%; margin-top: 28px; border-collapse: collapse; table-layout: fixed; }
    .received td { padding: 0 0 4px; border-bottom: 1.4px solid #111; font-size: 14pt; vertical-align: bottom; }
    .received-label { width: 76%; text-align: right; padding-right: 12px !important; }
    .received-amount { width: 24%; text-align: right; white-space: nowrap; }
    .signature-wrap { width: 100%; margin-top: 18px; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
    .signature-spacer { width: 68%; }
    .signature-block { width: 32%; padding: 0; text-align: center; font-size: 11pt; }
    .signature-place { white-space: nowrap; }
    .signature-gap { height: 58px; }
    .signature-name { white-space: nowrap; }
</style>
<div class="header">
    <table class="header-grid">
        <tr>
            <td class="company-block">
                <img class="company-logo" src="{$logoPerusahaanSrcHtml}" alt="Kalinyamat Perkasa">
                <div class="company-address">Jl. Bukit Watu Wila Permata Puri Blok H-IV No 04 RT 001 RW 011 Bringin, Ngalian, Kota Semarang</div>
            </td>
            <td class="title-block">
                <div class="slip-title">SLIP GAJI</div>
                <!-- <div class="period">Periode {$periodeHtml}</div> -->
            </td>
            <td class="header-spacer">
                <table class="meta">
                    <tr><td class="meta-label">Dicetak tanggal</td><td class="meta-colon">:</td><td class="meta-value">{$tanggalCetakHtml}</td></tr>
                    <tr><td class="meta-label">ID Karyawan</td><td class="meta-colon">:</td><td class="meta-value">{$empIdHtml}</td></tr>
                    <tr><td class="meta-label">Kontak</td><td class="meta-colon">:</td><td class="meta-value">{$kontakHtml}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
<table class="details-grid">
    <tr>
        <td class="details-identity">
            <div class="identity">
                <div class="identity-row">Nama: {$namaKaryawanHtml}</div>
                <div class="identity-row">Posisi: {$posisiHtml}</div>
            </div>
        </td>
    </tr>
</table>
<table class="salary">
    <colgroup><col class="label"><col class="currency"><col class="amount"><col class="label"><col class="currency"><col class="amount"></colgroup>
    <thead><tr><th colspan="3">PENDAPATAN</th><th colspan="3">POTONGAN</th></tr></thead>
    <tbody>
        {$barisSlip}
        <tr>
            <td class="item-label total">Total Pendapatan</td><td class="currency total">Rp</td><td class="amount total">{$totalPendapatanHtml}</td>
            <td class="item-label total">Total Potongan</td><td class="currency total">Rp</td><td class="amount total">{$totalPotonganHtml}</td>
        </tr>
    </tbody>
</table>
<table class="received"><tr><td class="received-label">Total Diterima</td><td class="received-amount"><b>{$jumlahDiterimaHtml}</b></td></tr></table>
<table class="signature-wrap">
    <tr>
        <td class="signature-spacer"></td>
        <td class="signature-block">
            <div class="signature-place">Jepara, ....................</div>
            <div class="signature-gap"></div>
            <div class="signature-name">............................</div>
        </td>
    </tr>
</table>
HTML;
$options = new \Dompdf\Options();
$options->set("defaultFont", "DejaVu Sans");
$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "portrait");
$dompdf->render();
$pdf = $dompdf->output();
if (!str_starts_with($pdf, "%PDF")) {
    http_response_code(500);
    exit("PDF gagal dibuat.");
}
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=slip-gaji-" . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $data["emp_id"]) . "-" . date("Y-m") . ".pdf");
echo $pdf;
