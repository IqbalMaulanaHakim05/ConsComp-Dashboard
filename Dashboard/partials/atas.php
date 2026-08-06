<?php

/*
|--------------------------------------------------------------------------
| Bagian atas layout: <head>, sidebar, topbar, dan notifikasi.
| Variabel yang diharapkan dari halaman pemanggil:
|   $judulHalaman, $subjudulHalaman, $halamanAktif
|--------------------------------------------------------------------------
*/

// URL_DASAR disediakan oleh auth.php. Fallback agar layout tetap aman
// bila suatu saat dipakai tanpa auth.
if (!defined("URL_DASAR")) {
    define("URL_DASAR", "");
}

// Folder "style" berada di root proyek, satu tingkat di atas aplikasi admin.
$urlAppTanpaSlash = rtrim(URL_DASAR, "/");
$urlRootProyek = $urlAppTanpaSlash === "" ? "" : dirname($urlAppTanpaSlash);
$urlRootProyek = ($urlRootProyek === "/" || $urlRootProyek === "\\") ? "" : $urlRootProyek;
$urlStyle = $urlRootProyek . "/style/";

// Nama variabel dibedakan dari $pesan halaman (mis. pesan error form).
$pesanNotifikasi = trim($_GET["pesan"] ?? "");
$halamanAktif = $halamanAktif ?? "dashboard";
$judulHalaman = $judulHalaman ?? "Dashboard Admin";
$subjudulHalaman = $subjudulHalaman ?? "";

// Path partial action bar; kosong berarti halaman tidak punya action bar.
$aksiTopbar = $aksiTopbar ?? "";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Admin Karyawan</title>

    <script>
        (() => {
            const saved = localStorage.getItem('employee-theme');
            document.documentElement.dataset.theme = saved || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="<?= htmlspecialchars($urlStyle); ?>admin.css?v=20260806-print2">
</head>

<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2>Admin Karyawan</h2>

        <p>
            Sistem pengelolaan dataset
        </p>
    </div>

    <nav class="sidebar-menu">
        <a
            href="<?= htmlspecialchars(URL_DASAR); ?>index.php"
            class="<?= $halamanAktif === "dashboard" ? "active" : ""; ?>"
        >
            Dashboard
        </a>

        <a
            href="<?= htmlspecialchars(URL_DASAR); ?>karyawan.php"
            class="<?= $halamanAktif === "karyawan" ? "active" : ""; ?>"
        >
            Karyawan
        </a>

        <?php if (function_exists("punyaRole") && punyaRole("admin", "superadmin")): ?>
            <div class="sidebar-tree-children">
                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>fungsi/tambah.php"
                    class="<?= $halamanAktif === "tambah" ? "active" : ""; ?>"
                >
                    Tambah Karyawan
                </a>
            </div>
        <?php endif; ?>

        <a
            href="<?= htmlspecialchars(URL_DASAR); ?>analisis.php"
            class="<?= $halamanAktif === "analisis" ? "active" : ""; ?>"
        >
            Analisis
        </a>

        <?php if (function_exists("punyaRole") && punyaRole("superadmin")): ?>
            <div class="sidebar-group-title">Pengaturan</div>

            <div class="sidebar-tree-children">
                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>pengaturan-publik.php"
                    class="<?= $halamanAktif === "pengaturan-publik" ? "active" : ""; ?>"
                >
                    Pengaturan Halaman Publik
                </a>

                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>pengguna.php"
                    class="<?= $halamanAktif === "pengguna" ? "active" : ""; ?>"
                >
                    Manajemen Admin
                </a>

                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>audit-aktivitas.php"
                    class="<?= $halamanAktif === "audit" ? "active" : ""; ?>"
                >
                    Audit Aktivitas
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <?php if (function_exists("sudahLogin") && sudahLogin()): ?>
        <div class="sidebar-user">
            <div class="sidebar-user-meta">
                <span class="sidebar-user-name">
                    <?= htmlspecialchars(namaPengguna()); ?>
                </span>

                <span class="role-badge role-<?= htmlspecialchars(rolePengguna()); ?>">
                    <?= htmlspecialchars(ucfirst(rolePengguna())); ?>
                </span>
            </div>

            <a href="<?= htmlspecialchars(URL_DASAR); ?>logout.php" class="btn-logout">
                Logout
            </a>

            <a href="<?= htmlspecialchars(URL_DASAR); ?>../index.php" class="btn-publik">
                Halaman Publik
            </a>
        </div>
    <?php endif; ?>
</aside>

<main class="main-content">

    <button
        type="button"
        class="btn btn-primary mobile-menu"
        onclick="toggleSidebar()"
    >
        Menu Admin
    </button>

    <div class="topbar">
        <div class="topbar-title">
            <h1><?= htmlspecialchars($judulHalaman); ?></h1>

            <p>
                <?= htmlspecialchars($subjudulHalaman); ?>
            </p>
        </div>

        <?php if ($aksiTopbar !== "" && is_file($aksiTopbar)): ?>
            <div class="topbar-actions">
                <?php require $aksiTopbar; ?>
            </div>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary theme-toggle" onclick="toggleTheme()" aria-label="Ganti tema">🌙 Dark</button>
    </div>

    <?php if ($pesanNotifikasi === "tambah-berhasil"): ?>
        <div class="alert">
            Data karyawan berhasil ditambahkan.
        </div>
    <?php elseif ($pesanNotifikasi === "edit-berhasil"): ?>
        <div class="alert">
            Data karyawan berhasil diperbarui.
        </div>
    <?php elseif ($pesanNotifikasi === "hapus-berhasil"): ?>
        <div class="alert">
            Data karyawan berhasil dihapus.
        </div>
    <?php elseif ($pesanNotifikasi === "import-excel-berhasil"): ?>
        <div class="alert">
            Import Excel berhasil.
            <strong><?= (int) ($_GET["jumlah"] ?? 0); ?></strong>
            data karyawan telah menggantikan data SQL sebelumnya.
        </div>
    <?php endif; ?>
