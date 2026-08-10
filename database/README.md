# Database migrations

Migrasi database disimpan di `database/migrations` dan dijalankan berdasarkan urutan nama file.

`001_create_schema_migrations.sql` membuat tabel kontrol migrasi secara idempotent. Setiap migrasi berikutnya wajib memiliki versi unik dan dicatat pada `schema_migrations` setelah berhasil dijalankan.

Backup database sebelum migrasi disimpan di `storage/backups` dan tidak boleh dihapus sebelum migrasi tervalidasi.

Jalankan migrasi dari root proyek dengan:

```bash
php database/migrate.php
```

Runner bersifat idempoten: versi yang sudah tercatat akan dilewati.
