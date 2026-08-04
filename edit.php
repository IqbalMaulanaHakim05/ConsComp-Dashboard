<?php
require  "koneksi.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id <= 0) {
    die("ID tidak valid.");
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM karyawan WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($hasil);

if (!$data) {
    die("Data tidak ditemukan.");
}

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

    $sql = "UPDATE karyawan SET
                employee_name = ?,
                emp_id = ?,
                position = ?,
                department = ?,
                salary = ?,
                gender = ?,
                employment_status = ?,
                performance_score = ?
            WHERE id = ?";

    $stmtUpdate = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmtUpdate,
        "ssssdsssi",
        $employeeName,
        $empId,
        $position,
        $department,
        $salary,
        $gender,
        $employmentStatus,
        $performanceScore,
        $id
    );

    if (mysqli_stmt_execute($stmtUpdate)) {
        header("Location: index.php");
        exit;
    }

    $pesan = "Data gagal diperbarui: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Karyawan</title>
</head>
<body>

<h2>Edit Data Karyawan</h2>

<?php if ($pesan !== ""): ?>
    <p><?= htmlspecialchars($pesan); ?></p>
<?php endif; ?>

<form method="POST">
    <label>ID Karyawan</label><br>
    <input
        type="text"
        name="emp_id"
        value="<?= htmlspecialchars($data["emp_id"]); ?>"
        required
    ><br><br>

    <label>Nama Karyawan</label><br>
    <input
        type="text"
        name="employee_name"
        value="<?= htmlspecialchars($data["employee_name"]); ?>"
        required
    ><br><br>

    <label>Posisi</label><br>
    <input
        type="text"
        name="position"
        value="<?= htmlspecialchars($data["position"]); ?>"
        required
    ><br><br>

    <label>Departemen</label><br>
    <input
        type="text"
        name="department"
        value="<?= htmlspecialchars($data["department"]); ?>"
        required
    ><br><br>

    <label>Gaji</label><br>
    <input
        type="number"
        name="salary"
        step="0.01"
        value="<?= htmlspecialchars($data["salary"]); ?>"
        required
    ><br><br>

    <label>Jenis Kelamin</label><br>
    <select name="gender" required>
        <option
            value="M"
            <?= trim($data["gender"]) === "M" ? "selected" : ""; ?>
        >
            Laki-laki
        </option>

        <option
            value="F"
            <?= trim($data["gender"]) === "F" ? "selected" : ""; ?>
        >
            Perempuan
        </option>
    </select><br><br>

    <label>Status Kerja</label><br>
    <input
        type="text"
        name="employment_status"
        value="<?= htmlspecialchars($data["employment_status"]); ?>"
        required
    ><br><br>

    <label>Skor Performa</label><br>
    <input
        type="text"
        name="performance_score"
        value="<?= htmlspecialchars($data["performance_score"]); ?>"
        required
    ><br><br>

    <button type="submit">Simpan Perubahan</button>
    <a href="index.php">Kembali</a>
</form>

</body>
</html>