<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("admin", "superadmin");
$id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT k.id, k.emp_id, k.employee_name, pg.id AS profil_id, COALESCE(pg.gaji_pokok, k.salary, 0) AS gaji_pokok, COALESCE(pg.uang_makan, 0) AS uang_makan, pg.berlaku_mulai FROM karyawan k LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE k.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$data) { http_response_code(404); exit("Data karyawan tidak ditemukan."); }
$jenisKomponen = [];
$hasilJenisKomponen = mysqli_query($conn, "SELECT id, kode, nama, kategori, metode FROM jenis_komponen_gaji ORDER BY kategori, nama");
while ($komponen = mysqli_fetch_assoc($hasilJenisKomponen)) $jenisKomponen[] = $komponen;
$jenisKomponenById = [];
$jenisKomponenByNama = ["pendapatan" => [], "potongan" => []];
foreach ($jenisKomponen as $komponen) {
    $jenisKomponenById[(int) $komponen["id"]] = $komponen;
    $jenisKomponenByNama[$komponen["kategori"]][mb_strtolower(trim((string) $komponen["nama"]))] = (int) $komponen["id"];
}
$nilaiKomponen = [];
if ($data["profil_id"] !== null) {
    $hasilKomponen = mysqli_query($conn, "SELECT jenis_komponen_id, nilai FROM komponen_gaji_karyawan WHERE profil_gaji_id = " . (int) $data["profil_id"]);
    while ($komponen = mysqli_fetch_assoc($hasilKomponen)) $nilaiKomponen[(int) $komponen["jenis_komponen_id"]] = (string) $komponen["nilai"];
}
$pendapatanTambahan = [];
$hasilPendapatan = mysqli_query($conn, "SELECT id, nama, nilai FROM pendapatan_tambahan_karyawan WHERE karyawan_id = " . $id . " ORDER BY id ASC");
while ($pendapatan = mysqli_fetch_assoc($hasilPendapatan)) $pendapatanTambahan[] = $pendapatan;
$potongan = [];
$hasilPotongan = mysqli_query($conn, "SELECT id, nama, nilai FROM potongan_karyawan WHERE karyawan_id = " . $id . " ORDER BY id ASC");
while ($itemPotongan = mysqli_fetch_assoc($hasilPotongan)) $potongan[] = $itemPotongan;
$namaPendapatanLama = [];
$namaPotonganLama = [];
foreach ($pendapatanTambahan as $pendapatan) $namaPendapatanLama[] = (string) $pendapatan["nama"];
foreach ($potongan as $itemPotongan) $namaPotonganLama[] = (string) $itemPotongan["nama"];
$hasilNamaPendapatan = mysqli_query($conn, "SELECT DISTINCT nama FROM pendapatan_tambahan_karyawan ORDER BY nama ASC");
while ($nama = mysqli_fetch_assoc($hasilNamaPendapatan)) if (!in_array((string) $nama["nama"], $namaPendapatanLama, true)) $namaPendapatanLama[] = (string) $nama["nama"];
$hasilNamaPotongan = mysqli_query($conn, "SELECT DISTINCT nama FROM potongan_karyawan ORDER BY nama ASC");
while ($nama = mysqli_fetch_assoc($hasilNamaPotongan)) if (!in_array((string) $nama["nama"], $namaPotonganLama, true)) $namaPotonganLama[] = (string) $nama["nama"];
$pendapatanLamaUntukForm = $pendapatanTambahan;
$potonganLamaUntukForm = $potongan;
$pendapatanTambahan = [];
$potongan = [];
$barisKomponenForm = ["pendapatan" => [], "potongan" => []];
foreach ($jenisKomponen as $komponen) {
    $jenisId = (int) $komponen["id"];
    $nilai = (float) ($nilaiKomponen[$jenisId] ?? 0);
    if ($nilai > 0) {
        $barisKomponenForm[$komponen["kategori"]][$jenisId] = ["nama" => (string) $komponen["nama"], "nilai" => $nilai];
    }
}
foreach ($pendapatanLamaUntukForm as $baris) {
    $nama = trim((string) $baris["nama"]); $nilai = (float) $baris["nilai"];
    $jenisId = $jenisKomponenByNama["pendapatan"][mb_strtolower($nama)] ?? 0;
    if ($jenisId > 0) $barisKomponenForm["pendapatan"][$jenisId] = ["nama" => $nama, "nilai" => (float) ($barisKomponenForm["pendapatan"][$jenisId]["nilai"] ?? 0) + $nilai];
    elseif ($nama !== "" && $nilai > 0) $pendapatanTambahan[] = ["nama" => $nama, "nilai" => $nilai];
}
foreach ($potonganLamaUntukForm as $baris) {
    $nama = trim((string) $baris["nama"]); $nilai = (float) $baris["nilai"];
    $jenisId = $jenisKomponenByNama["potongan"][mb_strtolower($nama)] ?? 0;
    if ($jenisId > 0) $barisKomponenForm["potongan"][$jenisId] = ["nama" => $nama, "nilai" => (float) ($barisKomponenForm["potongan"][$jenisId]["nilai"] ?? 0) + $nilai];
    elseif ($nama !== "" && $nilai > 0) $potongan[] = ["nama" => $nama, "nilai" => $nilai];
}
foreach ($barisKomponenForm["pendapatan"] as $jenisId => $baris) $pendapatanTambahan[] = ["nama" => $baris["nama"], "nilai" => $baris["nilai"], "jenis_id" => $jenisId];
foreach ($barisKomponenForm["potongan"] as $jenisId => $baris) $potongan[] = ["nama" => $baris["nama"], "nilai" => $baris["nilai"], "jenis_id" => $jenisId];

function ambilAtauBuatJenisKomponen(mysqli $conn, string $nama, string $kategori): int
{
    $cari = mysqli_prepare($conn, "SELECT id FROM jenis_komponen_gaji WHERE nama = ? AND kategori = ? LIMIT 1");
    mysqli_stmt_bind_param($cari, "ss", $nama, $kategori);
    mysqli_stmt_execute($cari);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($cari));
    mysqli_stmt_close($cari);
    if ($hasil) {
        $idKomponen = (int) $hasil["id"];
        $aktifkan = mysqli_prepare($conn, "UPDATE jenis_komponen_gaji SET is_active = 1 WHERE id = ?");
        mysqli_stmt_bind_param($aktifkan, "i", $idKomponen);
        mysqli_stmt_execute($aktifkan);
        mysqli_stmt_close($aktifkan);
        return $idKomponen;
    }

    $dasarKode = strtoupper($kategori === "pendapatan" ? "PEND_" : "POT_") . strtoupper((string) preg_replace('/[^A-Z0-9]+/i', "_", $nama));
    $dasarKode = trim(substr($dasarKode, 0, 45), "_");
    if ($dasarKode === "") $dasarKode = $kategori === "pendapatan" ? "PEND_KOMPONEN" : "POT_KOMPONEN";
    $kode = $dasarKode;
    $urutan = 2;
    do {
        $cekKode = mysqli_prepare($conn, "SELECT id FROM jenis_komponen_gaji WHERE kode = ? LIMIT 1");
        mysqli_stmt_bind_param($cekKode, "s", $kode);
        mysqli_stmt_execute($cekKode);
        $kodeAda = mysqli_fetch_assoc(mysqli_stmt_get_result($cekKode));
        mysqli_stmt_close($cekKode);
        if ($kodeAda) $kode = substr($dasarKode, 0, 45 - strlen((string) $urutan)) . "_" . $urutan++;
    } while ($kodeAda);

    $tambah = mysqli_prepare($conn, "INSERT INTO jenis_komponen_gaji (kode, nama, kategori, metode, is_active) VALUES (?, ?, ?, 'nominal', 1)");
    mysqli_stmt_bind_param($tambah, "sss", $kode, $nama, $kategori);
    if (!mysqli_stmt_execute($tambah)) { mysqli_stmt_close($tambah); throw new RuntimeException("Jenis komponen gagal dibuat."); }
    $idKomponen = (int) mysqli_insert_id($conn);
    mysqli_stmt_close($tambah);
    return $idKomponen;
}

$pesan = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $gaji = (float) ($_POST["gaji_pokok"] ?? -1);
    $makan = (float) ($_POST["uang_makan"] ?? -1);
    $berlakuMulai = trim((string) ($_POST["berlaku_mulai"] ?? date("Y-m-d")));
    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $pesan = "Token keamanan tidak valid.";
    } elseif ($gaji < 0 || $makan < 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $berlakuMulai)) {
        $pesan = "Nominal tidak boleh negatif.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $nilaiKomponenPost = [];
            foreach ((array) ($_POST["komponen"] ?? []) as $jenisId => $nilai) {
                $jenisId = (int) $jenisId; $nilai = (float) $nilai;
                if ($jenisId > 0 && isset($jenisKomponenById[$jenisId]) && $nilai > 0) $nilaiKomponenPost[$jenisId] = $nilai;
            }
            $pendapatanManualPost = [];
            $potonganManualPost = [];
            foreach (["pendapatan_tambahan" => "pendapatan", "potongan" => "potongan"] as $namaInput => $kategori) {
                foreach ((array) ($_POST[$namaInput] ?? []) as $baris) {
                    $nilaiBaris = (float) ($baris["nilai"] ?? 0);
                    if ($nilaiBaris <= 0) continue;
                    $pilihan = trim((string) ($baris["jenis_id"] ?? ""));
                    $namaBaris = "";
                    $jenisId = 0;
                    if (str_starts_with($pilihan, "jenis:")) {
                        $jenisId = (int) substr($pilihan, 6);
                        if (!isset($jenisKomponenById[$jenisId]) || $jenisKomponenById[$jenisId]["kategori"] !== $kategori) throw new RuntimeException("Jenis komponen tidak sesuai.");
                        $namaBaris = (string) $jenisKomponenById[$jenisId]["nama"];
                    } elseif (str_starts_with($pilihan, "manual:")) {
                        $namaBaris = trim((string) base64_decode(substr($pilihan, 7), true));
                        if ($kategori === "pendapatan" && !in_array($namaBaris, $namaPendapatanLama, true)) throw new RuntimeException("Nama pendapatan tidak tersedia.");
                        if ($kategori === "potongan" && !in_array($namaBaris, $namaPotonganLama, true)) throw new RuntimeException("Nama potongan tidak tersedia.");
                    } elseif ($pilihan === "baru") {
                        $namaBaris = trim((string) ($baris["nama_baru"] ?? ""));
                        if ($namaBaris !== "") $jenisId = ambilAtauBuatJenisKomponen($conn, $namaBaris, $kategori);
                    }
                    if ($namaBaris === "") throw new RuntimeException("Nama komponen belum diisi.");
                    if ($jenisId > 0) $nilaiKomponenPost[$jenisId] = $nilaiBaris;
                    elseif ($kategori === "pendapatan") $pendapatanManualPost[] = [$namaBaris, $nilaiBaris];
                    else $potonganManualPost[] = [$namaBaris, $nilaiBaris];
                }
            }
            if ($data["profil_id"] === null) {
                $insert = mysqli_prepare($conn, "INSERT INTO profil_gaji (karyawan_id, gaji_pokok, uang_makan, berlaku_mulai, created_by) VALUES (?, ?, ?, ?, ?)");
                $penggunaId = (int) ($_SESSION["user"]["id"] ?? 0);
                mysqli_stmt_bind_param($insert, "iddsi", $id, $gaji, $makan, $berlakuMulai, $penggunaId);
                mysqli_stmt_execute($insert);
                mysqli_stmt_close($insert);
            } else {
                $update = mysqli_prepare($conn, "UPDATE profil_gaji SET gaji_pokok = ?, uang_makan = ?, berlaku_mulai = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $profilId = (int) $data["profil_id"];
                mysqli_stmt_bind_param($update, "ddsi", $gaji, $makan, $berlakuMulai, $profilId);
                mysqli_stmt_execute($update);
                mysqli_stmt_close($update);
            }
            $profilIdAktif = $data["profil_id"] === null ? (int) mysqli_insert_id($conn) : (int) $data["profil_id"];
            $hapusKomponen = mysqli_prepare($conn, "DELETE FROM komponen_gaji_karyawan WHERE profil_gaji_id = ?");
            mysqli_stmt_bind_param($hapusKomponen, "i", $profilIdAktif); mysqli_stmt_execute($hapusKomponen); mysqli_stmt_close($hapusKomponen);
            $tambahKomponen = mysqli_prepare($conn, "INSERT INTO komponen_gaji_karyawan (profil_gaji_id, jenis_komponen_id, nilai) VALUES (?, ?, ?)");
            foreach ($nilaiKomponenPost as $jenisId => $nilai) {
                mysqli_stmt_bind_param($tambahKomponen, "iid", $profilIdAktif, $jenisId, $nilai); mysqli_stmt_execute($tambahKomponen);
            }
            mysqli_stmt_close($tambahKomponen);
            $hapusPendapatan = mysqli_prepare($conn, "DELETE FROM pendapatan_tambahan_karyawan WHERE karyawan_id = ?");
            mysqli_stmt_bind_param($hapusPendapatan, "i", $id); mysqli_stmt_execute($hapusPendapatan); mysqli_stmt_close($hapusPendapatan);
            $tambahPendapatan = mysqli_prepare($conn, "INSERT INTO pendapatan_tambahan_karyawan (karyawan_id, nama, nilai) VALUES (?, ?, ?)");
            foreach ($pendapatanManualPost as [$namaPendapatan, $nilaiPendapatan]) {
                mysqli_stmt_bind_param($tambahPendapatan, "isd", $id, $namaPendapatan, $nilaiPendapatan); mysqli_stmt_execute($tambahPendapatan);
            }
            mysqli_stmt_close($tambahPendapatan);
            $hapusPotongan = mysqli_prepare($conn, "DELETE FROM potongan_karyawan WHERE karyawan_id = ?");
            mysqli_stmt_bind_param($hapusPotongan, "i", $id); mysqli_stmt_execute($hapusPotongan); mysqli_stmt_close($hapusPotongan);
            $tambahPotongan = mysqli_prepare($conn, "INSERT INTO potongan_karyawan (karyawan_id, nama, nilai) VALUES (?, ?, ?)");
            foreach ($potonganManualPost as [$namaPotongan, $nilaiPotongan]) {
                mysqli_stmt_bind_param($tambahPotongan, "isd", $id, $namaPotongan, $nilaiPotongan); mysqli_stmt_execute($tambahPotongan);
            }
            mysqli_stmt_close($tambahPotongan);
            mysqli_commit($conn);
            catatAktivitas($conn, "Mengubah profil upah karyawan " . $data["emp_id"] . ".");
            header("Location: upah.php");
            exit;
        } catch (Throwable $exception) {
            mysqli_rollback($conn);
            $pesan = "Perubahan upah gagal disimpan.";
        }
    }
    $data["gaji_pokok"] = (string) $gaji;
    $data["uang_makan"] = (string) $makan;
}

$judulHalaman = "Edit Upah";
$subjudulHalaman = "Perbarui nominal profil upah karyawan.";
$halamanAktif = "upah";
$opsiPendapatan = '<option value="">Pilih nama pendapatan</option>';
foreach ($jenisKomponen as $komponen) if ($komponen["kategori"] === "pendapatan") $opsiPendapatan .= '<option value="jenis:' . (int) $komponen["id"] . '">' . htmlspecialchars((string) $komponen["nama"], ENT_QUOTES, "UTF-8") . '</option>';
foreach ($namaPendapatanLama as $namaLama) if (!isset($jenisKomponenByNama["pendapatan"][mb_strtolower(trim($namaLama))])) $opsiPendapatan .= '<option value="manual:' . base64_encode($namaLama) . '">' . htmlspecialchars($namaLama, ENT_QUOTES, "UTF-8") . '</option>';
$opsiPendapatan .= '<option value="baru">Isi Pendapatan</option>';
$opsiPotongan = '<option value="">Pilih nama potongan</option>';
foreach ($jenisKomponen as $komponen) if ($komponen["kategori"] === "potongan") $opsiPotongan .= '<option value="jenis:' . (int) $komponen["id"] . '">' . htmlspecialchars((string) $komponen["nama"], ENT_QUOTES, "UTF-8") . '</option>';
foreach ($namaPotonganLama as $namaLama) if (!isset($jenisKomponenByNama["potongan"][mb_strtolower(trim($namaLama))])) $opsiPotongan .= '<option value="manual:' . base64_encode($namaLama) . '">' . htmlspecialchars($namaLama, ENT_QUOTES, "UTF-8") . '</option>';
$opsiPotongan .= '<option value="baru">Isi Potongan</option>';
require __DIR__ . "/partials/atas.php";
?>
<section class="form-card">
    <div class="form-card-header"><h2>Edit Upah</h2><p><?= htmlspecialchars($data["emp_id"] . " - " . $data["employee_name"]); ?></p></div>
    <div class="form-body">
        <?php if ($pesan !== ""): ?><div class="alert-error" role="alert"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $id; ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
            <div class="form-group"><label for="gaji_pokok">Gaji Pokok</label><input id="gaji_pokok" name="gaji_pokok" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($data["gaji_pokok"] ?? 0)); ?>" required></div>
            <div class="form-group"><label for="uang_makan">Uang Makan</label><input id="uang_makan" name="uang_makan" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($data["uang_makan"] ?? 0)); ?>" required></div>
            <div class="form-group"><label for="berlaku_mulai">Berlaku Mulai</label><input id="berlaku_mulai" name="berlaku_mulai" type="date" value="<?= htmlspecialchars((string) ($data["berlaku_mulai"] ?? date("Y-m-d"))); ?>" required></div>
            <h3>Pendapatan Tambahan</h3>
            <div id="pendapatan-tambahan-list">
                <?php foreach ($pendapatanTambahan as $indeks => $pendapatan): $namaPendapatan = trim((string) $pendapatan["nama"]); $jenisIdPendapatan = $jenisKomponenByNama["pendapatan"][mb_strtolower($namaPendapatan)] ?? 0; $pilihanPendapatanSaatIni = $jenisIdPendapatan > 0 ? "jenis:" . $jenisIdPendapatan : "manual:" . base64_encode($namaPendapatan); ?><div class="form-grid income-row"><div class="form-group"><label>Nama Pendapatan</label><select name="pendapatan_tambahan[<?= $indeks; ?>][jenis_id]" class="component-choice" data-custom-input="pendapatan-custom-<?= $indeks; ?>" required><?= str_replace('value="' . htmlspecialchars($pilihanPendapatanSaatIni, ENT_QUOTES, "UTF-8") . '"', 'value="' . htmlspecialchars($pilihanPendapatanSaatIni, ENT_QUOTES, "UTF-8") . '" selected', $opsiPendapatan); ?></select><input id="pendapatan-custom-<?= $indeks; ?>" name="pendapatan_tambahan[<?= $indeks; ?>][nama_baru]" value="" placeholder="Nama pendapatan baru" style="display:none"></div><div class="form-group"><label>Nominal</label><input name="pendapatan_tambahan[<?= $indeks; ?>][nilai]" type="number" min="0" step="0.01" value="<?= htmlspecialchars($pendapatan["nilai"]); ?>" required></div><button class="btn btn-danger remove-income" type="button">Hapus</button></div><?php endforeach; ?>
            </div>
            <button class="btn btn-secondary" id="tambah-pendapatan" type="button">+ Tambah Pendapatan</button>
            <h3>Potongan</h3>
            <div id="potongan-list">
                <?php foreach ($potongan as $indeks => $itemPotongan): $namaPotongan = trim((string) $itemPotongan["nama"]); $jenisIdPotongan = $jenisKomponenByNama["potongan"][mb_strtolower($namaPotongan)] ?? 0; $pilihanPotonganSaatIni = $jenisIdPotongan > 0 ? "jenis:" . $jenisIdPotongan : "manual:" . base64_encode($namaPotongan); ?><div class="form-grid deduction-row"><div class="form-group"><label>Nama Potongan</label><select name="potongan[<?= $indeks; ?>][jenis_id]" class="component-choice" data-custom-input="potongan-custom-<?= $indeks; ?>" required><?= str_replace('value="' . htmlspecialchars($pilihanPotonganSaatIni, ENT_QUOTES, "UTF-8") . '"', 'value="' . htmlspecialchars($pilihanPotonganSaatIni, ENT_QUOTES, "UTF-8") . '" selected', $opsiPotongan); ?></select><input id="potongan-custom-<?= $indeks; ?>" name="potongan[<?= $indeks; ?>][nama_baru]" value="" placeholder="Nama potongan baru" style="display:none"></div><div class="form-group"><label>Nominal</label><input name="potongan[<?= $indeks; ?>][nilai]" type="number" min="0" step="0.01" value="<?= htmlspecialchars($itemPotongan["nilai"]); ?>" required></div><button class="btn btn-danger remove-deduction" type="button">Hapus</button></div><?php endforeach; ?>
            </div>
            <button class="btn btn-secondary" id="tambah-potongan" type="button">+ Tambah Potongan</button>
            <div class="form-actions"><a class="btn btn-secondary" href="upah.php">Batal</a><button class="btn btn-success" type="submit">Simpan</button></div>
        </form>
    </div>
</section>
<script>
(() => {
    const incomeOptions = <?= json_encode($opsiPendapatan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const deductionOptions = <?= json_encode($opsiPotongan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const bindChoice = (row) => { const choice = row.querySelector('.component-choice'); if (!choice) return; const custom = document.getElementById(choice.dataset.customInput); const toggle = () => { const isCustom = choice.value === 'baru'; custom.style.display = isCustom ? 'block' : 'none'; custom.required = isCustom; if (!isCustom) custom.value = ''; }; choice.onchange = toggle; toggle(); };
    const bind = () => { document.querySelectorAll('.remove-income').forEach(button => button.onclick = () => button.closest('.income-row').remove()); document.querySelectorAll('.remove-deduction').forEach(button => button.onclick = () => button.closest('.deduction-row').remove()); document.querySelectorAll('.income-row, .deduction-row').forEach(bindChoice); };
    const addRow = (list, index, name, label, options, rowClass, removeClass) => { const row = document.createElement('div'); row.className = `form-grid ${rowClass}`; row.innerHTML = `<div class="form-group"><label>${label}</label><select name="${name}[${index}][jenis_id]" class="component-choice" data-custom-input="${name}-custom-${index}" required>${options}</select><input id="${name}-custom-${index}" name="${name}[${index}][nama_baru]" placeholder="${label} baru" style="display:none"></div><div class="form-group"><label>Nominal</label><input name="${name}[${index}][nilai]" type="number" min="0" step="0.01" required></div><button class="btn btn-danger ${removeClass}" type="button">Hapus</button>`; list.appendChild(row); bindChoice(row); };
    const list = document.getElementById('pendapatan-tambahan-list'); const add = document.getElementById('tambah-pendapatan'); let index = <?= count($pendapatanTambahan); ?>; add.onclick = () => addRow(list, index++, 'pendapatan_tambahan', 'Nama Pendapatan', incomeOptions, 'income-row', 'remove-income');
    const dList = document.getElementById('potongan-list'); const dAdd = document.getElementById('tambah-potongan'); let dIndex = <?= count($potongan); ?>; dAdd.onclick = () => addRow(dList, dIndex++, 'potongan', 'Nama Potongan', deductionOptions, 'deduction-row', 'remove-deduction');
    bind();
})();
</script>
<?php require __DIR__ . "/partials/bawah.php"; ?>
