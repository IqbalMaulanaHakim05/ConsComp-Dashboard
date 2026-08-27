<?php
declare(strict_types=1);
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
siapkanAudit($conn);

$judulHalaman = "Notifikasi";
$subjudulHalaman = "Persetujuan lembur dan perubahan data terbaru.";
$halamanAktif = "lembur";
$aktivitas = mysqli_query($conn, "SELECT a.dibuat_pada, a.aktivitas, u.username FROM audit_aktivitas a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 100");
$approval = mysqli_query($conn, "SELECT o.id, o.deskripsi, o.status, o.created_at, k.emp_id, k.employee_name, GROUP_CONCAT(CONCAT(UPPER(a.tahap), ': ', IF(a.status = 'approved', 'disetujui', IF(a.status = 'rejected', 'ditolak', 'menunggu')), IF(TRIM(COALESCE(a.catatan, '')) <> '', CONCAT(' — ', a.catatan), '')) ORDER BY a.tahap SEPARATOR '||') AS detail_approval FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN overtime_approvals a ON a.overtime_id = o.id GROUP BY o.id ORDER BY o.created_at DESC LIMIT 100");
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Notifikasi Lembur</h2><p>Alasan pengajuan serta keputusan Koordinator dan Manager.</p></div><div class="notification-list"><?php if ($approval): while ($row = mysqli_fetch_assoc($approval)): ?><article class="notification-item"><strong>Lembur ID <?= (int) $row["id"]; ?> — <?= htmlspecialchars($row["emp_id"] . " - " . $row["employee_name"]); ?></strong><span>Status: <?= htmlspecialchars($row["status"]); ?></span><span><b>Alasan pengajuan:</b> <?= nl2br(htmlspecialchars(trim((string) ($row["deskripsi"] ?? "")) ?: "-")); ?></span><span><b>Approval:</b> <?= nl2br(htmlspecialchars(str_replace("||", "\n", trim((string) ($row["detail_approval"] ?? "")) ?: "Belum ada keputusan"))); ?></span><small><?= htmlspecialchars($row["created_at"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada notifikasi lembur.</p><?php endif; ?></div></section>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Perubahan Data</h2><p>Aktivitas perubahan data terbaru di sistem.</p></div><div class="notification-list"><?php if ($aktivitas): while ($row = mysqli_fetch_assoc($aktivitas)): ?><article class="notification-item"><strong><?= htmlspecialchars($row["username"] ?: "Sistem"); ?></strong><span><?= htmlspecialchars(labelAktivitas((string) $row["aktivitas"])); ?></span><small><?= htmlspecialchars($row["dibuat_pada"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada aktivitas.</p><?php endif; ?></div></section>
<style>.notification-page-card + .notification-page-card { display: none; }</style>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
