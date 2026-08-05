<?php

declare(strict_types=1);

require __DIR__ . '/koneksi.php';
require_once __DIR__ . '/fungsi/auth.php';
require_once __DIR__ . '/fungsi/pengaturan-profil.php';

wajibLogin();
siapkanPengaturanProfil($conn);
$pengaturanProfil = ambilPengaturanProfil($conn);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$karyawan = null;

if ($id !== false && $id !== null && $id > 0) {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM karyawan WHERE id = ? LIMIT 1');

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
        $karyawan = mysqli_fetch_assoc($hasil) ?: null;
        mysqli_stmt_close($stmt);
    }
}

if (!$karyawan) {
    http_response_code(404);
    $judulHalaman = 'Profil Tidak Ditemukan';
    $subjudulHalaman = 'Data karyawan yang diminta tidak tersedia.';
    $halamanAktif = 'karyawan';
    require __DIR__ . '/partials/atas.php';
    echo '<section class="data-card"><p class="empty-data">Profil karyawan tidak ditemukan.</p><a class="btn btn-secondary" href="karyawan.php">Kembali ke Data Karyawan</a></section>';
    require __DIR__ . '/partials/bawah.php';
    exit;
}

$nama = trim((string) ($karyawan['employee_name'] ?? 'Karyawan'));
$judulHalaman = 'Profil Karyawan';
$subjudulHalaman = 'Informasi internal karyawan yang sedang dipilih.';
$halamanAktif = 'karyawan';

function nilaiProfil(array $data, string $kolom, string $cadangan = '-'): string
{
    $nilai = trim((string) ($data[$kolom] ?? ''));
    return $nilai !== '' ? $nilai : $cadangan;
}

function tanggalProfil(array $data, string $kolom): string
{
    $nilai = $data[$kolom] ?? '';
    return $nilai && strtotime((string) $nilai) !== false
        ? date('d-m-Y', strtotime((string) $nilai))
        : '-';
}

require __DIR__ . '/partials/atas.php';
?>

<section class="profile-page">
    <div class="profile-hero">
        <div>
            <p class="profile-kicker"><?= htmlspecialchars($pengaturanProfil['judul'] ?? 'Profil Internal'); ?></p>
            <h2><?= htmlspecialchars($nama); ?></h2>
            <p><?= htmlspecialchars($pengaturanProfil['teks_pembuka'] ?? ''); ?></p>
        </div>
    </div>

    <div class="profile-grid">
        <?php if (!empty($pengaturanProfil['tampil_dokumen'])): ?><article class="profile-card profile-media-card">
            <h3>Dokumen Pendukung</h3>
            <?php if (!empty($karyawan['file_cv']) && is_file(__DIR__ . '/uploads/cv/' . basename((string) $karyawan['file_cv']))): ?>
                <a class="btn btn-primary" target="_blank" rel="noopener" href="uploads/cv/<?= rawurlencode(basename((string) $karyawan['file_cv'])); ?>">Lihat / Unduh Dokumen PDF</a>
            <?php else: ?>
                <p class="empty-data">CV belum diunggah.</p>
            <?php endif; ?>
        </article><?php endif; ?>

        <article class="profile-card">
            <h3>Informasi Utama</h3>
            <?php if (!empty($pengaturanProfil['tampil_foto'])): ?><div class="profile-photo-wrap">
                <?php if (!empty($karyawan['foto_profil']) && is_file(__DIR__ . '/uploads/foto/' . basename((string) $karyawan['foto_profil']))): ?>
                    <img class="profile-photo" src="uploads/foto/<?= rawurlencode(basename((string) $karyawan['foto_profil'])); ?>" alt="Foto <?= htmlspecialchars($nama); ?>">
                <?php else: ?>
                    <div class="profile-photo profile-photo-empty">Belum ada foto</div>
                <?php endif; ?>
            </div><?php endif; ?>
            <dl class="profile-details">
                <div><dt>ID Karyawan</dt><dd><?= htmlspecialchars(nilaiProfil($karyawan, 'emp_id')); ?></dd></div>
                <div><dt>Jenis Kelamin</dt><dd><?= htmlspecialchars(nilaiProfil($karyawan, 'gender')); ?></dd></div>
                <div><dt>Status Pernikahan</dt><dd><?= htmlspecialchars(nilaiProfil($karyawan, 'marital_status')); ?></dd></div>
                <div><dt>Tanggal Masuk</dt><dd><?= htmlspecialchars(tanggalProfil($karyawan, 'date_of_hire')); ?></dd></div>
            </dl>
        </article>

        <?php if (!empty($pengaturanProfil['tampil_status'])): ?><article class="profile-card">
            <h3>Status Pekerjaan</h3>
            <dl class="profile-details">
                <div><dt>Status Kerja</dt><dd><?= htmlspecialchars(nilaiProfil($karyawan, 'employment_status')); ?></dd></div>
                <div><dt>Performa</dt><dd><?= htmlspecialchars(nilaiProfil($karyawan, 'performance_score')); ?></dd></div>
                <div><dt>Departemen</dt><dd><?= htmlspecialchars(nilaiProfil($karyawan, 'department')); ?></dd></div>
                <div><dt>Posisi</dt><dd><?= htmlspecialchars(nilaiProfil($karyawan, 'position')); ?></dd></div>
            </dl>
        </article><?php endif; ?>
    </div>

    <div class="profile-actions">
        <a class="btn btn-secondary" href="karyawan.php">Kembali ke Data Karyawan</a>
        <?php if (punyaRole('admin', 'superadmin')): ?>
            <a class="btn btn-warning" href="fungsi/edit.php?id=<?= (int) $karyawan['id']; ?>">Edit Data</a>
        <?php endif; ?>
    </div>
</section>

<style>
    .profile-page { max-width: 1100px; }
    .profile-hero { display: flex; align-items: center; gap: 20px; margin-bottom: 22px; padding: 28px; border-radius: 14px; color: #fff; background: linear-gradient(135deg, <?= htmlspecialchars($pengaturanProfil['warna_awal'] ?? '#1e3a8a'); ?>, <?= htmlspecialchars($pengaturanProfil['warna_akhir'] ?? '#2563eb'); ?>); }
    .profile-kicker { margin: 0 0 7px; color: #bfdbfe; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .profile-hero h2 { margin: 0; font-size: 28px; }
    .profile-hero p:last-child { margin: 7px 0 0; color: #dbeafe; }
    .profile-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
    .profile-card { padding: 24px; border-radius: 12px; background: #fff; box-shadow: 0 4px 15px rgba(15,23,42,.07); }
    .profile-card h3 { margin: 0 0 18px; color: #0f172a; font-size: 18px; }
    .profile-media-card { min-height: 160px; order: 3; }
    .profile-grid > .profile-card:nth-child(2) { order: 1; }
    .profile-grid > .profile-card:nth-child(3) { order: 2; }
    .profile-photo-wrap { display: flex; align-items: center; gap: 18px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid #e2e8f0; }
    .profile-photo-label { color: #64748b; font-size: 13px; font-weight: 700; }
    .profile-photo { display: block; width: 220px; height: 220px; object-fit: cover; border-radius: 14px; }
    .profile-photo-empty { display: grid; place-items: center; color: #64748b; background: #e2e8f0; font-size: 13px; }
    .profile-details { display: grid; gap: 0; margin: 0; }
    .profile-details div { display: flex; justify-content: space-between; gap: 20px; padding: 13px 0; border-bottom: 1px solid #e2e8f0; }
    .profile-details div:last-child { border-bottom: 0; }
    .profile-details dt { color: #64748b; font-size: 13px; }
    .profile-details dd { margin: 0; color: #1e293b; font-size: 14px; font-weight: 700; text-align: right; }
    .profile-actions { display: flex; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
    :root[data-theme="dark"] .profile-card { background: #1e293b; box-shadow: 0 4px 15px rgba(0,0,0,.2); }
    :root[data-theme="dark"] .profile-card h3, :root[data-theme="dark"] .profile-details dd { color: #f8fafc; }
    :root[data-theme="dark"] .profile-photo-empty { color: #cbd5e1; background: #334155; }
    :root[data-theme="dark"] .profile-details dt { color: #cbd5e1; }
    :root[data-theme="dark"] .profile-details div { border-color: #334155; }
    :root[data-theme="dark"] .profile-photo-wrap { border-color: #334155; }
    :root[data-theme="dark"] .profile-photo-label { color: #cbd5e1; }
    @media screen and (max-width: 800px) { .profile-hero { align-items: flex-start; flex-direction: column; } .profile-photo { width: 180px; height: 180px; } }
</style>

<?php require __DIR__ . '/partials/bawah.php'; ?>
