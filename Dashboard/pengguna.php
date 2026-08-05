<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("superadmin");

$pesan = "";
$tipePesan = "sukses";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (($_POST["aksi"] ?? "") === "hapus") {
        $hapusId = (int) ($_POST["id"] ?? 0);
        $superadminAktif = (int) ($_SESSION["user"]["id"] ?? 0);

        $jumlahSuperadmin = (int) (mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total FROM users WHERE role = 'superadmin'"
        ))["total"] ?? 0);

        $target = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT role FROM users WHERE id = " . $hapusId . " LIMIT 1"
        ));

        if (!csrfValid($_POST["csrf_token"] ?? null)) {
            $pesan = "Sesi formulir tidak valid.";
            $tipePesan = "error";
        } elseif ($hapusId === $superadminAktif) {
            $pesan = "Akun yang sedang digunakan tidak dapat dihapus.";
            $tipePesan = "error";
        } elseif (($target["role"] ?? "") === "superadmin" && $jumlahSuperadmin <= 1) {
            $pesan = "Superadmin terakhir tidak dapat dihapus.";
            $tipePesan = "error";
        } else {
            $hapus = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($hapus, "i", $hapusId);
            $berhasilHapus = mysqli_stmt_execute($hapus);
            mysqli_stmt_close($hapus);

            if ($berhasilHapus) {
                $pesan = "Akun berhasil dihapus.";
                catatAktivitas(
                    $conn,
                    "Menghapus pengguna ID " . $hapusId
                        . " dengan role " . ($target["role"] ?? "tidak diketahui") . "."
                );
            } else {
                $pesan = "Akun gagal dihapus.";
                $tipePesan = "error";
            }
        }
    } else {
        $nama = trim((string) ($_POST["nama"] ?? ""));
        $username = trim((string) ($_POST["username"] ?? ""));
        $password = (string) ($_POST["password"] ?? "");
        $role = (string) ($_POST["role"] ?? "viewer");

        if ($nama === "" || $username === "" || strlen($password) < 8) {
            $pesan = "Nama, username, dan password minimal 8 karakter wajib diisi.";
            $tipePesan = "error";
        } elseif (!in_array($role, ["admin", "viewer", "superadmin"], true)) {
            $pesan = "Role pengguna tidak valid.";
            $tipePesan = "error";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)"
            );

            if ($stmt) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "ssss", $username, $hash, $nama, $role);

                if (mysqli_stmt_execute($stmt)) {
                    $pesan = "Akun berhasil ditambahkan.";
                    catatAktivitas($conn, "Menambahkan pengguna " . $username . " dengan role " . $role . ".");
                } else {
                    $pesan = mysqli_stmt_errno($stmt) === 1062
                        ? "Username sudah digunakan."
                        : "Akun gagal ditambahkan.";
                    $tipePesan = "error";
                }

                mysqli_stmt_close($stmt);
            } else {
                $pesan = "Query pengguna gagal disiapkan.";
                $tipePesan = "error";
            }
        }
    }
}

$daftarPengguna = mysqli_query(
    $conn,
    "SELECT id, username, nama, role FROM users ORDER BY nama ASC"
);

$judulHalaman = "Manajemen Admin";
$subjudulHalaman = "Kelola akun dan hak akses pengguna sistem.";
$halamanAktif = "pengguna";

require __DIR__ . "/partials/atas.php";

?>
    <div class="dashboard-chart">
        <section class="form-card">
            <div class="form-card-header">
                <h2>Tambah Akun</h2>
                <p>Buat akun baru untuk mengakses sistem.</p>
            </div>

            <div class="form-body">
                <?php if ($pesan !== ""): ?>
                    <div class="<?= $tipePesan === "error" ? "alert-error" : "alert"; ?>" role="status">
                        <?= htmlspecialchars($pesan); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input id="nama" name="nama" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" minlength="8" required>
                        <p class="field-note">Minimal 8 karakter.</p>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role">
                            <option value="viewer">Viewer</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-success" type="submit">Tambah Akun</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="data-card">
            <div class="data-card-header">
                <h2>Daftar Akun</h2>
            </div>

            <div class="table-wrapper">
                <table style="min-width:620px">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pengguna = mysqli_fetch_assoc($daftarPengguna)): ?>
                            <tr>
                                <td><?= htmlspecialchars($pengguna["nama"]); ?></td>
                                <td><?= htmlspecialchars($pengguna["username"]); ?></td>
                                <td><span class="badge"><?= htmlspecialchars(ucfirst($pengguna["role"])); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <a class="btn btn-warning" href="edit-pengguna.php?id=<?= (int) $pengguna["id"]; ?>">
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            style="display:inline"
                                            onsubmit="return confirm('Hapus akun ini?');"
                                        >
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int) $pengguna["id"]; ?>">
                                            <button class="btn btn-danger" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php
require __DIR__ . "/partials/bawah.php";
