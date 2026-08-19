<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/media-karyawan.php";
require_once __DIR__ . "/fungsi/master-data.php";
require_once __DIR__ . "/fungsi/performa-karyawan.php";
require_once __DIR__ . "/fungsi/slip-gaji.php";

wajibLogin();
siapkanMasterData($conn);
$masterDepartemen = ambilMasterData($conn, "department"); $masterPosisi = ambilMasterData($conn, "position"); $masterStatus = ambilMasterData($conn, "employment_status"); $masterAgama = ambilMasterData($conn, "agama");
$posisiPerDepartemen = ambilPosisiPerNamaDepartemen($conn);

$id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);
$karyawan = null;

if ($id !== false && $id !== null && $id > 0) {
    $karyawan = karyawanDalamCakupan($conn, (int) $id);
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
        <a class="btn btn-secondary profile-back-link" href="karyawan.php"><span aria-hidden="true">←</span> Profil Karyawan</a>
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
$modeEdit = isset($_GET["edit"]) && $_GET["edit"] === "1" && punyaRole("admin", "superadmin");
$adminHrgaEdit = $modeEdit && rolePengguna() === "admin";
$errorEdit = trim((string) ($_GET["error"] ?? ""));
$riwayatPendidikan = [];
$hasilPendidikan = mysqli_query($conn, "SELECT id, institusi, jenjang, jurusan, tanggal_mulai, tanggal_selesai, keterangan FROM riwayat_pendidikan WHERE karyawan_id = " . (int) $karyawan["id"] . " ORDER BY COALESCE(tanggal_mulai, tanggal_selesai) DESC, id DESC");
while ($rowPendidikan = mysqli_fetch_assoc($hasilPendidikan)) $riwayatPendidikan[] = $rowPendidikan;
$riwayatPekerjaan = [];
$hasilPekerjaan = mysqli_query($conn, "SELECT id, nama_perusahaan, posisi, departemen, tanggal_mulai, tanggal_selesai, deskripsi FROM riwayat_pekerjaan WHERE karyawan_id = " . (int) $karyawan["id"] . " ORDER BY COALESCE(tanggal_mulai, tanggal_selesai) DESC, id DESC");
while ($rowPekerjaan = mysqli_fetch_assoc($hasilPekerjaan)) $riwayatPekerjaan[] = $rowPekerjaan;
$daftarSlipGaji = siapkanPenyimpananSlipGaji($conn)
    ? daftarSlipGajiKaryawan($conn, (int) $karyawan["id"])
    : [];

require __DIR__ . "/partials/atas.php";

?>
<section class="profile-page <?= $modeEdit ? "profile-edit-mode" : ""; ?>">
    <div class="profile-hero">
        <p class="profile-kicker">Profil Internal</p>
        <h2><?= htmlspecialchars($nama); ?></h2>
        <p>Ringkasan informasi karyawan berdasarkan data yang tersimpan.</p>
    </div>

    <?php if ($errorEdit !== ""): ?><div class="alert-error" role="alert"><?= htmlspecialchars($errorEdit); ?></div><?php endif; ?>

    <?php if ($modeEdit): ?>
        <article class="profile-card profile-inline-edit">
            <h3>Edit Data Karyawan</h3>
            <form id="inline-edit-form" method="POST" action="fungsi/edit.php?id=<?= (int) $karyawan["id"]; ?>" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="return_to_profile" value="1">
                <div class="profile-edit-grid">
                    <?php
                    $fieldEdit = [
                        "employee_name" => "Nama Karyawan", "nik" => "NIK", "keahlian" => "Keahlian",
                        "tanggal_lahir" => "Tanggal Lahir", "tanggal_mcu_terakhir" => "Tanggal MCU Terakhir", "agama" => "Agama", "marital_status" => "Status Kawin",
                        "kontak" => "Kontak", "email" => "Email", "position" => "Posisi", "department" => "Departemen",
                        "salary" => "Gaji", "gender" => "Jenis Kelamin", "employment_status" => "Status Kerja",
                        "performance_score" => "Skor Performa"
                    ];
                    if ($adminHrgaEdit) {
                        $fieldEdit = array_filter(
                            $fieldEdit,
                            static fn (string $label, string $field): bool => adminHrgaBolehEditFieldKaryawan($field),
                            ARRAY_FILTER_USE_BOTH
                        );
                    }
                    foreach ($fieldEdit as $field => $label):
                        $type = str_starts_with($field, "tanggal_") ? "date" : ($field === "email" ? "email" : ($field === "salary" || $field === "performance_score" ? "number" : "text"));
                    ?>
                        <div class="form-group">
                            <label for="profile_<?= $field; ?>"><?= $label; ?><?= $field === "nik" ? ' <span class="required">*</span>' : ""; ?></label>
                            <?php if ($field === "position" || $field === "department" || $field === "employment_status" || $field === "agama"): ?>
                                <select id="profile_<?= $field; ?>" name="<?= $field; ?>" <?= $field === "agama" ? "" : "required"; ?>><option value="">Pilih</option><?php foreach (($field === "position" ? $masterPosisi : ($field === "department" ? $masterDepartemen : ($field === "employment_status" ? $masterStatus : $masterAgama))) as $item): ?><option value="<?= htmlspecialchars($item); ?>" <?= ($karyawan[$field] ?? "") === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
                            <?php elseif ($field === "alamat" || $field === "biografi" || $field === "keahlian"): ?>
                                <textarea id="profile_<?= $field; ?>" name="<?= $field; ?>" rows="4"><?= htmlspecialchars((string) ($karyawan[$field] ?? "")); ?></textarea>
                            <?php elseif ($field === "gender"): ?>
                                <select id="profile_<?= $field; ?>" name="<?= $field; ?>" required><option value="M" <?= ($karyawan[$field] ?? "") === "M" ? "selected" : ""; ?>>Laki-laki</option><option value="F" <?= ($karyawan[$field] ?? "") === "F" ? "selected" : ""; ?>>Perempuan</option></select>
                            <?php elseif ($field === "marital_status"): ?>
                                <select id="profile_<?= $field; ?>" name="<?= $field; ?>"><option value="">Pilih status</option><option <?= ($karyawan[$field] ?? "") === "Single" ? "selected" : ""; ?>>Single</option><option <?= ($karyawan[$field] ?? "") === "Married" ? "selected" : ""; ?>>Married</option></select>
                            <?php else: ?>
                                <input id="profile_<?= $field; ?>" type="<?= $type; ?>" name="<?= $field; ?>" value="<?= htmlspecialchars((string) ($karyawan[$field] ?? "")); ?>" <?= in_array($field, ["employee_name", "emp_id", "nik", "position", "department", "salary", "gender", "employment_status"], true) ? "required" : ""; ?> <?= $field === "performance_score" ? 'min="0" max="100" step="1"' : ""; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$adminHrgaEdit): ?><div class="form-group"><label for="profile_emp_id">ID Karyawan</label><input id="profile_emp_id" type="text" value="<?= htmlspecialchars((string) ($karyawan["emp_id"] ?? "")); ?>" readonly><p class="field-note">ID karyawan tidak dapat diubah.</p></div><?php endif; ?>
                    <div class="form-group form-group-full"><label for="profile_alamat">Alamat</label><textarea id="profile_alamat" name="alamat" rows="3"><?= htmlspecialchars((string) ($karyawan["alamat"] ?? "")); ?></textarea></div>
                    <div class="form-group form-group-full"><label for="profile_biografi">Biografi Diri</label><textarea id="profile_biografi" name="biografi" rows="5" maxlength="2000"><?= htmlspecialchars((string) ($karyawan["biografi"] ?? "")); ?></textarea></div>
                </div>
                <h3>Riwayat Pendidikan</h3><div id="pendidikan-list"><?php foreach ($riwayatPendidikan as $i => $item): ?><div class="form-grid history-row" data-history-key="db-pendidikan-<?= (int) $item["id"]; ?>"><input name="pendidikan[<?= $i; ?>][institusi]" placeholder="Institusi" value="<?= htmlspecialchars($item["institusi"]); ?>" required><input name="pendidikan[<?= $i; ?>][jenjang]" placeholder="Jenjang" value="<?= htmlspecialchars($item["jenjang"] ?? ""); ?>"><input name="pendidikan[<?= $i; ?>][jurusan]" placeholder="Jurusan" value="<?= htmlspecialchars($item["jurusan"] ?? ""); ?>"><input name="pendidikan[<?= $i; ?>][tanggal_mulai]" type="date" value="<?= htmlspecialchars($item["tanggal_mulai"] ?? ""); ?>"><input name="pendidikan[<?= $i; ?>][tanggal_selesai]" type="date" value="<?= htmlspecialchars($item["tanggal_selesai"] ?? ""); ?>"><input name="pendidikan[<?= $i; ?>][keterangan]" placeholder="Keterangan" value="<?= htmlspecialchars($item["keterangan"] ?? ""); ?>"><button class="btn btn-danger remove-history" type="button">Hapus</button></div><?php endforeach; ?></div><button class="btn btn-secondary" id="tambah-pendidikan" type="button">+ Tambah Pendidikan</button>
                <h3>Riwayat Pekerjaan</h3><div id="pekerjaan-list"><?php foreach ($riwayatPekerjaan as $i => $item): ?><div class="form-grid history-row" data-history-key="db-pekerjaan-<?= (int) $item["id"]; ?>"><input name="pekerjaan[<?= $i; ?>][nama_perusahaan]" placeholder="Nama Perusahaan" value="<?= htmlspecialchars($item["nama_perusahaan"]); ?>" required><input name="pekerjaan[<?= $i; ?>][posisi]" placeholder="Posisi" value="<?= htmlspecialchars($item["posisi"] ?? ""); ?>"><input name="pekerjaan[<?= $i; ?>][departemen]" placeholder="Departemen" value="<?= htmlspecialchars($item["departemen"] ?? ""); ?>"><input name="pekerjaan[<?= $i; ?>][tanggal_mulai]" type="date" value="<?= htmlspecialchars($item["tanggal_mulai"] ?? ""); ?>"><input name="pekerjaan[<?= $i; ?>][tanggal_selesai]" type="date" value="<?= htmlspecialchars($item["tanggal_selesai"] ?? ""); ?>"><input name="pekerjaan[<?= $i; ?>][deskripsi]" placeholder="Deskripsi" value="<?= htmlspecialchars($item["deskripsi"] ?? ""); ?>"><button class="btn btn-danger remove-history" type="button">Hapus</button></div><?php endforeach; ?></div><button class="btn btn-secondary" id="tambah-pekerjaan" type="button">+ Tambah Pekerjaan</button>
                <div class="profile-actions"><button class="btn btn-success" type="submit">Simpan Perubahan</button><a class="btn btn-secondary" href="profil-karyawan.php?id=<?= (int) $karyawan["id"]; ?>">Batal</a></div>
            </form>
        </article>
    <?php endif; ?>

    <div class="profile-grid">
        <article class="profile-card">
            <h3>Profil Karyawan</h3>

            <div class="profile-photo-wrap">
                <?php if (!empty($karyawan["foto_profil"] ?? "") && is_file(__DIR__ . "/uploads/foto/" . basename((string) $karyawan["foto_profil"]))): ?>
                    <img class="profile-photo" src="file.php?jenis=foto&amp;file=<?= rawurlencode(basename((string) $karyawan["foto_profil"])); ?>" alt="Foto <?= htmlspecialchars($nama); ?>">
                <?php else: ?>
                    <div class="profile-photo profile-photo-empty">Belum ada foto</div>
                <?php endif; ?>
                <?php if ($modeEdit && punyaRole("superadmin")): ?>
                    <div class="profile-photo-edit"><label for="inline_foto_profil">Ganti Foto Profil</label><input id="inline_foto_profil" type="file" name="foto_profil" form="inline-edit-form" accept=".jpg,.jpeg,image/jpeg"><p class="field-note">JPG/JPEG, maksimal 2 MB.</p></div>
                <?php endif; ?>
            </div>

            <dl class="profile-details">
                <div>
                    <dt>Nama Karyawan</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "employee_name")); ?></dd>
                </div>
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
                    <dd><?= htmlspecialchars(tampilkanSkorPerforma($karyawan["performance_score"] ?? null, "Belum dinilai")); ?></dd>
                </div>
            </dl>
        </article>

        <article class="profile-card profile-history-card">
            <h3>Informasi Karyawan</h3>
            <dl class="profile-details">
                <div class="profile-biography-row"><dt>Biodata</dt><dd><?= nl2br(htmlspecialchars(nilaiProfil($karyawan, "biografi"))); ?></dd></div>
                <div class="profile-biography-row"><dt>Keahlian</dt><dd><?= nl2br(htmlspecialchars(nilaiProfil($karyawan, "keahlian"))); ?></dd></div>
                <div class="profile-history-row"><dt>Riwayat Pekerjaan</dt><dd><?php if ($riwayatPekerjaan === []): ?><span class="profile-history-empty">-</span><?php else: ?><?php foreach ($riwayatPekerjaan as $item): ?><div class="profile-history-entry" data-history-key="db-pekerjaan-<?= (int) $item["id"]; ?>"><div><strong><?= htmlspecialchars($item["nama_perusahaan"]); ?></strong><span> - <?= htmlspecialchars($item["posisi"] ?? ""); ?></span></div><small><?= htmlspecialchars($item["tanggal_mulai"] ?: "-"); ?> &nbsp; s/d &nbsp; <?= htmlspecialchars($item["tanggal_selesai"] ?: "Sekarang"); ?></small><?php if (trim((string) ($item["deskripsi"] ?? "")) !== ""): ?><p class="profile-history-description"><?= nl2br(htmlspecialchars((string) $item["deskripsi"])); ?></p><?php endif; ?><?php if ($modeEdit): ?><button class="btn btn-danger" type="button" data-history-delete="pekerjaan" data-history-key="db-pekerjaan-<?= (int) $item["id"]; ?>">Hapus</button><?php endif; ?></div><?php endforeach; ?><?php endif; ?><?php if ($modeEdit): ?><button class="btn btn-secondary open-history" type="button" data-type="pekerjaan">+ Tambah Pekerjaan</button><?php endif; ?></dd></div>
            </dl>
        </article>

        <article class="profile-card profile-detail-card">
            <h3>Biodata Karyawan</h3>
            <dl class="profile-details">
                <div>
                    <dt>Kontak</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "kontak")); ?></dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars(nilaiProfil($karyawan, "email")); ?></dd>
                </div>
                <div>
                    <dt>NIK</dt>
                    <dd><?= htmlspecialchars(trim((string) ($karyawan["nik"] ?? "")) !== "" ? (string) $karyawan["nik"] : "Belum diisi"); ?></dd>
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
                <div class="profile-history-row"><dt>Riwayat Pendidikan</dt><dd><?php if ($riwayatPendidikan === []): ?><span class="profile-history-empty">-</span><?php else: ?><?php foreach ($riwayatPendidikan as $item): ?><div class="profile-history-entry" data-history-key="db-pendidikan-<?= (int) $item["id"]; ?>"><div><strong><?= htmlspecialchars($item["institusi"]); ?></strong><span> - <?= htmlspecialchars(trim((string) ($item["jenjang"] ?? "") . " " . (string) ($item["jurusan"] ?? ""))); ?></span></div><small><?= htmlspecialchars($item["tanggal_mulai"] ?: "-"); ?> &nbsp; s/d &nbsp; <?= htmlspecialchars($item["tanggal_selesai"] ?: "Sekarang"); ?></small><?php if ($modeEdit): ?><button class="btn btn-danger" type="button" data-history-delete="pendidikan" data-history-key="db-pendidikan-<?= (int) $item["id"]; ?>">Hapus</button><?php endif; ?></div><?php endforeach; ?><?php endif; ?><?php if ($modeEdit): ?><button class="btn btn-secondary open-history" type="button" data-type="pendidikan">+ Tambah Pendidikan</button><?php endif; ?></dd></div>
            </dl>
        </article>

        <article class="profile-card profile-documents-card" id="dokumen">
            <h3>Dokumen Pendukung</h3>
            <?php if ($modeEdit && punyaRole("superadmin")): ?>
                <div class="profile-document-edit-grid">
                    <div class="form-group"><label for="inline_file_cv">Ganti CV</label><input id="inline_file_cv" type="file" name="file_cv" form="inline-edit-form" accept=".pdf,application/pdf"><p class="field-note">PDF, maksimal 5 MB.</p></div>
                    <div class="form-group"><label for="inline_file_ijazah">Ganti Ijazah</label><input id="inline_file_ijazah" type="file" name="file_ijazah" form="inline-edit-form" accept=".pdf,application/pdf"><p class="field-note">PDF, maksimal 5 MB.</p></div>
                    <div class="form-group"><label for="inline_file_mcu">Ganti MCU</label><input id="inline_file_mcu" type="file" name="file_mcu" form="inline-edit-form" accept=".pdf,application/pdf"><p class="field-note">PDF, maksimal 5 MB.</p></div>
                </div>
            <?php endif; ?>
            <div class="profile-document-list">
                <div class="profile-document-row">
                    <span>CV</span>
                    <?php if (!empty($karyawan["file_cv"] ?? "") && is_file(__DIR__ . "/uploads/cv/" . basename((string) $karyawan["file_cv"]))): ?>
                        <a class="btn btn-primary" target="_blank" rel="noopener" href="file.php?jenis=cv&amp;file=<?= rawurlencode(basename((string) $karyawan["file_cv"])); ?>">Lihat/Unduh CV</a>
                    <?php else: ?><em>Belum diunggah</em><?php endif; ?>
                </div>
                <?php foreach (["file_ijazah" => "Ijazah", "file_mcu" => "MCU"] as $kolomDokumen => $labelDokumen): ?>
                    <div class="profile-document-row">
                        <span><?= $labelDokumen; ?></span>
                        <?php if (!empty($karyawan[$kolomDokumen] ?? "") && is_file(__DIR__ . "/uploads/" . ($kolomDokumen === "file_ijazah" ? "ijazah" : "mcu") . "/" . basename((string) $karyawan[$kolomDokumen]))): ?>
                            <a class="btn btn-primary" target="_blank" rel="noopener" href="file.php?jenis=<?= $kolomDokumen === "file_ijazah" ? "ijazah" : "mcu"; ?>&amp;file=<?= rawurlencode(basename((string) $karyawan[$kolomDokumen])); ?>">Lihat/Unduh <?= $labelDokumen; ?></a>
                        <?php else: ?><em>Belum diunggah</em><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="profile-document-row profile-document-date">
                    <span>Tanggal MCU terakhir</span>
                    <?php if ($modeEdit && punyaRole("superadmin")): ?>
                        <input class="profile-document-date-input" id="inline_tanggal_mcu_terakhir" type="date" name="tanggal_mcu_terakhir" form="inline-edit-form" value="<?= htmlspecialchars((string) ($karyawan["tanggal_mcu_terakhir"] ?? "")); ?>">
                    <?php else: ?>
                        <strong><?= htmlspecialchars(tanggalProfil($karyawan, "tanggal_mcu_terakhir")); ?></strong>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <article class="profile-card profile-payslip-card" id="slip-gaji">
            <h3>Slip Gaji</h3>
            <p class="profile-card-note">Riwayat slip tersimpan berdasarkan periode dan versi revisi.</p>
            <div class="profile-payslip-list">
                <?php if ($daftarSlipGaji === []): ?>
                    <p class="profile-history-empty">Belum ada slip gaji yang didistribusikan.</p>
                <?php else: ?>
                    <?php foreach ($daftarSlipGaji as $slip): ?>
                        <div class="profile-payslip-row">
                            <div>
                                <strong><?= htmlspecialchars(namaBulanSlipGaji((int) $slip["bulan"]) . " " . (int) $slip["tahun"]); ?></strong>
                                <span>Versi <?= (int) $slip["versi"]; ?> · Rp <?= number_format((float) $slip["gaji_bersih"], 0, ",", "."); ?></span>
                                <small>Dibuat oleh <?= htmlspecialchars((string) $slip["nama_pembuat"]); ?> pada <?= htmlspecialchars(date("d-m-Y H:i", strtotime((string) $slip["generated_at"]))); ?></small>
                            </div>
                            <?php if (trim((string) ($slip["nama_file"] ?? "")) !== ""): ?>
                                <a class="btn btn-primary" target="_blank" rel="noopener" href="fungsi/lihat-slip-gaji.php?id=<?= (int) $slip["id"]; ?>">Lihat/Unduh</a>
                            <?php else: ?>
                                <em>File tidak tersedia</em>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>
    </div>

    <div class="profile-actions">
        <a class="btn btn-secondary" href="karyawan.php">Kembali ke Data Karyawan</a>
        <?php if (punyaRole("admin", "superadmin")): ?>
            <?php if ($modeEdit): ?>
                <a class="btn btn-secondary" href="profil-karyawan.php?id=<?= (int) $karyawan["id"]; ?>">Batal</a>
                <button class="btn btn-success" type="submit" form="inline-edit-form">Simpan Perubahan</button>
            <?php else: ?>
                <a class="btn btn-warning" href="profil-karyawan.php?id=<?= (int) $karyawan["id"]; ?>&edit=1#edit-profile">Edit Data</a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (punyaRole("admin", "superadmin")): ?>
            <form class="profile-export-form" method="POST" action="fungsi/generate-cv.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                <input type="hidden" name="id" value="<?= (int) $karyawan["id"]; ?>">
                <button class="btn btn-primary profile-export-button" type="submit">Export PDF</button>
            </form>
        <?php endif; ?>
    </div>

    <?php
    $cvUntukDiunduh = basename((string) ($_GET["unduh_cv"] ?? ""));
    $cvAktif = basename((string) ($karyawan["file_cv"] ?? ""));
    if (
        $cvUntukDiunduh !== ""
        && hash_equals($cvAktif, $cvUntukDiunduh)
        && is_file(__DIR__ . "/uploads/cv/" . $cvUntukDiunduh)
    ):
    ?>
        <a id="cv-auto-preview" aria-hidden="true" style="display: none;" target="_blank" rel="noopener" href="file.php?jenis=cv&amp;file=<?= rawurlencode($cvUntukDiunduh); ?>">Lihat CV</a>
    <?php endif; ?>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.profile-page');
    const hero = page?.querySelector('.profile-hero');
    const actions = page?.querySelector(':scope > .profile-actions');
    if (page && hero && actions) page.insertBefore(actions, hero);
});
    const fieldConfig = {
        pendidikan: [['institusi','Institusi'],['jenjang','Jenjang'],['jurusan','Jurusan'],['tanggal_mulai','Tanggal mulai'],['tanggal_selesai','Tanggal selesai'],['keterangan','Keterangan']],
        pekerjaan: [['nama_perusahaan','Nama Perusahaan'],['posisi','Posisi'],['departemen','Departemen'],['tanggal_mulai','Tanggal mulai'],['tanggal_selesai','Tanggal selesai'],['deskripsi','Deskripsi']]
    };
    const riwayatLabel = { pendidikan: 'Riwayat Pendidikan', pekerjaan: 'Riwayat Pekerjaan' };
    const buatKunciRiwayat = () => 'baru-' + Date.now() + '-' + Math.random().toString(36).slice(2);
    const tambahTampilanRiwayat = (type, values, key) => {
        const detail = [...document.querySelectorAll('.profile-history-row')].find(row => row.querySelector('dt')?.textContent.trim() === riwayatLabel[type]);
        const target = detail?.querySelector('dd');
        if (!target) return;
        target.querySelector('.profile-history-empty')?.remove();
        const entry = document.createElement('div');
        entry.className = 'profile-history-entry';
        entry.dataset.historyKey = key;
        const title = document.createElement('div');
        const strong = document.createElement('strong');
        strong.textContent = values[fieldConfig[type][0][0]] || '-';
        title.appendChild(strong);
        const secondary = document.createElement('span');
        const secondaryValue = type === 'pendidikan'
            ? [values.jenjang, values.jurusan].filter(Boolean).join(' ')
            : values.posisi || '';
        secondary.textContent = secondaryValue ? ' - ' + secondaryValue : '';
        title.appendChild(secondary);
        const dates = document.createElement('small');
        dates.textContent = (values.tanggal_mulai || '-') + '  s/d  ' + (values.tanggal_selesai || 'Sekarang');
        entry.append(title, dates);
        if (values[type === 'pendidikan' ? 'keterangan' : 'deskripsi']) {
            const description = document.createElement('p');
            description.className = 'profile-history-description';
            description.textContent = values[type === 'pendidikan' ? 'keterangan' : 'deskripsi'];
            entry.appendChild(description);
        }
        const remove = document.createElement('button');
        remove.className = 'btn btn-danger';
        remove.type = 'button';
        remove.dataset.historyDelete = type;
        remove.dataset.historyKey = key;
        remove.textContent = 'Hapus';
        entry.appendChild(remove);
        const addButton = target.querySelector('.open-history');
        target.insertBefore(entry, addButton || null);
        bindHistory();
    };
    const tambahRiwayat = (type, values = {}) => {
        const list = document.getElementById(type + '-list');
        const index = list.children.length;
        const key = buatKunciRiwayat();
        const row = document.createElement('div');
        row.className = 'form-grid history-row';
        row.dataset.historyKey = key;
        row.innerHTML = fieldConfig[type].map(([name, label]) => `<input name="${type}[${index}][${name}]" placeholder="${label}" value="${(values[name] || '').replaceAll('"', '&quot;')}" ${name.includes('tanggal') ? 'type="date"' : ''} ${name === fieldConfig[type][0][0] ? 'required' : ''}>`).join('') + '<button class="btn btn-danger remove-history" type="button">Hapus</button>';
        list.appendChild(row);
        tambahTampilanRiwayat(type, values, key);
        bindHistory();
    };
    const bindHistory = () => {
        document.querySelectorAll('.remove-history').forEach(button => button.onclick = () => button.closest('.history-row')?.remove());
        document.querySelectorAll('[data-history-delete]').forEach(button => button.onclick = () => {
            const type = button.dataset.historyDelete;
            const key = button.dataset.historyKey;
            document.querySelector(`#${type}-list`)?.querySelector(`[data-history-key="${key}"]`)?.remove();
            button.closest('.profile-history-entry')?.remove();
        });
    };
    bindHistory(); document.getElementById('tambah-pendidikan')?.addEventListener('click', () => tambahRiwayat('pendidikan')); document.getElementById('tambah-pekerjaan')?.addEventListener('click', () => tambahRiwayat('pekerjaan'));
</script>
<?php if ($modeEdit): ?><script>document.getElementById('profile-edit-dialog')?.close();</script><?php endif; ?>
<?php if (isset($cvUntukDiunduh) && $cvUntukDiunduh !== ""): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tautan = document.getElementById('cv-auto-preview');
    if (!tautan) return;
    tautan.click();

    const url = new URL(window.location.href);
    url.searchParams.delete('unduh_cv');
    history.replaceState({}, '', url.pathname + url.search + url.hash);
});
</script>
<?php endif; ?>
<?php if ($modeEdit): ?>
<dialog id="history-dialog" class="history-dialog">
    <form id="history-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
        <input type="hidden" name="karyawan_id" value="<?= (int) $karyawan["id"]; ?>">
        <input type="hidden" name="aksi" value="tambah">
        <input type="hidden" id="history-type" name="jenis">
        <h3 id="history-title"></h3>
        <div id="history-fields" class="history-dialog-fields"></div>
        <div class="history-dialog-actions"><button class="btn btn-secondary" type="button" id="close-history-dialog">Batal</button><button class="btn btn-success" type="submit">Simpan</button></div>
    </form>
</dialog>
<script>
(() => {
    const dialog = document.getElementById('history-dialog');
    const fields = document.getElementById('history-fields');
    const templates = {
        pendidikan: '<label>Institusi<input name="institusi" required></label><label>Jenjang<input name="jenjang"></label><label>Jurusan<input name="jurusan"></label><label>Tanggal mulai<input name="tanggal_mulai" type="date"></label><label>Tanggal selesai<input name="tanggal_selesai" type="date"></label>',
        pekerjaan: '<label>Nama perusahaan<input name="nama_perusahaan" required></label><label>Posisi<input name="posisi"></label><label>Tanggal mulai<input name="tanggal_mulai" type="date"></label><label>Tanggal selesai<input name="tanggal_selesai" type="date"></label><label>Deskripsi<input name="deskripsi"></label>'
    };
    document.querySelectorAll('.open-history').forEach(button => button.addEventListener('click', () => {
        const type = button.dataset.type;
        document.getElementById('history-type').value = type;
        document.getElementById('history-title').textContent = type === 'pendidikan' ? 'Tambah Pendidikan' : 'Tambah Pekerjaan';
        fields.className = 'history-dialog-fields history-dialog-fields-' + type;
        fields.innerHTML = templates[type];
        dialog.showModal();
    }));
    document.getElementById('history-form').addEventListener('submit', event => {
        event.preventDefault();
        const type = document.getElementById('history-type').value;
        const values = Object.fromEntries(new FormData(event.currentTarget).entries());
        const requiredName = fieldConfig[type][0][0];
        if (!String(values[requiredName] || '').trim()) return;
        tambahRiwayat(type, values);
        event.currentTarget.reset();
        dialog.close();
    });
    document.getElementById('close-history-dialog').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
})();
</script>
<?php endif; ?>
<?php
require __DIR__ . "/partials/bawah.php";

if ($modeEdit): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sourceForm = document.querySelector('.profile-inline-edit form');
    const editor = document.querySelector('.profile-inline-edit');
    if (!sourceForm || !editor) return;
    editor.style.display = 'none';

    const fields = <?= json_encode(array_values(array_unique(array_merge(array_keys($fieldEdit), ["alamat", "biografi"]))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const labels = {
        employee_name:'Nama Karyawan', emp_id:'ID Karyawan', nik:'NIK', alamat:'Alamat', tanggal_lahir:'Tanggal Lahir', tanggal_mcu_terakhir:'Tanggal MCU Terakhir', riwayat_pekerjaan:'Riwayat Pekerjaan', tanggal_riwayat_pekerjaan:'Tanggal Riwayat Pekerjaan', riwayat_pendidikan:'Riwayat Pendidikan', tanggal_riwayat_pendidikan:'Tanggal Riwayat Pendidikan', agama:'Agama',
        marital_status:'Status Kawin', kontak:'Kontak', email:'Email', position:'Posisi', department:'Departemen', salary:'Gaji',
        gender:'Jenis Kelamin', employment_status:'Status Kerja', performance_score:'Skor Performa', biografi:'Biodata', keahlian:'Keahlian'
    };
    const detailRows = [...document.querySelectorAll('.profile-details > div')];
    const actionBox = document.querySelector('.profile-actions');
    fields.forEach(function (field) {
        const row = detailRows.find(item => item.querySelector('dt')?.textContent.trim() === labels[field]);
        const source = sourceForm.elements[field];
        if (!row || !source) return;
        const input = source.cloneNode(true);
        input.removeAttribute('id');
        input.classList.add('profile-inline-input');
        input.value = source.value;
        const valueCell = row.querySelector('dd');
        valueCell.replaceChildren(input);
        if (field === 'biografi' || field === 'alamat' || field === 'riwayat_pekerjaan' || field === 'riwayat_pendidikan') input.rows = field === 'biografi' ? 5 : 3;
        input.addEventListener('input', () => { source.value = input.value; });
        input.addEventListener('change', () => { source.value = input.value; });
    });
    const positionsByDepartment = <?= json_encode($posisiPerDepartemen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const department = sourceForm.elements.department;
    const position = sourceForm.elements.position;
    const inlineDepartment = document.querySelector('.profile-inline-input[name="department"]');
    const inlinePosition = document.querySelector('.profile-inline-input[name="position"]');
    const updatePositions = function (restore) {
        if (!department || !position) return;
        const current = restore ? position.value : '';
        const available = positionsByDepartment[department.value] || [];
        const fill = function (target) {
            if (!target) return;
            target.replaceChildren(new Option(available.length ? 'Pilih posisi' : 'Belum ada posisi pada departemen ini', ''));
            available.forEach(item => target.append(new Option(item, item)));
            target.disabled = available.length === 0;
            if (available.includes(current)) target.value = current;
        };
        fill(position); fill(inlinePosition);
    };
    department?.addEventListener('change', () => {
        if (inlineDepartment) inlineDepartment.value = department.value;
        updatePositions(false);
    });
    inlineDepartment?.addEventListener('change', () => {
        department.value = inlineDepartment.value;
        updatePositions(false);
        if (inlinePosition) inlinePosition.value = position.value;
    });
    inlinePosition?.addEventListener('change', () => { position.value = inlinePosition.value; });
    updatePositions(true);
});
</script>
<?php endif; ?>
