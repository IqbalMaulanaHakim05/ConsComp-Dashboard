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

$cssPerHalaman = [
    "dashboard" => "admin-dashboard.css",
    "analisis" => "admin-dashboard.css",
    "karyawan" => "admin-karyawan.css",
    "upah" => "admin-upah.css",
    "tambah" => "admin-form.css",
    "import" => "admin-form.css",
    "edit" => "admin-form.css",
    "profil-karyawan" => "admin-profile.css",
    "master-data" => "admin-settings.css",
    "pengguna" => "admin-settings.css",
    "audit" => "admin-settings.css",
    "pengaturan-publik" => "admin-settings.css",
    "lembur" => "admin-overtime.css",
];
$cssHalamanAktif = $cssPerHalaman[$halamanAktif] ?? "";
$kelasHalaman = preg_replace("/[^a-z0-9-]/i", "-", $halamanAktif);
if (function_exists("rolePengguna") && rolePengguna() === "pic" && !in_array($halamanAktif, ["upah", "lembur"], true)) {
    header("Location: upah.php");
    exit;
}
if (function_exists("rolePengguna") && rolePengguna() === "koordinator" && !in_array($halamanAktif, ["karyawan", "upah", "lembur"], true)) {
    header("Location: karyawan.php");
    exit;
}
if (function_exists("rolePengguna") && rolePengguna() === "koordinator" && basename((string) ($_SERVER["SCRIPT_NAME"] ?? "")) === "profil-karyawan.php") {
    header("Location: karyawan.php");
    exit;
}
if (basename((string) ($_SERVER["SCRIPT_NAME"] ?? "")) === "profil-karyawan.php") {
    $cssHalamanAktif = "admin-profile.css";
    $kelasHalaman = "profil-karyawan";
}

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

    <link rel="stylesheet" href="<?= htmlspecialchars($urlStyle); ?>admin.css?v=20260812-password-toggle3">
    <?php if ($cssHalamanAktif !== ""): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($urlStyle . $cssHalamanAktif); ?>?v=<?= $halamanAktif === "upah" ? "20260812-upah-preview-top2" : ($halamanAktif === "lembur" ? "20260812-overtime-buttons4" : ($halamanAktif === "karyawan" ? "20260811-karyawan-columns1" : ($halamanAktif === "dashboard" ? "20260811-dashboard-columns5" : "20260811-layout8"))); ?>">
    <?php endif; ?>
</head>

<body class="<?= htmlspecialchars($kelasHalaman); ?>-page">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2>Admin Karyawan</h2>

        <p>
            Sistem pengelolaan dataset
        </p>
    </div>

    <nav class="sidebar-menu">
        <?php if (!(function_exists("punyaRole") && punyaRole("pic", "koordinator"))): ?><a
            href="<?= htmlspecialchars(URL_DASAR); ?>index.php"
            class="<?= $halamanAktif === "dashboard" ? "active" : ""; ?>"
        >
            <span class="sidebar-icon" aria-hidden="true">⌂</span>Dashboard
        </a><?php endif; ?>

        <?php if (!(function_exists("punyaRole") && punyaRole("pic"))): ?><a
            href="<?= htmlspecialchars(URL_DASAR); ?>karyawan.php"
            class="<?= in_array($halamanAktif, ["karyawan", "analisis", "upah", "lembur", "periode-gaji"], true) ? "active" : ""; ?>"
        >
            <span class="sidebar-icon" aria-hidden="true">♙</span>Data Karyawan
        </a><?php endif; ?>

        <div class="sidebar-tree-children">
            <?php if (function_exists("punyaRole") && punyaRole("admin", "superadmin")): ?>
                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>fungsi/tambah.php"
                    class="<?= $halamanAktif === "tambah" ? "active" : ""; ?>"
                >
                    <span class="sidebar-icon" aria-hidden="true">+</span>Tambah Karyawan
                </a>
            <?php endif; ?>

            <?php if (!(function_exists("punyaRole") && punyaRole("pic", "koordinator"))): ?><a
                href="<?= htmlspecialchars(URL_DASAR); ?>analisis.php"
                class="<?= $halamanAktif === "analisis" ? "active" : ""; ?>"
            >
                <span class="sidebar-icon" aria-hidden="true">◒</span>Analisis
            </a><?php endif; ?>

            <a
                href="<?= htmlspecialchars(URL_DASAR); ?>upah.php"
                class="<?= $halamanAktif === "upah" ? "active" : ""; ?>"
            >
                <span class="sidebar-icon" aria-hidden="true">Rp</span>Upah
            </a>

            <a
                href="<?= htmlspecialchars(URL_DASAR); ?>lembur.php"
                class="<?= $halamanAktif === "lembur" ? "active" : ""; ?>"
            >
                <span class="sidebar-icon" aria-hidden="true">↗</span>Lembur
            </a>

        </div>

        <?php if (function_exists("punyaRole") && punyaRole("superadmin")): ?>
            <div class="sidebar-group-title">Pengaturan</div>

            <div class="sidebar-tree-children">
                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>pengaturan-publik.php"
                    class="<?= $halamanAktif === "pengaturan-publik" ? "active" : ""; ?>"
                >
                    <span class="sidebar-icon" aria-hidden="true">⚙</span>Personalisasi Tampilan
                </a>
                <?php if (punyaRole("superadmin")): ?>
                    <a href="<?= htmlspecialchars(URL_DASAR); ?>master-data.php" class="<?= $halamanAktif === "master-data" ? "active" : ""; ?>"><span class="sidebar-icon" aria-hidden="true">▦</span>Master Departemen, Posisi &amp; Status</a>
                <?php endif; ?>

                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>pengguna.php"
                    class="<?= $halamanAktif === "pengguna" ? "active" : ""; ?>"
                    >
                    <span class="sidebar-icon" aria-hidden="true">♟</span>Manajemen Admin
                </a>

                <a
                    href="<?= htmlspecialchars(URL_DASAR); ?>audit-aktivitas.php"
                    class="<?= $halamanAktif === "audit" ? "active" : ""; ?>"
                    >
                    <span class="sidebar-icon" aria-hidden="true">◷</span>Audit Aktivitas
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
    <?php elseif ($pesanNotifikasi === "cv-berhasil"): ?>
        <div class="alert">
            CV PDF berhasil dibuat, disimpan ke profil karyawan, dan siap diunduh.
        </div>
    <?php endif; ?>
