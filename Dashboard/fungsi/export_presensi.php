<?php

declare(strict_types=1);

require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/presensi.php";
require_once __DIR__ . "/xlsx-builder.php";

wajibRole("admin", "superadmin", "manager", "koordinator", "pic");
siapkanTabelPresensi($conn);

$departmentPengguna = departmentIdPengguna();
$halamanAktif = "presensi";

$where = ["1=1"];
if (roleOperasional()) {
    $where[] = "p.department_id = " . (int) ($departmentPengguna ?? 0);
}

$tanggal = trim((string) ($_GET["tanggal"] ?? ""));
$tanggalMulai = trim((string) ($_GET["tanggal_mulai"] ?? ""));
$tanggalSelesai = trim((string) ($_GET["tanggal_selesai"] ?? ""));
$status = trim((string) ($_GET["status"] ?? "semua"));
$department = (int) ($_GET["department"] ?? 0);
$cari = trim((string) ($_GET["cari"] ?? ""));

if ($tanggal !== "") {
    $safeTanggal = mysqli_real_escape_string($conn, $tanggal);
    $where[] = "p.tanggal = '$safeTanggal'";
} elseif ($tanggalMulai !== "" && $tanggalSelesai !== "") {
    $safeMulai = mysqli_real_escape_string($conn, $tanggalMulai);
    $safeSelesai = mysqli_real_escape_string($conn, $tanggalSelesai);
    $where[] = "p.tanggal BETWEEN '$safeMulai' AND '$safeSelesai'";
}

if ($status !== "" && $status !== "semua" && in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'], true)) {
    $safeStatus = mysqli_real_escape_string($conn, $status);
    $where[] = "p.status = '$safeStatus'";
}

if ($department > 0 && !roleOperasional()) {
    $where[] = "p.department_id = $department";
}

if ($cari !== "") {
    $safe = mysqli_real_escape_string($conn, $cari);
    $where[] = "(k.employee_name LIKE '%$safe%' OR k.emp_id LIKE '%$safe%' OR p.keterangan LIKE '%$safe%')";
}

$batasPilihan = [10, 25, 50, 100, 250, "semua"];
$batasExport = $_GET["batas_export"] ?? "semua";
if ($batasExport !== "semua") {
    $batasExport = max(1, min(10000, (int) $batasExport));
}
if (!in_array($batasExport, $batasPilihan, true) && $batasExport !== "semua") {
    $batasExport = "semua";
}

$arahExport = strtoupper((string) ($_GET["arah_export"] ?? "DESC"));
if (!in_array($arahExport, ["ASC", "DESC"], true)) {
    $arahExport = "DESC";
}

$sortExport = (string) ($_GET["sort_export"] ?? "tanggal");

$kolomExportPilihan = [
    "id" => ["label" => "ID", "sql" => "p.id"],
    "emp_id" => ["label" => "ID Karyawan", "sql" => "k.emp_id"],
    "employee_name" => ["label" => "Nama Karyawan", "sql" => "k.employee_name"],
    "department" => ["label" => "Departemen", "sql" => "k.department"],
    "position" => ["label" => "Posisi", "sql" => "k.position"],
    "tanggal" => ["label" => "Tanggal", "sql" => "p.tanggal"],
    "jam_masuk" => ["label" => "Jam Masuk", "sql" => "p.jam_masuk"],
    "jam_keluar" => ["label" => "Jam Keluar", "sql" => "p.jam_keluar"],
    "status" => ["label" => "Status", "sql" => "p.status"],
    "keterangan" => ["label" => "Keterangan", "sql" => "p.keterangan"],
    "sumber_data" => ["label" => "Sumber Data", "sql" => "p.sumber_data"],
    "nama_pembuat" => ["label" => "Dibuat Oleh", "sql" => "u_buat.nama"],
];

$kolomDipilih = $_GET["kolom"] ?? array_keys($kolomExportPilihan);
if (!is_array($kolomDipilih)) {
    $kolomDipilih = [$kolomDipilih];
}
$kolomDipilih = array_values(array_intersect(array_keys($kolomExportPilihan), $kolomDipilih));
if ($kolomDipilih === []) {
    $kolomDipilih = array_keys($kolomExportPilihan);
}

if (!isset($kolomExportPilihan[$sortExport])) {
    $sortExport = "tanggal";
}

$sortSql = $kolomExportPilihan[$sortExport]["sql"] ?? "p.tanggal";
$limitSql = $batasExport === "semua" ? "" : " LIMIT " . (int) $batasExport;

$whereClause = implode(" AND ", $where);
$sql = "SELECT p.*, k.emp_id, k.employee_name, k.position, k.department,
               d.nama AS nama_departemen,
               u_buat.nama AS nama_pembuat
        FROM presensi_karyawan p
        INNER JOIN karyawan k ON k.id = p.karyawan_id
        INNER JOIN master_departemen d ON d.id = p.department_id
        LEFT JOIN users u_buat ON u_buat.id = p.dibuat_oleh_user_id
        WHERE {$whereClause}
        ORDER BY {$sortSql} {$arahExport}, p.id DESC" . $limitSql;

$query = mysqli_query($conn, $sql);
if (!$query) {
    http_response_code(500);
    exit("Data presensi gagal diproses: " . mysqli_error($conn));
}

$rows = [];
while ($item = mysqli_fetch_assoc($query)) {
    $rows[] = [
        "id" => (int) $item["id"],
        "emp_id" => (string) ($item["emp_id"] ?? ""),
        "employee_name" => (string) ($item["employee_name"] ?? ""),
        "department" => (string) ($item["department"] ?? $item["nama_departemen"] ?? ""),
        "position" => (string) ($item["position"] ?? ""),
        "tanggal" => (string) ($item["tanggal"] ?? ""),
        "jam_masuk" => $item["jam_masuk"] ? substr((string) $item["jam_masuk"], 0, 5) : "-",
        "jam_keluar" => $item["jam_keluar"] ? substr((string) $item["jam_keluar"], 0, 5) : "-",
        "status" => labelStatusPresensi((string) ($item["status"] ?? "hadir")),
        "keterangan" => (string) ($item["keterangan"] ?? "-"),
        "sumber_data" => ucfirst(str_replace('_', ' ', (string) ($item["sumber_data"] ?? "manual"))),
        "nama_pembuat" => (string) (($item["nama_pembuat"] ?? "") ?: "-"),
    ];
}

if (isset($_GET["download"])) {
    $headers = [];
    foreach ($kolomDipilih as $namaKolom) {
        $headers[] = $kolomExportPilihan[$namaKolom]["label"];
    }

    $exportRows = [];
    foreach ($rows as $row) {
        $rowCells = [];
        foreach ($kolomDipilih as $namaKolom) {
            $rowCells[] = $row[$namaKolom] ?? "";
        }
        $exportRows[] = $rowCells;
    }

    $namaFile = "laporan-presensi-" . ($tanggal !== "" ? $tanggal : date("Y-m-d"));
    unduhSpreadsheetXlsx($namaFile, "Presensi Karyawan", $headers, $exportRows);
}

$judulHalaman = "Opsi Export Presensi";
$subjudulHalaman = "Pilih jumlah data, urutan, dan kolom sebelum mengunduh Excel.";
require __DIR__ . "/../partials/atas.php";
?>

<section class="form-card export-options-card">
    <div class="form-card-header">
        <h2>Opsi Export Data Presensi</h2>
        <p><?= count($rows); ?> data akan diekspor sesuai cakupan akses &amp; filter.</p>
    </div>
    <div class="form-body">
        <form method="GET" class="export-options-form">
            <div class="form-group">
                <label for="batas_export">Jumlah data</label>
                <select id="batas_export" name="batas_export">
                    <?php foreach ($batasPilihan as $pilihan): ?>
                        <option value="<?= $pilihan; ?>" <?= (string) $batasExport === (string) $pilihan ? "selected" : ""; ?>>
                            <?= $pilihan === "semua" ? "Semua data" : "Maksimal " . $pilihan . " data"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sort_export">Urutkan berdasarkan</label>
                <select id="sort_export" name="sort_export">
                    <?php foreach ($kolomExportPilihan as $kunci => $kolom): ?>
                        <option value="<?= $kunci; ?>" <?= $sortExport === $kunci ? "selected" : ""; ?>>
                            <?= htmlspecialchars($kolom["label"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="arah_export">Arah urutan</label>
                <select id="arah_export" name="arah_export">
                    <option value="DESC" <?= $arahExport === "DESC" ? "selected" : ""; ?>>Turun (Z–A / terbaru ke terlama)</option>
                    <option value="ASC" <?= $arahExport === "ASC" ? "selected" : ""; ?>>Naik (A–Z / terlama ke terbaru)</option>
                </select>
            </div>

            <?php foreach (["tanggal" => $tanggal, "tanggal_mulai" => $tanggalMulai, "tanggal_selesai" => $tanggalSelesai, "status" => $status, "department" => $department, "cari" => $cari] as $namaParam => $nilaiParam): ?>
                <?php if ($nilaiParam !== "" && $nilaiParam !== 0): ?>
                    <input type="hidden" name="<?= htmlspecialchars($namaParam); ?>" value="<?= htmlspecialchars((string) $nilaiParam); ?>">
                <?php endif; ?>
            <?php endforeach; ?>

            <fieldset class="export-columns-fieldset">
                <legend>Kolom yang diekspor</legend>
                <?php foreach ($kolomExportPilihan as $kunci => $kolom): ?>
                    <label>
                        <input type="checkbox" name="kolom[]" value="<?= $kunci; ?>" <?= in_array($kunci, $kolomDipilih, true) ? "checked" : ""; ?>>
                        <?= htmlspecialchars($kolom["label"]); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <div class="form-actions">
                <a class="btn btn-secondary" href="../presensi.php">Batal</a>
                <button class="btn btn-success" type="submit">Terapkan Opsi</button>
                <button class="btn btn-primary" type="submit" name="download" value="1">Unduh Excel</button>
            </div>
        </form>
    </div>
</section>

<section class="data-card export-preview-card">
    <div class="data-card-header">
        <h2>Pratinjau Export Presensi</h2>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <?php foreach ($kolomDipilih as $namaKolom): ?>
                        <th><?= htmlspecialchars($kolomExportPilihan[$namaKolom]["label"]); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="<?= count($kolomDipilih); ?>" class="empty-table">Belum ada data presensi untuk diekspor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($kolomDipilih as $namaKolom): ?>
                                <td><?= htmlspecialchars((string) ($row[$namaKolom] ?? "")); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="export-preview-actions">
        <a class="btn btn-secondary" href="../presensi.php">Kembali</a>
        <a class="btn btn-success" href="export_presensi.php?download=1&amp;<?= htmlspecialchars(http_build_query($_GET)); ?>">Unduh Excel</a>
    </div>
</section>

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
