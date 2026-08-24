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
$batasDiizinkan = [25, 50, 100];
$batasDefault = 25;
$batasParam = (string) ($_REQUEST['batas'] ?? $batasDefault);
$tanpaBatas = ($batasParam === 'semua');
if ($tanpaBatas) {
    $batas = null;
} else {
    $batas = (int) $batasParam;
    if (!in_array($batas, $batasDiizinkan, true)) {
        $batas = $batasDefault;
    }
}
$halaman = max(1, (int) ($_REQUEST['hal'] ?? 1));
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

$kondisiKaryawan = "({$departmentId} = 0 OR k.department_id = {$departmentId})
                  AND (? = '' OR k.position = ?)
                  AND (k.date_of_hire IS NULL OR k.date_of_hire <= '{$akhirPeriode}')
                  AND (k.date_of_exit IS NULL OR k.date_of_exit >= '{$awalPeriode}')";

$stmtHitung = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM karyawan k WHERE {$kondisiKaryawan}");
mysqli_stmt_bind_param($stmtHitung, 'ss', $posisi, $posisi);
mysqli_stmt_execute($stmtHitung);
$totalCocok = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmtHitung))['total'] ?? 0);
mysqli_stmt_close($stmtHitung);

if ($tanpaBatas) {
    $totalHalaman = 1;
    $halaman = 1;
    $offset = 0;
    $klausaLimit = '';
} else {
    $totalHalaman = max(1, (int) ceil($totalCocok / $batas));
    if ($halaman > $totalHalaman) {
        $halaman = $totalHalaman;
    }
    $offset = ($halaman - 1) * $batas;
    $klausaLimit = " LIMIT {$batas} OFFSET {$offset}";
}

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
                WHERE {$kondisiKaryawan}
                ORDER BY k.department, k.position, k.employee_name{$klausaLimit}";
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

$idHalamanIni = array_map(static fn (array $item): int => (int) $item['id'], $karyawan);
$mulai = $totalCocok > 0 ? ($offset + 1) : 0;
$sampai = $offset + count($karyawan);

$semuaId = [];
if (!$tanpaBatas && $totalHalaman > 1) {
    $stmtSemuaId = mysqli_prepare($conn, "SELECT k.id FROM karyawan k WHERE {$kondisiKaryawan} ORDER BY k.id");
    mysqli_stmt_bind_param($stmtSemuaId, 'ss', $posisi, $posisi);
    mysqli_stmt_execute($stmtSemuaId);
    $hasilSemuaId = mysqli_stmt_get_result($stmtSemuaId);
    while ($row = mysqli_fetch_assoc($hasilSemuaId)) {
        $semuaId[] = (int) $row['id'];
    }
    mysqli_stmt_close($stmtSemuaId);
} else {
    $semuaId = $idHalamanIni;
}

$batasRiwayatParam = (string) ($_REQUEST['batas_riwayat'] ?? $batasDefault);
$tanpaBatasRiwayat = ($batasRiwayatParam === 'semua');
if ($tanpaBatasRiwayat) {
    $batasRiwayat = null;
} else {
    $batasRiwayat = (int) $batasRiwayatParam;
    if (!in_array($batasRiwayat, $batasDiizinkan, true)) {
        $batasRiwayat = $batasDefault;
    }
}
$halamanRiwayat = max(1, (int) ($_REQUEST['hal_riwayat'] ?? 1));

$totalRiwayat = 0;
$queryTotalRiwayat = mysqli_query($conn, "SELECT COUNT(*) AS total FROM slip_gaji WHERE batch_id <> ''");
if ($queryTotalRiwayat) {
    $totalRiwayat = (int) (mysqli_fetch_assoc($queryTotalRiwayat)['total'] ?? 0);
}

if ($tanpaBatasRiwayat) {
    $totalHalamanRiwayat = 1;
    $halamanRiwayat = 1;
    $offsetRiwayat = 0;
    $klausaLimitRiwayat = '';
} else {
    $totalHalamanRiwayat = max(1, (int) ceil($totalRiwayat / $batasRiwayat));
    if ($halamanRiwayat > $totalHalamanRiwayat) {
        $halamanRiwayat = $totalHalamanRiwayat;
    }
    $offsetRiwayat = ($halamanRiwayat - 1) * $batasRiwayat;
    $klausaLimitRiwayat = " LIMIT {$batasRiwayat} OFFSET {$offsetRiwayat}";
}

$riwayatBatch = mysqli_query(
    $conn,
    "SELECT s.id, s.batch_id, s.versi, s.status, s.pesan_error, s.generated_at,
            s.employee_id_snapshot, s.nama_snapshot, p.bulan, p.tahun, u.nama AS nama_pembuat
     FROM slip_gaji s
     INNER JOIN periode_gaji p ON p.id = s.periode_gaji_id
     INNER JOIN users u ON u.id = s.generated_by
     WHERE s.batch_id <> ''
     ORDER BY s.generated_at DESC, s.id DESC{$klausaLimitRiwayat}"
);
$jumlahRiwayat = $riwayatBatch ? mysqli_num_rows($riwayatBatch) : 0;
$mulaiRiwayat = $jumlahRiwayat > 0 ? ($offsetRiwayat + 1) : 0;
$sampaiRiwayat = $offsetRiwayat + $jumlahRiwayat;

$paramHalaman = [
    'bulan' => $bulan, 'tahun' => $tahun, 'department_id' => $departmentId,
    'position' => $posisi,
    'batas' => $tanpaBatas ? 'semua' : $batas,
    'hal' => $halaman,
    'batas_riwayat' => $tanpaBatasRiwayat ? 'semua' : $batasRiwayat,
    'hal_riwayat' => $halamanRiwayat,
];

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
        <div class="table-wrapper"><table><thead><tr><th>Karyawan</th><th>Versi</th><th>Status</th><th>Keterangan</th><th>Slip PDF</th></tr></thead><tbody>
        <?php foreach ($hasilBatch as $hasil): ?><tr>
            <td><?= htmlspecialchars((string) $hasil['nama']); ?></td>
            <td><?= (int) $hasil['versi'] > 0 ? 'v' . (int) $hasil['versi'] : '-'; ?></td>
            <td><span class="status-badge status-<?= $hasil['berhasil'] ? 'disetujui' : 'ditolak'; ?>"><?= $hasil['berhasil'] ? 'Berhasil' : 'Gagal'; ?></span></td>
            <td><?= htmlspecialchars((string) $hasil['pesan']); ?></td>
            <td>
                <?php if (!empty($hasil['berhasil']) && !empty($hasil['slip_id'])): ?>
                    <a class="btn btn-primary" target="_blank" rel="noopener noreferrer" href="fungsi/lihat-slip-gaji.php?id=<?= (int) $hasil['slip_id']; ?>">Buka Slip PDF ↗</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
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
        <label>Tampilkan<select name="batas"><?php foreach ($batasDiizinkan as $opsiBatas): ?><option value="<?= $opsiBatas; ?>" <?= (!$tanpaBatas && $batas === $opsiBatas) ? 'selected' : ''; ?>><?= $opsiBatas; ?> baris</option><?php endforeach; ?><option value="semua" <?= $tanpaBatas ? 'selected' : ''; ?>>Semua</option></select></label>
        <button class="btn btn-primary" type="submit">Tampilkan Karyawan</button>
        <a class="btn btn-secondary" href="upah.php">Kembali ke Upah</a>
    </form>
</section>

<section class="data-card batch-employee-card">
    <div class="data-card-header"><div><h2>Daftar Karyawan</h2><p>Menampilkan baris <strong><?= $mulai; ?>&ndash;<?= $sampai; ?></strong> dari <strong><?= $totalCocok; ?></strong> karyawan untuk periode <?= htmlspecialchars(namaBulanSlipGaji($bulan) . ' ' . $tahun); ?>.</p></div></div>
    <form method="POST" id="batch-slip-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
        <input type="hidden" name="bulan" value="<?= $bulan; ?>">
        <input type="hidden" name="tahun" value="<?= $tahun; ?>">
        <input type="hidden" name="department_id" value="<?= $departmentId; ?>">
        <input type="hidden" name="position" value="<?= htmlspecialchars($posisi); ?>">
        <div class="batch-selection-bar">
            <div class="batch-selection-area">
                <div class="batch-name-search search-form">
                    <label for="batch-employee-search">Cari nama karyawan</label>
                    <input type="search" id="batch-employee-search" placeholder="Ketik nama karyawan" autocomplete="off">
                    <span id="batch-name-search-count" aria-live="polite"></span>
                </div>
                <div class="batch-selection-controls">
                    <div class="batch-live-count" id="batch-live-count">
                        <span class="batch-count-badge" id="batch-count-number">0</span>
                        <span class="batch-count-label">karyawan dipilih</span>
                    </div>
                    <label><input type="checkbox" id="pilih-semua-slip"> Pilih semua</label>
                </div>
            </div>
            <button class="btn btn-success" type="submit" id="btn-proses-batch">Proses Slip Terpilih</button>
        </div>
        <div class="table-wrapper"><table><thead><tr><th>Pilih</th><th>ID</th><th>Nama</th><th>Posisi</th><th>Departemen</th><th>Revisi Terakhir</th><th>Validasi Upah</th></tr></thead><tbody>
        <?php if ($karyawan === []): ?><tr><td colspan="7" class="empty-data">Tidak ada karyawan sesuai filter.</td></tr><?php endif; ?>
        <?php foreach ($karyawan as $item): ?><tr data-employee-name="<?= htmlspecialchars((string) $item['employee_name'], ENT_QUOTES, 'UTF-8'); ?>">
            <td><input class="pilih-slip" type="checkbox" name="karyawan_ids[]" value="<?= (int) $item['id']; ?>"></td>
            <td><?= htmlspecialchars((string) $item['emp_id']); ?></td>
            <td><?= htmlspecialchars((string) $item['employee_name']); ?></td>
            <td><?= htmlspecialchars((string) $item['position']); ?></td>
            <td><?= htmlspecialchars((string) $item['department']); ?></td>
            <td><?= (int) ($item['versi_terakhir'] ?? 0) > 0 ? 'v' . (int) $item['versi_terakhir'] : '-'; ?></td>
            <td class="batch-validation <?= $item['siap'] ? 'is-ready' : 'is-error'; ?>"><?= htmlspecialchars((string) $item['validasi']); ?></td>
        </tr><?php endforeach; ?>
        <?php if ($karyawan !== []): ?><tr id="batch-name-no-result" hidden><td colspan="7" class="empty-data">Tidak ada karyawan dengan nama tersebut.</td></tr><?php endif; ?>
        </tbody></table></div>
        <div id="hidden-ids-container"></div>
    </form>
</section>

<?php if (!$tanpaBatas && $totalHalaman > 1): ?>
    <div class="pagination" style="max-width:1280px;margin:0 auto 24px;">
        <div class="pagination-info">
            Halaman <strong><?= $halaman; ?></strong> dari <strong><?= $totalHalaman; ?></strong>
        </div>
        <div class="pagination-nav">
            <?php if ($halaman > 1): ?>
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($paramHalaman, ['hal' => $halaman - 1]))); ?>">&larr; Sebelumnya</a>
            <?php else: ?>
                <span class="disabled">&larr; Sebelumnya</span>
            <?php endif; ?>
            <?php if ($halaman < $totalHalaman): ?>
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($paramHalaman, ['hal' => $halaman + 1]))); ?>">Berikutnya &rarr;</a>
            <?php else: ?>
                <span class="disabled">Berikutnya &rarr;</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$paramTanpaRiwayat = $paramHalaman;
unset($paramTanpaRiwayat['batas_riwayat'], $paramTanpaRiwayat['hal_riwayat']);
$queryTanpaRiwayat = htmlspecialchars(http_build_query($paramTanpaRiwayat));
?>
<section class="data-card batch-history-card">
    <div class="data-card-header"><div>
        <h2>Riwayat Hasil Terbaru</h2>
        <p>Menampilkan baris <strong><?= $mulaiRiwayat; ?>&ndash;<?= $sampaiRiwayat; ?></strong> dari <strong><?= $totalRiwayat; ?></strong> riwayat.</p>
    </div>
    <select onchange="location.href='?<?= $queryTanpaRiwayat; ?>&batas_riwayat='+this.value" title="Jumlah baris riwayat"><?php foreach ($batasDiizinkan as $opsiBatas): ?><option value="<?= $opsiBatas; ?>" <?= (!$tanpaBatasRiwayat && $batasRiwayat === $opsiBatas) ? 'selected' : ''; ?>><?= $opsiBatas; ?> baris</option><?php endforeach; ?><option value="semua" <?= $tanpaBatasRiwayat ? 'selected' : ''; ?>>Semua</option></select>
    </div>
    <div class="table-wrapper"><table><thead><tr><th>Periode</th><th>Karyawan</th><th>Versi</th><th>Status</th><th>Keterangan</th><th>Slip PDF</th><th>Pembuat</th><th>Waktu</th></tr></thead><tbody>
    <?php if ($jumlahRiwayat === 0): ?><tr><td colspan="8" class="empty-data">Belum ada riwayat batch.</td></tr><?php else: ?>
        <?php while ($item = mysqli_fetch_assoc($riwayatBatch)): ?><tr>
            <td><?= htmlspecialchars(namaBulanSlipGaji((int) $item['bulan']) . ' ' . (int) $item['tahun']); ?></td>
            <td><?= htmlspecialchars((string) $item['employee_id_snapshot'] . ' - ' . (string) $item['nama_snapshot']); ?></td>
            <td>v<?= (int) $item['versi']; ?></td>
            <td><span class="status-badge status-<?= $item['status'] === 'berhasil' ? 'disetujui' : 'ditolak'; ?>"><?= $item['status'] === 'berhasil' ? 'Berhasil' : 'Gagal'; ?></span></td>
            <td><?= htmlspecialchars((string) ($item['pesan_error'] ?: 'Slip tersimpan pada profil karyawan.')); ?></td>
            <td>
                <?php if ($item['status'] === 'berhasil'): ?>
                    <a class="btn btn-primary" target="_blank" rel="noopener noreferrer" href="fungsi/lihat-slip-gaji.php?id=<?= (int) $item['id']; ?>">Buka Slip PDF ↗</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars((string) $item['nama_pembuat']); ?></td>
            <td><?= htmlspecialchars((string) $item['generated_at']); ?></td>
        </tr><?php endwhile; ?>
    <?php endif; ?></tbody></table></div>
</section>

<?php if (!$tanpaBatasRiwayat && $totalHalamanRiwayat > 1): ?>
    <div class="pagination" style="max-width:1280px;margin:0 auto 24px;">
        <div class="pagination-info">
            Halaman <strong><?= $halamanRiwayat; ?></strong> dari <strong><?= $totalHalamanRiwayat; ?></strong>
        </div>
        <div class="pagination-nav">
            <?php if ($halamanRiwayat > 1): ?>
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($paramHalaman, ['hal_riwayat' => $halamanRiwayat - 1]))); ?>">&larr; Sebelumnya</a>
            <?php else: ?>
                <span class="disabled">&larr; Sebelumnya</span>
            <?php endif; ?>
            <?php if ($halamanRiwayat < $totalHalamanRiwayat): ?>
                <a href="?<?= htmlspecialchars(http_build_query(array_merge($paramHalaman, ['hal_riwayat' => $halamanRiwayat + 1]))); ?>">Berikutnya &rarr;</a>
            <?php else: ?>
                <span class="disabled">Berikutnya &rarr;</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
(() => {
    const pilihSemua = document.getElementById('pilih-semua-slip');
    const formBatch = document.getElementById('batch-slip-form');
    const container = document.getElementById('hidden-ids-container');
    const countNumber = document.getElementById('batch-count-number');
    const countLiveWrap = document.getElementById('batch-live-count');
    const nameSearch = document.getElementById('batch-employee-search');
    const nameSearchCount = document.getElementById('batch-name-search-count');
    const nameSearchRows = Array.from(document.querySelectorAll('tr[data-employee-name]'));
    const nameSearchNoResult = document.getElementById('batch-name-no-result');

    if (!pilihSemua || !formBatch) return;

    const bulan = <?= (int) $bulan; ?>;
    const tahun = <?= (int) $tahun; ?>;
    const departmentId = <?= (int) $departmentId; ?>;
    const posisi = <?= json_encode($posisi, JSON_UNESCAPED_UNICODE); ?>;
    const storageKey = `batch_slip_sel_${bulan}_${tahun}_${departmentId}_${encodeURIComponent(posisi)}`;

    const idHalamanIni = <?= json_encode($idHalamanIni, JSON_UNESCAPED_UNICODE); ?>;
    const semuaId = <?= json_encode($semuaId, JSON_UNESCAPED_UNICODE); ?>;
    const hasilBatchDiproses = <?= !empty($hasilBatch) ? 'true' : 'false'; ?>;

    const applyNameSearch = () => {
        if (!nameSearch) return;

        const query = nameSearch.value.trim().toLocaleLowerCase();
        let jumlahCocok = 0;

        nameSearchRows.forEach(row => {
            const cocok = query === '' || row.dataset.employeeName.toLocaleLowerCase().includes(query);
            row.hidden = !cocok;
            if (cocok) jumlahCocok++;
        });

        if (nameSearchNoResult) {
            nameSearchNoResult.hidden = query === '' || jumlahCocok > 0;
        }
        if (nameSearchCount) {
            nameSearchCount.textContent = query === '' ? '' : `${jumlahCocok} karyawan ditemukan`;
        }
    };

    if (hasilBatchDiproses) {
        try {
            sessionStorage.removeItem(storageKey);
        } catch (e) {}
    }

    const loadSelected = () => {
        try {
            const raw = sessionStorage.getItem(storageKey);
            if (!raw) return new Set();
            const arr = JSON.parse(raw);
            return new Set(Array.isArray(arr) ? arr.map(Number).filter(n => n > 0) : []);
        } catch (e) {
            return new Set();
        }
    };

    const saveSelected = (set) => {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(Array.from(set)));
        } catch (e) {}
    };

    const selectedSet = loadSelected();

    const updateUI = () => {
        const checkboxes = document.querySelectorAll('.pilih-slip');

        checkboxes.forEach(cb => {
            const id = Number(cb.value);
            cb.checked = selectedSet.has(id);
        });

        if (idHalamanIni.length > 0) {
            const semuaHalamanTerpilih = idHalamanIni.every(id => selectedSet.has(id));
            const sebagianHalamanTerpilih = idHalamanIni.some(id => selectedSet.has(id));
            pilihSemua.checked = semuaHalamanTerpilih;
            pilihSemua.indeterminate = sebagianHalamanTerpilih && !semuaHalamanTerpilih;
        } else {
            pilihSemua.checked = false;
            pilihSemua.indeterminate = false;
        }

        if (container) {
            container.innerHTML = '';
            selectedSet.forEach(id => {
                if (!idHalamanIni.includes(id)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'karyawan_ids[]';
                    input.value = id;
                    container.appendChild(input);
                }
            });
        }

        const total = selectedSet.size;
        if (countNumber) {
            countNumber.textContent = total;
        }
        if (countLiveWrap) {
            if (total > 0) {
                countLiveWrap.classList.add('has-selection');
            } else {
                countLiveWrap.classList.remove('has-selection');
            }
        }
    };

    document.querySelectorAll('.pilih-slip').forEach(cb => {
        cb.addEventListener('change', function () {
            const id = Number(this.value);
            if (this.checked) {
                selectedSet.add(id);
            } else {
                selectedSet.delete(id);
            }
            saveSelected(selectedSet);
            updateUI();
        });
    });

    pilihSemua.addEventListener('change', function () {
        const targetIds = (semuaId && semuaId.length > 0) ? semuaId : idHalamanIni;
        if (this.checked) {
            targetIds.forEach(id => selectedSet.add(Number(id)));
        } else {
            selectedSet.clear();
        }
        saveSelected(selectedSet);
        updateUI();
    });

    if (nameSearch) {
        nameSearch.addEventListener('input', applyNameSearch);
        applyNameSearch();
    }

    formBatch.addEventListener('submit', function (e) {
        if (selectedSet.size === 0) {
            e.preventDefault();
            alert('Pilih minimal satu karyawan untuk diproses.');
            return false;
        }
        if (!confirm(`Proses slip untuk ${selectedSet.size} karyawan yang dipilih?`)) {
            e.preventDefault();
            return false;
        }
    });

    updateUI();
})();
</script>
<?php require __DIR__ . '/partials/bawah.php'; ?>
