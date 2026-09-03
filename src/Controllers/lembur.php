<?php

declare(strict_types=1);
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Settings/master-data.php';
require_once __DIR__ . '/../Services/Employee/jadwal-cuti.php';
wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
siapkanJadwalDanCutiKaryawan($conn);
$pesan = "";
$departmentId = departmentIdPengguna();
$roleSaatIni = rolePengguna();
$bolehInputLembur = in_array($roleSaatIni, ["pic", "admin", "superadmin"], true);
$bolehAjukanLangsung = in_array($roleSaatIni, ["admin", "superadmin"], true);
$bolehApprovalPusat = $roleSaatIni === "superadmin";
$formLembur = [
    "department_id" => "",
    "position" => "",
    "karyawan_id" => "",
    "mulai_at" => "",
    "selesai_at" => "",
    "deskripsi" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && $bolehInputLembur && !isset($_POST["aksi"])) {
    foreach ($formLembur as $field => $value) {
        $formLembur[$field] = trim((string) ($_POST[$field] ?? ""));
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $bolehInputLembur && !isset($_POST["aksi"])) {
    $karyawanId = (int) ($_POST["karyawan_id"] ?? 0);
    $mulai = trim((string) ($_POST["mulai_at"] ?? ""));
    $selesai = trim((string) ($_POST["selesai_at"] ?? ""));
    $deskripsi = trim((string) ($_POST["deskripsi"] ?? ""));
    if (!csrfValid($_POST["csrf_token"] ?? null)) $pesan = "Token keamanan tidak valid.";
    else {
        $awal = DateTime::createFromFormat("Y-m-d\\TH:i", $mulai);
        $akhir = DateTime::createFromFormat("Y-m-d\\TH:i", $selesai);
        $stmt = mysqli_prepare($conn, "SELECT department_id, TIME_FORMAT(shift_mulai, '%H:%i') AS shift_mulai, TIME_FORMAT(shift_selesai, '%H:%i') AS shift_selesai, shift_hari FROM karyawan WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $karyawanId);
        mysqli_stmt_execute($stmt);
        $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$awal || !$akhir || $akhir <= $awal) $pesan = "Waktu selesai harus lebih besar dari waktu mulai.";
        elseif (!$karyawan || ($departmentId !== null && (int) $karyawan["department_id"] !== $departmentId)) $pesan = "Karyawan tidak berada dalam cakupan departemen Anda.";
        elseif (($batasLembur = batasWaktuLemburKaryawan($karyawan, $awal)) === null) $pesan = "Karyawan belum memiliki jadwal kerja lengkap. Atur jam masuk, jam pulang, dan hari kerja terlebih dahulu.";
        elseif (($batasLembur['sedang_bekerja'] ?? false)) $pesan = "Jam mulai lembur tidak dapat berada pada jam kerja karyawan, yaitu " . $batasLembur['mulai_shift_aktif']->format('d-m-Y H:i') . " sampai " . $batasLembur['akhir_shift']->format('d-m-Y H:i') . ".";
        elseif (($batasLembur['hari_kerja'] ?? false) && ($awal >= $batasLembur['mulai_kerja_berikutnya'] || $akhir > $batasLembur['mulai_kerja_berikutnya'])) $pesan = "Waktu lembur pada hari kerja paling lambat berakhir pada jam masuk kerja berikutnya, yaitu " . $batasLembur['mulai_kerja_berikutnya']->format('d-m-Y H:i') . ".";
        elseif (($batasSelesai = batasWaktuLemburKaryawan($karyawan, $akhir)) === null) $pesan = "Karyawan belum memiliki jadwal kerja lengkap. Atur jam masuk, jam pulang, dan hari kerja terlebih dahulu.";
        elseif (($batasSelesai['sedang_bekerja'] ?? false) && $akhir->format('Y-m-d H:i') !== $batasSelesai['mulai_shift_aktif']->format('Y-m-d H:i')) $pesan = "Jam selesai lembur tidak dapat berada pada jam kerja karyawan.";
        else {
            $menit = (int) (($akhir->getTimestamp() - $awal->getTimestamp()) / 60);
            $stmt = mysqli_prepare($conn, "INSERT INTO overtime_reports (karyawan_id, department_id, dibuat_oleh_pic, mulai_at, selesai_at, total_menit, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $picId = (int) $_SESSION["user"]["id"];
            $departmentLaporan = (int) $karyawan["department_id"];
            $mulaiDb = $awal->format("Y-m-d H:i:s");
            $selesaiDb = $akhir->format("Y-m-d H:i:s");
            mysqli_stmt_bind_param($stmt, "iiissis", $karyawanId, $departmentLaporan, $picId, $mulaiDb, $selesaiDb, $menit, $deskripsi);
            if (mysqli_stmt_execute($stmt)) {
                $laporanBaruId = (int) mysqli_insert_id($conn);
                if ($bolehAjukanLangsung) {
                    mysqli_query($conn, "UPDATE overtime_reports SET status = 'menunggu_koordinator', submitted_at = CURRENT_TIMESTAMP WHERE id = " . $laporanBaruId);
                    mysqli_query($conn, "INSERT IGNORE INTO overtime_approvals (overtime_id, tahap) VALUES (" . $laporanBaruId . ", 'koordinator'), (" . $laporanBaruId . ", 'manager')");
                }
                catatAktivitas($conn, "Membuat laporan lembur karyawan ID " . $karyawanId . ".");
                header("Location: lembur.php");
                exit;
            }
            $pesan = "Laporan lembur gagal disimpan.";
            mysqli_stmt_close($stmt);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "keputusan_pusat" && $bolehApprovalPusat) {
    $overtimeId = (int) ($_POST["overtime_id"] ?? 0);
    $keputusan = (string) ($_POST["keputusan"] ?? "");
    $catatan = trim((string) ($_POST["catatan"] ?? ""));
    if (!csrfValid($_POST["csrf_token"] ?? null)) $pesan = "Token keamanan tidak valid.";
    elseif (!in_array($keputusan, ["approved", "rejected"], true) || ($keputusan === "rejected" && $catatan === "")) $pesan = "Keputusan tidak valid atau alasan penolakan belum diisi.";
    else {
        $statusAkhir = $keputusan === "approved" ? "disetujui" : "ditolak";
        $stmt = mysqli_prepare($conn, "UPDATE overtime_reports SET status = ? WHERE id = ? AND status IN ('menunggu_koordinator', 'menunggu_manager')");
        mysqli_stmt_bind_param($stmt, "si", $statusAkhir, $overtimeId);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $userId = (int) $_SESSION["user"]["id"];
            $statusApproval = $keputusan;
            $approval = mysqli_prepare($conn, "UPDATE overtime_approvals SET status = ?, approver_user_id = ?, catatan = ?, decided_at = CURRENT_TIMESTAMP WHERE overtime_id = ? AND status = 'pending'");
            mysqli_stmt_bind_param($approval, "sisi", $statusApproval, $userId, $catatan, $overtimeId);
            mysqli_stmt_execute($approval);
            mysqli_stmt_close($approval);
            catatAktivitas($conn, "Memproses persetujuan pusat lembur ID " . $overtimeId . ".");
            header("Location: lembur.php");
            exit;
        }
        $pesan = "Laporan tidak dapat diproses.";
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && in_array(($_POST["aksi"] ?? ""), ["kirim", "keputusan"], true)) {
    $overtimeId = (int) ($_POST["overtime_id"] ?? 0);
    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesan = "Token keamanan tidak valid.";
    } elseif (($_POST["aksi"] ?? "") === "kirim" && rolePengguna() === "pic") {
        $stmt = mysqli_prepare($conn, "UPDATE overtime_reports SET status = 'menunggu_koordinator', submitted_at = CURRENT_TIMESTAMP WHERE id = ? AND department_id = ? AND status = 'draft'");
        mysqli_stmt_bind_param($stmt, "ii", $overtimeId, $departmentId);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            mysqli_query($conn, "INSERT IGNORE INTO overtime_approvals (overtime_id, tahap) VALUES (" . $overtimeId . ", 'koordinator'), (" . $overtimeId . ", 'manager')");
            header("Location: lembur.php");
            exit;
        }
        $pesan = "Draft tidak dapat dikirim.";
        mysqli_stmt_close($stmt);
    } elseif (($_POST["aksi"] ?? "") === "keputusan" && punyaRole("koordinator", "manager")) {
        $keputusan = (string) ($_POST["keputusan"] ?? "");
        $catatan = trim((string) ($_POST["catatan"] ?? ""));
        $tahap = rolePengguna() === "koordinator" ? "koordinator" : "manager";
        if (!in_array($keputusan, ["approved", "rejected"], true) || ($keputusan === "rejected" && $catatan === "")) $pesan = "Keputusan tidak valid atau alasan penolakan belum diisi.";
        else {
            $bolehProses = true;
            if ($tahap === "manager") {
                $cek = mysqli_prepare($conn, "SELECT 1 FROM overtime_approvals WHERE overtime_id = ? AND tahap = 'koordinator' AND status = 'approved' LIMIT 1");
                mysqli_stmt_bind_param($cek, "i", $overtimeId);
                mysqli_stmt_execute($cek);
                $bolehProses = mysqli_fetch_assoc(mysqli_stmt_get_result($cek)) !== null;
                mysqli_stmt_close($cek);
            }
            if (!$bolehProses) {
                $pesan = "Approval Koordinator harus selesai sebelum Manager memproses laporan.";
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE overtime_approvals a INNER JOIN overtime_reports o ON o.id = a.overtime_id SET a.status = ?, a.approver_user_id = ?, a.catatan = ?, a.decided_at = CURRENT_TIMESTAMP WHERE a.overtime_id = ? AND a.tahap = ? AND o.department_id = ? AND a.status = 'pending'");
                $userId = (int) $_SESSION["user"]["id"];
                mysqli_stmt_bind_param($stmt, "sisisi", $keputusan, $userId, $catatan, $overtimeId, $tahap, $departmentId);
                mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    $status = $keputusan === "rejected" ? "ditolak" : ($tahap === "koordinator" ? "menunggu_manager" : "disetujui");
                    mysqli_query($conn, "UPDATE overtime_reports SET status = '" . $status . "' WHERE id = " . $overtimeId);
                    catatAktivitas($conn, "Memproses approval lembur ID " . $overtimeId . " pada tahap " . $tahap . ".");
                    header("Location: lembur.php");
                    exit;
                }
                $pesan = "Laporan tidak dapat diproses.";
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "kompensasi" && in_array(rolePengguna(), ["pic", "admin", "superadmin"], true)) {
    $overtimeId = (int) ($_POST["overtime_id"] ?? 0);
    $metode = "per_jam";
    $tarif = (float) ($_POST["tarif_per_jam"] ?? 0);
    $jumlah = (float) ($_POST["jumlah_upah"] ?? -1);
    if (!csrfValid($_POST["csrf_token"] ?? null) || !in_array($metode, ["per_jam", "nominal_final"], true) || $jumlah < 0 || $tarif < 0) {
        $pesan = "Data kompensasi tidak valid.";
    } else {
        $cekSql = "SELECT o.id, o.total_menit, COALESCE(pg.gaji_pokok, k.salary, 0) AS gaji_pokok FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN users pembuat ON pembuat.id = o.dibuat_oleh_pic LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE o.id = ? AND o.status = 'disetujui'";
        if (in_array(rolePengguna(), ["pic"], true)) $cekSql .= " AND o.department_id = ?";
        $cekSql .= " LIMIT 1";
        $cek = mysqli_prepare($conn, $cekSql);
        if (rolePengguna() === "pic") mysqli_stmt_bind_param($cek, "ii", $overtimeId, $departmentId);
        else mysqli_stmt_bind_param($cek, "i", $overtimeId);
        mysqli_stmt_execute($cek);
        $dataKompensasi = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
        $boleh = $dataKompensasi !== null;
        mysqli_stmt_close($cek);
        if (!$boleh) $pesan = "Kompensasi hanya dapat dimasukkan setelah approval lengkap.";
        else {
            if ($metode === "per_jam") {
                $tarif = round(((float) ($dataKompensasi["gaji_pokok"] ?? 0)) / 173, 2);
                $jumlah = round(((int) $dataKompensasi["total_menit"] / 60) * $tarif, 2);
            }
            $stmt = mysqli_prepare($conn, "INSERT INTO overtime_compensations (overtime_id, metode_perhitungan, tarif_per_jam, jumlah_upah, dimasukkan_oleh_pic) VALUES (?, ?, NULLIF(?, 0), ?, ?) ON DUPLICATE KEY UPDATE metode_perhitungan = VALUES(metode_perhitungan), tarif_per_jam = VALUES(tarif_per_jam), jumlah_upah = VALUES(jumlah_upah), dimasukkan_oleh_pic = VALUES(dimasukkan_oleh_pic)");
            $picId = (int) $_SESSION["user"]["id"];
            mysqli_stmt_bind_param($stmt, "isddi", $overtimeId, $metode, $tarif, $jumlah, $picId);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) >= 0) {
                $selesaiSql = "UPDATE overtime_reports SET status = 'selesai' WHERE id = ? AND status = 'disetujui'";
                if (rolePengguna() === "pic") $selesaiSql .= " AND department_id = ?";
                $selesaikan = mysqli_prepare($conn, $selesaiSql);
                if (rolePengguna() === "pic") mysqli_stmt_bind_param($selesaikan, "ii", $overtimeId, $departmentId);
                else mysqli_stmt_bind_param($selesaikan, "i", $overtimeId);
                mysqli_stmt_execute($selesaikan);
                mysqli_stmt_close($selesaikan);
                catatAktivitas($conn, "Memasukkan kompensasi dan menyelesaikan lembur ID " . $overtimeId . ".");
                header("Location: lembur.php");
                exit;
            }
            $pesan = "Kompensasi gagal disimpan.";
            mysqli_stmt_close($stmt);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "batalkan" && rolePengguna() === "pic") {
    $overtimeId = (int) ($_POST["overtime_id"] ?? 0);
    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesan = "Token keamanan tidak valid.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE overtime_reports SET status = 'ditolak' WHERE id = ? AND department_id = ? AND status IN ('draft', 'menunggu_koordinator', 'menunggu_manager', 'disetujui')");
        mysqli_stmt_bind_param($stmt, "ii", $overtimeId, $departmentId);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            catatAktivitas($conn, "Membatalkan laporan lembur ID " . $overtimeId . ".");
            header("Location: lembur.php");
            exit;
        }
        $pesan = "Laporan lembur tidak dapat dibatalkan.";
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "hapus" && in_array(rolePengguna(), ["admin", "superadmin"], true)) {
    $overtimeId = (int) ($_POST["overtime_id"] ?? 0);
    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesan = "Token keamanan tidak valid.";
    } elseif ($overtimeId <= 0) {
        $pesan = "Laporan lembur tidak valid.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM overtime_reports WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $overtimeId);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            catatAktivitas($conn, "Menghapus laporan lembur ID " . $overtimeId . ".");
            mysqli_stmt_close($stmt);
            header("Location: lembur.php");
            exit;
        }
        $pesan = "Laporan lembur tidak ditemukan atau gagal dihapus.";
        mysqli_stmt_close($stmt);
    }
}

$where = roleOperasional() ? "o.department_id = " . (int) ($departmentId ?? 0) : "1=1";
$sqlModeCheck = mysqli_query($conn, "SELECT @@SESSION.sql_mode AS sql_mode");
if ($sqlModeCheck) {
    $sqlModeRow = mysqli_fetch_assoc($sqlModeCheck);
    $sqlMode = (string) ($sqlModeRow["sql_mode"] ?? "");
    $sqlMode = implode(",", array_values(array_filter(explode(",", $sqlMode), static fn(string $mode): bool => strtoupper($mode) !== "ONLY_FULL_GROUP_BY")));
    mysqli_query($conn, "SET SESSION sql_mode = '" . mysqli_real_escape_string($conn, $sqlMode) . "'");
}
$laporan = mysqli_query($conn, "SELECT o.*, (SELECT u.role FROM overtime_approvals oa INNER JOIN users u ON u.id = oa.approver_user_id WHERE oa.overtime_id = o.id AND oa.status = 'approved' ORDER BY oa.decided_at DESC LIMIT 1) AS role_persetuju, k.emp_id, k.employee_name, k.position, k.department, pembuat.nama AS nama_pembuat, pg.gaji_pokok, oc.jumlah_upah, GROUP_CONCAT(CASE WHEN oa.tahap = 'koordinator' THEN CONCAT('Koordinator: ', IF(oa.status = 'approved', 'disetujui', IF(oa.status = 'rejected', 'ditolak', 'menunggu')), IF(TRIM(COALESCE(oa.catatan, '')) <> '', CONCAT(' — ', oa.catatan), '')) END SEPARATOR '||') AS catatan_koordinator, GROUP_CONCAT(CASE WHEN oa.tahap = 'manager' THEN CONCAT('Manager: ', IF(oa.status = 'approved', 'disetujui', IF(oa.status = 'rejected', 'ditolak', 'menunggu')), IF(TRIM(COALESCE(oa.catatan, '')) <> '', CONCAT(' — ', oa.catatan), '')) END SEPARATOR '||') AS catatan_manager FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN users pembuat ON pembuat.id = o.dibuat_oleh_pic LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id LEFT JOIN overtime_compensations oc ON oc.overtime_id = o.id LEFT JOIN overtime_approvals oa ON oa.overtime_id = o.id WHERE $where GROUP BY o.id ORDER BY o.created_at DESC");
$notifikasiLembur = mysqli_query($conn, "SELECT a.dibuat_pada, a.aktivitas, u.username FROM audit_aktivitas a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 12");
$detailLaporan = mysqli_query($conn, "SELECT o.id, o.deskripsi FROM overtime_reports o WHERE $where ORDER BY o.created_at DESC");
$gajiLembur = [];
$hasilGajiLembur = mysqli_query($conn, "SELECT o.id, COALESCE(pg.gaji_pokok, 0) AS gaji_pokok FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN users pembuat ON pembuat.id = o.dibuat_oleh_pic LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE $where");
if ($hasilGajiLembur) while ($itemGaji = mysqli_fetch_assoc($hasilGajiLembur)) $gajiLembur[(string) $itemGaji["id"]] = (float) $itemGaji["gaji_pokok"];

$dataKaryawanLembur = [];
$departemenPilihanLembur = ambilDepartemenPilihan($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);
$posisiPerDepartemenLembur = ambilPosisiPerDepartemen($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);

if ($bolehInputLembur) {
    $sqlKaryawanLembur = "SELECT id, emp_id, employee_name, position, department, department_id,
                                  TIME_FORMAT(shift_mulai, '%H:%i') AS shift_mulai,
                                  TIME_FORMAT(shift_selesai, '%H:%i') AS shift_selesai,
                                  shift_hari
                           FROM karyawan
                           WHERE department_id IS NOT NULL
                             AND TRIM(COALESCE(position, '')) <> ''
                             AND TRIM(COALESCE(department, '')) <> ''";
    $parameterDepartmentLembur = null;

    if (roleOperasional()) {
        $sqlKaryawanLembur .= " AND department_id = ?";
        $parameterDepartmentLembur = (int) ($departmentId ?? 0);
    }

    $sqlKaryawanLembur .= " ORDER BY department, position, employee_name";
    $stmtKaryawanLembur = mysqli_prepare($conn, $sqlKaryawanLembur);
    if ($parameterDepartmentLembur !== null) {
        mysqli_stmt_bind_param($stmtKaryawanLembur, "i", $parameterDepartmentLembur);
    }
    mysqli_stmt_execute($stmtKaryawanLembur);
    $hasilKaryawanLembur = mysqli_stmt_get_result($stmtKaryawanLembur);

    while ($item = mysqli_fetch_assoc($hasilKaryawanLembur)) {
        $dataKaryawanLembur[] = [
            "id" => (int) $item["id"],
            "emp_id" => (string) ($item["emp_id"] ?? ""),
            "nama" => (string) ($item["employee_name"] ?? ""),
            "posisi" => (string) ($item["position"] ?? ""),
            "departemen" => (string) ($item["department"] ?? ""),
            "department_id" => (int) $item["department_id"],
            "shift_mulai" => (string) ($item["shift_mulai"] ?? ""),
            "shift_selesai" => (string) ($item["shift_selesai"] ?? ""),
            "shift_hari" => (string) ($item["shift_hari"] ?? ""),
        ];
    }
    mysqli_stmt_close($stmtKaryawanLembur);
}
$judulHalaman = "Lembur";
$subjudulHalaman = "Input dan pemantauan laporan lembur.";
$halamanAktif = "lembur";
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<?php if ($pesan !== ""): ?><div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
<div class="overtime-notification-action"><a class="btn btn-primary" href="notifikasi.php">🔔 Buka Notifikasi</a><a class="btn export-excel-btn" href="export_lembur.php">Export Excel</a></div>
<section class="data-card overtime-notifications">
    <div class="data-card-header">
        <h2>Notifikasi Terbaru</h2>
        <p class="overtime-note">Persetujuan lembur dan perubahan data terbaru.</p>
    </div>
    <div class="notification-list"><?php if ($notifikasiLembur && mysqli_num_rows($notifikasiLembur) > 0): ?><?php while ($notif = mysqli_fetch_assoc($notifikasiLembur)): ?><div class="notification-item"><strong><?= htmlspecialchars($notif["username"] ?: "Sistem"); ?></strong><span><?= htmlspecialchars(labelAktivitas((string) $notif["aktivitas"])); ?></span><small><?= htmlspecialchars($notif["dibuat_pada"]); ?></small></div><?php endwhile; ?><?php else: ?><div class="notification-empty">Belum ada notifikasi.</div><?php endif; ?></div>
</section>
<section class="data-card overtime-details">
    <div class="data-card-header">
        <h2>Alasan Pengajuan</h2>
    </div>
    <div class="overtime-detail-list"><?php if ($detailLaporan && mysqli_num_rows($detailLaporan) > 0): ?><?php while ($detail = mysqli_fetch_assoc($detailLaporan)): ?><article class="overtime-detail-item"><strong>Lembur ID <?= (int) $detail["id"]; ?></strong>
            <p><b>Alasan pengajuan:</b> <?= nl2br(htmlspecialchars(trim((string) ($detail["deskripsi"] ?? "")) ?: "-")); ?></p>
        </article><?php endwhile; ?><?php else: ?><div class="notification-empty">Belum ada laporan lembur.</div><?php endif; ?></div>
</section>
<?php if ($bolehInputLembur): ?>
    <section class="form-card">
        <div class="form-card-header">
            <h2><?= $bolehAjukanLangsung ? "Tambah Catatan Lembur" : "Buat Laporan Lembur"; ?></h2>
            <p>Pilih departemen terlebih dahulu, kemudian pilih posisi dan karyawan yang sesuai.</p>
        </div>
        <div class="form-body">
            <form method="POST" class="overtime-entry-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">

                <div class="form-group">
                    <label for="lembur-department">Departemen</label>
                    <select id="lembur-department" name="department_id" class="overtime-department-select" required>
                        <option value="">Pilih departemen</option>
                        <?php foreach ($departemenPilihanLembur as $idDepartemen => $namaDepartemen): ?>
                            <option value="<?= (int) $idDepartemen; ?>" <?= $formLembur["department_id"] === (string) $idDepartemen ? "selected" : ""; ?>>
                                <?= htmlspecialchars($namaDepartemen); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="lembur-position">Posisi</label>
                    <select id="lembur-position" name="position" class="overtime-position-select" data-selected="<?= htmlspecialchars($formLembur["position"]); ?>" required disabled>
                        <option value="">Pilih departemen terlebih dahulu</option>
                    </select>
                </div>

                <div class="form-group overtime-employee-group">
                    <div class="overtime-employee-layout">
                        <div>
                            <label for="lembur-karyawan">Karyawan</label>
                            <select id="lembur-karyawan" name="karyawan_id" class="overtime-employee-choice" data-selected="<?= (int) $formLembur["karyawan_id"]; ?>" required disabled>
                                <option value="">Pilih departemen dan posisi terlebih dahulu</option>
                            </select>
                        </div>
                        <div class="overtime-schedule-time-fields" aria-label="Jadwal kerja karyawan terpilih">
                            <div><label for="lembur-jam-masuk">Jam masuk</label><input id="lembur-jam-masuk" type="time" readonly value=""></div>
                            <div><label for="lembur-jam-pulang">Jam pulang</label><input id="lembur-jam-pulang" type="time" readonly value=""></div>
                        </div>
                    </div>
                    <p class="overtime-workdays-info"><strong>Hari kerja:</strong> <span id="lembur-hari-kerja">-</span></p>
                </div>

                <div class="form-group">
                    <label>Mulai</label>
                    <div class="overtime-date-time">
                        <div><label for="lembur-tanggal-mulai">Tanggal</label><input id="lembur-tanggal-mulai" type="date" required></div>
                        <div><label for="lembur-jam-mulai">Jam</label><input id="lembur-jam-mulai" type="time" step="60" required></div>
                    </div>
                    <input id="lembur-mulai" type="hidden" name="mulai_at" value="<?= htmlspecialchars($formLembur["mulai_at"]); ?>">
                </div>

                <div class="form-group">
                    <label>Selesai</label>
                    <div class="overtime-date-time">
                        <div><label for="lembur-tanggal-selesai">Tanggal</label><input id="lembur-tanggal-selesai" type="date" required></div>
                        <div><label for="lembur-jam-selesai">Jam</label><input id="lembur-jam-selesai" type="time" step="60" required></div>
                    </div>
                    <input id="lembur-selesai" type="hidden" name="selesai_at" value="<?= htmlspecialchars($formLembur["selesai_at"]); ?>">
                </div>

                <div class="overtime-schedule-info"><p id="lembur-jadwal-note" class="overtime-note">Pilih karyawan dan waktu mulai untuk melihat batas waktu lembur.</p></div>

                <div class="form-group">
                    <label for="lembur-deskripsi"><?= $bolehAjukanLangsung ? "Catatan Lembur" : "Deskripsi"; ?></label>
                    <textarea id="lembur-deskripsi" name="deskripsi" rows="3" <?= $bolehAjukanLangsung ? "required" : ""; ?>><?= htmlspecialchars($formLembur["deskripsi"]); ?></textarea>
                </div>

                <button class="btn btn-success" type="submit"><?= $bolehAjukanLangsung ? "Ajukan Lembur" : "Simpan Draft"; ?></button>
            </form>
        </div>
    </section>
    <script>
        (() => {
            const employees = <?= json_encode($dataKaryawanLembur, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const positionsByDepartment = <?= json_encode($posisiPerDepartemenLembur, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const department = document.getElementById("lembur-department");
            const position = document.getElementById("lembur-position");
            const employee = document.getElementById("lembur-karyawan");
            const mulaiLembur = document.getElementById("lembur-mulai");
            const tanggalMulaiLembur = document.getElementById("lembur-tanggal-mulai");
            const jamMulaiLembur = document.getElementById("lembur-jam-mulai");
            const selesaiLembur = document.getElementById("lembur-selesai");
            const tanggalSelesaiLembur = document.getElementById("lembur-tanggal-selesai");
            const jamSelesaiLembur = document.getElementById("lembur-jam-selesai");
            const catatanJadwal = document.getElementById("lembur-jadwal-note");
            const jamMasukKaryawan = document.getElementById("lembur-jam-masuk");
            const jamPulangKaryawan = document.getElementById("lembur-jam-pulang");
            const hariKerjaKaryawan = document.getElementById("lembur-hari-kerja");

            const createOption = (value, label) => {
                const option = document.createElement("option");
                option.value = String(value);
                option.textContent = label;
                return option;
            };

            const namaHari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            const pad = value => String(value).padStart(2, "0");
            const waktuInput = value => `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}T${pad(value.getHours())}:${pad(value.getMinutes())}`;
            const waktuTampil = value => `${pad(value.getDate())}-${pad(value.getMonth() + 1)}-${value.getFullYear()} ${pad(value.getHours())}:${pad(value.getMinutes())}`;
            const tanggalTengahMalam = value => new Date(value.getFullYear(), value.getMonth(), value.getDate());
            const karyawanTerpilih = () => employees.find(item => String(item.id) === employee.value);
            const sinkronkanMulaiLembur = () => {
                mulaiLembur.value = tanggalMulaiLembur.value && jamMulaiLembur.value
                    ? `${tanggalMulaiLembur.value}T${jamMulaiLembur.value}`
                    : "";
            };
            const isiMulaiLembur = value => {
                tanggalMulaiLembur.value = `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
                jamMulaiLembur.value = `${pad(value.getHours())}:${pad(value.getMinutes())}`;
                sinkronkanMulaiLembur();
            };
            const sinkronkanSelesaiLembur = () => {
                selesaiLembur.value = tanggalSelesaiLembur.value && jamSelesaiLembur.value
                    ? `${tanggalSelesaiLembur.value}T${jamSelesaiLembur.value}`
                    : "";
            };
            const isiSelesaiLembur = value => {
                tanggalSelesaiLembur.value = `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
                jamSelesaiLembur.value = `${pad(value.getHours())}:${pad(value.getMinutes())}`;
                sinkronkanSelesaiLembur();
            };
            if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(mulaiLembur.value)) {
                const [tanggalAwal, jamAwal] = mulaiLembur.value.split("T");
                tanggalMulaiLembur.value = tanggalAwal;
                jamMulaiLembur.value = jamAwal;
            }
            if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(selesaiLembur.value)) {
                const [tanggalAkhir, jamAkhir] = selesaiLembur.value.split("T");
                tanggalSelesaiLembur.value = tanggalAkhir;
                jamSelesaiLembur.value = jamAkhir;
            }
            const tambahHari = (value, jumlah) => {
                const hasil = new Date(value);
                hasil.setDate(hasil.getDate() + jumlah);
                return hasil;
            };
            const jamPadaTanggal = (tanggal, jam) => {
                const cocok = /^(\d{2}):(\d{2})$/.exec(String(jam || ""));
                if (!cocok) return null;
                const hasil = new Date(tanggal);
                hasil.setHours(Number(cocok[1]), Number(cocok[2]), 0, 0);
                return hasil;
            };
            const hariKerja = item => item.shift_hari === "Senin-Jumat"
                ? ["Senin", "Selasa", "Rabu", "Kamis", "Jumat"]
                : String(item.shift_hari || "").split(/,\s*/).filter(Boolean);
            const perbaruiRingkasanJadwal = () => {
                const karyawan = karyawanTerpilih();
                jamMasukKaryawan.value = karyawan?.shift_mulai || "";
                jamPulangKaryawan.value = karyawan?.shift_selesai || "";
                hariKerjaKaryawan.textContent = karyawan ? hariKerja(karyawan).join(", ") : "-";
            };
            const aturJamMulaiOtomatis = () => {
                const karyawan = karyawanTerpilih();
                if (!karyawan || !tanggalMulaiLembur.value || !karyawan.shift_selesai) return;
                const tanggal = new Date(`${tanggalMulaiLembur.value}T00:00`);
                if (!Number.isNaN(tanggal.getTime()) && hariKerja(karyawan).includes(namaHari[tanggal.getDay()])) {
                    jamMulaiLembur.value = karyawan.shift_selesai;
                }
                sinkronkanMulaiLembur();
            };
            const jadwalLembur = (item, mulai) => {
                const hari = hariKerja(item);
                if (!hari.length || !item.shift_mulai || !item.shift_selesai) return null;
                const hariKerjaHariIni = hari.includes(namaHari[mulai.getDay()]);

                for (const mundur of [0, 1]) {
                    const tanggalKerja = tambahHari(tanggalTengahMalam(mulai), -mundur);
                    if (!hari.includes(namaHari[tanggalKerja.getDay()])) continue;
                    const mulaiShift = jamPadaTanggal(tanggalKerja, item.shift_mulai);
                    let selesaiShift = jamPadaTanggal(tanggalKerja, item.shift_selesai);
                    if (!mulaiShift || !selesaiShift) return null;
                    if (selesaiShift <= mulaiShift) selesaiShift = tambahHari(selesaiShift, 1);
                    if (mulai >= mulaiShift && mulai < selesaiShift) {
                        return { hariKerja: hariKerjaHariIni, sedangBekerja: true, mulaiShift, selesaiShift };
                    }
                }
                if (!hariKerjaHariIni) return { hariKerja: false, sedangBekerja: false };

                let akhirShiftTerakhir = null;
                const tanggalMulai = tanggalTengahMalam(mulai);
                for (let mundur = 0; mundur <= 14; mundur++) {
                    const tanggalKerja = tambahHari(tanggalMulai, -mundur);
                    if (!hari.includes(namaHari[tanggalKerja.getDay()])) continue;
                    const mulaiShift = jamPadaTanggal(tanggalKerja, item.shift_mulai);
                    let selesaiShift = jamPadaTanggal(tanggalKerja, item.shift_selesai);
                    if (!mulaiShift || !selesaiShift) return null;
                    if (selesaiShift <= mulaiShift) selesaiShift = tambahHari(selesaiShift, 1);
                    if (selesaiShift <= mulai) {
                        akhirShiftTerakhir = selesaiShift;
                        break;
                    }
                }
                if (!akhirShiftTerakhir) return null;

                const tanggalBerikutnya = tanggalTengahMalam(akhirShiftTerakhir);
                for (let maju = 0; maju <= 14; maju++) {
                    const tanggalKerja = tambahHari(tanggalBerikutnya, maju);
                    if (!hari.includes(namaHari[tanggalKerja.getDay()])) continue;
                    const mulaiKerjaBerikutnya = jamPadaTanggal(tanggalKerja, item.shift_mulai);
                    if (mulaiKerjaBerikutnya && mulaiKerjaBerikutnya > akhirShiftTerakhir) {
                        return { hariKerja: true, sedangBekerja: false, akhirShiftTerakhir, mulaiKerjaBerikutnya };
                    }
                }
                return null;
            };
            const perbaruiBatasLembur = () => {
                const karyawan = karyawanTerpilih();
                const mulai = mulaiLembur.value ? new Date(mulaiLembur.value) : null;
                const aktifkanSelesai = (aktif) => {
                    tanggalSelesaiLembur.disabled = !aktif;
                    jamSelesaiLembur.disabled = !aktif;
                };
                const kosongkanSelesai = () => {
                    tanggalSelesaiLembur.value = "";
                    jamSelesaiLembur.value = "";
                    sinkronkanSelesaiLembur();
                };

                if (!karyawan) {
                    aktifkanSelesai(false);
                    catatanJadwal.textContent = "Pilih karyawan dan waktu mulai untuk melihat batas waktu lembur.";
                    return;
                }
                if (!karyawan.shift_mulai || !karyawan.shift_selesai || !hariKerja(karyawan).length) {
                    aktifkanSelesai(false);
                    catatanJadwal.textContent = "Karyawan ini belum memiliki jadwal kerja lengkap.";
                    return;
                }
                if (!mulai || Number.isNaN(mulai.getTime())) {
                    aktifkanSelesai(false);
                    catatanJadwal.textContent = "Pilih waktu mulai untuk menghitung batas waktu lembur.";
                    return;
                }

                const batas = jadwalLembur(karyawan, mulai);
                if (!batas) {
                    aktifkanSelesai(false);
                    catatanJadwal.textContent = "Jadwal kerja karyawan tidak dapat digunakan untuk membatasi lembur.";
                    return;
                }
                if (batas.sedangBekerja) {
                    isiMulaiLembur(batas.selesaiShift);
                    return perbaruiBatasLembur();
                }
                const batasiSelesai = batasAkhir => {
                    aktifkanSelesai(true);
                    const tanggalMulai = waktuInput(mulai).slice(0, 10);
                    const tanggalBatas = batasAkhir ? waktuInput(batasAkhir).slice(0, 10) : "";
                    tanggalSelesaiLembur.min = tanggalMulai;
                    if (tanggalBatas) tanggalSelesaiLembur.max = tanggalBatas;
                    else tanggalSelesaiLembur.removeAttribute("max");
                    jamSelesaiLembur.removeAttribute("min");
                    jamSelesaiLembur.removeAttribute("max");
                    if (tanggalSelesaiLembur.value === tanggalMulai) jamSelesaiLembur.min = waktuInput(mulai).slice(11);
                    if (tanggalBatas && tanggalSelesaiLembur.value === tanggalBatas) jamSelesaiLembur.max = waktuInput(batasAkhir).slice(11);

                    let selesai = selesaiLembur.value ? new Date(selesaiLembur.value) : null;
                    if (!selesai || Number.isNaN(selesai.getTime())) return;
                    if (selesai <= mulai || (batasAkhir && selesai > batasAkhir)) {
                        kosongkanSelesai();
                        return;
                    }
                    const aturanSelesai = jadwalLembur(karyawan, selesai);
                    if (aturanSelesai?.sedangBekerja && selesai.getTime() !== aturanSelesai.mulaiShift.getTime()) {
                        isiSelesaiLembur(aturanSelesai.mulaiShift);
                        selesai = new Date(selesaiLembur.value);
                    }
                    if (selesai <= mulai || (batasAkhir && selesai > batasAkhir)) kosongkanSelesai();
                };
                if (!batas.hariKerja) {
                    batasiSelesai(null);
                    catatanJadwal.textContent = "Hari ini bukan hari kerja karyawan; waktu lembur dapat dimulai dan berakhir kapan saja.";
                    return;
                }

                batasiSelesai(batas.mulaiKerjaBerikutnya);
                catatanJadwal.textContent = `Waktu kerja tidak dapat dipilih. Waktu lembur berakhir paling lambat ${waktuTampil(batas.mulaiKerjaBerikutnya)}.`;
            };

            const updateEmployees = (restoreSelection = false) => {
                const selectedId = restoreSelection ? employee.dataset.selected : "";
                employee.replaceChildren();

                if (!department.value || !position.value) {
                    employee.append(createOption("", "Pilih departemen dan posisi terlebih dahulu"));
                    employee.disabled = true;
                    employee.dataset.selected = "";
                    perbaruiRingkasanJadwal();
                    perbaruiBatasLembur();
                    return;
                }

                const matching = employees.filter(item =>
                    String(item.department_id) === department.value && item.posisi === position.value
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
                perbaruiRingkasanJadwal();
                perbaruiBatasLembur();
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

                const positions = [...new Set(positionsByDepartment[department.value] || [])].sort((first, second) => first.localeCompare(second, "id", {
                    sensitivity: "base"
                }));

                position.append(createOption("", positions.length ? "Pilih posisi" : "Tidak ada posisi pada departemen ini"));
                positions.forEach(item => position.append(createOption(item, item)));
                position.disabled = positions.length === 0;

                if (positions.includes(selectedPosition)) {
                    position.value = selectedPosition;
                }
                position.dataset.selected = "";
                updateEmployees(restoreSelection);
            };

            department.addEventListener("change", () => updatePositions(false));
            position.addEventListener("change", () => updateEmployees(false));
            employee.addEventListener("change", () => {
                perbaruiRingkasanJadwal();
                aturJamMulaiOtomatis();
                perbaruiBatasLembur();
            });
            tanggalMulaiLembur.addEventListener("change", () => {
                aturJamMulaiOtomatis();
                perbaruiBatasLembur();
            });
            jamMulaiLembur.addEventListener("change", () => {
                sinkronkanMulaiLembur();
                perbaruiBatasLembur();
            });
            tanggalSelesaiLembur.addEventListener("change", () => {
                sinkronkanSelesaiLembur();
                perbaruiBatasLembur();
            });
            jamSelesaiLembur.addEventListener("change", () => {
                sinkronkanSelesaiLembur();
                perbaruiBatasLembur();
            });
            updatePositions(true);
            perbaruiRingkasanJadwal();
            perbaruiBatasLembur();
        })();
    </script>
<?php endif; ?>
<section class="data-card">
    <div class="data-card-header">
        <h2>Daftar Laporan Lembur</h2>
    </div>
    <div class="table-wrapper">
        <table class="overtime-report-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Karyawan</th>
                    <th>Posisi</th>
                    <th>Departemen</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Total Menit</th>
                    <th>Status</th>
                    <th>Dibuat Oleh</th>
                    <th>Upah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody><?php while ($row = mysqli_fetch_assoc($laporan)): ?><tr data-overtime-status="<?= htmlspecialchars((string) $row["status"]); ?>">
                        <td class="overtime-id"><?= (int) $row["id"]; ?></td>
                        <td><?= htmlspecialchars($row["emp_id"] . " - " . $row["employee_name"]); ?></td>
                        <td><?= htmlspecialchars(trim((string) ($row["position"] ?? "")) ?: "-"); ?></td>
                        <td><?= htmlspecialchars(trim((string) ($row["department"] ?? "")) ?: "-"); ?></td>
                        <td><?= htmlspecialchars($row["mulai_at"]); ?></td>
                        <td><?= htmlspecialchars($row["selesai_at"]); ?></td>
                        <td class="overtime-total-minutes"><?= number_format((int) $row["total_menit"], 0, ",", "."); ?></td>
                        <td class="overtime-status"><span class="status-badge status-<?= htmlspecialchars($row["status"]); ?>"><?php $statusLembur = match ((string) $row["status"]) {
                                                                                                                                    "menunggu_koordinator" => "Menunggu Koordinator",
                                                                                                                                    "menunggu_manager" => "Menunggu Manager",
                                                                                                                                    "disetujui" => "Disetujui " . labelRole((string) ($row["role_persetuju"] ?? "manager")),
                                                                                                                                    "ditolak" => "Ditolak",
                                                                                                                                    "selesai" => "Disetujui " . labelRole((string) ($row["role_persetuju"] ?? "manager")),
                                                                                                                                    default => ucwords(str_replace("_", " ", (string) $row["status"]))
                                                                                                                                }; ?><?= htmlspecialchars($statusLembur); ?></span></td>
                        <td><?= htmlspecialchars($row["nama_pembuat"] ?? "-"); ?></td>
                        <td><?= $row["jumlah_upah"] === null ? "-" : "Rp " . number_format((float) $row["jumlah_upah"], 0, ",", "."); ?></td>
                        <td class="overtime-actions"><?php if (rolePengguna() === "pic" && $row["status"] === "draft"): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="kirim"><input type="hidden" name="overtime_id" value="<?= (int) $row["id"]; ?>"><button class="btn btn-primary" type="submit">Kirim</button></form><?php elseif (rolePengguna() === "pic" && $row["status"] === "disetujui"): ?><div class="overtime-compensation-actions"><form method="POST" class="compensation-form"><input method="POST" type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="kompensasi"><input type="hidden" name="overtime_id" value="<?= (int) $row["id"]; ?>"><input type="hidden" name="metode_perhitungan" value="per_jam"><input name="tarif_per_jam" class="overtime-rate" type="hidden"><input name="jumlah_upah" class="overtime-total" type="hidden">
                                    <p class="overtime-compensation-summary"><span class="overtime-rate-label"></span><span aria-hidden="true"> | </span><span class="overtime-total-label"></span></p><div class="overtime-compensation-buttons"><button class="btn btn-success" type="submit">Simpan Upah</button></div>
                                </form></div><?php elseif ((rolePengguna() === "koordinator" && $row["status"] === "menunggu_koordinator") || (punyaRole("manager") && $row["status"] === "menunggu_manager")): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="keputusan"><input type="hidden" name="overtime_id" value="<?= (int) $row["id"]; ?>"><input name="catatan" placeholder="Catatan penolakan"><button class="btn btn-success" name="keputusan" value="approved">Setujui</button><button class="btn btn-danger" name="keputusan" value="rejected">Tolak</button></form><?php else: ?>-<?php endif; ?></td>
                    </tr><?php endwhile; ?></tbody>
        </table>
    </div>
</section>
<script>
    const gajiLembur = <?= json_encode($gajiLembur, JSON_UNESCAPED_UNICODE); ?>;
    const formatRupiahLembur = value => `Rp${new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)}`;
    const isiRingkasanKompensasi = form => {
        const rate = form.querySelector('.overtime-rate');
        const total = form.querySelector('.overtime-total');
        const minutes = Number(form.closest('tr')?.querySelector('.overtime-total-minutes')?.textContent.replace(/\D/g, '')) || 0;
        const overtimeId = form.querySelector('[name="overtime_id"]').value;
        const hourly = Number(gajiLembur[overtimeId] || 0) / 173;
        const totalValue = minutes / 60 * hourly;
        rate.value = hourly.toFixed(2);
        total.value = totalValue.toFixed(2);
        form.querySelector('.overtime-rate-label').textContent = `Tarif/jam: ${formatRupiahLembur(hourly)}`;
        form.querySelector('.overtime-total-label').textContent = `Total upah lembur: ${formatRupiahLembur(totalValue)}`;
    };
    document.querySelectorAll('.compensation-form').forEach(isiRingkasanKompensasi);
    document.querySelectorAll('.overtime-report-table tbody tr').forEach(row => {
        const status = row.querySelector('.overtime-status')?.textContent.trim();
        const action = row.querySelector('.overtime-actions');
        if (action && ['draft', 'menunggu_koordinator', 'menunggu_manager', 'disetujui'].includes(status)) {
            const id = row.querySelector('.overtime-id')?.textContent.trim();
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'inline-block';
            form.style.marginLeft = '6px';
            form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="batalkan"><input type="hidden" name="overtime_id" value="' + id + '"><button class="btn btn-danger" type="submit" onclick="return confirm(\'Batalkan laporan lembur ini?\')">Batalkan</button>';
            action.appendChild(form);
        }
    });
</script>
<?php if (in_array(rolePengguna(), ["admin", "superadmin"], true)): ?>
    <script>
        document.querySelectorAll('.overtime-report-table tbody tr').forEach(row => {
            if (row.dataset.overtimeStatus !== 'disetujui') return;
            const action = row.querySelector('.overtime-actions');
            if (!action || action.querySelector('.compensation-form')) return;
            const id = row.querySelector('.overtime-id')?.textContent.trim();
            const form = document.createElement('form');
            form.method = 'POST';
            form.className = 'compensation-form';
            form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="kompensasi"><input type="hidden" name="overtime_id" value="' + id + '"><input type="hidden" name="metode_perhitungan" value="per_jam"><input name="tarif_per_jam" class="overtime-rate" type="hidden"><input name="jumlah_upah" class="overtime-total" type="hidden"><p class="overtime-compensation-summary"><span class="overtime-rate-label"></span><span aria-hidden="true"> | </span><span class="overtime-total-label"></span></p><div class="overtime-compensation-buttons"><button class="btn btn-success" type="submit">Simpan Upah</button></div>';
            const layout = document.createElement('div');
            layout.className = 'overtime-compensation-actions';
            layout.appendChild(form);
            action.textContent = '';
            action.appendChild(layout);
            isiRingkasanKompensasi(form);
        });
    </script>
<?php endif; ?>
<?php if (punyaRole("koordinator", "manager", "admin", "superadmin")): ?>
    <script>
        document.querySelectorAll('input[name="aksi"][value="batalkan"]').forEach(input => input.form?.remove());
        document.querySelectorAll('button[name="keputusan"][value="approved"]').forEach(button => {
            button.textContent = 'Setuju';
        });
    </script>
<?php endif; ?>
<?php if ($bolehApprovalPusat): ?>
    <script>
        document.querySelectorAll('.overtime-report-table tbody tr').forEach(row => {
            const status = row.dataset.overtimeStatus;
            if (!['menunggu_koordinator', 'menunggu_manager'].includes(status)) return;
            const action = row.querySelector('.overtime-actions');
            const id = row.querySelector('.overtime-id')?.textContent.trim();
            action.textContent = '';
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="keputusan_pusat"><input type="hidden" name="overtime_id" value="' + id + '"><input name="catatan" placeholder="Catatan persetujuan/penolakan"><button class="btn btn-success" name="keputusan" value="approved">Setuju</button><button class="btn btn-danger" name="keputusan" value="rejected">Tolak</button>';
            action.appendChild(form);
        });
    </script>
<?php endif; ?>
<?php if (in_array(rolePengguna(), ["admin", "superadmin"], true)): ?>
    <script>
        document.querySelectorAll('.overtime-report-table tbody tr').forEach(row => {
            const action = row.querySelector('.overtime-actions');
            const id = row.querySelector('.overtime-id')?.textContent.trim();
            if (!action || !id) return;
            if (action.textContent.trim() === '-') action.textContent = '';
            const form = document.createElement('form');
            form.method = 'POST';
            form.className = 'delete-overtime-form';
            form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="overtime_id" value="' + id + '"><button class="btn btn-danger" type="submit">Hapus</button>';
            form.addEventListener('submit', event => {
                if (!confirm('Hapus laporan lembur ID ' + id + '? Data approval dan upah lembur terkait juga akan dihapus.')) event.preventDefault();
            });
            const compensationButtons = action.querySelector('.overtime-compensation-buttons');
            if (compensationButtons) compensationButtons.prepend(form);
            else action.appendChild(form);
        });
    </script>
<?php endif; ?>
<style>
    .data-card table td:last-child {
        min-width: 190px;
    }

    .data-card table td:last-child>form {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        margin: 0 4px 4px 0;
        vertical-align: middle;
    }

    .data-card table td:last-child button {
        white-space: nowrap;
    }
</style>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
