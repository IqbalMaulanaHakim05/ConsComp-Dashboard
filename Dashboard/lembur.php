<?php
declare(strict_types=1);
require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";
wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
$pesan = "";
$departmentId = departmentIdPengguna();
$roleSaatIni = rolePengguna();
$bolehInputLembur = in_array($roleSaatIni, ["pic", "admin", "superadmin"], true);
$bolehApprovalPusat = in_array($roleSaatIni, ["admin", "superadmin"], true);

if ($_SERVER["REQUEST_METHOD"] === "POST" && $bolehInputLembur && !isset($_POST["aksi"])) {
    $karyawanId = (int) ($_POST["karyawan_id"] ?? 0);
    $mulai = trim((string) ($_POST["mulai_at"] ?? ""));
    $selesai = trim((string) ($_POST["selesai_at"] ?? ""));
    $deskripsi = trim((string) ($_POST["deskripsi"] ?? ""));
    if (!csrfValid($_POST["csrf_token"] ?? null)) $pesan = "Token keamanan tidak valid.";
    else {
        $awal = DateTime::createFromFormat("Y-m-d\\TH:i", $mulai);
        $akhir = DateTime::createFromFormat("Y-m-d\\TH:i", $selesai);
        $stmt = mysqli_prepare($conn, "SELECT department_id FROM karyawan WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $karyawanId); mysqli_stmt_execute($stmt);
        $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
        if (!$awal || !$akhir || $akhir <= $awal) $pesan = "Waktu selesai harus lebih besar dari waktu mulai.";
        elseif (!$karyawan || ($departmentId !== null && (int) $karyawan["department_id"] !== $departmentId)) $pesan = "Karyawan tidak berada dalam cakupan departemen Anda.";
        else {
            $menit = (int) (($akhir->getTimestamp() - $awal->getTimestamp()) / 60);
            $stmt = mysqli_prepare($conn, "INSERT INTO overtime_reports (karyawan_id, department_id, dibuat_oleh_pic, mulai_at, selesai_at, total_menit, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $picId = (int) $_SESSION["user"]["id"];
            $departmentLaporan = (int) $karyawan["department_id"];
            $mulaiDb = $awal->format("Y-m-d H:i:s"); $selesaiDb = $akhir->format("Y-m-d H:i:s");
            mysqli_stmt_bind_param($stmt, "iiissis", $karyawanId, $departmentLaporan, $picId, $mulaiDb, $selesaiDb, $menit, $deskripsi);
            if (mysqli_stmt_execute($stmt)) {
                $laporanBaruId = (int) mysqli_insert_id($conn);
                if ($bolehApprovalPusat) {
                    mysqli_query($conn, "UPDATE overtime_reports SET status = 'menunggu_koordinator', submitted_at = CURRENT_TIMESTAMP WHERE id = " . $laporanBaruId);
                    mysqli_query($conn, "INSERT IGNORE INTO overtime_approvals (overtime_id, tahap) VALUES (" . $laporanBaruId . ", 'koordinator'), (" . $laporanBaruId . ", 'manager')");
                }
                catatAktivitas($conn, "Membuat laporan lembur karyawan ID " . $karyawanId . "."); header("Location: lembur.php"); exit;
            }
            $pesan = "Laporan lembur gagal disimpan."; mysqli_stmt_close($stmt);
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
        mysqli_stmt_bind_param($stmt, "si", $statusAkhir, $overtimeId); mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $userId = (int) $_SESSION["user"]["id"];
            $statusApproval = $keputusan;
            $approval = mysqli_prepare($conn, "UPDATE overtime_approvals SET status = ?, approver_user_id = ?, catatan = ?, decided_at = CURRENT_TIMESTAMP WHERE overtime_id = ? AND status = 'pending'");
            mysqli_stmt_bind_param($approval, "sisi", $statusApproval, $userId, $catatan, $overtimeId); mysqli_stmt_execute($approval); mysqli_stmt_close($approval);
            catatAktivitas($conn, "Memproses persetujuan pusat lembur ID " . $overtimeId . "."); header("Location: lembur.php"); exit;
        }
        $pesan = "Laporan tidak dapat diproses."; mysqli_stmt_close($stmt);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && in_array(($_POST["aksi"] ?? ""), ["kirim", "keputusan"], true)) {
    $overtimeId = (int) ($_POST["overtime_id"] ?? 0);
    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesan = "Token keamanan tidak valid.";
    } elseif (($_POST["aksi"] ?? "") === "kirim" && rolePengguna() === "pic") {
        $stmt = mysqli_prepare($conn, "UPDATE overtime_reports SET status = 'menunggu_koordinator', submitted_at = CURRENT_TIMESTAMP WHERE id = ? AND department_id = ? AND status = 'draft'");
        mysqli_stmt_bind_param($stmt, "ii", $overtimeId, $departmentId); mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) { mysqli_query($conn, "INSERT IGNORE INTO overtime_approvals (overtime_id, tahap) VALUES (" . $overtimeId . ", 'koordinator'), (" . $overtimeId . ", 'manager')"); header("Location: lembur.php"); exit; }
        $pesan = "Draft tidak dapat dikirim."; mysqli_stmt_close($stmt);
    } elseif (($_POST["aksi"] ?? "") === "keputusan" && in_array(rolePengguna(), ["koordinator", "manager"], true)) {
        $keputusan = (string) ($_POST["keputusan"] ?? ""); $catatan = trim((string) ($_POST["catatan"] ?? ""));
        $tahap = rolePengguna() === "koordinator" ? "koordinator" : "manager";
        if (!in_array($keputusan, ["approved", "rejected"], true) || ($keputusan === "rejected" && $catatan === "")) $pesan = "Keputusan tidak valid atau alasan penolakan belum diisi.";
        else {
            $bolehProses = true;
            if ($tahap === "manager") {
                $cek = mysqli_prepare($conn, "SELECT 1 FROM overtime_approvals WHERE overtime_id = ? AND tahap = 'koordinator' AND status = 'approved' LIMIT 1");
                mysqli_stmt_bind_param($cek, "i", $overtimeId); mysqli_stmt_execute($cek);
                $bolehProses = mysqli_fetch_assoc(mysqli_stmt_get_result($cek)) !== null; mysqli_stmt_close($cek);
            }
            if (!$bolehProses) {
                $pesan = "Approval Koordinator harus selesai sebelum Manager memproses laporan.";
            } else {
            $stmt = mysqli_prepare($conn, "UPDATE overtime_approvals a INNER JOIN overtime_reports o ON o.id = a.overtime_id SET a.status = ?, a.approver_user_id = ?, a.catatan = ?, a.decided_at = CURRENT_TIMESTAMP WHERE a.overtime_id = ? AND a.tahap = ? AND o.department_id = ? AND a.status = 'pending'");
            $userId = (int) $_SESSION["user"]["id"];
            mysqli_stmt_bind_param($stmt, "sisisi", $keputusan, $userId, $catatan, $overtimeId, $tahap, $departmentId); mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $status = $keputusan === "rejected" ? "ditolak" : ($tahap === "koordinator" ? "menunggu_manager" : "disetujui");
                mysqli_query($conn, "UPDATE overtime_reports SET status = '" . $status . "' WHERE id = " . $overtimeId);
                catatAktivitas($conn, "Memproses approval lembur ID " . $overtimeId . " pada tahap " . $tahap . "."); header("Location: lembur.php"); exit;
            }
            $pesan = "Laporan tidak dapat diproses."; mysqli_stmt_close($stmt);
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "kompensasi" && rolePengguna() === "pic") {
    $overtimeId = (int) ($_POST["overtime_id"] ?? 0);
    $metode = "per_jam";
    $tarif = (float) ($_POST["tarif_per_jam"] ?? 0);
    $jumlah = (float) ($_POST["jumlah_upah"] ?? -1);
    if (!csrfValid($_POST["csrf_token"] ?? null) || !in_array($metode, ["per_jam", "nominal_final"], true) || $jumlah < 0 || $tarif < 0) {
        $pesan = "Data kompensasi tidak valid.";
    } else {
        $cek = mysqli_prepare($conn, "SELECT o.id, o.total_menit, pg.gaji_pokok FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE o.id = ? AND o.department_id = ? AND o.status = 'disetujui' LIMIT 1");
        mysqli_stmt_bind_param($cek, "ii", $overtimeId, $departmentId); mysqli_stmt_execute($cek);
        $dataKompensasi = mysqli_fetch_assoc(mysqli_stmt_get_result($cek)); $boleh = $dataKompensasi !== null; mysqli_stmt_close($cek);
        if (!$boleh) $pesan = "Kompensasi hanya dapat dimasukkan setelah approval lengkap.";
        else {
            if ($metode === "per_jam") {
                $tarif = round(((float) ($dataKompensasi["gaji_pokok"] ?? 0)) / 173, 2);
                $jumlah = round(((int) $dataKompensasi["total_menit"] / 60) * $tarif, 2);
            }
            $stmt = mysqli_prepare($conn, "INSERT INTO overtime_compensations (overtime_id, metode_perhitungan, tarif_per_jam, jumlah_upah, dimasukkan_oleh_pic) VALUES (?, ?, NULLIF(?, 0), ?, ?) ON DUPLICATE KEY UPDATE metode_perhitungan = VALUES(metode_perhitungan), tarif_per_jam = VALUES(tarif_per_jam), jumlah_upah = VALUES(jumlah_upah), dimasukkan_oleh_pic = VALUES(dimasukkan_oleh_pic)");
            $picId = (int) $_SESSION["user"]["id"];
            mysqli_stmt_bind_param($stmt, "isddi", $overtimeId, $metode, $tarif, $jumlah, $picId); mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) >= 0) {
                $selesaikan = mysqli_prepare($conn, "UPDATE overtime_reports SET status = 'selesai' WHERE id = ? AND department_id = ? AND status = 'disetujui'");
                mysqli_stmt_bind_param($selesaikan, "ii", $overtimeId, $departmentId);
                mysqli_stmt_execute($selesaikan);
                mysqli_stmt_close($selesaikan);
                catatAktivitas($conn, "Memasukkan kompensasi dan menyelesaikan lembur ID " . $overtimeId . ".");
                header("Location: lembur.php"); exit;
            }
            $pesan = "Kompensasi gagal disimpan."; mysqli_stmt_close($stmt);
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
            header("Location: lembur.php"); exit;
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
            header("Location: lembur.php"); exit;
        }
        $pesan = "Laporan lembur tidak ditemukan atau gagal dihapus.";
        mysqli_stmt_close($stmt);
    }
}

$where = roleOperasional() ? "o.department_id = " . (int) ($departmentId ?? 0) : "1=1";
$laporan = mysqli_query($conn, "SELECT o.*, k.emp_id, k.employee_name, pg.gaji_pokok, oc.jumlah_upah FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id LEFT JOIN overtime_compensations oc ON oc.overtime_id = o.id WHERE $where ORDER BY o.created_at DESC");
$gajiLembur = [];
$hasilGajiLembur = mysqli_query($conn, "SELECT o.id, COALESCE(pg.gaji_pokok, 0) AS gaji_pokok FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE $where");
if ($hasilGajiLembur) while ($itemGaji = mysqli_fetch_assoc($hasilGajiLembur)) $gajiLembur[(string) $itemGaji["id"]] = (float) $itemGaji["gaji_pokok"];
$karyawanPilihan = $bolehInputLembur ? mysqli_query($conn, "SELECT id, emp_id, employee_name FROM karyawan" . ($roleSaatIni === "pic" ? " WHERE department_id = " . (int) ($departmentId ?? 0) : "") . " ORDER BY employee_name") : false;
$judulHalaman = "Lembur"; $subjudulHalaman = "Input dan pemantauan laporan lembur."; $halamanAktif = "lembur";
require __DIR__ . "/partials/atas.php";
?>
<?php if ($pesan !== ""): ?><div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
<?php if ($bolehApprovalPusat): ?>
<section class="form-card"><div class="form-card-header"><h2>Tambah Catatan Lembur</h2></div><div class="form-body"><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><div class="form-group"><label>Karyawan</label><select name="karyawan_id" required><?php while ($k = mysqli_fetch_assoc($karyawanPilihan)): ?><option value="<?= (int) $k["id"]; ?>"><?= htmlspecialchars($k["emp_id"] . " - " . $k["employee_name"]); ?></option><?php endwhile; ?></select></div><div class="form-group"><label>Mulai</label><input type="datetime-local" name="mulai_at" required></div><div class="form-group"><label>Selesai</label><input type="datetime-local" name="selesai_at" required></div><div class="form-group"><label>Catatan Lembur</label><textarea name="deskripsi" rows="3" required></textarea></div><button class="btn btn-success" type="submit">Ajukan Lembur</button></form></div></section>
<?php endif; ?>
<?php if (rolePengguna() === "pic"): ?><section class="form-card"><div class="form-card-header"><h2>Buat Laporan Lembur</h2></div><div class="form-body"><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><div class="form-group"><label>Karyawan</label><select name="karyawan_id" required><?php while ($k = mysqli_fetch_assoc($karyawanPilihan)): ?><option value="<?= (int) $k["id"]; ?>"><?= htmlspecialchars($k["emp_id"] . " - " . $k["employee_name"]); ?></option><?php endwhile; ?></select></div><div class="form-group"><label>Mulai</label><input type="datetime-local" name="mulai_at" required></div><div class="form-group"><label>Selesai</label><input type="datetime-local" name="selesai_at" required></div><div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" rows="3"></textarea></div><button class="btn btn-success" type="submit">Simpan Draft</button></form></div></section><?php endif; ?>
<section class="data-card"><div class="data-card-header"><h2>Daftar Laporan Lembur</h2></div><div class="table-wrapper"><table><thead><tr><th>ID</th><th>Karyawan</th><th>Mulai</th><th>Selesai</th><th>Total Menit</th><th>Status</th><th>Upah</th><th>Aksi</th></tr></thead><tbody><?php while ($row = mysqli_fetch_assoc($laporan)): ?><tr><td><?= (int) $row["id"]; ?></td><td><?= htmlspecialchars($row["emp_id"] . " - " . $row["employee_name"]); ?></td><td><?= htmlspecialchars($row["mulai_at"]); ?></td><td><?= htmlspecialchars($row["selesai_at"]); ?></td><td><?= number_format((int) $row["total_menit"], 0, ",", "."); ?></td><td><?= htmlspecialchars($row["status"]); ?></td><td><?= $row["jumlah_upah"] === null ? "-" : "Rp " . number_format((float) $row["jumlah_upah"], 0, ",", "."); ?></td><td><?php if (rolePengguna() === "pic" && $row["status"] === "draft"): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="kirim"><input type="hidden" name="overtime_id" value="<?= (int) $row["id"]; ?>"><button class="btn btn-primary" type="submit">Kirim</button></form><?php elseif (rolePengguna() === "pic" && $row["status"] === "disetujui"): ?><form method="POST" class="compensation-form"><input method="POST" type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="kompensasi"><input type="hidden" name="overtime_id" value="<?= (int) $row["id"]; ?>"><select name="metode_perhitungan" class="overtime-method"><option value="nominal_final">Nominal</option><option value="per_jam">Per jam</option></select><input name="tarif_per_jam" class="overtime-rate" type="number" min="0" step="0.01" placeholder="Tarif/jam"><input name="jumlah_upah" class="overtime-total" type="number" min="0" step="0.01" placeholder="Jumlah upah" required><button class="btn btn-success" type="submit">Simpan Upah</button></form><?php elseif ((rolePengguna() === "koordinator" && $row["status"] === "menunggu_koordinator") || (rolePengguna() === "manager" && $row["status"] === "menunggu_manager")): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="keputusan"><input type="hidden" name="overtime_id" value="<?= (int) $row["id"]; ?>"><input name="catatan" placeholder="Catatan penolakan"><button class="btn btn-success" name="keputusan" value="approved">Setujui</button><button class="btn btn-danger" name="keputusan" value="rejected">Tolak</button></form><?php else: ?>-<?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></section>
<script>const gajiLembur = <?= json_encode($gajiLembur, JSON_UNESCAPED_UNICODE); ?>; document.querySelectorAll('.compensation-form').forEach(form => { const select = form.querySelector('.overtime-method'); if (select) { const hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'metode_perhitungan'; hidden.value = 'per_jam'; select.replaceWith(hidden); } const rate = form.querySelector('.overtime-rate'); const total = form.querySelector('.overtime-total'); const minutes = Number(form.closest('tr').children[4].textContent.replace(/\D/g, '')) || 0; const overtimeId = form.querySelector('[name="overtime_id"]').value; const hourly = Number(gajiLembur[overtimeId] || 0) / 173; rate.value = hourly.toFixed(2); total.value = (minutes / 60 * hourly).toFixed(2); rate.readOnly = true; total.readOnly = true; }); document.querySelectorAll('tbody tr').forEach(row => { const status = row.children[5]?.textContent.trim(); const action = row.children[7]; if (action && ['draft', 'menunggu_koordinator', 'menunggu_manager', 'disetujui'].includes(status)) { const id = row.children[0].textContent.trim(); const form = document.createElement('form'); form.method = 'POST'; form.style.display = 'inline-block'; form.style.marginLeft = '6px'; form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="batalkan"><input type="hidden" name="overtime_id" value="' + id + '"><button class="btn btn-danger" type="submit" onclick="return confirm(\'Batalkan laporan lembur ini?\')">Batalkan</button>'; action.appendChild(form); } });</script>
<?php if (in_array(rolePengguna(), ["koordinator", "manager", "admin", "superadmin"], true)): ?>
<script>
    document.querySelectorAll('input[name="aksi"][value="batalkan"]').forEach(input => input.form?.remove());
    document.querySelectorAll('button[name="keputusan"][value="approved"]').forEach(button => { button.textContent = 'Setuju'; });
</script>
<?php endif; ?>
<?php if ($bolehApprovalPusat): ?>
<script>
document.querySelectorAll('.data-card tbody tr').forEach(row => {
    const status = row.children[5]?.textContent.trim();
    if (!['menunggu_koordinator', 'menunggu_manager'].includes(status)) return;
    const action = row.children[7];
    const id = row.children[0].textContent.trim();
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
document.querySelectorAll('.data-card tbody tr').forEach(row => {
    const action = row.children[7];
    const id = row.children[0]?.textContent.trim();
    if (!action || !id) return;
    if (action.textContent.trim() === '-') action.textContent = '';
    const form = document.createElement('form');
    form.method = 'POST';
    form.className = 'delete-overtime-form';
    form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="overtime_id" value="' + id + '"><button class="btn btn-danger" type="submit">Hapus</button>';
    form.addEventListener('submit', event => { if (!confirm('Hapus laporan lembur ID ' + id + '? Data approval dan upah lembur terkait juga akan dihapus.')) event.preventDefault(); });
    action.appendChild(form);
});
</script>
<?php endif; ?>
<style>
    .data-card table td:last-child { min-width: 190px; }
    .data-card table td:last-child > form { display: inline-flex !important; align-items: center; gap: 8px; margin: 0 4px 4px 0; vertical-align: middle; }
    .data-card table td:last-child > form.compensation-form { flex-wrap: wrap; }
    .data-card table td:last-child button { white-space: nowrap; }
</style>
<?php require __DIR__ . "/partials/bawah.php"; ?>
