<?php

require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";
require_once __DIR__ . "/media-karyawan.php";
require_once __DIR__ . "/sinkronisasi.php";

wajibRole("admin", "superadmin");
siapkanKolomMedia($conn);
siapkanKolomProfil($conn);

$pesan = "";

$form = [
    "employee_name" => "",
    "nik" => "",
    "alamat" => "",
    "biografi" => "",
    "riwayat_pekerjaan" => "", "tanggal_riwayat_pekerjaan" => "", "riwayat_pendidikan" => "", "tanggal_riwayat_pendidikan" => "",
    "tanggal_lahir" => "",
    "agama" => "",
    "marital_status" => "",
    "kontak" => "",
    "email" => "",
    "emp_id" => "",
    "position" => "",
    "department" => "",
    "salary" => "",
    "gender" => "",
    "employment_status" => "",
    "performance_score" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($form as $namaKolom => $nilaiAwal) {
        $form[$namaKolom] = trim($_POST[$namaKolom] ?? "");
    }

    $employeeName = $form["employee_name"];
    $nik = $form["nik"];
    $alamat = $form["alamat"];
    $biografi = $form["biografi"];
    $riwayatPekerjaan = $form["riwayat_pekerjaan"]; $tanggalRiwayatPekerjaan = $form["tanggal_riwayat_pekerjaan"] !== "" ? $form["tanggal_riwayat_pekerjaan"] : null; $riwayatPendidikan = $form["riwayat_pendidikan"]; $tanggalRiwayatPendidikan = $form["tanggal_riwayat_pendidikan"] !== "" ? $form["tanggal_riwayat_pendidikan"] : null;
    $tanggalLahir = $form["tanggal_lahir"] !== "" ? $form["tanggal_lahir"] : null;
    $agama = $form["agama"];
    $maritalStatus = $form["marital_status"];
    $kontak = $form["kontak"];
    $email = $form["email"];
    $empId = $form["emp_id"];
    $position = $form["position"];
    $department = $form["department"];
    $salary = (float) $form["salary"];
    $gender = $form["gender"];
    $employmentStatus = $form["employment_status"];
    $performanceScore = $form["performance_score"];

    if (
        $employeeName === ""
        || $empId === ""
        || $position === ""
        || $department === ""
        || $form["salary"] === ""
        || $gender === ""
        || $employmentStatus === ""
        || $performanceScore === ""
    ) {
        $pesan = "Semua kolom wajib diisi.";
    } elseif ($salary < 0) {
        $pesan = "Gaji tidak boleh bernilai negatif.";
    } else {
        $fileCv = unggahMediaKaryawan($_FILES["file_cv"] ?? [], "cv", $pesan);
        $fileIjazah = unggahMediaKaryawan($_FILES["file_ijazah"] ?? [], "ijazah", $pesan);
        $fileMcu = unggahMediaKaryawan($_FILES["file_mcu"] ?? [], "mcu", $pesan);
        $fotoProfil = unggahMediaKaryawan($_FILES["foto_profil"] ?? [], "foto", $pesan);

        if ($pesan === "") {
            $sql = "INSERT INTO karyawan (
                        employee_name,
                        nik, alamat, biografi, riwayat_pekerjaan, tanggal_riwayat_pekerjaan, riwayat_pendidikan, tanggal_riwayat_pendidikan, tanggal_lahir, agama, marital_status, kontak, email,
                        emp_id,
                        position,
                        department,
                        salary,
                        gender,
                        employment_status,
                        performance_score,
                        file_cv,
                        foto_profil,
                        file_ijazah,
                        file_mcu
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                $pesan = "Query gagal disiapkan: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    str_repeat("s", 16) . "d" . str_repeat("s", 7),
                    $employeeName,
                    $nik,
                    $alamat,
                    $biografi, $riwayatPekerjaan, $tanggalRiwayatPekerjaan, $riwayatPendidikan, $tanggalRiwayatPendidikan,
                    $tanggalLahir,
                    $agama,
                    $maritalStatus,
                    $kontak,
                    $email,
                    $empId,
                    $position,
                    $department,
                    $salary,
                    $gender,
                    $employmentStatus,
                    $performanceScore,
                    $fileCv,
                    $fotoProfil,
                    $fileIjazah,
                    $fileMcu
                );

                if (mysqli_stmt_execute($stmt)) {
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

                if (mysqli_stmt_errno($stmt) === 1062) {
                    $pesan = "ID karyawan sudah digunakan. Gunakan ID yang berbeda.";
                } else {
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
                    <input
                        type="text"
                        id="department"
                        name="department"
                        value="<?= htmlspecialchars($form["department"]); ?>"
                        placeholder="Contoh: Teknologi Informasi"
                        maxlength="120"
                        required>
                </div>


                <div class="form-group">
                    <label for="position">
                        Posisi <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="position"
                        name="position"
                        value="<?= htmlspecialchars($form["position"]); ?>"
                        placeholder="Contoh: Software Engineer"
                        maxlength="120"
                        required>
                </div>

                <div class="form-group">
                    <label for="employment_status">
                        Status Kerja <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="employment_status"
                        name="employment_status"
                        value="<?= htmlspecialchars($form["employment_status"]); ?>"
                        placeholder="Contoh: Aktif"
                        maxlength="100"
                        required>
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
                <div class="form-group"><label for="agama">Agama</label><input type="text" id="agama" name="agama" value="<?= htmlspecialchars($form["agama"]); ?>" maxlength="50"></div>
                <div class="form-group"><label for="marital_status">Status Kawin</label><select id="marital_status" name="marital_status">
                        <option value="">Pilih status</option>
                        <option <?= $form["marital_status"] === "Single" ? "selected" : ""; ?>>Single</option>
                        <option <?= $form["marital_status"] === "Married" ? "selected" : ""; ?>>Married</option>
                    </select></div>
                <div class="form-group"><label for="kontak">Kontak</label><input type="text" id="kontak" name="kontak" value="<?= htmlspecialchars($form["kontak"]); ?>" maxlength="50"></div>
                <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($form["email"]); ?>" maxlength="150"></div>
                <div class="form-group form-group-full"><label for="alamat">Alamat</label><textarea id="alamat" name="alamat" rows="3"><?= htmlspecialchars($form["alamat"]); ?></textarea></div>
                <div class="form-group form-group-full"><label for="biografi">Biografi Diri</label><textarea id="biografi" name="biografi" rows="5" maxlength="2000" placeholder="Tuliskan ringkasan diri, pengalaman, keahlian, atau tujuan karier karyawan."><?= htmlspecialchars($form["biografi"]); ?></textarea></div>
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
<?php
require __DIR__ . "/../partials/bawah.php";
