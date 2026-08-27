<?php
declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Leave/alur-persetujuan-izin.php';
require_once __DIR__ . '/../Services/Leave/cuti-khusus.php';
require_once __DIR__ . '/../Services/Settings/master-data.php';

wajibRole('admin', 'superadmin', 'pic', 'koordinator', 'manager');

if (!siapkanTabelIzinCutiKhusus($conn)) exit('Tabel cuti khusus tidak dapat disiapkan.');

$role = rolePengguna(); $departmentId = departmentIdPengguna(); $langsungSuperadmin = $role === 'superadmin';
$tahapRole = tahapPersetujuanIzinUntukRole($role); $bolehSetuju = $langsungSuperadmin || $tahapRole !== null;
$bolehInput = in_array($role, ['admin', 'superadmin'], true); $pesan = '';
$opsiDeskripsi = ['Menikah', 'Menikahkan anak', 'Istri melahirkan atau keguguran kandungan', 'Keluarga inti meninggal dunia', 'Khitanan atau baptisan anak', 'Ibadah keagamaan', 'Keperluan keluarga penting', 'Lainnya'];
$form = ['department_id' => '', 'position' => '', 'karyawan_id' => '', 'tanggal_mulai' => '', 'tanggal_selesai' => '', 'deskripsi' => '', 'nomor_kontak' => '', 'karyawan_pengganti_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? 'simpan');
    if (!csrfValid($_POST['csrf_token'] ?? null)) $pesan = 'Token keamanan tidak valid.';
    elseif ($aksi === 'keputusan') {
        $id = (int) ($_POST['cuti_khusus_id'] ?? 0); $keputusan = (string) ($_POST['keputusan'] ?? ''); $catatan = trim((string) ($_POST['catatan'] ?? ''));
        if (!$bolehSetuju || !in_array($keputusan, ['disetujui', 'ditolak'], true) || ($keputusan === 'ditolak' && $catatan === '')) $pesan = 'Keputusan tidak valid atau alasan penolakan belum diisi.';
        else {
            $pemroses = (int) ($_SESSION['user']['id'] ?? 0);
            $berhasil = $langsungSuperadmin
                ? prosesKeputusanLangsungSuperadminIzin($conn, 'izin_cuti_khusus', $id, $role, $keputusan, $catatan, $pemroses)
                : prosesKeputusanPersetujuanIzin($conn, 'izin_cuti_khusus', $id, (int) $departmentId, $role, $keputusan, $catatan, $pemroses);
            if ($berhasil) { catatAktivitas($conn, 'Memproses cuti khusus ID ' . $id . ' menjadi ' . $keputusan . '.'); header('Location: izin-cuti-khusus.php'); exit; }
            $pesan = 'Tahap persetujuan tidak sesuai atau data sudah diproses.';
        }
    } elseif ($aksi === 'hapus') {
        $id = (int) ($_POST['cuti_khusus_id'] ?? 0);
        if ($role !== 'superadmin' || $id <= 0) $pesan = 'Hanya Superadmin yang dapat menghapus data cuti khusus.';
        else { $stmt = mysqli_prepare($conn, "DELETE FROM izin_cuti_khusus WHERE id = ? AND status IN ('disetujui','ditolak')"); mysqli_stmt_bind_param($stmt, 'i', $id); mysqli_stmt_execute($stmt); $hapus = mysqli_stmt_affected_rows($stmt) > 0; mysqli_stmt_close($stmt); if ($hapus) { catatAktivitas($conn, 'Menghapus cuti khusus ID ' . $id . '.'); header('Location: izin-cuti-khusus.php'); exit; } $pesan = 'Data tidak dapat dihapus sebelum memiliki keputusan akhir.'; }
    } elseif ($aksi === 'simpan') {
        if (!$bolehInput) $pesan = 'Hanya Admin dan Superadmin yang dapat menginput cuti khusus.';
        else {
            foreach (array_keys($form) as $kunci) $form[$kunci] = trim((string) ($_POST[$kunci] ?? ''));
            $karyawanId = (int) $form['karyawan_id']; $penggantiId = (int) $form['karyawan_pengganti_id']; $departemenForm = (int) $form['department_id'];
            $mulai = DateTimeImmutable::createFromFormat('!Y-m-d', $form['tanggal_mulai']); $selesai = DateTimeImmutable::createFromFormat('!Y-m-d', $form['tanggal_selesai']);
            $tanggalValid = $mulai !== false && $selesai !== false && $selesai >= $mulai;
            $totalHari = $tanggalValid ? hitungHariCutiKhusus($mulai, $selesai) : 0;
            if ($departemenForm <= 0 || $form['position'] === '' || $karyawanId <= 0 || $penggantiId <= 0 || !$tanggalValid || !in_array($form['deskripsi'], $opsiDeskripsi, true) || $form['nomor_kontak'] === '' || $totalHari < 1) $pesan = 'Semua data wajib diisi. Pilih rentang yang mencakup minimal satu hari kerja.';
            elseif (strlen($form['nomor_kontak']) > 50) $pesan = 'Nomor kontak maksimal 50 karakter.';
            elseif ($karyawanId === $penggantiId) $pesan = 'Karyawan pengganti harus berbeda dari karyawan yang mengajukan cuti.';
            else {
                $cek = mysqli_prepare($conn, 'SELECT id FROM karyawan WHERE id = ? AND department_id = ? AND position = ? LIMIT 1'); mysqli_stmt_bind_param($cek, 'iis', $karyawanId, $departemenForm, $form['position']); mysqli_stmt_execute($cek); $valid = mysqli_fetch_assoc(mysqli_stmt_get_result($cek)); mysqli_stmt_close($cek);
                $cekPengganti = mysqli_prepare($conn, 'SELECT id FROM karyawan WHERE id = ? AND department_id = ? AND id <> ? LIMIT 1'); mysqli_stmt_bind_param($cekPengganti, 'iii', $penggantiId, $departemenForm, $karyawanId); mysqli_stmt_execute($cekPengganti); $penggantiValid = mysqli_fetch_assoc(mysqli_stmt_get_result($cekPengganti)); mysqli_stmt_close($cekPengganti);
                if (!$valid || !$penggantiValid) $pesan = 'Karyawan atau pengganti tidak sesuai dengan departemen yang dipilih.';
                else { $pembuat = (int) $_SESSION['user']['id']; $mulaiDb = $mulai->format('Y-m-d'); $selesaiDb = $selesai->format('Y-m-d');
                    $stmt = mysqli_prepare($conn, 'INSERT INTO izin_cuti_khusus (karyawan_id, department_id, tanggal_mulai, tanggal_selesai, total_hari, deskripsi, nomor_kontak, karyawan_pengganti_id, dibuat_oleh_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'); mysqli_stmt_bind_param($stmt, 'iissdssii', $karyawanId, $departemenForm, $mulaiDb, $selesaiDb, $totalHari, $form['deskripsi'], $form['nomor_kontak'], $penggantiId, $pembuat);
                    if (mysqli_stmt_execute($stmt)) { $id = (int) mysqli_insert_id($conn); mysqli_stmt_close($stmt); catatAktivitas($conn, 'Membuat cuti khusus ID ' . $id . '.'); header('Location: izin-cuti-khusus.php'); exit; } mysqli_stmt_close($stmt); $pesan = 'Cuti khusus gagal disimpan.';
                }
            }
        }
    }
}

$departemenPilihan = ambilDepartemenPilihan($conn, roleOperasional() ? (int) $departmentId : null); $posisiPerDepartemen = ambilPosisiPerDepartemen($conn, roleOperasional() ? (int) $departmentId : null);
$dataKaryawan = []; $hasilKaryawan = mysqli_query($conn, 'SELECT id, emp_id, employee_name, position, department_id FROM karyawan WHERE department_id IS NOT NULL' . (roleOperasional() ? ' AND department_id = ' . (int) $departmentId : '') . ' ORDER BY employee_name'); while ($item = mysqli_fetch_assoc($hasilKaryawan)) $dataKaryawan[] = ['id' => (int) $item['id'], 'emp_id' => $item['emp_id'], 'nama' => $item['employee_name'], 'posisi' => $item['position'], 'department_id' => (int) $item['department_id']];
$where = roleOperasional() ? 'c.department_id = ' . (int) $departmentId : '1=1';
$daftar = mysqli_query($conn, "SELECT c.*, k.emp_id, k.employee_name, k.position, k.department, p.emp_id AS pengganti_emp_id, p.employee_name AS pengganti_nama, u.nama AS nama_pembuat, pe.role AS role_pemroses FROM izin_cuti_khusus c INNER JOIN karyawan k ON k.id = c.karyawan_id LEFT JOIN karyawan p ON p.id = c.karyawan_pengganti_id LEFT JOIN users u ON u.id = c.dibuat_oleh_user_id LEFT JOIN users pe ON pe.id = c.diproses_oleh_user_id WHERE {$where} ORDER BY c.created_at DESC");
$judulHalaman = 'Cuti Khusus'; $subjudulHalaman = 'Pengajuan cuti khusus dengan pilihan deskripsi yang terstandar.'; $halamanAktif = 'izin-cuti-khusus'; $kelasHalaman = 'izin-cuti'; require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<?php if ($pesan !== ''): ?><div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
<div class="overtime-notification-action">
    <a class="btn btn-primary" href="notifikasi-izin-cuti-khusus.php">🔔 Buka Notifikasi</a>
    <a class="btn export-excel-btn" href="export_izin_cuti_khusus.php">Export Excel</a>
</div>
<?php if ($bolehInput): ?><form method="POST" class="izin-entry-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="simpan"><section class="form-card izin-form-card"><div class="form-card-header"><h2>Tambah Cuti Khusus</h2><p>Pilih departemen, posisi, karyawan, dan alasan cuti khusus.</p></div><div class="form-body izin-form-fields"><div class="form-group"><label for="khusus-department">Departemen</label><select id="khusus-department" name="department_id" required><option value="">Pilih departemen</option><?php foreach ($departemenPilihan as $id => $nama): ?><option value="<?= (int) $id; ?>" <?= $form['department_id'] === (string) $id ? 'selected' : ''; ?>><?= htmlspecialchars($nama); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="khusus-position">Posisi</label><select id="khusus-position" name="position" required disabled><option value="">Pilih departemen terlebih dahulu</option></select></div><div class="form-group full-width"><label for="khusus-karyawan">Karyawan</label><select id="khusus-karyawan" name="karyawan_id" required disabled><option value="">Pilih departemen dan posisi terlebih dahulu</option></select></div><div class="form-group"><label for="khusus-mulai">Tanggal Awal</label><input id="khusus-mulai" name="tanggal_mulai" type="date" value="<?= htmlspecialchars($form['tanggal_mulai']); ?>" required></div><div class="form-group"><label for="khusus-selesai">Tanggal Akhir</label><input id="khusus-selesai" name="tanggal_selesai" type="date" value="<?= htmlspecialchars($form['tanggal_selesai']); ?>" required></div><div class="form-group"><label for="khusus-deskripsi">Deskripsi Cuti Khusus</label><select id="khusus-deskripsi" name="deskripsi" required><option value="">Pilih deskripsi</option><?php foreach ($opsiDeskripsi as $opsi): ?><option value="<?= htmlspecialchars($opsi); ?>" <?= $form['deskripsi'] === $opsi ? 'selected' : ''; ?>><?= htmlspecialchars($opsi); ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="khusus-kontak">Nomor Kontak</label><input id="khusus-kontak" name="nomor_kontak" maxlength="50" value="<?= htmlspecialchars($form['nomor_kontak']); ?>" required></div></div></section><section class="form-card replacement-form-card"><div class="form-card-header"><h2>Karyawan Pengganti</h2><p>Pilih karyawan dari departemen yang sama untuk menggantikan selama cuti khusus.</p></div><div class="form-body replacement-form-body"><div class="form-group"><label for="khusus-pengganti-department">Departemen</label><select id="khusus-pengganti-department" disabled><option value="">Pilih departemen</option></select></div><div class="form-group"><label for="khusus-pengganti-position">Posisi</label><select id="khusus-pengganti-position" disabled><option value="">Pilih departemen terlebih dahulu</option></select></div><div class="form-group full-width"><label for="khusus-pengganti">Karyawan Pengganti</label><select id="khusus-pengganti" name="karyawan_pengganti_id" required disabled><option value="">Pilih departemen dan posisi terlebih dahulu</option></select><p class="field-note">Hanya karyawan lain dari departemen yang sama yang dapat dipilih.</p></div><button class="btn btn-success replacement-submit" type="submit">Simpan Cuti Khusus</button></div></section></form><?php endif; ?>
<section class="data-card izin-data-card"><div class="data-card-header"><h2>Daftar Cuti Khusus</h2></div><div class="table-wrapper"><table><thead><tr><th>ID</th><th>Karyawan</th><th>Posisi</th><th>Departemen</th><th>Mulai</th><th>Selesai</th><th>Total Hari</th><th>Deskripsi</th><th>Kontak</th><th>Pengganti</th><th>Status</th><th>Dibuat Oleh</th><th>Aksi</th></tr></thead><tbody><?php if ($daftar && mysqli_num_rows($daftar)): while ($cuti = mysqli_fetch_assoc($daftar)): $tahap = (string) $cuti['tahap_persetujuan']; $bolehProses = $cuti['status'] === 'menunggu' && ($langsungSuperadmin || ($bolehSetuju && $tahap === $tahapRole)); ?><tr><td><?= (int) $cuti['id']; ?></td><td><?= htmlspecialchars($cuti['emp_id'] . ' - ' . $cuti['employee_name']); ?></td><td><?= htmlspecialchars($cuti['position']); ?></td><td><?= htmlspecialchars($cuti['department']); ?></td><td><?= htmlspecialchars($cuti['tanggal_mulai']); ?></td><td><?= htmlspecialchars($cuti['tanggal_selesai']); ?></td><td><?= number_format((float) $cuti['total_hari'], 1, ',', '.') ?> hari</td><td><?= htmlspecialchars($cuti['deskripsi']); ?></td><td><?= htmlspecialchars($cuti['nomor_kontak']); ?></td><td><?= !empty($cuti['pengganti_emp_id']) ? htmlspecialchars($cuti['pengganti_emp_id'] . ' - ' . $cuti['pengganti_nama']) : '-'; ?></td><td><span class="status-badge status-<?= htmlspecialchars($cuti['status']); ?>"><?= htmlspecialchars(labelStatusPersetujuanIzin($cuti['status'], $tahap, $cuti['role_pemroses'])); ?></span></td><td><?= htmlspecialchars($cuti['nama_pembuat']); ?></td><td class="izin-actions"><?php if ($bolehProses): ?><form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="keputusan"><input type="hidden" name="cuti_khusus_id" value="<?= (int) $cuti['id']; ?>"><input name="catatan" placeholder="Catatan/alasan penolakan"><button class="btn btn-success" name="keputusan" value="disetujui">Setujui</button><button class="btn btn-danger" name="keputusan" value="ditolak">Tolak</button></form><?php endif; ?><?php if ($role === 'superadmin'): ?><form method="POST" class="delete-izin-form" onsubmit="return confirm('Hapus data cuti khusus ini?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="cuti_khusus_id" value="<?= (int) $cuti['id']; ?>"><button class="btn btn-danger" type="submit">Hapus</button></form><?php endif; ?></td></tr><?php endwhile; else: ?><tr><td colspan="13" class="empty-table">Belum ada data cuti khusus.</td></tr><?php endif; ?></tbody></table></div></section>
<?php if ($bolehInput): ?><script>(() => { const employees = <?= json_encode($dataKaryawan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>, positions = <?= json_encode($posisiPerDepartemen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>; const department = document.getElementById('khusus-department'), position = document.getElementById('khusus-position'), employee = document.getElementById('khusus-karyawan'); const option=(value,label)=>{const item=document.createElement('option');item.value=String(value);item.textContent=label;return item;}; const isiKaryawan=()=>{const daftar=employees.filter(item=>String(item.department_id)===department.value&&item.posisi===position.value);employee.replaceChildren(option('',daftar.length?'Pilih karyawan':'Tidak ada karyawan'));daftar.forEach(item=>employee.append(option(item.id,`${item.emp_id} - ${item.nama}`)));employee.disabled=!daftar.length;}; department.addEventListener('change',()=>{const daftar=[...new Set(positions[department.value]||[])];position.replaceChildren(option('',daftar.length?'Pilih posisi':'Tidak ada posisi'));daftar.forEach(item=>position.append(option(item,item)));position.disabled=!daftar.length;employee.replaceChildren(option('','Pilih departemen dan posisi terlebih dahulu'));employee.disabled=true;});position.addEventListener('change',isiKaryawan); if(department.value) department.dispatchEvent(new Event('change')); })();</script><?php endif; ?>
<?php if ($bolehInput): ?><script>(() => {
    const employees = <?= json_encode($dataKaryawan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const positions = <?= json_encode($posisiPerDepartemen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const department = document.getElementById('khusus-department');
    const employee = document.getElementById('khusus-karyawan');
    const replacementDepartment = document.getElementById('khusus-pengganti-department');
    const replacementPosition = document.getElementById('khusus-pengganti-position');
    const replacement = document.getElementById('khusus-pengganti');
    const option = (value, label) => { const item = document.createElement('option'); item.value = String(value); item.textContent = label; return item; };
    const isiPengganti = () => {
        const daftar = employees.filter(item => String(item.department_id) === department.value && item.posisi === replacementPosition.value && String(item.id) !== employee.value);
        replacement.replaceChildren(option('', daftar.length ? 'Pilih karyawan pengganti' : 'Tidak ada karyawan yang sesuai'));
        daftar.forEach(item => replacement.append(option(item.id, `${item.emp_id} - ${item.nama}`)));
        replacement.disabled = !daftar.length;
    };
    const isiPosisiPengganti = () => {
        const daftar = [...new Set(positions[department.value] || [])];
        replacementPosition.replaceChildren(option('', daftar.length ? 'Pilih posisi' : 'Tidak ada posisi pada departemen ini'));
        daftar.forEach(item => replacementPosition.append(option(item, item)));
        replacementPosition.disabled = !daftar.length;
        replacement.replaceChildren(option('', 'Pilih posisi terlebih dahulu')); replacement.disabled = true;
    };
    department.addEventListener('change', () => {
        replacementDepartment.replaceChildren(option('', 'Pilih departemen'));
        if (!department.value) { replacementDepartment.disabled = true; replacementPosition.replaceChildren(option('', 'Pilih departemen terlebih dahulu')); replacementPosition.disabled = true; replacement.replaceChildren(option('', 'Pilih departemen dan posisi terlebih dahulu')); replacement.disabled = true; return; }
        replacementDepartment.append(option(department.value, department.options[department.selectedIndex].text));
        replacementDepartment.value = department.value; replacementDepartment.disabled = true; isiPosisiPengganti();
    });
    replacementPosition.addEventListener('change', isiPengganti);
    employee.addEventListener('change', () => { if (replacementPosition.value) isiPengganti(); });
    if (department.value) department.dispatchEvent(new Event('change'));
})();</script><?php endif; ?>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
