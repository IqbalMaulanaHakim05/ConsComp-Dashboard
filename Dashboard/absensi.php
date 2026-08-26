<?php
declare(strict_types=1);

require __DIR__ . '/koneksi.php';
require_once __DIR__ . '/fungsi/auth.php';
require_once __DIR__ . '/fungsi/audit.php';
require_once __DIR__ . '/fungsi/xlsx-builder.php';

wajibRole('pic', 'koordinator', 'manager', 'admin', 'superadmin');

function siapkanTabelAbsensi(mysqli $conn): bool
{
    return mysqli_query($conn, "CREATE TABLE IF NOT EXISTS absensi_karyawan (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        karyawan_id INT NULL,
        nama_karyawan VARCHAR(255) NOT NULL,
        jam_fingerprint_masuk DATETIME NOT NULL,
        jam_fingerprint_keluar DATETIME NOT NULL,
        diimpor_oleh INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_absensi_nama_fingerprint (nama_karyawan, jam_fingerprint_masuk, jam_fingerprint_keluar),
        KEY idx_absensi_karyawan (karyawan_id),
        CONSTRAINT fk_absensi_karyawan FOREIGN KEY (karyawan_id) REFERENCES karyawan(id) ON DELETE CASCADE,
        CONSTRAINT fk_absensi_pengimpor FOREIGN KEY (diimpor_oleh) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4") === true;
}

function kolomTabelAbsensiAda(mysqli $conn, string $namaKolom): bool
{
    $namaKolom = mysqli_real_escape_string($conn, $namaKolom);
    $hasil = mysqli_query($conn, "SHOW COLUMNS FROM absensi_karyawan LIKE '{$namaKolom}'");
    return $hasil !== false && mysqli_num_rows($hasil) > 0;
}

function indeksTabelAbsensiAda(mysqli $conn, string $namaIndeks): bool
{
    $namaIndeks = mysqli_real_escape_string($conn, $namaIndeks);
    $hasil = mysqli_query($conn, "SHOW INDEX FROM absensi_karyawan WHERE Key_name = '{$namaIndeks}'");
    return $hasil !== false && mysqli_num_rows($hasil) > 0;
}

function siapkanKolomNamaAbsensi(mysqli $conn): bool
{
    if (!kolomTabelAbsensiAda($conn, 'nama_karyawan')
        && !mysqli_query($conn, 'ALTER TABLE absensi_karyawan ADD COLUMN nama_karyawan VARCHAR(255) NULL AFTER karyawan_id')) {
        return false;
    }
    if (!mysqli_query($conn, 'ALTER TABLE absensi_karyawan MODIFY karyawan_id INT NULL')) return false;
    mysqli_query($conn, 'UPDATE absensi_karyawan a INNER JOIN karyawan k ON k.id = a.karyawan_id SET a.nama_karyawan = k.employee_name WHERE a.nama_karyawan IS NULL OR a.nama_karyawan = \'\'');
    if (!indeksTabelAbsensiAda($conn, 'uniq_absensi_nama_fingerprint')
        && !mysqli_query($conn, 'ALTER TABLE absensi_karyawan ADD UNIQUE KEY uniq_absensi_nama_fingerprint (nama_karyawan, jam_fingerprint_masuk, jam_fingerprint_keluar)')) {
        return false;
    }
    return true;
}

function normalisasiHeaderAbsensi(string $nilai): string
{
    return preg_replace('/[^a-z0-9]+/', '', strtolower(trim($nilai))) ?? '';
}

function bacaCsvAbsensi(string $lokasi): array
{
    $file = fopen($lokasi, 'rb');
    if ($file === false) throw new RuntimeException('File CSV tidak dapat dibuka.');
    $baris = [];
    while (($data = fgetcsv($file)) !== false) $baris[] = array_map(static fn ($nilai) => trim((string) $nilai), $data);
    fclose($file);
    return $baris;
}

function indexKolomXlsxAbsensi(string $referensi): int
{
    preg_match('/^[A-Z]+/i', $referensi, $cocok);
    $hasil = 0;
    foreach (str_split(strtoupper($cocok[0] ?? 'A')) as $huruf) $hasil = $hasil * 26 + ord($huruf) - 64;
    return max(0, $hasil - 1);
}

function bacaXlsxAbsensi(string $lokasi): array
{
    if (!class_exists('ZipArchive')) throw new RuntimeException('Ekstensi PHP ZipArchive diperlukan untuk membaca file XLSX.');
    $zip = new ZipArchive();
    if ($zip->open($lokasi) !== true) throw new RuntimeException('File XLSX tidak dapat dibuka.');
    $strings = [];
    $shared = $zip->getFromName('xl/sharedStrings.xml');
    if ($shared !== false && ($xmlShared = simplexml_load_string($shared))) {
        $xmlShared->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($xmlShared->xpath('//s:si') ?: [] as $item) {
            $item->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $strings[] = implode('', array_map('strval', $item->xpath('.//s:t') ?: []));
        }
    }
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheet === false || !($xml = simplexml_load_string($sheet))) throw new RuntimeException('Worksheet pertama tidak valid.');
    $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $hasil = [];
    foreach ($xml->xpath('//s:sheetData/s:row') ?: [] as $row) {
        $row->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'); $data = [];
        foreach ($row->xpath('./s:c') ?: [] as $sel) {
            $sel->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $attr = $sel->attributes(); $index = indexKolomXlsxAbsensi((string) ($attr['r'] ?? 'A1'));
            $jenis = (string) ($attr['t'] ?? ''); $value = '';
            if ($jenis === 'inlineStr') { $node = $sel->xpath('./s:is/s:t'); $value = isset($node[0]) ? (string) $node[0] : ''; }
            else { $node = $sel->xpath('./s:v'); $raw = isset($node[0]) ? (string) $node[0] : ''; $value = $jenis === 's' ? ($strings[(int) $raw] ?? '') : $raw; }
            $data[$index] = trim($value);
        }
        if ($data !== []) $hasil[] = array_replace(array_fill(0, max(array_keys($data)) + 1, ''), $data);
    }
    return $hasil;
}

function waktuAbsensi(string $nilai): ?string
{
    $nilai = trim($nilai);
    if ($nilai === '') return null;
    if (is_numeric($nilai)) {
        if ((float) $nilai >= 0 && (float) $nilai < 1) {
            return date('Y-m-d ') . gmdate('H:i:s', (int) round((float) $nilai * 86400));
        }
        $detik = (int) round(((float) $nilai - 25569) * 86400);
        return gmdate('Y-m-d H:i:s', $detik);
    }
    $timestamp = strtotime(str_replace('/', '-', $nilai));
    if ($timestamp === false && preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $nilai)) $timestamp = strtotime(date('Y-m-d') . ' ' . $nilai);
    return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
}

if (!siapkanTabelAbsensi($conn) || !siapkanKolomNamaAbsensi($conn)) exit('Tabel absensi tidak dapat disiapkan.');
$pesan = '';
$cakupanDepartemen = departmentIdPengguna();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'impor') {
    if (!csrfValid($_POST['csrf_token'] ?? null)) $pesan = 'Token keamanan tidak valid.';
    elseif (!isset($_FILES['file_absensi']) || ($_FILES['file_absensi']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) $pesan = 'Pilih file Excel atau CSV yang valid.';
    else {
        try {
            $file = $_FILES['file_absensi']; $nama = strtolower((string) $file['name']);
            $baris = str_ends_with($nama, '.csv') ? bacaCsvAbsensi((string) $file['tmp_name']) : bacaXlsxAbsensi((string) $file['tmp_name']);
            $header = $baris[0] ?? []; $kolom = [];
            $peta = ['namakaryawan' => 'nama', 'jamfingerprintmasuk' => 'masuk', 'jamfingerprintkeluar' => 'keluar'];
            foreach ($header as $i => $nilai) { $key = normalisasiHeaderAbsensi((string) $nilai); if (isset($peta[$key])) $kolom[$peta[$key]] = $i; }
            if (count($kolom) !== 3) throw new RuntimeException('Header wajib: Nama Karyawan, Jam Fingerprint Masuk, Jam Fingerprint Keluar.');
            $berhasil = 0; $barisKe = 1;
            mysqli_begin_transaction($conn);
            $simpan = mysqli_prepare($conn, 'INSERT IGNORE INTO absensi_karyawan (nama_karyawan, jam_fingerprint_masuk, jam_fingerprint_keluar, diimpor_oleh) VALUES (?, ?, ?, ?)');
            foreach (array_slice($baris, 1) as $row) {
                $barisKe++; $namaKaryawan = trim((string) ($row[$kolom['nama']] ?? '')); $masuk = waktuAbsensi((string) ($row[$kolom['masuk']] ?? '')); $keluar = waktuAbsensi((string) ($row[$kolom['keluar']] ?? ''));
                if ($namaKaryawan === '' && $masuk === null && $keluar === null) continue;
                if ($namaKaryawan === '' || $masuk === null || $keluar === null || $keluar <= $masuk) throw new RuntimeException("Baris {$barisKe} tidak valid. Pastikan nama dan kedua jam fingerprint terisi, dengan jam keluar setelah masuk.");
                $pengimpor = (int) $_SESSION['user']['id']; mysqli_stmt_bind_param($simpan, 'sssi', $namaKaryawan, $masuk, $keluar, $pengimpor); mysqli_stmt_execute($simpan); $berhasil += mysqli_stmt_affected_rows($simpan);
            }
            mysqli_stmt_close($simpan); mysqli_commit($conn);
            catatAktivitas($conn, "Mengimpor {$berhasil} data absensi.");
            header('Location: absensi.php?pesan=' . urlencode("{$berhasil} data absensi berhasil diimpor.")); exit;
        } catch (Throwable $e) { mysqli_rollback($conn); $pesan = $e->getMessage(); }
    }
}

$hasil = mysqli_query($conn, "SELECT a.id, COALESCE(NULLIF(a.nama_karyawan, ''), k.employee_name) AS employee_name, a.jam_fingerprint_masuk, a.jam_fingerprint_keluar FROM absensi_karyawan a LEFT JOIN karyawan k ON k.id = a.karyawan_id ORDER BY a.jam_fingerprint_masuk DESC, a.id DESC");
if (isset($_GET['export'])) {
    $barisExport = []; while ($row = mysqli_fetch_assoc($hasil)) $barisExport[] = [$row['employee_name'], $row['jam_fingerprint_masuk'], $row['jam_fingerprint_keluar']];
    unduhSpreadsheetXlsx('absensi-' . date('Y-m-d'), 'Absensi', ['Nama Karyawan', 'Jam Fingerprint Masuk', 'Jam Fingerprint Keluar'], $barisExport);
}
$judulHalaman = 'Absensi'; $subjudulHalaman = 'Impor dan ekspor data fingerprint karyawan.'; $halamanAktif = 'absensi';
require __DIR__ . '/partials/atas.php';
?>
<?php if ($pesan !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
<section class="form-card absensi-import-card"><div class="form-card-header"><h2>Impor Absensi</h2><p>Gunakan Excel (.xlsx) atau CSV dengan tiga kolom: Nama Karyawan, Jam Fingerprint Masuk, dan Jam Fingerprint Keluar. Nama dari file dapat diimpor langsung tanpa harus terdaftar di Data Karyawan.</p></div><div class="form-body"><form method="POST" enctype="multipart/form-data" class="absensi-import-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="impor"><label class="absensi-file-field"><span>Pilih file absensi</span><input type="file" name="file_absensi" accept=".xlsx,.csv" required></label><div class="absensi-import-actions"><button class="btn btn-primary" type="submit">Impor File</button><a class="btn btn-success" href="absensi.php?export=1">Export Excel</a></div></form></div></section>
<section class="data-card"><div class="data-card-header"><h2>Data Absensi</h2></div><div class="table-wrapper no-actions absensi-table-wrapper"><table><thead><tr><th>Nama Karyawan</th><th>Jam Fingerprint Masuk</th><th>Jam Fingerprint Keluar</th></tr></thead><tbody><?php if ($hasil && mysqli_num_rows($hasil) > 0): while ($row = mysqli_fetch_assoc($hasil)): ?><tr><td><?= htmlspecialchars($row['employee_name']); ?></td><td><?= htmlspecialchars($row['jam_fingerprint_masuk']); ?></td><td><?= htmlspecialchars($row['jam_fingerprint_keluar']); ?></td></tr><?php endwhile; else: ?><tr><td colspan="3" class="empty-table">Belum ada data absensi.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require __DIR__ . '/partials/bawah.php'; ?>
