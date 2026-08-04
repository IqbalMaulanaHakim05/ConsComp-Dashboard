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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            padding: 25px 18px;
            background-color: #0f172a;
            color: #ffffff;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            margin-bottom: 35px;
        }

        .sidebar-brand h2 {
            margin: 0;
            font-size: 22px;
        }

        .sidebar-brand p {
            margin: 7px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 14px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            color: #ffffff;
            background-color: #2563eb;
        }

        /* Sub-menu bergaya folder-tree: menjorok ke kanan dengan garis cabang. */
        .sidebar-tree-children {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-left: 16px;
            padding-left: 14px;
            border-left: 1px solid #334155;
        }

        .sidebar-tree-children a {
            position: relative;
        }

        .sidebar-tree-children a::before {
            content: "";
            position: absolute;
            left: -14px;
            top: 50%;
            width: 14px;
            height: 1px;
            background-color: #334155;
        }

        .sidebar-user {
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid #1e293b;
        }

        .sidebar-user-meta {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 13px;
        }

        .sidebar-user-name {
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
        }

        .role-badge {
            align-self: flex-start;
            padding: 3px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            color: #e2e8f0;
            background-color: #334155;
        }

        .role-badge.role-superadmin {
            color: #fde68a;
            background-color: #78350f;
        }

        .role-badge.role-admin {
            color: #bfdbfe;
            background-color: #1e3a8a;
        }

        .role-badge.role-viewer {
            color: #cbd5e1;
            background-color: #334155;
        }

        .btn-logout {
            display: block;
            padding: 10px 14px;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            color: #fecaca;
            background-color: #7f1d1d;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-logout:hover {
            color: #ffffff;
            background-color: #991b1b;
        }

        .main-content {
            margin-left: 240px;
            min-height: 100vh;
            padding: 30px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .topbar-title h1 {
            margin: 0;
            font-size: 29px;
            color: #0f172a;
        }

        .topbar-title p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 14px;
        }

        .topbar-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border: none;
            border-radius: 7px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover {
            opacity: 0.86;
        }

        .btn-primary {
            color: #ffffff;
            background-color: #2563eb;
        }

        .btn-success {
            color: #ffffff;
            background-color: #16a34a;
        }

        .btn-warning {
            color: #ffffff;
            background-color: #f59e0b;
        }

        .btn-danger {
            color: #ffffff;
            background-color: #dc2626;
        }

        .btn-secondary {
            color: #334155;
            background-color: #e2e8f0;
        }

        .alert {
            padding: 13px 16px;
            margin-bottom: 22px;
            border-radius: 8px;
            border: 1px solid #86efac;
            color: #166534;
            background-color: #dcfce7;
        }

        .statistics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            padding: 22px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.07);
        }

        .stat-card span {
            display: block;
            margin-bottom: 10px;
            color: #64748b;
            font-size: 13px;
        }

        .stat-card h3 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
        }

        .stat-card p {
            margin: 8px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .dashboard-chart {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .chart-card {
            min-width: 0;
            padding: 22px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.07);
        }

        .chart-card h2 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
        }

        .chart-card p {
            margin: 6px 0 20px;
            color: #64748b;
            font-size: 13px;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 320px;
        }

        .data-card {
            padding: 22px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.07);
        }

        .data-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .data-card-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 19px;
        }

        .search-form {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .search-form input {
            width: 280px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }

        .search-form input:focus {
            border-color: #2563eb;
        }

        .search-form select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            background-color: #ffffff;
            color: #1e293b;
            cursor: pointer;
            outline: none;
        }

        .search-form select:focus {
            border-color: #2563eb;
        }

        .result-info {
            margin-bottom: 14px;
            color: #64748b;
            font-size: 13px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1450px;
            border-collapse: collapse;
        }

        th:last-child,
        td:last-child {
            position: sticky;
            right: 0;
            z-index: 2;
        }

        th:last-child {
            background-color: #0f172a;
        }

        td:last-child {
            background-color: #ffffff;
        }

        tbody tr:nth-child(even) td:last-child {
            background-color: #f8fafc;
        }

        tbody tr:hover td:last-child {
            background-color: #eff6ff;
        }

        thead {
            color: #ffffff;
            background-color: #0f172a;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
            font-size: 13px;
        }

        th {
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tbody tr:hover {
            background-color: #eff6ff;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            color: #1d4ed8;
            background-color: #dbeafe;
            font-size: 11px;
        }

        .action-buttons {
            display: flex;
            gap: 7px;
            white-space: nowrap;
        }

        .action-buttons .btn {
            padding: 7px 10px;
            font-size: 12px;
        }

        .empty-data {
            padding: 30px;
            text-align: center;
            color: #64748b;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .pagination-info {
            color: #64748b;
            font-size: 13px;
        }

        .pagination-nav {
            display: flex;
            gap: 8px;
        }

        .pagination-nav a,
        .pagination-nav span {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 13px;
            text-decoration: none;
            color: #334155;
            background-color: #ffffff;
            transition: 0.2s;
        }

        .pagination-nav a:hover {
            border-color: #2563eb;
            color: #2563eb;
        }

        .pagination-nav .disabled {
            color: #cbd5e1;
            background-color: #f8fafc;
            cursor: not-allowed;
        }

        /* Form (tambah/edit/import) mengikuti gaya kartu dashboard. */
        .form-card {
            max-width: 960px;
            overflow: hidden;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.07);
        }

        .form-card-header {
            padding: 22px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-card-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 19px;
        }

        .form-card-header p {
            margin: 7px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-body {
            padding: 24px;
        }

        .alert-error {
            margin-bottom: 20px;
            padding: 13px 16px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #991b1b;
            background-color: #fee2e2;
            font-size: 14px;
        }

        .form-warning {
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #fde68a;
            border-radius: 8px;
            color: #92400e;
            background-color: #fef3c7;
            line-height: 1.5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            min-width: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-card label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .form-card .required {
            color: #dc2626;
        }

        .form-card input,
        .form-card select {
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            color: #0f172a;
            background-color: #ffffff;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-card input:focus,
        .form-card select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-card input::placeholder {
            color: #94a3b8;
        }

        .field-note {
            margin: 7px 0 0;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.4;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }

        .mobile-menu {
            display: none;
            margin-bottom: 20px;
        }

        @media screen and (max-width: 1200px) {
            .statistics {
                grid-template-columns: repeat(2, 1fr);
            }

            .dashboard-chart {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 800px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                display: none;
            }

            .sidebar.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 18px;
            }

            .mobile-menu {
                display: inline-block;
            }

            .statistics {
                grid-template-columns: 1fr;
            }

            .search-form {
                width: 100%;
            }

            .search-form input {
                width: 100%;
            }

            .data-card-header {
                align-items: stretch;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
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

        <div class="sidebar-tree-children">
            <a
                href="<?= htmlspecialchars(URL_DASAR); ?>karyawan.php"
                class="<?= $halamanAktif === "karyawan" ? "active" : ""; ?>"
            >
                Karyawan
            </a>

            <a
                href="<?= htmlspecialchars(URL_DASAR); ?>analisis.php"
                class="<?= $halamanAktif === "analisis" ? "active" : ""; ?>"
            >
                Analisis
            </a>
        </div>

        <a href="<?= htmlspecialchars(URL_DASAR); ?>../index.php">
            Halaman Publik
        </a>
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
