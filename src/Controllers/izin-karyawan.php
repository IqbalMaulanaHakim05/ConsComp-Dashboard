<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Leave/izin-karyawan.php';
require_once __DIR__ . '/../Services/Settings/master-data.php';

wajibRole("admin", "superadmin", "pic", "koordinator", "manager");

$pesan = "";
$roleSaatIni = rolePengguna();
$departmentId = departmentIdPengguna();
$keputusanLangsungSuperadmin = $roleSaatIni === "superadmin";
$bolehMenginput = in_array($roleSaatIni, ["admin", "superadmin"], true);
$tahapPersetujuanRole = tahapPersetujuanIzinUntukRole($roleSaatIni);
$bolehMenyetujui = $keputusanLangsungSuperadmin || $tahapPersetujuanRole !== null;
$bolehMenghapus = $roleSaatIni === "superadmin";
$durasiDiizinkan = [30, 60, 120];
$form = [
    "position" => "",
    "department_id" => "",
    "karyawan_id" => "",
    "jam_mulai" => "",
    "durasi_menit" => "",
    "deskripsi" => "",
    "nomor_kontak" => "",
    "karyawan_pengganti_id" => "",
];

if (!siapkanTabelIzinKaryawan($conn)) {
    http_response_code(500);
    die("Tabel izin meninggalkan pekerjaan tidak dapat disiapkan.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = (string) ($_POST["aksi"] ?? "simpan");

    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesan = "Token keamanan tidak valid.";
    } elseif ($aksi === "keputusan") {
        $izinId = (int) ($_POST["izin_id"] ?? 0);
        $keputusan = (string) ($_POST["keputusan"] ?? "");
        $catatan = trim((string) ($_POST["catatan"] ?? ""));

        if (!$bolehMenyetujui) {
            $pesan = "Persetujuan izin hanya dapat diproses berurutan oleh PIC, Koordinator, dan Manager.";
        } elseif (!in_array($keputusan, ["disetujui", "ditolak"], true)) {
            $pesan = "Keputusan izin tidak valid.";
        } elseif ($keputusan === "ditolak" && $catatan === "") {
            $pesan = "Alasan penolakan wajib diisi.";
        } else {
            $pemrosesId = (int) ($_SESSION["user"]["id"] ?? 0);
            if ($keputusanLangsungSuperadmin) {
                $berhasilDiproses = prosesKeputusanLangsungSuperadminIzin(
                    $conn,
                    "izin_meninggalkan_pekerjaan",
                    $izinId,
                    $roleSaatIni,
                    $keputusan,
                    $catatan,
                    $pemrosesId
                );
                $labelTahap = "Superadmin";
            } else {
                $berhasilDiproses = prosesKeputusanPersetujuanIzin(
                    $conn,
                    "izin_meninggalkan_pekerjaan",
                    $izinId,
                    (int) ($departmentId ?? 0),
                    $roleSaatIni,
                    $keputusan,
                    $catatan,
                    $pemrosesId
                );
                $labelTahap = labelTahapPersetujuanIzin((string) $tahapPersetujuanRole);
            }

            if ($berhasilDiproses) {
                catatAktivitas($conn, $labelTahap . " memproses izin meninggalkan pekerjaan ID " . $izinId . " menjadi " . $keputusan . ".");
                header("Location: izin-karyawan.php?pesan=" . urlencode("Keputusan " . $labelTahap . " berhasil disimpan."));
                exit;
            }

            $pesan = $keputusanLangsungSuperadmin
                ? "Data izin sudah diproses atau tidak ditemukan."
                : "Tahap persetujuan izin tidak sesuai, sudah diproses, atau berada di luar departemen Anda.";
        }
    } elseif ($aksi === "hapus") {
        $izinId = (int) ($_POST["izin_id"] ?? 0);

        if (!$bolehMenghapus) {
            $pesan = "Hanya superadmin yang dapat menghapus data izin.";
        } elseif ($izinId <= 0) {
            $pesan = "Data izin yang akan dihapus tidak valid.";
        } else {
            $berhasilDihapus = false;

            try {
                $stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM izin_meninggalkan_pekerjaan
                     WHERE id = ? AND status IN ('disetujui', 'ditolak')"
                );
                mysqli_stmt_bind_param($stmt, "i", $izinId);
                mysqli_stmt_execute($stmt);
                $berhasilDihapus = mysqli_stmt_affected_rows($stmt) > 0;
                mysqli_stmt_close($stmt);
            } catch (mysqli_sql_exception $exception) {
                $berhasilDihapus = false;
            }

            if ($berhasilDihapus) {
                catatAktivitas($conn, "Menghapus izin meninggalkan pekerjaan ID " . $izinId . ".");
                header("Location: izin-karyawan.php?pesan=" . urlencode("Data izin berhasil dihapus."));
                exit;
            }

            $pesan = "Data izin tidak ditemukan atau statusnya belum disetujui/ditolak.";
        }
    } elseif ($aksi === "simpan" && !$bolehMenginput) {
        $pesan = "Hanya Admin dan superadmin yang dapat menginput izin.";
    } elseif ($aksi === "simpan") {
        foreach (array_keys($form) as $namaKolom) {
            $form[$namaKolom] = trim((string) ($_POST[$namaKolom] ?? ""));
        }

        $karyawanId = (int) $form["karyawan_id"];
        $departmentFormId = (int) $form["department_id"];
        $karyawanPenggantiId = (int) $form["karyawan_pengganti_id"];
        $durasiMenit = (int) $form["durasi_menit"];
        $jamMulai = DateTimeImmutable::createFromFormat("!H:i", $form["jam_mulai"]);
        $jamValid = $jamMulai !== false && $jamMulai->format("H:i") === $form["jam_mulai"];

        if (
            $form["position"] === ""
            || $departmentFormId <= 0
            || $karyawanId <= 0
            || !$jamValid
            || !in_array($durasiMenit, $durasiDiizinkan, true)
            || $form["deskripsi"] === ""
            || $form["nomor_kontak"] === ""
            || $karyawanPenggantiId <= 0
        ) {
            $pesan = "Seluruh data izin wajib diisi dengan pilihan yang valid.";
        } elseif (strlen($form["nomor_kontak"]) > 50) {
            $pesan = "Nomor kontak maksimal 50 karakter.";
        } elseif ($karyawanId === $karyawanPenggantiId) {
            $pesan = "Karyawan pengganti harus berbeda dari karyawan yang mengajukan izin.";
        } elseif (roleOperasional() && (int) ($departmentId ?? 0) !== $departmentFormId) {
            $pesan = "Departemen yang dipilih berada di luar cakupan Anda.";
        } else {
            $stmtKaryawan = mysqli_prepare(
                $conn,
                "SELECT id, department_id
                 FROM karyawan
                 WHERE id = ? AND department_id = ? AND position = ?
                 LIMIT 1"
            );
            mysqli_stmt_bind_param($stmtKaryawan, "iis", $karyawanId, $departmentFormId, $form["position"]);
            mysqli_stmt_execute($stmtKaryawan);
            $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtKaryawan));
            mysqli_stmt_close($stmtKaryawan);

            $stmtPengganti = mysqli_prepare(
                $conn,
                "SELECT id
                 FROM karyawan
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
                    "INSERT INTO izin_meninggalkan_pekerjaan
                        (karyawan_id, department_id, jam_mulai, durasi_menit, deskripsi,
                         nomor_kontak, karyawan_pengganti_id, dibuat_oleh_user_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "iisissii",
                    $karyawanId,
                    $departmentFormId,
                    $form["jam_mulai"],
                    $durasiMenit,
                    $form["deskripsi"],
                    $form["nomor_kontak"],
                    $karyawanPenggantiId,
                    $pembuatId
                );

                if (mysqli_stmt_execute($stmt)) {
                    $izinBaruId = (int) mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);
                    catatAktivitas($conn, "Membuat izin meninggalkan pekerjaan ID " . $izinBaruId . ".");
                    header("Location: izin-karyawan.php?pesan=" . urlencode("Izin berhasil disimpan dan menunggu persetujuan."));
                    exit;
                }

                mysqli_stmt_close($stmt);
                $pesan = "Izin meninggalkan pekerjaan gagal disimpan.";
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
$departemenPilihan = ambilDepartemenPilihan($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);
$posisiPerDepartemen = ambilPosisiPerDepartemen($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);

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


$sqlDaftar = "SELECT i.*, k.emp_id, k.employee_name, k.position, k.department,
                     p.emp_id AS pengganti_emp_id, p.employee_name AS pengganti_nama,
                     pembuat.nama AS nama_pembuat, pemroses.nama AS nama_pemroses,
                     pemroses.role AS role_pemroses
              FROM izin_meninggalkan_pekerjaan i
              INNER JOIN karyawan k ON k.id = i.karyawan_id
              INNER JOIN karyawan p ON p.id = i.karyawan_pengganti_id
              INNER JOIN users pembuat ON pembuat.id = i.dibuat_oleh_user_id
              LEFT JOIN users pemroses ON pemroses.id = i.diproses_oleh_user_id";
$parameterDaftar = null;

if (roleOperasional()) {
    $sqlDaftar .= " WHERE i.department_id = ?";
    $parameterDaftar = (int) ($departmentId ?? 0);
}

$sqlDaftar .= " ORDER BY i.created_at DESC, i.id DESC";
$stmtDaftar = mysqli_prepare($conn, $sqlDaftar);
if ($parameterDaftar !== null) {
    mysqli_stmt_bind_param($stmtDaftar, "i", $parameterDaftar);
}
mysqli_stmt_execute($stmtDaftar);
$daftarIzin = mysqli_stmt_get_result($stmtDaftar);

$notifikasiIzinKaryawan = mysqli_query($conn, "SELECT a.dibuat_pada, a.aktivitas, u.username FROM audit_aktivitas a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 12");

$judulHalaman = "Izin Karyawan";
$subjudulHalaman = "Pengajuan izin meninggalkan pekerjaan.";
$halamanAktif = "izin-karyawan";

require __DIR__ . '/../../resources/views/layouts/atas.php';
?>

<?php if ($pesan !== ""): ?>
    <div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div>
<?php endif; ?>

<div class="overtime-notification-action">
    <a class="btn btn-primary" href="notifikasi-izin-karyawan.php">🔔 Buka Notifikasi</a>
    <a class="btn export-excel-btn" href="export_izin_karyawan.php">Export Excel</a>
</div>
<section class="data-card overtime-notifications"><div class="data-card-header"><h2>Notifikasi Terbaru</h2><p class="overtime-note">Persetujuan izin karyawan dan perubahan data terbaru.</p></div><div class="notification-list"><?php if ($notifikasiIzinKaryawan && mysqli_num_rows($notifikasiIzinKaryawan) > 0): ?><?php while ($notif = mysqli_fetch_assoc($notifikasiIzinKaryawan)): ?><div class="notification-item"><strong><?= htmlspecialchars($notif["username"] ?: "Sistem"); ?></strong><span><?= htmlspecialchars(labelAktivitas((string) $notif["aktivitas"])); ?></span><small><?= htmlspecialchars($notif["dibuat_pada"]); ?></small></div><?php endwhile; ?><?php else: ?><div class="notification-empty">Belum ada notifikasi.</div><?php endif; ?></div></section>

<?php if ($bolehMenginput): ?>
<form method="POST" id="izin-karyawan-form" class="izin-entry-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
    <input type="hidden" name="aksi" value="simpan">

    <section class="form-card izin-form-card">
        <div class="form-card-header">
            <h2>Tambah Izin Meninggalkan Pekerjaan</h2>
            <p>Pilih departemen terlebih dahulu, kemudian pilih posisi untuk menampilkan karyawan yang sesuai.</p>
        </div>

        <div class="form-body izin-form-fields">

            <div class="form-group">
                <label for="izin-department">Departemen</label>
                <select id="izin-department" name="department_id" required>
                    <option value="">Pilih departemen</option>
                    <?php foreach ($departemenPilihan as $idDepartemen => $namaDepartemen): ?>
                        <option value="<?= (int) $idDepartemen; ?>" <?= $form["department_id"] === (string) $idDepartemen ? "selected" : ""; ?>>
                            <?= htmlspecialchars($namaDepartemen); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="izin-position">Posisi</label>
                <select id="izin-position" name="position" data-selected="<?= htmlspecialchars($form["position"]); ?>" required disabled>
                    <option value="">Pilih departemen terlebih dahulu</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="izin-karyawan">Karyawan</label>
                <select id="izin-karyawan" name="karyawan_id" data-selected="<?= (int) $form["karyawan_id"]; ?>" required disabled>
                    <option value="">Pilih departemen dan posisi terlebih dahulu</option>
                </select>
            </div>

            <div class="form-group">
                <label for="izin-jam-mulai">Jam Mulai</label>
                <input id="izin-jam-mulai" type="time" name="jam_mulai" value="<?= htmlspecialchars($form["jam_mulai"]); ?>" required>
            </div>

            <div class="form-group">
                <label for="izin-durasi">Lama Meninggalkan Pekerjaan</label>
                <select id="izin-durasi" name="durasi_menit" required>
                    <option value="">Pilih durasi</option>
                    <option value="30" <?= $form["durasi_menit"] === "30" ? "selected" : ""; ?>>Setengah jam</option>
                    <option value="60" <?= $form["durasi_menit"] === "60" ? "selected" : ""; ?>>1 jam</option>
                    <option value="120" <?= $form["durasi_menit"] === "120" ? "selected" : ""; ?>>2 jam</option>
                </select>
                <p id="izin-durasi-note" class="field-note" hidden>Maksimal meninggalkan pekerjaan adalah 2 jam.</p>
            </div>

            <div class="form-group">
                <label for="izin-jam-kembali">Jam Kembali</label>
                <input id="izin-jam-kembali" type="text" value="-" readonly aria-describedby="izin-jam-kembali-note">
                <p id="izin-jam-kembali-note" class="field-note">Terisi otomatis berdasarkan jam masuk dan durasi.</p>
            </div>

            <div class="form-group">
                <label for="izin-kontak">Nomor Kontak yang Bisa Dihubungi</label>
                <input id="izin-kontak" type="tel" name="nomor_kontak" maxlength="50" value="<?= htmlspecialchars($form["nomor_kontak"]); ?>" placeholder="Contoh: 081234567890" required>
            </div>

            <div class="form-group full-width">
                <label for="izin-deskripsi">Deskripsi Keperluan</label>
                <textarea id="izin-deskripsi" name="deskripsi" rows="4" required><?= htmlspecialchars($form["deskripsi"]); ?></textarea>
            </div>

        </div>
    </section>

    <section class="form-card replacement-form-card">
        <div class="form-card-header">
            <h2>Karyawan Pengganti</h2>
            <p>Pilih karyawan yang akan menggantikan selama izin berlangsung.</p>
        </div>

        <div class="form-body replacement-form-body">
            <div class="form-group">
                <label for="izin-pengganti-department">Departemen</label>
                <select id="izin-pengganti-department" disabled>
                    <option value="">Pilih departemen</option>
                </select>
            </div>
            <div class="form-group">
                <label for="izin-pengganti-position">Posisi</label>
                <select id="izin-pengganti-position" disabled>
                    <option value="">Pilih departemen terlebih dahulu</option>
                </select>
            </div>
            <div class="form-group">
                <label for="izin-pengganti">Karyawan Pengganti</label>
                <select id="izin-pengganti" name="karyawan_pengganti_id" data-selected="<?= (int) $form["karyawan_pengganti_id"]; ?>" required disabled>
                    <option value="">Pilih departemen dan karyawan terlebih dahulu</option>
                </select>
                <div id="izin-pengganti-info" class="replacement-employee-info" hidden></div>
                <p class="field-note">Pilihan hanya menampilkan karyawan lain dari departemen yang sama.</p>
            </div>

            <button class="btn btn-success replacement-submit" type="submit">Simpan Izin</button>
        </div>
    </section>
</form>
<?php endif; ?>

<section class="data-card izin-data-card">
    <div class="data-card-header">
        <h2>Daftar Izin Meninggalkan Pekerjaan</h2>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Karyawan</th>
                    <th>Posisi</th>
                    <th>Departemen</th>
                    <th>Jam Masuk</th>
                    <th>Durasi</th>
                    <th>Jam Kembali</th>
                    <th>Keperluan</th>
                    <th>Kontak</th>
                    <th>Karyawan Pengganti</th>
                    <th>Status</th>
                    <th>Dibuat Oleh</th>
                    <th class="izin-actions-header">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($daftarIzin && mysqli_num_rows($daftarIzin) > 0): ?>
                    <?php while ($izin = mysqli_fetch_assoc($daftarIzin)): ?>
                        <?php
                        $jamMulaiTabel = DateTimeImmutable::createFromFormat("H:i:s", (string) $izin["jam_mulai"]);
                        $jamKembaliTabel = $jamMulaiTabel
                            ? $jamMulaiTabel->modify("+" . (int) $izin["durasi_menit"] . " minutes")->format("H:i")
                            : "-";
                        $labelDurasi = match ((int) $izin["durasi_menit"]) {
                            30 => "Setengah jam",
                            60 => "1 jam",
                            120 => "2 jam",
                            default => (int) $izin["durasi_menit"] . " menit",
                        };
                        $tahapPersetujuan = (string) ($izin["tahap_persetujuan"] ?? "pic");
                        $statusPersetujuan = labelStatusPersetujuanIzin(
                            (string) $izin["status"],
                            $tahapPersetujuan,
                            (string) ($izin["role_pemroses"] ?? "")
                        );
                        $bolehMemproses = $izin["status"] === "menunggu"
                            && ($keputusanLangsungSuperadmin
                                || ($bolehMenyetujui && $tahapPersetujuan === $tahapPersetujuanRole));
                        $bolehMenghapusData = $bolehMenghapus && in_array($izin["status"], ["disetujui", "ditolak"], true);
                        ?>
                        <tr>
                            <td><?= (int) $izin["id"]; ?></td>
                            <td><?= htmlspecialchars($izin["emp_id"] . " - " . $izin["employee_name"]); ?></td>
                            <td><?= htmlspecialchars((string) $izin["position"]); ?></td>
                            <td><?= htmlspecialchars((string) $izin["department"]); ?></td>
                            <td><?= htmlspecialchars(substr((string) $izin["jam_mulai"], 0, 5)); ?></td>
                            <td><?= htmlspecialchars($labelDurasi); ?></td>
                            <td><?= htmlspecialchars($jamKembaliTabel); ?></td>
                            <td class="izin-description-cell"><?= nl2br(htmlspecialchars((string) $izin["deskripsi"])); ?></td>
                            <td><?= htmlspecialchars((string) $izin["nomor_kontak"]); ?></td>
                            <td><?= htmlspecialchars($izin["pengganti_emp_id"] . " - " . $izin["pengganti_nama"]); ?></td>
                            <td><span class="status-badge status-<?= htmlspecialchars((string) $izin["status"]); ?>"><?= htmlspecialchars($statusPersetujuan); ?></span></td>
                            <td><?= htmlspecialchars((string) $izin["nama_pembuat"]); ?></td>
                            <td class="izin-actions">
                                <?php if ($bolehMemproses): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                                        <input type="hidden" name="aksi" value="keputusan">
                                        <input type="hidden" name="izin_id" value="<?= (int) $izin["id"]; ?>">
                                        <input name="catatan" placeholder="Catatan/alasan penolakan">
                                        <button class="btn btn-success" type="submit" name="keputusan" value="disetujui">Setujui</button>
                                        <button class="btn btn-danger" type="submit" name="keputusan" value="ditolak">Tolak</button>
                                    </form>
                                <?php elseif (!$bolehMenghapusData): ?>
                                    -
                                <?php endif; ?>

                                <?php if ($bolehMenghapusData): ?>
                                    <form method="POST" class="delete-izin-form" onsubmit="return confirm('Hapus permanen data izin ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="izin_id" value="<?= (int) $izin["id"]; ?>">
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13" class="empty-table">Belum ada data izin meninggalkan pekerjaan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($bolehMenginput): ?>
<script>
(() => {
    const employees = <?= json_encode($dataKaryawan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const positionsByDepartment = <?= json_encode($posisiPerDepartemen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const position = document.getElementById("izin-position");
    const department = document.getElementById("izin-department");
    const employee = document.getElementById("izin-karyawan");
    const replacement = document.getElementById("izin-pengganti");
    const replacementDepartment = document.getElementById("izin-pengganti-department");
    const replacementPosition = document.getElementById("izin-pengganti-position");
    const replacementInfo = document.getElementById("izin-pengganti-info");
    const startTime = document.getElementById("izin-jam-mulai");
    const duration = document.getElementById("izin-durasi");
    const returnTime = document.getElementById("izin-jam-kembali");
    const durationNote = document.getElementById("izin-durasi-note");

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

        const availablePositions = [...new Set(positionsByDepartment[department.value] || [])].sort((first, second) => first.localeCompare(second, "id", { sensitivity: "base" }));

        position.append(createOption("", availablePositions.length ? "Pilih posisi" : "Tidak ada posisi pada departemen ini"));
        availablePositions.forEach(item => position.append(createOption(item, item)));
        position.disabled = availablePositions.length === 0;

        if (availablePositions.includes(selectedPosition)) {
            position.value = selectedPosition;
        }
        position.dataset.selected = "";
        updateEmployees(restoreSelection);
    };

    const updateEmployees = (restoreSelection = false) => {
        const selectedId = restoreSelection ? employee.dataset.selected : "";
        employee.replaceChildren();

        if (!position.value || !department.value) {
            employee.append(createOption("", "Pilih departemen dan posisi terlebih dahulu"));
            employee.disabled = true;
            employee.dataset.selected = "";
            updateReplacements(false);
            return;
        }

        const matching = employees.filter(item =>
            item.posisi === position.value
            && String(item.department_id) === department.value
        );

        employee.append(createOption("", matching.length ? "Pilih karyawan" : "Tidak ada karyawan yang sesuai"));
        matching.forEach(item => employee.append(createOption(item.id, `${item.emp_id} - ${item.nama}`)));
        employee.disabled = matching.length === 0;

        if (matching.length === 1) {
            employee.value = String(matching[0].id);
        } else if (matching.some(item => String(item.id) === String(selectedId))) {
            employee.value = String(selectedId);
        }
        employee.dataset.selected = "";
        updateReplacements(restoreSelection);
    };

    const updateReplacements = (restoreSelection = false) => {
        const selectedReplacement = restoreSelection ? replacement.dataset.selected : "";
        const selectedDepartment = department.value;
        const selectedEmployee = employee.value;
        const selectedReplacementPosition = replacementPosition.value;
        replacement.replaceChildren();
        replacementInfo.hidden = true;
        replacementInfo.replaceChildren();
        replacementDepartment.replaceChildren(createOption("", "Pilih departemen"));
        replacementPosition.replaceChildren(createOption("", "Pilih departemen terlebih dahulu"));
        replacementDepartment.disabled = !selectedDepartment;
        replacementPosition.disabled = true;
        if (selectedDepartment) {
            replacementDepartment.append(createOption(selectedDepartment, department.options[department.selectedIndex].text));
            replacementDepartment.value = selectedDepartment;
            replacementPosition.replaceChildren(createOption("", "Posisi karyawan pengganti"));
            (positionsByDepartment[selectedDepartment] || []).forEach(item => replacementPosition.append(createOption(item, item)));
            replacementPosition.disabled = false;
            if ((positionsByDepartment[selectedDepartment] || []).includes(selectedReplacementPosition)) {
                replacementPosition.value = selectedReplacementPosition;
            }
        }

        if (!selectedDepartment || !selectedEmployee) {
            replacement.append(createOption("", "Pilih departemen dan karyawan terlebih dahulu"));
            replacement.disabled = true;
            replacement.dataset.selected = "";
            return;
        }

        const matching = employees.filter(item =>
            String(item.department_id) === selectedDepartment
            && (!replacementPosition.value || item.posisi === replacementPosition.value)
            && String(item.id) !== selectedEmployee
        );

        replacement.append(createOption("", matching.length ? "Pilih karyawan pengganti" : "Tidak ada karyawan pengganti"));
        matching.forEach(item => replacement.append(createOption(item.id, `${item.emp_id} - ${item.nama}`)));
        replacement.disabled = matching.length === 0;

        if (matching.some(item => String(item.id) === String(selectedReplacement))) {
            replacement.value = String(selectedReplacement);
        }
        replacement.dataset.selected = "";
        renderReplacementInfo();
    };

    const renderReplacementInfo = () => {
        const selected = employees.find(item => String(item.id) === replacement.value);
        replacementInfo.replaceChildren();
        replacementInfo.hidden = !selected;
        if (!selected) return;
        replacementPosition.value = selected.posisi || "";
        const positionInfo = document.createElement("span");
        positionInfo.textContent = `Posisi: ${selected.posisi || "-"}`;
        const departmentInfo = document.createElement("span");
        departmentInfo.textContent = `Departemen: ${selected.departemen || "-"}`;
        replacementInfo.append(positionInfo, departmentInfo);
    };

    const updateReturnTime = () => {
        durationNote.hidden = !duration.value;

        if (!startTime.value || !duration.value) {
            returnTime.value = "-";
            return;
        }

        const [hours, minutes] = startTime.value.split(":").map(Number);
        const totalMinutes = hours * 60 + minutes + Number(duration.value);
        const returnHours = Math.floor((totalMinutes % 1440) / 60);
        const returnMinutes = totalMinutes % 60;
        returnTime.value = `${String(returnHours).padStart(2, "0")}:${String(returnMinutes).padStart(2, "0")}`;
    };

    department.addEventListener("change", () => updatePositions(false));
    position.addEventListener("change", () => updateEmployees(false));
    employee.addEventListener("change", () => updateReplacements(false));
    replacement.addEventListener("change", renderReplacementInfo);
    replacementPosition.addEventListener("change", () => updateReplacements(false));
    startTime.addEventListener("input", updateReturnTime);
    startTime.addEventListener("click", () => {
        if (typeof startTime.showPicker !== "function") return;

        try {
            startTime.showPicker();
        } catch (error) {
            // Browser tetap dapat memakai pemilih waktu bawaan melalui ikonnya.
        }
    });
    duration.addEventListener("change", updateReturnTime);

    updatePositions(true);
    updateReturnTime();
})();
</script>
<?php endif; ?>

<?php
mysqli_stmt_close($stmtDaftar);
require __DIR__ . '/../../resources/views/layouts/bawah.php';
