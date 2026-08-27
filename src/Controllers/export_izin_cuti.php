<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/izin-cuti.php';
require_once __DIR__ . '/../Utils/xlsx-builder.php';

wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
siapkanTabelIzinCuti($conn);

$departmentId = departmentIdPengguna();
$halamanAktif = "izin-cuti";
$where = roleOperasional() ? "c.department_id = " . (int) ($departmentId ?? 0) : "1=1";

$statusFilter = (string) ($_GET["status"] ?? "semua");
if (in_array($statusFilter, ["menunggu", "disetujui", "ditolak"], true)) {
    $where .= " AND c.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}

$cari = trim((string) ($_GET["cari"] ?? ""));
if ($cari !== "") {
    $safe = mysqli_real_escape_string($conn, $cari);
    $where .= " AND (k.employee_name LIKE '%$safe%' OR k.emp_id LIKE '%$safe%' OR c.deskripsi LIKE '%$safe%')";
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

$sortExport = (string) ($_GET["sort_export"] ?? "created_at");

$kolomExportPilihan = [
    "id" => ["label" => "ID", "sql" => "c.id"],
    "emp_id" => ["label" => "ID Karyawan", "sql" => "k.emp_id"],
    "employee_name" => ["label" => "Nama Karyawan", "sql" => "k.employee_name"],
    "position" => ["label" => "Posisi", "sql" => "k.position"],
    "department" => ["label" => "Departemen", "sql" => "k.department"],
    "tanggal_mulai" => ["label" => "Tanggal Awal", "sql" => "c.tanggal_mulai"],
    "tanggal_selesai" => ["label" => "Tanggal Akhir", "sql" => "c.tanggal_selesai"],
    "jenis_cuti" => ["label" => "Jenis Cuti", "sql" => "c.jenis_cuti"],
    "periode_setengah_hari" => ["label" => "Periode", "sql" => "c.periode_setengah_hari"],
    "total_hari" => ["label" => "Total Hari", "sql" => "c.total_hari"],
    "deskripsi" => ["label" => "Keperluan", "sql" => "c.deskripsi"],
    "nomor_kontak" => ["label" => "Kontak", "sql" => "c.nomor_kontak"],
    "pengganti" => ["label" => "Karyawan Pengganti", "sql" => "p.employee_name"],
    "status" => ["label" => "Status", "sql" => "c.status"],
    "nama_pembuat" => ["label" => "Dibuat Oleh", "sql" => "pembuat.nama"],
    "nama_pemroses" => ["label" => "Diproses Oleh", "sql" => "pemroses.nama"],
    "created_at" => ["label" => "Waktu Pengajuan", "sql" => "c.created_at"],
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
    $sortExport = "created_at";
}

$orderSql = $sortExport === "created_at"
    ? "c.created_at"
    : ($kolomExportPilihan[$sortExport]["sql"] ?? "c.id");
$orderSql .= " " . $arahExport . ", c.id DESC";

$limitSql = $batasExport === "semua" ? "" : " LIMIT " . (int) $batasExport;

$sql = "SELECT c.*, k.emp_id, k.employee_name, k.position, k.department,
               p.emp_id AS pengganti_emp_id, p.employee_name AS pengganti_nama,
               pembuat.nama AS nama_pembuat, pemroses.nama AS nama_pemroses,
               pemroses.role AS role_pemroses
        FROM izin_cuti c
        INNER JOIN karyawan k ON k.id = c.karyawan_id
        INNER JOIN karyawan p ON p.id = c.karyawan_pengganti_id
        INNER JOIN users pembuat ON pembuat.id = c.dibuat_oleh_user_id
        LEFT JOIN users pemroses ON pemroses.id = c.diproses_oleh_user_id
        WHERE $where
        ORDER BY $orderSql$limitSql";

$query = mysqli_query($conn, $sql);
if (!$query) {
    http_response_code(500);
    exit("Data izin cuti gagal diproses: " . mysqli_error($conn));
}

$rows = [];
while ($item = mysqli_fetch_assoc($query)) {
    $jenisLabel = $item["jenis_cuti"] === "setengah_hari" ? "Setengah hari" : "Harian penuh";
    $periodeLabel = match ((string) ($item["periode_setengah_hari"] ?? "")) {
        "pagi" => "Pagi",
        "siang" => "Siang",
        default => "-",
    };
    $totalHariLabel = (float) $item["total_hari"] === 0.5
        ? "0.5 hari"
        : number_format((float) $item["total_hari"], 0, ",", ".") . " hari";
    $statusLabel = labelStatusPersetujuanIzin(
        (string) $item["status"],
        (string) ($item["tahap_persetujuan"] ?? "pic"),
        (string) ($item["role_pemroses"] ?? "")
    );
    $rows[] = [
        "id" => (int) $item["id"],
        "emp_id" => (string) ($item["emp_id"] ?? ""),
        "employee_name" => (string) ($item["employee_name"] ?? ""),
        "position" => (string) ($item["position"] ?? ""),
        "department" => (string) ($item["department"] ?? ""),
        "tanggal_mulai" => (string) ($item["tanggal_mulai"] ?? ""),
        "tanggal_selesai" => (string) ($item["tanggal_selesai"] ?? ""),
        "jenis_cuti" => $jenisLabel,
        "periode_setengah_hari" => $periodeLabel,
        "total_hari" => $totalHariLabel,
        "deskripsi" => (string) ($item["deskripsi"] ?? ""),
        "nomor_kontak" => (string) ($item["nomor_kontak"] ?? ""),
        "pengganti" => ($item["pengganti_emp_id"] ?? "") . " - " . ($item["pengganti_nama"] ?? ""),
        "status" => $statusLabel,
        "nama_pembuat" => (string) ($item["nama_pembuat"] ?? ""),
        "nama_pemroses" => (string) (($item["nama_pemroses"] ?? "") ?: "-"),
        "created_at" => (string) ($item["created_at"] ?? ""),
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

    unduhSpreadsheetXlsx(
        "laporan-izin-cuti-" . date("Y-m-d"),
        "Izin Cuti",
        $headers,
        $exportRows
    );
}

$judulHalaman = "Opsi Export Izin Cuti";
$subjudulHalaman = "Pilih jumlah data, urutan, dan kolom sebelum mengunduh Excel.";
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>

<section class="form-card export-options-card">
    <div class="form-card-header">
        <h2>Opsi Export Izin Cuti</h2>
        <p><?= count($rows); ?> data akan diekspor sesuai cakupan akses.</p>
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
                    <option value="ASC" <?= $arahExport === "ASC" ? "selected" : ""; ?>>Naik (A–Z / lama ke baru)</option>
                    <option value="DESC" <?= $arahExport === "DESC" ? "selected" : ""; ?>>Turun (Z–A / baru ke lama)</option>
                </select>
            </div>
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
                <a class="btn btn-secondary" href="izin-cuti.php">Batal</a>
                <button class="btn btn-success" type="submit">Terapkan Opsi</button>
                <button class="btn btn-primary" type="submit" name="download" value="1">Unduh Excel</button>
            </div>
        </form>
    </div>
</section>

<section class="data-card export-preview-card">
    <div class="data-card-header">
        <h2>Pratinjau Export Izin Cuti</h2>
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
                        <td colspan="<?= count($kolomDipilih); ?>" class="empty-table">Belum ada data izin cuti untuk diekspor.</td>
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
        <a class="btn btn-secondary" href="izin-cuti.php">Kembali</a>
        <a class="btn btn-success" href="export_izin_cuti.php?download=1&amp;<?= htmlspecialchars(http_build_query($_GET)); ?>">Unduh Excel</a>
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

<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
