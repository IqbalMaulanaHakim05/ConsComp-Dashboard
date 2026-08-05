<?php
require __DIR__ . "/../koneksi.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";
require_once __DIR__ . "/sinkronisasi.php";

wajibRole("admin", "superadmin");

$pesan = "";
$tipePesan = "error";

function normalisasiHeaderExcel(string $nilai): string
{
    $nilai = strtolower(trim($nilai));
    return preg_replace('/[^a-z0-9]+/', '', $nilai) ?? '';
}

function petaHeaderExcel(): array
{
    return [
        'idkaryawan' => 'emp_id',
        'empid' => 'emp_id',
        'nama' => 'employee_name',
        'namakaryawan' => 'employee_name',
        'employeename' => 'employee_name',
        'posisi' => 'position',
        'position' => 'position',
        'departemen' => 'department',
        'department' => 'department',
        'gaji' => 'salary',
        'salary' => 'salary',
        'jeniskelamin' => 'gender',
        'sex' => 'gender',
        'gender' => 'gender',
        'statuspernikahan' => 'marital_status',
        'maritaldesc' => 'marital_status',
        'tanggalmasuk' => 'date_of_hire',
        'dateofhire' => 'date_of_hire',
        'statuskerja' => 'employment_status',
        'employmentstatus' => 'employment_status',
        'skorperforma' => 'performance_score',
        'performancescore' => 'performance_score',
    ];
}

function kolomExcelKeIndex(string $referensi): int
{
    preg_match('/^[A-Z]+/i', $referensi, $cocok);
    $huruf = strtoupper($cocok[0] ?? 'A');
    $index = 0;
    foreach (str_split($huruf) as $karakter) {
        $index = ($index * 26) + (ord($karakter) - 64);
    }
    return max(0, $index - 1);
}

function bacaXlsx(string $lokasi): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Ekstensi PHP ZipArchive diperlukan untuk membaca file .xlsx.');
    }

    $zip = new ZipArchive();
    if ($zip->open($lokasi) !== true) {
        throw new RuntimeException('File XLSX tidak dapat dibuka.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml);
        if ($xml !== false) {
            $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($xml->xpath('//s:si') ?: [] as $item) {
                $item->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $bagian = [];
                foreach ($item->xpath('.//s:t') ?: [] as $teks) {
                    $bagian[] = (string) $teks;
                }
                $sharedStrings[] = implode('', $bagian);
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetXml === false) {
        throw new RuntimeException('Worksheet pertama tidak ditemukan dalam file XLSX.');
    }

    $xml = simplexml_load_string($sheetXml);
    if ($xml === false) {
        throw new RuntimeException('Isi worksheet XLSX tidak valid.');
    }

    $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $hasil = [];
    foreach ($xml->xpath('//s:sheetData/s:row') ?: [] as $row) {
        $row->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $baris = [];
        foreach ($row->xpath('./s:c') ?: [] as $cell) {
            $cell->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $atribut = $cell->attributes();
            $index = kolomExcelKeIndex((string) ($atribut['r'] ?? 'A1'));
            $tipe = (string) ($atribut['t'] ?? '');
            $nilai = '';

            if ($tipe === 'inlineStr') {
                $teks = $cell->xpath('./s:is/s:t');
                $nilai = isset($teks[0]) ? (string) $teks[0] : '';
            } else {
                $nodeNilai = $cell->xpath('./s:v');
                $nilaiMentah = isset($nodeNilai[0]) ? (string) $nodeNilai[0] : '';
                $nilai = $tipe === 's'
                    ? ($sharedStrings[(int) $nilaiMentah] ?? '')
                    : $nilaiMentah;
            }
            $baris[$index] = trim($nilai);
        }
        if ($baris !== []) {
            $maks = max(array_keys($baris));
            $hasil[] = array_replace(array_fill(0, $maks + 1, ''), $baris);
        }
    }
    return $hasil;
}

function bacaXlsXml(string $lokasi): array
{
    $isi = file_get_contents($lokasi);
    if ($isi === false) {
        throw new RuntimeException('File Excel tidak dapat dibaca.');
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($isi);
    if ($xml === false) {
        throw new RuntimeException('Format .xls harus berupa Excel XML seperti hasil Export Excel aplikasi.');
    }

    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
    $hasil = [];
    foreach ($xml->xpath('//ss:Worksheet[1]/ss:Table/ss:Row') ?: [] as $row) {
        $row->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
        $baris = [];
        $posisi = 0;
        foreach ($row->xpath('./ss:Cell') ?: [] as $cell) {
            $cell->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
            $atribut = $cell->attributes('urn:schemas-microsoft-com:office:spreadsheet');
            if (isset($atribut['Index'])) {
                $posisi = ((int) $atribut['Index']) - 1;
            }
            $data = $cell->xpath('./ss:Data');
            $baris[$posisi] = isset($data[0]) ? trim((string) $data[0]) : '';
            $posisi++;
        }
        if ($baris !== []) {
            $maks = max(array_keys($baris));
            $hasil[] = array_replace(array_fill(0, $maks + 1, ''), $baris);
        }
    }
    return $hasil;
}

function cariHeaderDanData(array $barisExcel): array
{
    $peta = petaHeaderExcel();
    foreach ($barisExcel as $indexBaris => $baris) {
        $kolom = [];
        foreach ($baris as $indexKolom => $judul) {
            $normal = normalisasiHeaderExcel((string) $judul);
            if (isset($peta[$normal])) {
                $kolom[$peta[$normal]] = $indexKolom;
            }
        }

        $wajib = ['emp_id', 'employee_name', 'position', 'department', 'salary', 'gender', 'employment_status', 'performance_score'];
        if (count(array_intersect($wajib, array_keys($kolom))) === count($wajib)) {
            return [$kolom, array_slice($barisExcel, $indexBaris + 1)];
        }
    }
    throw new RuntimeException('Header Excel tidak sesuai. Gunakan file hasil Export Excel aplikasi sebagai template.');
}

function nilaiKolom(array $baris, array $kolom, string $nama): string
{
    return trim((string) ($baris[$kolom[$nama] ?? -1] ?? ''));
}

function normalisasiGender(string $gender): string
{
    if (strcasecmp($gender, 'Laki-laki') === 0 || strcasecmp($gender, 'Male') === 0 || strtoupper($gender) === 'M') {
        return 'M';
    }
    if (strcasecmp($gender, 'Perempuan') === 0 || strcasecmp($gender, 'Female') === 0 || strtoupper($gender) === 'F') {
        return 'F';
    }
    return $gender;
}

function normalisasiTanggal(string $tanggal): ?string
{
    if ($tanggal === '') {
        return null;
    }
    if (is_numeric($tanggal)) {
        $timestamp = ((float) $tanggal - 25569) * 86400;
        return gmdate('Y-m-d', (int) $timestamp);
    }
    $timestamp = strtotime(str_replace('/', '-', $tanggal));
    return $timestamp === false ? null : date('Y-m-d', $timestamp);
}

function validasiDataExcel(array $barisExcel): array
{
    [$kolom, $barisData] = cariHeaderDanData($barisExcel);
    $dataValid = [];
    $idTerpakai = [];
    $nomorBaris = 1;

    foreach ($barisData as $baris) {
        $nomorBaris++;
        $nilaiGabungan = trim(implode('', array_map('strval', $baris)));
        if ($nilaiGabungan === '') {
            continue;
        }

        $empId = nilaiKolom($baris, $kolom, 'emp_id');
        $nama = nilaiKolom($baris, $kolom, 'employee_name');
        $posisi = nilaiKolom($baris, $kolom, 'position');
        $departemen = nilaiKolom($baris, $kolom, 'department');
        $gajiMentah = str_replace([',', ' '], ['', ''], nilaiKolom($baris, $kolom, 'salary'));
        $gender = normalisasiGender(nilaiKolom($baris, $kolom, 'gender'));
        $statusPernikahan = nilaiKolom($baris, $kolom, 'marital_status');
        $tanggalMentah = nilaiKolom($baris, $kolom, 'date_of_hire');
        $statusKerja = nilaiKolom($baris, $kolom, 'employment_status');
        $skorMentah = nilaiKolom($baris, $kolom, 'performance_score');

        if ($empId === '' || $nama === '' || $posisi === '' || $departemen === '' || $statusKerja === '') {
            throw new RuntimeException("Baris data ke-{$nomorBaris}: kolom wajib belum lengkap.");
        }
        if (isset($idTerpakai[$empId])) {
            throw new RuntimeException("ID karyawan {$empId} tercatat lebih dari sekali.");
        }
        if (!is_numeric($gajiMentah) || (float) $gajiMentah < 0) {
            throw new RuntimeException("Baris data ke-{$nomorBaris}: gaji tidak valid.");
        }
        if (!in_array($gender, ['M', 'F'], true)) {
            throw new RuntimeException("Baris data ke-{$nomorBaris}: jenis kelamin harus Laki-laki/M atau Perempuan/F.");
        }
        if (!ctype_digit($skorMentah) || (int) $skorMentah < 1 || (int) $skorMentah > 100) {
            throw new RuntimeException("Baris data ke-{$nomorBaris}: skor performa harus 1 sampai 100.");
        }
        $tanggal = normalisasiTanggal($tanggalMentah);
        if ($tanggalMentah !== '' && $tanggal === null) {
            throw new RuntimeException("Baris data ke-{$nomorBaris}: tanggal masuk tidak valid.");
        }

        $idTerpakai[$empId] = true;
        $dataValid[] = [
            'employee_name' => $nama,
            'emp_id' => $empId,
            'position' => $posisi,
            'department' => $departemen,
            'salary' => (float) $gajiMentah,
            'gender' => $gender,
            'marital_status' => $statusPernikahan,
            'date_of_hire' => $tanggal,
            'employment_status' => $statusKerja,
            'performance_score' => (int) $skorMentah,
        ];
    }

    if ($dataValid === []) {
        throw new RuntimeException('File Excel tidak memiliki data karyawan.');
    }
    return $dataValid;
}

function gantiDataSql(mysqli $conn, array $data): void
{
    mysqli_begin_transaction($conn);
    try {
        if (!mysqli_query($conn, 'DELETE FROM karyawan')) {
            throw new RuntimeException('Data lama gagal dikosongkan: ' . mysqli_error($conn));
        }

        $sql = "INSERT INTO karyawan (
                    employee_name, emp_id, position, department, salary,
                    gender, marital_status, date_of_hire, employment_status, performance_score
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new RuntimeException('Query import gagal disiapkan: ' . mysqli_error($conn));
        }

        foreach ($data as $item) {
            $nama = $item['employee_name'];
            $empId = $item['emp_id'];
            $posisi = $item['position'];
            $departemen = $item['department'];
            $gaji = $item['salary'];
            $gender = $item['gender'];
            $statusPernikahan = $item['marital_status'];
            $tanggalMasuk = $item['date_of_hire'];
            $statusKerja = $item['employment_status'];
            $skor = (string) $item['performance_score'];

            mysqli_stmt_bind_param(
                $stmt,
                'ssssdsssss',
                $nama,
                $empId,
                $posisi,
                $departemen,
                $gaji,
                $gender,
                $statusPernikahan,
                $tanggalMasuk,
                $statusKerja,
                $skor
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('Baris ' . $empId . ' gagal disimpan: ' . mysqli_stmt_error($stmt));
            }
        }
        mysqli_stmt_close($stmt);
        mysqli_commit($conn);
    } catch (Throwable $error) {
        mysqli_rollback($conn);
        throw $error;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Pilih file Excel yang akan diimpor.');
        }
        if ((int) $_FILES['file_excel']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Ukuran file maksimal 5 MB.');
        }

        $namaFile = (string) $_FILES['file_excel']['name'];
        $ekstensi = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
        if (!in_array($ekstensi, ['xls', 'xlsx'], true)) {
            throw new RuntimeException('File harus berformat .xls atau .xlsx.');
        }

        $lokasi = (string) $_FILES['file_excel']['tmp_name'];
        $barisExcel = $ekstensi === 'xlsx' ? bacaXlsx($lokasi) : bacaXlsXml($lokasi);
        $dataValid = validasiDataExcel($barisExcel);
        gantiDataSql($conn, $dataValid);
        sinkronkanSemuaDataset($conn);
        catatAktivitas($conn, "Mengimpor " . count($dataValid) . " data karyawan dari Excel.");

        header('Location: ../karyawan.php?pesan=import-excel-berhasil&jumlah=' . count($dataValid));
        exit;
    } catch (Throwable $error) {
        $pesan = $error->getMessage();
    }
}

$judulHalaman = "Import Excel";
$subjudulHalaman = "Ganti seluruh data karyawan menggunakan file Excel.";
$halamanAktif = "karyawan";

require __DIR__ . "/../partials/atas.php";

?>
    <section class="form-card">
        <div class="form-card-header">
            <h2>Unggah File Excel</h2>
            <p>
                Gunakan file hasil Export Excel sebagai template. File akan
                divalidasi terlebih dahulu sebelum database diubah.
            </p>
        </div>

        <div class="form-body">
            <div class="form-warning">
                <strong>Perhatian:</strong> proses ini mengganti seluruh isi
                tabel karyawan dengan data dari file Excel.
            </div>

            <?php if ($pesan !== ""): ?>
                <div class="alert-error" role="alert">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <form
                method="POST"
                enctype="multipart/form-data"
                onsubmit="return confirm('Yakin ingin mengganti seluruh data karyawan dari file Excel ini?');"
            >
                <div class="form-group full-width">
                    <label for="file_excel">
                        File Excel <span class="required">*</span>
                    </label>
                    <input
                        id="file_excel"
                        type="file"
                        name="file_excel"
                        accept=".xls,.xlsx"
                        required
                    >
                    <p class="field-note">Format .xls atau .xlsx, maksimal 5 MB. Skor performa wajib 1&ndash;100.</p>
                </div>

                <div class="form-actions">
                    <a href="<?= htmlspecialchars(URL_DASAR); ?>karyawan.php" class="btn btn-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-warning">
                        Import dan Ganti Data
                    </button>
                </div>
            </form>
        </div>
    </section>
<?php
require __DIR__ . "/../partials/bawah.php";
