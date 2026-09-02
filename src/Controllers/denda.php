<?php
declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Settings/master-data.php';

wajibRole('pic', 'koordinator', 'manager', 'direktur', 'admin', 'superadmin');

function siapkanTabelDenda(mysqli $conn): bool
{
    $laporan = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS denda_reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, karyawan_id INT NOT NULL, department_id INT NOT NULL,
        nominal DECIMAL(15,2) NOT NULL, deskripsi TEXT NOT NULL, status ENUM('draft','menunggu_koordinator','menunggu_manager','disetujui','ditolak') NOT NULL DEFAULT 'draft',
        dibuat_oleh_user_id INT NOT NULL, submitted_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        potongan_id INT NULL, UNIQUE KEY uniq_denda_potongan (potongan_id), KEY idx_denda_departemen (department_id),
        CONSTRAINT fk_denda_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
        CONSTRAINT fk_denda_pembuat FOREIGN KEY (dibuat_oleh_user_id) REFERENCES users(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $approval = mysqli_query($conn, "CREATE TABLE IF NOT EXISTS denda_approvals (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, denda_id INT UNSIGNED NOT NULL, tahap ENUM('koordinator','manager') NOT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', approver_user_id INT NULL, catatan TEXT NULL, decided_at DATETIME NULL,
        UNIQUE KEY uniq_denda_tahap (denda_id, tahap),
        CONSTRAINT fk_denda_approval_laporan FOREIGN KEY (denda_id) REFERENCES denda_reports(id) ON DELETE CASCADE,
        CONSTRAINT fk_denda_approval_user FOREIGN KEY (approver_user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!$laporan || !$approval) return false;
    $kolom = mysqli_query($conn, "SHOW COLUMNS FROM denda_reports LIKE 'diproses_oleh_user_id'");
    if ($kolom && mysqli_num_rows($kolom) === 0) {
        mysqli_query($conn, "ALTER TABLE denda_reports ADD COLUMN diproses_oleh_user_id INT NULL AFTER dibuat_oleh_user_id");
    }
    if ($kolom) mysqli_free_result($kolom);
    $catatanKolom = mysqli_query($conn, "SHOW COLUMNS FROM denda_reports LIKE 'catatan_persetujuan'");
    if ($catatanKolom && mysqli_num_rows($catatanKolom) === 0) mysqli_query($conn, "ALTER TABLE denda_reports ADD COLUMN catatan_persetujuan TEXT NULL AFTER diproses_oleh_user_id");
    if ($catatanKolom) mysqli_free_result($catatanKolom);
    foreach ([
        'tipe_denda' => "VARCHAR(30) NULL AFTER nominal",
        'jam_jadwal' => "TIME NULL AFTER tipe_denda",
        'jam_kejadian' => "TIME NULL AFTER jam_jadwal",
        'tanggal_kejadian' => "DATE NULL AFTER jam_kejadian",
        'selisih_menit' => "INT NULL AFTER jam_kejadian",
        'selisih_detik' => "INT NULL AFTER selisih_menit",
        'toleransi_menit' => "INT NULL AFTER selisih_detik",
        'pengali_jam' => "DECIMAL(6,2) NULL AFTER toleransi_menit",
        'gaji_pokok_snapshot' => "DECIMAL(15,2) NULL AFTER pengali_jam",
        'pembagi_jam_bulanan' => "DECIMAL(8,2) NULL AFTER gaji_pokok_snapshot",
    ] as $namaKolom => $definisiKolom) {
        $cekKolom = mysqli_query($conn, "SHOW COLUMNS FROM denda_reports LIKE '{$namaKolom}'");
        $adaKolom = $cekKolom && mysqli_num_rows($cekKolom) > 0;
        if ($cekKolom) mysqli_free_result($cekKolom);
        if (!$adaKolom) mysqli_query($conn, "ALTER TABLE denda_reports ADD COLUMN {$namaKolom} {$definisiKolom}");
    }
    return true;
}

if (!siapkanTabelDenda($conn)) exit('Tabel denda tidak dapat disiapkan.');
$aturanDenda = ambilAturanDenda($conn);
$pesan = ''; $role = rolePengguna(); $roleEfektif = roleEfektif($role); $departmentId = departmentIdPengguna(); $langsungSuperadmin = punyaRole('superadmin'); $bolehInput = in_array($role, ['pic', 'admin', 'superadmin'], true);
$karyawanSql = 'SELECT id, emp_id, employee_name, position, department, department_id, TIME_FORMAT(shift_mulai, \'%H:%i\') AS shift_mulai, TIME_FORMAT(shift_selesai, \'%H:%i\') AS shift_selesai FROM karyawan WHERE department_id IS NOT NULL' . (roleOperasional() ? ' AND department_id = ' . (int) $departmentId : '') . ' ORDER BY employee_name';
$karyawanPilihan = mysqli_query($conn, $karyawanSql);
$dataKaryawanDenda = [];
if ($karyawanPilihan) while ($karyawanItem = mysqli_fetch_assoc($karyawanPilihan)) {
    $dataKaryawanDenda[] = [
        'id' => (int) $karyawanItem['id'], 'emp_id' => (string) $karyawanItem['emp_id'],
        'nama' => (string) $karyawanItem['employee_name'], 'posisi' => (string) $karyawanItem['position'],
        'department_id' => (int) $karyawanItem['department_id'], 'shift_mulai' => (string) ($karyawanItem['shift_mulai'] ?? ''), 'shift_selesai' => (string) ($karyawanItem['shift_selesai'] ?? ''),
    ];
}
$departemenPilihanDenda = ambilDepartemenPilihan($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);
$posisiPerDepartemenDenda = ambilPosisiPerDepartemen($conn, roleOperasional() ? (int) ($departmentId ?? 0) : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? '');
    if (!csrfValid($_POST['csrf_token'] ?? null)) $pesan = 'Token keamanan tidak valid.';
    elseif ($aksi === 'buat' && $bolehInput) {
        $karyawanId = (int) ($_POST['karyawan_id'] ?? 0); $tipeDenda = (string) ($_POST['tipe_denda'] ?? ''); $jamKejadian = trim((string) ($_POST['jam_kejadian'] ?? '')); $tanggalKejadian = trim((string) ($_POST['tanggal_kejadian'] ?? '')); $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));
        $cek = mysqli_prepare($conn, "SELECT k.department_id, TIME_FORMAT(k.shift_mulai, '%H:%i') AS shift_mulai, TIME_FORMAT(k.shift_selesai, '%H:%i') AS shift_selesai, COALESCE((SELECT pg.gaji_pokok FROM profil_gaji pg WHERE pg.karyawan_id = k.id AND pg.berlaku_mulai <= CURDATE() AND (pg.berlaku_sampai IS NULL OR pg.berlaku_sampai >= CURDATE()) ORDER BY pg.berlaku_mulai DESC, pg.id DESC LIMIT 1), k.salary, 0) AS gaji_pokok FROM karyawan k WHERE k.id = ? LIMIT 1"); mysqli_stmt_bind_param($cek, 'i', $karyawanId); mysqli_stmt_execute($cek); $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($cek)); mysqli_stmt_close($cek);
        $aturan = $aturanDenda[$tipeDenda] ?? null;
        $jamValid = preg_match('/^([01]\\d|2[0-3]):[0-5]\\d(?::[0-5]\\d)?$/', $jamKejadian) === 1;
        $tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggalKejadian);
        $tanggalValid = $tanggal !== false && $tanggal->format('Y-m-d') === $tanggalKejadian;
        $jamJadwal = $tipeDenda === 'terlambat' ? (string) ($karyawan['shift_mulai'] ?? '') : (string) ($karyawan['shift_selesai'] ?? '');
        $menit = static fn (string $jam): int => ((int) substr($jam, 0, 2) * 60) + (int) substr($jam, 3, 2);
        $jamMasuk = (string) ($karyawan['shift_mulai'] ?? '');
        $jamPulang = (string) ($karyawan['shift_selesai'] ?? '');
        $lintasHari = $jamMasuk !== '' && $jamPulang !== '' && $menit($jamPulang) <= $menit($jamMasuk);
        $selisih = 0;
        $selisihDetik = 0;
        if ($jamValid && $tanggalValid && $jamJadwal !== '' && $jamMasuk !== '' && $jamPulang !== '') {
            $aktual = new DateTimeImmutable($tanggalKejadian . ' ' . $jamKejadian);
            if ($tipeDenda === 'terlambat') {
                $tanggalJadwal = $lintasHari && $menit($jamKejadian) < $menit($jamMasuk) ? $tanggal->modify('-1 day') : $tanggal;
                $terjadwal = new DateTimeImmutable($tanggalJadwal->format('Y-m-d') . ' ' . $jamMasuk);
                $selisihDetik = $aktual->getTimestamp() - $terjadwal->getTimestamp();
            } else {
                $tanggalJadwal = $lintasHari && $menit($jamKejadian) >= $menit($jamMasuk) ? $tanggal->modify('+1 day') : $tanggal;
                $terjadwal = new DateTimeImmutable($tanggalJadwal->format('Y-m-d') . ' ' . $jamPulang);
                $selisihDetik = $terjadwal->getTimestamp() - $aktual->getTimestamp();
            }
            $selisih = (int) floor($selisihDetik / 60);
        }
        $toleransi = (int) ($aturan['toleransi_menit'] ?? 0);
        $toleransiDetik = $toleransi * 60;
        $tingkatDenda = $aturan && $selisihDetik > $toleransiDetik ? intdiv($selisihDetik - $toleransiDetik - 1, 300) + 1 : 0;
        $pengali = $tingkatDenda > 0 ? (float) $aturan['pengali_tingkat_1'] * $tingkatDenda : 0.0;
        $pembagi = (float) ($aturan['pembagi_jam_bulanan'] ?? 0); $gajiPokok = (float) ($karyawan['gaji_pokok'] ?? 0); $nominal = $pembagi > 0 ? round(($gajiPokok / $pembagi) * $pengali, 2) : 0;
        if (!$karyawan || !$aturan || !$jamValid || !$tanggalValid || $jamJadwal === '' || $deskripsi === '') $pesan = 'Karyawan, tipe denda, tanggal dan jam kejadian, jadwal kerja, dan alasan wajib valid.';
        elseif ($selisihDetik < 0) $pesan = 'Jam kejadian tidak sesuai untuk tipe denda yang dipilih.';
        elseif ($selisihDetik <= $toleransiDetik) $pesan = 'Selisih waktu masih dalam toleransi dan tidak menghasilkan denda.';
        elseif ($gajiPokok <= 0 || $nominal <= 0) $pesan = 'Gaji pokok aktif karyawan belum tersedia untuk menghitung denda.';
        elseif (roleOperasional() && (int) $karyawan['department_id'] !== (int) $departmentId) $pesan = 'Karyawan berada di luar cakupan departemen Anda.';
        else {
            $status = $role === 'pic' ? 'draft' : 'menunggu_koordinator'; $pembuat = (int) $_SESSION['user']['id']; $dept = (int) $karyawan['department_id'];
            $stmt = mysqli_prepare($conn, 'INSERT INTO denda_reports (karyawan_id, department_id, nominal, tipe_denda, jam_jadwal, jam_kejadian, tanggal_kejadian, selisih_menit, selisih_detik, toleransi_menit, pengali_jam, gaji_pokok_snapshot, pembagi_jam_bulanan, deskripsi, status, dibuat_oleh_user_id, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? = \'menunggu_koordinator\', NOW(), NULL))');
            $labelTipe = $tipeDenda === 'terlambat' ? 'Datang terlambat' : 'Pulang lebih awal';
            $selisihTampil = intdiv($selisihDetik, 60) . ' menit ' . ($selisihDetik % 60) . ' detik';
            $deskripsi = $labelTipe . ': ' . $selisihTampil . ' (tanggal ' . $tanggalKejadian . ', jadwal ' . $jamJadwal . ', kejadian ' . $jamKejadian . ")\n" . $deskripsi;
            mysqli_stmt_bind_param($stmt, 'iidssssiiidddssis', $karyawanId, $dept, $nominal, $tipeDenda, $jamJadwal, $jamKejadian, $tanggalKejadian, $selisih, $selisihDetik, $toleransi, $pengali, $gajiPokok, $pembagi, $deskripsi, $status, $pembuat, $status);
            if (mysqli_stmt_execute($stmt)) { $id = (int) mysqli_insert_id($conn); if ($status !== 'draft') mysqli_query($conn, "INSERT IGNORE INTO denda_approvals (denda_id, tahap) VALUES ({$id}, 'koordinator'), ({$id}, 'manager')"); catatAktivitas($conn, "Membuat denda ID {$id}."); header('Location: denda.php'); exit; }
            $pesan = 'Denda gagal disimpan.'; mysqli_stmt_close($stmt);
        }
    } elseif ($aksi === 'kirim' && $role === 'pic') {
        $id = (int) ($_POST['denda_id'] ?? 0); $stmt = mysqli_prepare($conn, "UPDATE denda_reports SET status = 'menunggu_koordinator', submitted_at = NOW() WHERE id = ? AND department_id = ? AND status = 'draft'"); mysqli_stmt_bind_param($stmt, 'ii', $id, $departmentId); mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) { mysqli_query($conn, "INSERT IGNORE INTO denda_approvals (denda_id, tahap) VALUES ({$id}, 'koordinator'), ({$id}, 'manager')"); catatAktivitas($conn, "Mengajukan denda ID {$id}."); header('Location: denda.php'); exit; } $pesan = 'Draft denda tidak dapat dikirim.'; mysqli_stmt_close($stmt);
    } elseif ($aksi === 'hapus' && $role === 'superadmin') {
        $id = (int) ($_POST['denda_id'] ?? 0);
        if ($id <= 0) $pesan = 'Data denda tidak valid.';
        else {
            $cekPotongan = mysqli_prepare($conn, 'SELECT potongan_id FROM denda_reports WHERE id = ? LIMIT 1');
            mysqli_stmt_bind_param($cekPotongan, 'i', $id); mysqli_stmt_execute($cekPotongan);
            $dataPotongan = mysqli_fetch_assoc(mysqli_stmt_get_result($cekPotongan)); mysqli_stmt_close($cekPotongan);
            mysqli_begin_transaction($conn);
            $stmt = mysqli_prepare($conn, 'DELETE FROM denda_reports WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                mysqli_stmt_close($stmt);
                $potonganId = (int) ($dataPotongan['potongan_id'] ?? 0);
                if ($potonganId > 0) {
                    $hapusPotongan = mysqli_prepare($conn, 'DELETE FROM potongan_karyawan WHERE id = ?');
                    mysqli_stmt_bind_param($hapusPotongan, 'i', $potonganId); mysqli_stmt_execute($hapusPotongan); mysqli_stmt_close($hapusPotongan);
                }
                mysqli_commit($conn); catatAktivitas($conn, 'Menghapus denda ID ' . $id . ' beserta potongan upahnya.'); header('Location: denda.php'); exit;
            }
            mysqli_rollback($conn);
            mysqli_stmt_close($stmt); $pesan = 'Data denda tidak ditemukan.';
        }
    } elseif ($aksi === 'keputusan' && ($langsungSuperadmin || in_array($roleEfektif, ['koordinator', 'manager'], true))) {
        $id = (int) ($_POST['denda_id'] ?? 0); $keputusan = (string) ($_POST['keputusan'] ?? ''); $keputusan = $keputusan === 'disetujui' ? 'approved' : ($keputusan === 'ditolak' ? 'rejected' : $keputusan); $catatan = trim((string) ($_POST['catatan'] ?? '')); $tahap = $roleEfektif === 'koordinator' ? 'koordinator' : 'manager';
        if (!in_array($keputusan, ['approved', 'rejected'], true) || ($keputusan === 'rejected' && $catatan === '')) $pesan = 'Keputusan tidak valid atau alasan penolakan belum diisi.';
        else {
            if ($langsungSuperadmin) {
                $stmtLangsung = mysqli_prepare($conn, "UPDATE denda_reports SET status = ?, diproses_oleh_user_id = ?, catatan_persetujuan = NULLIF(?, '') WHERE id = ? AND status NOT IN ('disetujui', 'ditolak')");
                $statusLangsung = $keputusan === 'approved' ? 'disetujui' : 'ditolak'; $userIdLangsung = (int) $_SESSION['user']['id']; mysqli_stmt_bind_param($stmtLangsung, 'sisi', $statusLangsung, $userIdLangsung, $catatan, $id); mysqli_stmt_execute($stmtLangsung);
                if (mysqli_stmt_affected_rows($stmtLangsung) > 0) { if ($statusLangsung === 'disetujui') { $denda = mysqli_fetch_assoc(mysqli_query($conn, "SELECT karyawan_id, nominal FROM denda_reports WHERE id = {$id} AND potongan_id IS NULL")); if ($denda) { $pot = mysqli_prepare($conn, "INSERT INTO potongan_karyawan (karyawan_id, nama, nilai) VALUES (?, 'Denda', ?)"); mysqli_stmt_bind_param($pot, 'id', $denda['karyawan_id'], $denda['nominal']); mysqli_stmt_execute($pot); $pid = mysqli_insert_id($conn); mysqli_query($conn, "UPDATE denda_reports SET potongan_id = {$pid} WHERE id = {$id}"); mysqli_stmt_close($pot); } } header('Location: denda.php'); exit; } mysqli_stmt_close($stmtLangsung);
            }
            if ($tahap === 'manager') { $cek = mysqli_query($conn, "SELECT 1 FROM denda_approvals WHERE denda_id = {$id} AND tahap = 'koordinator' AND status = 'approved'"); if (!$cek || mysqli_num_rows($cek) === 0) $pesan = 'Approval Koordinator harus selesai sebelum Manager memproses denda.'; }
            if ($pesan === '') {
                $stmt = mysqli_prepare($conn, 'UPDATE denda_approvals a INNER JOIN denda_reports d ON d.id = a.denda_id SET a.status = ?, a.approver_user_id = ?, a.catatan = ?, a.decided_at = NOW() WHERE a.denda_id = ? AND a.tahap = ? AND d.department_id = ? AND a.status = \'pending\''); $userId = (int) $_SESSION['user']['id']; mysqli_stmt_bind_param($stmt, 'sisisi', $keputusan, $userId, $catatan, $id, $tahap, $departmentId); mysqli_stmt_execute($stmt);
                if (mysqli_stmt_affected_rows($stmt) > 0) {
                    mysqli_query($conn, "UPDATE denda_reports SET diproses_oleh_user_id = " . (int) $_SESSION['user']['id'] . ", catatan_persetujuan = " . ($catatan === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $catatan) . "'") . " WHERE id = " . $id);
                    $status = $keputusan === 'rejected' ? 'ditolak' : ($tahap === 'koordinator' ? 'menunggu_manager' : 'disetujui'); mysqli_query($conn, "UPDATE denda_reports SET status = '{$status}' WHERE id = {$id}");
                    if ($status === 'disetujui') { $denda = mysqli_query($conn, "SELECT karyawan_id, nominal FROM denda_reports WHERE id = {$id} AND potongan_id IS NULL"); if ($denda && ($data = mysqli_fetch_assoc($denda))) { $kId = (int) $data['karyawan_id']; $nilai = (float) $data['nominal']; $potongan = mysqli_prepare($conn, "INSERT INTO potongan_karyawan (karyawan_id, nama, nilai) VALUES (?, 'Denda', ?)"); mysqli_stmt_bind_param($potongan, 'id', $kId, $nilai); if (mysqli_stmt_execute($potongan)) { $potonganId = (int) mysqli_insert_id($conn); mysqli_query($conn, "UPDATE denda_reports SET potongan_id = {$potonganId} WHERE id = {$id}"); } mysqli_stmt_close($potongan); } }
                    catatAktivitas($conn, "Memproses approval denda ID {$id} pada tahap {$tahap}."); header('Location: denda.php'); exit;
                } $pesan = 'Denda tidak dapat diproses.'; mysqli_stmt_close($stmt);
            }
        }
    }
}

$where = roleOperasional() ? 'd.department_id = ' . (int) $departmentId : '1=1';
$daftar = mysqli_query($conn, "SELECT d.*, k.emp_id, k.employee_name, k.position, k.department, u.nama AS nama_pembuat, pe.role AS role_pemroses, GROUP_CONCAT(CONCAT(a.tahap, ': ', a.status, IF(TRIM(COALESCE(a.catatan, '')) <> '', CONCAT(' — ', a.catatan), '')) ORDER BY a.id SEPARATOR ' | ') AS approval_ringkasan FROM denda_reports d INNER JOIN karyawan k ON k.id = d.karyawan_id LEFT JOIN users u ON u.id = d.dibuat_oleh_user_id LEFT JOIN users pe ON pe.id = d.diproses_oleh_user_id LEFT JOIN denda_approvals a ON a.denda_id = d.id WHERE {$where} GROUP BY d.id ORDER BY d.created_at DESC");
$judulHalaman = 'Denda'; $subjudulHalaman = 'Pengajuan, persetujuan, dan penerapan denda karyawan.'; $halamanAktif = 'denda'; $kelasHalaman = 'lembur';
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<?php if ($pesan !== ''): ?><div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
<div class="overtime-notification-action"><a class="btn btn-primary" href="notifikasi-denda.php">🔔 Buka Notifikasi</a><a class="btn export-excel-btn" href="export_denda.php">Export Excel</a></div><?php if ($bolehInput): ?><section class="form-card"><div class="form-card-header"><h2>Buat Pengajuan Denda</h2><p>Denda dari PIC disimpan sebagai draft, lalu dikirim untuk approval Koordinator dan Manager.</p></div><div class="form-body"><form method="POST" class="overtime-entry-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="buat"><div class="form-group"><label for="denda-department">Departemen</label><select id="denda-department" required><option value="">Pilih departemen</option><?php foreach ($departemenPilihanDenda as $idDepartemen => $namaDepartemen): ?><option value="<?= (int) $idDepartemen; ?>"><?= htmlspecialchars($namaDepartemen); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="denda-position">Posisi</label><select id="denda-position" disabled required><option value="">Pilih departemen terlebih dahulu</option></select></div><div class="form-group overtime-employee-group"><label for="denda-karyawan">Karyawan</label><select id="denda-karyawan" name="karyawan_id" disabled required><option value="">Pilih departemen dan posisi terlebih dahulu</option></select></div><div class="form-group"><label for="denda-nominal">Nominal Denda</label><input id="denda-nominal" name="nominal" type="number" min="1" step="1" required></div><div class="form-group"><label for="denda-deskripsi">Alasan Denda</label><textarea id="denda-deskripsi" name="deskripsi" rows="3" required></textarea></div><button class="btn btn-success" type="submit"><?= $role === 'pic' ? 'Simpan Draft' : 'Ajukan Denda'; ?></button></form></div></section><script>(() => { const employees = <?= json_encode($dataKaryawanDenda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>; const positionsByDepartment = <?= json_encode($posisiPerDepartemenDenda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>; const department = document.getElementById('denda-department'); const position = document.getElementById('denda-position'); const employee = document.getElementById('denda-karyawan'); const option = (value, label) => { const item = document.createElement('option'); item.value = String(value); item.textContent = label; return item; }; const updateEmployees = () => { employee.replaceChildren(); const matching = employees.filter(item => String(item.department_id) === department.value && item.posisi === position.value); employee.append(option('', matching.length ? 'Pilih karyawan' : 'Tidak ada karyawan yang sesuai')); matching.forEach(item => employee.append(option(item.id, `${item.emp_id} - ${item.nama}`))); employee.disabled = matching.length === 0; }; const updatePositions = () => { position.replaceChildren(); if (!department.value) { position.append(option('', 'Pilih departemen terlebih dahulu')); position.disabled = true; employee.replaceChildren(option('', 'Pilih departemen dan posisi terlebih dahulu')); employee.disabled = true; return; } const positions = [...new Set(positionsByDepartment[department.value] || [])].sort((a, b) => a.localeCompare(b, 'id')); position.append(option('', positions.length ? 'Pilih posisi' : 'Tidak ada posisi pada departemen ini')); positions.forEach(item => position.append(option(item, item))); position.disabled = positions.length === 0; employee.replaceChildren(option('', 'Pilih departemen dan posisi terlebih dahulu')); employee.disabled = true; }; department.addEventListener('change', updatePositions); position.addEventListener('change', updateEmployees); })();</script><?php endif; ?>
<section class="data-card"><div class="data-card-header"><h2>Daftar Pengajuan Denda</h2></div><div class="table-wrapper"><table class="overtime-report-table"><thead><tr><th>ID</th><th>Karyawan</th><th>Posisi</th><th>Departemen</th><th>Nominal</th><th>Alasan</th><th>Status</th><th>Dibuat Oleh</th><th>Aksi</th></tr></thead><tbody><?php if ($daftar && mysqli_num_rows($daftar) > 0): while ($row = mysqli_fetch_assoc($daftar)): ?><tr><td><?= (int) $row['id']; ?></td><td><?= htmlspecialchars($row['emp_id'] . ' - ' . $row['employee_name']); ?></td><td><?= htmlspecialchars((string) $row['position']); ?></td><td><?= htmlspecialchars((string) $row['department']); ?></td><td>Rp <?= number_format((float) $row['nominal'], 0, ',', '.'); ?></td><td><?= nl2br(htmlspecialchars($row['deskripsi'])); ?></td><td><?php $statusLabel = ucwords(str_replace('_', ' ', $row['status'])); if ($row['status'] === 'disetujui') $statusLabel = 'Disetujui ' . labelRole((string) ($row['role_pemroses'] ?? 'manager')); ?><span class="status-badge status-<?= htmlspecialchars($row['status']); ?>"><?= htmlspecialchars($statusLabel); ?></span></td><td><?= htmlspecialchars($row['nama_pembuat'] ?? '-'); ?></td><td><?php if ($role === 'pic' && $row['status'] === 'draft'): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="kirim"><input type="hidden" name="denda_id" value="<?= (int) $row['id']; ?>"><button class="btn btn-primary">Kirim</button></form><?php elseif (($role === 'koordinator' && $row['status'] === 'menunggu_koordinator') || (in_array($role, ['manager', 'direktur'], true) && $row['status'] === 'menunggu_manager') || ($role === 'superadmin' && in_array($row['status'], ['menunggu_koordinator', 'menunggu_manager', 'draft'], true))): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="keputusan"><input type="hidden" name="denda_id" value="<?= (int) $row['id']; ?>"><input name="catatan" placeholder="Catatan penolakan"><button class="btn btn-success" name="keputusan" value="approved">Setujui</button><button class="btn btn-danger" name="keputusan" value="rejected">Tolak</button></form><?php endif; ?><?php if ($role === 'superadmin'): ?><form method="POST" onsubmit="return confirm('Hapus data denda ini?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="denda_id" value="<?= (int) $row['id']; ?>"><button class="btn btn-danger" type="submit">Hapus</button></form><?php elseif (!in_array($role, ['pic', 'koordinator', 'manager'], true) || ($role !== 'pic' && $row['status'] !== 'menunggu_koordinator' && $row['status'] !== 'menunggu_manager')): ?><?php if ($role !== 'pic' && $role !== 'koordinator' && $role !== 'manager'): ?>-<?php endif; ?><?php endif; ?></td></tr><?php endwhile; else: ?><tr><td colspan="9" class="empty-table">Belum ada pengajuan denda.</td></tr><?php endif; ?></tbody></table></div></section>
<?php if ($bolehInput): ?>
<script>
(() => {
    const form = document.querySelector('.overtime-entry-form');
    const employee = document.getElementById('denda-karyawan');
    const nominal = document.getElementById('denda-nominal');
    if (!form || !employee || !nominal) return;
    form.id = 'denda-entry-form';
    nominal.required = false; nominal.name = ''; nominal.readOnly = true;
    nominal.closest('.form-group').style.display = 'none';
    employee.closest('.form-group').classList.remove('overtime-employee-group');
    const scheduleFields = document.createElement('div');
    scheduleFields.className = 'form-group denda-schedule-field';
    scheduleFields.innerHTML = '<div><label for="denda-jam-masuk">Jam masuk</label><input id="denda-jam-masuk" type="text" value="-" readonly></div><div><label for="denda-jam-pulang">Jam pulang</label><input id="denda-jam-pulang" type="text" value="-" readonly></div>';
    employee.closest('.form-group').after(scheduleFields);
    const fields = document.createElement('div');
    fields.className = 'form-group denda-type-field';
    fields.innerHTML = '<label for="denda-tipe">Tipe Denda</label><select id="denda-tipe" name="tipe_denda" required><option value="">Pilih tipe denda</option><option value="terlambat">Datang terlambat</option><option value="pulang_lebih_awal">Pulang lebih awal</option></select>';
    nominal.closest('.form-group').before(fields);
    const eventDateTime = document.createElement('div');
    eventDateTime.className = 'form-group denda-event-datetime';
    eventDateTime.innerHTML = '<div><label for="denda-tanggal-kejadian">Tanggal kejadian</label><input id="denda-tanggal-kejadian" name="tanggal_kejadian" type="date" value="<?= date('Y-m-d'); ?>" required></div><div><label for="denda-jam-kejadian">Jam kejadian</label><input id="denda-jam-kejadian" name="jam_kejadian" type="time" step="1" required disabled></div>';
    fields.after(eventDateTime);
    const info = document.createElement('p');
    info.id = 'denda-jadwal-info';
    info.className = 'field-note denda-info-field';
    info.textContent = 'Pilih karyawan dan tipe denda untuk melihat jadwal kerja.';
    eventDateTime.after(info);
    const type = document.getElementById('denda-tipe');
    const time = document.getElementById('denda-jam-kejadian');
    const timeLabel = eventDateTime.querySelector('label[for="denda-jam-kejadian"]');
    const startSchedule = document.getElementById('denda-jam-masuk');
    const endSchedule = document.getElementById('denda-jam-pulang');
    const employees = <?= json_encode($dataKaryawanDenda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const rules = <?= json_encode($aturanDenda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const update = () => {
        const selected = employees.find(item => String(item.id) === employee.value);
        const rule = rules[type.value];
        const scheduled = selected && type.value === 'terlambat' ? selected.shift_mulai : selected && type.value === 'pulang_lebih_awal' ? selected.shift_selesai : '';
        startSchedule.value = selected && selected.shift_mulai ? selected.shift_mulai : '-';
        endSchedule.value = selected && selected.shift_selesai ? selected.shift_selesai : '-';
        timeLabel.textContent = type.value === 'terlambat' ? 'Jam terlambat' : type.value === 'pulang_lebih_awal' ? 'Jam pulang lebih awal' : 'Jam kejadian';
        time.disabled = !scheduled || !rule;
        time.value = '';
        if (!selected || !selected.shift_mulai || !selected.shift_selesai) { info.textContent = 'Pilih karyawan yang memiliki jadwal kerja.'; return; }
        if (!scheduled || !rule) { info.textContent = 'Pilih tipe denda untuk melanjutkan.'; return; }
        const label = type.value === 'terlambat' ? 'Jam masuk' : 'Jam pulang';
        info.textContent = `${label} menjadi pembanding. Toleransi ${rule.toleransi_menit} menit; denda mulai 1 detik setelah batas toleransi, lalu pengali naik satu tingkat setiap tambahan 5 menit.`;
    };
    employee.addEventListener('change', update); type.addEventListener('change', update);
    const formStateKey = 'conscomp-denda-form-state';
    const hasServerError = <?= $pesan !== '' ? 'true' : 'false'; ?>;
    const fieldsToPersist = ['denda-department', 'denda-position', 'denda-karyawan', 'denda-tipe', 'denda-tanggal-kejadian', 'denda-jam-kejadian', 'denda-deskripsi'];
    const saveFormState = () => {
        const state = {};
        fieldsToPersist.forEach(id => { const input = document.getElementById(id); if (input) state[id] = input.value; });
        sessionStorage.setItem(formStateKey, JSON.stringify(state));
    };
    const restoreFormState = () => {
        try {
            const state = JSON.parse(sessionStorage.getItem(formStateKey) || '{}');
            if (!state['denda-department']) return;
            const setAndChange = id => { const input = document.getElementById(id); if (!input || !(id in state)) return; input.value = state[id]; input.dispatchEvent(new Event('change', { bubbles: true })); };
            setAndChange('denda-department');
            setAndChange('denda-position');
            setAndChange('denda-karyawan');
            setAndChange('denda-tipe');
            const dateValue = document.getElementById('denda-tanggal-kejadian');
            const timeValue = document.getElementById('denda-jam-kejadian');
            const description = document.getElementById('denda-deskripsi');
            if (dateValue && state['denda-tanggal-kejadian']) dateValue.value = state['denda-tanggal-kejadian'];
            if (timeValue && state['denda-jam-kejadian']) timeValue.value = state['denda-jam-kejadian'];
            if (description && 'denda-deskripsi' in state) description.value = state['denda-deskripsi'];
        } catch (error) {
            sessionStorage.removeItem(formStateKey);
        }
    };
    form.addEventListener('submit', saveFormState);
    if (hasServerError) {
        restoreFormState();
        const alert = document.querySelector('.alert-error');
        if (alert) alert.setAttribute('tabindex', '-1');
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
        alert?.focus({ preventScroll: true });
    } else {
        sessionStorage.removeItem(formStateKey);
    }
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
