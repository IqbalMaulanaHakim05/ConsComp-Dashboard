<?php

require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";
require_once __DIR__ . "/media-karyawan.php";
require_once __DIR__ . "/sinkronisasi.php";
require_once __DIR__ . "/master-data.php";
require_once __DIR__ . "/tanggal-keluar-karyawan.php";
require_once __DIR__ . "/nik-karyawan.php";
require_once __DIR__ . "/performa-karyawan.php";

wajibRole("admin", "superadmin");
siapkanMasterData($conn);
siapkanTanggalKeluarKaryawan($conn);
$masterDepartemen = ambilMasterData($conn, "department"); $masterPosisi = ambilMasterData($conn, "position"); $masterStatus = ambilMasterData($conn, "employment_status"); $masterAgama = ambilMasterData($conn, "agama");
$posisiPerDepartemen = ambilPosisiPerNamaDepartemen($conn);

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    die("ID karyawan tidak valid.");
}

$pesan = "";

$stmtData = mysqli_prepare($conn, "SELECT * FROM karyawan WHERE id = ?");

if (!$stmtData) {
    die("Query data gagal disiapkan: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmtData, "i", $id);
mysqli_stmt_execute($stmtData);
$hasilData = mysqli_stmt_get_result($stmtData);
$data = mysqli_fetch_assoc($hasilData);
mysqli_stmt_close($stmtData);

if (!$data) {
    die("Data karyawan tidak ditemukan.");
}

$form = [
    "employee_name" => (string) ($data["employee_name"] ?? ""),
    "nik"=>(string)($data["nik"]??""), "alamat"=>(string)($data["alamat"]??""), "biografi"=>(string)($data["biografi"]??""), "keahlian"=>(string)($data["keahlian"]??""), "riwayat_pekerjaan"=>(string)($data["riwayat_pekerjaan"]??""), "tanggal_riwayat_pekerjaan"=>(string)($data["tanggal_riwayat_pekerjaan"]??""), "riwayat_pendidikan"=>(string)($data["riwayat_pendidikan"]??""), "tanggal_riwayat_pendidikan"=>(string)($data["tanggal_riwayat_pendidikan"]??""), "tanggal_lahir"=>(string)($data["tanggal_lahir"]??""), "tanggal_mcu_terakhir"=>(string)($data["tanggal_mcu_terakhir"]??""), "agama"=>(string)($data["agama"]??""), "marital_status"=>(string)($data["marital_status"]??""), "kontak"=>(string)($data["kontak"]??""), "email"=>(string)($data["email"]??""),
    "emp_id" => (string) ($data["emp_id"] ?? ""),
    "position" => (string) ($data["position"] ?? ""),
    "department" => (string) ($data["department"] ?? ""),
    "salary" => (string) ($data["salary"] ?? ""),
    "gender" => trim((string) ($data["gender"] ?? "")),
    "employment_status" => (string) ($data["employment_status"] ?? ""),
    "performance_score" => (string) ($data["performance_score"] ?? ""),
    "date_of_exit" => (string) ($data["date_of_exit"] ?? ""),
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($form as $namaKolom => $nilaiAwal) {
        $form[$namaKolom] = trim($_POST[$namaKolom] ?? "");
    }

    $employeeName = $form["employee_name"];
    $nik=$form["nik"]; $alamat=$form["alamat"]; $biografi=$form["biografi"]; $keahlian=$form["keahlian"]; $riwayatPekerjaan=$form["riwayat_pekerjaan"]; $tanggalRiwayatPekerjaan=$form["tanggal_riwayat_pekerjaan"] !== "" ? $form["tanggal_riwayat_pekerjaan"] : null; $riwayatPendidikan=$form["riwayat_pendidikan"]; $tanggalRiwayatPendidikan=$form["tanggal_riwayat_pendidikan"] !== "" ? $form["tanggal_riwayat_pendidikan"] : null; $tanggalLahir=$form["tanggal_lahir"] !== "" ? $form["tanggal_lahir"] : null; $tanggalMcuTerakhir=$form["tanggal_mcu_terakhir"] !== "" ? $form["tanggal_mcu_terakhir"] : null; $agama=$form["agama"]; $maritalStatus=$form["marital_status"]; $kontak=$form["kontak"]; $email=$form["email"];
    // ID karyawan berasal dari record yang ditemukan berdasarkan URL dan tidak boleh diubah dari request.
    $empId = (string) ($data["emp_id"] ?? "");
    $position = $form["position"];
    $department = $form["department"];
    $salary = (float) $form["salary"];
    $gender = $form["gender"];
    $employmentStatus = $form["employment_status"];
    $dateOfExit = $form["date_of_exit"] !== "" ? $form["date_of_exit"] : null;
    $pesanPerforma = "";
    try {
        $performanceScore = normalisasiSkorPerforma($form["performance_score"]);
    } catch (InvalidArgumentException $exception) {
        $performanceScore = null;
        $pesanPerforma = $exception->getMessage();
    }

    if (
        $employeeName === ""
        || $nik === ""
        || $position === ""
        || $department === ""
        || $form["salary"] === ""
        || $gender === ""
        || $employmentStatus === ""
    ) {
        $pesan = "Semua kolom wajib diisi.";
    } elseif ($pesanPerforma !== "") {
        $pesan = $pesanPerforma;
    } elseif (nikKaryawanSudahDigunakan($conn, $nik, $id)) {
        $pesan = "NIK sudah digunakan oleh karyawan lain. Gunakan NIK yang berbeda.";
    } elseif (!posisiValidUntukDepartemen($conn, $department, $position)) {
        $pesan = "Posisi yang dipilih tidak terdaftar pada departemen tersebut.";
    } elseif ($salary < 0) {
        $pesan = "Gaji tidak boleh bernilai negatif.";
    } elseif ($dateOfExit !== null && (!preg_match("/^\\d{4}-\\d{2}-\\d{2}$/", $dateOfExit) || !checkdate((int) substr($dateOfExit, 5, 2), (int) substr($dateOfExit, 8, 2), (int) substr($dateOfExit, 0, 4)))) {
        $pesan = "Tanggal keluar tidak valid.";
    } else {
        $fileCv = $data["file_cv"] ?? null;
        $fotoProfil = $data["foto_profil"] ?? null;
        $fileIjazah = $data["file_ijazah"] ?? null;
        $fileMcu = $data["file_mcu"] ?? null;
        $unggahCv = unggahMediaKaryawan($_FILES["file_cv"] ?? [], "cv", $pesan);
        $unggahFoto = unggahMediaKaryawan($_FILES["foto_profil"] ?? [], "foto", $pesan);
        $unggahIjazah = unggahMediaKaryawan($_FILES["file_ijazah"] ?? [], "ijazah", $pesan);
        $unggahMcu = unggahMediaKaryawan($_FILES["file_mcu"] ?? [], "mcu", $pesan);

        if ($unggahCv !== null) {
            $fileCv = $unggahCv;
        }
        if ($unggahFoto !== null) {
            $fotoProfil = $unggahFoto;
        }
        if ($unggahIjazah !== null) $fileIjazah = $unggahIjazah;
        if ($unggahMcu !== null) $fileMcu = $unggahMcu;

        if ($pesan === "") {
            $sql = "UPDATE karyawan SET
                        employee_name = ?,
                        nik = ?, alamat = ?, biografi = ?, keahlian = ?, riwayat_pekerjaan = ?, tanggal_riwayat_pekerjaan = ?, riwayat_pendidikan = ?, tanggal_riwayat_pendidikan = ?, tanggal_lahir = ?, tanggal_mcu_terakhir = ?, agama = ?, marital_status = ?, kontak = ?, email = ?,
                        position = ?,
                        department = ?,
                        salary = ?,
                        gender = ?,
                        employment_status = ?,
                        date_of_exit = ?,
                        performance_score = ?,
                        file_cv = ?,
                        foto_profil = ?,
                        file_ijazah = ?,
                        file_mcu = ?
                    WHERE id = ?";

            $stmtUpdate = mysqli_prepare($conn, $sql);

            if (!$stmtUpdate) {
                $pesan = "Query gagal disiapkan: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param(
                    $stmtUpdate,
                    str_repeat("s", 17) . "d" . str_repeat("s", 8) . "i",
                    $employeeName, $nik, $alamat, $biografi, $keahlian, $riwayatPekerjaan, $tanggalRiwayatPekerjaan, $riwayatPendidikan, $tanggalRiwayatPendidikan, $tanggalLahir, $tanggalMcuTerakhir, $agama, $maritalStatus, $kontak, $email,
                    $position,
                    $department,
                    $salary,
                    $gender,
                    $employmentStatus,
                    $dateOfExit,
                    $performanceScore,
                    $fileCv,
                    $fotoProfil, $fileIjazah, $fileMcu,
                    $id
                );

                $berhasilUpdate = false;
                try {
                    $berhasilUpdate = mysqli_stmt_execute($stmtUpdate);
                } catch (mysqli_sql_exception $exception) {
                    if (pelanggaranIndeksUnikNik((int) $exception->getCode(), $exception->getMessage())) {
                        $pesan = "NIK sudah digunakan oleh karyawan lain. Gunakan NIK yang berbeda.";
                    } else {
                        $pesan = "Data gagal diperbarui.";
                        error_log("Edit karyawan gagal: " . $exception->getMessage());
                    }
                }

                if ($berhasilUpdate) {
                    mysqli_query($conn, "UPDATE karyawan k INNER JOIN master_departemen d ON d.nama = k.department SET k.department_id = d.id WHERE k.id = " . (int) $id);
                    mysqli_query($conn, "DELETE FROM riwayat_pendidikan WHERE karyawan_id = " . $id);
                    $insertPendidikan = mysqli_prepare($conn, "INSERT INTO riwayat_pendidikan (karyawan_id, institusi, jenjang, jurusan, tanggal_mulai, tanggal_selesai, keterangan) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)");
                    foreach ((array) ($_POST["pendidikan"] ?? []) as $itemPendidikan) {
                        $institusi = trim((string) ($itemPendidikan["institusi"] ?? "")); if ($institusi === "") continue;
                        $jenjang = trim((string) ($itemPendidikan["jenjang"] ?? "")); $jurusan = trim((string) ($itemPendidikan["jurusan"] ?? "")); $mulai = trim((string) ($itemPendidikan["tanggal_mulai"] ?? "")); $selesai = trim((string) ($itemPendidikan["tanggal_selesai"] ?? "")); $keterangan = trim((string) ($itemPendidikan["keterangan"] ?? ""));
                        mysqli_stmt_bind_param($insertPendidikan, "issssss", $id, $institusi, $jenjang, $jurusan, $mulai, $selesai, $keterangan); mysqli_stmt_execute($insertPendidikan);
                    }
                    mysqli_stmt_close($insertPendidikan);
                    mysqli_query($conn, "DELETE FROM riwayat_pekerjaan WHERE karyawan_id = " . $id);
                    $insertPekerjaan = mysqli_prepare($conn, "INSERT INTO riwayat_pekerjaan (karyawan_id, nama_perusahaan, posisi, departemen, tanggal_mulai, tanggal_selesai, deskripsi) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)");
                    foreach ((array) ($_POST["pekerjaan"] ?? []) as $itemPekerjaan) {
                        $perusahaan = trim((string) ($itemPekerjaan["nama_perusahaan"] ?? "")); if ($perusahaan === "") continue;
                        $posisiRiwayat = trim((string) ($itemPekerjaan["posisi"] ?? "")); $departemenRiwayat = trim((string) ($itemPekerjaan["departemen"] ?? "")); $mulaiRiwayat = trim((string) ($itemPekerjaan["tanggal_mulai"] ?? "")); $selesaiRiwayat = trim((string) ($itemPekerjaan["tanggal_selesai"] ?? "")); $deskripsiRiwayat = trim((string) ($itemPekerjaan["deskripsi"] ?? ""));
                        mysqli_stmt_bind_param($insertPekerjaan, "issssss", $id, $perusahaan, $posisiRiwayat, $departemenRiwayat, $mulaiRiwayat, $selesaiRiwayat, $deskripsiRiwayat); mysqli_stmt_execute($insertPekerjaan);
                    }
                    mysqli_stmt_close($insertPekerjaan);
                    try {
                        sinkronkanSemuaDataset($conn);
                    } catch (Throwable $error) {
                        error_log("Sinkronisasi CSV gagal: " . $error->getMessage());
                    }

                    catatAktivitas($conn, "Mengedit data karyawan " . $employeeName . " (" . $empId . ").");
                    mysqli_stmt_close($stmtUpdate);
                    if (($_POST["return_to_profile"] ?? "") === "1") {
                        header("Location: ../profil-karyawan.php?id=" . $id . "&pesan=edit-berhasil");
                    } else {
                        header("Location: ../karyawan.php?pesan=edit-berhasil");
                    }
                    exit;
                }

                if ($pesan === "" && mysqli_stmt_errno($stmtUpdate) === 1062) {
                    $pesan = pelanggaranIndeksUnikNik(mysqli_stmt_errno($stmtUpdate), mysqli_stmt_error($stmtUpdate))
                        ? "NIK sudah digunakan oleh karyawan lain. Gunakan NIK yang berbeda."
                        : "ID karyawan sudah digunakan. Gunakan ID yang berbeda.";
                } elseif ($pesan === "") {
                    $pesan = "Data gagal diperbarui: " . mysqli_stmt_error($stmtUpdate);
                }

                mysqli_stmt_close($stmtUpdate);
            }
        }
    }
}

$judulHalaman = "Edit Karyawan";
$subjudulHalaman = "Perbarui informasi karyawan yang tersimpan dalam database.";
$halamanAktif = "karyawan";

require __DIR__ . "/../partials/atas.php";

?>
    <section class="form-card">
        <div class="form-card-header">
            <h2>Form Edit Data Karyawan</h2>
            <p>
                Periksa kembali data yang akan diperbarui. Setelah disimpan,
                perubahan SQL akan disinkronkan ke dataset lokal.
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
                            value="<?= htmlspecialchars($form["emp_id"]); ?>"
                            readonly
                            autofocus
                        >
                        <p class="field-note">ID harus tetap unik dan tidak boleh sama dengan karyawan lain.</p>
                    </div>

                    <div class="form-group">
                        <label for="foto_profil">Ganti Foto Profil</label>
                        <input type="file" id="foto_profil" name="foto_profil" accept=".jpg,.jpeg,image/jpeg">
                        <p class="field-note">Kosongkan jika tidak ingin mengganti foto. JPG/JPEG, maksimal 2 MB.</p>
                    </div>

                    <div class="form-group">
                        <label for="file_cv">Ganti CV</label>
                        <input type="file" id="file_cv" name="file_cv" accept=".pdf,application/pdf">
                        <p class="field-note">Kosongkan jika tidak ingin mengganti CV. PDF, maksimal 5 MB.</p>
                    </div>
                    <div class="form-group"><label for="file_ijazah">Ganti Ijazah</label><input type="file" id="file_ijazah" name="file_ijazah" accept=".pdf,application/pdf"><p class="field-note">Kosongkan jika tidak ingin mengganti. PDF, maksimal 5 MB.</p></div>
                    <div class="form-group"><label for="file_mcu">Ganti MCU</label><input type="file" id="file_mcu" name="file_mcu" accept=".pdf,application/pdf"><p class="field-note">Kosongkan jika tidak ingin mengganti. PDF, maksimal 5 MB.</p></div>

                    <div class="form-group">
                        <label for="employee_name">
                            Nama Karyawan <span class="required">*</span>
                        </label>
                        <select
                            type="text"
                            id="employee_name"
                            name="employee_name"
                            value="<?= htmlspecialchars($form["employee_name"]); ?>"
                            placeholder="Masukkan nama lengkap"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="form-group"><label for="nik">NIK <span class="required">*</span></label><input type="text" id="nik" name="nik" value="<?= htmlspecialchars($form["nik"]); ?>" maxlength="50" required><p class="field-note">Wajib diisi dan tidak boleh sama dengan NIK karyawan lain.</p></div>
                    <div class="form-group"><label for="tanggal_lahir">Tanggal Lahir</label><input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars($form["tanggal_lahir"]); ?>"></div>
                    <div class="form-group"><label for="tanggal_mcu_terakhir">Tanggal MCU Terakhir</label><input type="date" id="tanggal_mcu_terakhir" name="tanggal_mcu_terakhir" value="<?= htmlspecialchars($form["tanggal_mcu_terakhir"]); ?>"></div>
                    <div class="form-group"><label for="agama">Agama</label><select id="agama" name="agama"><option value="">Pilih agama</option><?php foreach ($masterAgama as $item): ?><option value="<?= htmlspecialchars($item); ?>" <?= $form["agama"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="marital_status">Status Kawin</label><select id="marital_status" name="marital_status"><option value="">Pilih status</option><option <?= $form["marital_status"] === "Single" ? "selected" : ""; ?>>Single</option><option <?= $form["marital_status"] === "Married" ? "selected" : ""; ?>>Married</option></select></div>
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
                        <label for="position">
                            Posisi <span class="required">*</span>
                        </label>
                        <select
                            id="position"
                            name="position"
                            required><option value="">Pilih departemen terlebih dahulu</option></select>
                    </div>

                    <div class="form-group">
                        <label for="department">
                            Departemen <span class="required">*</span>
                        </label>
                        <select
                            type="text"
                            id="department"
                            name="department"
                            required><option value="">Pilih departemen</option><?php foreach ($masterDepartemen as $item): ?><option <?= $form["department"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
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
                            required><option value="">Pilih status kerja</option><?php foreach ($masterStatus as $item): ?><option <?= $form["employment_status"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
                    </div>

                    <div class="form-group">
                        <label for="date_of_exit">Tanggal Keluar</label>
                        <input type="date" id="date_of_exit" name="date_of_exit" value="<?= htmlspecialchars($form["date_of_exit"]); ?>">
                        <p class="field-note">Isi saat karyawan berhenti/keluar. Data ini dipakai pada grafik analisis.</p>
                    </div>

                    <div class="form-group">
                        <label for="performance_score">Skor Performa</label>
                        <input
                            type="number"
                            id="performance_score"
                            name="performance_score"
                            value="<?= htmlspecialchars($form["performance_score"]); ?>"
                            placeholder="Kosongkan atau isi 0 jika belum dinilai"
                            min="0"
                            max="100"
                            step="1"
                            inputmode="numeric"
                        >
                        <p class="field-note">Nilai 0 atau kosong disimpan sebagai belum dinilai.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= htmlspecialchars(URL_DASAR); ?>karyawan.php" class="btn btn-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-success">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const positionsByDepartment = <?= json_encode($posisiPerDepartemen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const department = document.getElementById('department');
    const position = document.getElementById('position');
    const selected = <?= json_encode($form["position"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    if (!department || !position) return;
    const update = function (restore) {
        const current = restore ? selected : '';
        const available = positionsByDepartment[department.value] || [];
        position.replaceChildren(new Option(department.value ? (available.length ? 'Pilih posisi' : 'Belum ada posisi pada departemen ini') : 'Pilih departemen terlebih dahulu', ''));
        available.forEach(item => position.append(new Option(item, item)));
        position.disabled = available.length === 0;
        if (available.includes(current)) position.value = current;
    };
    department.addEventListener('change', () => update(false));
    update(true);
});
</script>
<?php
require __DIR__ . "/../partials/bawah.php";
