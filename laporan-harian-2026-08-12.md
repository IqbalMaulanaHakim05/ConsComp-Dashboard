# Laporan Harian Pengembangan Sistem Karyawan

## Aktivitas

| Waktu | Aktivitas | Referensi |
| --- | --- | --- |
| [HH:mm] | Menyesuaikan struktur sidebar, termasuk Data Karyawan, Analisis, Upah, dan Lembur sesuai kebutuhan role. | `Dashboard/partials/atas.php` |
| [HH:mm] | Memperbaiki tabel karyawan agar kolom Performa tidak menutupi kolom lain pada Dashboard dan halaman Data Karyawan. | `Dashboard/partials/tabel.php`, `style/admin-base.css`, `style/admin-dashboard.css` |
| [HH:mm] | Membatasi akses role PIC dan Koordinator sesuai halaman yang diperbolehkan. | `Dashboard/partials/atas.php` |
| [HH:mm] | Memperbaiki tampilan dan fungsi halaman Upah, termasuk gaji pokok, komponen pendapatan, potongan, slip PDF, serta filter periode. | `Dashboard/upah.php`, `Dashboard/edit-upah.php` |
| [HH:mm] | Menambahkan preview export Excel untuk data Upah dan Lembur sebelum proses download. | `Dashboard/fungsi/export_upah_excel.php`, `Dashboard/fungsi/export_lembur.php` |
| [HH:mm] | Membatasi export Excel Manager berdasarkan departemennya dan mempertahankan akses export Admin/Superadmin. | `Dashboard/fungsi/export_excel.php`, `Dashboard/karyawan.php` |
| [HH:mm] | Memperbaiki alur lembur, approval Koordinator/Manager, perhitungan upah lembur 1/173, tombol aksi, serta tampilan kolom Upah dan Aksi. | `Dashboard/lembur.php`, `style/admin-overtime.css` |
| [HH:mm] | Menambahkan halaman Notifikasi untuk menampilkan status lembur, alasan pengajuan, serta catatan persetujuan/penolakan. | `Dashboard/notifikasi.php` |
| [HH:mm] | Menghapus card notifikasi dan perubahan data dari halaman utama Lembur; notifikasi diarahkan ke halaman khusus. | `Dashboard/lembur.php`, `Dashboard/notifikasi.php` |
| [HH:mm] | Memperbaiki tampilan deskripsi riwayat pekerjaan pada profil karyawan agar tampil seperti pada export PDF. | `Dashboard/profil-karyawan.php` |
| [HH:mm] | Memperbaiki error query MySQL akibat `ONLY_FULL_GROUP_BY` dan nama kolom waktu audit yang sesuai struktur database. | `Dashboard/lembur.php`, `Dashboard/fungsi/audit.php` |
| [HH:mm] | Membersihkan output teks `?>` yang tampil pada halaman preview export Upah. | `Dashboard/fungsi/export_upah_excel.php` |

## Hasil

- Tabel karyawan lebih rapi dan kolom Performa tidak lagi menutupi kolom lainnya.
- Hak akses PIC dan Koordinator lebih sesuai dengan kebutuhan operasional.
- Halaman Upah mendukung filter, preview export Excel, dan export berdasarkan departemen.
- Halaman Lembur mendukung approval bertahap, perhitungan upah lembur, notifikasi, dan preview export.
- Notifikasi lembur menampilkan alasan pengajuan serta catatan approval atau penolakan.
- Profil karyawan menampilkan deskripsi riwayat pekerjaan.
- Error query SQL dan output `?>` pada preview export telah diperbaiki.
- File PHP yang diperbarui telah diperiksa menggunakan validasi sintaks PHP.

## Kendala dan solusi

| Kendala | Penyebab | Solusi/tindak lanjut |
| --- | --- | --- |
| Kolom Performa menutupi kolom lain. | Aturan CSS global menjadikan kolom terakhir sebagai sticky seperti kolom Aksi. | Menonaktifkan sticky pada tabel tanpa kolom Aksi dan menetapkan lebar minimum kolom. |
| Koordinator dan PIC melihat menu yang tidak sesuai. | Pembatasan role belum diterapkan secara konsisten pada sidebar dan akses URL. | Menambahkan pembatasan halaman berdasarkan role pada layout utama. |
| Query lembur menghasilkan error `ONLY_FULL_GROUP_BY`. | Query menggunakan `SELECT o.*` bersama `GROUP BY o.id`. | Menyesuaikan query dan mode SQL sesi agar kompatibel dengan konfigurasi MySQL. |
| Notifikasi gagal dimuat karena kolom `created_at` tidak tersedia. | Struktur tabel audit menggunakan kolom `dibuat_pada`. | Mengubah query notifikasi menggunakan `dibuat_pada` dan pengurutan berdasarkan `id`. |
| Export Excel Manager belum tersedia pada halaman Data Karyawan. | Action bar hanya dipanggil untuk Admin dan Superadmin. | Menambahkan Manager ke action bar dan membatasi hasil export berdasarkan departemen. |
| Filter pada preview export tidak sesuai kebutuhan. | Filter departemen sempat ditambahkan pada halaman preview. | Menghapus filter dari halaman preview dan mempertahankan filter dari halaman Upah. |
| Teks `?>` tampil pada preview export Upah. | Terdapat tag PHP penutup ganda pada template. | Menghapus tag penutup yang berlebih dan memvalidasi ulang sintaks PHP. |

