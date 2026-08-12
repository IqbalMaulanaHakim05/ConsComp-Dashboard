<?php

require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";

wajibRole("admin", "superadmin", "manager");

$departmentIdPengguna = departmentIdPengguna();
$filterDepartemen = rolePengguna() === "manager"
    ? " WHERE department_id = " . (int) ($departmentIdPengguna ?? 0)
    : "";
$kataKunci = trim((string) ($_GET["cari"] ?? ""));
$filterKolom = (string) ($_GET["filter"] ?? "semua");
$kolomExport = [
    "semua" => "employee_name,emp_id,position,department,salary,date_of_hire,employment_status,performance_score",
    "id" => "emp_id", "posisi" => "position", "departemen" => "department",
    "gaji" => "salary", "tanggal_masuk" => "date_of_hire", "status_kerja" => "employment_status", "performa" => "performance_score"
];
if (!isset($kolomExport[$filterKolom])) $filterKolom = "semua";
$kondisiExport = "";
if ($kataKunci !== "") {
    $safeKunci = mysqli_real_escape_string($conn, $kataKunci);
    $nilaiCari = "'%" . $safeKunci . "%'";
    $kondisiExport = $filterKolom === "semua"
        ? "(" . implode(" LIKE $nilaiCari OR ", explode(",", $kolomExport[$filterKolom])) . " LIKE $nilaiCari)"
        : $kolomExport[$filterKolom] . " LIKE $nilaiCari";
}
$filterSql = $filterDepartemen === "" ? " WHERE 1=1" : $filterDepartemen;
if ($kondisiExport !== "") $filterSql .= " AND " . $kondisiExport;

if (!class_exists("ZipArchive")) {
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
     ORDER BY id ASC"
);

if (!$query) {
    http_response_code(500);
    die("Data gagal diekspor: " . mysqli_error($conn));
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

$judulKolom = [
    "No.",
    "ID Karyawan",
    "Nama",
    "Posisi",
    "Departemen",
    "Gaji",
    "Jenis Kelamin",
    "Status Pernikahan",
    "Tanggal Masuk",
    "Status Kerja",
    "Skor Performa",
];

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
    $skor = (string) (int) ($row["performance_score"] ?? 0);

    $barisXml .= "<row r=\"{$nomorBaris}\">";
    $barisXml .= selAngka($kolom[0] . $nomorBaris, (string) $nomor);
    $barisXml .= selTeks($kolom[1] . $nomorBaris, $row["emp_id"] ?? "");
    $barisXml .= selTeks($kolom[2] . $nomorBaris, $row["employee_name"] ?? "");
    $barisXml .= selTeks($kolom[3] . $nomorBaris, $row["position"] ?? "");
    $barisXml .= selTeks($kolom[4] . $nomorBaris, $row["department"] ?? "");
    $barisXml .= selAngka($kolom[5] . $nomorBaris, $gaji, 2);
    $barisXml .= selTeks($kolom[6] . $nomorBaris, $genderTampil);
    $barisXml .= selTeks($kolom[7] . $nomorBaris, $row["marital_status"] ?? "");
    $barisXml .= selTeks($kolom[8] . $nomorBaris, $tanggalMasuk);
    $barisXml .= selTeks($kolom[9] . $nomorBaris, $row["employment_status"] ?? "");
    $barisXml .= selAngka($kolom[10] . $nomorBaris, $skor);
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
    . '<dimension ref="A1:K' . $barisTerakhir . '"/>'
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
