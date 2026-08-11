<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("superadmin");
siapkanAudit($conn);

$perHalaman = 50;
$totalAktivitas = 0;
$hasilJumlah = mysqli_query($conn, "SELECT COUNT(*) AS total FROM audit_aktivitas");
if ($hasilJumlah) $totalAktivitas = (int) (mysqli_fetch_assoc($hasilJumlah)["total"] ?? 0);
$totalHalaman = max(1, (int) ceil($totalAktivitas / $perHalaman));
$halaman = max(1, (int) ($_GET["hal"] ?? 1));
if ($halaman > $totalHalaman) $halaman = $totalHalaman;
$offset = ($halaman - 1) * $perHalaman;

$hasil = mysqli_query(
    $conn,
    "SELECT a.*, u.nama, u.username
     FROM audit_aktivitas a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.id DESC
     LIMIT " . $perHalaman . " OFFSET " . $offset
);

$judulHalaman = "Audit Aktivitas";
$subjudulHalaman = "Riwayat aktivitas penting sistem (50 baris per halaman).";
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
        <div class="pagination">
            <div class="pagination-info">
                Halaman <strong><?= $halaman; ?></strong> dari <strong><?= $totalHalaman; ?></strong>
                · Total <strong><?= number_format($totalAktivitas, 0, ",", "."); ?></strong> aktivitas
            </div>
            <div class="pagination-nav">
                <?php if ($halaman > 1): ?>
                    <a href="?hal=<?= $halaman - 1; ?>">&larr; Sebelumnya</a>
                <?php else: ?>
                    <span class="disabled">&larr; Sebelumnya</span>
                <?php endif; ?>
                <?php if ($halaman < $totalHalaman): ?>
                    <a href="?hal=<?= $halaman + 1; ?>">Berikutnya &rarr;</a>
                <?php else: ?>
                    <span class="disabled">Berikutnya &rarr;</span>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php
require __DIR__ . "/partials/bawah.php";
