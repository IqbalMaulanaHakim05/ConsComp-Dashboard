<?php
require_once 'auth.php';
wajibLoginAdmin();
require "koneksi.php";
require_once "sinkronisasi.php";

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
    $performanceScore = filter_var(
        $form["performance_score"],
        FILTER_VALIDATE_INT
    );

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
    } elseif (
        $performanceScore === false
        || $performanceScore < 1
        || $performanceScore > 100
    ) {
        $pesan = "Skor performa harus berupa angka antara 1 sampai 100.";
    } else {
        $sql = "INSERT INTO karyawan (
                    employee_name,
                    emp_id,
                    position,
                    department,
                    salary,
                    gender,
                    employment_status,
                    performance_score
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            $pesan = "Query gagal disiapkan: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "ssssdsss",
                $employeeName,
                $empId,
                $position,
                $department,
                $salary,
                $gender,
                $employmentStatus,
                $performanceScore
            );

            if (mysqli_stmt_execute($stmt)) {
                try {
                    sinkronkanSemuaDataset($conn);
                } catch (Throwable $error) {
                    error_log("Sinkronisasi CSV gagal: " . $error->getMessage());
                }

                mysqli_stmt_close($stmt);
                header("Location: index.php?pesan=tambah-berhasil");
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan | Admin Karyawan</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            width: 240px;
            height: 100vh;
            padding: 25px 18px;
            overflow-y: auto;
            color: #ffffff;
            background-color: #0f172a;
        }

        .sidebar-brand {
            margin-bottom: 35px;
        }

        .sidebar-brand h2 {
            margin: 0;
            font-size: 22px;
        }

        .sidebar-brand p {
            margin: 7px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }

        .sidebar-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 14px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: #ffffff;
            background-color: #2563eb;
        }

        .sidebar-logout {
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid #334155;
        }

        .sidebar-logout a {
            display: block;
            padding: 12px 14px;
            color: #fecaca;
            background-color: rgba(220, 38, 38, 0.12);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .sidebar-logout a:hover {
            color: #ffffff;
            background-color: #dc2626;
        }

        .main-content {
            min-height: 100vh;
            margin-left: 240px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .topbar-title h1 {
            margin: 0;
            color: #0f172a;
            font-size: 29px;
        }

        .topbar-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 42px;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
        }

        .btn:hover {
            opacity: 0.88;
            transform: translateY(-1px);
        }

        .btn-primary {
            color: #ffffff;
            background-color: #2563eb;
        }

        .btn-success {
            color: #ffffff;
            background-color: #16a34a;
        }

        .btn-secondary {
            color: #334155;
            background-color: #e2e8f0;
        }

        .form-card {
            max-width: 960px;
            margin: 0 auto;
            overflow: hidden;
            background-color: #ffffff;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(15, 23, 42, 0.08);
        }

        .form-card-header {
            padding: 24px 26px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-card-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 20px;
        }

        .form-card-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-body {
            padding: 26px;
        }

        .alert-error {
            margin-bottom: 22px;
            padding: 13px 16px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #991b1b;
            background-color: #fee2e2;
            font-size: 14px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            min-width: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .required {
            color: #dc2626;
        }

        input,
        select {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            color: #0f172a;
            background-color: #ffffff;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus,
        select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        input::placeholder {
            color: #94a3b8;
        }

        .field-note {
            margin: 7px 0 0;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.4;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }

        @media screen and (max-width: 800px) {
            .sidebar {
                position: static;
                display: none;
                width: 100%;
                height: auto;
            }

            .sidebar.show {
                display: flex;
            }

            .sidebar-logout {
                margin-top: 24px;
            }

            .main-content {
                margin-left: 0;
                padding: 18px;
            }

            .topbar-title h1 {
                font-size: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: auto;
            }
        }

        @media screen and (max-width: 520px) {
            .form-card-header,
            .form-body {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2>Admin Karyawan</h2>
        <p>Sistem pengelolaan dataset</p>
    </div>

    <nav class="sidebar-menu">
        <a href="index.php">Dashboard</a>
        <a href="tambah.php" class="active">Tambah Data</a>
        <a href="import_excel.php">Import Excel</a>
        <a href="../index.php">Halaman Publik</a>
    </nav>

    <div class="sidebar-logout">
        <a
            href="logout.php"
            onclick="return confirm('Yakin ingin keluar dari akun admin?');"
        >
            Logout Admin
        </a>
    </div>
</aside>

<main class="main-content">
    <div class="topbar">
        <div class="topbar-title">
            <h1>Tambah Karyawan</h1>
            <p>Masukkan informasi karyawan baru ke dalam database.</p>
        </div>

        <a href="index.php" class="btn btn-secondary">
            ← Kembali ke Dashboard
        </a>
    </div>

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

            <form method="POST" autocomplete="off">
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
                            placeholder="Contoh: Active"
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
                    <a href="index.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-success">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

</body>
</html>