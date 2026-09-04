<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Settings/pengaturan-publik.php';
require_once __DIR__ . '/../Services/Audit/audit.php';

wajibRole("superadmin");
siapkanPengaturanPublik($conn);

$data = ambilPengaturanPublik($conn);
// Sinkronkan nilai legacy agar form mencerminkan company profile publik baru.
if (($data['nama_situs'] ?? '') === 'Profil Karyawan') $data['nama_situs'] = 'Kalimayat Perkasa';
if (($data['judul_hero'] ?? '') === 'Profil Pekerja Perusahaan' || ($data['judul_hero'] ?? '') === 'Kelola Karyawan Lebih Mudah dan Terintegrasi') $data['judul_hero'] = 'Kelola SDM Lebih Efisien, Kerja Lebih Terorganisir';
if (($data['deskripsi_hero'] ?? '') === 'Website ini menyajikan informasi profil karyawan berdasarkan dataset Human Resources.' || ($data['deskripsi_hero'] ?? '') === 'Menyediakan layanan engineering, konstruksi, dan pengadaan yang aman, profesional, dan tepat waktu.') $data['deskripsi_hero'] = 'Kelola data karyawan, absensi, cuti, penggajian, dan performa dalam satu sistem yang terintegrasi.';
if (($data['teks_tombol'] ?? '') === 'Lihat Data Karyawan') $data['teks_tombol'] = 'Jelajahi Layanan';
if (($data['warna_utama'] ?? '') === '#2563eb') $data['warna_utama'] = '#f5a000';
if (($data['warna_hero'] ?? '') === '#0f172a') $data['warna_hero'] = '#082a57';
$pesan = "";
$tipePesan = "sukses";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach (["foto_tim" => "company-team.jpg", "foto_fasilitas" => "company-plant.jpg", "foto_operasional" => "company-forklift.jpg"] as $field => $filename) {
        if (!empty($_FILES[$field]["tmp_name"]) && is_uploaded_file($_FILES[$field]["tmp_name"])) {
            $mime = mime_content_type($_FILES[$field]["tmp_name"]);
            if (in_array($mime, ["image/jpeg", "image/png", "image/webp"], true)) move_uploaded_file($_FILES[$field]["tmp_name"], __DIR__ . "/../../public/assets/images/" . $filename);
        }
    }
    $nama = trim((string) ($_POST["nama_situs"] ?? ""));
    $judul = trim((string) ($_POST["judul_hero"] ?? ""));
    $deskripsi = trim((string) ($_POST["deskripsi_profil"] ?? $_POST["deskripsi_hero"] ?? ""));
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
    $warnaGrafikStatus = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_grafik_status"] ?? "")) ? $_POST["warna_grafik_status"] : "#2563eb";
    $warnaGrafikTren = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_grafik_tren"] ?? "")) ? $_POST["warna_grafik_tren"] : "#2563eb";
    $warnaGrafikPosisi = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_grafik_posisi"] ?? "")) ? $_POST["warna_grafik_posisi"] : "#2563eb";
    $warnaGrafikDepartemen = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_grafik_departemen"] ?? "")) ? $_POST["warna_grafik_departemen"] : "#2563eb";
    $warnaGrafikGaji = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_grafik_gaji"] ?? "")) ? $_POST["warna_grafik_gaji"] : "#2563eb";
    $warnaGrafikPerforma = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST["warna_grafik_performa"] ?? "")) ? $_POST["warna_grafik_performa"] : "#2563eb";
    $kolomPublikDiizinkan = ["emp_id", "employee_name", "position", "department", "gender", "date_of_hire", "employment_status", "performance_score"];
    $kartuDashboardDiizinkan = ["total_karyawan", "total_departemen", "rata_performa"];
    $kolomPublik = array_values(array_intersect($kolomPublikDiizinkan, (array) ($_POST["kolom_tabel_publik"] ?? [])));
    $kartuDashboard = array_values(array_intersect($kartuDashboardDiizinkan, (array) ($_POST["kartu_dashboard"] ?? [])));
    $kolomPublikJson = json_encode($kolomPublik, JSON_UNESCAPED_UNICODE);
    $kartuDashboardJson = json_encode($kartuDashboard, JSON_UNESCAPED_UNICODE);

    if ($nama === "" || $judul === "" || $deskripsi === "" || $tombol === "") {
        $pesan = "Semua kolom teks wajib diisi.";
        $tipePesan = "error";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pengaturan_publik
             SET nama_situs = ?, judul_hero = ?, deskripsi_hero = ?,
                 teks_tombol = ?, warna_utama = ?, warna_hero = ?, warna_dashboard_awal = ?, warna_dashboard_akhir = ?, warna_pie_laki = ?, warna_pie_perempuan = ?, warna_bar_awal = ?, warna_bar_akhir = ?, warna_grafik_status = ?, warna_grafik_tren = ?, warna_grafik_posisi = ?, warna_grafik_departemen = ?, warna_grafik_gaji = ?, warna_grafik_performa = ?, kolom_tabel_publik = ?, kartu_dashboard = ?
             WHERE id = 1"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssssssssssssss",
            $nama,
            $judul,
            $deskripsi,
            $tombol,
            $warnaUtama,
            $warnaHero,
            $warnaDashboardAwal,
            $warnaDashboardAkhir,
            $warnaPieLaki,
            $warnaPiePerempuan,
            $warnaBarAwal,
            $warnaBarAkhir,
            $warnaGrafikStatus,
            $warnaGrafikTren,
            $warnaGrafikPosisi,
            $warnaGrafikDepartemen,
            $warnaGrafikGaji,
            $warnaGrafikPerforma,
            $kolomPublikJson,
            $kartuDashboardJson
        );

        if ($stmt && mysqli_stmt_execute($stmt)) {
            $data = ambilPengaturanPublik($conn);
            $pesan = "Personalisasi tampilan berhasil disimpan.";
            catatAktivitas($conn, "Mengubah personalisasi tampilan publik, dashboard, dan grafik analisis.");
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
$subjudulHalaman = "Atur identitas, hero, warna, dan elemen company profile yang tampil kepada pengunjung.";
$halamanAktif = "pengaturan-publik";

require __DIR__ . '/../../resources/views/layouts/atas.php';

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

        <form method="POST" enctype="multipart/form-data">

            <section class="settings-section-card">
                <div class="settings-section-heading"><span class="settings-section-icon">◒</span>
                    <div>
                        <h3>Pengaturan warna Dashboard (Internal)</h3>
                        <p>Warna grafik untuk dashboard dan analisis internal.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label for="warna_dashboard_awal">Gradien Dashboard Awal</label><input id="warna_dashboard_awal" type="color" name="warna_dashboard_awal" value="<?= htmlspecialchars($data["warna_dashboard_awal"] ?? "#1e3a8a"); ?>"></div>
                    <div class="form-group"><label for="warna_dashboard_akhir">Gradien Dashboard Akhir</label><input id="warna_dashboard_akhir" type="color" name="warna_dashboard_akhir" value="<?= htmlspecialchars($data["warna_dashboard_akhir"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_pie_laki">Gender Laki-laki</label><input id="warna_pie_laki" type="color" name="warna_pie_laki" value="<?= htmlspecialchars($data["warna_pie_laki"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_pie_perempuan">Gender Perempuan</label><input id="warna_pie_perempuan" type="color" name="warna_pie_perempuan" value="<?= htmlspecialchars($data["warna_pie_perempuan"] ?? "#ec4899"); ?>"></div>
                    <div class="form-group"><label for="warna_bar_awal">Warna Grafik Utama</label><input id="warna_bar_awal" type="color" name="warna_bar_awal" value="<?= htmlspecialchars($data["warna_bar_awal"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_bar_akhir">Warna Grafik Sekunder</label><input id="warna_bar_akhir" type="color" name="warna_bar_akhir" value="<?= htmlspecialchars($data["warna_bar_akhir"] ?? "#93c5fd"); ?>"></div>
                    <div class="form-group"><label for="warna_grafik_status">Grafik Status Kerja</label><input id="warna_grafik_status" type="color" name="warna_grafik_status" value="<?= htmlspecialchars($data["warna_grafik_status"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_grafik_tren">Grafik Tren Penerimaan</label><input id="warna_grafik_tren" type="color" name="warna_grafik_tren" value="<?= htmlspecialchars($data["warna_grafik_tren"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_grafik_posisi">Grafik Karyawan per Posisi</label><input id="warna_grafik_posisi" type="color" name="warna_grafik_posisi" value="<?= htmlspecialchars($data["warna_grafik_posisi"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_grafik_departemen">Grafik Karyawan per Departemen</label><input id="warna_grafik_departemen" type="color" name="warna_grafik_departemen" value="<?= htmlspecialchars($data["warna_grafik_departemen"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_grafik_gaji">Grafik Rata-rata Gaji</label><input id="warna_grafik_gaji" type="color" name="warna_grafik_gaji" value="<?= htmlspecialchars($data["warna_grafik_gaji"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_grafik_performa">Grafik Rata-rata Performa</label><input id="warna_grafik_performa" type="color" name="warna_grafik_performa" value="<?= htmlspecialchars($data["warna_grafik_performa"] ?? "#2563eb"); ?>"></div>
                </div>
            </section>

            <section class="settings-section-card obsolete-public-setting">
                <div class="settings-section-heading"><span class="settings-section-icon">●</span>
                    <div>
                        <h3>Warna Company Profile</h3>
                        <p>Tentukan warna aksen tombol dan latar hero pada halaman publik.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label for="warna_utama">Warna Utama</label><input id="warna_utama" type="color" name="warna_utama" value="<?= htmlspecialchars($data["warna_utama"] ?? "#2563eb"); ?>"></div>
                    <div class="form-group"><label for="warna_hero">Warna Latar Hero</label><input id="warna_hero" type="color" name="warna_hero" value="<?= htmlspecialchars($data["warna_hero"] ?? "#0f172a"); ?>"></div>
                </div>
            </section>

            <?php
            $kolomPublikAktif = json_decode((string) ($data["kolom_tabel_publik"] ?? ""), true);
            if (!is_array($kolomPublikAktif)) $kolomPublikAktif = ["emp_id", "employee_name", "position", "department", "gender", "date_of_hire", "employment_status", "performance_score"];
            $kartuDashboardAktif = json_decode((string) ($data["kartu_dashboard"] ?? ""), true);
            if (!is_array($kartuDashboardAktif)) $kartuDashboardAktif = ["total_karyawan", "total_departemen", "rata_performa"];
            ?>
            <!-- Card Tabel Publik -->
            <section class="settings-section-card obsolete-public-setting">
                <div class="settings-section-heading"><span class="settings-section-icon">▦</span>
                    <div>
                        <h3>Kolom Tabel Publik</h3>
                        <p>Pilih informasi yang boleh ditampilkan pada tabel halaman utama.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <?php foreach (["emp_id" => "ID Karyawan", "employee_name" => "Nama", "position" => "Posisi", "department" => "Departemen", "gender" => "Jenis Kelamin", "date_of_hire" => "Tanggal Masuk", "employment_status" => "Status Kerja", "performance_score" => "Performa"] as $kodeKolom => $labelKolom): ?>
                        <label class="settings-check"><input type="checkbox" name="kolom_tabel_publik[]" value="<?= $kodeKolom; ?>" <?= in_array($kodeKolom, $kolomPublikAktif, true) ? "checked" : ""; ?>> <?= $labelKolom; ?></label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="settings-section-card">
                <div class="settings-section-heading"><span class="settings-section-icon">▤</span>
                    <div>
                        <h3>Kartu Dashboard</h3>
                        <p>Pilih kartu statistik yang ditampilkan pada halaman Dashboard.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <?php foreach (["total_karyawan" => "Total karyawan aktif", "total_departemen" => "Total Departemen", "rata_performa" => "Rata-rata Performa"] as $kodeKartu => $labelKartu): ?>
                        <label class="settings-check"><input type="checkbox" name="kartu_dashboard[]" value="<?= $kodeKartu; ?>" <?= in_array($kodeKartu, $kartuDashboardAktif, true) ? "checked" : ""; ?>> <?= $labelKartu; ?></label>
                    <?php endforeach; ?>
                </div>
            </section>



            <section class="settings-section-card">
                <div class="settings-section-heading"><span class="settings-section-icon">✦</span>
                    <div>
                        <h3>Identitas Company Profile &amp; Hero</h3>
                        <p>Atur nama brand, judul pembuka, deskripsi perusahaan, dan teks tombol halaman publik.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama_situs">Nama Situs</label>
                        <input
                            id="nama_situs"
                            name="nama_situs"
                            value="<?= htmlspecialchars($data["nama_situs"] ?? "Profil Karyawan"); ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="teks_tombol">Teks Tombol Hero</label>
                        <input
                            id="teks_tombol"
                            name="teks_tombol"
                            value="<?= htmlspecialchars($data["teks_tombol"] ?? "Lihat Data Karyawan"); ?>"
                            required>
                    </div>

                    <div class="form-group full-width">
                        <label for="judul_hero">Judul Hero</label>
                        <input
                            id="judul_hero"
                            name="judul_hero"
                            value="<?= htmlspecialchars($data["judul_hero"] ?? "Profil Pekerja Perusahaan"); ?>"
                            required>
                    </div>

                    <div class="form-group full-width">
                        <label for="deskripsi_hero">Deskripsi Hero</label>
                        <textarea id="deskripsi_hero" name="deskripsi_hero" rows="4" required><?= htmlspecialchars($data["deskripsi_hero"] ?? ""); ?></textarea>
                    </div>
                    <div class="form-group full-width">
                        <label for="deskripsi_profil">Deskripsi Profil</label>
                        <textarea id="deskripsi_profil" name="deskripsi_profil" rows="3" required><?= htmlspecialchars($data["deskripsi_hero"] ?? ""); ?></textarea>
                    </div>

                </div>
            </section>

            <section class="settings-section-card">
                <div class="settings-section-heading"><span class="settings-section-icon">▧</span>
                    <div>
                        <h3>Foto Carousel Profil</h3>
                        <p>Ganti foto yang bergulir pada bagian profil publik. Format JPG, PNG, atau WebP.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label for="foto_tim">Foto Tim</label><input id="foto_tim" type="file" name="foto_tim" accept="image/jpeg,image/png,image/webp"></div>
                    <div class="form-group"><label for="foto_fasilitas">Foto Fasilitas</label><input id="foto_fasilitas" type="file" name="foto_fasilitas" accept="image/jpeg,image/png,image/webp"></div>
                    <div class="form-group"><label for="foto_operasional">Foto Operasional</label><input id="foto_operasional" type="file" name="foto_operasional" accept="image/jpeg,image/png,image/webp"></div>
                </div>
            </section>

            <div class="form-actions">
                <a href="<?= htmlspecialchars(URL_DASAR); ?>../../index.php" class="btn btn-secondary" target="_blank" rel="noopener">
                    Lihat Halaman Publik
                </a>
                <button class="btn btn-success" type="submit">Simpan Tampilan</button>
            </div>
        </form>
    </div>
</section>
<?php
require __DIR__ . '/../../resources/views/layouts/bawah.php';
