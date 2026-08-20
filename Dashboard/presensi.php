<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";
require_once __DIR__ . "/fungsi/presensi.php";
require_once __DIR__ . "/fungsi/master-data.php";

wajibRole("admin", "superadmin", "manager", "koordinator", "pic");

$roleSaatIni = rolePengguna();
$departmentId = departmentIdPengguna();
$bolehKelola = in_array($roleSaatIni, ["admin", "superadmin"], true);

siapkanTabelPresensi($conn);

$pesanSukses = trim((string) ($_GET["pesan"] ?? ""));
$pesanError = trim((string) ($_GET["error"] ?? ""));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = (string) ($_POST["aksi"] ?? "");

    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesanError = "Token keamanan tidak valid.";
    } elseif ($aksi === "simpan") {
        if (!$bolehKelola) {
            $pesanError = "Anda tidak memiliki izin untuk menyimpan data presensi.";
        } else {
            $hasilSimpan = simpanPresensi($conn, $_POST, (int) ($_SESSION["user"]["id"] ?? 0));
            if ($hasilSimpan["sukses"]) {
                $urlRedirect = "presensi.php?pesan=" . urlencode($hasilSimpan["pesan"]);
                if (!empty($_POST["filter_redirect"])) {
                    $urlRedirect .= "&" . $_POST["filter_redirect"];
                }
                header("Location: " . $urlRedirect);
                exit;
            }
            $pesanError = $hasilSimpan["pesan"];
        }
    } elseif ($aksi === "hapus") {
        if (!$bolehKelola) {
            $pesanError = "Anda tidak memiliki izin untuk menghapus data presensi.";
        } else {
            $presensiHapusId = (int) ($_POST["presensi_id"] ?? 0);
            if ($presensiHapusId <= 0) {
                $pesanError = "ID presensi tidak valid.";
            } elseif (hapusPresensi($conn, $presensiHapusId)) {
                $urlRedirect = "presensi.php?pesan=" . urlencode("Data presensi berhasil dihapus.");
                if (!empty($_POST["filter_redirect"])) {
                    $urlRedirect .= "&" . $_POST["filter_redirect"];
                }
                header("Location: " . $urlRedirect);
                exit;
            } else {
                $pesanError = "Gagal menghapus data presensi.";
            }
        }
    }
}

// Filter Parameters
$modeTanggal = (string) ($_GET["mode_tanggal"] ?? "hari_ini");
$tanggal = trim((string) ($_GET["tanggal"] ?? ""));
$tanggalMulai = trim((string) ($_GET["tanggal_mulai"] ?? ""));
$tanggalSelesai = trim((string) ($_GET["tanggal_selesai"] ?? ""));
$filterStatus = trim((string) ($_GET["status"] ?? "semua"));
$filterDepartment = (int) ($_GET["department"] ?? 0);
$filterCari = trim((string) ($_GET["cari"] ?? ""));

$where = ["1=1"];

if (roleOperasional()) {
    $where[] = "p.department_id = " . (int) ($departmentId ?? 0);
}

if ($modeTanggal === "hari_ini") {
    $hariIni = date("Y-m-d");
    $where[] = "p.tanggal = '{$hariIni}'";
} elseif ($modeTanggal === "spesifik" && $tanggal !== "") {
    $safeTanggal = mysqli_real_escape_string($conn, $tanggal);
    $where[] = "p.tanggal = '{$safeTanggal}'";
} elseif ($modeTanggal === "rentang" && $tanggalMulai !== "" && $tanggalSelesai !== "") {
    $safeMulai = mysqli_real_escape_string($conn, $tanggalMulai);
    $safeSelesai = mysqli_real_escape_string($conn, $tanggalSelesai);
    $where[] = "p.tanggal BETWEEN '{$safeMulai}' AND '{$safeSelesai}'";
}

if ($filterStatus !== "" && $filterStatus !== "semua" && in_array($filterStatus, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'], true)) {
    $safeStatus = mysqli_real_escape_string($conn, $filterStatus);
    $where[] = "p.status = '{$safeStatus}'";
}

if ($filterDepartment > 0 && !roleOperasional()) {
    $where[] = "p.department_id = {$filterDepartment}";
}

if ($filterCari !== "") {
    $safeCari = mysqli_real_escape_string($conn, $filterCari);
    $where[] = "(k.employee_name LIKE '%{$safeCari}%' OR k.emp_id LIKE '%{$safeCari}%' OR p.keterangan LIKE '%{$safeCari}%')";
}

$whereClause = implode(" AND ", $where);

// Ambil Statistik / Ringkasan
$ringkasan = ambilRingkasanPresensi($conn, $whereClause);

// Query Data Presensi
$sqlDaftar = "SELECT p.*, k.emp_id, k.employee_name, k.position, k.department,
                     d.nama AS nama_departemen,
                     u_buat.nama AS nama_pembuat
              FROM presensi_karyawan p
              INNER JOIN karyawan k ON k.id = p.karyawan_id
              INNER JOIN master_departemen d ON d.id = p.department_id
              LEFT JOIN users u_buat ON u_buat.id = p.dibuat_oleh_user_id
              WHERE {$whereClause}
              ORDER BY p.tanggal DESC, p.id DESC";

$hasilPresensi = mysqli_query($conn, $sqlDaftar);

// Ambil Data Karyawan untuk Dropdown Bertingkat pada Modal
$departemenPilihan = ambilDepartemenPilihan($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);
$posisiPerDepartemen = ambilPosisiPerDepartemen($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);

$sqlKaryawan = "SELECT id, emp_id, employee_name, position, department, department_id
                FROM karyawan
                WHERE department_id IS NOT NULL
                  AND TRIM(COALESCE(position, '')) <> ''
                  AND TRIM(COALESCE(department, '')) <> ''";
if (roleOperasional()) {
    $sqlKaryawan .= " AND department_id = " . (int) ($departmentId ?? 0);
}
$sqlKaryawan .= " ORDER BY department, position, employee_name";
$hasilKaryawan = mysqli_query($conn, $sqlKaryawan);
$dataKaryawan = [];
if ($hasilKaryawan) {
    while ($rowK = mysqli_fetch_assoc($hasilKaryawan)) {
        $dataKaryawan[] = [
            "id" => (int) $rowK["id"],
            "emp_id" => (string) ($rowK["emp_id"] ?? ""),
            "nama" => (string) ($rowK["employee_name"] ?? ""),
            "posisi" => (string) ($rowK["position"] ?? ""),
            "departemen" => (string) ($rowK["department"] ?? ""),
            "department_id" => (int) $rowK["department_id"],
        ];
    }
}

// Build Export Query String
$exportQuery = http_build_query([
    "tanggal" => $modeTanggal === "spesifik" ? $tanggal : ($modeTanggal === "hari_ini" ? date("Y-m-d") : ""),
    "tanggal_mulai" => $modeTanggal === "rentang" ? $tanggalMulai : "",
    "tanggal_selesai" => $modeTanggal === "rentang" ? $tanggalSelesai : "",
    "status" => $filterStatus,
    "department" => $filterDepartment,
    "cari" => $filterCari,
]);

$currentFilterQuery = http_build_query($_GET);

$judulHalaman = "Presensi Karyawan";
$subjudulHalaman = "Pencatatan dan pemantauan kehadiran harian karyawan.";
$halamanAktif = "presensi";

require __DIR__ . "/partials/atas.php";
?>

<?php if ($pesanSukses !== ""): ?>
    <div class="alert-success" role="alert"><?= htmlspecialchars($pesanSukses); ?></div>
<?php endif; ?>

<?php if ($pesanError !== ""): ?>
    <div class="alert-error" role="alert"><?= htmlspecialchars($pesanError); ?></div>
<?php endif; ?>

<div class="presensi-top-actions">
    <div class="presensi-top-buttons">
        <?php if ($bolehKelola): ?>
            <button type="button" class="btn btn-primary btn-add-presensi" id="btn-buka-tambah">
                + Tambah Presensi
            </button>
        <?php endif; ?>
        <a class="btn export-excel-btn" href="fungsi/export_presensi.php?<?= htmlspecialchars($exportQuery); ?>">
            Export Excel
        </a>
    </div>
</div>

<section class="presensi-summary-grid">
    <div class="summary-card total">
        <div class="summary-info">
            <span class="summary-label">Total Log</span>
            <strong class="summary-count"><?= number_format($ringkasan["total"], 0, ',', '.'); ?></strong>
        </div>
        <div class="summary-icon">📋</div>
    </div>
    <div class="summary-card hadir">
        <div class="summary-info">
            <span class="summary-label">Hadir Tepat Waktu</span>
            <strong class="summary-count"><?= number_format($ringkasan["hadir"], 0, ',', '.'); ?></strong>
        </div>
        <div class="summary-icon">✅</div>
    </div>
    <div class="summary-card terlambat">
        <div class="summary-info">
            <span class="summary-label">Terlambat</span>
            <strong class="summary-count"><?= number_format($ringkasan["terlambat"], 0, ',', '.'); ?></strong>
        </div>
        <div class="summary-icon">⏱️</div>
    </div>
    <div class="summary-card izin">
        <div class="summary-info">
            <span class="summary-label">Izin / Sakit</span>
            <strong class="summary-count"><?= number_format($ringkasan["izin_sakit"], 0, ',', '.'); ?></strong>
        </div>
        <div class="summary-icon">📝</div>
    </div>
    <div class="summary-card alpa">
        <div class="summary-info">
            <span class="summary-label">Alpa</span>
            <strong class="summary-count"><?= number_format($ringkasan["alpa"], 0, ',', '.'); ?></strong>
        </div>
        <div class="summary-icon">⚠️</div>
    </div>
</section>

<section class="form-card presensi-filter-card">
    <div class="form-card-header">
        <h2>Filter Data Presensi</h2>
        <p>Sesuaikan tanggal, departemen, atau status kehadiran untuk menampilkan data yang spesifik.</p>
    </div>
    <div class="form-body">
        <form method="GET" class="presensi-filter-form" id="presensi-filter-form">
            <div class="form-group">
                <label for="filter-mode-tanggal">Periode Tanggal</label>
                <select id="filter-mode-tanggal" name="mode_tanggal">
                    <option value="hari_ini" <?= $modeTanggal === "hari_ini" ? "selected" : ""; ?>>Hari Ini (<?= date("d/m/Y"); ?>)</option>
                    <option value="spesifik" <?= $modeTanggal === "spesifik" ? "selected" : ""; ?>>Tanggal Tertentu</option>
                    <option value="rentang" <?= $modeTanggal === "rentang" ? "selected" : ""; ?>>Rentang Tanggal</option>
                    <option value="semua" <?= $modeTanggal === "semua" ? "selected" : ""; ?>>Semua Tanggal</option>
                </select>
            </div>

            <div class="form-group tanggal-spesifik-group" id="group-tanggal-spesifik" <?= $modeTanggal === "spesifik" ? "" : "hidden"; ?>>
                <label for="filter-tanggal">Pilih Tanggal</label>
                <input type="date" id="filter-tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal); ?>">
            </div>

            <div class="form-group tanggal-rentang-group" id="group-tanggal-mulai" <?= $modeTanggal === "rentang" ? "" : "hidden"; ?>>
                <label for="filter-tanggal-mulai">Tanggal Mulai</label>
                <input type="date" id="filter-tanggal-mulai" name="tanggal_mulai" value="<?= htmlspecialchars($tanggalMulai); ?>">
            </div>

            <div class="form-group tanggal-rentang-group" id="group-tanggal-selesai" <?= $modeTanggal === "rentang" ? "" : "hidden"; ?>>
                <label for="filter-tanggal-selesai">Tanggal Selesai</label>
                <input type="date" id="filter-tanggal-selesai" name="tanggal_selesai" value="<?= htmlspecialchars($tanggalSelesai); ?>">
            </div>

            <?php if (!roleOperasional()): ?>
                <div class="form-group">
                    <label for="filter-department">Departemen</label>
                    <select id="filter-department" name="department">
                        <option value="0">Semua Departemen</option>
                        <?php foreach ($departemenPilihan as $idDept => $namaDept): ?>
                            <option value="<?= (int) $idDept; ?>" <?= $filterDepartment === (int) $idDept ? "selected" : ""; ?>>
                                <?= htmlspecialchars($namaDept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="filter-status">Status</label>
                <select id="filter-status" name="status">
                    <option value="semua">Semua Status</option>
                    <option value="hadir" <?= $filterStatus === "hadir" ? "selected" : ""; ?>>Hadir</option>
                    <option value="terlambat" <?= $filterStatus === "terlambat" ? "selected" : ""; ?>>Terlambat</option>
                    <option value="izin" <?= $filterStatus === "izin" ? "selected" : ""; ?>>Izin</option>
                    <option value="sakit" <?= $filterStatus === "sakit" ? "selected" : ""; ?>>Sakit</option>
                    <option value="alpa" <?= $filterStatus === "alpa" ? "selected" : ""; ?>>Alpa</option>
                </select>
            </div>

            <div class="form-group presensi-search-group">
                <label for="filter-cari">Cari Karyawan / Catatan</label>
                <input type="text" id="filter-cari" name="cari" placeholder="Nama, ID karyawan, catatan..." value="<?= htmlspecialchars($filterCari); ?>">
            </div>

            <div class="form-actions presensi-filter-actions">
                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                <a href="presensi.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</section>

<section class="data-card presensi-data-card">
    <div class="data-card-header">
        <h2>Daftar Riwayat Presensi</h2>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Posisi</th>
                    <th>Tanggal Masuk</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <?php if ($bolehKelola): ?>
                        <th class="presensi-actions-header">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($hasilPresensi && mysqli_num_rows($hasilPresensi) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($hasilPresensi)): ?>
                        <?php
                        $statusKode = (string) ($p["status"] ?? "hadir");
                        $statusLabel = labelStatusPresensi($statusKode);
                        $jamMasukTampil = $p["jam_masuk"] ? htmlspecialchars(substr((string) $p["jam_masuk"], 0, 5)) : "-";
                        $jamKeluarTampil = $p["jam_keluar"] ? htmlspecialchars(substr((string) $p["jam_keluar"], 0, 5)) : "-";
                        $tanggalTampil = htmlspecialchars(date("d/m/Y", strtotime((string) $p["tanggal"])));
                        ?>
                        <tr data-id="<?= (int) $p["id"]; ?>"
                            data-karyawan-id="<?= (int) $p["karyawan_id"]; ?>"
                            data-department-id="<?= (int) $p["department_id"]; ?>"
                            data-posisi="<?= htmlspecialchars((string) $p["position"]); ?>"
                            data-tanggal="<?= htmlspecialchars((string) $p["tanggal"]); ?>"
                            data-jam-masuk="<?= htmlspecialchars((string) ($p["jam_masuk"] ? substr((string) $p["jam_masuk"], 0, 5) : "")); ?>"
                            data-jam-keluar="<?= htmlspecialchars((string) ($p["jam_keluar"] ? substr((string) $p["jam_keluar"], 0, 5) : "")); ?>"
                            data-status="<?= htmlspecialchars($statusKode); ?>"
                            data-keterangan="<?= htmlspecialchars((string) ($p["keterangan"] ?? "")); ?>">
                            <td><?= (int) $p["id"]; ?></td>
                            <td>
                                <strong><?= htmlspecialchars((string) $p["employee_name"]); ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars((string) $p["emp_id"]); ?></small>
                            </td>
                            <td><?= htmlspecialchars((string) ($p["department"] ?? $p["nama_departemen"])); ?></td>
                            <td><?= htmlspecialchars((string) $p["position"]); ?></td>
                            <td><?= $tanggalTampil; ?></td>
                            <td><span class="badge-time"><?= $jamMasukTampil; ?></span></td>
                            <td><span class="badge-time"><?= $jamKeluarTampil; ?></span></td>
                            <td>
                                <span class="status-badge status-presensi-<?= htmlspecialchars($statusKode); ?>">
                                    <?= htmlspecialchars($statusLabel); ?>
                                </span>
                            </td>
                            <td class="presensi-note-cell"><?= nl2br(htmlspecialchars((string) ($p["keterangan"] ?: "-"))); ?></td>
                            <?php if ($bolehKelola): ?>
                                <td class="presensi-actions">
                                    <button type="button" class="btn btn-secondary btn-sm btn-edit-presensi">
                                        Edit
                                    </button>
                                    <form method="POST" class="delete-presensi-form" onsubmit="return confirm('Hapus data presensi karyawan ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="presensi_id" value="<?= (int) $p["id"]; ?>">
                                        <input type="hidden" name="filter_redirect" value="<?= htmlspecialchars($currentFilterQuery); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $bolehKelola ? "10" : "9"; ?>" class="empty-table">
                            Belum ada data presensi yang sesuai dengan filter.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($bolehKelola): ?>
<!-- Modal Input & Edit Presensi -->
<div class="modal-backdrop" id="modal-presensi" hidden>
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="modal-presensi-title">Tambah Data Presensi</h3>
            <button type="button" class="btn-close-modal" id="btn-tutup-modal" aria-label="Tutup">&times;</button>
        </div>
        <form method="POST" id="form-modal-presensi">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
            <input type="hidden" name="aksi" value="simpan">
            <input type="hidden" name="id" id="modal-presensi-id" value="0">
            <input type="hidden" name="filter_redirect" value="<?= htmlspecialchars($currentFilterQuery); ?>">

            <div class="modal-body">
                <div class="form-group">
                    <label for="modal-department">Departemen <span class="required-star">*</span></label>
                    <select id="modal-department" name="department_id" required>
                        <option value="">Pilih departemen</option>
                        <?php foreach ($departemenPilihan as $idDept => $namaDept): ?>
                            <option value="<?= (int) $idDept; ?>"><?= htmlspecialchars($namaDept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modal-position">Posisi <span class="required-star">*</span></label>
                    <select id="modal-position" name="position" required disabled>
                        <option value="">Pilih departemen terlebih dahulu</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="modal-karyawan">Karyawan <span class="required-star">*</span></label>
                    <select id="modal-karyawan" name="karyawan_id" required disabled>
                        <option value="">Pilih departemen dan posisi terlebih dahulu</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modal-tanggal">Tanggal Presensi <span class="required-star">*</span></label>
                    <input type="date" id="modal-tanggal" name="tanggal" value="<?= date("Y-m-d"); ?>" required>
                </div>

                <div class="form-group">
                    <label for="modal-status">Status Kehadiran <span class="required-star">*</span></label>
                    <select id="modal-status" name="status" required>
                        <option value="hadir">Hadir Tepat Waktu</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="alpa">Alpa</option>
                    </select>
                </div>

                <div class="form-group" id="modal-jam-masuk-group">
                    <label for="modal-jam-masuk">Jam Masuk <span class="required-star" id="star-jam-masuk">*</span></label>
                    <input type="time" id="modal-jam-masuk" name="jam_masuk" value="08:00" required>
                </div>

                <div class="form-group" id="modal-jam-keluar-group">
                    <label for="modal-jam-keluar">Jam Keluar</label>
                    <input type="time" id="modal-jam-keluar" name="jam_keluar" value="17:00">
                </div>

                <div class="form-group full-width">
                    <label for="modal-keterangan">Keterangan / Catatan</label>
                    <textarea id="modal-keterangan" name="keterangan" placeholder="Contoh: Datang dinas luar, surat dokter terlampir, dsb..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btn-batal-modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="btn-simpan-modal">Simpan Presensi</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const employees = <?= json_encode($dataKaryawan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const positionsByDepartment = <?= json_encode($posisiPerDepartemen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    // Filter bar date mode toggles
    const modeTanggal = document.getElementById("filter-mode-tanggal");
    const groupSpesifik = document.getElementById("group-tanggal-spesifik");
    const groupMulai = document.getElementById("group-tanggal-mulai");
    const groupSelesai = document.getElementById("group-tanggal-selesai");

    if (modeTanggal) {
        modeTanggal.addEventListener("change", () => {
            const val = modeTanggal.value;
            if (groupSpesifik) groupSpesifik.hidden = (val !== "spesifik");
            if (groupMulai) groupMulai.hidden = (val !== "rentang");
            if (groupSelesai) groupSelesai.hidden = (val !== "rentang");
        });
    }

    // Modal elements
    const modal = document.getElementById("modal-presensi");
    const modalTitle = document.getElementById("modal-presensi-title");
    const modalId = document.getElementById("modal-presensi-id");
    const deptSelect = document.getElementById("modal-department");
    const posSelect = document.getElementById("modal-position");
    const empSelect = document.getElementById("modal-karyawan");
    const tglInput = document.getElementById("modal-tanggal");
    const statusSelect = document.getElementById("modal-status");
    const jamMasuk = document.getElementById("modal-jam-masuk");
    const jamKeluar = document.getElementById("modal-jam-keluar");
    const starJamMasuk = document.getElementById("star-jam-masuk");
    const ketInput = document.getElementById("modal-keterangan");

    const btnBukaTambah = document.getElementById("btn-buka-tambah");
    const btnTutupModal = document.getElementById("btn-tutup-modal");
    const btnBatalModal = document.getElementById("btn-batal-modal");

    const syncStatusTimeFields = () => {
        const status = statusSelect.value;
        const butuhJam = (status === "hadir" || status === "terlambat");
        jamMasuk.disabled = !butuhJam;
        jamKeluar.disabled = !butuhJam;
        jamMasuk.required = butuhJam;
        if (starJamMasuk) starJamMasuk.style.display = butuhJam ? "inline" : "none";

        if (!butuhJam) {
            jamMasuk.value = "";
            jamKeluar.value = "";
        } else if (!jamMasuk.value) {
            jamMasuk.value = status === "terlambat" ? "08:30" : "08:00";
            if (!jamKeluar.value) jamKeluar.value = "17:00";
        }
    };

    if (statusSelect) {
        statusSelect.addEventListener("change", syncStatusTimeFields);
    }

    const updatePositions = (selectedDepartmentId, selectedPosition = "") => {
        posSelect.innerHTML = '<option value="">Pilih posisi</option>';
        empSelect.innerHTML = '<option value="">Pilih posisi terlebih dahulu</option>';
        empSelect.disabled = true;

        if (!selectedDepartmentId || !positionsByDepartment[selectedDepartmentId]) {
            posSelect.disabled = true;
            return;
        }

        positionsByDepartment[selectedDepartmentId].forEach(pos => {
            const opt = document.createElement("option");
            opt.value = pos;
            opt.textContent = pos;
            if (pos === selectedPosition) opt.selected = true;
            posSelect.appendChild(opt);
        });

        posSelect.disabled = false;
    };

    const updateEmployees = (selectedDepartmentId, selectedPosition, selectedEmployeeId = 0) => {
        empSelect.innerHTML = '<option value="">Pilih karyawan</option>';

        if (!selectedDepartmentId || !selectedPosition) {
            empSelect.disabled = true;
            return;
        }

        const filtered = employees.filter(e =>
            String(e.department_id) === String(selectedDepartmentId) &&
            e.posisi === selectedPosition
        );

        filtered.forEach(e => {
            const opt = document.createElement("option");
            opt.value = e.id;
            opt.textContent = `${e.emp_id} - ${e.nama}`;
            if (Number(e.id) === Number(selectedEmployeeId)) opt.selected = true;
            empSelect.appendChild(opt);
        });

        empSelect.disabled = filtered.length === 0;
    };

    if (deptSelect) {
        deptSelect.addEventListener("change", () => {
            updatePositions(deptSelect.value);
        });
    }

    if (posSelect) {
        posSelect.addEventListener("change", () => {
            updateEmployees(deptSelect.value, posSelect.value);
        });
    }

    const bukaModalTambah = () => {
        if (!modal) return;
        modalTitle.textContent = "Tambah Data Presensi";
        modalId.value = "0";
        deptSelect.value = "";
        posSelect.value = "";
        posSelect.disabled = true;
        empSelect.value = "";
        empSelect.disabled = true;
        tglInput.value = new Date().toISOString().split("T")[0];
        statusSelect.value = "hadir";
        jamMasuk.value = "08:00";
        jamKeluar.value = "17:00";
        ketInput.value = "";
        syncStatusTimeFields();
        modal.hidden = false;
    };

    const bukaModalEdit = (tr) => {
        if (!modal || !tr) return;
        modalTitle.textContent = "Edit Data Presensi";
        modalId.value = tr.dataset.id || "0";
        const deptId = tr.dataset.departmentId || "";
        const posisi = tr.dataset.posisi || "";
        const karyawanId = tr.dataset.karyawanId || "";

        deptSelect.value = deptId;
        updatePositions(deptId, posisi);
        updateEmployees(deptId, posisi, karyawanId);

        tglInput.value = tr.dataset.tanggal || "";
        statusSelect.value = tr.dataset.status || "hadir";
        jamMasuk.value = tr.dataset.jamMasuk || "";
        jamKeluar.value = tr.dataset.jamKeluar || "";
        ketInput.value = tr.dataset.keterangan || "";

        syncStatusTimeFields();
        modal.hidden = false;
    };

    const tutupModal = () => {
        if (modal) modal.hidden = true;
    };

    if (btnBukaTambah) btnBukaTambah.addEventListener("click", bukaModalTambah);
    if (btnTutupModal) btnTutupModal.addEventListener("click", tutupModal);
    if (btnBatalModal) btnBatalModal.addEventListener("click", tutupModal);

    // Edit button triggers
    document.querySelectorAll(".btn-edit-presensi").forEach(btn => {
        btn.addEventListener("click", (e) => {
            const tr = e.target.closest("tr");
            bukaModalEdit(tr);
        });
    });

    // Close on clicking backdrop
    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) tutupModal();
        });
    }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . "/partials/bawah.php"; ?>
