<?php
declare(strict_types=1);
require dirname(__DIR__) . "/koneksi.php";
require_once __DIR__ . "/auth.php";
wajibRole("admin", "superadmin");
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrfValid($_POST["csrf_token"] ?? null)) { http_response_code(403); exit("Permintaan tidak valid."); }
$id = (int) ($_POST["id"] ?? 0);
$periodeBulan = max(1, min(12, (int) ($_POST["bulan"] ?? date("n"))));
$periodeTahun = max(2000, min(2100, (int) ($_POST["tahun"] ?? date("Y"))));
$stmt = mysqli_prepare($conn, "SELECT k.emp_id, k.employee_name, k.position, k.kontak, pg.gaji_pokok, pg.uang_makan FROM karyawan k LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE k.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id); mysqli_stmt_execute($stmt); $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); mysqli_stmt_close($stmt);
if (!$data) { http_response_code(404); exit("Data karyawan tidak ditemukan."); }
$items = [["Gaji Pokok", (float) ($data["gaji_pokok"] ?? 0)], ["Uang Makan", (float) ($data["uang_makan"] ?? 0)]];
$tambahan = mysqli_query($conn, "SELECT nama, nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id = " . $id . " ORDER BY id ASC");
if ($tambahan) while ($item = mysqli_fetch_assoc($tambahan)) $items[] = [(string) $item["nama"], (float) $item["nilai"]];
$total = 0.0; foreach ($items as $item) $total += $item[1];
$lembur = mysqli_query($conn, "SELECT SUM(oc.jumlah_upah) AS total FROM overtime_reports o INNER JOIN overtime_compensations oc ON oc.overtime_id = o.id WHERE o.karyawan_id = " . $id . " AND o.status IN ('disetujui', 'selesai') AND MONTH(o.mulai_at) = " . $periodeBulan . " AND YEAR(o.mulai_at) = " . $periodeTahun);
$totalLembur = (float) ((mysqli_fetch_assoc($lembur)["total"] ?? 0));
if ($totalLembur > 0) { $items[] = ["Upah Lembur", $totalLembur]; $total += $totalLembur; }
$potonganItems = [];
$totalPotongan = 0.0;
$hasilPotongan = mysqli_query($conn, "SELECT nama, nilai FROM potongan_karyawan WHERE karyawan_id = " . $id . " ORDER BY id ASC");
if ($hasilPotongan) while ($itemPotongan = mysqli_fetch_assoc($hasilPotongan)) { $nilaiPotongan = (float) $itemPotongan["nilai"]; $potonganItems[] = [(string) $itemPotongan["nama"], $nilaiPotongan]; $totalPotongan += $nilaiPotongan; }
$jumlahDiterima = $total - $totalPotongan;
$autoload = dirname(__DIR__, 2) . "/vendor/autoload.php"; if (!is_file($autoload)) exit("Dependensi PDF belum terpasang."); require_once $autoload;
$html = '<style>body{font-family:DejaVu Sans,sans-serif;color:#111;font-size:12px}.header{border-bottom:2px solid #111;padding-bottom:14px}h1{text-align:center;font-size:25px;letter-spacing:2px;margin:0 0 15px}.meta{width:100%}.meta td{padding:3px}.identity{margin:16px 0;line-height:1.8}.salary{width:100%;border-collapse:collapse;border:1px solid #111}.salary th,.salary td{padding:8px;border:1px solid #111}.salary th{text-align:center;font-size:14px}.amount{text-align:right}.total{font-weight:bold;font-size:13px}.received{text-align:right;font-size:19px;font-weight:bold;margin-top:35px;border-bottom:1px solid #111;padding-bottom:8px}</style><div class="header"><h1>SLIP GAJI</h1><table class="meta"><tr><td>Dicetak tanggal</td><td>: ' . date("d F Y") . '</td><td>ID Karyawan</td><td>: ' . htmlspecialchars($data["emp_id"]) . '</td></tr><tr><td>Kontak</td><td>: ' . htmlspecialchars((string) ($data["kontak"] ?: "-")) . '</td><td></td><td></td></tr></table></div><div class="identity">Nama: ' . htmlspecialchars($data["employee_name"]) . '<br>Posisi: ' . htmlspecialchars($data["position"]) . '</div><table class="salary"><thead><tr><th colspan="2">PENDAPATAN</th><th colspan="2">POTONGAN</th></tr></thead><tbody>';
 $jumlahBaris = max(count($items), count($potonganItems)); for ($i = 0; $i < $jumlahBaris; $i++) { $pendapatan = $items[$i] ?? ["", null]; $potongan = $potonganItems[$i] ?? ["", null]; $html .= '<tr><td>' . htmlspecialchars($pendapatan[0]) . '</td><td class="amount">' . ($pendapatan[1] === null ? "" : "Rp " . number_format($pendapatan[1], 0, ",", ".")) . '</td><td>' . htmlspecialchars($potongan[0]) . '</td><td class="amount">' . ($potongan[1] === null ? "" : "Rp " . number_format($potongan[1], 0, ",", ".")) . '</td></tr>'; }
$html .= '<tr><td class="total">Total Pendapatan</td><td class="amount total">Rp ' . number_format($total, 0, ",", ".") . '</td><td class="total">Total Potongan</td><td class="amount total">Rp ' . number_format($totalPotongan, 0, ",", ".") . '</td></tr></tbody></table><div class="received">Jumlah Diterima: Rp ' . number_format($jumlahDiterima, 0, ",", ".") . '</div>';
$options = new \Dompdf\Options(); $options->set("defaultFont", "DejaVu Sans"); $dompdf = new \Dompdf\Dompdf($options); $dompdf->loadHtml($html, "UTF-8"); $dompdf->setPaper("A4", "portrait"); $dompdf->render(); $pdf = $dompdf->output();
if (!str_starts_with($pdf, "%PDF")) { http_response_code(500); exit("PDF gagal dibuat."); }
header("Content-Type: application/pdf"); header("Content-Disposition: inline; filename=slip-gaji-" . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $data["emp_id"]) . "-" . date("Y-m") . ".pdf"); echo $pdf;
