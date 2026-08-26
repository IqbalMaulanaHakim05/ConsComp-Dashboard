<?php
declare(strict_types=1);
require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/xlsx-builder.php";
wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
$departmentId = departmentIdPengguna();
$halamanAktif = "lembur";
$where = roleOperasional() ? "o.department_id = " . (int) ($departmentId ?? 0) : "1=1";
$batasPilihan = [10, 25, 50, 100, 250, "semua"];
$batasExport = $_GET["batas_export"] ?? "semua";
if ($batasExport !== "semua") $batasExport = max(1, min(10000, (int) $batasExport));
if (!in_array($batasExport, $batasPilihan, true) && $batasExport !== "semua") $batasExport = "semua";
$arahExport = strtoupper((string) ($_GET["arah_export"] ?? "DESC"));
if (!in_array($arahExport, ["ASC", "DESC"], true)) $arahExport = "DESC";
$sortExport = (string) ($_GET["sort_export"] ?? "created_at");
$kolomExportPilihan = [
    "id" => ["label" => "ID", "sql" => "o.id"],
    "emp_id" => ["label" => "ID Karyawan", "sql" => "k.emp_id"],
    "employee_name" => ["label" => "Nama", "sql" => "k.employee_name"],
    "mulai_at" => ["label" => "Mulai", "sql" => "o.mulai_at"],
    "selesai_at" => ["label" => "Selesai", "sql" => "o.selesai_at"],
    "total_menit" => ["label" => "Total Menit", "sql" => "o.total_menit"],
    "deskripsi" => ["label" => "Deskripsi", "sql" => "o.deskripsi"],
    "status" => ["label" => "Status", "sql" => "o.status"],
    "jumlah_upah" => ["label" => "Upah", "sql" => "oc.jumlah_upah"],
];
$kolomDipilih = $_GET["kolom"] ?? array_keys($kolomExportPilihan);
if (!is_array($kolomDipilih)) $kolomDipilih = [$kolomDipilih];
$kolomDipilih = array_values(array_intersect(array_keys($kolomExportPilihan), $kolomDipilih));
if ($kolomDipilih === []) $kolomDipilih = array_keys($kolomExportPilihan);
if (!isset($kolomExportPilihan[$sortExport])) $sortExport = "id";
$orderSql = $sortExport === "created_at" ? "o.created_at" : $kolomExportPilihan[$sortExport]["sql"];
$orderSql .= " " . $arahExport . ", o.id DESC";
$limitSql = $batasExport === "semua" ? "" : " LIMIT " . (int) $batasExport;
$query = mysqli_query($conn, "SELECT o.id, k.emp_id, k.employee_name, o.mulai_at, o.selesai_at, o.total_menit, o.deskripsi, o.status, oc.jumlah_upah, (SELECT u.role FROM overtime_approvals oa INNER JOIN users u ON u.id = oa.approver_user_id WHERE oa.overtime_id = o.id AND oa.status = 'approved' ORDER BY oa.decided_at DESC LIMIT 1) AS role_persetuju FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN overtime_compensations oc ON oc.overtime_id = o.id WHERE $where ORDER BY $orderSql$limitSql");
if (!$query) { http_response_code(500); exit("Data lembur gagal diproses."); }
$rows = [];
while ($row = mysqli_fetch_assoc($query)) $rows[] = $row;
if (isset($_GET["download"])) {
    $headers = [];
    foreach ($kolomDipilih as $namaKolom) {
        $headers[] = $kolomExportPilihan[$namaKolom]["label"];
    }
    $exportRows = [];
    foreach ($rows as $row) {
        $rowCells = [];
        foreach ($kolomDipilih as $namaKolom) {
            $rowCells[] = $namaKolom === "status" && in_array($row["status"], ["disetujui", "selesai"], true) ? "Disetujui " . labelRole((string) ($row["role_persetuju"] ?? "manager")) : ($row[$namaKolom] ?? "");
        }
        $exportRows[] = $rowCells;
    }
    unduhSpreadsheetXlsx("laporan-lembur-" . date("Y-m-d"), "Lembur", $headers, $exportRows);
}
require __DIR__ . "/../partials/atas.php";
?>
<section class="form-card export-options-card"><div class="form-card-header"><h2>Opsi Export Lembur</h2><p><?= count($rows); ?> data akan diekspor sesuai cakupan akses.</p></div><div class="form-body"><form method="GET" class="export-options-form"><div class="form-group"><label for="batas_export">Jumlah data</label><select id="batas_export" name="batas_export"><?php foreach ($batasPilihan as $pilihan): ?><option value="<?= $pilihan; ?>" <?= (string) $batasExport === (string) $pilihan ? "selected" : ""; ?>><?= $pilihan === "semua" ? "Semua data" : "Maksimal " . $pilihan . " data"; ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="sort_export">Urutkan berdasarkan</label><select id="sort_export" name="sort_export"><?php foreach ($kolomExportPilihan as $kunci => $kolom): ?><option value="<?= $kunci; ?>" <?= $sortExport === $kunci ? "selected" : ""; ?>><?= htmlspecialchars($kolom["label"]); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="arah_export">Arah urutan</label><select id="arah_export" name="arah_export"><option value="ASC" <?= $arahExport === "ASC" ? "selected" : ""; ?>>Naik</option><option value="DESC" <?= $arahExport === "DESC" ? "selected" : ""; ?>>Turun</option></select></div><fieldset class="export-columns-fieldset"><legend>Kolom yang diekspor</legend><?php foreach ($kolomExportPilihan as $kunci => $kolom): ?><label><input type="checkbox" name="kolom[]" value="<?= $kunci; ?>" <?= in_array($kunci, $kolomDipilih, true) ? "checked" : ""; ?>> <?= htmlspecialchars($kolom["label"]); ?></label><?php endforeach; ?></fieldset><div class="form-actions"><a class="btn btn-secondary" href="../lembur.php">Batal</a><button class="btn btn-success" type="submit">Terapkan Opsi</button><button class="btn btn-primary" type="submit" name="download" value="1">Unduh Excel</button></div></form></div></section><section class="data-card export-preview-card"><div class="data-card-header"><h2>Pratinjau Export Lembur</h2></div><div class="table-wrapper"><table><thead><tr><?php foreach ($kolomDipilih as $namaKolom): ?><th><?= htmlspecialchars($kolomExportPilihan[$namaKolom]["label"]); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($kolomDipilih as $namaKolom): ?><td><?= htmlspecialchars((string) ($namaKolom === "status" && in_array($row["status"], ["disetujui", "selesai"], true) ? "Disetujui " . labelRole((string) ($row["role_persetuju"] ?? "manager")) : ($row[$namaKolom] ?? ""))); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><div class="export-preview-actions"><a class="btn btn-secondary" href="../lembur.php">Kembali</a><a class="btn btn-success" href="export_lembur.php?download=1&amp;<?= htmlspecialchars(http_build_query($_GET)); ?>">Unduh Excel</a></div></section>
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
            form.querySelectorAll('button[name="download"]').forEach(button => button.removeAttribute('name'));
            form.submit();
        });
    });
})();
</script>
<?php require __DIR__ . "/../partials/bawah.php"; ?>
