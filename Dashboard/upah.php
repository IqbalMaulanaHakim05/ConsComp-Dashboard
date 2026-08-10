<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";

wajibRole("admin", "superadmin", "pic", "koordinator", "manager");

$kataKunci = trim((string) ($_GET["cari"] ?? ""));
$departemen = trim((string) ($_GET["department"] ?? ""));
$periodeBulan = max(1, min(12, (int) ($_GET["bulan"] ?? date("n"))));
$periodeTahun = max(2000, min(2100, (int) ($_GET["tahun"] ?? date("Y"))));
$cakupan = roleOperasional() ? " AND k.department_id = " . (int) (departmentIdPengguna() ?? 0) : "";
$departemenPilihan = mysqli_query($conn, "SELECT id, nama FROM master_departemen WHERE is_active = 1 ORDER BY nama ASC");
$komponenPendapatan = mysqli_query($conn, "SELECT id, kode, nama FROM jenis_komponen_gaji WHERE kategori = 'pendapatan' AND is_active = 1 ORDER BY nama ASC");
$daftarKomponenPendapatan = [];
while ($komponen = mysqli_fetch_assoc($komponenPendapatan)) $daftarKomponenPendapatan[] = $komponen;
$namaPendapatanManual = mysqli_query($conn, "SELECT DISTINCT nama FROM pendapatan_tambahan_karyawan ORDER BY nama ASC");
$daftarPendapatanManual = [];
while ($pendapatan = mysqli_fetch_assoc($namaPendapatanManual)) $daftarPendapatanManual[] = $pendapatan["nama"];
$namaPotongan = mysqli_query($conn, "SELECT DISTINCT nama FROM potongan_karyawan ORDER BY nama ASC");
$daftarPotongan = [];
while ($potongan = mysqli_fetch_assoc($namaPotongan)) $daftarPotongan[] = $potongan["nama"];

$sql = "SELECT
            k.id, k.emp_id, k.employee_name, k.position, k.department,
            COALESCE(lembur.total_upah_lembur, 0) AS total_upah_lembur,
            pg.id AS profil_id, pg.gaji_pokok, pg.uang_makan, pg.berlaku_mulai
        FROM karyawan k
        LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id
        LEFT JOIN (
            SELECT o.karyawan_id, SUM(oc.jumlah_upah) AS total_upah_lembur
            FROM overtime_reports o
            INNER JOIN overtime_compensations oc ON oc.overtime_id = o.id
            WHERE o.status IN ('disetujui', 'selesai')
              AND MONTH(o.mulai_at) = " . $periodeBulan . "
              AND YEAR(o.mulai_at) = " . $periodeTahun . "
            GROUP BY o.karyawan_id
        ) lembur ON lembur.karyawan_id = k.id
        WHERE (? = '' OR k.employee_name LIKE CONCAT('%', ?, '%') OR k.emp_id LIKE CONCAT('%', ?, '%'))" . $cakupan . "
          AND (? = '' OR k.department_id = ?)
        ORDER BY k.employee_name ASC";
$stmt = mysqli_prepare($conn, $sql);
$departemenId = $departemen === '' ? 0 : (int) $departemen;
mysqli_stmt_bind_param($stmt, "sssii", $kataKunci, $kataKunci, $kataKunci, $departemen, $departemenId);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$judulHalaman = "Upah";
$subjudulHalaman = "Kelola profil gaji dan komponen upah karyawan.";
$halamanAktif = "upah";
require __DIR__ . "/partials/atas.php";
?>
<section class="data-card">
    <div class="data-card-header">
        <div>
            <h2>Data Upah Karyawan</h2>
            <p>Profil gaji aktif yang tersimpan pada sistem.</p>
        </div>
    </div>
    <form method="GET" class="filter-bar">
        <input name="cari" value="<?= htmlspecialchars($kataKunci); ?>" placeholder="Cari nama atau ID karyawan">
        <select name="bulan"><?php for ($bulan = 1; $bulan <= 12; $bulan++): ?><option value="<?= $bulan; ?>" <?= $periodeBulan === $bulan ? "selected" : ""; ?>>Bulan <?= $bulan; ?></option><?php endfor; ?></select>
        <input name="tahun" type="number" min="2000" max="2100" value="<?= $periodeTahun; ?>">
        <select name="department">
            <option value="">Semua departemen</option>
            <?php while ($item = mysqli_fetch_assoc($departemenPilihan)): ?>
                <option value="<?= (int) $item["id"]; ?>" <?= $departemenId === (int) $item["id"] ? "selected" : ""; ?>><?= htmlspecialchars($item["nama"]); ?></option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <div class="table-wrapper">
        <table style="min-width:1050px">
            <thead><tr><th>No</th><th>ID</th><th>Nama</th><th>Posisi</th><th>Departemen</th><th>Gaji Pokok</th><th>Uang Makan</th><th>Upah Lembur</th><?php foreach ($daftarKomponenPendapatan as $komponen): ?><th><?= htmlspecialchars($komponen["nama"]); ?></th><?php endforeach; ?><?php foreach ($daftarPendapatanManual as $namaManual): ?><th><?= htmlspecialchars($namaManual); ?></th><?php endforeach; ?><?php foreach ($daftarPotongan as $namaPotonganItem): ?><th><?= htmlspecialchars($namaPotonganItem); ?></th><?php endforeach; ?><th>Berlaku Mulai</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php $nomor = 1; while ($baris = mysqli_fetch_assoc($hasil)): ?>
                <tr>
                    <td><?= $nomor++; ?></td>
                    <td><?= htmlspecialchars((string) $baris["emp_id"]); ?></td>
                    <td><?= htmlspecialchars((string) $baris["employee_name"]); ?></td>
                    <td><?= htmlspecialchars((string) $baris["position"]); ?></td>
                    <td><?= htmlspecialchars((string) $baris["department"]); ?></td>
                    <td>Rp <?= number_format((float) ($baris["gaji_pokok"] ?? 0), 0, ",", "."); ?></td>
                    <td>Rp <?= number_format((float) ($baris["uang_makan"] ?? 0), 0, ",", "."); ?></td>
                    <td><?= (float) $baris["total_upah_lembur"] > 0 ? "Rp " . number_format((float) $baris["total_upah_lembur"], 0, ",", ".") : ""; ?></td>
                    <?php
                    $nilaiKomponen = [];
                    $hasilNilaiKomponen = mysqli_query($conn, "SELECT jenis_komponen_id, nilai FROM komponen_gaji_karyawan WHERE profil_gaji_id = " . (int) ($baris["profil_id"] ?? 0));
                    if ($hasilNilaiKomponen) while ($nilai = mysqli_fetch_assoc($hasilNilaiKomponen)) $nilaiKomponen[(int) $nilai["jenis_komponen_id"]] = (float) $nilai["nilai"];
                    foreach ($daftarKomponenPendapatan as $komponen):
                        $nilai = $nilaiKomponen[(int) $komponen["id"]] ?? null;
                    ?>
                        <td><?= $nilai === null || $nilai == 0 ? "" : "Rp " . number_format($nilai, 0, ",", "."); ?></td>
                    <?php endforeach; ?>
                    <?php $hasilPendapatanManual = mysqli_query($conn, "SELECT nama, nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id = " . (int) $baris["id"]); $nilaiManual = []; if ($hasilPendapatanManual) while ($itemManual = mysqli_fetch_assoc($hasilPendapatanManual)) $nilaiManual[$itemManual["nama"]] = (float) $itemManual["nilai"]; foreach ($daftarPendapatanManual as $namaManual): ?><td><?= isset($nilaiManual[$namaManual]) && $nilaiManual[$namaManual] > 0 ? "Rp " . number_format($nilaiManual[$namaManual], 0, ",", ".") : ""; ?></td><?php endforeach; ?>
                    <?php $hasilPotongan = mysqli_query($conn, "SELECT nama, nilai FROM potongan_karyawan WHERE karyawan_id = " . (int) $baris["id"]); $nilaiPotongan = []; if ($hasilPotongan) while ($itemPotongan = mysqli_fetch_assoc($hasilPotongan)) $nilaiPotongan[$itemPotongan["nama"]] = (float) $itemPotongan["nilai"]; foreach ($daftarPotongan as $namaPotonganItem): ?><td><?= isset($nilaiPotongan[$namaPotonganItem]) && $nilaiPotongan[$namaPotonganItem] > 0 ? "Rp " . number_format($nilaiPotongan[$namaPotonganItem], 0, ",", ".") : ""; ?></td><?php endforeach; ?>
                    <td><?= htmlspecialchars((string) ($baris["berlaku_mulai"] ?? "-")); ?></td>
                    <td><?php if (punyaRole("admin", "superadmin")): ?><a class="btn btn-warning" href="edit-upah.php?id=<?= (int) $baris["id"]; ?>">Edit</a><form method="POST" action="fungsi/generate-slip-gaji.php" style="display:inline"><input type="hidden" name="id" value="<?= (int) $baris["id"]; ?>"><input type="hidden" name="bulan" value="<?= $periodeBulan; ?>"><input type="hidden" name="tahun" value="<?= $periodeTahun; ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><button class="btn btn-secondary" type="submit">PDF</button></form><?php else: ?>-<?php endif; ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . "/partials/bawah.php"; ?>
