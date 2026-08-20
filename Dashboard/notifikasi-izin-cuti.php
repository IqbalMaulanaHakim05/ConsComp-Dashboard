<?php
declare(strict_types=1);
require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";
require_once __DIR__ . "/fungsi/izin-cuti.php";
wajibRole("pic", "koordinator", "manager", "admin", "superadmin");
siapkanAudit($conn);

$judulHalaman = "Notifikasi Izin Cuti";
$subjudulHalaman = "Persetujuan izin cuti dan perubahan data terbaru.";
$halamanAktif = "izin-cuti";
$pengajuan = mysqli_query($conn, "SELECT ic.id, ic.deskripsi, ic.status, ic.tahap_persetujuan, ic.catatan_persetujuan, ic.created_at, k.emp_id, k.employee_name, u.nama AS nama_pemroses FROM izin_cuti ic INNER JOIN karyawan k ON k.id = ic.karyawan_id LEFT JOIN users u ON u.id = ic.diproses_oleh_user_id ORDER BY ic.created_at DESC LIMIT 100");
$aktivitas = mysqli_query($conn, "SELECT a.dibuat_pada, a.aktivitas, u.username FROM audit_aktivitas a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 100");
require __DIR__ . "/partials/atas.php";
?>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Notifikasi Izin Cuti</h2><p>Pengajuan izin cuti beserta status keputusan.</p></div><div class="notification-list"><?php if ($pengajuan): while ($row = mysqli_fetch_assoc($pengajuan)): ?><article class="notification-item"><strong>Izin Cuti ID <?= (int) $row["id"]; ?> — <?= htmlspecialchars($row["emp_id"] . " - " . $row["employee_name"]); ?></strong><span>Status: <?= htmlspecialchars(labelStatusPersetujuanIzin((string) $row["status"], (string) $row["tahap_persetujuan"])); ?></span><span><b>Alasan:</b> <?= nl2br(htmlspecialchars(trim((string) ($row["deskripsi"] ?? "")) ?: "-")); ?></span><span><b>Keputusan:</b> <?= htmlspecialchars(trim((string) ($row["nama_pemroses"] ?? "")) !== "" ? $row["nama_pemroses"] . (trim((string) ($row["catatan_persetujuan"] ?? "")) !== "" ? " — " . $row["catatan_persetujuan"] : "") : "Belum ada keputusan"); ?></span><small><?= htmlspecialchars($row["created_at"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada notifikasi izin cuti.</p><?php endif; ?></div></section>
<section class="data-card notification-page-card"><div class="data-card-header"><h2>Perubahan Data</h2><p>Aktivitas perubahan data terbaru di sistem.</p></div><div class="notification-list"><?php if ($aktivitas): while ($row = mysqli_fetch_assoc($aktivitas)): ?><article class="notification-item"><strong><?= htmlspecialchars($row["username"] ?: "Sistem"); ?></strong><span><?= htmlspecialchars(labelAktivitas((string) $row["aktivitas"])); ?></span><small><?= htmlspecialchars($row["dibuat_pada"]); ?></small></article><?php endwhile; else: ?><p class="notification-empty">Belum ada aktivitas.</p><?php endif; ?></div></section>
<style>.notification-page-card + .notification-page-card { display: none; }</style>
<?php require __DIR__ . "/partials/bawah.php"; ?>
