<?php
declare(strict_types=1);
require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
$departmentId = departmentIdPengguna();
$halamanAktif = "lembur";
$where = roleOperasional() ? "o.department_id = " . (int) ($departmentId ?? 0) : "1=1";
$query = mysqli_query($conn, "SELECT o.id, k.emp_id, k.employee_name, o.mulai_at, o.selesai_at, o.total_menit, o.deskripsi, o.status, oc.jumlah_upah FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN overtime_compensations oc ON oc.overtime_id = o.id WHERE $where ORDER BY o.created_at DESC");
if (!$query) { http_response_code(500); exit("Data lembur gagal diproses."); }
$rows = [];
while ($row = mysqli_fetch_assoc($query)) $rows[] = $row;
if (isset($_GET["download"])) {
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header('Content-Disposition: attachment; filename="laporan-lembur-' . date("Y-m-d") . '.xls"');
    echo "\xEF\xBB\xBF";
    echo "<table border='1'><tr><th>ID</th><th>ID Karyawan</th><th>Nama</th><th>Mulai</th><th>Selesai</th><th>Total Menit</th><th>Deskripsi</th><th>Status</th><th>Upah</th></tr>";
    foreach ($rows as $row) { echo "<tr>"; foreach ([$row["id"], $row["emp_id"], $row["employee_name"], $row["mulai_at"], $row["selesai_at"], $row["total_menit"], $row["deskripsi"], $row["status"], $row["jumlah_upah"] ?? ""] as $value) echo "<td>" . htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8") . "</td>"; echo "</tr>"; }
    echo "</table>"; exit;
}
require __DIR__ . "/../partials/atas.php";
?>
<section class="data-card export-preview-card"><div class="data-card-header"><h2>Pratinjau Export Lembur</h2><p>Periksa data sebelum mengunduh file Excel.</p></div><div class="table-wrapper"><table><thead><tr><th>ID</th><th>Karyawan</th><th>Mulai</th><th>Selesai</th><th>Menit</th><th>Deskripsi</th><th>Status</th><th>Upah</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= (int) $row["id"]; ?></td><td><?= htmlspecialchars($row["emp_id"] . " - " . $row["employee_name"]); ?></td><td><?= htmlspecialchars($row["mulai_at"]); ?></td><td><?= htmlspecialchars($row["selesai_at"]); ?></td><td><?= number_format((int) $row["total_menit"], 0, ",", "."); ?></td><td><?= nl2br(htmlspecialchars($row["deskripsi"] ?? "-")); ?></td><td><?= htmlspecialchars($row["status"]); ?></td><td><?= htmlspecialchars((string) ($row["jumlah_upah"] ?? "-")); ?></td></tr><?php endforeach; ?></tbody></table></div><div class="export-preview-actions"><a class="btn btn-secondary" href="../lembur.php">Kembali</a><a class="btn btn-success" href="export_lembur.php?download=1">Unduh Excel</a></div></section>
<?php require __DIR__ . "/../partials/bawah.php"; ?>
