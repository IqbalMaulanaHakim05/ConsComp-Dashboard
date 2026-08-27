<?php
declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Leave/alur-persetujuan-izin.php';
require_once __DIR__ . '/../Services/Leave/cuti-khusus.php';

wajibRole('pic', 'koordinator', 'manager', 'admin', 'superadmin');
if (!siapkanTabelIzinCutiKhusus($conn)) exit('Tabel cuti khusus tidak dapat disiapkan.');

$where = roleOperasional() ? 'c.department_id = ' . (int) departmentIdPengguna() : '1=1';
$data = mysqli_query(
    $conn,
    "SELECT c.id, c.deskripsi, c.status, c.tahap_persetujuan, c.catatan_persetujuan, c.created_at,
            k.emp_id, k.employee_name, pemroses.nama AS nama_pemroses, pemroses.role AS role_pemroses
     FROM izin_cuti_khusus c
     INNER JOIN karyawan k ON k.id = c.karyawan_id
     LEFT JOIN users pemroses ON pemroses.id = c.diproses_oleh_user_id
     WHERE {$where}
     ORDER BY c.created_at DESC
     LIMIT 100"
);

$judulHalaman = 'Notifikasi Cuti Khusus';
$subjudulHalaman = 'Pengajuan cuti khusus beserta status keputusan terbaru.';
$halamanAktif = 'izin-cuti-khusus';
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="data-card notification-page-card">
    <div class="data-card-header">
        <h2>Notifikasi Cuti Khusus</h2>
        <p>Pengajuan cuti khusus beserta status keputusan.</p>
    </div>
    <div class="notification-list">
        <?php if ($data && mysqli_num_rows($data) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($data)): ?>
                <article class="notification-item">
                    <strong>Cuti Khusus ID <?= (int) $row['id']; ?> — <?= htmlspecialchars($row['emp_id'] . ' - ' . $row['employee_name']); ?></strong>
                    <span>Status: <?= htmlspecialchars(labelStatusPersetujuanIzin((string) $row['status'], (string) $row['tahap_persetujuan'], (string) ($row['role_pemroses'] ?? ''))); ?></span>
                    <span><b>Deskripsi:</b> <?= nl2br(htmlspecialchars(trim((string) $row['deskripsi']) ?: '-')); ?></span>
                    <span><b>Keputusan:</b> <?= htmlspecialchars(trim((string) ($row['nama_pemroses'] ?? '')) !== '' ? $row['nama_pemroses'] . (trim((string) ($row['catatan_persetujuan'] ?? '')) !== '' ? ' — ' . $row['catatan_persetujuan'] : '') : 'Belum ada keputusan'); ?></span>
                    <small><?= htmlspecialchars($row['created_at']); ?></small>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="notification-empty">Belum ada notifikasi cuti khusus.</p>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
