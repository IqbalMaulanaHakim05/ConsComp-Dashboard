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
$approval = mysqli_query($conn, "SELECT o.id, o.deskripsi, o.status, o.created_at, k.emp_id, k.employee_name, pemroses.nama AS nama_pemroses, pemroses.role AS role_pemroses, keputusan.catatan AS catatan_persetujuan FROM overtime_reports o INNER JOIN karyawan k ON k.id = o.karyawan_id LEFT JOIN overtime_approvals keputusan ON keputusan.id = (SELECT oa.id FROM overtime_approvals oa WHERE oa.overtime_id = o.id AND oa.status IN ('approved', 'rejected') ORDER BY oa.decided_at DESC, oa.id DESC LIMIT 1) LEFT JOIN users pemroses ON pemroses.id = keputusan.approver_user_id ORDER BY o.created_at DESC LIMIT 100");
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Notifikasi Lembur</h2><p>Pengajuan lembur beserta status keputusan.</p></div><div class="notification-list"><?php if ($approval): while ($row = mysqli_fetch_assoc($approval)): ?><article class="notification-item"><strong>Lembur ID <?= (int) $row["id"]; ?> — <?= htmlspecialchars($row["emp_id"] . " - " . $row["employee_name"]); ?></strong><span>Status: <?= htmlspecialchars((string) ($row["status"] === "disetujui" ? "Disetujui " . labelRole((string) ($row["role_pemroses"] ?? "manager")) : ($row["status"] === "ditolak" ? "Ditolak" : ($row["status"] === "menunggu_koordinator" ? "Menunggu Koordinator" : ($row["status"] === "menunggu_manager" ? "Menunggu Manager" : "Draft"))))); ?></span><span><b>Alasan:</b> <?= nl2br(htmlspecialchars(trim((string) ($row["deskripsi"] ?? "")) ?: "-")); ?></span><span><b>Keputusan:</b> <?= htmlspecialchars(trim((string) ($row["nama_pemroses"] ?? "")) !== "" ? $row["nama_pemroses"] . (trim((string) ($row["catatan_persetujuan"] ?? "")) !== "" ? " — " . $row["catatan_persetujuan"] : "") : "Belum ada keputusan"); ?></span><small><?= htmlspecialchars($row["created_at"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada notifikasi lembur.</p><?php endif; ?></div></section>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Perubahan Data</h2><p>Aktivitas perubahan data terbaru di sistem.</p></div><div class="notification-list"><?php if ($aktivitas): while ($row = mysqli_fetch_assoc($aktivitas)): ?><article class="notification-item"><strong><?= htmlspecialchars($row["username"] ?: "Sistem"); ?></strong><span><?= htmlspecialchars(labelAktivitas((string) $row["aktivitas"])); ?></span><small><?= htmlspecialchars($row["dibuat_pada"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada aktivitas.</p><?php endif; ?></div></section>
<style>.notification-page-card + .notification-page-card { display: none; }</style>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
