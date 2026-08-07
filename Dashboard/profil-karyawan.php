<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/media-karyawan.php";
require_once __DIR__ . "/fungsi/master-data.php";

wajibLogin();
siapkanKolomMedia($conn);
siapkanKolomProfil($conn);
siapkanMasterData($conn);
$masterDepartemen = ambilMasterData($conn, "department"); $masterPosisi = ambilMasterData($conn, "position"); $masterStatus = ambilMasterData($conn, "employment_status");

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
$modeEdit = isset($_GET["edit"]) && $_GET["edit"] === "1" && punyaRole("admin", "superadmin");

require __DIR__ . "/partials/atas.php";

?>
<section class="profile-page">
    <div class="profile-hero">
        <p class="profile-kicker">Profil Internal</p>
        <h2><?= htmlspecialchars($nama); ?></h2>
        <p>Ringkasan informasi karyawan berdasarkan data yang tersimpan.</p>
    </div>

    <?php if ($modeEdit): ?>
        <article class="profile-card profile-inline-edit">
            <h3>Edit Data Karyawan</h3>
            <form id="inline-edit-form" method="POST" action="fungsi/edit.php?id=<?= (int) $karyawan["id"]; ?>" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="return_to_profile" value="1">
                <div class="profile-edit-grid">
                    <?php
                    $fieldEdit = [
                        "employee_name" => "Nama Karyawan", "emp_id" => "ID Karyawan", "nik" => "NIK",
                        "tanggal_lahir" => "Tanggal Lahir", "riwayat_pekerjaan" => "Riwayat Pekerjaan", "tanggal_riwayat_pekerjaan" => "Tanggal Riwayat Pekerjaan", "riwayat_pendidikan" => "Riwayat Pendidikan", "tanggal_riwayat_pendidikan" => "Tanggal Riwayat Pendidikan", "agama" => "Agama", "marital_status" => "Status Kawin",
                        "kontak" => "Kontak", "email" => "Email", "position" => "Posisi", "department" => "Departemen",
                        "salary" => "Gaji", "gender" => "Jenis Kelamin", "employment_status" => "Status Kerja",
                        "performance_score" => "Skor Performa"
                    ];
                    foreach ($fieldEdit as $field => $label):
                        $type = str_starts_with($field, "tanggal_") ? "date" : ($field === "email" ? "email" : ($field === "salary" || $field === "performance_score" ? "number" : "text"));
                    ?>
                        <div class="form-group">
                            <label for="profile_<?= $field; ?>"><?= $label; ?></label>
                            <?php if ($field === "position" || $field === "department" || $field === "employment_status"): ?>
                                <select id="profile_<?= $field; ?>" name="<?= $field; ?>" required><option value="">Pilih</option><?php foreach (($field === "position" ? $masterPosisi : ($field === "department" ? $masterDepartemen : $masterStatus)) as $item): ?><option <?= ($karyawan[$field] ?? "") === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
                            <?php elseif ($field === "alamat" || $field === "biografi"): ?>
                                <textarea id="profile_<?= $field; ?>" name="<?= $field; ?>" rows="4"><?= htmlspecialchars((string) ($karyawan[$field] ?? "")); ?></textarea>
                            <?php elseif ($field === "gender"): ?>
                                <select id="profile_<?= $field; ?>" name="<?= $field; ?>" required><option value="M" <?= ($karyawan[$field] ?? "") === "M" ? "selected" : ""; ?>>Laki-laki</option><option value="F" <?= ($karyawan[$field] ?? "") === "F" ? "selected" : ""; ?>>Perempuan</option></select>
                            <?php elseif ($field === "marital_status"): ?>
                                <select id="profile_<?= $field; ?>" name="<?= $field; ?>"><option value="">Pilih status</option><option <?= ($karyawan[$field] ?? "") === "Single" ? "selected" : ""; ?>>Single</option><option <?= ($karyawan[$field] ?? "") === "Married" ? "selected" : ""; ?>>Married</option></select>
                            <?php else: ?>
                                <input id="profile_<?= $field; ?>" type="<?= $type; ?>" name="<?= $field; ?>" value="<?= htmlspecialchars((string) ($karyawan[$field] ?? "")); ?>" <?= in_array($field, ["employee_name", "emp_id", "position", "department", "salary", "gender", "employment_status", "performance_score"], true) ? "required" : ""; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="form-group form-group-full"><label for="profile_alamat">Alamat</label><textarea id="profile_alamat" name="alamat" rows="3"><?= htmlspecialchars((string) ($karyawan["alamat"] ?? "")); ?></textarea></div>
                    <div class="form-group form-group-full"><label for="profile_biografi">Biografi Diri</label><textarea id="profile_biografi" name="biografi" rows="5" maxlength="2000"><?= htmlspecialchars((string) ($karyawan["biografi"] ?? "")); ?></textarea></div>
                </div>
                <div class="profile-actions"><button class="btn btn-success" type="submit">Simpan Perubahan</button><a class="btn btn-secondary" href="profil-karyawan.php?id=<?= (int) $karyawan["id"]; ?>">Batal</a></div>
            </form>
        </article>
    <?php endif; ?>

    <div class="profile-grid">
        <article class="profile-card">
            <h3>Profil Karyawan</h3>

            <div class="profile-photo-wrap">
                <?php if (!empty($karyawan["foto_profil"] ?? "") && is_file(__DIR__ . "/uploads/foto/" . basename((string) $karyawan["foto_profil"]))): ?>
                    <img class="profile-photo" src="uploads/foto/<?= rawurlencode(basename((string) $karyawan["foto_profil"])); ?>" alt="Foto <?= htmlspecialchars($nama); ?>">
                <?php else: ?>
                    <div class="profile-photo profile-photo-empty">Belum ada foto</div>
                <?php endif; ?>
                <?php if ($modeEdit): ?>
                    <div class="profile-photo-edit"><label for="inline_foto_profil">Ganti Foto Profil</label><input id="inline_foto_profil" type="file" name="foto_profil" form="inline-edit-form" accept=".jpg,.jpeg,image/jpeg"><p class="field-note">JPG/JPEG, maksimal 2 MB.</p></div>
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

        <article class="profile-card profile-history-card">
            <h3>Informasi Karyawan</h3>
            <dl class="profile-details">
                <div><dt>Riwayat Pekerjaan</dt><dd><?= nl2br(htmlspecialchars(nilaiProfil($karyawan, "riwayat_pekerjaan"))); ?></dd></div>
                <div><dt>Tanggal Riwayat Pekerjaan</dt><dd><?= htmlspecialchars(tanggalProfil($karyawan, "tanggal_riwayat_pekerjaan")); ?></dd></div>
                <div><dt>Riwayat Pendidikan</dt><dd><?= nl2br(htmlspecialchars(nilaiProfil($karyawan, "riwayat_pendidikan"))); ?></dd></div>
                <div><dt>Tanggal Riwayat Pendidikan</dt><dd><?= htmlspecialchars(tanggalProfil($karyawan, "tanggal_riwayat_pendidikan")); ?></dd></div>
            </dl>
        </article>

        <article class="profile-card profile-detail-card">
            <h3>Detail Karyawan</h3>
            <dl class="profile-details">
                <div class="profile-biography-row">
                    <dt>Biografi Diri</dt>
                    <dd><?= nl2br(htmlspecialchars(nilaiProfil($karyawan, "biografi"))); ?></dd>
                </div>
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

        <article class="profile-card profile-documents-card" id="dokumen">
            <h3>Dokumen Pendukung</h3>
            <?php if ($modeEdit): ?>
                <div class="profile-document-edit-grid">
                    <div class="form-group"><label for="inline_file_cv">Ganti CV</label><input id="inline_file_cv" type="file" name="file_cv" form="inline-edit-form" accept=".pdf,application/pdf"><p class="field-note">PDF, maksimal 5 MB.</p></div>
                    <div class="form-group"><label for="inline_file_ijazah">Ganti Ijazah</label><input id="inline_file_ijazah" type="file" name="file_ijazah" form="inline-edit-form" accept=".pdf,application/pdf"><p class="field-note">PDF, maksimal 5 MB.</p></div>
                    <div class="form-group"><label for="inline_file_mcu">Ganti MCU</label><input id="inline_file_mcu" type="file" name="file_mcu" form="inline-edit-form" accept=".pdf,application/pdf"><p class="field-note">PDF, maksimal 5 MB.</p></div>
                </div>
            <?php endif; ?>
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
            <?php if ($modeEdit): ?>
                <a class="btn btn-secondary" href="profil-karyawan.php?id=<?= (int) $karyawan["id"]; ?>">Batal</a>
                <button class="btn btn-success" type="submit" form="inline-edit-form">Simpan Perubahan</button>
            <?php else: ?>
                <a class="btn btn-warning" href="profil-karyawan.php?id=<?= (int) $karyawan["id"]; ?>&edit=1">Edit Data</a>
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
        <a id="cv-auto-preview" aria-hidden="true" style="display: none;" target="_blank" rel="noopener" href="uploads/cv/<?= rawurlencode($cvUntukDiunduh); ?>">Lihat CV</a>
    <?php endif; ?>
</section>
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
<?php
require __DIR__ . "/partials/bawah.php";

if ($modeEdit): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sourceForm = document.querySelector('.profile-inline-edit form');
    const editor = document.querySelector('.profile-inline-edit');
    if (!sourceForm || !editor) return;
    editor.style.display = 'none';

    const fields = ['employee_name','emp_id','nik','alamat','tanggal_lahir','riwayat_pekerjaan','tanggal_riwayat_pekerjaan','riwayat_pendidikan','tanggal_riwayat_pendidikan','agama','marital_status','kontak','email','position','department','salary','gender','employment_status','performance_score','biografi'];
    const labels = {
        employee_name:'Nama Karyawan', emp_id:'ID Karyawan', nik:'NIK', alamat:'Alamat', tanggal_lahir:'Tanggal Lahir', riwayat_pekerjaan:'Riwayat Pekerjaan', tanggal_riwayat_pekerjaan:'Tanggal Riwayat Pekerjaan', riwayat_pendidikan:'Riwayat Pendidikan', tanggal_riwayat_pendidikan:'Tanggal Riwayat Pendidikan', agama:'Agama',
        marital_status:'Status Kawin', kontak:'Kontak', email:'Email', position:'Posisi', department:'Departemen', salary:'Gaji',
        gender:'Jenis Kelamin', employment_status:'Status Kerja', performance_score:'Skor Performa', biografi:'Biografi Diri'
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
});
</script>
<?php endif; ?>
