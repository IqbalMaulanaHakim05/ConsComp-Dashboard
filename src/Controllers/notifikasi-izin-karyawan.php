<?php
declare(strict_types=1);
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Audit/audit.php';
require_once __DIR__ . '/../Services/Leave/izin-karyawan.php';
wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
siapkanAudit($conn);

$judulHalaman = "Notifikasi Izin Karyawan";
$subjudulHalaman = "Persetujuan izin meninggalkan pekerjaan dan perubahan data terbaru.";
$halamanAktif = "izin-karyawan";
$pengajuan = mysqli_query($conn, "SELECT imp.id, imp.deskripsi, imp.status, imp.tahap_persetujuan, imp.catatan_persetujuan, imp.created_at, k.emp_id, k.employee_name, u.nama AS nama_pemroses FROM izin_meninggalkan_pekerjaan imp INNER JOIN karyawan k ON k.id = imp.karyawan_id LEFT JOIN users u ON u.id = imp.diproses_oleh_user_id ORDER BY imp.created_at DESC LIMIT 100");
$aktivitas = mysqli_query($conn, "SELECT a.dibuat_pada, a.aktivitas, u.username FROM audit_aktivitas a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 100");
require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Notifikasi Izin Karyawan</h2><p>Pengajuan izin meninggalkan pekerjaan beserta status keputusan.</p></div><div class="notification-list"><?php if ($pengajuan): while ($row = mysqli_fetch_assoc($pengajuan)): ?><article class="notification-item"><strong>Izin ID <?= (int) $row["id"]; ?> — <?= htmlspecialchars($row["emp_id"] . " - " . $row["employee_name"]); ?></strong><span>Status: <?= htmlspecialchars(labelStatusPersetujuanIzin((string) $row["status"], (string) $row["tahap_persetujuan"])); ?></span><span><b>Alasan:</b> <?= nl2br(htmlspecialchars(trim((string) ($row["deskripsi"] ?? "")) ?: "-")); ?></span><span><b>Keputusan:</b> <?= htmlspecialchars(trim((string) ($row["nama_pemroses"] ?? "")) !== "" ? $row["nama_pemroses"] . (trim((string) ($row["catatan_persetujuan"] ?? "")) !== "" ? " — " . $row["catatan_persetujuan"] : "") : "Belum ada keputusan"); ?></span><small><?= htmlspecialchars($row["created_at"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada notifikasi izin karyawan.</p><?php endif; ?></div></section>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Perubahan Data</h2><p>Aktivitas perubahan data terbaru di sistem.</p></div><div class="notification-list"><?php if ($aktivitas): while ($row = mysqli_fetch_assoc($aktivitas)): ?><article class="notification-item"><strong><?= htmlspecialchars($row["username"] ?: "Sistem"); ?></strong><span><?= htmlspecialchars(labelAktivitas((string) $row["aktivitas"])); ?></span><small><?= htmlspecialchars($row["dibuat_pada"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada aktivitas.</p><?php endif; ?></div></section>
<style>.notification-page-card + .notification-page-card { display: none; }</style>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
