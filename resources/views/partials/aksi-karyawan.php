<?php

/*
|--------------------------------------------------------------------------
| Action bar khusus halaman Karyawan: import dan export data.
| (Tambah Karyawan dipindah menjadi sub-nav pada sidebar.)
|--------------------------------------------------------------------------
*/

?>
<?php if (punyaRole("superadmin")): ?>
    <a
        href="import_excel.php"
        class="btn btn-warning"
        title="Ganti data SQL menggunakan file Excel"
    >
        Import Excel
    </a>
<?php endif; ?>

<a
    href="export_excel.php?<?= htmlspecialchars(http_build_query(["cari" => $_GET["cari"] ?? "", "filter" => $_GET["filter"] ?? "semua", "sort" => $_GET["sort"] ?? "id", "arah" => $_GET["arah"] ?? "DESC"])); ?>"
    class="btn btn-primary"
    title="Export data karyawan sesuai cakupan akses"
>
    Export Excel
</a>
