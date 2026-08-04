<?php
require  "koneksi.php";

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employeeName = trim($_POST["employee_name"]);
    $empId = trim($_POST["emp_id"]);
    $position = trim($_POST["position"]);
    $department = trim($_POST["department"]);
    $salary = (float) $_POST["salary"];
    $gender = trim($_POST["gender"]);
    $employmentStatus = trim($_POST["employment_status"]);
    $performanceScore = trim($_POST["performance_score"]);

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
        header("Location: index.php");
        exit;
    }

    $pesan = "Data gagal ditambahkan: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Karyawan</title>
</head>
<body>

<h2>Tambah Data Karyawan</h2>

<?php if ($pesan !== ""): ?>
    <p><?= htmlspecialchars($pesan); ?></p>
<?php endif; ?>

<form method="POST">
    <label>ID Karyawan</label><br>
    <input type="text" name="emp_id" required><br><br>

    <label>Nama Karyawan</label><br>
    <input type="text" name="employee_name" required><br><br>

    <label>Posisi</label><br>
    <input type="text" name="position" required><br><br>

    <label>Departemen</label><br>
    <input type="text" name="department" required><br><br>

    <label>Gaji</label><br>
    <input type="number" name="salary" step="0.01" required><br><br>

    <label>Jenis Kelamin</label><br>
    <select name="gender" required>
        <option value="">Pilih</option>
        <option value="M">Laki-laki</option>
        <option value="F">Perempuan</option>
    </select><br><br>

    <label>Status Kerja</label><br>
    <input type="text" name="employment_status" required><br><br>

    <label>Skor Performa</label><br>
    <input type="text" name="performance_score" required><br><br>

    <button type="submit">Simpan</button>
    <a href="index.php">Kembali</a>
</form>

</body>
</html>