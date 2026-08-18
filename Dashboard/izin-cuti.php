<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";
require_once __DIR__ . "/fungsi/izin-cuti.php";

wajibRole("admin", "superadmin", "pic", "koordinator", "manager");

$pesan = "";
$roleSaatIni = rolePengguna();
$departmentId = departmentIdPengguna();
$bolehMenyetujui = in_array($roleSaatIni, ["admin", "superadmin"], true);
$form = [
    "department_id" => "",
    "position" => "",
    "karyawan_id" => "",
    "tanggal_mulai" => "",
    "tanggal_selesai" => "",
    "jenis_cuti" => "harian",
    "periode_setengah_hari" => "",
    "deskripsi" => "",
    "nomor_kontak" => "",
    "karyawan_pengganti_id" => "",
];

if (!siapkanTabelIzinCuti($conn)) {
    http_response_code(500);
    die("Tabel izin cuti tidak dapat disiapkan.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = (string) ($_POST["aksi"] ?? "simpan");

    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesan = "Token keamanan tidak valid.";
    } elseif ($aksi === "keputusan") {
        $cutiId = (int) ($_POST["cuti_id"] ?? 0);
        $keputusan = (string) ($_POST["keputusan"] ?? "");
        $catatan = trim((string) ($_POST["catatan"] ?? ""));

        if (!$bolehMenyetujui) {
            $pesan = "Hanya admin dan superadmin yang dapat memproses izin cuti.";
        } elseif (!in_array($keputusan, ["disetujui", "ditolak"], true)) {
            $pesan = "Keputusan izin cuti tidak valid.";
        } elseif ($keputusan === "ditolak" && $catatan === "") {
            $pesan = "Alasan penolakan wajib diisi.";
        } else {
            $pemrosesId = (int) ($_SESSION["user"]["id"] ?? 0);
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE izin_cuti
                 SET status = ?, diproses_oleh_user_id = ?, catatan_persetujuan = NULLIF(?, ''),
                     diproses_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND status = 'menunggu'"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sisi", $keputusan, $pemrosesId, $catatan, $cutiId);
                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    mysqli_stmt_close($stmt);
                    catatAktivitas($conn, "Memproses izin cuti ID " . $cutiId . " menjadi " . $keputusan . ".");
                    header("Location: izin-cuti.php?pesan=" . urlencode("Izin cuti berhasil " . $keputusan . "."));
                    exit;
                }

                mysqli_stmt_close($stmt);
            }

            $pesan = "Izin cuti sudah diproses atau tidak ditemukan.";
        }
    } elseif ($aksi === "simpan") {
        foreach (array_keys($form) as $namaKolom) {
            $form[$namaKolom] = trim((string) ($_POST[$namaKolom] ?? ""));
        }

        $karyawanId = (int) $form["karyawan_id"];
        $departmentFormId = (int) $form["department_id"];
        $karyawanPenggantiId = (int) $form["karyawan_pengganti_id"];
        $jenisCuti = $form["jenis_cuti"];
        $periodeSetengahHari = $jenisCuti === "setengah_hari" ? $form["periode_setengah_hari"] : "";
        $tanggalMulai = DateTimeImmutable::createFromFormat("!Y-m-d", $form["tanggal_mulai"]);
        $tanggalSelesai = DateTimeImmutable::createFromFormat("!Y-m-d", $form["tanggal_selesai"]);
        $tanggalMulaiValid = $tanggalMulai !== false && $tanggalMulai->format("Y-m-d") === $form["tanggal_mulai"];
        $tanggalSelesaiValid = $tanggalSelesai !== false && $tanggalSelesai->format("Y-m-d") === $form["tanggal_selesai"];
        $totalHari = 0.0;

        if (
            $departmentFormId <= 0
            || $form["position"] === ""
            || $karyawanId <= 0
            || !$tanggalMulaiValid
            || !$tanggalSelesaiValid
            || !in_array($jenisCuti, ["harian", "setengah_hari"], true)
            || $form["deskripsi"] === ""
            || $form["nomor_kontak"] === ""
            || $karyawanPenggantiId <= 0
        ) {
            $pesan = "Seluruh data izin cuti wajib diisi dengan pilihan yang valid.";
        } elseif ($tanggalSelesai < $tanggalMulai) {
            $pesan = "Tanggal akhir cuti tidak boleh mendahului tanggal awal.";
        } elseif (
            $jenisCuti === "setengah_hari"
            && ($tanggalSelesai != $tanggalMulai || !in_array($periodeSetengahHari, ["pagi", "siang"], true))
        ) {
            $pesan = "Cuti setengah hari harus berada pada satu tanggal dengan pilihan pagi atau siang.";
        } elseif (strlen($form["nomor_kontak"]) > 50) {
            $pesan = "Nomor kontak maksimal 50 karakter.";
        } elseif ($karyawanId === $karyawanPenggantiId) {
            $pesan = "Karyawan pengganti harus berbeda dari karyawan yang mengajukan cuti.";
        } elseif (roleOperasional() && (int) ($departmentId ?? 0) !== $departmentFormId) {
            $pesan = "Departemen yang dipilih berada di luar cakupan Anda.";
        } else {
            $totalHari = $jenisCuti === "setengah_hari"
                ? 0.5
                : (float) ((int) $tanggalMulai->diff($tanggalSelesai)->days + 1);

            $stmtKaryawan = mysqli_prepare(
                $conn,
                "SELECT id FROM karyawan
                 WHERE id = ? AND department_id = ? AND position = ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($stmtKaryawan, "iis", $karyawanId, $departmentFormId, $form["position"]);
            mysqli_stmt_execute($stmtKaryawan);
            $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtKaryawan));
            mysqli_stmt_close($stmtKaryawan);

            $stmtPengganti = mysqli_prepare(
                $conn,
                "SELECT id FROM karyawan
                 WHERE id = ? AND department_id = ? AND id <> ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($stmtPengganti, "iii", $karyawanPenggantiId, $departmentFormId, $karyawanId);
            mysqli_stmt_execute($stmtPengganti);
            $karyawanPengganti = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtPengganti));
            mysqli_stmt_close($stmtPengganti);

            if (!$karyawan) {
                $pesan = "Karyawan tidak sesuai dengan posisi dan departemen yang dipilih.";
            } elseif (!$karyawanPengganti) {
                $pesan = "Karyawan pengganti harus berasal dari departemen yang sama.";
            } else {
                $pembuatId = (int) ($_SESSION["user"]["id"] ?? 0);
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO izin_cuti
                        (karyawan_id, department_id, tanggal_mulai, tanggal_selesai, jenis_cuti,
                         periode_setengah_hari, total_hari, deskripsi, nomor_kontak,
                         karyawan_pengganti_id, dibuat_oleh_user_id)
                     VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "iissssdssii",
                    $karyawanId,
                    $departmentFormId,
                    $form["tanggal_mulai"],
                    $form["tanggal_selesai"],
                    $jenisCuti,
                    $periodeSetengahHari,
                    $totalHari,
                    $form["deskripsi"],
                    $form["nomor_kontak"],
                    $karyawanPenggantiId,
                    $pembuatId
                );

                if (mysqli_stmt_execute($stmt)) {
                    $cutiBaruId = (int) mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);
                    catatAktivitas($conn, "Membuat izin cuti ID " . $cutiBaruId . ".");
                    header("Location: izin-cuti.php?pesan=" . urlencode("Izin cuti berhasil disimpan dan menunggu persetujuan."));
                    exit;
                }

                mysqli_stmt_close($stmt);
                $pesan = "Izin cuti gagal disimpan.";
            }
        }
    } else {
        $pesan = "Aksi tidak dikenali.";
    }
}

$sqlKaryawan = "SELECT id, emp_id, employee_name, position, department, department_id
                FROM karyawan
                WHERE department_id IS NOT NULL
                  AND TRIM(COALESCE(position, '')) <> ''
                  AND TRIM(COALESCE(department, '')) <> ''";
$parameterDepartment = null;

if (roleOperasional()) {
    $sqlKaryawan .= " AND department_id = ?";
    $parameterDepartment = (int) ($departmentId ?? 0);
}

$sqlKaryawan .= " ORDER BY department, position, employee_name";
$stmtKaryawan = mysqli_prepare($conn, $sqlKaryawan);
if ($parameterDepartment !== null) {
    mysqli_stmt_bind_param($stmtKaryawan, "i", $parameterDepartment);
}
mysqli_stmt_execute($stmtKaryawan);
$hasilKaryawan = mysqli_stmt_get_result($stmtKaryawan);
$dataKaryawan = [];

while ($item = mysqli_fetch_assoc($hasilKaryawan)) {
    $dataKaryawan[] = [
        "id" => (int) $item["id"],
        "emp_id" => (string) ($item["emp_id"] ?? ""),
        "nama" => (string) ($item["employee_name"] ?? ""),
        "posisi" => (string) ($item["position"] ?? ""),
        "departemen" => (string) ($item["department"] ?? ""),
        "department_id" => (int) $item["department_id"],
    ];
}
mysqli_stmt_close($stmtKaryawan);

$departemenPilihan = [];
foreach ($dataKaryawan as $item) {
    $departemenPilihan[(string) $item["department_id"]] = $item["departemen"];
}
asort($departemenPilihan, SORT_NATURAL | SORT_FLAG_CASE);

$sqlDaftar = "SELECT c.*, k.emp_id, k.employee_name, k.position, k.department,
                     p.emp_id AS pengganti_emp_id, p.employee_name AS pengganti_nama,
                     pembuat.nama AS nama_pembuat, pemroses.nama AS nama_pemroses
              FROM izin_cuti c
              INNER JOIN karyawan k ON k.id = c.karyawan_id
              INNER JOIN karyawan p ON p.id = c.karyawan_pengganti_id
              INNER JOIN users pembuat ON pembuat.id = c.dibuat_oleh_user_id
              LEFT JOIN users pemroses ON pemroses.id = c.diproses_oleh_user_id";
$parameterDaftar = null;

if (roleOperasional()) {
    $sqlDaftar .= " WHERE c.department_id = ?";
    $parameterDaftar = (int) ($departmentId ?? 0);
}

$sqlDaftar .= " ORDER BY c.created_at DESC, c.id DESC";
$stmtDaftar = mysqli_prepare($conn, $sqlDaftar);
if ($parameterDaftar !== null) {
    mysqli_stmt_bind_param($stmtDaftar, "i", $parameterDaftar);
}
mysqli_stmt_execute($stmtDaftar);
$daftarCuti = mysqli_stmt_get_result($stmtDaftar);
$adaAksiTabel = false;

if ($bolehMenyetujui && $daftarCuti) {
    while ($itemCuti = mysqli_fetch_assoc($daftarCuti)) {
        if ($itemCuti["status"] === "menunggu") {
            $adaAksiTabel = true;
            break;
        }
    }

    if (mysqli_num_rows($daftarCuti) > 0) {
        mysqli_data_seek($daftarCuti, 0);
    }
}

$judulHalaman = "Izin Cuti";
$subjudulHalaman = "Pengajuan dan pemantauan izin cuti karyawan.";
$halamanAktif = "izin-cuti";

require __DIR__ . "/partials/atas.php";
?>

<?php if ($pesan !== ""): ?>
    <div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div>
<?php endif; ?>

<section class="form-card izin-form-card">
    <div class="form-card-header">
        <h2>Tambah Izin Cuti</h2>
        <p>Pilih departemen terlebih dahulu, kemudian pilih posisi dan karyawan yang sesuai.</p>
    </div>

    <div class="form-body">
        <form method="POST" id="izin-cuti-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
            <input type="hidden" name="aksi" value="simpan">

            <div class="form-group">
                <label for="cuti-department">Departemen</label>
                <select id="cuti-department" name="department_id" required>
                    <option value="">Pilih departemen</option>
                    <?php foreach ($departemenPilihan as $idDepartemen => $namaDepartemen): ?>
                        <option value="<?= (int) $idDepartemen; ?>" <?= $form["department_id"] === (string) $idDepartemen ? "selected" : ""; ?>>
                            <?= htmlspecialchars($namaDepartemen); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="cuti-position">Posisi</label>
                <select id="cuti-position" name="position" data-selected="<?= htmlspecialchars($form["position"]); ?>" required disabled>
                    <option value="">Pilih departemen terlebih dahulu</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="cuti-karyawan">Karyawan</label>
                <select id="cuti-karyawan" name="karyawan_id" data-selected="<?= (int) $form["karyawan_id"]; ?>" required disabled>
                    <option value="">Pilih departemen dan posisi terlebih dahulu</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cuti-tanggal-mulai">Tanggal Awal Cuti</label>
                <input id="cuti-tanggal-mulai" type="date" name="tanggal_mulai" value="<?= htmlspecialchars($form["tanggal_mulai"]); ?>" required>
            </div>

            <div class="form-group">
                <label for="cuti-tanggal-selesai">Tanggal Akhir Cuti</label>
                <input id="cuti-tanggal-selesai" type="date" name="tanggal_selesai" value="<?= htmlspecialchars($form["tanggal_selesai"]); ?>" required>
            </div>

            <div class="form-group">
                <label for="cuti-jenis">Jenis Durasi Cuti</label>
                <select id="cuti-jenis" name="jenis_cuti" required>
                    <option value="harian" <?= $form["jenis_cuti"] === "harian" ? "selected" : ""; ?>>Harian penuh</option>
                    <option value="setengah_hari" <?= $form["jenis_cuti"] === "setengah_hari" ? "selected" : ""; ?>>Setengah hari</option>
                </select>
                <p class="field-note">Durasi dihitung berdasarkan hari kalender.</p>
            </div>

            <div class="form-group">
                <label for="cuti-periode">Periode Setengah Hari</label>
                <select id="cuti-periode" name="periode_setengah_hari" data-selected="<?= htmlspecialchars($form["periode_setengah_hari"]); ?>" disabled>
                    <option value="">Pilih periode</option>
                    <option value="pagi">Setengah hari pertama (pagi)</option>
                    <option value="siang">Setengah hari kedua (siang)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="cuti-total-hari">Total Durasi</label>
                <input id="cuti-total-hari" type="text" value="-" readonly>
            </div>

            <div class="form-group">
                <label for="cuti-kontak">Nomor Kontak yang Bisa Dihubungi</label>
                <input id="cuti-kontak" type="tel" name="nomor_kontak" maxlength="50" value="<?= htmlspecialchars($form["nomor_kontak"]); ?>" placeholder="Contoh: 081234567890" required>
            </div>

            <div class="form-group full-width">
                <label for="cuti-deskripsi">Deskripsi Keperluan</label>
                <textarea id="cuti-deskripsi" name="deskripsi" rows="4" required><?= htmlspecialchars($form["deskripsi"]); ?></textarea>
            </div>

            <div class="form-group full-width">
                <label for="cuti-pengganti">Karyawan Pengganti</label>
                <select id="cuti-pengganti" name="karyawan_pengganti_id" data-selected="<?= (int) $form["karyawan_pengganti_id"]; ?>" required disabled>
                    <option value="">Pilih departemen dan karyawan terlebih dahulu</option>
                </select>
                <div id="cuti-pengganti-info" class="replacement-employee-info" hidden></div>
                <p class="field-note">Pilihan hanya menampilkan karyawan lain dari departemen yang sama.</p>
            </div>

            <button class="btn btn-success" type="submit">Simpan Izin Cuti</button>
        </form>
    </div>
</section>

<section class="data-card izin-data-card">
    <div class="data-card-header">
        <h2>Daftar Izin Cuti</h2>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Karyawan</th>
                    <th>Posisi</th>
                    <th>Departemen</th>
                    <th>Tanggal Awal</th>
                    <th>Tanggal Akhir</th>
                    <th>Jenis</th>
                    <th>Periode</th>
                    <th>Total Hari</th>
                    <th>Keperluan</th>
                    <th>Kontak</th>
                    <th>Karyawan Pengganti</th>
                    <th>Status</th>
                    <th>Dibuat Oleh</th>
                    <th class="izin-actions-header <?= $adaAksiTabel ? "has-action-buttons" : "no-action-buttons"; ?>">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($daftarCuti && mysqli_num_rows($daftarCuti) > 0): ?>
                    <?php while ($cuti = mysqli_fetch_assoc($daftarCuti)): ?>
                        <?php
                        $jenisLabel = $cuti["jenis_cuti"] === "setengah_hari" ? "Setengah hari" : "Harian penuh";
                        $periodeLabel = match ((string) ($cuti["periode_setengah_hari"] ?? "")) {
                            "pagi" => "Pagi",
                            "siang" => "Siang",
                            default => "-",
                        };
                        $totalHariLabel = (float) $cuti["total_hari"] === 0.5
                            ? "0,5 hari"
                            : number_format((float) $cuti["total_hari"], 0, ",", ".") . " hari";
                        $aksiTersedia = $bolehMenyetujui && $cuti["status"] === "menunggu";
                        ?>
                        <tr>
                            <td><?= (int) $cuti["id"]; ?></td>
                            <td><?= htmlspecialchars($cuti["emp_id"] . " - " . $cuti["employee_name"]); ?></td>
                            <td><?= htmlspecialchars((string) $cuti["position"]); ?></td>
                            <td><?= htmlspecialchars((string) $cuti["department"]); ?></td>
                            <td><?= htmlspecialchars((string) $cuti["tanggal_mulai"]); ?></td>
                            <td><?= htmlspecialchars((string) $cuti["tanggal_selesai"]); ?></td>
                            <td><?= htmlspecialchars($jenisLabel); ?></td>
                            <td><?= htmlspecialchars($periodeLabel); ?></td>
                            <td><?= htmlspecialchars($totalHariLabel); ?></td>
                            <td class="izin-description-cell"><?= nl2br(htmlspecialchars((string) $cuti["deskripsi"])); ?></td>
                            <td><?= htmlspecialchars((string) $cuti["nomor_kontak"]); ?></td>
                            <td><?= htmlspecialchars($cuti["pengganti_emp_id"] . " - " . $cuti["pengganti_nama"]); ?></td>
                            <td><span class="status-badge status-<?= htmlspecialchars((string) $cuti["status"]); ?>"><?= htmlspecialchars((string) $cuti["status"]); ?></span></td>
                            <td><?= htmlspecialchars((string) $cuti["nama_pembuat"]); ?></td>
                            <td class="izin-actions <?= $aksiTersedia ? "has-action-buttons" : "no-action-buttons"; ?>">
                                <?php if ($aksiTersedia): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                                        <input type="hidden" name="aksi" value="keputusan">
                                        <input type="hidden" name="cuti_id" value="<?= (int) $cuti["id"]; ?>">
                                        <input name="catatan" placeholder="Catatan/alasan penolakan">
                                        <button class="btn btn-success" type="submit" name="keputusan" value="disetujui">Setujui</button>
                                        <button class="btn btn-danger" type="submit" name="keputusan" value="ditolak">Tolak</button>
                                    </form>
                                <?php elseif ($cuti["nama_pemroses"]): ?>
                                    <div class="decision-summary">
                                        <strong><?= htmlspecialchars((string) $cuti["nama_pemroses"]); ?></strong>
                                        <?php if (trim((string) ($cuti["catatan_persetujuan"] ?? "")) !== ""): ?>
                                            <span><?= htmlspecialchars((string) $cuti["catatan_persetujuan"]); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="15" class="empty-table">Belum ada data izin cuti.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(() => {
    const employees = <?= json_encode($dataKaryawan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const department = document.getElementById("cuti-department");
    const position = document.getElementById("cuti-position");
    const employee = document.getElementById("cuti-karyawan");
    const replacement = document.getElementById("cuti-pengganti");
    const replacementInfo = document.getElementById("cuti-pengganti-info");
    const startDate = document.getElementById("cuti-tanggal-mulai");
    const endDate = document.getElementById("cuti-tanggal-selesai");
    const leaveType = document.getElementById("cuti-jenis");
    const halfDayPeriod = document.getElementById("cuti-periode");
    const totalDays = document.getElementById("cuti-total-hari");

    const createOption = (value, label) => {
        const option = document.createElement("option");
        option.value = String(value);
        option.textContent = label;
        return option;
    };

    const updatePositions = (restoreSelection = false) => {
        const selectedPosition = restoreSelection ? position.dataset.selected : "";
        position.replaceChildren();

        if (!department.value) {
            position.append(createOption("", "Pilih departemen terlebih dahulu"));
            position.disabled = true;
            position.dataset.selected = "";
            updateEmployees(false);
            return;
        }

        const positions = [...new Set(
            employees
                .filter(item => String(item.department_id) === department.value)
                .map(item => item.posisi)
        )].sort((first, second) => first.localeCompare(second, "id", { sensitivity: "base" }));

        position.append(createOption("", positions.length ? "Pilih posisi" : "Tidak ada posisi pada departemen ini"));
        positions.forEach(item => position.append(createOption(item, item)));
        position.disabled = positions.length === 0;

        if (positions.includes(selectedPosition)) position.value = selectedPosition;
        position.dataset.selected = "";
        updateEmployees(restoreSelection);
    };

    const updateEmployees = (restoreSelection = false) => {
        const selectedId = restoreSelection ? employee.dataset.selected : "";
        employee.replaceChildren();

        if (!department.value || !position.value) {
            employee.append(createOption("", "Pilih departemen dan posisi terlebih dahulu"));
            employee.disabled = true;
            employee.dataset.selected = "";
            updateReplacements(false);
            return;
        }

        const matching = employees.filter(item =>
            String(item.department_id) === department.value && item.posisi === position.value
        );
        employee.append(createOption("", matching.length ? "Pilih karyawan" : "Tidak ada karyawan yang sesuai"));
        matching.forEach(item => employee.append(createOption(item.id, `${item.emp_id} - ${item.nama}`)));
        employee.disabled = matching.length === 0;

        if (matching.some(item => String(item.id) === String(selectedId))) employee.value = String(selectedId);
        employee.dataset.selected = "";
        updateReplacements(restoreSelection);
    };

    const updateReplacements = (restoreSelection = false) => {
        const selectedId = restoreSelection ? replacement.dataset.selected : "";
        replacement.replaceChildren();
        replacementInfo.hidden = true;
        replacementInfo.replaceChildren();

        if (!department.value || !employee.value) {
            replacement.append(createOption("", "Pilih departemen dan karyawan terlebih dahulu"));
            replacement.disabled = true;
            replacement.dataset.selected = "";
            return;
        }

        const matching = employees.filter(item =>
            String(item.department_id) === department.value && String(item.id) !== employee.value
        );
        replacement.append(createOption("", matching.length ? "Pilih karyawan pengganti" : "Tidak ada karyawan pengganti"));
        matching.forEach(item => replacement.append(createOption(item.id, `${item.emp_id} - ${item.nama}`)));
        replacement.disabled = matching.length === 0;

        if (matching.some(item => String(item.id) === String(selectedId))) replacement.value = String(selectedId);
        replacement.dataset.selected = "";
        renderReplacementInfo();
    };

    const renderReplacementInfo = () => {
        const selected = employees.find(item => String(item.id) === replacement.value);
        replacementInfo.replaceChildren();
        replacementInfo.hidden = !selected;
        if (!selected) return;
        const positionInfo = document.createElement("span");
        positionInfo.textContent = `Posisi: ${selected.posisi || "-"}`;
        const departmentInfo = document.createElement("span");
        departmentInfo.textContent = `Departemen: ${selected.departemen || "-"}`;
        replacementInfo.append(positionInfo, departmentInfo);
    };

    const updateDuration = () => {
        const halfDay = leaveType.value === "setengah_hari";
        halfDayPeriod.disabled = !halfDay;
        halfDayPeriod.required = halfDay;

        if (!halfDay) {
            halfDayPeriod.value = "";
        } else if (halfDayPeriod.dataset.selected) {
            halfDayPeriod.value = halfDayPeriod.dataset.selected;
        }
        halfDayPeriod.dataset.selected = "";

        endDate.readOnly = halfDay;
        if (startDate.value) {
            endDate.min = startDate.value;
            if (halfDay) endDate.value = startDate.value;
        }

        if (halfDay && startDate.value) {
            totalDays.value = "0,5 hari";
            return;
        }

        if (!startDate.value || !endDate.value || endDate.value < startDate.value) {
            totalDays.value = "-";
            return;
        }

        const start = new Date(`${startDate.value}T00:00:00`);
        const end = new Date(`${endDate.value}T00:00:00`);
        const days = Math.round((end - start) / 86400000) + 1;
        totalDays.value = `${days} hari`;
    };

    const openDatePicker = input => {
        if (input.readOnly || typeof input.showPicker !== "function") return;
        try {
            input.showPicker();
        } catch (error) {
            // Browser tetap dapat memakai pemilih tanggal bawaannya.
        }
    };

    department.addEventListener("change", () => updatePositions(false));
    position.addEventListener("change", () => updateEmployees(false));
    employee.addEventListener("change", () => updateReplacements(false));
    replacement.addEventListener("change", renderReplacementInfo);
    startDate.addEventListener("change", updateDuration);
    endDate.addEventListener("change", updateDuration);
    leaveType.addEventListener("change", updateDuration);
    startDate.addEventListener("click", () => openDatePicker(startDate));
    endDate.addEventListener("click", () => openDatePicker(endDate));

    updatePositions(true);
    updateDuration();
})();
</script>

<?php
mysqli_stmt_close($stmtDaftar);
require __DIR__ . "/partials/bawah.php";
