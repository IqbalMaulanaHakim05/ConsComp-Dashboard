<?php
declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Leave/alur-persetujuan-izin.php';
require_once __DIR__ . '/../Services/Leave/cuti-khusus.php';
require_once __DIR__ . '/../Utils/xlsx-builder.php';

wajibRole('pic', 'koordinator', 'manager', 'admin', 'superadmin');
if (!siapkanTabelIzinCutiKhusus($conn)) exit('Tabel cuti khusus tidak dapat disiapkan.');

$where = roleOperasional() ? 'c.department_id = ' . (int) departmentIdPengguna() : '1=1';
$kolom = [
    'id' => ['label' => 'ID', 'sql' => 'c.id'],
    'emp_id' => ['label' => 'ID Karyawan', 'sql' => 'k.emp_id'],
    'nama' => ['label' => 'Nama Karyawan', 'sql' => 'k.employee_name'],
    'posisi' => ['label' => 'Posisi', 'sql' => 'k.position'],
    'departemen' => ['label' => 'Departemen', 'sql' => 'k.department'],
    'tanggal_mulai' => ['label' => 'Tanggal Awal', 'sql' => 'c.tanggal_mulai'],
    'tanggal_selesai' => ['label' => 'Tanggal Akhir', 'sql' => 'c.tanggal_selesai'],
    'total_hari' => ['label' => 'Total Hari', 'sql' => 'c.total_hari'],
    'deskripsi' => ['label' => 'Deskripsi Cuti Khusus', 'sql' => 'c.deskripsi'],
    'nomor_kontak' => ['label' => 'Nomor Kontak', 'sql' => 'c.nomor_kontak'],
    'pengganti' => ['label' => 'Karyawan Pengganti', 'sql' => 'p.employee_name'],
    'status' => ['label' => 'Status', 'sql' => 'c.status'],
    'dibuat_oleh' => ['label' => 'Dibuat Oleh', 'sql' => 'pembuat.nama'],
    'diproses_oleh' => ['label' => 'Diproses Oleh', 'sql' => 'pemroses.nama'],
    'waktu_pengajuan' => ['label' => 'Waktu Pengajuan', 'sql' => 'c.created_at'],
];

$pilihanKolom = $_GET['kolom'] ?? array_keys($kolom);
if (!is_array($pilihanKolom)) $pilihanKolom = [$pilihanKolom];
$pilihanKolom = array_values(array_intersect(array_keys($kolom), $pilihanKolom));
if ($pilihanKolom === []) $pilihanKolom = array_keys($kolom);

$batasPilihan = [10, 25, 50, 100, 250, 'semua'];
$batas = $_GET['batas_export'] ?? 'semua';
if ($batas !== 'semua') $batas = max(1, min(10000, (int) $batas));
if (!in_array($batas, $batasPilihan, true) && $batas !== 'semua') $batas = 'semua';

$sort = (string) ($_GET['sort_export'] ?? 'waktu_pengajuan');
if (!isset($kolom[$sort])) $sort = 'waktu_pengajuan';
$arah = strtoupper((string) ($_GET['arah_export'] ?? 'DESC'));
if (!in_array($arah, ['ASC', 'DESC'], true)) $arah = 'DESC';
$limit = $batas === 'semua' ? '' : ' LIMIT ' . (int) $batas;

$query = mysqli_query(
    $conn,
    "SELECT c.*, k.emp_id, k.employee_name, k.position, k.department,
            p.emp_id AS pengganti_emp_id, p.employee_name AS pengganti_nama,
            pembuat.nama AS nama_pembuat, pemroses.nama AS nama_pemroses, pemroses.role AS role_pemroses
     FROM izin_cuti_khusus c
     INNER JOIN karyawan k ON k.id = c.karyawan_id
     LEFT JOIN karyawan p ON p.id = c.karyawan_pengganti_id
     LEFT JOIN users pembuat ON pembuat.id = c.dibuat_oleh_user_id
     LEFT JOIN users pemroses ON pemroses.id = c.diproses_oleh_user_id
     WHERE {$where}
     ORDER BY {$kolom[$sort]['sql']} {$arah}, c.id DESC{$limit}"
);
if (!$query) {
    http_response_code(500);
    exit('Data cuti khusus gagal diproses: ' . mysqli_error($conn));
}

$data = [];
while ($item = mysqli_fetch_assoc($query)) {
    $nilai = [
        'id' => (int) $item['id'],
        'emp_id' => (string) ($item['emp_id'] ?? ''),
        'nama' => (string) ($item['employee_name'] ?? ''),
        'posisi' => (string) ($item['position'] ?? ''),
        'departemen' => (string) ($item['department'] ?? ''),
        'tanggal_mulai' => (string) ($item['tanggal_mulai'] ?? ''),
        'tanggal_selesai' => (string) ($item['tanggal_selesai'] ?? ''),
        'total_hari' => number_format((float) $item['total_hari'], 1, ',', '.') . ' hari',
        'deskripsi' => (string) ($item['deskripsi'] ?? ''),
        'nomor_kontak' => (string) ($item['nomor_kontak'] ?? ''),
        'pengganti' => trim((string) (($item['pengganti_emp_id'] ?? '') . ' - ' . ($item['pengganti_nama'] ?? '')), ' -'),
        'status' => labelStatusPersetujuanIzin((string) $item['status'], (string) ($item['tahap_persetujuan'] ?? ''), (string) ($item['role_pemroses'] ?? '')),
        'dibuat_oleh' => (string) ($item['nama_pembuat'] ?? '-'),
        'diproses_oleh' => (string) (($item['nama_pemroses'] ?? '') ?: '-'),
        'waktu_pengajuan' => (string) ($item['created_at'] ?? ''),
    ];
    $data[] = $nilai;
}

$headers = array_map(static fn (string $kunci): string => $kolom[$kunci]['label'], $pilihanKolom);
$rows = array_map(static fn (array $nilai): array => array_map(static fn (string $kunci) => $nilai[$kunci], $pilihanKolom), $data);
if (isset($_GET['download'])) unduhSpreadsheetXlsx('laporan-cuti-khusus-' . date('Y-m-d'), 'Cuti Khusus', $headers, $rows);

$judulHalaman = 'Export Cuti Khusus';
$subjudulHalaman = 'Pilih jumlah data, urutan, dan kolom sebelum mengunduh Excel.';
$halamanAktif = 'izin-cuti-khusus';
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="form-card export-options-card">
    <div class="form-card-header"><h2>Opsi Export Cuti Khusus</h2><p><?= count($data); ?> data akan diekspor sesuai cakupan akses.</p></div>
    <div class="form-body">
        <form method="GET" class="export-options-form">
            <div class="form-group"><label for="batas_export">Jumlah data</label><select id="batas_export" name="batas_export"><?php foreach ($batasPilihan as $pilihan): ?><option value="<?= $pilihan; ?>" <?= (string) $batas === (string) $pilihan ? 'selected' : ''; ?>><?= $pilihan === 'semua' ? 'Semua data' : 'Maksimal ' . $pilihan . ' data'; ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="sort_export">Urutkan berdasarkan</label><select id="sort_export" name="sort_export"><?php foreach ($kolom as $kunci => $kolomItem): ?><option value="<?= $kunci; ?>" <?= $sort === $kunci ? 'selected' : ''; ?>><?= htmlspecialchars($kolomItem['label']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="arah_export">Arah urutan</label><select id="arah_export" name="arah_export"><option value="ASC" <?= $arah === 'ASC' ? 'selected' : ''; ?>>Naik (A–Z / lama ke baru)</option><option value="DESC" <?= $arah === 'DESC' ? 'selected' : ''; ?>>Turun (Z–A / baru ke lama)</option></select></div>
            <fieldset class="export-columns-fieldset"><legend>Kolom yang diekspor</legend><?php foreach ($kolom as $kunci => $kolomItem): ?><label><input type="checkbox" name="kolom[]" value="<?= $kunci; ?>" <?= in_array($kunci, $pilihanKolom, true) ? 'checked' : ''; ?>> <?= htmlspecialchars($kolomItem['label']); ?></label><?php endforeach; ?></fieldset>
            <div class="form-actions"><a class="btn btn-secondary" href="izin-cuti-khusus.php">Batal</a><button class="btn btn-success" type="submit">Terapkan Opsi</button><button class="btn btn-primary" type="submit" name="download" value="1">Unduh Excel</button></div>
        </form>
    </div>
</section>
<section class="data-card export-preview-card">
    <div class="data-card-header"><h2>Pratinjau Export Cuti Khusus</h2></div>
    <div class="table-wrapper"><table><thead><tr><?php foreach ($headers as $header): ?><th><?= htmlspecialchars($header); ?></th><?php endforeach; ?></tr></thead><tbody><?php if ($rows !== []): ?><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $cell): ?><td><?= nl2br(htmlspecialchars((string) $cell)); ?></td><?php endforeach; ?></tr><?php endforeach; ?><?php else: ?><tr><td colspan="<?= count($headers); ?>" class="empty-table">Belum ada data cuti khusus untuk diekspor.</td></tr><?php endif; ?></tbody></table></div>
</section>
<script>
(() => {
    const form = document.querySelector('.export-options-form');
    if (!form) return;
    form.querySelectorAll('select, input[type="checkbox"]').forEach(field => field.addEventListener('change', () => {
        if (field.name === 'kolom[]' && !form.querySelectorAll('input[name="kolom[]"]:checked').length) { field.checked = true; return; }
        form.querySelectorAll('button[name="download"]').forEach(button => button.removeAttribute('name'));
        form.submit();
    }));
})();
</script>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
