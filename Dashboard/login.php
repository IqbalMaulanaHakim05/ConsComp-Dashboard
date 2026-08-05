<?php

declare(strict_types=1);

require __DIR__ . '/koneksi.php';
require_once __DIR__ . '/fungsi/auth.php';

if (sudahLogin()) {
    header('Location: ' . URL_DASAR . 'index.php');
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
        header('Location: ' . URL_DASAR . $next);
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
    <title>Login Admin Karyawan</title>
    <script>const savedTheme = localStorage.getItem('employee-theme'); document.documentElement.dataset.theme = savedTheme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');</script>
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, .18), transparent 34%),
                linear-gradient(135deg, #e2e8f0, #f8fafc 55%, #dbeafe);
        }
        .login-layout {
            width: min(920px, 100%);
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            overflow: hidden;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 24px 65px rgba(15, 23, 42, .18);
        }
        .intro {
            position: relative;
            min-height: 520px;
            padding: 56px 48px;
            color: #fff;
            background: linear-gradient(145deg, #0f172a, #1e3a8a 70%, #2563eb);
        }
        .intro::after {
            content: "";
            position: absolute;
            right: -65px;
            bottom: -65px;
            width: 210px;
            height: 210px;
            border: 34px solid rgba(255,255,255,.09);
            border-radius: 50%;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .brand-icon {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(255,255,255,.14);
        }
        .intro h1 {
            max-width: 420px;
            margin: 76px 0 18px;
            font-size: clamp(34px, 5vw, 48px);
            line-height: 1.08;
        }
        .intro p {
            max-width: 440px;
            margin: 0;
            color: #cbd5e1;
            font-size: 16px;
            line-height: 1.75;
        }
        .login-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 52px 46px;
        }
        .login-panel h2 {
            margin: 0;
            font-size: 30px;
        }
        .subtitle {
            margin: 9px 0 28px;
            color: #64748b;
            line-height: 1.6;
        }
        .alert {
            margin-bottom: 20px;
            padding: 13px 15px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #991b1b;
            background: #fef2f2;
            font-size: 14px;
        }
        .field { margin-bottom: 18px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
        }
        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font: inherit;
            outline: none;
            transition: .2s;
        }
        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }
        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 78px; }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            padding: 7px 9px;
            border: 0;
            color: #2563eb;
            background: transparent;
            cursor: pointer;
            font-weight: 700;
        }
        .submit {
            width: 100%;
            margin-top: 5px;
            padding: 13px 16px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #2563eb;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
        }
        .submit:hover { background: #1d4ed8; transform: translateY(-1px); }
        .hint {
            margin: 22px 0 0;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }
        :root[data-theme="dark"] body { color: #e2e8f0; background: #0f172a; }
        :root[data-theme="dark"] .login-layout, :root[data-theme="dark"] .login-panel { background: #1e293b; }
        :root[data-theme="dark"] .subtitle, :root[data-theme="dark"] .hint { color: #94a3b8; }
        :root[data-theme="dark"] input { color: #e2e8f0; background: #0f172a; border-color: #475569; }
        .theme-toggle { position: fixed; top: 18px; right: 18px; z-index: 2; padding: 9px 12px; border: 0; border-radius: 9px; color: #334155; background: #e2e8f0; cursor: pointer; }
        :root[data-theme="dark"] .theme-toggle { color: #e2e8f0; background: #334155; }
        @media (max-width: 760px) {
            .login-layout { grid-template-columns: 1fr; }
            .intro { min-height: auto; padding: 34px 28px; }
            .intro h1 { margin-top: 35px; font-size: 34px; }
            .login-panel { padding: 38px 28px; }
        }
    </style>
</head>
<body>
<button type="button" class="theme-toggle" onclick="toggleTheme()" aria-label="Ganti tema">🌙 Dark</button>
<div class="login-layout">
    <section class="intro">
        <div class="brand">
            <span class="brand-icon">HR</span>
            Admin Karyawan
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

        <!-- <p class="hint">Akun awal &mdash; <strong>superadmin</strong>/super123, <strong>admin</strong>/admin123, <strong>viewer</strong>/viewer123. Demi keamanan, ganti password akun-akun ini di tabel <code>users</code> sebelum aplikasi digunakan secara publik.</p> -->
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
