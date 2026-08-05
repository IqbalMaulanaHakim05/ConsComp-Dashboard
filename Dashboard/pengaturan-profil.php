<?php

declare(strict_types=1);

require __DIR__ . '/fungsi/pengaturan-profil.php';
require_once __DIR__ . '/fungsi/audit.php';
wajibRole('superadmin');

$pengaturan = ambilPengaturanProfil($conn);
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'judul' => trim((string) ($_POST['judul'] ?? '')) ?: 'Profil Internal',
        'teks_pembuka' => trim((string) ($_POST['teks_pembuka'] ?? '')),
        'warna_awal' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST['warna_awal'] ?? '')) ? $_POST['warna_awal'] : '#1e3a8a',
        'warna_akhir' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST['warna_akhir'] ?? '')) ? $_POST['warna_akhir'] : '#2563eb',
        'tampil_foto' => isset($_POST['tampil_foto']) ? 1 : 0,
        'tampil_status' => isset($_POST['tampil_status']) ? 1 : 0,
        'tampil_dokumen' => isset($_POST['tampil_dokumen']) ? 1 : 0,
    ];
    if (simpanPengaturanProfil($conn, $data)) {
        catatAktivitas($conn, 'Mengubah pengaturan tampilan profil.');
        $pengaturan = $data;
        $pesan = 'Pengaturan profil berhasil disimpan.';
    } else {
        $pesan = 'Pengaturan gagal disimpan.';
    }
}

$judulHalaman = 'Pengaturan Profil';
$subjudulHalaman = 'Atur tampilan profil karyawan tanpa coding.';
$halamanAktif = 'pengaturan-profil';
require __DIR__ . '/partials/atas.php';
?>
<section class="form-card">
    <div class="form-card-header"><h2>Tampilan Profil Karyawan</h2><p>Perubahan berlaku untuk seluruh profil internal.</p></div>
    <div class="form-body">
        <?php if ($pesan !== ''): ?><div class="alert" role="status"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group full-width"><label for="judul">Judul Profil</label><input id="judul" name="judul" maxlength="150" value="<?= htmlspecialchars($pengaturan['judul'] ?? 'Profil Internal'); ?>" required></div>
                <div class="form-group full-width"><label for="teks_pembuka">Teks Pembuka</label><input id="teks_pembuka" name="teks_pembuka" maxlength="255" value="<?= htmlspecialchars($pengaturan['teks_pembuka'] ?? ''); ?>" placeholder="Contoh: Informasi internal karyawan"></div>
                <div class="form-group"><label for="warna_awal">Warna Awal Header</label><input type="color" id="warna_awal" name="warna_awal" value="<?= htmlspecialchars($pengaturan['warna_awal'] ?? '#1e3a8a'); ?>"></div>
                <div class="form-group"><label for="warna_akhir">Warna Akhir Header</label><input type="color" id="warna_akhir" name="warna_akhir" value="<?= htmlspecialchars($pengaturan['warna_akhir'] ?? '#2563eb'); ?>"></div>
                <div class="form-group full-width"><label>Bagian yang Ditampilkan</label>
                    <label><input type="checkbox" name="tampil_foto" <?= !empty($pengaturan['tampil_foto']) ? 'checked' : ''; ?>> Foto profil</label>
                    <label><input type="checkbox" name="tampil_status" <?= !empty($pengaturan['tampil_status']) ? 'checked' : ''; ?>> Status pekerjaan</label>
                    <label><input type="checkbox" name="tampil_dokumen" <?= !empty($pengaturan['tampil_dokumen']) ? 'checked' : ''; ?>> Dokumen pendukung</label>
                </div>
            </div>
            <div class="form-actions"><a href="index.php" class="btn btn-secondary">Batal</a><button class="btn btn-success" type="submit">Simpan Pengaturan</button></div>
        </form>
    </div>
</section>
<?php require __DIR__ . '/partials/bawah.php'; ?>
