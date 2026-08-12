<?php

/*
|--------------------------------------------------------------------------
| Action bar khusus halaman Karyawan: import dan export data.
| (Tambah Karyawan dipindah menjadi sub-nav pada sidebar.)
|--------------------------------------------------------------------------
*/

?>
<?php if (in_array(rolePengguna(), ["admin", "superadmin"], true)): ?>
    <a
        href="fungsi/import_excel.php"
        class="btn btn-warning"
        title="Ganti data SQL menggunakan file Excel"
    >
        Import Excel
    </a>
<?php endif; ?>

<a
    href="fungsi/export_excel.php"
    class="btn btn-primary"
    title="Export data karyawan sesuai cakupan akses"
>
    Export Excel
</a>
