<?php
require __DIR__ . "/../koneksi.php";

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
     ORDER BY id ASC"
);

if (!$query) {
    http_response_code(500);
    die("Data gagal diekspor: " . mysqli_error($conn));
}

function xmlAman($nilai): string
{
    return htmlspecialchars(
        (string) ($nilai ?? ""),
        ENT_XML1 | ENT_QUOTES,
        "UTF-8"
    );
}

function selString($nilai, string $style = ""): string
{
    $atributStyle = $style !== ""
        ? ' ss:StyleID="' . xmlAman($style) . '"'
        : "";

    return '<Cell' . $atributStyle . '><Data ss:Type="String">'
        . xmlAman($nilai)
        . '</Data></Cell>';
}

function selAngka($nilai, string $style = ""): string
{
    $atributStyle = $style !== ""
        ? ' ss:StyleID="' . xmlAman($style) . '"'
        : "";

    $angka = is_numeric($nilai) ? (float) $nilai : 0;

    return '<Cell' . $atributStyle . '><Data ss:Type="Number">'
        . $angka
        . '</Data></Cell>';
}

$namaFile = "data-karyawan-" . date("Y-m-d-His") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $namaFile . '"');
header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
header("Pragma: public");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
?>
<Workbook
    xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40"
>
    <Styles>
        <Style ss:ID="Default" ss:Name="Normal">
            <Alignment ss:Vertical="Center"/>
            <Font ss:FontName="Arial" ss:Size="10"/>
        </Style>
        <Style ss:ID="Judul">
            <Font ss:FontName="Arial" ss:Size="15" ss:Bold="1"/>
            <Alignment ss:Vertical="Center"/>
        </Style>
        <Style ss:ID="Header">
            <Font ss:FontName="Arial" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/>
            <Interior ss:Color="#0F172A" ss:Pattern="Solid"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/>
            </Borders>
        </Style>
        <Style ss:ID="Data">
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
            </Borders>
        </Style>
        <Style ss:ID="Uang">
            <NumberFormat ss:Format="#,##0.00"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
            </Borders>
        </Style>
        <Style ss:ID="Skor">
            <NumberFormat ss:Format="0"/>
            <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
            <Borders>
                <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
                <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>
            </Borders>
        </Style>
    </Styles>

    <Worksheet ss:Name="Data Karyawan">
        <Table>
            <Column ss:Width="45"/>
            <Column ss:Width="90"/>
            <Column ss:Width="160"/>
            <Column ss:Width="130"/>
            <Column ss:Width="120"/>
            <Column ss:Width="90"/>
            <Column ss:Width="95"/>
            <Column ss:Width="110"/>
            <Column ss:Width="100"/>
            <Column ss:Width="125"/>
            <Column ss:Width="90"/>

            <Row ss:Height="25">
                <Cell ss:MergeAcross="10" ss:StyleID="Judul">
                    <Data ss:Type="String">Data Karyawan</Data>
                </Cell>
            </Row>
            <Row>
                <Cell ss:MergeAcross="10">
                    <Data ss:Type="String">Diekspor dari database SQL pada <?= xmlAman(date("d-m-Y H:i:s")); ?></Data>
                </Cell>
            </Row>
            <Row/>
            <Row ss:Height="30">
                <?= selString("No.", "Header"); ?>
                <?= selString("ID Karyawan", "Header"); ?>
                <?= selString("Nama", "Header"); ?>
                <?= selString("Posisi", "Header"); ?>
                <?= selString("Departemen", "Header"); ?>
                <?= selString("Gaji", "Header"); ?>
                <?= selString("Jenis Kelamin", "Header"); ?>
                <?= selString("Status Pernikahan", "Header"); ?>
                <?= selString("Tanggal Masuk", "Header"); ?>
                <?= selString("Status Kerja", "Header"); ?>
                <?= selString("Skor Performa", "Header"); ?>
            </Row>

            <?php $nomor = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($query)): ?>
                <?php
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
                ?>
                <Row>
                    <?= selAngka($nomor++, "Skor"); ?>
                    <?= selString($row["emp_id"] ?? "", "Data"); ?>
                    <?= selString($row["employee_name"] ?? "", "Data"); ?>
                    <?= selString($row["position"] ?? "", "Data"); ?>
                    <?= selString($row["department"] ?? "", "Data"); ?>
                    <?= selAngka($row["salary"] ?? 0, "Uang"); ?>
                    <?= selString($genderTampil, "Data"); ?>
                    <?= selString($row["marital_status"] ?? "", "Data"); ?>
                    <?= selString($tanggalMasuk, "Data"); ?>
                    <?= selString($row["employment_status"] ?? "", "Data"); ?>
                    <?= selAngka($row["performance_score"] ?? 0, "Skor"); ?>
                </Row>
            <?php endwhile; ?>
        </Table>

        <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
            <FreezePanes/>
            <FrozenNoSplit/>
            <SplitHorizontal>4</SplitHorizontal>
            <TopRowBottomPane>4</TopRowBottomPane>
            <ActivePane>2</ActivePane>
            <ProtectObjects>False</ProtectObjects>
            <ProtectScenarios>False</ProtectScenarios>
        </WorksheetOptions>
        <AutoFilter x:Range="R4C1:R4C11" xmlns="urn:schemas-microsoft-com:office:excel"/>
    </Worksheet>
</Workbook>
