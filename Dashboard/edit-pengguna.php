<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("superadmin");

$id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, username, nama, role FROM users WHERE id = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$pengguna = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$pengguna) {
    die("Akun tidak ditemukan.");
}

if ($pengguna["role"] !== "admin") {
    http_response_code(403);
    die("403 - Akun superadmin tidak dapat dikelola dari halaman ini.");
}

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim((string) ($_POST["nama"] ?? ""));
    $role = "admin";
    $password = (string) ($_POST["password"] ?? "");

    if ($nama === "") {
        $pesan = "Nama wajib diisi.";
    } elseif ($password !== "" && strlen($password) < 8) {
        $pesan = "Password baru minimal 8 karakter.";
    } else {
        if ($password !== "") {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = mysqli_prepare(
                $conn,
                "UPDATE users SET nama = ?, role = ?, password = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($update, "sssi", $nama, $role, $hash, $id);
        } else {
            $update = mysqli_prepare(
                $conn,
                "UPDATE users SET nama = ?, role = ? WHERE id = ?"
            );
            mysqli_stmt_bind_param($update, "ssi", $nama, $role, $id);
        }

        if ($update && mysqli_stmt_execute($update)) {
            catatAktivitas(
                $conn,
                "Mengubah pengguna " . $pengguna["username"] . " menjadi role " . $role . "."
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
}

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
                        <label>Role</label>
                        <input value="Admin" readonly aria-label="Role akun">
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
