<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/pengaturan-publik.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("superadmin");
siapkanPengaturanPublik($conn);

$data = ambilPengaturanPublik($conn);
$pesan = "";
$tipePesan = "sukses";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim((string) ($_POST["nama_situs"] ?? ""));
    $judul = trim((string) ($_POST["judul_hero"] ?? ""));
    $deskripsi = trim((string) ($_POST["deskripsi_hero"] ?? ""));
    $tombol = trim((string) ($_POST["teks_tombol"] ?? ""));

    $warnaUtama = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_utama"] ?? ""))
        ? $_POST["warna_utama"]
        : "#2563eb";
    $warnaHero = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_hero"] ?? ""))
        ? $_POST["warna_hero"]
        : "#0f172a";
    $warnaDashboardAwal = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_dashboard_awal"] ?? "")) ? $_POST["warna_dashboard_awal"] : "#1e3a8a";
    $warnaDashboardAkhir = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_dashboard_akhir"] ?? "")) ? $_POST["warna_dashboard_akhir"] : "#2563eb";
    $warnaPieLaki = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_pie_laki"] ?? "")) ? $_POST["warna_pie_laki"] : "#2563eb";
    $warnaPiePerempuan = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_pie_perempuan"] ?? "")) ? $_POST["warna_pie_perempuan"] : "#ec4899";
    $warnaBarAwal = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_bar_awal"] ?? "")) ? $_POST["warna_bar_awal"] : "#2563eb";
    $warnaBarAkhir = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_bar_akhir"] ?? "")) ? $_POST["warna_bar_akhir"] : "#93c5fd";

    if ($nama === "" || $judul === "" || $deskripsi === "" || $tombol === "") {
        $pesan = "Semua kolom teks wajib diisi.";
        $tipePesan = "error";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pengaturan_publik
             SET nama_situs = ?, judul_hero = ?, deskripsi_hero = ?,
                 teks_tombol = ?, warna_utama = ?, warna_hero = ?, warna_dashboard_awal = ?, warna_dashboard_akhir = ?, warna_pie_laki = ?, warna_pie_perempuan = ?, warna_bar_awal = ?, warna_bar_akhir = ?
             WHERE id = 1"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssssss",
            $nama,
            $judul,
            $deskripsi,
            $tombol,
            $warnaUtama,
            $warnaHero, $warnaDashboardAwal, $warnaDashboardAkhir, $warnaPieLaki, $warnaPiePerempuan, $warnaBarAwal, $warnaBarAkhir
        );

        if ($stmt && mysqli_stmt_execute($stmt)) {
            $data = ambilPengaturanPublik($conn);
            $pesan = "Tampilan halaman publik berhasil disimpan.";
            catatAktivitas($conn, "Mengubah tampilan halaman publik.");
        } else {
            $pesan = "Pengaturan gagal disimpan.";
            $tipePesan = "error";
        }

        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
    }
}

$judulHalaman = "Personalisasi Tampilan";
$subjudulHalaman = "Atur tampilan halaman publik sesuai identitas website.";
$halamanAktif = "pengaturan-publik";

require __DIR__ . "/partials/atas.php";

?>
    <section class="form-card">
        <div class="form-card-header">
            <h2>Personalisasi Tampilan</h2>
            <p>Perubahan langsung terlihat oleh pengunjung halaman publik.</p>
        </div>

        <div class="form-body">
            <?php if ($pesan !== ""): ?>
                <div class="<?= $tipePesan === "error" ? "alert-error" : "alert"; ?>" role="status">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <section class="settings-section-card">
                    <div class="settings-section-heading"><span class="settings-section-icon">✦</span><div><h3>Identitas &amp; Hero</h3><p>Atur teks utama yang tampil di halaman publik.</p></div></div>
                    <div class="form-grid">
                    <div class="form-group">
                        <label for="nama_situs">Nama Situs</label>
                        <input
                            id="nama_situs"
                            name="nama_situs"
                            value="<?= htmlspecialchars($data["nama_situs"] ?? "Profil Karyawan"); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="teks_tombol">Teks Tombol Hero</label>
                        <input
                            id="teks_tombol"
                            name="teks_tombol"
                            value="<?= htmlspecialchars($data["teks_tombol"] ?? "Lihat Data Karyawan"); ?>"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="judul_hero">Judul Hero</label>
                        <input
                            id="judul_hero"
                            name="judul_hero"
                            value="<?= htmlspecialchars($data["judul_hero"] ?? "Profil Pekerja Perusahaan"); ?>"
                            required
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="deskripsi_hero">Deskripsi Hero</label>
                        <textarea id="deskripsi_hero" name="deskripsi_hero" rows="4" required><?= htmlspecialchars($data["deskripsi_hero"] ?? ""); ?></textarea>
                    </div>

                    </div>
                </section>

                <section class="settings-section-card">
                    <div class="settings-section-heading"><span class="settings-section-icon">▦</span><div><h3>Warna Dashboard</h3><p>Sesuaikan gradasi kartu statistik dashboard.</p></div></div>
                    <div class="form-grid">
                        <div class="form-group"><label for="warna_dashboard_awal">Gradien Dashboard Awal</label><input id="warna_dashboard_awal" type="color" name="warna_dashboard_awal" value="<?= htmlspecialchars($data["warna_dashboard_awal"] ?? "#1e3a8a"); ?>"></div>
                        <div class="form-group"><label for="warna_dashboard_akhir">Gradien Dashboard Akhir</label><input id="warna_dashboard_akhir" type="color" name="warna_dashboard_akhir" value="<?= htmlspecialchars($data["warna_dashboard_akhir"] ?? "#2563eb"); ?>"></div>
                    </div>
                </section>

                <section class="settings-section-card">
                    <div class="settings-section-heading"><span class="settings-section-icon">◒</span><div><h3>Warna Grafik</h3><p>Atur warna pie chart dan bar chart.</p></div></div>
                    <div class="form-grid">
                        <div class="form-group"><label for="warna_pie_laki">Pie Laki-laki</label><input id="warna_pie_laki" type="color" name="warna_pie_laki" value="<?= htmlspecialchars($data["warna_pie_laki"] ?? "#2563eb"); ?>"></div>
                        <div class="form-group"><label for="warna_pie_perempuan">Pie Perempuan</label><input id="warna_pie_perempuan" type="color" name="warna_pie_perempuan" value="<?= htmlspecialchars($data["warna_pie_perempuan"] ?? "#ec4899"); ?>"></div>
                        <div class="form-group"><label for="warna_bar_awal">Bar Awal</label><input id="warna_bar_awal" type="color" name="warna_bar_awal" value="<?= htmlspecialchars($data["warna_bar_awal"] ?? "#2563eb"); ?>"></div>
                        <div class="form-group"><label for="warna_bar_akhir">Bar Akhir</label><input id="warna_bar_akhir" type="color" name="warna_bar_akhir" value="<?= htmlspecialchars($data["warna_bar_akhir"] ?? "#93c5fd"); ?>"></div>
                    </div>
                </section>

                <section class="settings-section-card">
                    <div class="settings-section-heading"><span class="settings-section-icon">●</span><div><h3>Warna Halaman</h3><p>Tentukan warna utama dan latar hero.</p></div></div>
                    <div class="form-grid">
                        <div class="form-group"><label for="warna_utama">Warna Utama</label><input id="warna_utama" type="color" name="warna_utama" value="<?= htmlspecialchars($data["warna_utama"] ?? "#2563eb"); ?>"></div>
                        <div class="form-group"><label for="warna_hero">Warna Latar Hero</label><input id="warna_hero" type="color" name="warna_hero" value="<?= htmlspecialchars($data["warna_hero"] ?? "#0f172a"); ?>"></div>
                    </div>
                </section>

                <div class="form-actions">
                    <a href="<?= htmlspecialchars(URL_DASAR); ?>../index.php" class="btn btn-secondary">
                        Lihat Halaman Publik
                    </a>
                    <button class="btn btn-success" type="submit">Simpan Tampilan</button>
                </div>
            </form>
        </div>
    </section>
<?php
require __DIR__ . "/partials/bawah.php";
