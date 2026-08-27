<?php

declare(strict_types=1);

/**
 * Konversi nomor kolom berbasis 1 menjadi huruf kolom Excel (1 -> A, 27 -> AA).
 */
function nomorKolomExcel(int $index): string
{
    $huruf = '';
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $huruf = chr(65 + $mod) . $huruf;
        $index = intdiv($index - $mod, 26);
    }
    return $huruf ?: 'A';
}

/**
 * Escape teks agar valid di dalam XML.
 */
function xmlAmanXlsx($nilai): string
{
    return htmlspecialchars((string) ($nilai ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Membuat dan mengunduh berkas spreadsheet asli berekstensi .xlsx (OpenXML).
 * Tidak memicu peringatan format mismatch pada Microsoft Excel.
 *
 * @param string $namaFile   Nama berkas tanpa atau dengan ekstensi .xlsx
 * @param string $namaSheet  Nama worksheet
 * @param string[] $headers  Array judul kolom header
 * @param array[] $rows      Array 2D data baris
 * @param array $kolomFormat Array asosiatif konfigurasi format kolom (opsional)
 */
function unduhSpreadsheetXlsx(
    string $namaFile,
    string $namaSheet,
    array $headers,
    array $rows,
    array $kolomFormat = []
): void {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('Ekstensi PHP ZipArchive diperlukan untuk menghasilkan berkas .xlsx.');
    }

    if (!str_ends_with(strtolower($namaFile), '.xlsx')) {
        $namaFile .= '.xlsx';
    }

    $namaSheetBersih = mb_substr(preg_replace('/[\\\\\/*?:\[\]]/', '', $namaSheet) ?: 'Data', 0, 31);
    $jumlahKolom = max(1, count($headers));
    $xmlDeklarasi = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

    // Hitung perkiraan lebar kolom otomatis berdasarkan panjang teks
    $lebarKolom = [];
    foreach ($headers as $i => $h) {
        $lebarKolom[$i] = max(10, min(45, mb_strlen((string) $h) + 4));
    }

    // Stylesheet definition
    // Style 0: Default font regular
    // Style 1: Header (Dark navy #0F172A, Bold white text, centered, thin border)
    // Style 2: Number with decimal #,##0.00
    // Style 3: Number integer #,##0
    // Style 4: Center aligned
    $styles = $xmlDeklarasi
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="2">'
        . '<numFmt numFmtId="164" formatCode="#,##0.00"/>'
        . '<numFmt numFmtId="165" formatCode="#,##0"/>'
        . '</numFmts>'
        . '<fonts count="2">'
        . '<font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="3">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border>'
        . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
        . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
        . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
        . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
        . '<diagonal/>'
        . '</border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="5">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
        . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';

    // Bangun baris header
    $barisXml = '<row r="1" ht="26" customHeight="1">';
    foreach ($headers as $colIdx => $headerTitle) {
        $colLetter = nomorKolomExcel($colIdx + 1);
        $barisXml .= '<c r="' . $colLetter . '1" s="1" t="inlineStr"><is><t xml:space="preserve">'
            . xmlAmanXlsx($headerTitle)
            . '</t></is></c>';
    }
    $barisXml .= '</row>';

    // Bangun baris data
    $nomorBaris = 2;
    foreach ($rows as $row) {
        $barisXml .= '<row r="' . $nomorBaris . '">';
        $colIdx = 0;
        foreach ($row as $val) {
            $colLetter = nomorKolomExcel($colIdx + 1);
            $ref = $colLetter . $nomorBaris;
            $format = $kolomFormat[$colIdx] ?? null;

            $panjangTeks = mb_strlen((string) ($val ?? ''));
            if (isset($lebarKolom[$colIdx])) {
                $lebarKolom[$colIdx] = max($lebarKolom[$colIdx], min(50, $panjangTeks + 3));
            }

            if ($format === 'money' || (is_numeric($val) && str_contains((string) $val, '.'))) {
                $numVal = (float) $val;
                $barisXml .= '<c r="' . $ref . '" s="2"><v>' . number_format($numVal, 2, '.', '') . '</v></c>';
            } elseif ($format === 'integer' || (is_int($val) || (is_numeric($val) && (string)(int)$val === (string)$val && strlen((string)$val) < 11 && !str_starts_with((string)$val, '0')))) {
                $numVal = (int) $val;
                $barisXml .= '<c r="' . $ref . '" s="3"><v>' . $numVal . '</v></c>';
            } elseif ($format === 'center') {
                $barisXml .= '<c r="' . $ref . '" s="4" t="inlineStr"><is><t xml:space="preserve">'
                    . xmlAmanXlsx($val)
                    . '</t></is></c>';
            } else {
                $barisXml .= '<c r="' . $ref . '" s="0" t="inlineStr"><is><t xml:space="preserve">'
                    . xmlAmanXlsx($val)
                    . '</t></is></c>';
            }
            $colIdx++;
        }
        $barisXml .= '</row>';
        $nomorBaris++;
    }

    $barisTerakhir = max(1, $nomorBaris - 1);
    $kolomTerakhir = nomorKolomExcel($jumlahKolom);

    // Definisikan lebar kolom XML
    $colsXml = '<cols>';
    foreach ($lebarKolom as $idx => $width) {
        $cNum = $idx + 1;
        $colsXml .= '<col min="' . $cNum . '" max="' . $cNum . '" width="' . $width . '" customWidth="1"/>';
    }
    $colsXml .= '</cols>';

    // Worksheet XML
    $worksheet = $xmlDeklarasi
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
        . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<dimension ref="A1:' . $kolomTerakhir . $barisTerakhir . '"/>'
        . '<sheetViews><sheetView workbookViewId="0">'
        . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
        . '</sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>'
        . $colsXml
        . '<sheetData>' . $barisXml . '</sheetData>'
        . '</worksheet>';

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
        . '<sheets><sheet name="' . xmlAmanXlsx($namaSheetBersih) . '" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';

    $workbookRels = $xmlDeklarasi
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $lokasiZip = tempnam(sys_get_temp_dir(), 'xlsx');
    if ($lokasiZip === false) {
        http_response_code(500);
        exit('File sementara gagal dibuat.');
    }

    $zip = new ZipArchive();
    if ($zip->open($lokasiZip, ZipArchive::OVERWRITE) !== true) {
        @unlink($lokasiZip);
        http_response_code(500);
        exit('Arsip XLSX gagal dibuat.');
    }

    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $relsUtama);
    $zip->addFromString('xl/workbook.xml', $workbook);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    $zip->addFromString('xl/styles.xml', $styles);
    $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);
    $zip->close();

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    header('Content-Length: ' . (string) filesize($lokasiZip));
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: public');

    readfile($lokasiZip);
    @unlink($lokasiZip);
    exit;
}
