# Laporan Harian Development

Tanggal: 10 Agustus 2026

## Aktivitas

| Waktu | Aktivitas | Referensi |
| --- | --- | --- |
| Hari ini | Mengubah label sidebar `Karyawan` menjadi `Data Karyawan`. | `Dashboard/partials/atas.php` |
| Hari ini | Memindahkan `Analisis` menjadi sub-sidebar `Data Karyawan`. | `Dashboard/partials/atas.php` |
| Hari ini | Menambahkan card jumlah karyawan laki-laki dan perempuan pada Analisis. | `Dashboard/analisis.php`, `Dashboard/fungsi/analisis-karyawan.php` |
| Hari ini | Menyusun kerangka sidebar Upah dan Lembur. | `Dashboard/partials/atas.php` |
| Hari ini | Membuat sistem backup dan tabel kontrol `schema_migrations`. | `database/migrations/001_create_schema_migrations.sql` |
| Hari ini | Menjalankan migrasi normalisasi departemen dan riwayat karyawan. | Migrasi `002_normalize_departments_and_histories` |
| Hari ini | Menambahkan role PIC, Koordinator, Manager, `department_id`, dan `is_active`. | Migrasi `003_extend_user_roles` |
| Hari ini | Menerapkan pembatasan akses berdasarkan departemen pada Dashboard, Data Karyawan, Analisis, dan Profil. | `Dashboard/fungsi/auth.php`, `data-karyawan.php`, `analisis-karyawan.php` |
| Hari ini | Menambahkan manajemen akun role operasional dan audit perubahan role/departemen. | `Dashboard/pengguna.php`, `edit-pengguna.php` |
| Hari ini | Membuat fondasi Gaji/Upah, profil gaji, komponen, dan migrasi salary lama. | Migrasi `004_create_payroll_tables` |
| Hari ini | Membuat halaman Upah, filter, edit nominal, komponen manual, serta PDF slip gaji. | `Dashboard/upah.php`, `edit-upah.php`, `generate-slip-gaji.php` |
| Hari ini | Membuat fondasi Overtime, approval Koordinator/Manager, dan kompensasi lembur. | Migrasi `005` sampai `007`, `Dashboard/lembur.php` |
| Hari ini | Membuat fondasi periode dan snapshot slip gaji, kemudian menghapus halaman UI Periode/Slip sesuai revisi. | Migrasi `008`, halaman UI dihapus sesuai arahan |
| Hari ini | Menambahkan pendapatan tambahan dan potongan manual per karyawan dengan tambah/hapus baris. | Migrasi `009` dan `010`, `Dashboard/edit-upah.php` |
| Hari ini | Menambahkan kolom dinamis pendapatan, potongan, dan Upah Lembur pada tabel Upah. | `Dashboard/upah.php` |
| Hari ini | Merevisi PDF slip gaji menjadi preview browser, tanpa nama PT dan tanpa periode. | `Dashboard/fungsi/generate-slip-gaji.php` |
| Hari ini | Mengubah Profil Karyawan menjadi inline edit dan popup khusus tambah riwayat. | `Dashboard/profil-karyawan.php`, `Dashboard/fungsi/riwayat.php` |
| Hari ini | Memperbaiki warning ID riwayat dan menghapus tombol aksi duplikat pada card profil. | `Dashboard/profil-karyawan.php` |

## Hasil

- Sidebar mengikuti struktur terbaru: Dashboard, Data Karyawan, Upah, Lembur, dan Pengaturan.
- Sistem migrasi bernomor tersedia dan migrasi `001` sampai `010` tercatat.
- Backup database dibuat sebelum setiap migrasi penting.
- Data karyawan berhasil dipetakan ke departemen tanpa data yang tidak terpetakan.
- Role operasional dan pembatasan departemen tersedia pada sisi database dan sebagian besar halaman aplikasi.
- Halaman Upah menampilkan gaji pokok, uang makan, pendapatan tambahan, potongan, dan upah lembur.
- Pendapatan tambahan dan potongan dapat ditambah atau dihapus per karyawan.
- PDF slip gaji dapat dipreview di browser dan menghitung pendapatan dikurangi potongan.
- Profil Karyawan mendukung tambah dan hapus riwayat pendidikan/pekerjaan melalui popup.
- Laporan Overtime mendukung draft, pengiriman, approval bertingkat, dan kompensasi lembur.

## Kendala dan solusi

| Kendala | Penyebab | Solusi/tindak lanjut |
| --- | --- | --- |
| Tabel `schema_migrations` belum tersedia. | Sistem sebelumnya belum memiliki migration runner. | Membuat tabel kontrol, migrasi bernomor, runner CLI, dan backup database. |
| Sintaks `ADD COLUMN IF NOT EXISTS` gagal pada MySQL lingkungan Laragon. | Sintaks tidak kompatibel dengan server yang digunakan. | Mengganti migrasi dengan sintaks `ADD COLUMN` yang kompatibel dan menjalankannya dari backup. |
| Role operasional belum tersedia pada tabel `users`. | Enum role lama hanya berisi superadmin, admin, dan viewer. | Menambahkan PIC, Koordinator, Manager, `department_id`, dan `is_active`. |
| Akses profil departemen lain masih berpotensi terbuka melalui URL. | Query profil awal hanya memfilter berdasarkan ID karyawan. | Menambahkan helper `karyawanDalamCakupan()` dan validasi departemen server-side. |
| Tombol Edit Upah menghasilkan warning ID tidak ditemukan. | Query halaman Upah belum mengambil `k.id`. | Menambahkan `k.id` pada SELECT. |
| Approval Overtime menghasilkan error MySQL target table. | Query update membaca tabel approval yang sama melalui subquery. | Memisahkan validasi approval Koordinator/Manager dari query update. |
| Kolom komponen menampilkan nama tidak informatif seperti `A`. | Data komponen lama memiliki nama yang tidak sesuai kebutuhan. | Menghapus komponen `A` beserta assignment dan membuat input komponen manual. |
| PDF langsung terunduh. | Header menggunakan `Content-Disposition: attachment`. | Mengubah menjadi `inline` agar PDF dapat dipreview di browser. |
| Riwayat profil menampilkan warning ID. | Query riwayat belum mengambil kolom `id`. | Menambahkan kolom `id` pada query riwayat. |
| Form Edit Data Karyawan tampil sebagai popup besar dan duplikat. | Form lama dan mekanisme inline edit berjalan bersamaan. | Mempertahankan fungsi edit, menampilkan field secara inline, dan menggunakan popup hanya untuk tambah riwayat. |
| Tombol Simpan/Batal muncul dua kali. | Tombol ditampilkan di toolbar dan card profil. | Menghapus tombol duplikat dari card biru dan mempertahankan toolbar atas. |

## Catatan lanjutan

- PHP CLI belum tersedia di PATH sehingga PHP lint belum dapat dijalankan.
- Pengujian login setiap role dan pengujian isolasi departemen melalui request langsung masih perlu dilakukan.
- Layout PDF perlu dirender dan diverifikasi secara visual di lingkungan PHP aktif.
- Integrasi periode payroll dan pembuatan snapshot slip masih berupa fondasi database.
