<?php

declare(strict_types=1);

function kolomSlipGajiAda(mysqli $conn, string $kolom): bool
{
    $kolomAman = mysqli_real_escape_string($conn, $kolom);
    $hasil = mysqli_query($conn, "SHOW COLUMNS FROM slip_gaji LIKE '{$kolomAman}'");
    return $hasil !== false && mysqli_num_rows($hasil) > 0;
}

function indeksSlipGajiAda(mysqli $conn, string $indeks): bool
{
    $indeksAman = mysqli_real_escape_string($conn, $indeks);
    $hasil = mysqli_query($conn, "SHOW INDEX FROM slip_gaji WHERE Key_name = '{$indeksAman}'");
    return $hasil !== false && mysqli_num_rows($hasil) > 0;
}

function siapkanPenyimpananSlipGaji(mysqli $conn): bool
{
    $periodeSiap = mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS periode_gaji (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tahun SMALLINT UNSIGNED NOT NULL,
            bulan TINYINT UNSIGNED NOT NULL,
            status ENUM('draft', 'dikunci') NOT NULL DEFAULT 'draft',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_periode_gaji_tahun_bulan (tahun, bulan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) !== false;

    $slipSiap = $periodeSiap && mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS slip_gaji (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            periode_gaji_id INT UNSIGNED NOT NULL,
            karyawan_id INT NOT NULL,
            versi SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            profil_gaji_id INT UNSIGNED NULL,
            employee_id_snapshot VARCHAR(30) NOT NULL,
            nama_snapshot VARCHAR(150) NOT NULL,
            posisi_snapshot VARCHAR(100) NULL,
            departemen_snapshot VARCHAR(100) NULL,
            total_pendapatan DECIMAL(15,2) NOT NULL DEFAULT 0,
            total_potongan DECIMAL(15,2) NOT NULL DEFAULT 0,
            gaji_bersih DECIMAL(15,2) NOT NULL DEFAULT 0,
            nama_file VARCHAR(255) NULL,
            status ENUM('berhasil', 'gagal') NOT NULL DEFAULT 'berhasil',
            pesan_error VARCHAR(500) NULL,
            batch_id VARCHAR(64) NOT NULL DEFAULT '',
            generated_by INT NOT NULL,
            generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_slip_gaji_periode_karyawan_versi (periode_gaji_id, karyawan_id, versi),
            KEY idx_slip_gaji_karyawan (karyawan_id),
            KEY idx_slip_gaji_batch (batch_id),
            CONSTRAINT fk_slip_gaji_periode FOREIGN KEY (periode_gaji_id) REFERENCES periode_gaji(id) ON DELETE RESTRICT,
            CONSTRAINT fk_slip_gaji_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
            CONSTRAINT fk_slip_gaji_generator FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) !== false;

    if (!$slipSiap) {
        return false;
    }

    $kolomBaru = [
        'versi' => "ADD COLUMN versi SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER karyawan_id",
        'profil_gaji_id' => "ADD COLUMN profil_gaji_id INT UNSIGNED NULL AFTER versi",
        'nama_file' => "ADD COLUMN nama_file VARCHAR(255) NULL AFTER gaji_bersih",
        'status' => "ADD COLUMN status ENUM('berhasil', 'gagal') NOT NULL DEFAULT 'berhasil' AFTER nama_file",
        'pesan_error' => "ADD COLUMN pesan_error VARCHAR(500) NULL AFTER status",
        'batch_id' => "ADD COLUMN batch_id VARCHAR(64) NOT NULL DEFAULT '' AFTER pesan_error",
    ];

    foreach ($kolomBaru as $kolom => $sqlTambah) {
        if (!kolomSlipGajiAda($conn, $kolom) && !mysqli_query($conn, "ALTER TABLE slip_gaji {$sqlTambah}")) {
            return false;
        }
    }

    if (!indeksSlipGajiAda($conn, 'idx_slip_gaji_periode')) {
        if (!mysqli_query($conn, "ALTER TABLE slip_gaji ADD KEY idx_slip_gaji_periode (periode_gaji_id)")) {
            return false;
        }
    }
    if (indeksSlipGajiAda($conn, 'uq_slip_gaji_periode_karyawan')) {
        if (!mysqli_query($conn, "ALTER TABLE slip_gaji DROP INDEX uq_slip_gaji_periode_karyawan")) {
            return false;
        }
    }
    if (!indeksSlipGajiAda($conn, 'uq_slip_gaji_periode_karyawan_versi')) {
        if (!mysqli_query(
            $conn,
            "ALTER TABLE slip_gaji
             ADD UNIQUE KEY uq_slip_gaji_periode_karyawan_versi (periode_gaji_id, karyawan_id, versi)"
        )) {
            return false;
        }
    }
    if (!indeksSlipGajiAda($conn, 'idx_slip_gaji_batch')) {
        if (!mysqli_query($conn, "ALTER TABLE slip_gaji ADD KEY idx_slip_gaji_batch (batch_id)")) {
            return false;
        }
    }

    return mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS slip_gaji_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            slip_gaji_id INT UNSIGNED NOT NULL,
            kategori ENUM('pendapatan', 'potongan') NOT NULL,
            kode VARCHAR(50) NOT NULL,
            nama VARCHAR(150) NOT NULL,
            jumlah DECIMAL(15,2) NOT NULL DEFAULT 0,
            sumber_reference VARCHAR(100) NULL,
            KEY idx_slip_gaji_items_slip (slip_gaji_id),
            CONSTRAINT fk_slip_gaji_items_slip FOREIGN KEY (slip_gaji_id) REFERENCES slip_gaji(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ) !== false;
}

function namaBulanSlipGaji(int $bulan): string
{
    return [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ][$bulan] ?? '-';
}

function periodeSlipGajiValid(int $bulan, int $tahun): bool
{
    return $bulan >= 1 && $bulan <= 12 && $tahun >= 2000 && $tahun <= 2100;
}

function ambilAtauBuatPeriodeGaji(mysqli $conn, int $bulan, int $tahun): int
{
    if (!periodeSlipGajiValid($bulan, $tahun)) {
        throw new InvalidArgumentException('Periode slip tidak valid.');
    }

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO periode_gaji (tahun, bulan)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
    );
    if (!$stmt) {
        throw new RuntimeException('Periode slip tidak dapat disiapkan.');
    }
    mysqli_stmt_bind_param($stmt, 'ii', $tahun, $bulan);
    mysqli_stmt_execute($stmt);
    $periodeId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if ($periodeId <= 0) {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM periode_gaji WHERE tahun = ? AND bulan = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ii', $tahun, $bulan);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        $periodeId = (int) ($row['id'] ?? 0);
    }

    if ($periodeId <= 0) {
        throw new RuntimeException('Periode slip tidak ditemukan setelah disiapkan.');
    }

    return $periodeId;
}

function tambahItemSlip(array &$items, string $kategori, string $kode, string $nama, float $jumlah, string $sumber): void
{
    if ($jumlah <= 0) {
        return;
    }

    $kunci = $kategori . ':' . mb_strtolower(trim($nama));
    if (isset($items[$kunci])) {
        $items[$kunci]['jumlah'] += $jumlah;
        return;
    }

    $items[$kunci] = [
        'kategori' => $kategori,
        'kode' => $kode !== '' ? $kode : substr(hash('sha256', $kunci), 0, 12),
        'nama' => $nama,
        'jumlah' => $jumlah,
        'sumber_reference' => $sumber,
    ];
}

function dataSlipGajiKaryawan(mysqli $conn, int $karyawanId, int $bulan, int $tahun): array
{
    if ($karyawanId <= 0 || !periodeSlipGajiValid($bulan, $tahun)) {
        throw new InvalidArgumentException('Karyawan atau periode slip tidak valid.');
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, emp_id, employee_name, position, department, department_id, kontak
         FROM karyawan WHERE id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$karyawan) {
        throw new RuntimeException('Data karyawan tidak ditemukan.');
    }

    foreach (['emp_id' => 'ID karyawan', 'employee_name' => 'nama karyawan', 'position' => 'posisi', 'department' => 'departemen'] as $kolom => $label) {
        if (trim((string) ($karyawan[$kolom] ?? '')) === '') {
            throw new RuntimeException(ucfirst($label) . ' belum diisi.');
        }
    }

    $awalPeriode = sprintf('%04d-%02d-01', $tahun, $bulan);
    $akhirPeriode = (new DateTimeImmutable($awalPeriode))->modify('last day of this month')->format('Y-m-d');
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, gaji_pokok, uang_makan, berlaku_mulai, berlaku_sampai
         FROM profil_gaji
         WHERE karyawan_id = ?
           AND berlaku_mulai <= ?
           AND (berlaku_sampai IS NULL OR berlaku_sampai >= ?)
         ORDER BY berlaku_mulai DESC, id DESC
         LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'iss', $karyawanId, $akhirPeriode, $awalPeriode);
    mysqli_stmt_execute($stmt);
    $profil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$profil) {
        throw new RuntimeException('Profil upah aktif untuk periode ini belum tersedia.');
    }
    if ((float) ($profil['gaji_pokok'] ?? 0) <= 0) {
        throw new RuntimeException('Gaji pokok belum diisi atau bernilai nol.');
    }

    $items = [];
    tambahItemSlip($items, 'pendapatan', 'GAJI_POKOK', 'Gaji Pokok', (float) $profil['gaji_pokok'], 'profil_gaji');
    tambahItemSlip($items, 'pendapatan', 'UANG_MAKAN', 'Uang Makan', (float) $profil['uang_makan'], 'profil_gaji');

    $stmt = mysqli_prepare(
        $conn,
        "SELECT j.kode, j.nama, j.kategori, c.nilai
         FROM komponen_gaji_karyawan c
         INNER JOIN jenis_komponen_gaji j ON j.id = c.jenis_komponen_id
         WHERE c.profil_gaji_id = ? AND c.nilai > 0
         ORDER BY j.kategori, j.nama"
    );
    $profilId = (int) $profil['id'];
    mysqli_stmt_bind_param($stmt, 'i', $profilId);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    while ($item = mysqli_fetch_assoc($hasil)) {
        tambahItemSlip(
            $items,
            (string) $item['kategori'],
            (string) $item['kode'],
            (string) $item['nama'],
            (float) $item['nilai'],
            'komponen_gaji_karyawan'
        );
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT id, nama, nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id = ? ORDER BY id');
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    while ($item = mysqli_fetch_assoc($hasil)) {
        tambahItemSlip($items, 'pendapatan', 'MANUAL_' . (int) $item['id'], (string) $item['nama'], (float) $item['nilai'], 'pendapatan_tambahan_karyawan');
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT id, nama, nilai FROM potongan_karyawan WHERE karyawan_id = ? ORDER BY id');
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    while ($item = mysqli_fetch_assoc($hasil)) {
        tambahItemSlip($items, 'potongan', 'MANUAL_' . (int) $item['id'], (string) $item['nama'], (float) $item['nilai'], 'potongan_karyawan');
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT COALESCE(SUM(oc.jumlah_upah), 0) AS total
         FROM overtime_reports o
         INNER JOIN overtime_compensations oc ON oc.overtime_id = o.id
         WHERE o.karyawan_id = ?
           AND o.status IN ('disetujui', 'selesai')
           AND DATE(o.mulai_at) BETWEEN ? AND ?"
    );
    mysqli_stmt_bind_param($stmt, 'iss', $karyawanId, $awalPeriode, $akhirPeriode);
    mysqli_stmt_execute($stmt);
    $lembur = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    tambahItemSlip($items, 'pendapatan', 'UPAH_LEMBUR', 'Upah Lembur', (float) ($lembur['total'] ?? 0), 'overtime_compensations');

    $totalPendapatan = 0.0;
    $totalPotongan = 0.0;
    foreach ($items as $item) {
        if ($item['kategori'] === 'pendapatan') {
            $totalPendapatan += (float) $item['jumlah'];
        } else {
            $totalPotongan += (float) $item['jumlah'];
        }
    }

    return [
        'karyawan' => $karyawan,
        'profil' => $profil,
        'items' => array_values($items),
        'bulan' => $bulan,
        'tahun' => $tahun,
        'total_pendapatan' => $totalPendapatan,
        'total_potongan' => $totalPotongan,
        'gaji_bersih' => $totalPendapatan - $totalPotongan,
    ];
}

function buatPdfSlipGaji(array $data): string
{
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Dependensi PDF belum terpasang.');
    }
    require_once $autoload;

    $logoPath = dirname(__DIR__, 3) . '/public/assets/images/ikonlogo_KP_1_S.png';
    $logo = is_file($logoPath) ? file_get_contents($logoPath) : false;
    if ($logo === false) {
        throw new RuntimeException('Logo perusahaan tidak ditemukan.');
    }

    $escape = static fn (string $nilai): string => htmlspecialchars($nilai, ENT_QUOTES, 'UTF-8');
    $rupiah = static fn (float $nilai): string => number_format($nilai, 0, ',', '.');
    $pendapatan = array_values(array_filter($data['items'], static fn (array $item): bool => $item['kategori'] === 'pendapatan'));
    $potongan = array_values(array_filter($data['items'], static fn (array $item): bool => $item['kategori'] === 'potongan'));
    $baris = '';
    $jumlahBaris = max(count($pendapatan), count($potongan), 1);
    for ($i = 0; $i < $jumlahBaris; $i++) {
        $masuk = $pendapatan[$i] ?? null;
        $keluar = $potongan[$i] ?? null;
        $baris .= '<tr>'
            . '<td>' . $escape((string) ($masuk['nama'] ?? '')) . '</td>'
            . '<td class="currency">' . ($masuk ? 'Rp' : '') . '</td>'
            . '<td class="amount">' . ($masuk ? $rupiah((float) $masuk['jumlah']) : '') . '</td>'
            . '<td>' . $escape((string) ($keluar['nama'] ?? '')) . '</td>'
            . '<td class="currency">' . ($keluar ? 'Rp' : '') . '</td>'
            . '<td class="amount">' . ($keluar ? $rupiah((float) $keluar['jumlah']) : '') . '</td>'
            . '</tr>';
    }

    $karyawan = $data['karyawan'];
    $periode = namaBulanSlipGaji((int) $data['bulan']) . ' ' . (int) $data['tahun'];
    $tanggalCetak = new DateTimeImmutable('now');
    $tanggalCetakTampil = $tanggalCetak->format('j') . ' ' . namaBulanSlipGaji((int) $tanggalCetak->format('n')) . ' ' . $tanggalCetak->format('Y');
    $logoSrc = 'data:image/png;base64,' . base64_encode($logo);
    $html = '<style>
        @page{margin:15mm 14mm}body{font-family:DejaVu Sans,sans-serif;color:#111;font-size:10pt;margin:0}
        .header{border-bottom:1.6px solid #111;padding-bottom:10px}.header-table,.details,.salary,.received,.signature{width:100%;border-collapse:collapse;table-layout:fixed}
        .header-table td{vertical-align:top}.company{width:34%}.title{width:32%;text-align:center}.meta{width:34%;font-size:8pt;text-align:right}
        .logo{height:24px;max-width:100%}.address{font-size:8pt;margin-top:4px}.title h1{font-size:22pt;letter-spacing:2px;margin:5px 0 3px}.period{font-size:11pt}
        .details{margin:12px 0}.details td{font-size:11pt;padding:2px 0}.salary{border:1.4px solid #111}.salary th,.salary td{padding:5px 6px}.salary th{font-size:12pt;border-bottom:1.4px solid #111}.salary th:nth-child(4),.salary td:nth-child(4){border-left:1.4px solid #111}.currency{text-align:right;width:5%}.amount{text-align:right;width:17%;white-space:nowrap}
        .total td{font-weight:bold;border-top:1.4px solid #111}.received{margin-top:25px}.received td{font-size:14pt;border-bottom:1.4px solid #111;padding:4px}.received .label{text-align:right}.received .value{text-align:right;width:25%}.signature{margin-top:20px}.signature td:first-child{width:68%}.sign{text-align:center}.gap{height:58px}
    </style>
    <div class="header"><table class="header-table"><tr>
        <td class="company"><img class="logo" src="' . $escape($logoSrc) . '"><div class="address">Jalan Bukit Watu Wila Permata Puri Blok H-IV Nomor 4, Bringin, Ngaliyan, Semarang</div></td>
        <td class="title"><h1>SLIP GAJI</h1><div class="period">Periode ' . $escape($periode) . '</div></td>
        <td class="meta">Dicetak: ' . $escape($tanggalCetakTampil) . '<br>ID Karyawan: ' . $escape((string) $karyawan['emp_id']) . '<br>Kontak: ' . $escape(trim((string) ($karyawan['kontak'] ?? '')) ?: '-') . '</td>
    </tr></table></div>
    <table class="details"><tr><td>Nama: ' . $escape((string) $karyawan['employee_name']) . '</td></tr><tr><td>Posisi: ' . $escape((string) $karyawan['position']) . '</td></tr><tr><td>Departemen: ' . $escape((string) $karyawan['department']) . '</td></tr></table>
    <table class="salary"><thead><tr><th colspan="3">PENDAPATAN</th><th colspan="3">POTONGAN</th></tr></thead><tbody>' . $baris . '<tr class="total"><td>Total Pendapatan</td><td class="currency">Rp</td><td class="amount">' . $rupiah((float) $data['total_pendapatan']) . '</td><td>Total Potongan</td><td class="currency">Rp</td><td class="amount">' . $rupiah((float) $data['total_potongan']) . '</td></tr></tbody></table>
    <table class="received"><tr><td class="label">Total Diterima</td><td class="value"><b>Rp ' . $rupiah((float) $data['gaji_bersih']) . '</b></td></tr></table>
    <table class="signature"><tr><td></td><td class="sign">....., .........<div class="gap"></div>............................</td></tr></table>';

    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf = $dompdf->output();
    if (!str_starts_with($pdf, '%PDF')) {
        throw new RuntimeException('PDF gagal dibuat.');
    }

    return $pdf;
}

function simpanMetadataSlipGaji(
    mysqli $conn,
    int $periodeId,
    array $data,
    int $versi,
    ?string $namaFile,
    string $status,
    ?string $pesanError,
    string $batchId,
    int $pembuatId
): int {
    $karyawan = $data['karyawan'];
    $profilId = isset($data['profil']['id']) ? (int) $data['profil']['id'] : null;
    $karyawanId = (int) $karyawan['id'];
    $empId = (string) $karyawan['emp_id'];
    $nama = (string) $karyawan['employee_name'];
    $posisi = (string) ($karyawan['position'] ?? '');
    $departemen = (string) ($karyawan['department'] ?? '');
    $totalPendapatan = (float) ($data['total_pendapatan'] ?? 0);
    $totalPotongan = (float) ($data['total_potongan'] ?? 0);
    $gajiBersih = (float) ($data['gaji_bersih'] ?? 0);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO slip_gaji
            (periode_gaji_id, karyawan_id, versi, profil_gaji_id,
             employee_id_snapshot, nama_snapshot, posisi_snapshot, departemen_snapshot,
             total_pendapatan, total_potongan, gaji_bersih,
             nama_file, status, pesan_error, batch_id, generated_by)
         VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, ''), ?, ?)"
    );
    if (!$stmt) {
        throw new RuntimeException('Metadata slip tidak dapat disiapkan.');
    }
    mysqli_stmt_bind_param(
        $stmt,
        'iiiissssdddssssi',
        $periodeId,
        $karyawanId,
        $versi,
        $profilId,
        $empId,
        $nama,
        $posisi,
        $departemen,
        $totalPendapatan,
        $totalPotongan,
        $gajiBersih,
        $namaFile,
        $status,
        $pesanError,
        $batchId,
        $pembuatId
    );
    mysqli_stmt_execute($stmt);
    $slipId = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if ($slipId <= 0) {
        throw new RuntimeException('Metadata slip gagal disimpan.');
    }

    if ($status === 'berhasil') {
        $stmtItem = mysqli_prepare(
            $conn,
            "INSERT INTO slip_gaji_items (slip_gaji_id, kategori, kode, nama, jumlah, sumber_reference)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$stmtItem) {
            throw new RuntimeException('Rincian slip tidak dapat disiapkan.');
        }
        foreach ($data['items'] as $item) {
            $kategori = (string) $item['kategori'];
            $kode = (string) $item['kode'];
            $namaItem = (string) $item['nama'];
            $jumlah = (float) $item['jumlah'];
            $sumber = (string) $item['sumber_reference'];
            mysqli_stmt_bind_param($stmtItem, 'isssds', $slipId, $kategori, $kode, $namaItem, $jumlah, $sumber);
            mysqli_stmt_execute($stmtItem);
        }
        mysqli_stmt_close($stmtItem);
    }

    return $slipId;
}

function dataDasarSlipGagal(mysqli $conn, int $karyawanId, int $bulan, int $tahun): array
{
    $stmt = mysqli_prepare($conn, 'SELECT id, emp_id, employee_name, position, department, department_id, kontak FROM karyawan WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$karyawan) {
        throw new RuntimeException('Data karyawan tidak ditemukan.');
    }
    return [
        'karyawan' => $karyawan,
        'profil' => [],
        'items' => [],
        'bulan' => $bulan,
        'tahun' => $tahun,
        'total_pendapatan' => 0.0,
        'total_potongan' => 0.0,
        'gaji_bersih' => 0.0,
    ];
}

function versiSlipBerikutnya(mysqli $conn, int $periodeId, int $karyawanId): int
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM periode_gaji WHERE id = ? FOR UPDATE');
    mysqli_stmt_bind_param($stmt, 'i', $periodeId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT COALESCE(MAX(versi), 0) + 1 AS versi FROM slip_gaji WHERE periode_gaji_id = ? AND karyawan_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $periodeId, $karyawanId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return max(1, (int) ($row['versi'] ?? 1));
}

function prosesSlipGajiKaryawan(
    mysqli $conn,
    int $periodeId,
    int $karyawanId,
    int $bulan,
    int $tahun,
    string $batchId,
    int $pembuatId
): array {
    $data = null;
    $namaFile = null;
    try {
        $data = dataSlipGajiKaryawan($conn, $karyawanId, $bulan, $tahun);
        $pdf = buatPdfSlipGaji($data);

        mysqli_begin_transaction($conn);
        $versi = versiSlipBerikutnya($conn, $periodeId, $karyawanId);
        $empIdAman = trim((string) preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $data['karyawan']['emp_id']), '-');
        $namaFile = sprintf(
            'slip-%s-%04d-%02d-v%d-%s.pdf',
            $empIdAman !== '' ? $empIdAman : (string) $karyawanId,
            $tahun,
            $bulan,
            $versi,
            substr($batchId, 0, 8)
        );
        $folder = __DIR__ . '/../../../storage/uploads/slip';
        if (!is_dir($folder) && !mkdir($folder, 0775, true) && !is_dir($folder)) {
            throw new RuntimeException('Folder penyimpanan slip tidak dapat dibuat.');
        }
        $path = $folder . '/' . $namaFile;
        $pathSementara = $path . '.tmp';
        if (file_put_contents($pathSementara, $pdf, LOCK_EX) === false || !rename($pathSementara, $path)) {
            @unlink($pathSementara);
            throw new RuntimeException('File slip tidak dapat disimpan.');
        }

        $slipId = simpanMetadataSlipGaji($conn, $periodeId, $data, $versi, $namaFile, 'berhasil', null, $batchId, $pembuatId);
        mysqli_commit($conn);
        return [
            'berhasil' => true,
            'slip_id' => $slipId,
            'karyawan_id' => $karyawanId,
            'nama' => (string) $data['karyawan']['employee_name'],
            'versi' => $versi,
            'pesan' => 'Slip berhasil dibuat.',
        ];
    } catch (Throwable $exception) {
        if (mysqli_errno($conn) !== 0) {
            // Kesalahan koneksi/statement akan ditangani sebagai hasil gagal per karyawan.
        }
        try {
            mysqli_rollback($conn);
        } catch (Throwable) {
        }
        if ($namaFile !== null) {
            @unlink(__DIR__ . '/../../../storage/uploads/slip/' . basename($namaFile));
        }

        $pesan = mb_substr(trim($exception->getMessage()) ?: 'Slip gagal dibuat.', 0, 500);
        try {
            $dataGagal = $data ?? dataDasarSlipGagal($conn, $karyawanId, $bulan, $tahun);
            mysqli_begin_transaction($conn);
            $versi = versiSlipBerikutnya($conn, $periodeId, $karyawanId);
            $slipId = simpanMetadataSlipGaji($conn, $periodeId, $dataGagal, $versi, null, 'gagal', $pesan, $batchId, $pembuatId);
            mysqli_commit($conn);
        } catch (Throwable) {
            try {
                mysqli_rollback($conn);
            } catch (Throwable) {
            }
            $slipId = 0;
            $versi = 0;
        }

        return [
            'berhasil' => false,
            'slip_id' => $slipId,
            'karyawan_id' => $karyawanId,
            'nama' => (string) (($data['karyawan']['employee_name'] ?? null) ?: ('Karyawan #' . $karyawanId)),
            'versi' => $versi,
            'pesan' => $pesan,
        ];
    }
}

function daftarRiwayatSlipGajiKaryawanTerbatas(mysqli $conn, int $karyawanId): array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT s.generated_at, p.bulan, p.tahun
         FROM slip_gaji s
         INNER JOIN periode_gaji p ON p.id = s.periode_gaji_id
         WHERE s.karyawan_id = ? AND s.status = 'berhasil'
         ORDER BY p.tahun DESC, p.bulan DESC, s.versi DESC, s.id DESC"
    );
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $daftar = [];
    while ($row = mysqli_fetch_assoc($hasil)) {
        $daftar[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $daftar;
}

function daftarSlipGajiKaryawan(mysqli $conn, int $karyawanId): array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT s.id, s.versi, s.nama_file, s.status, s.total_pendapatan,
                s.total_potongan, s.gaji_bersih, s.generated_at,
                p.bulan, p.tahun, u.nama AS nama_pembuat
         FROM slip_gaji s
         INNER JOIN periode_gaji p ON p.id = s.periode_gaji_id
         INNER JOIN users u ON u.id = s.generated_by
         WHERE s.karyawan_id = ? AND s.status = 'berhasil'
         ORDER BY p.tahun DESC, p.bulan DESC, s.versi DESC, s.id DESC"
    );
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, 'i', $karyawanId);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $daftar = [];
    while ($row = mysqli_fetch_assoc($hasil)) {
        $daftar[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $daftar;
}

function hapusSlipGaji(mysqli $conn, int $slipId, int $karyawanId): array
{
    if ($slipId <= 0 || $karyawanId <= 0) {
        throw new InvalidArgumentException('ID slip gaji atau ID karyawan tidak valid.');
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT s.id, s.nama_file, s.versi, s.nama_snapshot, p.bulan, p.tahun, s.karyawan_id
         FROM slip_gaji s
         INNER JOIN periode_gaji p ON p.id = s.periode_gaji_id
         WHERE s.id = ? AND s.karyawan_id = ?"
    );
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan query data slip gaji.');
    }
    mysqli_stmt_bind_param($stmt, 'ii', $slipId, $karyawanId);
    mysqli_stmt_execute($stmt);
    $slip = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$slip) {
        throw new RuntimeException('Data slip gaji tidak ditemukan atau sudah dihapus.');
    }

    mysqli_begin_transaction($conn);
    try {
        $stmtItems = mysqli_prepare($conn, "DELETE FROM slip_gaji_items WHERE slip_gaji_id = ?");
        if ($stmtItems) {
            mysqli_stmt_bind_param($stmtItems, 'i', $slipId);
            mysqli_stmt_execute($stmtItems);
            mysqli_stmt_close($stmtItems);
        }

        $stmtDel = mysqli_prepare($conn, "DELETE FROM slip_gaji WHERE id = ? AND karyawan_id = ?");
        if (!$stmtDel) {
            throw new RuntimeException('Gagal menyiapkan statement penghapusan slip gaji.');
        }
        mysqli_stmt_bind_param($stmtDel, 'ii', $slipId, $karyawanId);
        mysqli_stmt_execute($stmtDel);
        $terhapus = mysqli_stmt_affected_rows($stmtDel);
        mysqli_stmt_close($stmtDel);

        if ($terhapus < 1) {
            throw new RuntimeException('Data slip gaji gagal dihapus dari database.');
        }

        mysqli_commit($conn);

        if (!empty($slip['nama_file'])) {
            $path = __DIR__ . '/../../../storage/uploads/slip/' . basename((string) $slip['nama_file']);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return $slip;
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

