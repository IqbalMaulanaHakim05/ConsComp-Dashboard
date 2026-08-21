<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";

wajibRole("admin", "superadmin", "pic", "koordinator", "manager");

$kataKunci = trim((string) ($_GET["cari"] ?? ""));
$departemen = trim((string) ($_GET["department"] ?? ""));
$posisiFilter = trim((string) ($_GET["position"] ?? ""));
$bulanFilter = max(0, min(12, (int) ($_GET["bulan"] ?? 0)));
$tahunFilter = max(0, (int) ($_GET["tahun"] ?? 0));
$filterPeriodeProfil = ($bulanFilter > 0 ? " AND MONTH(pg.berlaku_mulai) = " . $bulanFilter : "")
    . ($tahunFilter > 0 ? " AND YEAR(pg.berlaku_mulai) = " . $tahunFilter : "");
$cakupan = roleOperasional() ? " AND k.department_id = " . (int) (departmentIdPengguna() ?? 0) : "";
$departemenPilihan = mysqli_query($conn, "SELECT id, nama FROM master_departemen WHERE is_active = 1 ORDER BY nama ASC");
$filterOperasional = roleOperasional();
$daftarPosisi = mysqli_query($conn, "SELECT DISTINCT position FROM karyawan WHERE position IS NOT NULL AND TRIM(position) <> ''" . ($filterOperasional ? " AND department_id = " . (int) (departmentIdPengguna() ?? 0) : "") . " ORDER BY position ASC");
$komponenPendapatan = mysqli_query($conn, "SELECT id, kode, nama FROM jenis_komponen_gaji WHERE kategori = 'pendapatan' ORDER BY nama ASC");
$daftarKomponenPendapatan = [];
while ($komponen = mysqli_fetch_assoc($komponenPendapatan)) $daftarKomponenPendapatan[] = $komponen;
$komponenPotongan = mysqli_query($conn, "SELECT id, kode, nama FROM jenis_komponen_gaji WHERE kategori = 'potongan' ORDER BY nama ASC");
$daftarKomponenPotongan = [];
while ($komponen = mysqli_fetch_assoc($komponenPotongan)) $daftarKomponenPotongan[] = $komponen;
$namaPendapatanManual = mysqli_query($conn, "SELECT DISTINCT nama FROM pendapatan_tambahan_karyawan ORDER BY nama ASC");
$daftarPendapatanManual = [];
while ($pendapatan = mysqli_fetch_assoc($namaPendapatanManual)) $daftarPendapatanManual[] = $pendapatan["nama"];
$namaKomponenPendapatan = array_map(static fn (array $komponen): string => (string) $komponen["nama"], $daftarKomponenPendapatan);
$daftarPendapatanManual = array_values(array_filter($daftarPendapatanManual, static fn (string $nama): bool => !in_array($nama, $namaKomponenPendapatan, true)));
$namaPotongan = mysqli_query($conn, "SELECT DISTINCT nama FROM potongan_karyawan ORDER BY nama ASC");
$daftarPotongan = [];
while ($potongan = mysqli_fetch_assoc($namaPotongan)) $daftarPotongan[] = $potongan["nama"];
$namaKomponenPotongan = array_map(static fn (array $komponen): string => (string) $komponen["nama"], $daftarKomponenPotongan);
$daftarPotongan = array_values(array_filter($daftarPotongan, static fn (string $nama): bool => !in_array($nama, $namaKomponenPotongan, true)));
$tahunPilihan = [(int) date("Y")];
$hasilTahun = mysqli_query($conn, "SELECT DISTINCT YEAR(berlaku_mulai) AS tahun FROM profil_gaji WHERE berlaku_mulai IS NOT NULL ORDER BY tahun DESC");
if ($hasilTahun) while ($itemTahun = mysqli_fetch_assoc($hasilTahun)) {
    $nilaiTahun = (int) $itemTahun["tahun"];
    if ($nilaiTahun > 0 && !in_array($nilaiTahun, $tahunPilihan, true)) $tahunPilihan[] = $nilaiTahun;
}
rsort($tahunPilihan);

// Konfigurasi Sorting
$sortPilihan = [
    "nama" => "k.employee_name",
    "emp_id" => "k.emp_id",
    "posisi" => "k.position",
    "departemen" => "k.department",
    "gaji_pokok" => "COALESCE(pg.gaji_pokok, k.salary, 0)",
    "uang_makan" => "COALESCE(pg.uang_makan, 0)",
    "upah_lembur" => "COALESCE(lembur.total_upah_lembur, 0)",
    "berlaku_mulai" => "COALESCE(pg.berlaku_mulai, k.date_of_hire)",
];
$sort = (string) ($_GET["sort"] ?? "emp_id");
if (!isset($sortPilihan[$sort])) $sort = "emp_id";
$arah = strtoupper((string) ($_GET["arah"] ?? "ASC"));
if (!in_array($arah, ["ASC", "DESC"], true)) $arah = "ASC";

// Konfigurasi Pembatasan Baris & Pagination (sama seperti Data Karyawan: 50, 100, 150, Semua)
$batasDiizinkan = [50, 100, 150];
$batasParam = (string) ($_GET["batas"] ?? 50);
$tanpaBatas = ($batasParam === "semua");
$batas = 50;
if (!$tanpaBatas) {
    $batasAngka = (int) $batasParam;
    $batas = in_array($batasAngka, $batasDiizinkan, true) ? $batasAngka : 50;
}

$filterSql = $filterOperasional ? " AND (? = '' OR k.position = ?)" : " AND (? = '' OR k.department_id = ?)";
$departemenId = $departemen === '' ? 0 : (int) $departemen;
$filterNilai = $filterOperasional ? $posisiFilter : $departemen;

// Hitung total baris yang cocok
$sqlCount = "SELECT COUNT(DISTINCT k.id) AS total
             FROM karyawan k
             LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id
             WHERE (? = '' OR k.employee_name LIKE CONCAT('%', ?, '%'))" . $cakupan . $filterSql . $filterPeriodeProfil;
$stmtCount = mysqli_prepare($conn, $sqlCount);
if ($filterOperasional) {
    mysqli_stmt_bind_param($stmtCount, "ssss", $kataKunci, $kataKunci, $filterNilai, $filterNilai);
} else {
    mysqli_stmt_bind_param($stmtCount, "sssi", $kataKunci, $kataKunci, $filterNilai, $departemenId);
}
mysqli_stmt_execute($stmtCount);
$resCount = mysqli_stmt_get_result($stmtCount);
$totalCocok = (int) (($resCount ? mysqli_fetch_assoc($resCount) : null)["total"] ?? 0);
mysqli_stmt_close($stmtCount);

// Hitung pagination
$totalHalaman = $tanpaBatas ? 1 : max(1, (int) ceil($totalCocok / $batas));
$halaman = max(1, min($totalHalaman, (int) ($_GET["hal"] ?? 1)));
$offset = ($halaman - 1) * $batas;

// Query data utama
$sql = "SELECT
            k.id, k.emp_id, k.employee_name, k.position, k.department,
            COALESCE(lembur.total_upah_lembur, 0) AS total_upah_lembur,
            pg.id AS profil_id, COALESCE(pg.gaji_pokok, k.salary, 0) AS gaji_pokok,
            COALESCE(pg.uang_makan, 0) AS uang_makan,
            COALESCE(pg.berlaku_mulai, k.date_of_hire) AS berlaku_mulai
        FROM karyawan k
        LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id
        LEFT JOIN (
            SELECT o.karyawan_id, SUM(oc.jumlah_upah) AS total_upah_lembur
            FROM overtime_reports o
            INNER JOIN overtime_compensations oc ON oc.overtime_id = o.id
            WHERE o.status IN ('disetujui', 'selesai')
            GROUP BY o.karyawan_id
        ) lembur ON lembur.karyawan_id = k.id
        WHERE (? = '' OR k.employee_name LIKE CONCAT('%', ?, '%'))" . $cakupan . $filterSql . $filterPeriodeProfil . "
        ORDER BY " . $sortPilihan[$sort] . " " . $arah . ", k.id ASC";

if (!$tanpaBatas) {
    $sql .= " LIMIT " . (int) $batas . " OFFSET " . (int) $offset;
}

$stmt = mysqli_prepare($conn, $sql);
if ($filterOperasional) {
    mysqli_stmt_bind_param($stmt, "ssss", $kataKunci, $kataKunci, $filterNilai, $filterNilai);
} else {
    mysqli_stmt_bind_param($stmt, "sssi", $kataKunci, $kataKunci, $filterNilai, $departemenId);
}
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$jumlahData = $hasil ? mysqli_num_rows($hasil) : 0;

$mulai = $jumlahData > 0 ? ($offset + 1) : 0;
$sampai = $offset + $jumlahData;

$paramDasar = [];
if ($kataKunci !== "") $paramDasar["cari"] = $kataKunci;
if ($departemen !== "") $paramDasar["department"] = $departemen;
if ($posisiFilter !== "") $paramDasar["position"] = $posisiFilter;
if ($bulanFilter > 0) $paramDasar["bulan"] = $bulanFilter;
if ($tahunFilter > 0) $paramDasar["tahun"] = $tahunFilter;
$paramDasar["batas"] = $tanpaBatas ? "semua" : $batas;
$paramDasar["sort"] = $sort;
$paramDasar["arah"] = $arah;

$parameterFilterDaftar = array_filter([
    "cari" => $kataKunci,
    "department" => $departemen,
    "position" => $posisiFilter,
    "bulan" => $bulanFilter,
    "tahun" => $tahunFilter,
], static fn (string|int $nilai): bool => $nilai !== "" && $nilai !== 0);

$filterAktif = $kataKunci !== ""
    || $departemen !== ""
    || $posisiFilter !== ""
    || $bulanFilter > 0
    || $tahunFilter > 0
    || $sort !== "emp_id"
    || $arah !== "ASC"
    || $tanpaBatas
    || (!$tanpaBatas && $batas !== 50);

$totalKolomTabel = 10 + count($daftarKomponenPendapatan) + count($daftarPendapatanManual) + count($daftarKomponenPotongan) + count($daftarPotongan);

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
        <form method="GET" class="search-form">
            <input
                type="text"
                name="cari"
                placeholder="Cari nama karyawan"
                value="<?= htmlspecialchars($kataKunci); ?>"
            >

            <?php if ($filterOperasional): ?><select name="position" onchange="this.form.submit()">
                <option value="">Semua posisi</option>
                <?php while ($posisi = mysqli_fetch_assoc($daftarPosisi)): ?><option value="<?= htmlspecialchars($posisi["position"]); ?>" <?= $posisiFilter === $posisi["position"] ? "selected" : ""; ?>><?= htmlspecialchars($posisi["position"]); ?></option><?php endwhile; ?>
            </select><?php else: ?><select name="department" onchange="this.form.submit()">
                <option value="">Semua departemen</option>
                <?php while ($item = mysqli_fetch_assoc($departemenPilihan)): ?>
                    <option value="<?= (int) $item["id"]; ?>" <?= $departemenId === (int) $item["id"] ? "selected" : ""; ?>><?= htmlspecialchars($item["nama"]); ?></option>
                <?php endwhile; ?>
            </select><?php endif; ?>

            <select name="bulan" onchange="this.form.submit()">
                <option value="0">Semua bulan</option>
                <?php foreach ([1 => "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"] as $nomorBulan => $namaBulan): ?>
                    <option value="<?= $nomorBulan; ?>" <?= $bulanFilter === $nomorBulan ? "selected" : ""; ?>><?= $namaBulan; ?></option>
                <?php endforeach; ?>
            </select>

            <select name="tahun" onchange="this.form.submit()">
                <option value="0">Semua tahun</option>
                <?php foreach ($tahunPilihan as $tahun): ?><option value="<?= $tahun; ?>" <?= $tahunFilter === $tahun ? "selected" : ""; ?>><?= $tahun; ?></option><?php endforeach; ?>
            </select>

            <?php if ($sort !== "emp_id"): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort); ?>">
            <?php endif; ?>
            <?php if ($arah !== "ASC"): ?>
                <input type="hidden" name="arah" value="<?= htmlspecialchars($arah); ?>">
            <?php endif; ?>

            <button class="btn btn-primary" type="submit">Cari</button>

            <select
                name="batas"
                onchange="this.form.submit()"
                title="Jumlah baris yang ditampilkan"
            >
                <?php foreach ($batasDiizinkan as $opsiBatas): ?>
                    <option
                        value="<?= $opsiBatas; ?>"
                        <?= (!$tanpaBatas && $batas === $opsiBatas) ? "selected" : ""; ?>
                    >
                        <?= $opsiBatas; ?> baris
                    </option>
                <?php endforeach; ?>
                <option value="semua" <?= $tanpaBatas ? "selected" : ""; ?>>Semua</option>
            </select>

            <?php if ($filterAktif): ?>
                <a href="upah.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>

            <?php if (punyaRole("admin", "superadmin", "pic", "koordinator", "manager")): ?>
                <a class="btn btn-primary export-excel-btn" href="fungsi/export_upah_excel.php?<?= htmlspecialchars(http_build_query(["cari" => $kataKunci, "department" => $departemen, "bulan" => $bulanFilter, "tahun" => $tahunFilter, "position" => $posisiFilter])); ?>">Export Excel</a>
            <?php endif; ?>
            <?php if (punyaRole("admin", "superadmin")): ?>
                <a class="btn btn-success" href="batch-slip-gaji.php?<?= htmlspecialchars(http_build_query(["department_id" => $departemen, "position" => $posisiFilter, "bulan" => $bulanFilter > 0 ? $bulanFilter : (int) date("n"), "tahun" => $tahunFilter > 0 ? $tahunFilter : (int) date("Y")])); ?>">Proses Slip Batch</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="result-info">
        <?php if ($kataKunci !== ""): ?>
            Ditemukan <strong><?= $totalCocok; ?></strong> data untuk pencarian <strong><?= htmlspecialchars($kataKunci); ?></strong>, menampilkan baris <strong><?= $mulai; ?>&ndash;<?= $sampai; ?></strong>.
        <?php else: ?>
            Menampilkan baris <strong><?= $mulai; ?>&ndash;<?= $sampai; ?></strong> dari <strong><?= $totalCocok; ?></strong> data karyawan.
        <?php endif; ?>
    </div>

    <div class="table-wrapper">
        <table class="upah-data-table" style="min-width:1050px">
            <thead>
                <tr>
                    <th>No</th>
                    <th class="th-sortable <?= $sort === 'emp_id' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('emp_id', '<?= ($sort === 'emp_id' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan ID Karyawan"
                        >
                            <span>ID</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'emp_id' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <th class="th-sortable <?= $sort === 'nama' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('nama', '<?= ($sort === 'nama' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan Nama"
                        >
                            <span>Nama</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'nama' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <th class="th-sortable <?= $sort === 'posisi' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('posisi', '<?= ($sort === 'posisi' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan Posisi"
                        >
                            <span>Posisi</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'posisi' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <th class="th-sortable <?= $sort === 'departemen' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('departemen', '<?= ($sort === 'departemen' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan Departemen"
                        >
                            <span>Departemen</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'departemen' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <th class="th-sortable <?= $sort === 'gaji_pokok' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('gaji_pokok', '<?= ($sort === 'gaji_pokok' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan Gaji Pokok"
                        >
                            <span>Gaji Pokok</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'gaji_pokok' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <th class="th-sortable <?= $sort === 'uang_makan' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('uang_makan', '<?= ($sort === 'uang_makan' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan Uang Makan"
                        >
                            <span>Uang Makan</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'uang_makan' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <th class="th-sortable <?= $sort === 'upah_lembur' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('upah_lembur', '<?= ($sort === 'upah_lembur' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan Upah Lembur"
                        >
                            <span>Upah Lembur</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'upah_lembur' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <?php foreach ($daftarKomponenPendapatan as $komponen): ?><th><?= htmlspecialchars($komponen["nama"]); ?></th><?php endforeach; ?>
                    <?php foreach ($daftarPendapatanManual as $namaManual): ?><th><?= htmlspecialchars($namaManual); ?></th><?php endforeach; ?>
                    <?php foreach ($daftarKomponenPotongan as $komponen): ?><th><?= htmlspecialchars($komponen["nama"]); ?></th><?php endforeach; ?>
                    <?php foreach ($daftarPotongan as $namaPotonganItem): ?><th><?= htmlspecialchars($namaPotonganItem); ?></th><?php endforeach; ?>
                    <th class="th-sortable <?= $sort === 'berlaku_mulai' ? ('is-active ' . ($arah === 'DESC' ? 'is-desc' : 'is-asc')) : ''; ?>">
                        <button
                            type="button"
                            class="th-sort-btn"
                            onclick="urutkanKolomTabel('berlaku_mulai', '<?= ($sort === 'berlaku_mulai' && $arah === 'ASC') ? 'DESC' : 'ASC'; ?>')"
                            title="Urutkan Berlaku Mulai"
                        >
                            <span>Berlaku Mulai</span>
                            <span class="th-sort-indicator" aria-hidden="true"><?= $sort === 'berlaku_mulai' ? ($arah === 'DESC' ? '▼' : '▲') : '⇅'; ?></span>
                        </button>
                    </th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($jumlahData > 0): ?>
                <?php $nomor = $offset + 1; while ($baris = mysqli_fetch_assoc($hasil)): ?>
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
                            $nilai = (float) ($nilaiKomponen[(int) $komponen["id"]] ?? 0);
                            $hasilManualKomponen = mysqli_query($conn, "SELECT SUM(nilai) AS nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id = " . (int) $baris["id"] . " AND nama = '" . mysqli_real_escape_string($conn, (string) $komponen["nama"]) . "'");
                            $nilai += $hasilManualKomponen ? (float) (mysqli_fetch_assoc($hasilManualKomponen)["nilai"] ?? 0) : 0;
                            if ($nilai <= 0) $nilai = null;
                        ?>
                            <td><?= $nilai === null || $nilai == 0 ? "" : "Rp " . number_format($nilai, 0, ",", "."); ?></td>
                        <?php endforeach; ?>
                        <?php $hasilPendapatanManual = mysqli_query($conn, "SELECT nama, nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id = " . (int) $baris["id"]); $nilaiManual = []; if ($hasilPendapatanManual) while ($itemManual = mysqli_fetch_assoc($hasilPendapatanManual)) $nilaiManual[$itemManual["nama"]] = (float) $itemManual["nilai"]; foreach ($daftarPendapatanManual as $namaManual): ?><td><?= isset($nilaiManual[$namaManual]) && $nilaiManual[$namaManual] > 0 ? "Rp " . number_format($nilaiManual[$namaManual], 0, ",", ".") : ""; ?></td><?php endforeach; ?>
                        <?php $hasilPotongan = mysqli_query($conn, "SELECT nama, nilai FROM potongan_karyawan WHERE karyawan_id = " . (int) $baris["id"]); $nilaiPotongan = []; if ($hasilPotongan) while ($itemPotongan = mysqli_fetch_assoc($hasilPotongan)) $nilaiPotongan[$itemPotongan["nama"]] = ($nilaiPotongan[$itemPotongan["nama"]] ?? 0) + (float) $itemPotongan["nilai"]; foreach ($daftarKomponenPotongan as $komponen): $nilai = (float) ($nilaiKomponen[(int) $komponen["id"]] ?? 0) + (float) ($nilaiPotongan[$komponen["nama"]] ?? 0); ?><td><?= $nilai > 0 ? "Rp " . number_format($nilai, 0, ",", ".") : ""; ?></td><?php endforeach; ?><?php foreach ($daftarPotongan as $namaPotonganItem): ?><td><?= isset($nilaiPotongan[$namaPotonganItem]) && $nilaiPotongan[$namaPotonganItem] > 0 ? "Rp " . number_format($nilaiPotongan[$namaPotonganItem], 0, ",", ".") : ""; ?></td><?php endforeach; ?>
                        <td><?= htmlspecialchars((string) ($baris["berlaku_mulai"] ?? "-")); ?></td>
                        <td>
                            <?php if (punyaRole("superadmin")): ?><a class="btn btn-warning" href="edit-upah.php?<?= htmlspecialchars(http_build_query(array_merge(["id" => (int) $baris["id"]], $parameterFilterDaftar)), ENT_QUOTES, "UTF-8"); ?>">Edit</a><?php endif; ?>
                            <?php if (punyaRole("admin", "superadmin")): ?><form method="POST" action="fungsi/generate-slip-gaji.php" style="display:inline"><input type="hidden" name="id" value="<?= (int) $baris["id"]; ?>"><input type="hidden" name="bulan" value="<?= $bulanFilter > 0 ? $bulanFilter : (int) date("n"); ?>"><input type="hidden" name="tahun" value="<?= $tahunFilter > 0 ? $tahunFilter : (int) date("Y"); ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><button class="btn btn-secondary" type="submit">PDF</button></form><?php else: ?>-<?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $totalKolomTabel; ?>" class="empty-data">
                        <?= $kataKunci !== "" ? "Data yang dicari tidak ditemukan." : "Data upah belum tersedia."; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$tanpaBatas && $totalHalaman > 1): ?>
        <div class="pagination">
            <div class="pagination-info">
                Halaman <strong><?= $halaman; ?></strong> dari <strong><?= $totalHalaman; ?></strong>
            </div>
            <div class="pagination-nav">
                <?php if ($halaman > 1): ?>
                    <a href="?<?= htmlspecialchars(http_build_query($paramDasar + ["hal" => $halaman - 1])); ?>">
                        &larr; Sebelumnya
                    </a>
                <?php else: ?>
                    <span class="disabled">&larr; Sebelumnya</span>
                <?php endif; ?>

                <?php if ($halaman < $totalHalaman): ?>
                    <a href="?<?= htmlspecialchars(http_build_query($paramDasar + ["hal" => $halaman + 1])); ?>">
                        Berikutnya &rarr;
                    </a>
                <?php else: ?>
                    <span class="disabled">Berikutnya &rarr;</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function urutkanKolomTabel(kunciKolom, arahBaru) {
            const form = document.querySelector('.search-form');
            if (!form) return;
            let inputSort = form.querySelector('input[name="sort"]');
            let inputArah = form.querySelector('input[name="arah"]');
            if (inputSort) {
                inputSort.value = kunciKolom;
            } else {
                const el = document.createElement('input');
                el.type = 'hidden';
                el.name = 'sort';
                el.value = kunciKolom;
                form.appendChild(el);
            }
            if (inputArah) {
                inputArah.value = arahBaru;
            } else {
                const el = document.createElement('input');
                el.type = 'hidden';
                el.name = 'arah';
                el.value = arahBaru;
                form.appendChild(el);
            }
            form.submit();
        }
    </script>
</section>
<?php require __DIR__ . "/partials/bawah.php"; ?>

