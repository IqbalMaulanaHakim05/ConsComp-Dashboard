# Dokumentasi Sistem Dashboard Kelola Karyawan

## Status dokumen

Dokumen ini disusun dari template `ConstComp Dashboard Docs - Google Dokumen.pdf` dan pemeriksaan source code proyek. Bagian yang memerlukan verifikasi melalui database aktif atau pengujian manual diberi label **Perlu verifikasi**.

## BAB 1. PENDAHULUAN

### 1.1 Latar belakang

Sumber daya manusia merupakan salah satu bagian penting dalam keberlangsungan operasional organisasi. Informasi mengenai karyawan, jabatan, departemen, status kepegawaian, pengupahan, izin, lembur, dan penilaian kinerja perlu dikelola secara teratur karena digunakan dalam berbagai proses administrasi dan pengambilan keputusan. Seiring bertambahnya jumlah karyawan dan kompleksitas proses administrasi, kebutuhan terhadap sistem pengelolaan data yang terstruktur menjadi semakin penting.

Pengelolaan data karyawan yang dilakukan secara manual, menggunakan dokumen terpisah, atau disimpan pada beberapa tempat yang tidak terintegrasi dapat menimbulkan berbagai kendala. Kendala tersebut antara lain kesulitan dalam menemukan data, terjadinya duplikasi atau ketidakkonsistenan informasi, keterlambatan pembaruan data, kesalahan dalam pencatatan, serta kesulitan dalam menelusuri riwayat perubahan. Proses pengajuan izin, pencatatan lembur, pengelolaan komponen gaji, dan pembuatan dokumen karyawan juga dapat membutuhkan waktu lebih lama apabila tidak didukung oleh sistem yang terpusat.

Selain kebutuhan terhadap pengelolaan data, setiap pengguna memiliki tanggung jawab dan kewenangan yang berbeda. Administrator membutuhkan akses untuk mengelola data dan konfigurasi, sedangkan pengguna operasional hanya perlu mengakses data sesuai departemen atau tanggung jawabnya. Oleh karena itu, sistem perlu menyediakan mekanisme autentikasi dan otorisasi yang dapat membedakan hak akses pengguna berdasarkan role. Pembatasan tersebut diperlukan untuk menjaga keamanan data, mengurangi risiko perubahan oleh pihak yang tidak berwenang, dan memastikan proses kerja berjalan sesuai struktur organisasi.

Berdasarkan kebutuhan tersebut, dikembangkan Sistem Dashboard Kelola Karyawan sebagai aplikasi web internal untuk membantu proses administrasi sumber daya manusia secara terpusat. Sistem ini menyediakan beberapa modul yang saling berkaitan, yaitu modul dashboard, data karyawan, profil dan dokumen karyawan, master data, pengguna, pengupahan, slip gaji, izin, cuti, lembur, penilaian performa, analisis, notifikasi, pengaturan publik, dan audit aktivitas.

Melalui sistem ini, data karyawan dapat dikelola dalam satu aplikasi sehingga proses pencarian, pembaruan, dan pemanfaatan data menjadi lebih terorganisasi. Informasi pengupahan dapat disusun berdasarkan profil gaji, komponen gaji, pendapatan tambahan, dan potongan. Proses izin dan cuti dapat mengikuti tahapan persetujuan yang tersedia, sedangkan aktivitas penting pengguna dapat dicatat sebagai bahan penelusuran dan pengawasan.

Sistem juga menerapkan pembagian hak akses berdasarkan role pengguna, seperti superadmin, admin, PIC, koordinator, manager, direktur, dan viewer. Untuk role operasional tertentu, akses data dapat dibatasi berdasarkan departemen pengguna. Penerapan pembagian hak akses ini diharapkan dapat membantu organisasi menjaga kerahasiaan informasi, meningkatkan akuntabilitas, dan memberikan pengalaman penggunaan yang sesuai dengan tugas masing-masing pengguna.

Dengan adanya Sistem Dashboard Kelola Karyawan, proses administrasi yang sebelumnya tersebar dapat dilakukan melalui alur kerja yang lebih terarah. Sistem ini diharapkan dapat membantu meningkatkan efisiensi pengelolaan data, mengurangi kesalahan pencatatan, mempercepat penyediaan informasi, serta menyediakan dasar dokumentasi yang dapat digunakan dalam pemeliharaan dan pengembangan sistem pada tahap berikutnya.

### 1.2 Tujuan

Dokumentasi ini bertujuan untuk:

1. menjelaskan gambaran umum dan ruang lingkup Sistem Dashboard Kelola Karyawan;
2. mendokumentasikan fungsi utama dan kebutuhan sistem;
3. menjelaskan arsitektur aplikasi, pengelolaan data, dan relasi antarkomponen;
4. mendokumentasikan role pengguna beserta hak aksesnya;
5. menyediakan panduan penggunaan sistem untuk setiap role;
6. menjadi acuan dalam proses pengujian, pemeliharaan, dan pengembangan sistem berikutnya; dan
7. membantu pengembang memahami struktur source code serta fungsi-fungsi penting di dalam aplikasi.

### 1.3 Ruang lingkup

Ruang lingkup sistem yang dibahas dalam dokumentasi ini meliputi:

- halaman publik dan informasi karyawan yang tersedia untuk umum;
- autentikasi pengguna, session, logout, dan pembatasan akses;
- dashboard internal;
- pengelolaan master data dan data karyawan;
- pengelolaan profil, media, CV, promosi, dan riwayat karyawan;
- pengelolaan upah, komponen gaji, pendapatan tambahan, potongan, dan slip gaji;
- pengelolaan izin, cuti, lembur, dan alur persetujuan;
- penilaian serta analisis performa karyawan;
- pengelolaan pengguna dan role;
- notifikasi dan audit aktivitas;
- ekspor data dan pembuatan dokumen pendukung; serta
- struktur source code dan rencana pengujian sistem.

Dokumentasi ini tidak mencakup perubahan kebijakan perusahaan, konfigurasi infrastruktur produksi, isi kredensial, maupun data pribadi nyata. Detail skema database dan beberapa aturan bisnis yang tidak tertulis di source code perlu dikonfirmasi melalui database aktif atau pemilik sistem.

## 2. Deskripsi sistem

### 2.1 Definisi dan singkatan

| Istilah | Keterangan |
|---|---|
| HRGA | Human Resources and General Affairs; label untuk role admin HRGA. |
| PIC | Person in Charge; role operasional dengan cakupan departemen. |
| NIK | Nomor identitas karyawan. |
| Role | Peran pengguna yang menentukan otorisasi. |
| Departemen | Unit organisasi yang membatasi cakupan data operasional. |

### 2.2 Arsitektur umum

Aplikasi menggunakan PHP server-side dengan MySQL/MariaDB melalui ekstensi `mysqli`. Halaman dashboard berada di folder `Dashboard/`, fungsi bersama di `Dashboard/fungsi/`, partial tampilan di `Dashboard/partials/`, dan stylesheet di `style/`. Composer digunakan untuk dependensi PHP, termasuk Dompdf yang terdeteksi pada `vendor/`.

### 2.3 Fungsi utama

| Kode | Fungsi |
|---|---|
| FN-01 | Login, validasi akun aktif, pembentukan session, dan logout. |
| FN-02 | Pembatasan akses berdasarkan role. |
| FN-03 | Pembatasan data karyawan berdasarkan departemen untuk role operasional. |
| FN-04 | Pengelolaan data karyawan, profil, media, CV, promosi, dan riwayat. |
| FN-05 | Pengelolaan profil gaji, komponen gaji, pendapatan tambahan, dan potongan. |
| FN-06 | Pembuatan dan pengelolaan slip gaji, termasuk batch slip. |
| FN-07 | Pengajuan/pengelolaan izin dan cuti dengan tahapan persetujuan. |
| FN-08 | Pengelolaan lembur dan ekspor data terkait. |
| FN-09 | Penilaian dan analisis performa karyawan. |
| FN-10 | Pengelolaan pengguna dan role oleh superadmin. |
| FN-11 | Pencatatan audit aktivitas pengguna. |

## 3. Hak akses pengguna

Role yang terdeteksi di source code adalah `superadmin`, `admin`, `pic`, `koordinator`, `manager`, `direktur`, dan `viewer`.

| Role | Hak akses yang teridentifikasi |
|---|---|
| Superadmin | Mengelola pengguna, melihat audit, dan mengakses fungsi administratif tingkat tertinggi. |
| Admin HRGA | Mengelola operasional SDM seperti karyawan, upah, izin, lembur, dan performa sesuai pembatasan halaman. |
| PIC | Mengakses fungsi operasional terbatas, terutama lembur, izin, dan halaman pendukungnya; dibatasi departemen. |
| Koordinator | Mengakses fungsi operasional sesuai departemen dan tahapan persetujuan yang tersedia. |
| Manager | Mengakses fungsi operasional sesuai departemen dan tahapan persetujuan manager. |
| Direktur | Dipetakan ke cakupan efektif `manager` pada fungsi otorisasi tertentu. Perlu verifikasi apakah ini sesuai kebijakan bisnis. |
| Viewer | Role tersedia pada pengelolaan pengguna; halaman yang boleh diakses perlu verifikasi manual. |

## 4. Pengelolaan data

Tabel database yang dirujuk oleh source code antara lain `users`, `karyawan`, `master_departemen`, `profil_gaji`, `jenis_komponen_gaji`, `komponen_gaji_karyawan`, `pendapatan_tambahan_karyawan`, `potongan_karyawan`, `slip_gaji`, `audit_aktivitas`, serta tabel izin dan lembur. Struktur kolom lengkap dan foreign key harus dikonfirmasi dari skema database aktif karena file dump SQL tidak ditemukan di repository.

## 5. Struktur source code

| Lokasi | Peran |
|---|---|
| `index.php` | Halaman publik dan pencarian/filter data publik. |
| `Dashboard/login.php` | Halaman login. |
| `Dashboard/dashboard.php` | Dashboard internal. |
| `Dashboard/fungsi/auth.php` | Session, login, role, cakupan departemen, dan guard akses. |
| `Dashboard/fungsi/audit.php` | Persiapan dan pencatatan audit aktivitas. |
| `Dashboard/fungsi/alur-persetujuan-izin.php` | Tahapan persetujuan izin. |
| `Dashboard/partials/` | Komponen tampilan yang digunakan bersama. |
| `style/` | CSS halaman publik dan dashboard. |

## 6. Keamanan dan keandalan

- Query penting menggunakan prepared statement.
- Password diproses melalui mekanisme hash/verifikasi PHP.
- Session ID diregenerasi setelah login untuk mengurangi risiko session fixation.
- Akses halaman dilindungi fungsi `wajibRole()` dan pemeriksaan session.
- Data operasional dapat dibatasi berdasarkan `department_id`.
- Output HTML menggunakan escaping pada banyak titik tampilan.
- Audit aktivitas digunakan untuk mencatat tindakan pengguna.

Perlu verifikasi: konfigurasi HTTPS, CSRF protection, backup database, logging error produksi, rate limiting login, dan kebijakan retensi data.

## 7. Panduan singkat

1. Buka halaman login melalui `Dashboard/login.php`.
2. Masuk menggunakan akun aktif.
3. Pilih modul dari dashboard sesuai role.
4. Untuk data operasional, pastikan departemen pengguna telah diatur.
5. Gunakan logout setelah selesai.

Alur detail per role dan screenshot UI masih memerlukan pengujian manual pada aplikasi yang terhubung ke database.

## 8. Rencana pengujian

| Test ID | Skenario | Hasil yang diharapkan |
|---|---|---|
| TST-01 | Login dengan kredensial benar | Pengguna masuk ke dashboard. |
| TST-02 | Login dengan akun tidak aktif/salah | Login ditolak dan pesan ditampilkan. |
| TST-03 | Membuka halaman tanpa role sesuai | Akses ditolak. |
| TST-04 | Role operasional membuka data departemen lain | Data di luar cakupan tidak dapat diakses. |
| TST-05 | Superadmin mengubah pengguna | Perubahan tersimpan dan tercatat pada audit. |
| TST-06 | Menyimpan profil gaji | Data gaji dan komponennya tersimpan konsisten. |
| TST-07 | Menjalankan alur persetujuan izin | Tahap izin berubah sesuai role dan status. |
| TST-08 | Logout | Session dihentikan dan halaman internal tidak dapat dibuka kembali. |

## 9. Keterbatasan dan tindak lanjut

- Tidak ada dump skema database pada repository, sehingga relasi dan tipe kolom belum dapat didokumentasikan secara definitif.
- PDF sumber masih berupa template dengan placeholder.
- Pengujian browser dan screenshot tiap halaman belum dilakukan.
- Hak akses `viewer` dan pemetaan detail `direktur` perlu konfirmasi pemilik sistem.
