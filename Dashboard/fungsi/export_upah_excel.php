<?php
declare(strict_types=1);
require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
wajibRole("admin", "superadmin", "pic", "koordinator", "manager");
$roleExport = rolePengguna();
$departmentPengguna = departmentIdPengguna();
$cari = trim((string) ($_GET["cari"] ?? ""));
$department = (int) ($_GET["department"] ?? 0);
$bulan = max(0, min(12, (int) ($_GET["bulan"] ?? 0)));
$tahun = max(0, (int) ($_GET["tahun"] ?? 0));
$position = trim((string) ($_GET["position"] ?? ""));
$batasPilihan = [10, 25, 50, 100, 250, "semua"];
$batasExport = $_GET["batas_export"] ?? "semua";
if ($batasExport !== "semua") $batasExport = max(1, min(10000, (int) $batasExport));
if (!in_array($batasExport, $batasPilihan, true) && $batasExport !== "semua") $batasExport = "semua";
$arahExport = strtoupper((string) ($_GET["arah_export"] ?? "ASC"));
if (!in_array($arahExport, ["ASC", "DESC"], true)) $arahExport = "ASC";
$sortExport = (string) ($_GET["sort_export"] ?? "employee_name");
$kolomExportPilihan = [
    "emp_id" => ["label" => "ID Karyawan", "sql" => "k.emp_id"],
    "employee_name" => ["label" => "Nama", "sql" => "k.employee_name"],
    "position" => ["label" => "Posisi", "sql" => "k.position"],
    "department" => ["label" => "Departemen", "sql" => "k.department"],
    "gaji_pokok" => ["label" => "Gaji Pokok", "sql" => "gaji_pokok"],
    "uang_makan" => ["label" => "Uang Makan", "sql" => "uang_makan"],
    "upah_lembur" => ["label" => "Upah Lembur", "sql" => "upah_lembur"],
    "berlaku_mulai" => ["label" => "Berlaku Mulai", "sql" => "berlaku_mulai"],
];
$kolomExportUrutan = $kolomExportPilihan;
$komponenPendapatan = mysqli_query($conn, "SELECT id, nama FROM jenis_komponen_gaji WHERE kategori = 'pendapatan' ORDER BY nama ASC");
$komponenPotongan = mysqli_query($conn, "SELECT id, nama FROM jenis_komponen_gaji WHERE kategori = 'potongan' ORDER BY nama ASC");
$dynamicExportColumns = [];
$namaKomponenPendapatan = [];
if ($komponenPendapatan) while ($komponen = mysqli_fetch_assoc($komponenPendapatan)) {
    $key = "komponen_pendapatan_" . (int) $komponen["id"];
    $dynamicExportColumns[$key] = ["label" => (string) $komponen["nama"], "kategori" => "pendapatan", "jenis_id" => (int) $komponen["id"]];
    $namaKomponenPendapatan[mb_strtolower(trim((string) $komponen["nama"]))] = true;
}
$namaKomponenPotongan = [];
if ($komponenPotongan) while ($komponen = mysqli_fetch_assoc($komponenPotongan)) {
    $key = "komponen_potongan_" . (int) $komponen["id"];
    $dynamicExportColumns[$key] = ["label" => (string) $komponen["nama"], "kategori" => "potongan", "jenis_id" => (int) $komponen["id"]];
    $namaKomponenPotongan[mb_strtolower(trim((string) $komponen["nama"]))] = true;
}
$namaPendapatanManual = mysqli_query($conn, "SELECT DISTINCT nama FROM pendapatan_tambahan_karyawan ORDER BY nama ASC");
if ($namaPendapatanManual) while ($item = mysqli_fetch_assoc($namaPendapatanManual)) {
    $nama = (string) $item["nama"];
    if (isset($namaKomponenPendapatan[mb_strtolower(trim($nama))])) continue;
    $key = "pendapatan_manual_" . count($dynamicExportColumns);
    $dynamicExportColumns[$key] = ["label" => $nama, "kategori" => "pendapatan_manual", "nama" => $nama];
}
$namaPotonganManual = mysqli_query($conn, "SELECT DISTINCT nama FROM potongan_karyawan ORDER BY nama ASC");
if ($namaPotonganManual) while ($item = mysqli_fetch_assoc($namaPotonganManual)) {
    $nama = (string) $item["nama"];
    if (isset($namaKomponenPotongan[mb_strtolower(trim($nama))])) continue;
    $key = "potongan_manual_" . count($dynamicExportColumns);
    $dynamicExportColumns[$key] = ["label" => $nama, "kategori" => "potongan_manual", "nama" => $nama];
}
foreach ($dynamicExportColumns as $key => $column) $kolomExportPilihan[$key] = ["label" => $column["label"], "sql" => "k.employee_name"];
$kolomDipilih = $_GET["kolom"] ?? array_keys($kolomExportPilihan);
if (!is_array($kolomDipilih)) $kolomDipilih = [$kolomDipilih];
$kolomDipilih = array_values(array_intersect(array_keys($kolomExportPilihan), $kolomDipilih));
if ($kolomDipilih === []) $kolomDipilih = array_keys($kolomExportPilihan);
if (!isset($kolomExportUrutan[$sortExport])) $sortExport = "employee_name";
$departemenPilihan = mysqli_query($conn, "SELECT id, nama FROM master_departemen WHERE is_active = 1 ORDER BY nama ASC");
$where = ["1=1"];
if (in_array($roleExport, ["pic", "koordinator", "manager"], true)) $where[] = "k.department_id = " . (int) ($departmentPengguna ?? 0);
if ($cari !== "") { $safe = mysqli_real_escape_string($conn, $cari); $where[] = "(k.employee_name LIKE '%$safe%' OR k.emp_id LIKE '%$safe%')"; }
if ($department > 0) $where[] = "k.department_id = $department";
if ($position !== "") { $safePosition = mysqli_real_escape_string($conn, $position); $where[] = "k.position = '$safePosition'"; }
if ($bulan > 0) $where[] = "MONTH(COALESCE(pg.berlaku_mulai, k.date_of_hire)) = $bulan";
if ($tahun > 0) $where[] = "YEAR(COALESCE(pg.berlaku_mulai, k.date_of_hire)) = $tahun";
$sortSql = $kolomExportPilihan[$sortExport]["sql"] ?? "k.employee_name";
$sql = "SELECT k.id AS karyawan_id, k.emp_id, k.employee_name, k.position, k.department, pg.id AS profil_id, COALESCE(pg.gaji_pokok, k.salary, 0) AS gaji_pokok, COALESCE(pg.uang_makan, 0) AS uang_makan, COALESCE(lembur.total_upah, 0) AS upah_lembur, COALESCE(pg.berlaku_mulai, k.date_of_hire) AS berlaku_mulai FROM karyawan k LEFT JOIN profil_gaji pg ON pg.karyawan_id=k.id LEFT JOIN (SELECT o.karyawan_id, SUM(oc.jumlah_upah) total_upah FROM overtime_reports o INNER JOIN overtime_compensations oc ON oc.overtime_id=o.id WHERE o.status IN ('disetujui','selesai') GROUP BY o.karyawan_id) lembur ON lembur.karyawan_id=k.id WHERE " . implode(" AND ", $where) . " ORDER BY " . $sortSql . " " . $arahExport . ", k.employee_name ASC" . ($batasExport === "semua" ? "" : " LIMIT " . (int) $batasExport);
$result = mysqli_query($conn, $sql);
if (!$result) { http_response_code(500); exit("Data upah gagal diproses."); }
$rows = []; while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
$karyawanIds = array_values(array_filter(array_map(static fn (array $row): int => (int) $row["karyawan_id"], $rows)));
$profilIds = array_values(array_filter(array_map(static fn (array $row): int => (int) ($row["profil_id"] ?? 0), $rows)));
$nilaiKomponen = [];
if ($profilIds !== []) {
    $hasilKomponen = mysqli_query($conn, "SELECT profil_gaji_id, jenis_komponen_id, nilai FROM komponen_gaji_karyawan WHERE profil_gaji_id IN (" . implode(",", $profilIds) . ")");
    if ($hasilKomponen) while ($item = mysqli_fetch_assoc($hasilKomponen)) $nilaiKomponen[(int) $item["profil_gaji_id"]][(int) $item["jenis_komponen_id"]] = (float) $item["nilai"];
}
$nilaiManual = [];
if ($karyawanIds !== []) {
    $hasilManual = mysqli_query($conn, "SELECT karyawan_id, nama, nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id IN (" . implode(",", $karyawanIds) . ")");
    if ($hasilManual) while ($item = mysqli_fetch_assoc($hasilManual)) $nilaiManual[(int) $item["karyawan_id"]]["pendapatan"][mb_strtolower((string) $item["nama"])] = ($nilaiManual[(int) $item["karyawan_id"]]["pendapatan"][mb_strtolower((string) $item["nama"])] ?? 0) + (float) $item["nilai"];
    $hasilManualPotongan = mysqli_query($conn, "SELECT karyawan_id, nama, nilai FROM potongan_karyawan WHERE karyawan_id IN (" . implode(",", $karyawanIds) . ")");
    if ($hasilManualPotongan) while ($item = mysqli_fetch_assoc($hasilManualPotongan)) $nilaiManual[(int) $item["karyawan_id"]]["potongan"][mb_strtolower((string) $item["nama"])] = ($nilaiManual[(int) $item["karyawan_id"]]["potongan"][mb_strtolower((string) $item["nama"])] ?? 0) + (float) $item["nilai"];
}
foreach ($rows as &$row) {
    $row["_dynamic"] = [];
    foreach ($dynamicExportColumns as $key => $column) {
        $nilai = 0.0;
        if (isset($column["jenis_id"])) $nilai = (float) ($nilaiKomponen[(int) ($row["profil_id"] ?? 0)][(int) $column["jenis_id"]] ?? 0) + (float) ($nilaiManual[(int) $row["karyawan_id"]][$column["kategori"]][mb_strtolower((string) $column["label"])] ?? 0);
        else $nilai = (float) ($nilaiManual[(int) $row["karyawan_id"]][$column["kategori"] === "pendapatan_manual" ? "pendapatan" : "potongan"][mb_strtolower((string) $column["nama"])] ?? 0);
        $row["_dynamic"][$key] = $nilai;
        $row[$key] = $nilai;
    }
}
unset($row);
if (isset($_GET["download"])) {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8"); header('Content-Disposition: attachment; filename="data-upah-' . date("Y-m-d") . '.xls"'); echo "\xEF\xBB\xBF<table border='1'><tr>";
    foreach ($kolomDipilih as $namaKolom) echo "<th>" . htmlspecialchars($kolomExportPilihan[$namaKolom]["label"], ENT_QUOTES, "UTF-8") . "</th>";
    echo "</tr>";
    foreach ($rows as $row) { echo "<tr>"; foreach ($kolomDipilih as $namaKolom) echo "<td>" . htmlspecialchars((string) ($row[$namaKolom] ?? ""), ENT_QUOTES, "UTF-8") . "</td>"; echo "</tr>"; } echo "</table>"; exit;
}
$judulHalaman = "Pratinjau Export Upah"; $subjudulHalaman = "Periksa data upah sebelum mengunduh Excel."; $halamanAktif = "upah"; require __DIR__ . "/../partials/atas.php";
?>
<div class="export-preview-top-actions"><a class="btn btn-secondary" href="../upah.php">Kembali</a><a class="btn btn-success" href="export_upah_excel.php?download=1&amp;<?= htmlspecialchars(http_build_query($_GET)); ?>">Unduh Excel</a></div>
<section class="form-card export-options-card"><div class="form-card-header"><h2>Opsi Export Data Upah</h2><p><?= count($rows); ?> data sesuai filter.</p></div><div class="form-body"><form method="GET" class="export-options-form"><div class="form-group"><label for="batas_export">Jumlah data</label><select id="batas_export" name="batas_export"><?php foreach ($batasPilihan as $pilihan): ?><option value="<?= $pilihan; ?>" <?= (string) $batasExport === (string) $pilihan ? "selected" : ""; ?>><?= $pilihan === "semua" ? "Semua data" : "Maksimal " . $pilihan . " data"; ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="sort_export">Urutkan berdasarkan</label><select id="sort_export" name="sort_export"><?php foreach ($kolomExportUrutan as $kunci => $kolom): ?><option value="<?= $kunci; ?>" <?= $sortExport === $kunci ? "selected" : ""; ?>><?= htmlspecialchars($kolom["label"]); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="arah_export">Arah urutan</label><select id="arah_export" name="arah_export"><option value="ASC" <?= $arahExport === "ASC" ? "selected" : ""; ?>>Naik</option><option value="DESC" <?= $arahExport === "DESC" ? "selected" : ""; ?>>Turun</option></select></div><?php foreach (["cari" => $cari, "department" => $department, "bulan" => $bulan, "tahun" => $tahun, "position" => $position] as $nama => $nilai): ?><input type="hidden" name="<?= $nama; ?>" value="<?= htmlspecialchars((string) $nilai); ?>"><?php endforeach; ?><fieldset class="export-columns-fieldset"><legend>Kolom yang diekspor</legend><?php foreach ($kolomExportPilihan as $kunci => $kolom): ?><label><input type="checkbox" name="kolom[]" value="<?= $kunci; ?>" <?= in_array($kunci, $kolomDipilih, true) ? "checked" : ""; ?>> <?= htmlspecialchars($kolom["label"]); ?></label><?php endforeach; ?></fieldset><div class="form-actions"><a class="btn btn-secondary" href="../upah.php">Batal</a><button class="btn btn-success" type="submit">Terapkan Opsi</button><button class="btn btn-primary" type="submit" name="download" value="1">Unduh Excel</button></div></form></div></section><section class="data-card"><div class="data-card-header"><h2>Pratinjau Export Data Upah</h2></div><div class="table-wrapper"><table><thead><tr><?php foreach ($kolomDipilih as $namaKolom): ?><th><?= htmlspecialchars($kolomExportPilihan[$namaKolom]["label"]); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($kolomDipilih as $namaKolom): ?><td><?= htmlspecialchars((string) ($row[$namaKolom] ?? "")); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></section>
<script>
(() => {
    const form = document.querySelector('.export-options-form');
    if (!form) return;
    const applyButton = [...form.querySelectorAll('button')].find(button => button.textContent.trim() === 'Terapkan Opsi');
    if (applyButton) applyButton.remove();
    form.querySelectorAll('select, input[type="checkbox"]').forEach(field => {
        field.addEventListener('change', () => {
            if (field.name === 'kolom[]' && !form.querySelectorAll('input[name="kolom[]"]:checked').length) {
                field.checked = true;
                return;
            }
            form.submit();
        });
    });
})();
</script>
<?php require __DIR__ . "/../partials/bawah.php"; ?>
