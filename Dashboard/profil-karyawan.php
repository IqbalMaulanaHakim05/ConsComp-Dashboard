<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/media-karyawan.php";

wajibLogin();
siapkanKolomProfil($conn);

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$karyawan = null;

if ($id !== false && $id !== null && $id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM karyawan WHERE id = ? LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
        mysqli_stmt_close($stmt);
    }
}

if (!$karyawan) {
    http_response_code(404);
    $judulHalaman = "Profil Tidak Ditemukan";
    $subjudulHalaman = "Data karyawan yang diminta tidak tersedia.";
    $halamanAktif = "karyawan";
    require __DIR__ . "/partials/atas.php";
?>
    <section class="data-card">
        <p class="empty-data">Profil karyawan tidak ditemukan.</p>
        <a class="btn btn-secondary" href="karyawan.php">Kembali ke Data Karyawan</a>
    </section>
<?php
    require __DIR__ . "/partials/bawah.php";
    exit;
}

/**
 * Menampilkan nilai kolom, atau tanda "-" bila kosong.
 */
function nilaiProfil(array $data, string $kolom): string
{
    $nilai = trim((string) ($data[$kolom] ?? ""));
    return $nilai !== "" ? $nilai : "-";
}

/**
 * Memformat tanggal ke d-m-Y, atau "-" bila kosong/tidak valid.
 */
function tanggalProfil(array $data, string $kolom): string
{
    $nilai = $data[$kolom] ?? "";
    return $nilai && strtotime((string) $nilai) !== false
        ? date("d-m-Y", strtotime((string) $nilai))
        : "-";
}

$nama = trim((string) ($karyawan["employee_name"] ?? "Karyawan"));

$gender = trim((string) ($karyawan["gender"] ?? ""));
if ($gender === "M" || strcasecmp($gender, "Male") === 0) {
    $genderTampil = "Laki-laki";
} elseif ($gender === "F" || strcasecmp($gender, "Female") === 0) {
    $genderTampil = "Perempuan";
} else {
    $genderTampil = $gender !== "" ? $gender : "-";
}

$judulHalaman = "Profil Karyawan";
$subjudulHalaman = "Informasi internal karyawan yang sedang dipilih.";
$halamanAktif = "karyawan";

require __DIR__ . "/partials/atas.php";

?>
<section class="profile-page">
    <div class="profile-hero">
        <p class="profile-kicker">Profil Internal</p>
        <h2><?= htmlspecialchars($nama); ?></h2>
        <p>Ringkasan informasi karyawan berdasarkan data yang tersimpan.</p>
    </div>

    <div class="profile-grid">
        <article class="profile-card">
            <h3>Informasi Karyawan</h3>

            <div class="profile-photo-wrap">
                <?php if (!empty($karyawan["foto_profil"] ?? "") && is_file(__DIR__ . "/uploads/foto/" . basename((string) $karyawan["foto_profil"]))): ?>
                    <img class="profile-photo" src="uploads/foto/<?= rawurlencode(basename((string) $karyawan["foto_profil"])); ?>" alt="Foto <?= htmlspecialchars($nama); ?>">
                <?php else: ?>
                    <div class="profile-photo profile-photo-empty">Belum ada foto</div>
                <?php endif; ?>
            </div>

            <dl class="profile-details">
                <div>
                    <dt>ID Karyawan</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "emp_id")); ?></dd>
                </div>
                <div>
                    <dt>Tanggal Masuk</dt>
                    <dd><?= htmlspecialchars(tanggalProfil($karyawan, "date_of_hire")); ?></dd>
                </div>
                <div>
                    <dt>Posisi</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "position")); ?></dd>
                </div>
                <div>
                    <dt>Departemen</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "department")); ?></dd>
                </div>
                <div>
                    <dt>Status Kerja</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "employment_status")); ?></dd>
                </div>
                <div>
                    <dt>Skor Performa</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "performance_score")); ?></dd>
                </div>
            </dl>
        </article>

        <article class="profile-card">
            <h3>Detail Karyawan</h3>
            <dl class="profile-details">
                <div>
                    <dt>NIK</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "nik")); ?></dd>
                </div>
                <div>
                    <dt>Alamat</dt>
                    <dd><?= nl2br(htmlspecialchars(nilaiProfil($karyawan, "alamat"))); ?></dd>
                </div>
                <div>
                    <dt>Tanggal Lahir</dt>
                    <dd><?= htmlspecialchars(tanggalProfil($karyawan, "tanggal_lahir")); ?></dd>
                </div>
                <div>
                    <dt>Agama</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "agama")); ?></dd>
                </div>
                <div>
                    <dt>Jenis Kelamin</dt>
                    <dd><?= htmlspecialchars($genderTampil); ?></dd>
                </div>
                <div>
                    <dt>Status Kawin</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "marital_status")); ?></dd>
                </div>
                <div>
                    <dt>Kontak</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "kontak")); ?></dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "email")); ?></dd>
                </div>
            </dl>
        </article>

        <article class="profile-card">
            <h3>Dokumen Pendukung</h3>
            <?php if (!empty($karyawan["file_cv"] ?? "") && is_file(__DIR__ . "/uploads/cv/" . basename((string) $karyawan["file_cv"]))): ?>
                <a class="btn btn-primary" target="_blank" rel="noopener" href="uploads/cv/<?= rawurlencode(basename((string) $karyawan["file_cv"])); ?>">Lihat / Unduh CV (PDF)</a>
            <?php else: ?>
                <p class="empty-data">CV belum diunggah.</p>
            <?php endif; ?>
            <?php foreach (["file_ijazah" => "Ijazah", "file_mcu" => "MCU"] as $kolomDokumen => $labelDokumen): ?>
                <?php if (!empty($karyawan[$kolomDokumen] ?? "") && is_file(__DIR__ . "/uploads/" . ($kolomDokumen === "file_ijazah" ? "ijazah" : "mcu") . "/" . basename((string) $karyawan[$kolomDokumen]))): ?>
                    <a class="btn btn-primary" target="_blank" rel="noopener" href="uploads/<?= $kolomDokumen === "file_ijazah" ? "ijazah" : "mcu"; ?>/<?= rawurlencode(basename((string) $karyawan[$kolomDokumen])); ?>">Lihat / Unduh <?= $labelDokumen; ?> (PDF)</a>
                <?php else: ?><p class="empty-data"><?= $labelDokumen; ?> belum diunggah.</p><?php endif; ?>
            <?php endforeach; ?>
        </article>
    </div>

    <div class="profile-actions">
        <a class="btn btn-secondary" href="karyawan.php">Kembali ke Data Karyawan</a>
        <?php if (punyaRole("admin", "superadmin")): ?>
            <a class="btn btn-warning" href="fungsi/edit.php?id=<?= (int) $karyawan["id"]; ?>">Edit Data</a>
        <?php endif; ?>
    </div>
</section>
<?php
require __DIR__ . "/partials/bawah.php";
