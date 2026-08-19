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

        $target = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT username, nama, role FROM users WHERE id = " . $hapusId . " LIMIT 1"
        ));

        if (!csrfValid($_POST["csrf_token"] ?? null)) {
            $pesan = "Sesi formulir tidak valid.";
            $tipePesan = "error";
        } elseif (!in_array(($target["role"] ?? ""), ["admin", "pic", "koordinator", "direktur", "manager", "viewer"], true)) {
            $pesan = "Akun dengan role ini tidak dapat dihapus.";
            $tipePesan = "error";
        } elseif ($hapusId === $superadminAktif) {
            $pesan = "Akun yang sedang digunakan tidak dapat dihapus.";
            $tipePesan = "error";
        } else {
            $referensi = mysqli_prepare(
                $conn,
                "SELECT
                    (SELECT COUNT(*) FROM overtime_reports WHERE dibuat_oleh_pic = ?) AS laporan_lembur,
                    (SELECT COUNT(*) FROM overtime_compensations WHERE dimasukkan_oleh_pic = ?) AS kompensasi_lembur,
                    (SELECT COUNT(*) FROM slip_gaji WHERE generated_by = ?) AS slip_gaji"
            );
            mysqli_stmt_bind_param($referensi, "iii", $hapusId, $hapusId, $hapusId);
            mysqli_stmt_execute($referensi);
            $jumlahReferensi = mysqli_fetch_assoc(mysqli_stmt_get_result($referensi)) ?: [];
            mysqli_stmt_close($referensi);

            $namaTarget = trim((string) ($target["nama"] ?? $target["username"] ?? "Akun"));
            $memilikiReferensi = (int) ($jumlahReferensi["laporan_lembur"] ?? 0) > 0
                || (int) ($jumlahReferensi["kompensasi_lembur"] ?? 0) > 0
                || (int) ($jumlahReferensi["slip_gaji"] ?? 0) > 0;

            if ($memilikiReferensi) {
                $pesan = "Akun \"" . $namaTarget . "\" tidak dapat dihapus karena masih memiliki riwayat lembur atau slip gaji.";
                $tipePesan = "error";
            } else {
                try {
                    $hapus = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
                    mysqli_stmt_bind_param($hapus, "i", $hapusId);
                    $berhasilHapus = mysqli_stmt_execute($hapus);
                    mysqli_stmt_close($hapus);

                    if ($berhasilHapus) {
                        $pesan = "Akun berhasil dihapus.";
                        catatAktivitas(
                            $conn,
                            "Menghapus pengguna ID " . $hapusId
                                . " dengan role " . labelRole((string) ($target["role"] ?? "tidak diketahui")) . "."
                        );
                    } else {
                        $pesan = "Akun gagal dihapus.";
                        $tipePesan = "error";
                    }
                } catch (mysqli_sql_exception $exception) {
                    $pesan = "Akun tidak dapat dihapus karena masih memiliki data terkait.";
                    $tipePesan = "error";
                }
            }
        }
    } else {
        $nama = trim((string) ($_POST["nama"] ?? ""));
        $username = trim((string) ($_POST["username"] ?? ""));
        $password = (string) ($_POST["password"] ?? "");
        $konfirmasiPassword = (string) ($_POST["password_confirmation"] ?? "");
        $role = (string) ($_POST["role"] ?? "admin");
        $departmentId = (int) ($_POST["department_id"] ?? 0);
        $roleDiizinkan = ["admin", "pic", "koordinator", "direktur", "manager"];

        if (!csrfValid($_POST["csrf_token"] ?? null)) {
            $pesan = "Sesi formulir tidak valid.";
            $tipePesan = "error";
        } elseif ($nama === "" || $username === "" || strlen($password) < 8 || !in_array($role, $roleDiizinkan, true)) {
            $pesan = "Nama, username, dan password minimal 8 karakter wajib diisi.";
            $tipePesan = "error";
        } elseif ($password !== $konfirmasiPassword) {
            $pesan = "Konfirmasi password tidak cocok.";
            $tipePesan = "error";
        } elseif (in_array(roleEfektif($role), ["pic", "koordinator", "manager"], true) && $departmentId <= 0) {
            $pesan = "Departemen wajib dipilih untuk role operasional.";
            $tipePesan = "error";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (username, password, nama, role, department_id) VALUES (?, ?, ?, ?, NULLIF(?, 0))"
            );

            if ($stmt) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "ssssi", $username, $hash, $nama, $role, $departmentId);

                if (mysqli_stmt_execute($stmt)) {
                    $pesan = "Akun berhasil ditambahkan.";
                    catatAktivitas(
                        $conn,
                        "Menambahkan pengguna " . $username . " dengan role " . labelRole($role)
                            . " dan departemen ID " . ($departmentId > 0 ? (string) $departmentId : "semua") . "."
                    );
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
$daftarDepartemen = mysqli_query($conn, "SELECT id, nama FROM master_departemen WHERE is_active = 1 ORDER BY nama ASC");

$judulHalaman = "Manajemen Pengguna";
$subjudulHalaman = "Kelola akun dan akses Admin HRGA serta peran operasional.";
$halamanAktif = "pengguna";

require __DIR__ . "/partials/atas.php";

?>
    <div class="dashboard-chart">
        <section class="form-card">
            <div class="form-card-header">
                        <h2>Tambah Akun</h2>
                        <p>Buat akun baru dengan hak akses sesuai kebutuhan.</p>
            </div>

            <div class="form-body">
                <?php if ($pesan !== ""): ?>
                    <div class="<?= $tipePesan === "error" ? "alert-error" : "alert"; ?>" role="status">
                        <?= htmlspecialchars($pesan); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">

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
                        <div class="password-input-wrap tambah-akun-password">
                            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                            <button class="password-toggle" type="button" data-password-toggle="password" aria-controls="password" aria-label="Tampilkan password" aria-pressed="false">
                                <svg class="password-toggle-icon password-eye-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"></path>
                                    <circle cx="12" cy="12" r="2.5"></circle>
                                </svg>
                                <svg class="password-toggle-icon password-eye-off-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="m3 3 18 18"></path>
                                    <path d="M10.6 6.9A10.4 10.4 0 0 1 12 7c6 0 9.5 5 9.5 5a16.7 16.7 0 0 1-3.1 3.2M6.5 6.6C3.9 8.1 2.5 12 2.5 12s3.5 5 9.5 5c1 0 1.9-.1 2.7-.4"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="field-note">Minimal 8 karakter.</p>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <div class="password-input-wrap tambah-akun-password">
                            <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                            <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan password" aria-pressed="false">
                                <svg class="password-toggle-icon password-eye-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"></path>
                                    <circle cx="12" cy="12" r="2.5"></circle>
                                </svg>
                                <svg class="password-toggle-icon password-eye-off-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="m3 3 18 18"></path>
                                    <path d="M10.6 6.9A10.4 10.4 0 0 1 12 7c6 0 9.5 5 9.5 5a16.7 16.7 0 0 1-3.1 3.2M6.5 6.6C3.9 8.1 2.5 12 2.5 12s3.5 5 9.5 5c1 0 1.9-.1 2.7-.4"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="admin">Admin HRGA</option>
                            <option value="pic">PIC</option>
                            <option value="koordinator">Koordinator</option>
                            <option value="direktur">Direktur</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="department_id">Departemen</label>
                        <select id="department_id" name="department_id">
                            <option value="0">Semua departemen / tidak terikat</option>
                            <?php while ($departemen = mysqli_fetch_assoc($daftarDepartemen)): ?>
                                <option value="<?= (int) $departemen["id"]; ?>"><?= htmlspecialchars($departemen["nama"]); ?></option>
                            <?php endwhile; ?>
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
                                <td><span class="badge"><?= htmlspecialchars(labelRole((string) $pengguna["role"])); ?></span></td>
                                <td>
                                    <?php if ($pengguna["role"] !== "superadmin"): ?>
                                        <div class="action-buttons">
                                            <a class="btn btn-warning" href="edit-pengguna.php?id=<?= (int) $pengguna["id"]; ?>">
                                                Edit
                                            </a>

                                            <?php if (in_array($pengguna["role"], ["admin", "pic", "koordinator", "direktur", "manager", "viewer"], true)): ?>
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
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="field-note">Akun terlindungi</span>
                                    <?php endif; ?>
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
