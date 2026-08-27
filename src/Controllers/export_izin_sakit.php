<?php
declare(strict_types=1);
require __DIR__ . '/../../config/database.php'; require_once __DIR__ . '/../Services/Auth/auth.php'; require_once __DIR__ . '/../Services/Leave/alur-persetujuan-izin.php'; require_once __DIR__ . '/../Utils/xlsx-builder.php';
wajibRole('pic', 'koordinator', 'manager', 'admin', 'superadmin');
$where = roleOperasional() ? 's.department_id = ' . (int) departmentIdPengguna() : '1=1';
function labelDurasiSakitExport(int $menit): string { $hari = intdiv($menit, 1440); $sisa = $menit % 1440; $jam = intdiv($sisa, 60); $m = $sisa % 60; return trim(($hari ? $hari . ' hari ' : '') . ($jam ? $jam . ' jam ' : '') . ($m || (!$hari && !$jam) ? $m . ' menit' : '')); }
$kolom = ['nama'=>['label'=>'Nama Karyawan','sql'=>'k.employee_name'], 'mulai'=>['label'=>'Mulai','sql'=>'s.mulai_at'], 'selesai'=>['label'=>'Selesai','sql'=>'s.selesai_at'], 'durasi'=>['label'=>'Durasi','sql'=>'s.total_menit'], 'keterangan'=>['label'=>'Keterangan','sql'=>'s.deskripsi'], 'kontak'=>['label'=>'Kontak','sql'=>'s.nomor_kontak'], 'pengganti'=>['label'=>'Pengganti','sql'=>'p.employee_name'], 'status'=>['label'=>'Status','sql'=>'s.status'], 'waktu_pengajuan'=>['label'=>'Waktu Pengajuan','sql'=>'s.created_at']];
$pilihanKolom = $_GET['kolom'] ?? array_keys($kolom); if (!is_array($pilihanKolom)) $pilihanKolom = [$pilihanKolom]; $pilihanKolom = array_values(array_intersect(array_keys($kolom), $pilihanKolom)); if ($pilihanKolom === []) $pilihanKolom = array_keys($kolom);
$batas = (string) ($_GET['batas_export'] ?? 'semua'); $limit = $batas === 'semua' ? '' : ' LIMIT ' . max(1, min(10000, (int) $batas)); $sort = (string) ($_GET['sort_export'] ?? 'waktu_pengajuan'); if (!isset($kolom[$sort])) $sort = 'waktu_pengajuan'; $arah = strtoupper((string) ($_GET['arah_export'] ?? 'DESC')); if (!in_array($arah, ['ASC','DESC'], true)) $arah = 'DESC';
$result = mysqli_query($conn, "SELECT k.employee_name, s.mulai_at, s.selesai_at, s.total_menit, s.deskripsi, s.nomor_kontak, p.employee_name AS pengganti, s.status, s.created_at FROM izin_sakit s INNER JOIN karyawan k ON k.id = s.karyawan_id INNER JOIN karyawan p ON p.id = s.karyawan_pengganti_id WHERE {$where} ORDER BY {$kolom[$sort]['sql']} {$arah}, s.id DESC{$limit}");
$rows = []; while ($result && ($row = mysqli_fetch_assoc($result))) { $nilai = ['nama'=>$row['employee_name'],'mulai'=>$row['mulai_at'],'selesai'=>$row['selesai_at'],'durasi'=>labelDurasiSakitExport((int)$row['total_menit']),'keterangan'=>$row['deskripsi'],'kontak'=>$row['nomor_kontak'],'pengganti'=>$row['pengganti'],'status'=>labelStatusPersetujuanIzin((string)$row['status'],'selesai'),'waktu_pengajuan'=>$row['created_at']]; $rows[] = array_map(static fn($k) => $nilai[$k], $pilihanKolom); }
$headers = array_map(static fn($k) => $kolom[$k]['label'], $pilihanKolom);
if (isset($_GET['download'])) unduhSpreadsheetXlsx('laporan-izin-sakit-' . date('Y-m-d'), 'Izin Sakit', $headers, $rows);
$judulHalaman = 'Export Izin Sakit'; $subjudulHalaman = 'Pratinjau data izin sakit sebelum diunduh.'; $halamanAktif = 'izin-sakit';
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="form-card export-options-card"><div class="form-card-header"><h2>Opsi Export Izin Sakit</h2><p><?= count($rows); ?> data akan diekspor sesuai opsi.</p></div><div class="form-body"><form method="GET" class="export-options-form"><div class="form-group"><label>Jumlah data</label><select name="batas_export"><option value="semua">Semua data</option><?php foreach ([10,25,50,100] as $n): ?><option value="<?= $n; ?>" <?= $batas === (string)$n ? 'selected' : ''; ?>>Maksimal <?= $n; ?> data</option><?php endforeach; ?></select></div><div class="form-group"><label>Urutkan berdasarkan</label><select name="sort_export"><?php foreach ($kolom as $k=>$v): ?><option value="<?= $k; ?>" <?= $sort === $k ? 'selected' : ''; ?>><?= htmlspecialchars($v['label']); ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Arah urutan</label><select name="arah_export"><option value="DESC" <?= $arah==='DESC'?'selected':''; ?>>Turun (baru ke lama)</option><option value="ASC" <?= $arah==='ASC'?'selected':''; ?>>Naik (lama ke baru)</option></select></div><fieldset class="export-columns-fieldset"><legend>Kolom yang diekspor</legend><?php foreach ($kolom as $k=>$v): ?><label><input type="checkbox" name="kolom[]" value="<?= $k; ?>" <?= in_array($k,$pilihanKolom,true)?'checked':''; ?>> <?= htmlspecialchars($v['label']); ?></label><?php endforeach; ?></fieldset><div class="form-actions"><a class="btn btn-secondary" href="izin-sakit.php">Batal</a><button class="btn btn-primary" type="submit" name="download" value="1">Unduh Excel</button></div></form></div></section>
<section class="data-card export-preview-card"><div class="data-card-header"><h2>Pratinjau Export Izin Sakit</h2></div><div class="table-wrapper"><table><thead><tr><?php foreach ($headers as $header): ?><th><?= htmlspecialchars($header); ?></th><?php endforeach; ?></tr></thead><tbody><?php if ($rows !== []): foreach ($rows as $row): ?><tr><?php foreach ($row as $cell): ?><td><?= nl2br(htmlspecialchars((string) $cell)); ?></td><?php endforeach; ?></tr><?php endforeach; else: ?><tr><td colspan="8" class="empty-table">Belum ada data izin sakit untuk diekspor.</td></tr><?php endif; ?></tbody></table></div></section>
<script>
(() => {
    const form = document.querySelector('.export-options-form');
    if (!form) return;
    form.querySelectorAll('select, input[type="checkbox"]').forEach(field => field.addEventListener('change', () => {
        if (field.matches('input[type="checkbox"]') && !form.querySelectorAll('input[name="kolom[]"]:checked').length) {
            field.checked = true;
            return;
        }
        form.querySelectorAll('button[name="download"]').forEach(button => button.removeAttribute('name'));
        form.submit();
    }));
})();
</script>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
