# Laporan Harian — 11 Agustus 2026

## Aktivitas

| Waktu | Aktivitas | Referensi |
| --- | --- | --- |
| Awal sesi | Memperbaiki perhitungan upah lembur otomatis dengan tarif per jam berdasarkan rumus `gaji pokok / 173`, menghapus pilihan nominal, dan mengunci hasil perhitungan. | `Dashboard/lembur.php` |
| Awal sesi | Menambahkan aksi pembatalan laporan lembur untuk PIC dan merapikan posisi tombol Kirim/Batalkan. | `Dashboard/lembur.php` |
| Awal sesi | Menyesuaikan aksi approval Koordinator dan Manajer agar hanya menampilkan Setuju dan Tolak. | `Dashboard/lembur.php` |
| Awal sesi | Memperbaiki penyimpanan upah lembur agar status otomatis berubah menjadi `selesai` setelah kompensasi tersimpan. | `Dashboard/lembur.php` |
| Sesi 2 | Menambahkan filter bulan dan tahun pada halaman Upah berdasarkan kolom `berlaku_mulai`. | `Dashboard/upah.php` |
| Sesi 2 | Membatasi Audit Aktivitas menjadi 50 baris per halaman serta menambahkan navigasi Sebelumnya/Berikutnya. | `Dashboard/audit-aktivitas.php` |
| Sesi 2 | Mengembalikan grafik Karyawan per Departemen untuk Admin/Superadmin dan mempertahankan grafik per Posisi untuk role operasional. | `Dashboard/analisis.php` |
| Sesi 2 | Menata grafik Jenis Kelamin dan Karyawan per Departemen berdampingan serta mengubah grafik departemen menjadi batang vertikal. | `Dashboard/analisis.php`, `style/admin-dashboard.css` |
| Sesi 2 | Merapikan grid kartu dan grafik Analisis agar tidak menyisakan ruang kosong. | `style/admin-dashboard.css`, `Dashboard/partials/atas.php` |
| Sesi 3 | Menambahkan pengaturan warna terpisah untuk grafik status, tren, posisi, departemen, gaji, dan performa pada Personalisasi Tampilan. | `Dashboard/pengaturan-publik.php`, `Dashboard/fungsi/pengaturan-publik.php`, `Dashboard/analisis.php` |
| Sesi 3 | Memberikan Admin dan Superadmin kemampuan menambah catatan lembur dan melakukan persetujuan/penolakan. | `Dashboard/lembur.php` |
| Sesi 3 | Menambahkan pengaturan pemilihan kolom tabel publik dan kartu statistik Dashboard. | `Dashboard/pengaturan-publik.php`, `Dashboard/fungsi/pengaturan-publik.php`, `Dashboard/dashboard.php`, `index.php` |
| Sesi 4 | Mengubah bagian Biodata & Riwayat pada halaman Tambah Karyawan menjadi form riwayat dinamis dengan tombol tambah dan hapus. | `Dashboard/fungsi/tambah.php`, `style/admin-form.css` |
| Sesi 4 | Menyimpan banyak riwayat pendidikan dan pekerjaan ke tabel khusus menggunakan transaksi database. | `Dashboard/fungsi/tambah.php`, tabel `riwayat_pendidikan`, tabel `riwayat_pekerjaan` |
| Sesi 4 | Menambahkan tombol Hapus laporan lembur untuk Admin dan Superadmin beserta konfirmasi, CSRF, audit, dan penghapusan relasi terkait. | `Dashboard/lembur.php` |
| Sesi 4 | Merapikan UI card halaman Lembur untuk PIC, Admin, dan Superadmin. | `style/admin-overtime.css`, `Dashboard/partials/atas.php` |
| Sesi 5 | Menata posisi Riwayat Pekerjaan dan Riwayat Pendidikan pada Profil Karyawan menjadi judul di kiri dan detail bertumpuk di kanan. | `Dashboard/profil-karyawan.php`, `style/admin-profile.css` |
| Sesi 5 | Memperbaiki layout riwayat yang sempat terpecah menjadi tiga kolom akibat konflik aturan CSS umum. | `style/admin-profile.css` |
| Sesi 5 | Memperbarui Export PDF/CV agar membaca riwayat terbaru dari tabel pendidikan dan pekerjaan. | `Dashboard/fungsi/generate-cv.php`, `Dashboard/fungsi/cv-generator.php`, `Dashboard/template-cv.php` |
| Akhir sesi | Mengubah popup Tambah Pendidikan dan Tambah Pekerjaan menjadi form card responsif dengan label serta tombol Batal/Simpan yang rapi. | `Dashboard/profil-karyawan.php`, `style/admin-profile.css` |

## Hasil

- Alur lembur lebih lengkap untuk PIC, Koordinator, Manajer, Admin, dan Superadmin.
- Perhitungan upah lembur berjalan otomatis berdasarkan tarif per jam dan status laporan selesai setelah upah disimpan.
- Halaman Upah memiliki filter periode berdasarkan tanggal berlaku profil gaji.
- Audit Aktivitas memiliki pagination 50 baris per halaman.
- Grafik Analisis tampil sesuai role, lebih rapi, dan warnanya dapat dipersonalisasi.
- Superadmin dapat memilih kolom tabel publik dan kartu yang tampil pada Dashboard.
- Form Tambah Karyawan mendukung banyak riwayat pendidikan dan pekerjaan.
- Profil Karyawan menampilkan riwayat dalam susunan yang lebih terstruktur.
- Export PDF/CV sudah menggunakan data riwayat terbaru.
- Popup penambahan riwayat sudah responsif dan konsisten dengan desain aplikasi.
- Pemeriksaan sintaks PHP dilakukan pada file-file yang diubah dan tidak ditemukan kesalahan sintaks.

## Kendala dan solusi

| Kendala | Penyebab | Solusi/tindak lanjut |
| --- | --- | --- |
| Tombol Simpan Upah terlihat tidak bekerja. | Kompensasi sudah tersimpan, tetapi status laporan masih `disetujui` sehingga form terus muncul. | Mengubah status laporan menjadi `selesai` setelah kompensasi berhasil disimpan. |
| Tombol Batalkan ikut muncul pada Koordinator dan Manajer. | Tombol ditambahkan melalui JavaScript tanpa pembatasan tampilan role. | Menghapus tombol tersebut untuk role approval dan membatasi aksi pembatalan pada PIC. |
| Grafik Karyawan per Departemen hilang untuk Admin/Superadmin. | Grafik sebelumnya diganti global menjadi grafik per Posisi. | Membuat tampilan kondisional berdasarkan role. |
| Layout Analisis menyisakan ruang kosong. | Jumlah kartu tidak sesuai jumlah kolom dan stylesheet lama masih tersimpan di cache browser. | Mengubah grid menjadi tiga kartu per baris, membuat pasangan grafik khusus, dan memperbarui versi stylesheet. |
| Riwayat Profil terpecah menjadi nama, posisi, dan tanggal pada kolom berbeda. | Selector umum `.profile-details div` ikut memengaruhi elemen di dalam item riwayat. | Menambahkan aturan CSS khusus agar setiap item riwayat menjadi satu blok vertikal. |
| Pendidikan dan pekerjaan tidak muncul pada PDF/CV. | Generator masih membaca kolom riwayat lama di tabel `karyawan`. | Mengambil data dari tabel `riwayat_pendidikan` dan `riwayat_pekerjaan`, kemudian meneruskannya ke template PDF. |
| Form lama hanya mendukung satu riwayat. | Riwayat disimpan pada textarea dan tanggal tunggal. | Menggunakan baris form dinamis dan penyimpanan transaksi ke tabel riwayat khusus. |
| Perubahan CSS kadang tidak langsung terlihat. | Browser menggunakan stylesheet yang masih tersimpan di cache. | Memperbarui parameter versi stylesheet pada layout utama. |

> Catatan waktu: percakapan tidak menyertakan timestamp pasti untuk setiap pekerjaan. Urutan waktu di atas menggunakan tahapan sesi secara kronologis.
