<?php
require __DIR__ . "/koneksi.php"; require_once __DIR__ . "/fungsi/auth.php"; require_once __DIR__ . "/fungsi/master-data.php";
wajibRole("superadmin"); siapkanMasterData($conn); $pesan = "";
$map = ["departemen" => "master_departemen", "posisi" => "master_posisi", "status" => "master_status_kerja"];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $jenis = $_POST["jenis"] ?? ""; $nama = trim($_POST["nama"] ?? ""); $table = $map[$jenis] ?? null;
    if ($table && $nama !== "") { $stmt = mysqli_prepare($conn, "INSERT INTO `$table` (nama) VALUES (?)"); mysqli_stmt_bind_param($stmt, "s", $nama); $pesan = mysqli_stmt_execute($stmt) ? "Data berhasil ditambahkan." : "Data sudah ada atau tidak valid."; }
}
if (isset($_GET["hapus"], $_GET["jenis"]) && isset($map[$_GET["jenis"]])) { mysqli_query($conn, "DELETE FROM `" . $map[$_GET["jenis"]] . "` WHERE id=" . (int) $_GET["hapus"]); header("Location: master-data.php"); exit; }
$judulHalaman="Master Data"; $subjudulHalaman="Kelola departemen, posisi, dan status kerja."; $halamanAktif="master-data"; require __DIR__ . "/partials/atas.php";
?>
<section class="dashboard-chart master-data-grid">
<?php foreach ($map as $jenis => $table): $items = []; $q=mysqli_query($conn,"SELECT id,nama FROM `$table` ORDER BY nama"); while($q&&$r=mysqli_fetch_assoc($q))$items[]=$r; ?>
<article class="chart-card"><h2><?= ucfirst($jenis); ?></h2><form method="POST" class="search-form"><input type="hidden" name="jenis" value="<?= $jenis; ?>"><input name="nama" placeholder="Tambah <?= $jenis; ?>" required><button class="btn btn-success">Tambah</button></form><ul class="master-list"><?php foreach($items as $item): ?><li><?= htmlspecialchars($item["nama"]); ?><a class="btn btn-danger" href="?hapus=<?= (int)$item["id"]; ?>&jenis=<?= $jenis; ?>" onclick="return confirm('Hapus data ini?')">Hapus</a></li><?php endforeach; ?></ul></article>
<?php endforeach; ?></section><?php require __DIR__ . "/partials/bawah.php";
