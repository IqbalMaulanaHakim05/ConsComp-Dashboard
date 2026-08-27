<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';

if (sudahLogin()) {
    $tujuanPenggunaAktif = rolePengguna() === 'pic' ? 'lembur.php' : 'index.php';
    header('Location: ' . URL_DASAR . $tujuanPenggunaAktif);
    exit;
}

$pesan = '';
$username = '';

// Tujuan setelah login dibatasi ke halaman utama yang dikenal.
$halamanTujuan = ['index.php', 'dashboard.php', 'karyawan.php', 'analisis.php'];
$nextMentah = (string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php');
$next = basename(strtok($nextMentah, '?'));

if (!in_array($next, $halamanTujuan, true)) {
    $next = 'index.php';
}

$info = (($_GET['pesan'] ?? '') === 'logout-berhasil')
    ? 'Anda telah keluar dari sesi.'
    : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrfValid($_POST['csrf_token'] ?? null)) {
        $pesan = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } elseif ($username === '' || $password === '') {
        $pesan = 'Username dan password wajib diisi.';
    } elseif (loginPengguna($conn, $username, $password)) {
        $tujuanSetelahLogin = rolePengguna() === 'pic' ? 'lembur.php' : $next;
        header('Location: ' . URL_DASAR . $tujuanSetelahLogin);
        exit;
    } else {
        // Pesan dibuat umum agar tidak membocorkan bagian kredensial yang salah.
        $pesan = 'Username atau password tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <script>const savedTheme = localStorage.getItem('employee-theme'); document.documentElement.dataset.theme = savedTheme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');</script>
    <?php 
    $urlAppTanpaSlash = rtrim(URL_DASAR, "/");
    $urlRootProyek = $urlAppTanpaSlash === "" ? "" : dirname(dirname($urlAppTanpaSlash));
    $urlRootProyek = ($urlRootProyek === "/" || $urlRootProyek === "\\") ? "" : $urlRootProyek;
    $urlLoginCss = $urlRootProyek . "/public/assets/css/login.css";
  ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($urlLoginCss); ?>">
</head>
<body>
<button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Ganti tema">🌙 Dark</button>
<div class="login-layout">
    <section class="intro">
        <div class="brand">
            <span class="brand-icon">HR</span>
            Admin
        </div>
        <h1>Kelola data karyawan dengan aman.</h1>
        <p>Masuk untuk mengakses dashboard, memperbarui data SQL, melakukan sinkronisasi, serta mengelola impor dan ekspor Excel.</p>
    </section>

    <main class="login-panel">
        <h2>Login Admin</h2>
        <p class="subtitle">Masukkan akun administrator untuk melanjutkan.</p>

        <?php if ($info !== ''): ?>
            <div class="alert" role="status" style="border-color:#bbf7d0;color:#166534;background:#f0fdf4;"><?= htmlspecialchars($info); ?></div>
        <?php endif; ?>

        <?php if ($pesan !== ''): ?>
            <div class="alert" role="alert"><?= htmlspecialchars($pesan); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
            <input type="hidden" name="next" value="<?= htmlspecialchars($next); ?>">

            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" value="<?= htmlspecialchars($username); ?>" autocomplete="username" autofocus required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="toggle-password" type="button" onclick="togglePassword()">Lihat</button>
                </div>
            </div>

            <button class="submit" type="submit">Masuk ke Dashboard</button>
        </form>

    </main>
</div>
<script>
function toggleTheme() { const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark'; document.documentElement.dataset.theme = next; localStorage.setItem('employee-theme', next); document.querySelector('.theme-toggle').textContent = next === 'dark' ? '☀️ Light' : '🌙 Dark'; }
document.querySelector('.theme-toggle').textContent = document.documentElement.dataset.theme === 'dark' ? '☀️ Light' : '🌙 Dark';
function togglePassword() {
    const input = document.getElementById('password');
    const button = document.querySelector('.toggle-password');
    const terlihat = input.type === 'text';
    input.type = terlihat ? 'password' : 'text';
    button.textContent = terlihat ? 'Lihat' : 'Sembunyikan';
}
</script>
<?php if ($info !== ''): ?>
<script>
    // Hapus parameter URL agar notifikasi tidak muncul lagi saat halaman dimuat ulang.
    if (window.history && window.history.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>
<?php endif; ?>
</body>
</html>
