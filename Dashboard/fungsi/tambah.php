<?php

require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/sinkronisasi.php";
require_once __DIR__ . "/media-karyawan.php";
require_once __DIR__ . "/audit.php";

wajibRole("admin", "superadmin");
siapkanKolomMedia($conn);

$pesan = "";

$form = [
    "employee_name" => "",
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
        $fileCv = unggahMediaKaryawan($_FILES['file_cv'] ?? [], 'cv', $pesan);
        $fotoProfil = unggahMediaKaryawan($_FILES['foto_profil'] ?? [], 'foto', $pesan);
        if ($pesan !== '') {
            $fileCv = null;
            $fotoProfil = null;
        }
        if ($pesan === '') {
        $sql = "INSERT INTO karyawan (
                    employee_name,
                    emp_id,
                    position,
                    department,
                    salary,
                    gender,
                    employment_status,
                    performance_score,
                    file_cv,
                    foto_profil
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            $pesan = "Query gagal disiapkan: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "ssssdsssss",
                $employeeName,
                $empId,
                $position,
                $department,
                $salary,
                $gender,
                $employmentStatus,
                $performanceScore,
                $fileCv,
                $fotoProfil
            );

            if (mysqli_stmt_execute($stmt)) {
                catatAktivitas($conn, "Menambahkan karyawan " . $employeeName . " (" . $empId . ").");
                try {
                    sinkronkanSemuaDataset($conn);
                } catch (Throwable $error) {
                    error_log("Sinkronisasi CSV gagal: " . $error->getMessage());
                }

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
$halamanAktif = "karyawan";

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
                            autofocus
                        >
                        <p class="field-note">Gunakan ID unik untuk setiap karyawan.</p>
                    </div>
                    <div class="form-group">
                        <label for="foto_profil">Foto Profil</label>
                        <input type="file" id="foto_profil" name="foto_profil" accept=".jpg,.jpeg,image/jpeg">
                        <p class="field-note">JPG/JPEG, maksimal 2 MB.</p>
                    </div>
                    <div class="form-group">
                        <label for="file_cv">CV</label>
                        <input type="file" id="file_cv" name="file_cv" accept=".pdf,application/pdf">
                        <p class="field-note">PDF, maksimal 5 MB.</p>
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
                            required
                        >
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
                            required
                        >
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
                            required
                        >
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
                            required
                        >
                        <p class="field-note">Masukkan angka tanpa pemisah ribuan.</p>
                    </div>

                    <div class="form-group">
                        <label for="gender">
                            Jenis Kelamin <span class="required">*</span>
                        </label>
                        <select id="gender" name="gender" required>
                            <option value="">Pilih jenis kelamin</option>
                            <option
                                value="M"
                                <?= $form["gender"] === "M" ? "selected" : ""; ?>
                            >
                                Laki-laki
                            </option>
                            <option
                                value="F"
                                <?= $form["gender"] === "F" ? "selected" : ""; ?>
                            >
                                Perempuan
                            </option>
                        </select>
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
                            required
                        >
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
                            required
                        >
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
