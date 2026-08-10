<?php

declare(strict_types=1);

require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/audit.php";

wajibRole("admin", "superadmin");
$id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT k.id, k.emp_id, k.employee_name, pg.id AS profil_id, pg.gaji_pokok, pg.uang_makan FROM karyawan k LEFT JOIN profil_gaji pg ON pg.karyawan_id = k.id WHERE k.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$data) { http_response_code(404); exit("Data karyawan tidak ditemukan."); }
$jenisKomponen = mysqli_query($conn, "SELECT id, kode, nama, kategori, metode FROM jenis_komponen_gaji WHERE is_active = 1 ORDER BY kategori, nama");
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
            foreach ((array) ($_POST["komponen"] ?? []) as $jenisId => $nilai) {
                $jenisId = (int) $jenisId; $nilai = (float) $nilai;
                if ($jenisId > 0 && $nilai > 0) { mysqli_stmt_bind_param($tambahKomponen, "iid", $profilIdAktif, $jenisId, $nilai); mysqli_stmt_execute($tambahKomponen); }
            }
            mysqli_stmt_close($tambahKomponen);
            $hapusPendapatan = mysqli_prepare($conn, "DELETE FROM pendapatan_tambahan_karyawan WHERE karyawan_id = ?");
            mysqli_stmt_bind_param($hapusPendapatan, "i", $id); mysqli_stmt_execute($hapusPendapatan); mysqli_stmt_close($hapusPendapatan);
            $tambahPendapatan = mysqli_prepare($conn, "INSERT INTO pendapatan_tambahan_karyawan (karyawan_id, nama, nilai) VALUES (?, ?, ?)");
            foreach ((array) ($_POST["pendapatan_tambahan"] ?? []) as $baris) {
                $namaPendapatan = trim((string) ($baris["nama"] ?? "")); $nilaiPendapatan = (float) ($baris["nilai"] ?? 0);
                if ($namaPendapatan !== "" && $nilaiPendapatan > 0) { mysqli_stmt_bind_param($tambahPendapatan, "isd", $id, $namaPendapatan, $nilaiPendapatan); mysqli_stmt_execute($tambahPendapatan); }
            }
            mysqli_stmt_close($tambahPendapatan);
            $hapusPotongan = mysqli_prepare($conn, "DELETE FROM potongan_karyawan WHERE karyawan_id = ?");
            mysqli_stmt_bind_param($hapusPotongan, "i", $id); mysqli_stmt_execute($hapusPotongan); mysqli_stmt_close($hapusPotongan);
            $tambahPotongan = mysqli_prepare($conn, "INSERT INTO potongan_karyawan (karyawan_id, nama, nilai) VALUES (?, ?, ?)");
            foreach ((array) ($_POST["potongan"] ?? []) as $baris) {
                $namaPotongan = trim((string) ($baris["nama"] ?? "")); $nilaiPotongan = (float) ($baris["nilai"] ?? 0);
                if ($namaPotongan !== "" && $nilaiPotongan > 0) { mysqli_stmt_bind_param($tambahPotongan, "isd", $id, $namaPotongan, $nilaiPotongan); mysqli_stmt_execute($tambahPotongan); }
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
                <?php foreach ($pendapatanTambahan as $indeks => $pendapatan): ?><div class="form-grid income-row"><div class="form-group"><label>Nama Pendapatan</label><input name="pendapatan_tambahan[<?= $indeks; ?>][nama]" value="<?= htmlspecialchars($pendapatan["nama"]); ?>" placeholder="Contoh: Bonus" required></div><div class="form-group"><label>Nominal</label><input name="pendapatan_tambahan[<?= $indeks; ?>][nilai]" type="number" min="0" step="0.01" value="<?= htmlspecialchars($pendapatan["nilai"]); ?>" required></div><button class="btn btn-danger remove-income" type="button">Hapus</button></div><?php endforeach; ?>
            </div>
            <button class="btn btn-secondary" id="tambah-pendapatan" type="button">+ Tambah Pendapatan</button>
            <h3>Potongan</h3>
            <div id="potongan-list">
                <?php foreach ($potongan as $indeks => $itemPotongan): ?><div class="form-grid deduction-row"><div class="form-group"><label>Nama Potongan</label><input name="potongan[<?= $indeks; ?>][nama]" value="<?= htmlspecialchars($itemPotongan["nama"]); ?>" placeholder="Contoh: BPJS" required></div><div class="form-group"><label>Nominal</label><input name="potongan[<?= $indeks; ?>][nilai]" type="number" min="0" step="0.01" value="<?= htmlspecialchars($itemPotongan["nilai"]); ?>" required></div><button class="btn btn-danger remove-deduction" type="button">Hapus</button></div><?php endforeach; ?>
            </div>
            <button class="btn btn-secondary" id="tambah-potongan" type="button">+ Tambah Potongan</button>
            <div class="form-actions"><a class="btn btn-secondary" href="upah.php">Batal</a><button class="btn btn-success" type="submit">Simpan</button></div>
        </form>
    </div>
</section>
<script>
(() => { const list = document.getElementById('pendapatan-tambahan-list'); const add = document.getElementById('tambah-pendapatan'); let index = <?= count($pendapatanTambahan); ?>; const bind = () => document.querySelectorAll('.remove-income').forEach(button => button.onclick = () => button.closest('.income-row').remove()); bind(); add.onclick = () => { const row = document.createElement('div'); row.className = 'form-grid income-row'; row.innerHTML = `<div class="form-group"><label>Nama Pendapatan</label><input name="pendapatan_tambahan[${index}][nama]" placeholder="Contoh: Bonus" required></div><div class="form-group"><label>Nominal</label><input name="pendapatan_tambahan[${index}][nilai]" type="number" min="0" step="0.01" required></div><button class="btn btn-danger remove-income" type="button">Hapus</button>`; list.appendChild(row); index++; bind(); }; const dList = document.getElementById('potongan-list'); const dAdd = document.getElementById('tambah-potongan'); let dIndex = <?= count($potongan); ?>; const bindD = () => document.querySelectorAll('.remove-deduction').forEach(button => button.onclick = () => button.closest('.deduction-row').remove()); bindD(); dAdd.onclick = () => { const row = document.createElement('div'); row.className = 'form-grid deduction-row'; row.innerHTML = `<div class="form-group"><label>Nama Potongan</label><input name="potongan[${dIndex}][nama]" placeholder="Contoh: BPJS" required></div><div class="form-group"><label>Nominal</label><input name="potongan[${dIndex}][nilai]" type="number" min="0" step="0.01" required></div><button class="btn btn-danger remove-deduction" type="button">Hapus</button>`; dList.appendChild(row); dIndex++; bindD(); }; })();
</script>
<?php require __DIR__ . "/partials/bawah.php"; ?>
