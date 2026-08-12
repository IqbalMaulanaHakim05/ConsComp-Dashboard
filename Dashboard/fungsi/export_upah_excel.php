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
$departemenPilihan = mysqli_query($conn, "SELECT id, nama FROM master_departemen WHERE is_active = 1 ORDER BY nama ASC");
$where = ["1=1"];
if (in_array($roleExport, ["pic", "koordinator", "manager"], true)) $where[] = "k.department_id = " . (int) ($departmentPengguna ?? 0);
if ($cari !== "") { $safe = mysqli_real_escape_string($conn, $cari); $where[] = "(k.employee_name LIKE '%$safe%' OR k.emp_id LIKE '%$safe%')"; }
if ($department > 0) $where[] = "k.department_id = $department";
if ($position !== "") { $safePosition = mysqli_real_escape_string($conn, $position); $where[] = "k.position = '$safePosition'"; }
if ($bulan > 0) $where[] = "MONTH(COALESCE(pg.berlaku_mulai, k.date_of_hire)) = $bulan";
if ($tahun > 0) $where[] = "YEAR(COALESCE(pg.berlaku_mulai, k.date_of_hire)) = $tahun";
$sql = "SELECT k.emp_id, k.employee_name, k.position, k.department, COALESCE(pg.gaji_pokok, k.salary, 0) AS gaji_pokok, COALESCE(pg.uang_makan, 0) AS uang_makan, COALESCE(lembur.total_upah, 0) AS upah_lembur, COALESCE(pg.berlaku_mulai, k.date_of_hire) AS berlaku_mulai FROM karyawan k LEFT JOIN profil_gaji pg ON pg.karyawan_id=k.id LEFT JOIN (SELECT o.karyawan_id, SUM(oc.jumlah_upah) total_upah FROM overtime_reports o INNER JOIN overtime_compensations oc ON oc.overtime_id=o.id WHERE o.status IN ('disetujui','selesai') GROUP BY o.karyawan_id) lembur ON lembur.karyawan_id=k.id WHERE " . implode(" AND ", $where) . " ORDER BY k.employee_name";
$result = mysqli_query($conn, $sql);
if (!$result) { http_response_code(500); exit("Data upah gagal diproses."); }
$rows = []; while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
if (isset($_GET["download"])) {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8"); header('Content-Disposition: attachment; filename="data-upah-' . date("Y-m-d") . '.xls"'); echo "\xEF\xBB\xBF<table border='1'><tr><th>ID</th><th>Nama</th><th>Posisi</th><th>Departemen</th><th>Gaji Pokok</th><th>Uang Makan</th><th>Upah Lembur</th><th>Berlaku Mulai</th></tr>";
    foreach ($rows as $row) { echo "<tr>"; foreach ($row as $value) echo "<td>" . htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8") . "</td>"; echo "</tr>"; } echo "</table>"; exit;
}
$judulHalaman = "Pratinjau Export Upah"; $subjudulHalaman = "Periksa data upah sebelum mengunduh Excel."; $halamanAktif = "upah"; require __DIR__ . "/../partials/atas.php";
?>
<div class="export-preview-top-actions"><a class="btn btn-secondary" href="../upah.php">Kembali</a><a class="btn btn-success" href="export_upah_excel.php?download=1&amp;<?= htmlspecialchars(http_build_query($_GET)); ?>">Unduh Excel</a></div>
<section class="data-card"><div class="data-card-header"><h2>Pratinjau Export Data Upah</h2><p><?= count($rows); ?> data sesuai filter.</p></div><div class="table-wrapper"><table><thead><tr><th>ID</th><th>Nama</th><th>Posisi</th><th>Departemen</th><th>Gaji Pokok</th><th>Uang Makan</th><th>Upah Lembur</th><th>Berlaku Mulai</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $value): ?><td><?= htmlspecialchars((string) $value); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><div style="display:flex;justify-content:flex-end;gap:10px;padding:20px"><a class="btn btn-secondary" href="../upah.php">Kembali</a><a class="btn btn-success" href="export_upah_excel.php?download=1&amp;<?= htmlspecialchars(http_build_query($_GET)); ?>">Unduh Excel</a></div></section>
<?php require __DIR__ . "/../partials/bawah.php"; ?>
