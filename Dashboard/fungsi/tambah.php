<?php

require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";
require_once __DIR__ . "/media-karyawan.php";
require_once __DIR__ . "/sinkronisasi.php";
require_once __DIR__ . "/master-data.php";

wajibRole("admin", "superadmin");
siapkanMasterData($conn);
$masterDepartemen = ambilMasterData($conn, "department"); $masterPosisi = ambilMasterData($conn, "position"); $masterStatus = ambilMasterData($conn, "employment_status"); $masterAgama = ambilMasterData($conn, "agama");

$pesan = "";
$riwayatPendidikanForm = [];
$riwayatPekerjaanForm = [];

$form = [
    "employee_name" => "",
    "nik" => "",
    "alamat" => "",
    "biografi" => "",
    "keahlian" => "",
    "riwayat_pekerjaan" => "", "tanggal_riwayat_pekerjaan" => "", "riwayat_pendidikan" => "", "tanggal_riwayat_pendidikan" => "",
    "tanggal_lahir" => "",
    "tanggal_mcu_terakhir" => "",
    "agama" => "",
    "marital_status" => "",
    "kontak" => "",
    "email" => "",
    "emp_id" => buatIdKaryawanOtomatis($conn),
    "position" => "",
    "department" => "",
    "salary" => "",
    "gender" => "",
    "date_of_hire" => "",
    "employment_status" => "",
    "performance_score" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($form as $namaKolom => $nilaiAwal) {
        $form[$namaKolom] = trim($_POST[$namaKolom] ?? "");
    }

    $riwayatPendidikanForm = array_values(array_filter((array) ($_POST["pendidikan"] ?? []), static fn($item): bool => is_array($item) && trim((string) ($item["institusi"] ?? "")) !== ""));
    $riwayatPekerjaanForm = array_values(array_filter((array) ($_POST["pekerjaan"] ?? []), static fn($item): bool => is_array($item) && trim((string) ($item["nama_perusahaan"] ?? "")) !== ""));

    $employeeName = $form["employee_name"];
    $nik = $form["nik"];
    $alamat = $form["alamat"];
    $biografi = $form["biografi"];
    $keahlian = $form["keahlian"];
    $riwayatPekerjaan = trim((string) ($riwayatPekerjaanForm[0]["nama_perusahaan"] ?? ""));
    $tanggalRiwayatPekerjaan = trim((string) ($riwayatPekerjaanForm[0]["tanggal_selesai"] ?? "")) ?: null;
    $riwayatPendidikan = trim((string) ($riwayatPendidikanForm[0]["institusi"] ?? ""));
    $tanggalRiwayatPendidikan = trim((string) ($riwayatPendidikanForm[0]["tanggal_selesai"] ?? "")) ?: null;
    $tanggalLahir = $form["tanggal_lahir"] !== "" ? $form["tanggal_lahir"] : null;
    $tanggalMcuTerakhir = $form["tanggal_mcu_terakhir"] !== "" ? $form["tanggal_mcu_terakhir"] : null;
    $agama = $form["agama"];
    $maritalStatus = $form["marital_status"];
    $kontak = $form["kontak"];
    $email = $form["email"];
    $empId = $form["emp_id"];
    $position = $form["position"];
    $department = $form["department"];
    $salary = (float) $form["salary"];
    $gender = $form["gender"];
    $dateOfHire = $form["date_of_hire"];
    $employmentStatus = $form["employment_status"];
    $performanceScore = $form["performance_score"];

    if (
        $employeeName === ""
        || $empId === ""
        || $position === ""
        || $department === ""
        || $form["salary"] === ""
        || $gender === ""
        || $dateOfHire === ""
        || $employmentStatus === ""
        || $performanceScore === ""
    ) {
        $pesan = "Semua kolom wajib diisi.";
    } elseif ($salary < 0) {
        $pesan = "Gaji tidak boleh bernilai negatif.";
    } elseif (
        !preg_match("/^\d{4}-\d{2}-\d{2}$/", $dateOfHire)
        || !checkdate(
            (int) substr($dateOfHire, 5, 2),
            (int) substr($dateOfHire, 8, 2),
            (int) substr($dateOfHire, 0, 4)
        )
    ) {
        $pesan = "Tanggal masuk tidak valid.";
    } else {
        $fileCv = unggahMediaKaryawan($_FILES["file_cv"] ?? [], "cv", $pesan);
        $fileIjazah = unggahMediaKaryawan($_FILES["file_ijazah"] ?? [], "ijazah", $pesan);
        $fileMcu = unggahMediaKaryawan($_FILES["file_mcu"] ?? [], "mcu", $pesan);
        $fotoProfil = unggahMediaKaryawan($_FILES["foto_profil"] ?? [], "foto", $pesan);

        if ($pesan === "") {
            mysqli_begin_transaction($conn);
            $sql = "INSERT INTO karyawan (
                        employee_name,
                        nik, alamat, biografi, keahlian, riwayat_pekerjaan, tanggal_riwayat_pekerjaan, riwayat_pendidikan, tanggal_riwayat_pendidikan, tanggal_lahir, tanggal_mcu_terakhir, agama, marital_status, kontak, email,
                        emp_id,
                        position,
                        department,
                        salary,
                        gender,
                        date_of_hire,
                        employment_status,
                        performance_score,
                        file_cv,
                        foto_profil,
                        file_ijazah,
                        file_mcu
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                mysqli_rollback($conn);
                $pesan = "Query gagal disiapkan: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    str_repeat("s", 18) . "d" . str_repeat("s", 8),
                    $employeeName,
                    $nik,
                    $alamat,
                    $biografi, $keahlian, $riwayatPekerjaan, $tanggalRiwayatPekerjaan, $riwayatPendidikan, $tanggalRiwayatPendidikan,
                    $tanggalLahir,
                    $tanggalMcuTerakhir,
                    $agama,
                    $maritalStatus,
                    $kontak,
                    $email,
                    $empId,
                    $position,
                    $department,
                    $salary,
                    $gender,
                    $dateOfHire,
                    $employmentStatus,
                    $performanceScore,
                    $fileCv,
                    $fotoProfil,
                    $fileIjazah,
                    $fileMcu
                );

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_query($conn, "UPDATE karyawan k INNER JOIN master_departemen d ON d.nama = k.department SET k.department_id = d.id WHERE k.id = " . (int) mysqli_insert_id($conn));
                    try {
                        $karyawanBaruId = (int) mysqli_insert_id($conn);
                        if ($riwayatPendidikanForm !== []) {
                            $stmtPendidikan = mysqli_prepare($conn, "INSERT INTO riwayat_pendidikan (karyawan_id, institusi, jenjang, jurusan, tanggal_mulai, tanggal_selesai, keterangan) VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))");
                            foreach ($riwayatPendidikanForm as $item) {
                                $institusi = trim((string) ($item["institusi"] ?? "")); $jenjang = trim((string) ($item["jenjang"] ?? "")); $jurusan = trim((string) ($item["jurusan"] ?? ""));
                                $tanggalMulai = trim((string) ($item["tanggal_mulai"] ?? "")); $tanggalSelesai = trim((string) ($item["tanggal_selesai"] ?? "")); $keterangan = trim((string) ($item["keterangan"] ?? ""));
                                mysqli_stmt_bind_param($stmtPendidikan, "issssss", $karyawanBaruId, $institusi, $jenjang, $jurusan, $tanggalMulai, $tanggalSelesai, $keterangan); mysqli_stmt_execute($stmtPendidikan);
                            }
                            mysqli_stmt_close($stmtPendidikan);
                        }
                        if ($riwayatPekerjaanForm !== []) {
                            $stmtPekerjaan = mysqli_prepare($conn, "INSERT INTO riwayat_pekerjaan (karyawan_id, nama_perusahaan, posisi, departemen, tanggal_mulai, tanggal_selesai, deskripsi) VALUES (?, ?, NULLIF(?, ''), NULL, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))");
                            foreach ($riwayatPekerjaanForm as $item) {
                                $namaPerusahaan = trim((string) ($item["nama_perusahaan"] ?? "")); $posisiRiwayat = trim((string) ($item["posisi"] ?? ""));
                                $tanggalMulai = trim((string) ($item["tanggal_mulai"] ?? "")); $tanggalSelesai = trim((string) ($item["tanggal_selesai"] ?? "")); $deskripsiRiwayat = trim((string) ($item["deskripsi"] ?? ""));
                                mysqli_stmt_bind_param($stmtPekerjaan, "isssss", $karyawanBaruId, $namaPerusahaan, $posisiRiwayat, $tanggalMulai, $tanggalSelesai, $deskripsiRiwayat); mysqli_stmt_execute($stmtPekerjaan);
                            }
                            mysqli_stmt_close($stmtPekerjaan);
                        }
                        mysqli_commit($conn);
                    } catch (Throwable $error) {
                        mysqli_rollback($conn);
                        $pesan = "Data riwayat gagal disimpan: " . $error->getMessage();
                        error_log($pesan);
                    }

                    if ($pesan === "") {
                        try {
                            sinkronkanSemuaDataset($conn);
                        } catch (Throwable $error) {
                            error_log("Sinkronisasi CSV gagal: " . $error->getMessage());
                        }
                        catatAktivitas($conn, "Menambahkan karyawan " . $employeeName . " (" . $empId . ").");
                        mysqli_stmt_close($stmt);
                        header("Location: ../karyawan.php?pesan=tambah-berhasil");
                        exit;
                    }
                }

                if ($pesan === "" && mysqli_stmt_errno($stmt) === 1062) {
                    $pesan = "ID karyawan sudah digunakan. Gunakan ID yang berbeda.";
                } elseif ($pesan === "") {
                    mysqli_rollback($conn);
                    $pesan = "Data gagal ditambahkan: " . mysqli_stmt_error($stmt);
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

$judulHalaman = "Tambah Karyawan";
$subjudulHalaman = "Masukkan informasi karyawan baru ke dalam database.";
$halamanAktif = "tambah";

require __DIR__ . "/../partials/atas.php";

?>
<section class="form-card">
    <div class="form-card-header">
        <h2>Form Data Karyawan</h2>
        <p>
            Kolom bertanda bintang wajib diisi. Setelah disimpan,
            data SQL akan disinkronkan ke dataset lokal.
        </p>
    </div>

    <div class="form-body">
        <?php if ($pesan !== ""): ?>
            <div class="alert-error" role="alert">
                <?= htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label for="emp_id">
                        ID Karyawan <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="emp_id"
                        name="emp_id"
                        value="<?= htmlspecialchars($form["emp_id"]); ?>"
                        readonly
                        placeholder="Contoh: EMP001"
                        maxlength="50"
                        required
                        autofocus>
                    <p class="field-note">Gunakan ID unik untuk setiap karyawan.</p>
                </div>

                <div class="form-group">
                    <label for="employee_name">
                        Nama Karyawan <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="employee_name"
                        name="employee_name"
                        value="<?= htmlspecialchars($form["employee_name"]); ?>"
                        placeholder="Masukkan nama lengkap"
                        maxlength="150"
                        required>
                </div>

                <div class="form-group">
                    <label for="department">
                        Departemen <span class="required">*</span>
                    </label>
                    <select
                        id="department"
                        name="department"
                        required><option value="">Pilih departemen</option><?php foreach ($masterDepartemen as $item): ?><option <?= $form["department"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
                </div>


                <div class="form-group">
                    <label for="position">
                        Posisi <span class="required">*</span>
                    </label>
                    <select
                        type="text"
                        id="position"
                        name="position"
                        required><option value="">Pilih posisi</option><?php foreach ($masterPosisi as $item): ?><option <?= $form["position"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
                </div>

                <div class="form-group">
                    <label for="employment_status">
                        Status Kerja <span class="required">*</span>
                    </label>
                    <select
                        id="employment_status"
                        name="employment_status"
                            required><option value="">Pilih status kerja</option><?php foreach ($masterStatus as $item): ?><option <?= $form["employment_status"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
                </div>

                <div class="form-group">
                    <label for="salary">
                        Gaji <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="salary"
                        name="salary"
                        value="<?= htmlspecialchars($form["salary"]); ?>"
                        placeholder="0"
                        min="0"
                        step="0.01"
                        required>
                    <p class="field-note">Masukkan angka tanpa pemisah ribuan.</p>
                </div>

                <div class="form-group">
                    <label for="foto_profil">Foto Profil</label>
                    <input type="file" id="foto_profil" name="foto_profil" accept=".jpg,.jpeg,image/jpeg">
                    <p class="field-note">JPG/JPEG, maksimal 2 MB. Boleh dikosongkan.</p>
                </div>

                <div class="form-group">
                    <label for="file_cv">CV</label>
                    <input type="file" id="file_cv" name="file_cv" accept=".pdf,application/pdf">
                    <p class="field-note">PDF, maksimal 5 MB. Boleh dikosongkan.</p>
                </div>
                <div class="form-group"><label for="file_ijazah">Ijazah</label><input type="file" id="file_ijazah" name="file_ijazah" accept=".pdf,application/pdf">
                    <p class="field-note">PDF, maksimal 5 MB. Boleh dikosongkan.</p>
                </div>
                <div class="form-group"><label for="file_mcu">MCU</label><input type="file" id="file_mcu" name="file_mcu" accept=".pdf,application/pdf">
                    <p class="field-note">PDF, maksimal 5 MB. Boleh dikosongkan.</p>
                </div>



                <div class="form-group"><label for="nik">NIK</label><input type="text" id="nik" name="nik" value="<?= htmlspecialchars($form["nik"]); ?>" maxlength="50"></div>
                <div class="form-group"><label for="tanggal_lahir">Tanggal Lahir</label><input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars($form["tanggal_lahir"]); ?>"></div>
                <div class="form-group"><label for="tanggal_mcu_terakhir">Tanggal MCU Terakhir</label><input type="date" id="tanggal_mcu_terakhir" name="tanggal_mcu_terakhir" value="<?= htmlspecialchars($form["tanggal_mcu_terakhir"]); ?>"></div>
                <div class="form-group"><label for="agama">Agama</label><select id="agama" name="agama"><option value="">Pilih agama</option><?php foreach ($masterAgama as $item): ?><option value="<?= htmlspecialchars($item); ?>" <?= $form["agama"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="marital_status">Status Kawin</label><select id="marital_status" name="marital_status">
                        <option value="">Pilih status</option>
                        <option <?= $form["marital_status"] === "Single" ? "selected" : ""; ?>>Single</option>
                        <option <?= $form["marital_status"] === "Married" ? "selected" : ""; ?>>Married</option>
                    </select></div>
                <div class="form-group"><label for="kontak">Kontak</label><input type="text" id="kontak" name="kontak" value="<?= htmlspecialchars($form["kontak"]); ?>" maxlength="50"></div>
                <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($form["email"]); ?>" maxlength="150"></div>
                <div class="form-group form-group-full"><label for="alamat">Alamat</label><textarea id="alamat" name="alamat" rows="3"><?= htmlspecialchars($form["alamat"]); ?></textarea></div>
                <div class="form-group form-group-full"><label for="biografi">Biografi Diri</label><textarea id="biografi" name="biografi" rows="5" maxlength="2000" placeholder="Tuliskan ringkasan diri, pengalaman, atau tujuan karier karyawan."><?= htmlspecialchars($form["biografi"]); ?></textarea></div>
                <div class="form-group form-group-full"><label for="keahlian">Keahlian</label><textarea id="keahlian" name="keahlian" rows="4" maxlength="2000" placeholder="Contoh: Komunikasi, analisis data, Microsoft Office."><?= htmlspecialchars($form["keahlian"]); ?></textarea></div>
                <div class="form-group form-group-full"><label for="riwayat_pekerjaan">Riwayat Pekerjaan</label><textarea id="riwayat_pekerjaan" name="riwayat_pekerjaan" rows="4"><?= htmlspecialchars($form["riwayat_pekerjaan"]); ?></textarea></div>
                <div class="form-group"><label for="tanggal_riwayat_pekerjaan">Tanggal Riwayat Pekerjaan</label><input type="date" id="tanggal_riwayat_pekerjaan" name="tanggal_riwayat_pekerjaan" value="<?= htmlspecialchars($form["tanggal_riwayat_pekerjaan"]); ?>"></div>
                <div class="form-group form-group-full"><label for="riwayat_pendidikan">Riwayat Pendidikan</label><textarea id="riwayat_pendidikan" name="riwayat_pendidikan" rows="4"><?= htmlspecialchars($form["riwayat_pendidikan"]); ?></textarea></div>
                <div class="form-group"><label for="tanggal_riwayat_pendidikan">Tanggal Riwayat Pendidikan</label><input type="date" id="tanggal_riwayat_pendidikan" name="tanggal_riwayat_pendidikan" value="<?= htmlspecialchars($form["tanggal_riwayat_pendidikan"]); ?>"></div>




                <div class="form-group">
                    <label for="gender">
                        Jenis Kelamin <span class="required">*</span>
                    </label>
                    <select id="gender" name="gender" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option
                            value="M"
                            <?= $form["gender"] === "M" ? "selected" : ""; ?>>
                            Laki-laki
                        </option>
                        <option
                            value="F"
                            <?= $form["gender"] === "F" ? "selected" : ""; ?>>
                            Perempuan
                        </option>
                    </select>
                </div>



                <div class="form-group">
                    <label for="performance_score">
                        Skor Performa <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="performance_score"
                        name="performance_score"
                        value="<?= htmlspecialchars($form["performance_score"]); ?>"
                        placeholder="Masukkan nilai 1-100"
                        min="1"
                        max="100"
                        step="1"
                        inputmode="numeric"
                        required>
                </div>

                <div class="form-group">
                    <label for="date_of_hire">
                        Tanggal Masuk <span class="required">*</span>
                    </label>
                    <input
                        type="date"
                        id="date_of_hire"
                        name="date_of_hire"
                        value="<?= htmlspecialchars($form["date_of_hire"]); ?>"
                        required>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= htmlspecialchars(URL_DASAR); ?>karyawan.php" class="btn btn-secondary">
                    Batal
                </a>
                <button type="submit" class="btn btn-success">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const grid = document.querySelector('.tambah-page .form-grid');
    if (!grid) return;

    const groups = {};
    grid.querySelectorAll('.form-group').forEach(function (group) {
        const field = group.querySelector('[name]');
        if (field) {
            groups[field.name] = group;
            group.classList.add('field-' + field.name);
        }
    });

    const makeCard = function (title, fields, extraClass) {
        const card = document.createElement('section');
        card.className = 'tambah-layout-card ' + (extraClass || '');
        card.innerHTML = '<h3>' + title + '</h3>';
        fields.forEach(function (name) {
            if (groups[name]) card.appendChild(groups[name]);
        });
        return card;
    };

    const cards = [
        makeCard('Profil Karyawan', ['employee_name','emp_id','department','position','employment_status','salary','performance_score','date_of_hire'], 'tambah-main-card'),
        makeCard('Biodata Karyawan', ['nik','alamat','tanggal_lahir','agama','gender','marital_status','kontak','email'], 'tambah-personal-card'),
        makeCard('Informasi Karyawan', ['biografi','keahlian'], 'tambah-history-card'),
        makeCard('Berkas Pendukung', ['foto_profil','file_cv','file_ijazah','file_mcu','tanggal_mcu_terakhir'], 'tambah-documents-card')
    ];
    const biodataCard = cards[1];
    const historyCard = cards[2];
    biodataCard.insertAdjacentHTML('beforeend', '<section class="history-section"><div class="history-heading"><h4>Riwayat Pendidikan</h4><button class="history-add" id="add-education" type="button">Tambah +</button></div><div id="education-list" class="history-list"></div></section>');
    historyCard.insertAdjacentHTML('beforeend', '<section class="history-section"><div class="history-heading"><h4>Riwayat Pekerjaan</h4><button class="history-add" id="add-work" type="button">Tambah +</button></div><div id="work-list" class="history-list"></div></section>');

    const educationList = biodataCard.querySelector('#education-list');
    const workList = historyCard.querySelector('#work-list');
    let educationIndex = 0;
    let workIndex = 0;
    const educationInitial = <?= json_encode($riwayatPendidikanForm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const workInitial = <?= json_encode($riwayatPekerjaanForm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const esc = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));

    const addEducation = (data = {}) => {
        const index = educationIndex++;
        const row = document.createElement('div');
        row.className = 'history-entry history-entry-education';
        row.innerHTML = `<label>Institusi<input name="pendidikan[${index}][institusi]" value="${esc(data.institusi)}"></label><label>Jenjang<input name="pendidikan[${index}][jenjang]" value="${esc(data.jenjang)}"></label><label>Jurusan<input name="pendidikan[${index}][jurusan]" value="${esc(data.jurusan)}"></label><label>Tanggal mulai<input type="date" name="pendidikan[${index}][tanggal_mulai]" value="${esc(data.tanggal_mulai)}"></label><label>Tanggal selesai<input type="date" name="pendidikan[${index}][tanggal_selesai]" value="${esc(data.tanggal_selesai)}"></label><button class="history-remove" type="button" aria-label="Hapus riwayat pendidikan">&#128465;</button>`;
        row.querySelector('.history-remove').addEventListener('click', () => row.remove());
        educationList.appendChild(row);
    };
    const addWork = (data = {}) => {
        const index = workIndex++;
        const row = document.createElement('div');
        row.className = 'history-entry history-entry-work';
        row.innerHTML = `<label>Nama perusahaan<input name="pekerjaan[${index}][nama_perusahaan]" value="${esc(data.nama_perusahaan)}"></label><label>Posisi<input name="pekerjaan[${index}][posisi]" value="${esc(data.posisi)}"></label><label>Tanggal mulai<input type="date" name="pekerjaan[${index}][tanggal_mulai]" value="${esc(data.tanggal_mulai)}"></label><label>Tanggal selesai<input type="date" name="pekerjaan[${index}][tanggal_selesai]" value="${esc(data.tanggal_selesai)}"></label><label>Deskripsi<input name="pekerjaan[${index}][deskripsi]" value="${esc(data.deskripsi)}"></label><button class="history-remove" type="button" aria-label="Hapus riwayat pekerjaan">&#128465;</button>`;
        row.querySelector('.history-remove').addEventListener('click', () => row.remove());
        workList.appendChild(row);
    };
    (educationInitial.length ? educationInitial : [{}]).forEach(addEducation);
    (workInitial.length ? workInitial : [{}]).forEach(addWork);
    biodataCard.querySelector('#add-education').addEventListener('click', () => addEducation());
    historyCard.querySelector('#add-work').addEventListener('click', () => addWork());

    grid.replaceChildren(...cards);
});
</script>
<?php
require __DIR__ . "/../partials/bawah.php";
