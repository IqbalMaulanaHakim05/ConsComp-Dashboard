<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("superadmin");
siapkanAudit($conn);

$hasil = mysqli_query(
    $conn,
    "SELECT a.*, u.nama, u.username
     FROM audit_aktivitas a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.id DESC
     LIMIT 200"
);

$judulHalaman = "Audit Aktivitas";
$subjudulHalaman = "Riwayat aktivitas penting sistem (200 terbaru).";
$halamanAktif = "audit";

require __DIR__ . "/partials/atas.php";

?>
    <section class="data-card">
        <div class="data-card-header">
            <h2>Riwayat Aktivitas</h2>
        </div>

        <div class="table-wrapper">
            <table style="min-width:700px">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hasil && mysqli_num_rows($hasil) > 0): ?>
                        <?php while ($baris = mysqli_fetch_assoc($hasil)): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $baris["dibuat_pada"]); ?></td>
                                <td><?= htmlspecialchars($baris["nama"] ?: ($baris["username"] ?: "-")); ?></td>
                                <td><?= htmlspecialchars($baris["aktivitas"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="empty-data">Belum ada aktivitas tercatat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php
require __DIR__ . "/partials/bawah.php";
