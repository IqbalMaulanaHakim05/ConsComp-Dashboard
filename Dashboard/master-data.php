<?php
require __DIR__ . "/koneksi.php";
require_once __DIR__ . "/fungsi/auth.php";
require_once __DIR__ . "/fungsi/master-data.php";

wajibRole("superadmin");
siapkanMasterData($conn);

$map = [
    "departemen" => "master_departemen",
    "posisi" => "master_posisi",
    "status" => "master_status_kerja",
    "agama" => "master_agama",
];
$pesan = trim((string) ($_GET["pesan"] ?? ""));
$error = trim((string) ($_GET["error"] ?? ""));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $error = "Token keamanan tidak valid. Muat ulang halaman lalu coba kembali.";
    } else {
        $aksi = (string) ($_POST["aksi"] ?? "tambah");
        $jenis = (string) ($_POST["jenis"] ?? "");
        $table = $map[$jenis] ?? null;

        if ($aksi === "tambah") {
            $nama = trim((string) ($_POST["nama"] ?? ""));
            if ($table && $nama !== "") {
                $stmt = mysqli_prepare($conn, "INSERT INTO `$table` (nama) VALUES (?)");
                mysqli_stmt_bind_param($stmt, "s", $nama);
                $berhasil = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $tujuan = $berhasil ? "?pesan=" . rawurlencode("Data berhasil ditambahkan.") : "?error=" . rawurlencode("Data sudah ada atau tidak valid.");
                header("Location: master-data.php" . $tujuan);
                exit;
            }
        }

        if ($aksi === "hubungkan_posisi") {
            $departmentId = (int) ($_POST["department_id"] ?? 0);
            $posisiId = (int) ($_POST["posisi_id"] ?? 0);
            $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO master_posisi_departemen (posisi_id, department_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ii", $posisiId, $departmentId);
            $berhasil = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: master-data.php?" . ($berhasil ? "pesan=" : "error=") . rawurlencode($berhasil ? "Posisi berhasil dikaitkan dengan departemen." : "Relasi posisi tidak valid."));
            exit;
        }

        if ($aksi === "putuskan_posisi") {
            $departmentId = (int) ($_POST["department_id"] ?? 0);
            $posisiId = (int) ($_POST["posisi_id"] ?? 0);
            $stmt = mysqli_prepare($conn, "DELETE FROM master_posisi_departemen WHERE posisi_id = ? AND department_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $posisiId, $departmentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            header("Location: master-data.php?pesan=" . rawurlencode("Relasi posisi berhasil dihapus."));
            exit;
        }

        if ($aksi === "hapus" && $table) {
            $id = (int) ($_POST["id"] ?? 0);
            if ($id < 1) {
                $error = "Data yang akan dihapus tidak valid.";
            } else {
                mysqli_begin_transaction($conn);
                try {
                    if ($jenis === "departemen") {
                        $stmtCek = mysqli_prepare($conn, "SELECT (SELECT COUNT(*) FROM karyawan WHERE department_id = ?) + (SELECT COUNT(*) FROM overtime_reports WHERE department_id = ?)");
                        mysqli_stmt_bind_param($stmtCek, "ii", $id, $id);
                        mysqli_stmt_execute($stmtCek);
                        $hasilCek = mysqli_fetch_row(mysqli_stmt_get_result($stmtCek));
                        mysqli_stmt_close($stmtCek);
                        if ((int) ($hasilCek[0] ?? 0) > 0) {
                            throw new RuntimeException("Departemen masih digunakan oleh data karyawan atau laporan lembur.");
                        }

                        // Akun tetap dipertahankan, hanya cakupan departemennya dilepas.
                        $stmtLepas = mysqli_prepare($conn, "UPDATE users SET department_id = NULL WHERE department_id = ?");
                        mysqli_stmt_bind_param($stmtLepas, "i", $id);
                        mysqli_stmt_execute($stmtLepas);
                        mysqli_stmt_close($stmtLepas);
                    } elseif ($jenis === "posisi" || $jenis === "status" || $jenis === "agama") {
                        $kolom = $jenis === "posisi" ? "position" : ($jenis === "status" ? "employment_status" : "agama");
                        $stmtNama = mysqli_prepare($conn, "SELECT nama FROM `$table` WHERE id = ? LIMIT 1");
                        mysqli_stmt_bind_param($stmtNama, "i", $id);
                        mysqli_stmt_execute($stmtNama);
                        $item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtNama));
                        mysqli_stmt_close($stmtNama);
                        if ($item) {
                            $stmtPakai = mysqli_prepare($conn, "SELECT COUNT(*) FROM karyawan WHERE `$kolom` = ?");
                            mysqli_stmt_bind_param($stmtPakai, "s", $item["nama"]);
                            mysqli_stmt_execute($stmtPakai);
                            $hasilPakai = mysqli_fetch_row(mysqli_stmt_get_result($stmtPakai));
                            mysqli_stmt_close($stmtPakai);
                            if ((int) ($hasilPakai[0] ?? 0) > 0) {
                                throw new RuntimeException(ucfirst($jenis) . " masih digunakan oleh data karyawan.");
                            }
                        }
                    }

                    $stmtHapus = mysqli_prepare($conn, "DELETE FROM `$table` WHERE id = ?");
                    mysqli_stmt_bind_param($stmtHapus, "i", $id);
                    if (!mysqli_stmt_execute($stmtHapus)) {
                        throw new RuntimeException("Data gagal dihapus: " . mysqli_stmt_error($stmtHapus));
                    }
                    $terhapus = mysqli_stmt_affected_rows($stmtHapus);
                    mysqli_stmt_close($stmtHapus);
                    if ($terhapus < 1) {
                        throw new RuntimeException("Data tidak ditemukan atau sudah dihapus.");
                    }
                    mysqli_commit($conn);
                    header("Location: master-data.php?pesan=" . rawurlencode("Data berhasil dihapus."));
                    exit;
                } catch (Throwable $exception) {
                    mysqli_rollback($conn);
                    $error = $exception->getMessage();
                }
            }
        }
    }
}

$judulHalaman = "Master Data";
$subjudulHalaman = "Kelola departemen, posisi, status kerja, dan agama.";
$halamanAktif = "master-data";
$departemenRelasi = [];
$hasilDepartemen = mysqli_query($conn, "SELECT id, nama FROM master_departemen ORDER BY nama");
while ($hasilDepartemen && ($item = mysqli_fetch_assoc($hasilDepartemen))) $departemenRelasi[] = $item;
$posisiRelasi = [];
$hasilPosisi = mysqli_query($conn, "SELECT id, nama FROM master_posisi ORDER BY nama");
while ($hasilPosisi && ($item = mysqli_fetch_assoc($hasilPosisi))) $posisiRelasi[] = $item;
$relasiPosisi = [];
$hasilRelasi = mysqli_query($conn, "SELECT r.department_id, r.posisi_id, d.nama AS departemen, p.nama AS posisi FROM master_posisi_departemen r INNER JOIN master_departemen d ON d.id = r.department_id INNER JOIN master_posisi p ON p.id = r.posisi_id ORDER BY d.nama, p.nama");
while ($hasilRelasi && ($item = mysqli_fetch_assoc($hasilRelasi))) $relasiPosisi[] = $item;
require __DIR__ . "/partials/atas.php";
?>

<?php if ($pesan !== ""): ?><div class="alert alert-success"><?= htmlspecialchars($pesan); ?></div><?php endif; ?>
<?php if ($error !== ""): ?><div class="alert alert-error"><?= htmlspecialchars($error); ?></div><?php endif; ?>

<section class="dashboard-chart master-data-grid">
    <?php foreach ($map as $jenis => $table): ?>
        <?php
        $items = [];
        $query = mysqli_query($conn, "SELECT id, nama FROM `$table` ORDER BY nama");
        while ($query && ($item = mysqli_fetch_assoc($query))) {
            $items[] = $item;
        }
        ?>
        <article class="chart-card">
            <h2><?= ucfirst($jenis); ?></h2>
            <form method="POST" class="search-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis); ?>">
                <input name="nama" placeholder="Tambah <?= htmlspecialchars($jenis); ?>" required>
                <button class="btn btn-success" type="submit">Tambah</button>
            </form>
            <ul class="master-list">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span><?= htmlspecialchars($item["nama"]); ?></span>
                        <form method="POST" onsubmit="return confirm('Hapus data ini?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis); ?>">
                            <input type="hidden" name="id" value="<?= (int) $item["id"]; ?>">
                            <?php if ($jenis === "departemen"): ?><button class="btn btn-secondary manage-positions" type="button" data-department-id="<?= (int) $item["id"]; ?>" data-department-name="<?= htmlspecialchars($item["nama"], ENT_QUOTES); ?>">Kelola Posisi</button><?php endif; ?>
                            <button class="btn btn-danger" type="submit">Hapus</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endforeach; ?>
</section>

<style>
    #position-dialog {
        width: min(760px, calc(100vw - 2rem));
        max-height: min(650px, calc(100vh - 2rem));
        padding: 2rem;
        border: 1px solid #9ca3af;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
    }
    #position-dialog::backdrop { background: rgba(0, 0, 0, .38); }
    #position-dialog h2 { margin: 0 0 1.25rem; text-align: center; color: #111827; }
    .position-dialog-add { display: flex; gap: .75rem; margin-bottom: 1.25rem; }
    .position-dialog-add select { flex: 1; min-width: 0; border: 1px solid #667cff; border-radius: 14px; padding: .8rem 1rem; }
    .position-dialog-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 1.25rem; max-height: 330px; overflow-y: auto; padding: .1rem .15rem; }
    .position-dialog-list li { display: flex; align-items: center; justify-content: space-between; gap: .75rem; min-width: 0; padding: .7rem 1rem; border: 1px solid #d1d5db; border-radius: 14px; background: #fafafa; }
    .position-dialog-list li span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .position-dialog-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.25rem; }
    .position-dialog-pages { display: flex; align-items: center; gap: .75rem; }
    .position-dialog-pages strong { min-width: 110px; text-align: center; color: #111827; }
    @media (max-width: 640px) { #position-dialog { padding: 1.25rem; } .position-dialog-add, .position-dialog-list { grid-template-columns: 1fr; } .position-dialog-add { display: grid; } }
</style>
<dialog id="position-dialog">
    <h2>Posisi Departemen <span id="position-department-name"></span></h2>
    <form method="POST" class="position-dialog-add">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
        <input type="hidden" name="aksi" value="hubungkan_posisi">
        <input type="hidden" id="position-department-id" name="department_id">
        <select name="posisi_id" required><option value="">Tambahkan posisi yang ingin ditambahkan</option><?php foreach ($posisiRelasi as $item): ?><option value="<?= (int) $item["id"]; ?>"><?= htmlspecialchars($item["nama"]); ?></option><?php endforeach; ?></select>
        <button class="btn btn-success" type="submit">Tambah Posisi</button>
    </form>
    <ul id="department-position-list" class="master-list position-dialog-list"></ul>
    <div class="position-dialog-footer">
        <button class="btn btn-secondary" type="button" id="close-position-dialog">Batal</button>
        <div class="position-dialog-pages">
            <button class="btn btn-secondary" type="button" id="position-previous-page">Sebelumnya</button>
            <strong id="position-page-label">Halaman 1 dari 1</strong>
            <button class="btn btn-secondary" type="button" id="position-next-page">Berikutnya</button>
        </div>
    </div>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dialog = document.getElementById('position-dialog');
    const list = document.getElementById('department-position-list');
    const departmentId = document.getElementById('position-department-id');
    const departmentName = document.getElementById('position-department-name');
    const relations = <?= json_encode($relasiPosisi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const previousPage = document.getElementById('position-previous-page');
    const nextPage = document.getElementById('position-next-page');
    const pageLabel = document.getElementById('position-page-label');
    const pageSize = 8;
    let currentPage = 1;
    let currentItems = [];
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const renderPositionPage = function () {
        const totalPages = Math.max(1, Math.ceil(currentItems.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * pageSize;
        list.replaceChildren();
        currentItems.slice(start, start + pageSize).forEach(function (item) {
            const row = document.createElement('li');
            row.innerHTML = '<span>' + escapeHtml(item.posisi) + '</span>';
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="putuskan_posisi"><input type="hidden" name="department_id" value="' + departmentId.value + '"><input type="hidden" name="posisi_id" value="' + item.posisi_id + '"><button class="btn btn-danger" type="submit">Hapus</button>';
            row.append(form);
            list.append(row);
        });
        if (!currentItems.length) {
            const empty = document.createElement('li');
            empty.textContent = 'Belum ada posisi pada departemen ini.';
            list.append(empty);
        }
        pageLabel.textContent = 'Halaman ' + currentPage + ' dari ' + totalPages;
        previousPage.disabled = currentPage <= 1;
        nextPage.disabled = currentPage >= totalPages;
    };
    document.querySelectorAll('.manage-positions').forEach(function (button) {
        button.addEventListener('click', function () {
            const id = button.dataset.departmentId;
            departmentId.value = id;
            departmentName.textContent = button.dataset.departmentName;
            currentItems = relations.filter(item => String(item.department_id) === id);
            currentPage = 1;
            renderPositionPage();
            dialog.showModal();
        });
    });
    previousPage.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPositionPage(); } });
    nextPage.addEventListener('click', () => { if (currentPage < Math.ceil(currentItems.length / pageSize)) { currentPage++; renderPositionPage(); } });
    document.getElementById('close-position-dialog').addEventListener('click', () => dialog.close());
});
</script>

<?php require __DIR__ . "/partials/bawah.php"; ?>
