<?php
declare(strict_types=1);
require __DIR__ . '/../../config/database.php'; require_once __DIR__ . '/../Services/Auth/auth.php'; require_once __DIR__ . '/../Services/Leave/alur-persetujuan-izin.php';
wajibRole('pic', 'koordinator', 'manager', 'admin', 'superadmin');
$judulHalaman = 'Notifikasi Sakit'; $subjudulHalaman = 'Persetujuan izin sakit dan perubahan data terbaru.'; $halamanAktif = 'izin-sakit';
$where = roleOperasional() ? 's.department_id = ' . (int) departmentIdPengguna() : '1=1';
$data = mysqli_query($conn, "SELECT s.id, s.deskripsi, s.status, s.tahap_persetujuan, s.created_at, k.emp_id, k.employee_name FROM izin_sakit s INNER JOIN karyawan k ON k.id = s.karyawan_id WHERE {$where} ORDER BY s.created_at DESC LIMIT 100");
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Notifikasi Sakit</h2><p>Pengajuan izin sakit beserta status keputusan.</p></div><div class="notification-list"><?php if ($data && mysqli_num_rows($data) > 0): while ($row = mysqli_fetch_assoc($data)): ?><article class="notification-item"><strong>Izin Sakit ID <?= (int) $row['id']; ?> — <?= htmlspecialchars($row['emp_id'] . ' - ' . $row['employee_name']); ?></strong><span>Status: <?= htmlspecialchars(labelStatusPersetujuanIzin((string) $row['status'], (string) $row['tahap_persetujuan'])); ?></span><span><b>Keterangan:</b> <?= nl2br(htmlspecialchars($row['deskripsi'])); ?></span><small><?= htmlspecialchars($row['created_at']); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada notifikasi sakit.</p><?php endif; ?></div></section>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
