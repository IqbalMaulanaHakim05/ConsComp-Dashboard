<?php

require "koneksi.php";
require_once "sinkronisasi.php";

$pesan = "";
$tipePesan = "info";
$folderData = __DIR__ . DIRECTORY_SEPARATOR . "data";
$hasilSinkronisasi = [];

function ambilDaftarDataset(string $folder): array
{
    $daftar = [];
    $fileCsv = glob($folder . DIRECTORY_SEPARATOR . "*.csv") ?: [];

    foreach ($fileCsv as $lokasi) {
        if (!is_file($lokasi) || !is_readable($lokasi)) {
            continue;
        }

        $daftar[] = [
            "nama" => basename($lokasi),
            "ukuran" => filesize($lokasi) ?: 0,
            "diubah" => filemtime($lokasi) ?: 0,
        ];
    }

    usort($daftar, static fn(array $a, array $b): int => strcasecmp($a["nama"], $b["nama"]));
    return $daftar;
}

function formatUkuran(int $byte): string
{
    if ($byte >= 1048576) {
        return number_format($byte / 1048576, 2, ",", ".") . " MB";
    }
    if ($byte >= 1024) {
        return number_format($byte / 1024, 2, ",", ".") . " KB";
    }
    return $byte . " byte";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $hasilSinkronisasi = sinkronkanSemuaDataset($conn, $folderData);
        $jumlahBaris = $hasilSinkronisasi[0]["jumlah_data"] ?? 0;
        $jumlahBerubah = count(array_filter(
            $hasilSinkronisasi,
            static fn(array $hasil): bool => $hasil["berubah"]
        ));

        $pesan = "Sinkronisasi selesai. "
            . count($hasilSinkronisasi) . " file diperiksa, "
            . $jumlahBerubah . " file diperbarui, dan "
            . $jumlahBaris . " baris mengikuti data SQL.";
        $tipePesan = "success";
    } catch (Throwable $error) {
        $pesan = $error->getMessage();
        $tipePesan = "error";
    }
}

$daftarDataset = ambilDaftarDataset($folderData);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinkronisasi Dataset Lokal</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 40px 20px; font-family: Arial, sans-serif; color: #1e293b; background: #f3f4f6; }
        .container { max-width: 760px; margin: auto; padding: 28px; background: #fff; border-radius: 12px; box-shadow: 0 8px 25px rgba(15,23,42,.08); }
        h2 { margin-top: 0; }
        .description { color: #64748b; line-height: 1.65; }
        .warning { padding: 14px; margin: 18px 0; color: #92400e; background: #fef3c7; border-radius: 8px; }
        .pesan { padding: 13px 15px; margin: 18px 0; border-radius: 8px; }
        .pesan.success { color: #166534; background: #dcfce7; }
        .pesan.error { color: #991b1b; background: #fee2e2; }
        table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        th, td { padding: 11px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        button, .btn { display: inline-block; padding: 11px 16px; border: 0; border-radius: 7px; text-decoration: none; cursor: pointer; }
        button { color: #fff; background: #2563eb; }
        .btn { color: #334155; background: #e2e8f0; }
    </style>
</head>
<body>
<div class="container">
    <h2>Sinkronisasi SQL → Dataset Lokal</h2>
    <p class="description">
        Database SQL menjadi sumber data utama. Seluruh file CSV pada folder
        <code>data/</code> akan disamakan dengan tabel <code>karyawan</code>.
    </p>

    <div class="warning">
        Baris yang hanya ada pada CSV akan dihapus. Data yang hilang atau kosong
        pada CSV akan diisi kembali dari SQL. Perubahan manual pada CSV tidak akan
        dimasukkan ke database.
    </div>

    <?php if ($pesan !== ""): ?>
        <div class="pesan <?= htmlspecialchars($tipePesan); ?>">
            <?= htmlspecialchars($pesan); ?>
        </div>
    <?php endif; ?>

    <h3>File lokal yang dikelola</h3>
    <?php if ($daftarDataset !== []): ?>
        <table>
            <thead><tr><th>Nama file</th><th>Ukuran</th><th>Terakhir diubah</th></tr></thead>
            <tbody>
            <?php foreach ($daftarDataset as $dataset): ?>
                <tr>
                    <td><?= htmlspecialchars($dataset["nama"]); ?></td>
                    <td><?= htmlspecialchars(formatUkuran((int) $dataset["ukuran"])); ?></td>
                    <td><?= date("d-m-Y H:i:s", (int) $dataset["diubah"]); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Belum ada file CSV. Sistem akan membuat <code>data/karyawan.csv</code>.</p>
    <?php endif; ?>

    <form method="POST">
        <button type="submit">Sinkronkan Sekarang</button>
        <a class="btn" href="index.php">Kembali ke Dashboard</a>
    </form>
</div>
</body>
</html>
