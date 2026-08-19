<?php

declare(strict_types=1);

require __DIR__ . '/koneksi.php';
require_once __DIR__ . '/fungsi/auth.php';
require_once __DIR__ . '/fungsi/audit.php';
require_once __DIR__ . '/fungsi/slip-gaji.php';

wajibRole('admin', 'superadmin');

if (!siapkanPenyimpananSlipGaji($conn)) {
    http_response_code(500);
    exit('Penyimpanan slip gaji tidak dapat disiapkan.');
}

$bulan = max(1, min(12, (int) ($_REQUEST['bulan'] ?? date('n'))));
$tahun = max(2000, min(2100, (int) ($_REQUEST['tahun'] ?? date('Y'))));
$departmentId = max(0, (int) ($_REQUEST['department_id'] ?? 0));
$posisi = trim((string) ($_REQUEST['position'] ?? ''));
$pesan = '';
$hasilBatch = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['karyawan_ids'] ?? [])),
        static fn (int $id): bool => $id > 0
    )));

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $pesan = 'Token keamanan tidak valid.';
    } elseif (!periodeSlipGajiValid($bulan, $tahun)) {
        $pesan = 'Periode slip tidak valid.';
    } elseif ($ids === []) {
        $pesan = 'Pilih minimal satu karyawan untuk diproses.';
    } else {
        set_time_limit(0);
        try {
            $periodeId = ambilAtauBuatPeriodeGaji($conn, $bulan, $tahun);
            $batchId = bin2hex(random_bytes(16));
            $pembuatId = (int) ($_SESSION['user']['id'] ?? 0);

            foreach ($ids as $karyawanId) {
                $hasilBatch[] = prosesSlipGajiKaryawan(
                    $conn,
                    $periodeId,
                    $karyawanId,
                    $bulan,
                    $tahun,
                    $batchId,
                    $pembuatId
                );
            }

            $jumlahBerhasil = count(array_filter($hasilBatch, static fn (array $hasil): bool => $hasil['berhasil']));
            $jumlahGagal = count($hasilBatch) - $jumlahBerhasil;
            catatAktivitas(
                $conn,
                sprintf(
                    'Memproses batch slip gaji periode %02d-%04d: %d berhasil dan %d gagal.',
                    $bulan,
                    $tahun,
                    $jumlahBerhasil,
                    $jumlahGagal
                )
            );
        } catch (Throwable $exception) {
            $pesan = 'Batch tidak dapat dimulai: ' . $exception->getMessage();
        }
    }
}

$departemen = [];
$hasilDepartemen = mysqli_query($conn, 'SELECT id, nama FROM master_departemen WHERE is_active = 1 ORDER BY nama');
if ($hasilDepartemen) {
    while ($row = mysqli_fetch_assoc($hasilDepartemen)) {
        $departemen[] = $row;
    }
}

$daftarPosisi = [];
$sqlPosisi = "SELECT DISTINCT position FROM karyawan WHERE TRIM(COALESCE(position, '')) <> ''";
if ($departmentId > 0) {
    $sqlPosisi .= ' AND department_id = ' . $departmentId;
}
$sqlPosisi .= ' ORDER BY position';
$hasilPosisi = mysqli_query($conn, $sqlPosisi);
if ($hasilPosisi) {
    while ($row = mysqli_fetch_assoc($hasilPosisi)) {
        $daftarPosisi[] = (string) $row['position'];
    }
}

$awalPeriode = sprintf('%04d-%02d-01', $tahun, $bulan);
$akhirPeriode = (new DateTimeImmutable($awalPeriode))->modify('last day of this month')->format('Y-m-d');
$sqlKaryawan = "SELECT k.id, k.emp_id, k.employee_name, k.position, k.department,
                       pg.id AS profil_id, pg.gaji_pokok,
                       (SELECT MAX(s.versi)
                        FROM slip_gaji s
                        INNER JOIN periode_gaji p ON p.id = s.periode_gaji_id
                        WHERE s.karyawan_id = k.id AND p.bulan = {$bulan} AND p.tahun = {$tahun}) AS versi_terakhir
                FROM karyawan k
                LEFT JOIN profil_gaji pg ON pg.id = (
                    SELECT pg2.id FROM profil_gaji pg2
                    WHERE pg2.karyawan_id = k.id
                      AND pg2.berlaku_mulai <= '{$akhirPeriode}'
                      AND (pg2.berlaku_sampai IS NULL OR pg2.berlaku_sampai >= '{$awalPeriode}')
                    ORDER BY pg2.berlaku_mulai DESC, pg2.id DESC LIMIT 1
                )
                WHERE ({$departmentId} = 0 OR k.department_id = {$departmentId})
                  AND (? = '' OR k.position = ?)
                  AND (k.date_of_hire IS NULL OR k.date_of_hire <= '{$akhirPeriode}')
                  AND (k.date_of_exit IS NULL OR k.date_of_exit >= '{$awalPeriode}')
                ORDER BY k.department, k.position, k.employee_name";
$stmtKaryawan = mysqli_prepare($conn, $sqlKaryawan);
mysqli_stmt_bind_param($stmtKaryawan, 'ss', $posisi, $posisi);
mysqli_stmt_execute($stmtKaryawan);
$hasilKaryawan = mysqli_stmt_get_result($stmtKaryawan);
$karyawan = [];
while ($row = mysqli_fetch_assoc($hasilKaryawan)) {
    $alasan = [];
    foreach (['emp_id' => 'ID karyawan', 'employee_name' => 'nama', 'position' => 'posisi', 'department' => 'departemen'] as $kolom => $label) {
        if (trim((string) ($row[$kolom] ?? '')) === '') {
            $alasan[] = $label . ' kosong';
        }
    }
    if (empty($row['profil_id'])) {
        $alasan[] = 'profil upah periode ini tidak tersedia';
    } elseif ((float) ($row['gaji_pokok'] ?? 0) <= 0) {
        $alasan[] = 'gaji pokok kosong/nol';
    }
    $row['validasi'] = $alasan === [] ? 'Siap diproses' : implode('; ', $alasan);
    $row['siap'] = $alasan === [];
    $karyawan[] = $row;
}
mysqli_stmt_close($stmtKaryawan);

$riwayatBatch = mysqli_query(
    $conn,
    "SELECT s.id, s.batch_id, s.versi, s.status, s.pesan_error, s.generated_at,
            s.employee_id_snapshot, s.nama_snapshot, p.bulan, p.tahun, u.nama AS nama_pembuat
     FROM slip_gaji s
     INNER JOIN periode_gaji p ON p.id = s.periode_gaji_id
     INNER JOIN users u ON u.id = s.generated_by
     WHERE s.batch_id <> ''
     ORDER BY s.generated_at DESC, s.id DESC
     LIMIT 50"
);

$judulHalaman = 'Batch Slip Gaji';
$subjudulHalaman = 'Buat dan distribusikan slip ke profil beberapa karyawan sekaligus.';
$halamanAktif = 'upah';
require __DIR__ . '/partials/atas.php';
?>

<?php if ($pesan !== ''): ?><div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>

<?php if ($hasilBatch !== []): ?>
    <?php
    $jumlahBerhasil = count(array_filter($hasilBatch, static fn (array $hasil): bool => $hasil['berhasil']));
    $jumlahGagal = count($hasilBatch) - $jumlahBerhasil;
    ?>
    <section class="data-card batch-result-card">
        <div class="data-card-header"><div><h2>Hasil Batch</h2><p><?= $jumlahBerhasil; ?> berhasil dan <?= $jumlahGagal; ?> gagal. Setiap hasil diproses secara terpisah.</p></div></div>
        <div class="table-wrapper"><table><thead><tr><th>Karyawan</th><th>Versi</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
        <?php foreach ($hasilBatch as $hasil): ?><tr>
            <td><?= htmlspecialchars((string) $hasil['nama']); ?></td>
            <td><?= (int) $hasil['versi'] > 0 ? 'v' . (int) $hasil['versi'] : '-'; ?></td>
            <td><span class="status-badge status-<?= $hasil['berhasil'] ? 'disetujui' : 'ditolak'; ?>"><?= $hasil['berhasil'] ? 'Berhasil' : 'Gagal'; ?></span></td>
            <td><?= htmlspecialchars((string) $hasil['pesan']); ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
<?php endif; ?>

<section class="data-card batch-filter-card">
    <div class="data-card-header"><div><h2>Filter Karyawan dan Periode</h2><p>Slip baru untuk periode yang sama otomatis disimpan sebagai versi revisi berikutnya.</p></div></div>
    <form method="GET" class="search-form batch-filter-form">
        <label>Bulan<select name="bulan" required><?php foreach (range(1, 12) as $nomor): ?><option value="<?= $nomor; ?>" <?= $bulan === $nomor ? 'selected' : ''; ?>><?= htmlspecialchars(namaBulanSlipGaji($nomor)); ?></option><?php endforeach; ?></select></label>
        <label>Tahun<input type="number" name="tahun" min="2000" max="2100" value="<?= $tahun; ?>" required></label>
        <label>Departemen<select name="department_id"><option value="0">Semua departemen</option><?php foreach ($departemen as $item): ?><option value="<?= (int) $item['id']; ?>" <?= $departmentId === (int) $item['id'] ? 'selected' : ''; ?>><?= htmlspecialchars((string) $item['nama']); ?></option><?php endforeach; ?></select></label>
        <label>Posisi<select name="position"><option value="">Semua posisi</option><?php foreach ($daftarPosisi as $item): ?><option value="<?= htmlspecialchars($item); ?>" <?= $posisi === $item ? 'selected' : ''; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select></label>
        <button class="btn btn-primary" type="submit">Tampilkan Karyawan</button>
        <a class="btn btn-secondary" href="upah.php">Kembali ke Upah</a>
    </form>
</section>

<section class="data-card batch-employee-card">
    <div class="data-card-header"><div><h2>Daftar Karyawan</h2><p><?= count($karyawan); ?> karyawan sesuai filter untuk periode <?= htmlspecialchars(namaBulanSlipGaji($bulan) . ' ' . $tahun); ?>.</p></div></div>
    <form method="POST" id="batch-slip-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
        <input type="hidden" name="bulan" value="<?= $bulan; ?>">
        <input type="hidden" name="tahun" value="<?= $tahun; ?>">
        <input type="hidden" name="department_id" value="<?= $departmentId; ?>">
        <input type="hidden" name="position" value="<?= htmlspecialchars($posisi); ?>">
        <div class="batch-selection-bar">
            <label><input type="checkbox" id="pilih-semua-slip"> Pilih semua</label>
            <button class="btn btn-success" type="submit" onclick="return confirm('Proses slip untuk seluruh karyawan yang dipilih?');">Proses Slip Terpilih</button>
        </div>
        <div class="table-wrapper"><table><thead><tr><th>Pilih</th><th>ID</th><th>Nama</th><th>Posisi</th><th>Departemen</th><th>Revisi Terakhir</th><th>Validasi Upah</th></tr></thead><tbody>
        <?php if ($karyawan === []): ?><tr><td colspan="7" class="empty-data">Tidak ada karyawan sesuai filter.</td></tr><?php endif; ?>
        <?php foreach ($karyawan as $item): ?><tr>
            <td><input class="pilih-slip" type="checkbox" name="karyawan_ids[]" value="<?= (int) $item['id']; ?>"></td>
            <td><?= htmlspecialchars((string) $item['emp_id']); ?></td>
            <td><?= htmlspecialchars((string) $item['employee_name']); ?></td>
            <td><?= htmlspecialchars((string) $item['position']); ?></td>
            <td><?= htmlspecialchars((string) $item['department']); ?></td>
            <td><?= (int) ($item['versi_terakhir'] ?? 0) > 0 ? 'v' . (int) $item['versi_terakhir'] : '-'; ?></td>
            <td class="batch-validation <?= $item['siap'] ? 'is-ready' : 'is-error'; ?>"><?= htmlspecialchars((string) $item['validasi']); ?></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
    </form>
</section>

<section class="data-card batch-history-card">
    <div class="data-card-header"><div><h2>Riwayat Hasil Terbaru</h2><p>Status setiap karyawan dicatat terpisah, termasuk kegagalan.</p></div></div>
    <div class="table-wrapper"><table><thead><tr><th>Periode</th><th>Karyawan</th><th>Versi</th><th>Status</th><th>Keterangan</th><th>Pembuat</th><th>Waktu</th></tr></thead><tbody>
    <?php if (!$riwayatBatch || mysqli_num_rows($riwayatBatch) === 0): ?><tr><td colspan="7" class="empty-data">Belum ada riwayat batch.</td></tr><?php else: ?>
        <?php while ($item = mysqli_fetch_assoc($riwayatBatch)): ?><tr>
            <td><?= htmlspecialchars(namaBulanSlipGaji((int) $item['bulan']) . ' ' . (int) $item['tahun']); ?></td>
            <td><?= htmlspecialchars((string) $item['employee_id_snapshot'] . ' - ' . (string) $item['nama_snapshot']); ?></td>
            <td>v<?= (int) $item['versi']; ?></td>
            <td><span class="status-badge status-<?= $item['status'] === 'berhasil' ? 'disetujui' : 'ditolak'; ?>"><?= $item['status'] === 'berhasil' ? 'Berhasil' : 'Gagal'; ?></span></td>
            <td><?= htmlspecialchars((string) ($item['pesan_error'] ?: 'Slip tersimpan pada profil karyawan.')); ?></td>
            <td><?= htmlspecialchars((string) $item['nama_pembuat']); ?></td>
            <td><?= htmlspecialchars((string) $item['generated_at']); ?></td>
        </tr><?php endwhile; ?>
    <?php endif; ?></tbody></table></div>
</section>

<script>
document.getElementById('pilih-semua-slip')?.addEventListener('change', function () {
    document.querySelectorAll('.pilih-slip').forEach(checkbox => { checkbox.checked = this.checked; });
});
</script>
<?php require __DIR__ . '/partials/bawah.php'; ?>
