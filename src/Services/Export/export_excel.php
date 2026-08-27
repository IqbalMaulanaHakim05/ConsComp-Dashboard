<?php

require __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../Auth/auth.php';
require_once __DIR__ . '/../Employee/performa-karyawan.php';

wajibRole("admin", "superadmin", "manager");

$departmentIdPengguna = departmentIdPengguna();
$filterDepartemen = roleOperasional()
    ? " WHERE department_id = " . (int) ($departmentIdPengguna ?? 0)
    : "";
$kataKunci = trim((string) ($_GET["cari"] ?? ""));
$filterKolom = (string) ($_GET["filter"] ?? "nama");
$batasPilihan = [10, 25, 50, 100, 250, "semua"];
$batasExport = $_GET["batas_export"] ?? "semua";
if ($batasExport !== "semua") $batasExport = max(1, min(10000, (int) $batasExport));
if (!in_array($batasExport, $batasPilihan, true) && $batasExport !== "semua") $batasExport = "semua";
$arahExport = strtoupper((string) ($_GET["arah_export"] ?? "ASC"));
if (!in_array($arahExport, ["ASC", "DESC"], true)) $arahExport = "ASC";
$sortExport = (string) ($_GET["sort_export"] ?? "id");
$kolomExportPilihan = [
    "emp_id" => ["label" => "ID Karyawan", "sql" => "emp_id"],
    "employee_name" => ["label" => "Nama", "sql" => "employee_name"],
    "position" => ["label" => "Posisi", "sql" => "position"],
    "department" => ["label" => "Departemen", "sql" => "department"],
    "salary" => ["label" => "Gaji", "sql" => "salary"],
    "gender" => ["label" => "Jenis Kelamin", "sql" => "gender"],
    "marital_status" => ["label" => "Status Pernikahan", "sql" => "marital_status"],
    "date_of_hire" => ["label" => "Tanggal Masuk", "sql" => "date_of_hire"],
    "employment_status" => ["label" => "Status Kerja", "sql" => "employment_status"],
    "performance_score" => ["label" => "Skor Performa", "sql" => "performance_score"],
];
$kolomDipilih = $_GET["kolom"] ?? array_keys($kolomExportPilihan);
if (!is_array($kolomDipilih)) $kolomDipilih = [$kolomDipilih];
$kolomDipilih = array_values(array_intersect(array_keys($kolomExportPilihan), $kolomDipilih));
if ($kolomDipilih === []) $kolomDipilih = array_keys($kolomExportPilihan);
$sortSql = ["id" => "id"] + array_combine(array_keys($kolomExportPilihan), array_map(static fn($item) => $item["sql"], $kolomExportPilihan));
if (!isset($sortSql[$sortExport])) $sortExport = "id";
$kolomExport = [
    "semua" => "employee_name",
    "nama" => "employee_name",
];
if (!isset($kolomExport[$filterKolom])) $filterKolom = "nama";
$kondisiExport = "";
if ($kataKunci !== "") {
    $safeKunci = mysqli_real_escape_string($conn, $kataKunci);
    $nilaiCari = "'%" . $safeKunci . "%'";
    $kondisiExport = $kolomExport[$filterKolom] . " LIKE $nilaiCari";
}
$filterSql = $filterDepartemen === "" ? " WHERE 1=1" : $filterDepartemen;
if ($kondisiExport !== "") $filterSql .= " AND " . $kondisiExport;

if (isset($_GET["download"]) && !class_exists("ZipArchive")) {
    http_response_code(500);
    die("Ekstensi PHP ZipArchive diperlukan untuk membuat file .xlsx.");
}

$query = mysqli_query(
    $conn,
    "SELECT
        emp_id,
        employee_name,
        position,
        department,
        salary,
        gender,
        marital_status,
        date_of_hire,
        employment_status,
        performance_score
     FROM karyawan
     $filterSql
     ORDER BY " . $sortSql[$sortExport] . " " . $arahExport . ", id ASC"
     . ($batasExport === "semua" ? "" : " LIMIT " . (int) $batasExport)
);

if (!$query) {
    http_response_code(500);
    die("Data gagal diekspor: " . mysqli_error($conn));
}

if (!isset($_GET["download"])) {
    $judulHalaman = "Opsi Export Karyawan";
    $subjudulHalaman = "Pilih jumlah data, urutan, dan kolom sebelum mengunduh Excel.";
    $halamanAktif = "karyawan";
    require __DIR__ . '/../../../resources/views/layouts/atas.php';
    ?>
    <section class="form-card export-options-card">
        <div class="form-card-header"><h2>Opsi Export Data Karyawan</h2><p><?= mysqli_num_rows($query); ?> data akan diekspor sesuai cakupan akses.</p></div>
        <div class="form-body">
            <form method="GET" class="export-options-form">
                <div class="form-group"><label for="batas_export">Jumlah data</label><select id="batas_export" name="batas_export"><?php foreach ($batasPilihan as $pilihan): ?><option value="<?= $pilihan; ?>" <?= (string) $batasExport === (string) $pilihan ? "selected" : ""; ?>><?= $pilihan === "semua" ? "Semua data" : "Maksimal " . $pilihan . " data"; ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="sort_export">Urutkan berdasarkan</label><select id="sort_export" name="sort_export"><option value="id" <?= $sortExport === "id" ? "selected" : ""; ?>>ID database</option><?php foreach ($kolomExportPilihan as $kunci => $kolom): ?><option value="<?= $kunci; ?>" <?= $sortExport === $kunci ? "selected" : ""; ?>><?= htmlspecialchars($kolom["label"]); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="arah_export">Arah urutan</label><select id="arah_export" name="arah_export"><option value="ASC" <?= $arahExport === "ASC" ? "selected" : ""; ?>>Naik (A–Z / kecil ke besar)</option><option value="DESC" <?= $arahExport === "DESC" ? "selected" : ""; ?>>Turun (Z–A / besar ke kecil)</option></select></div>
                <?php foreach (["cari" => $kataKunci, "filter" => $filterKolom] as $nama => $nilai): ?><input type="hidden" name="<?= $nama; ?>" value="<?= htmlspecialchars($nilai); ?>"><?php endforeach; ?>
                <fieldset class="export-columns-fieldset"><legend>Kolom yang diekspor</legend><?php foreach ($kolomExportPilihan as $kunci => $kolom): ?><label><input type="checkbox" name="kolom[]" value="<?= $kunci; ?>" <?= in_array($kunci, $kolomDipilih, true) ? "checked" : ""; ?>> <?= htmlspecialchars($kolom["label"]); ?></label><?php endforeach; ?></fieldset>
                <div class="form-actions"><a class="btn btn-secondary" href="../karyawan.php">Batal</a><button class="btn btn-primary" type="submit" name="download" value="1">Unduh Excel</button></div>
            </form>
        </div>
    </section>
    <section class="data-card export-preview-card">
        <div class="data-card-header"><h2>Pratinjau Data Karyawan</h2><p><?= mysqli_num_rows($query); ?> data siap diekspor.</p></div>
        <div class="table-wrapper no-actions export-preview-table"><table><thead><tr><?php foreach ($kolomDipilih as $namaKolom): ?><th><?= htmlspecialchars($kolomExportPilihan[$namaKolom]["label"]); ?></th><?php endforeach; ?></tr></thead><tbody><?php while ($barisPreview = mysqli_fetch_assoc($query)): ?><tr><?php foreach ($kolomDipilih as $namaKolom): ?><td><?= htmlspecialchars((string) ($barisPreview[$namaKolom] ?? "-")); ?></td><?php endforeach; ?></tr><?php endwhile; ?></tbody></table></div>
    </section>
    <script>
        (() => {
            const form = document.querySelector('.export-options-form');
            if (!form) return;
            form.querySelectorAll('select, input[type="checkbox"]').forEach(field => {
                field.addEventListener('change', () => {
                    if (field.name === 'kolom[]' && !form.querySelectorAll('input[name="kolom[]"]:checked').length) {
                        field.checked = true;
                        return;
                    }
                    form.querySelectorAll('button[name="download"]').forEach(button => button.removeAttribute('name'));
                    form.submit();
                });
            });
        })();
    </script>
    <?php
    require __DIR__ . '/../../../resources/views/layouts/bawah.php';
    exit;
}

/*
|--------------------------------------------------------------------------
| Utilitas penyusun sel XLSX
|--------------------------------------------------------------------------
*/

// Escape teks agar aman dalam XML.
function xmlAman($nilai): string
{
    return htmlspecialchars(
        (string) ($nilai ?? ""),
        ENT_XML1 | ENT_QUOTES,
        "UTF-8"
    );
}

// Sel teks memakai inline string agar tidak perlu sharedStrings.
function selTeks(string $ref, $nilai, int $style = 0): string
{
    $atribut = ' r="' . $ref . '"';
    if ($style > 0) {
        $atribut .= ' s="' . $style . '"';
    }
    return '<c' . $atribut . ' t="inlineStr"><is><t xml:space="preserve">'
        . xmlAman($nilai)
        . '</t></is></c>';
}

// Sel angka. $nilai harus berupa string angka dengan titik desimal.
function selAngka(string $ref, string $nilai, int $style = 0): string
{
    $atribut = ' r="' . $ref . '"';
    if ($style > 0) {
        $atribut .= ' s="' . $style . '"';
    }
    return '<c' . $atribut . '><v>' . $nilai . '</v></c>';
}

/*
|--------------------------------------------------------------------------
| Menyusun baris data worksheet
|--------------------------------------------------------------------------
*/

$kolom = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K"];

$judulKolom = ["No."];
foreach ($kolomDipilih as $namaKolom) $judulKolom[] = $kolomExportPilihan[$namaKolom]["label"];

// Style: 1 = header, 2 = angka gaji (#,##0.00).
$barisXml = "<row r=\"1\">";
foreach ($judulKolom as $i => $judul) {
    $barisXml .= selTeks($kolom[$i] . "1", $judul, 1);
}
$barisXml .= "</row>";

$nomor = 1;
$nomorBaris = 2;

while ($row = mysqli_fetch_assoc($query)) {
    $gender = trim((string) ($row["gender"] ?? ""));
    if ($gender === "M" || strcasecmp($gender, "Male") === 0) {
        $genderTampil = "Laki-laki";
    } elseif ($gender === "F" || strcasecmp($gender, "Female") === 0) {
        $genderTampil = "Perempuan";
    } else {
        $genderTampil = $gender;
    }

    $tanggalMasuk = trim((string) ($row["date_of_hire"] ?? ""));
    if ($tanggalMasuk !== "" && strtotime($tanggalMasuk) !== false) {
        $tanggalMasuk = date("d-m-Y", strtotime($tanggalMasuk));
    }

    $gaji = number_format((float) ($row["salary"] ?? 0), 2, ".", "");
    $skor = tampilkanSkorPerforma($row["performance_score"] ?? null, "");

    $barisXml .= "<row r=\"{$nomorBaris}\">";
    $barisXml .= selAngka($kolom[0] . $nomorBaris, (string) $nomor);
    foreach ($kolomDipilih as $index => $namaKolom) {
        $nilai = $row[$namaKolom] ?? "";
        if ($namaKolom === "gender") $nilai = $genderTampil;
        if ($namaKolom === "date_of_hire") $nilai = $tanggalMasuk;
        if ($namaKolom === "salary") $barisXml .= selAngka($kolom[$index + 1] . $nomorBaris, $gaji, 2);
        elseif ($namaKolom === "performance_score") {
            $barisXml .= $skor === ""
                ? selTeks($kolom[$index + 1] . $nomorBaris, "")
                : selAngka($kolom[$index + 1] . $nomorBaris, $skor);
        }
        else $barisXml .= selTeks($kolom[$index + 1] . $nomorBaris, $nilai);
    }
    $barisXml .= "</row>";

    $nomor++;
    $nomorBaris++;
}

$barisTerakhir = $nomorBaris - 1;

/*
|--------------------------------------------------------------------------
| Menyusun seluruh bagian paket XLSX (OOXML)
|--------------------------------------------------------------------------
*/

$xmlDeklarasi = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

$contentTypes = $xmlDeklarasi
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '</Types>';

$relsUtama = $xmlDeklarasi
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$workbook = $xmlDeklarasi
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
    . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Data Karyawan" sheetId="1" r:id="rId1"/></sheets>'
    . '</workbook>';

$workbookRels = $xmlDeklarasi
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$styles = $xmlDeklarasi
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
    . '<fonts count="2">'
    . '<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>'
    . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
    . '</fonts>'
    . '<fills count="3">'
    . '<fill><patternFill patternType="none"/></fill>'
    . '<fill><patternFill patternType="gray125"/></fill>'
    . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>'
    . '</fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="3">'
    . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
    . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
    . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
    . '</cellXfs>'
    . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
    . '</styleSheet>';

$sheet = $xmlDeklarasi
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
    . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
     . '<dimension ref="A1:' . $kolom[count($judulKolom) - 1] . $barisTerakhir . '"/>'
    . '<sheetViews><sheetView workbookViewId="0">'
    . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
    . '</sheetView></sheetViews>'
    . '<sheetFormatPr defaultRowHeight="15"/>'
    . '<cols>'
    . '<col min="1" max="1" width="6"/>'
    . '<col min="2" max="2" width="14"/>'
    . '<col min="3" max="3" width="26"/>'
    . '<col min="4" max="4" width="22"/>'
    . '<col min="5" max="5" width="20"/>'
    . '<col min="6" max="6" width="16"/>'
    . '<col min="7" max="7" width="14"/>'
    . '<col min="8" max="8" width="18"/>'
    . '<col min="9" max="9" width="14"/>'
    . '<col min="10" max="10" width="16"/>'
    . '<col min="11" max="11" width="13"/>'
    . '</cols>'
    . '<sheetData>' . $barisXml . '</sheetData>'
    . '</worksheet>';

/*
|--------------------------------------------------------------------------
| Mengemas seluruh bagian menjadi satu file .xlsx (arsip ZIP)
|--------------------------------------------------------------------------
*/

$lokasiZip = tempnam(sys_get_temp_dir(), "xlsx");
if ($lokasiZip === false) {
    http_response_code(500);
    die("File sementara gagal dibuat.");
}

$zip = new ZipArchive();
if ($zip->open($lokasiZip, ZipArchive::OVERWRITE) !== true) {
    @unlink($lokasiZip);
    http_response_code(500);
    die("Arsip XLSX gagal dibuat.");
}

$zip->addFromString("[Content_Types].xml", $contentTypes);
$zip->addFromString("_rels/.rels", $relsUtama);
$zip->addFromString("xl/workbook.xml", $workbook);
$zip->addFromString("xl/_rels/workbook.xml.rels", $workbookRels);
$zip->addFromString("xl/styles.xml", $styles);
$zip->addFromString("xl/worksheets/sheet1.xml", $sheet);
$zip->close();

/*
|--------------------------------------------------------------------------
| Mengirim file ke browser
|--------------------------------------------------------------------------
*/

// Buang buffer keluaran agar file biner tidak tercampur output lain.
while (ob_get_level() > 0) {
    ob_end_clean();
}

$namaFile = "data-karyawan-" . date("Y-m-d-His") . ".xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header('Content-Disposition: attachment; filename="' . $namaFile . '"');
header("Content-Length: " . filesize($lokasiZip));
header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
header("Pragma: public");

readfile($lokasiZip);
@unlink($lokasiZip);
exit;
