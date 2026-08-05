<?php

/*
|--------------------------------------------------------------------------
| Action bar khusus halaman Karyawan: import dan export data.
| (Tambah Karyawan dipindah menjadi sub-nav pada sidebar.)
|--------------------------------------------------------------------------
*/

?>
<a
    href="fungsi/import_excel.php"
    class="btn btn-warning"
    title="Ganti data SQL menggunakan file Excel"
>
    Import Excel
</a>

<a
    href="fungsi/export_excel.php"
    class="btn btn-primary"
    title="Unduh seluruh data karyawan dari SQL"
>
    Export Excel
</a>
