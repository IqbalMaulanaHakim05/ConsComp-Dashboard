<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("superadmin");

$id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, username, nama, role, department_id FROM users WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$pengguna = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pengguna) {
    die("Akun tidak ditemukan.");
}

if ($pengguna["role"] === "superadmin") {
    http_response_code(403);
    die("403 - Akun superadmin tidak dapat dikelola dari halaman ini.");
}

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim((string) ($_POST["nama"] ?? ""));
    $role = (string) ($_POST["role"] ?? "admin");
    $departmentId = (int) ($_POST["department_id"] ?? 0);
    $password = (string) ($_POST["password"] ?? "");

    if ($nama === "" || !in_array($role, ["admin", "pic", "koordinator", "manager", "viewer"], true)) {
        $pesan = "Nama wajib diisi.";
    } elseif (in_array($role, ["pic", "koordinator", "manager"], true) && $departmentId <= 0) {
        $pesan = "Departemen wajib dipilih untuk role operasional.";
    } elseif ($password !== "" && strlen($password) < 8) {
        $pesan = "Password baru minimal 8 karakter.";
    } else {
        if ($password !== "") {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = mysqli_prepare(
                $conn,
                "UPDATE users SET nama = ?, role = ?, department_id = NULLIF(?, 0), password = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($update, "ssisi", $nama, $role, $departmentId, $hash, $id);
        } else {
            $update = mysqli_prepare(
                $conn,
                "UPDATE users SET nama = ?, role = ?, department_id = NULLIF(?, 0) WHERE id = ?"
            );
            mysqli_stmt_bind_param($update, "ssii", $nama, $role, $departmentId, $id);
        }

        if ($update && mysqli_stmt_execute($update)) {
            catatAktivitas(
                $conn,
                "Mengubah pengguna " . $pengguna["username"]
                    . " menjadi role " . $role
                    . " dan departemen ID " . ($departmentId > 0 ? (string) $departmentId : "semua") . "."
            );
            mysqli_stmt_close($update);
            header("Location: pengguna.php");
            exit;
        }

        if ($update) {
            mysqli_stmt_close($update);
        }
        $pesan = "Perubahan akun gagal disimpan.";
    }

    $pengguna["nama"] = $nama;
    $pengguna["role"] = $role;
    $pengguna["department_id"] = $departmentId;
}

$daftarDepartemen = mysqli_query($conn, "SELECT id, nama FROM master_departemen WHERE is_active = 1 ORDER BY nama ASC");

$judulHalaman = "Edit Akun";
$subjudulHalaman = "Perbarui informasi dan hak akses akun.";
$halamanAktif = "pengguna";

require __DIR__ . "/partials/atas.php";

?>
    <section class="form-card">
        <div class="form-card-header">
            <h2>Edit Akun</h2>
            <p>Username <strong><?= htmlspecialchars($pengguna["username"]); ?></strong> tidak dapat diubah.</p>
        </div>

        <div class="form-body">
            <?php if ($pesan !== ""): ?>
                <div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="id" value="<?= (int) $pengguna["id"]; ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input id="nama" name="nama" value="<?= htmlspecialchars($pengguna["nama"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <?php foreach (["admin" => "Admin", "pic" => "PIC", "koordinator" => "Koordinator", "manager" => "Manager", "viewer" => "Viewer"] as $nilai => $label): ?>
                                <option value="<?= $nilai; ?>" <?= $pengguna["role"] === $nilai ? "selected" : ""; ?>><?= $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department_id">Departemen</label>
                        <select id="department_id" name="department_id">
                            <option value="0">Semua departemen / tidak terikat</option>
                            <?php while ($departemen = mysqli_fetch_assoc($daftarDepartemen)): ?>
                                <option value="<?= (int) $departemen["id"]; ?>" <?= (int) ($pengguna["department_id"] ?? 0) === (int) $departemen["id"] ? "selected" : ""; ?>><?= htmlspecialchars($departemen["nama"]); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="password">Password Baru</label>
                        <input id="password" name="password" type="password" minlength="8">
                        <p class="field-note">Kosongkan jika password tidak ingin diubah. Minimal 8 karakter jika diisi.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="pengguna.php" class="btn btn-secondary">Batal</a>
                    <button class="btn btn-success" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </section>
<?php
require __DIR__ . "/partials/bawah.php";
