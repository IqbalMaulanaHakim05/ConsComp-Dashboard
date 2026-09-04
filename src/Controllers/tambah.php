<?php

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Employee/media-karyawan.php';
require_once __DIR__ . '/../Services/Settings/sinkronisasi.php';
require_once __DIR__ . '/../Services/Settings/master-data.php';
require_once __DIR__ . '/../Services/Employee/nik-karyawan.php';
require_once __DIR__ . '/../Services/Employee/performa-karyawan.php';
require_once __DIR__ . '/../Services/Employee/jadwal-cuti.php';

wajibRole("admin", "superadmin");
siapkanMasterData($conn);
siapkanJadwalDanCutiKaryawan($conn);
$masterDepartemen = ambilMasterData($conn, "department"); $masterPosisi = ambilMasterData($conn, "position"); $masterStatus = pilihanStatusKerja(); $masterTipeKerja = pilihanTipeKerja($conn); $masterAgama = ambilMasterData($conn, "agama");
$masterShift = ambilMasterShift($conn);
$namaShiftDiizinkan = array_column($masterShift, 'nama');
$posisiPerDepartemen = ambilPosisiPerNamaDepartemen($conn);

if (($_GET['aksi'] ?? '') === 'detail_master_shift') {
    header('Content-Type: application/json; charset=utf-8');
    $shift = ambilMasterShiftBerdasarkanNama($conn, trim((string) ($_GET['nama'] ?? '')));
    echo json_encode($shift, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$pesan = "";
$riwayatPendidikanForm = [];
$riwayatPekerjaanForm = [];
$shiftHariForm = [];

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
    "tipe_kerja" => (string) ($masterTipeKerja[0] ?? ""),
    "performance_score" => "",
    "shift_nama" => (string) ($masterShift[0]['nama'] ?? 'Non Shift'),
    "shift_mulai" => "",
    "shift_selesai" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach ($form as $namaKolom => $nilaiAwal) {
        $form[$namaKolom] = trim($_POST[$namaKolom] ?? "");
    }

    $riwayatPendidikanForm = array_values(array_filter((array) ($_POST["pendidikan"] ?? []), static fn($item): bool => is_array($item) && trim((string) ($item["institusi"] ?? "")) !== ""));
    $riwayatPekerjaanForm = array_values(array_filter((array) ($_POST["pekerjaan"] ?? []), static fn($item): bool => is_array($item) && trim((string) ($item["nama_perusahaan"] ?? "")) !== ""));
    $shiftHariForm = array_values(array_intersect(
        daftarHariKerja(),
        array_map('strval', (array) ($_POST['shift_hari'] ?? []))
    ));

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
    $tipeKerja = $form["tipe_kerja"];
    $shiftNama = in_array($form['shift_nama'], array_merge(['Non Shift'], $namaShiftDiizinkan), true) ? $form['shift_nama'] : '';
    $shiftMulai = '';
    $shiftSelesai = '';
    $shiftHari = '';
    if ($shiftNama === 'Non Shift') {
        $shiftMulai = $form['shift_mulai'];
        $shiftSelesai = $form['shift_selesai'];
        $shiftHari = implode(', ', $shiftHariForm);
    } elseif ($shiftNama !== '') {
        $masterShiftDipilih = ambilMasterShiftBerdasarkanNama($conn, $shiftNama);
        if ($masterShiftDipilih) {
            $shiftMulai = (string) $masterShiftDipilih['jam_mulai'];
            $shiftSelesai = (string) $masterShiftDipilih['jam_selesai'];
            $shiftHari = (string) $masterShiftDipilih['hari'];
        }
    }
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
        || $empId === ""
        || $position === ""
        || $department === ""
        || $form["salary"] === ""
        || $gender === ""
        || $dateOfHire === ""
        || $employmentStatus === ""
        || $tipeKerja === ""
        || $shiftNama === ''
    ) {
        $pesan = "Semua kolom wajib diisi.";
    } elseif (
        $shiftNama === 'Non Shift'
        && (
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $shiftMulai)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $shiftSelesai)
            || $shiftHariForm === []
        )
    ) {
        $pesan = "Jam Masuk, Jam Pulang, dan minimal satu Hari Kerja wajib diisi untuk Non Shift.";
    } elseif (!in_array($employmentStatus, pilihanStatusKerja(), true) || !in_array($tipeKerja, $masterTipeKerja, true)) {
        $pesan = "Status Kerja atau Tipe Kerja tidak valid.";
    } elseif ($pesanPerforma !== "") {
        $pesan = $pesanPerforma;
    } elseif (nikKaryawanSudahDigunakan($conn, $nik)) {
        $pesan = "NIK sudah digunakan oleh karyawan lain. Gunakan NIK yang berbeda.";
    } elseif (!posisiValidUntukDepartemen($conn, $department, $position)) {
        $pesan = "Posisi yang dipilih tidak terdaftar pada departemen tersebut.";
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
                        tipe_kerja,
                        performance_score,
                        file_cv,
                        foto_profil,
                        file_ijazah,
                        file_mcu,
                        shift_nama,
                        shift_mulai,
                        shift_selesai,
                        shift_hari
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            if (!$stmt) {
                mysqli_rollback($conn);
                $pesan = "Query gagal disiapkan: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    str_repeat("s", 18) . "d" . str_repeat("s", 13),
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
                    $tipeKerja,
                    $performanceScore,
                    $fileCv,
                    $fotoProfil,
                    $fileIjazah,
                    $fileMcu,
                    $shiftNama,
                    $shiftMulai,
                    $shiftSelesai,
                    $shiftHari
                );

                $berhasilSimpan = false;
                try {
                    $berhasilSimpan = mysqli_stmt_execute($stmt);
                } catch (mysqli_sql_exception $exception) {
                    if (pelanggaranIndeksUnikNik((int) $exception->getCode(), $exception->getMessage())) {
                        $pesan = "NIK sudah digunakan oleh karyawan lain. Gunakan NIK yang berbeda.";
                    } else {
                        $pesan = "Data gagal ditambahkan.";
                        error_log("Tambah karyawan gagal: " . $exception->getMessage());
                    }
                }

                if ($berhasilSimpan) {
                    // Simpan ID segera setelah INSERT. Query UPDATE berikutnya dapat
                    // membuat mysqli_insert_id() tidak lagi mengembalikan ID karyawan.
                    $karyawanBaruId = (int) mysqli_insert_id($conn);
                    mysqli_query($conn, "UPDATE karyawan k INNER JOIN master_departemen d ON d.nama = k.department SET k.department_id = d.id WHERE k.id = " . $karyawanBaruId);
                    try {
                        if ($karyawanBaruId <= 0) {
                            throw new RuntimeException("ID karyawan baru tidak valid.");
                        }
                        if ($riwayatPendidikanForm !== []) {
                            $stmtPendidikan = mysqli_prepare($conn, "INSERT INTO riwayat_pendidikan (karyawan_id, institusi, jenjang, jurusan, tanggal_mulai, tanggal_selesai, keterangan) VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))");
                            if (!$stmtPendidikan) throw new RuntimeException(mysqli_error($conn));
                            foreach ($riwayatPendidikanForm as $item) {
                                $institusi = trim((string) ($item["institusi"] ?? "")); $jenjang = trim((string) ($item["jenjang"] ?? "")); $jurusan = trim((string) ($item["jurusan"] ?? ""));
                                $tanggalMulai = trim((string) ($item["tanggal_mulai"] ?? "")); $tanggalSelesai = trim((string) ($item["tanggal_selesai"] ?? "")); $keterangan = trim((string) ($item["keterangan"] ?? ""));
                                mysqli_stmt_bind_param($stmtPendidikan, "issssss", $karyawanBaruId, $institusi, $jenjang, $jurusan, $tanggalMulai, $tanggalSelesai, $keterangan);
                                if (!mysqli_stmt_execute($stmtPendidikan)) throw new RuntimeException(mysqli_stmt_error($stmtPendidikan));
                            }
                            mysqli_stmt_close($stmtPendidikan);
                        }
                        if ($riwayatPekerjaanForm !== []) {
                            $stmtPekerjaan = mysqli_prepare($conn, "INSERT INTO riwayat_pekerjaan (karyawan_id, nama_perusahaan, posisi, departemen, tanggal_mulai, tanggal_selesai, deskripsi) VALUES (?, ?, NULLIF(?, ''), NULL, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))");
                            if (!$stmtPekerjaan) throw new RuntimeException(mysqli_error($conn));
                            foreach ($riwayatPekerjaanForm as $item) {
                                $namaPerusahaan = trim((string) ($item["nama_perusahaan"] ?? "")); $posisiRiwayat = trim((string) ($item["posisi"] ?? ""));
                                $tanggalMulai = trim((string) ($item["tanggal_mulai"] ?? "")); $tanggalSelesai = trim((string) ($item["tanggal_selesai"] ?? "")); $deskripsiRiwayat = trim((string) ($item["deskripsi"] ?? ""));
                                mysqli_stmt_bind_param($stmtPekerjaan, "isssss", $karyawanBaruId, $namaPerusahaan, $posisiRiwayat, $tanggalMulai, $tanggalSelesai, $deskripsiRiwayat);
                                if (!mysqli_stmt_execute($stmtPekerjaan)) throw new RuntimeException(mysqli_stmt_error($stmtPekerjaan));
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
                        header('Location: karyawan.php?pesan=tambah-berhasil');
                        exit;
                    }
                }

                if (!$berhasilSimpan) {
                    mysqli_rollback($conn);
                }

                if ($pesan === "" && mysqli_stmt_errno($stmt) === 1062) {
                    $pesan = pelanggaranIndeksUnikNik(mysqli_stmt_errno($stmt), mysqli_stmt_error($stmt))
                        ? "NIK sudah digunakan oleh karyawan lain. Gunakan NIK yang berbeda."
                        : "ID karyawan sudah digunakan. Gunakan ID yang berbeda.";
                } elseif ($pesan === "") {
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

require __DIR__ . '/../../resources/views/layouts/atas.php';

?>
<section class="form-card">
    <div class="form-card-header">
        <h2>Form Data Karyawan</h2>
        <p>
            Kolom bertanda bintang wajib diisi.
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
                        placeholder="ID dibuat otomatis mengikuti pola data karyawan"
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
                        required><option value="">Pilih departemen terlebih dahulu</option></select>
                </div>

                <div class="form-group">
                    <label for="employment_status">
                        Status Kerja <span class="required">*</span>
                    </label>
                    <select id="employment_status" name="employment_status" required><option value="">Pilih status kerja</option><?php foreach ($masterStatus as $item): ?><option value="<?= htmlspecialchars($item); ?>" <?= $form["employment_status"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
                </div>

                <div class="form-group">
                    <label for="tipe_kerja">Tipe Kerja <span class="required">*</span></label>
                    <select id="tipe_kerja" name="tipe_kerja" required><?php foreach ($masterTipeKerja as $item): ?><option value="<?= htmlspecialchars($item); ?>" <?= $form["tipe_kerja"] === $item ? "selected" : ""; ?>><?= htmlspecialchars($item); ?></option><?php endforeach; ?></select>
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
                    <label for="shift_nama">Jadwal Kerja <span class="required">*</span></label>
                    <select id="shift_nama" name="shift_nama" required><?php foreach ($masterShift as $shift): ?><option value="<?= htmlspecialchars($shift['nama']); ?>" <?= $form['shift_nama'] === $shift['nama'] ? 'selected' : ''; ?>><?= htmlspecialchars($shift['nama']); ?></option><?php endforeach; ?><option value="Non Shift" <?= $form['shift_nama'] === 'Non Shift' ? 'selected' : ''; ?>>Non Shift</option></select>
                    <div id="non-shift-schedule-fields" class="tambah-non-shift-fields">
                        <div class="non-shift-time-fields">
                            <label for="shift_mulai">Jam Masuk <span class="required">*</span><input id="shift_mulai" name="shift_mulai" type="time" value="<?= htmlspecialchars($form['shift_mulai']); ?>"<?= $form['shift_nama'] === 'Non Shift' ? ' required' : ''; ?>></label>
                            <label for="shift_selesai">Jam Pulang <span class="required">*</span><input id="shift_selesai" name="shift_selesai" type="time" value="<?= htmlspecialchars($form['shift_selesai']); ?>"<?= $form['shift_nama'] === 'Non Shift' ? ' required' : ''; ?>></label>
                        </div>
                        <fieldset class="shift-day-checklist"><legend>Hari Kerja <span class="required">*</span></legend><?php foreach (daftarHariKerja() as $hari): ?><label><input type="checkbox" name="shift_hari[]" value="<?= $hari; ?>"<?= in_array($hari, $shiftHariForm, true) ? ' checked' : ''; ?>> <?= $hari; ?></label><?php endforeach; ?></fieldset>
                        <p class="field-note" id="shift-schedule-note">Jam kerja dan minimal satu hari kerja wajib diisi untuk Non Shift.</p>
                    </div>
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



                <div class="form-group"><label for="nik">NIK <span class="required">*</span></label><input type="text" id="nik" name="nik" value="<?= htmlspecialchars($form["nik"]); ?>" maxlength="50" required><p class="field-note">Wajib diisi dan tidak boleh sama dengan NIK karyawan lain.</p></div>
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
                        inputmode="numeric">
                    <p class="field-note">Nilai 0 atau kosong disimpan sebagai belum dinilai.</p>
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
    const positionsByDepartment = <?= json_encode($posisiPerDepartemen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const department = document.getElementById('department');
    const position = document.getElementById('position');
    const selectedPosition = <?= json_encode($form["position"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const updatePositions = function (restore) {
        const current = restore ? selectedPosition : '';
        position.replaceChildren();
        const available = positionsByDepartment[department.value] || [];
        position.append(new Option(department.value ? (available.length ? 'Pilih posisi' : 'Belum ada posisi pada departemen ini') : 'Pilih departemen terlebih dahulu', ''));
        available.forEach(item => position.append(new Option(item, item)));
        position.disabled = available.length === 0;
        if (available.includes(current)) position.value = current;
    };
    department.addEventListener('change', () => updatePositions(false));
    updatePositions(true);

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
        makeCard('Profil Karyawan', ['employee_name','emp_id','department','position','employment_status','tipe_kerja','salary','performance_score','date_of_hire','shift_nama'], 'tambah-main-card'),
        makeCard('Biodata Karyawan', ['nik','alamat','tanggal_lahir','agama','gender','marital_status','kontak','email'], 'tambah-personal-card'),
        makeCard('Informasi Karyawan', ['biografi','keahlian'], 'tambah-history-card'),
        makeCard('Berkas Pendukung', ['foto_profil','file_cv','file_ijazah','file_mcu','tanggal_mcu_terakhir'], 'tambah-documents-card')
    ];
    const biodataCard = cards[1];
    const historyCard = cards[2];
    const profileCard = cards[0];
    const shiftSection = document.createElement('section');
    shiftSection.className = 'history-section shift-section';
    shiftSection.innerHTML = '<div class="history-heading"><h4>Jadwal Kerja</h4></div><div class="shift-fields"></div>';
    const shiftFields = shiftSection.querySelector('.shift-fields');
    ['shift_nama'].forEach(name => {
        if (groups[name]) shiftFields.appendChild(groups[name]);
    });
    profileCard.appendChild(shiftSection);
    const shiftSelect = shiftSection.querySelector('#shift_nama');
    const nonShiftFields = shiftSection.querySelector('#non-shift-schedule-fields');
    const shiftMulai = shiftSection.querySelector('#shift_mulai');
    const shiftSelesai = shiftSection.querySelector('#shift_selesai');
    const shiftDays = [...shiftSection.querySelectorAll('input[name="shift_hari[]"]')];
    const shiftNote = shiftSection.querySelector('#shift-schedule-note');
    const shiftPreset = Object.fromEntries(<?= json_encode(array_map(static fn (array $shift): array => [$shift['nama'], [$shift['jam_mulai'], $shift['jam_selesai'], $shift['hari']]], $masterShift), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
    const hariDariNilai = nilai => nilai === 'Senin-Jumat' ? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] : String(nilai || '').split(/,\s*/).filter(Boolean);
    const aturHari = nilai => {
        const terpilih = hariDariNilai(nilai);
        shiftDays.forEach(item => { item.checked = terpilih.includes(item.value); });
    };
    const validateShiftDays = () => {
        const wajib = shiftSelect.value === 'Non Shift';
        const adaHari = shiftDays.some(item => item.checked);
        shiftDays[0].setCustomValidity(wajib && !adaHari ? 'Pilih minimal satu hari kerja.' : '');
    };
    const toggleNonShiftFields = () => {
        const nonShift = shiftSelect.value === 'Non Shift';
        const detailMaster = shiftPreset[shiftSelect.value];
        if (detailMaster) {
            shiftMulai.value = detailMaster[0];
            shiftSelesai.value = detailMaster[1];
            aturHari(detailMaster[2]);
        }
        shiftMulai.required = nonShift;
        shiftSelesai.required = nonShift;
        shiftMulai.readOnly = !nonShift;
        shiftSelesai.readOnly = !nonShift;
        shiftDays.forEach(item => { item.disabled = !nonShift; });
        nonShiftFields.classList.toggle('is-master-shift', !nonShift);
        shiftNote.textContent = nonShift
            ? 'Jam kerja dan minimal satu hari kerja wajib diisi untuk Non Shift.'
            : 'Jadwal ini mengikuti master shift dan tidak dapat diubah dari halaman ini.';
        validateShiftDays();
    };
    shiftSelect.addEventListener('change', toggleNonShiftFields);
    shiftDays.forEach(item => item.addEventListener('change', validateShiftDays));
    toggleNonShiftFields();
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
require __DIR__ . '/../../resources/views/layouts/bawah.php';
