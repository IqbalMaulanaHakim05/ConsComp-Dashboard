<?php

declare(strict_types=1);

require __DIR__ . '/koneksi.php';
require_once __DIR__ . '/fungsi/auth.php';
require_once __DIR__ . '/fungsi/audit.php';
require_once __DIR__ . '/fungsi/penilaian-performa-karyawan.php';

wajibRole('admin', 'superadmin');

if (!siapkanTabelPenilaianPerformaKaryawan($conn)) {
    http_response_code(500);
    exit('Penyimpanan penilaian performa tidak dapat disiapkan.');
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$karyawan = $id ? karyawanDalamCakupan($conn, (int) $id) : null;
if (!$karyawan) {
    http_response_code(404);
    exit('Data karyawan tidak ditemukan.');
}

$indikator = indikatorPenilaianPerforma();
$penilaian = ambilPenilaianPerformaKaryawan($conn, (int) $karyawan['id']);
$form = [];
foreach ($indikator as $kolom => $konfigurasi) {
    $form[$kolom] = $penilaian !== null && $penilaian[$kolom] !== null
        ? (string) $penilaian[$kolom]
        : '';
}
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($indikator as $kolom => $konfigurasi) {
        $form[$kolom] = trim((string) ($_POST[$kolom] ?? ''));
    }

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $pesan = 'Token keamanan tidak valid.';
    } else {
        try {
            $nilai = normalisasiIndikatorPenilaianPerforma($form);
            $penilaiId = (int) ($_SESSION['user']['id'] ?? 0);
            if (!simpanPenilaianPerformaKaryawan($conn, (int) $karyawan['id'], $nilai, $penilaiId)) {
                throw new RuntimeException('Penilaian performa gagal disimpan.');
            }

            catatAktivitas(
                $conn,
                'Menyimpan penilaian performa terstruktur untuk karyawan ID ' . (int) $karyawan['id'] . '.'
            );
            header(
                'Location: penilaian-performa.php?id=' . (int) $karyawan['id']
                    . '&pesan=' . rawurlencode('Penilaian performa berhasil disimpan.')
            );
            exit;
        } catch (InvalidArgumentException | RuntimeException $exception) {
            $pesan = $exception->getMessage();
        }
    }
}

$nilaiRataRata = [];
foreach ($indikator as $kolom => $konfigurasi) {
    try {
        $nilaiRataRata[$kolom] = normalisasiSkorPerforma($form[$kolom]);
    } catch (InvalidArgumentException) {
        $nilaiRataRata[$kolom] = null;
    }
}
$rataRata = rataRataPenilaianPerforma($nilaiRataRata);

$judulHalaman = 'Penilaian Performa';
$subjudulHalaman = 'Nilai rata-rata indikator akan menjadi skor performa karyawan.';
$halamanAktif = 'penilaian-performa';
require __DIR__ . '/partials/atas.php';
?>

<?php if ($pesan !== ''): ?><div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>

<section class="performance-page-shell">
    <article class="performance-employee-card">
        <div>
            <span class="performance-kicker">Karyawan yang Dinilai</span>
            <h2><?= htmlspecialchars((string) $karyawan['employee_name']); ?></h2>
            <p><?= htmlspecialchars((string) $karyawan['emp_id']); ?> · <?= htmlspecialchars((string) $karyawan['position']); ?> · <?= htmlspecialchars((string) $karyawan['department']); ?></p>
        </div>
        <div class="performance-average">
            <span>Rata-rata indikator</span>
            <strong><?= $rataRata === null ? 'Belum dinilai' : number_format($rataRata, 2, ',', '.'); ?></strong>
            <small>Hanya menghitung indikator bernilai 1–100.</small>
        </div>
    </article>

    <form method="POST" class="performance-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
        <div class="performance-grid">
            <?php foreach ($indikator as $kolom => $konfigurasi): ?>
                <article class="performance-indicator-card">
                    <div class="performance-indicator-heading">
                        <label for="<?= htmlspecialchars($kolom); ?>"><?= htmlspecialchars($konfigurasi['label']); ?></label>
                        <button
                            type="button"
                            class="performance-info"
                            aria-label="Pedoman <?= htmlspecialchars($konfigurasi['label']); ?>"
                            data-tooltip="<?= htmlspecialchars($konfigurasi['pedoman']); ?>"
                            title="<?= htmlspecialchars($konfigurasi['pedoman']); ?>">i</button>
                    </div>
                    <input
                        id="<?= htmlspecialchars($kolom); ?>"
                        name="<?= htmlspecialchars($kolom); ?>"
                        type="number"
                        inputmode="numeric"
                        min="0"
                        max="100"
                        step="1"
                        value="<?= htmlspecialchars($form[$kolom]); ?>"
                        placeholder="Belum dinilai">
                    <p>Masukkan nilai 1–100. Nilai 0 atau kosong disimpan sebagai belum dinilai.</p>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- <aside class="performance-note">
            <strong>Catatan</strong>
            <p>Rata-rata indikator yang diisi akan otomatis memperbarui kolom Performa pada tabel karyawan.</p>
        </aside> -->

        <div class="performance-actions">
            <a class="btn btn-secondary" href="karyawan.php">Kembali</a>
            <button class="btn btn-success" type="submit">Simpan Penilaian</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/partials/bawah.php'; ?>