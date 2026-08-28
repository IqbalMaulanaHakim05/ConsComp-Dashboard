<?php
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/Auth/auth.php';
require_once __DIR__ . '/../Services/Settings/master-data.php';

wajibRole("superadmin");
siapkanMasterData($conn);

$map = [
    "departemen" => "master_departemen",
    "posisi" => "master_posisi",
    "status" => "master_status_kerja",
    "agama" => "master_agama",
];
$tampilanMaster = [
    "departemen" => ["judul" => "Master Departemen", "placeholder" => "Tambah Departemen"],
    "posisi" => ["judul" => "Master Posisi", "placeholder" => "Tambah Posisi"],
    "status" => ["judul" => "Master Status", "placeholder" => "Tambah Status"],
    "agama" => ["judul" => "Master Agama", "placeholder" => "Tambah Agama"],
];
$pesan = trim((string) ($_GET["pesan"] ?? ""));
$error = trim((string) ($_GET["error"] ?? ""));
$masterShift = ambilMasterShift($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrfValid($_POST["csrf_token"] ?? null)) {
        $error = "Token keamanan tidak valid. Muat ulang halaman lalu coba kembali.";
    } else {
        $aksi = (string) ($_POST["aksi"] ?? "tambah");
        $jenis = (string) ($_POST["jenis"] ?? "");
        $table = $map[$jenis] ?? null;

        if ($aksi === 'edit_shift') {
            $idShift = (int) ($_POST['id'] ?? 0);
            $mulai = trim((string) ($_POST['jam_mulai'] ?? ''));
            $selesai = trim((string) ($_POST['jam_selesai'] ?? ''));
            $hariTerpilih = array_values(array_intersect(
                ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
                array_map('strval', (array) ($_POST['hari'] ?? []))
            ));
            $hari = implode(', ', $hariTerpilih) ?: 'Senin-Jumat';
            $stmt = mysqli_prepare($conn, "UPDATE master_shift SET jam_mulai = ?, jam_selesai = ?, hari = ? WHERE id = ?");
            if ($stmt && $idShift > 0 && preg_match('/^\d{2}:\d{2}$/', $mulai) && preg_match('/^\d{2}:\d{2}$/', $selesai)) {
                mysqli_stmt_bind_param($stmt, 'sssi', $mulai, $selesai, $hari, $idShift);
                $berhasil = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                header('Location: master-data.php?' . ($berhasil ? 'pesan=' : 'error=') . rawurlencode($berhasil ? 'Shift berhasil diperbarui.' : 'Shift gagal diperbarui.'));
                exit;
            }
            if ($stmt) mysqli_stmt_close($stmt);
            $error = 'Data shift tidak valid.';
        }

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
            $tujuan = "?error=" . rawurlencode("Relasi posisi gagal dihapus karena data tidak valid.");

            if ($departmentId > 0 && $posisiId > 0) {
                try {
                    $stmtCek = mysqli_prepare(
                        $conn,
                        "SELECT COUNT(*) FROM karyawan k INNER JOIN master_posisi p ON p.id = ? WHERE k.department_id = ? AND k.position = p.nama"
                    );
                    mysqli_stmt_bind_param($stmtCek, "ii", $posisiId, $departmentId);
                    mysqli_stmt_execute($stmtCek);
                    $hasilCek = mysqli_fetch_row(mysqli_stmt_get_result($stmtCek));
                    mysqli_stmt_close($stmtCek);

                    if ((int) ($hasilCek[0] ?? 0) > 0) {
                        throw new RuntimeException("Relasi posisi gagal dihapus karena masih digunakan oleh data karyawan pada departemen tersebut.");
                    }

                    $stmt = mysqli_prepare($conn, "DELETE FROM master_posisi_departemen WHERE posisi_id = ? AND department_id = ?");
                    mysqli_stmt_bind_param($stmt, "ii", $posisiId, $departmentId);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new RuntimeException("Relasi posisi gagal dihapus.");
                    }
                    $terhapus = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);

                    if ($terhapus < 1) {
                        throw new RuntimeException("Relasi posisi gagal dihapus karena data tidak ditemukan.");
                    }

                    $tujuan = "?pesan=" . rawurlencode("Relasi posisi berhasil dihapus.");
                } catch (Throwable $exception) {
                    $tujuan = "?error=" . rawurlencode($exception->getMessage());
                }
            }

            header("Location: master-data.php" . $tujuan);
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
require __DIR__ . '/../../resources/views/layouts/atas.php';
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
        <article class="chart-card master-data-card master-data-card-<?= htmlspecialchars($jenis); ?>" data-master-card>
            <h2><?= htmlspecialchars($tampilanMaster[$jenis]["judul"]); ?></h2>
            <form method="POST" class="search-form master-add-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                <input type="hidden" name="aksi" value="tambah">
                <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis); ?>">
                <input name="nama" placeholder="<?= htmlspecialchars($tampilanMaster[$jenis]["placeholder"]); ?>" aria-label="<?= htmlspecialchars($tampilanMaster[$jenis]["placeholder"]); ?>" required>
                <button class="btn btn-success" type="submit">Tambah</button>
            </form>
            <ul class="master-list" data-master-list>
                <?php foreach ($items as $item): ?>
                    <li class="master-item" data-master-item>
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
                <?php if ($items === []): ?>
                    <li class="master-list-empty">Belum ada data <?= htmlspecialchars($jenis); ?>.</li>
                <?php endif; ?>
            </ul>
            <div class="master-pagination" data-master-pagination>
                <button class="btn btn-secondary master-page-reset" type="button" hidden style="display: none;">Awal</button>
                <span class="master-page-label" aria-live="polite">Halaman 1 dari 1</span>
                <div class="master-page-controls">
                    <button class="btn btn-secondary master-page-previous" type="button">Sebelumnya</button>
                    <button class="btn btn-secondary master-page-next" type="button">Berikutnya</button>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-chart master-data-grid master-shift-grid">
    <article class="chart-card master-data-card master-shift-card">
        <h2>Master Shift Kerja</h2>
        <ul class="master-list">
            <?php foreach ($masterShift as $shift): ?><li class="master-item"><span><?= htmlspecialchars($shift['nama'] . ' · ' . $shift['jam_mulai'] . ' - ' . $shift['jam_selesai'] . ' · ' . $shift['hari']); ?></span><button class="btn btn-secondary edit-master-shift" type="button" data-id="<?= (int) $shift['id']; ?>" data-nama="<?= htmlspecialchars($shift['nama'], ENT_QUOTES); ?>" data-mulai="<?= htmlspecialchars($shift['jam_mulai'], ENT_QUOTES); ?>" data-selesai="<?= htmlspecialchars($shift['jam_selesai'], ENT_QUOTES); ?>" data-hari="<?= htmlspecialchars($shift['hari'], ENT_QUOTES); ?>">Edit</button></li><?php endforeach; ?>
        </ul>
    </article>
</section>

<dialog id="shift-master-dialog">
    <form method="POST" class="position-dialog-add">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>"><input type="hidden" name="aksi" value="edit_shift"><input type="hidden" name="id" id="shift-master-id">
        <h2 id="shift-master-title"></h2><input name="jam_mulai" id="shift-master-mulai" type="time" required><input name="jam_selesai" id="shift-master-selesai" type="time" required><fieldset class="shift-day-checklist"><legend>Hari Kerja</legend><?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari): ?><label><input type="checkbox" name="hari[]" value="<?= $hari; ?>"> <?= $hari; ?></label><?php endforeach; ?></fieldset><button class="btn btn-success" type="submit">Simpan</button><button class="btn btn-secondary" type="button" id="close-shift-master-dialog">Batal</button>
    </form>
</dialog>
<script>
(() => {
    const dialog = document.getElementById('shift-master-dialog');
    document.querySelectorAll('.edit-master-shift').forEach(button => button.addEventListener('click', () => {
        document.getElementById('shift-master-id').value = button.dataset.id;
        document.getElementById('shift-master-title').textContent = 'Edit ' + button.dataset.nama;
        document.getElementById('shift-master-mulai').value = button.dataset.mulai;
        document.getElementById('shift-master-selesai').value = button.dataset.selesai;
        const hari = button.dataset.hari.split(/,\s*/);
        dialog.querySelectorAll('input[name="hari[]"]').forEach(item => { item.checked = hari.includes(item.value) || (button.dataset.hari === 'Senin-Jumat' && ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'].includes(item.value)); });
        dialog.showModal();
    }));
    document.getElementById('close-shift-master-dialog').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
})();
</script>

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
    #shift-master-dialog { width: min(620px, calc(100vw - 2rem)); padding: 2rem; border: 1px solid #9ca3af; border-radius: 14px; background: #fff; box-shadow: 0 20px 60px rgba(0, 0, 0, .28); }
    #shift-master-dialog::backdrop { background: rgba(0, 0, 0, .38); }
    #shift-master-dialog .position-dialog-add { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    #shift-master-dialog h2 { grid-column: 1 / -1; margin: 0; color: #111827; }
    #shift-master-dialog .shift-day-checklist { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .7rem 1rem; margin: 0; padding: .9rem 1rem; border: 1px solid #cbd5e1; border-radius: 12px; }
    #shift-master-dialog .shift-day-checklist legend { padding: 0 .35rem; font-weight: 600; }
    #shift-master-dialog .shift-day-checklist label { display: inline-flex; align-items: center; gap: .35rem; }
    #position-dialog h2 { margin: 0 0 1.25rem; text-align: center; color: #111827; }
    .position-dialog-add { display: flex; gap: .75rem; margin-bottom: 1.25rem; }
    .position-dialog-add select { flex: 1; min-width: 0; border: 1px solid #667cff; border-radius: 14px; padding: .8rem 1rem; }
    .position-dialog-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 1.25rem; max-height: 330px; overflow-y: auto; padding: .1rem .15rem; }
    .position-dialog-list li { display: flex; align-items: center; justify-content: space-between; gap: .75rem; min-width: 0; padding: .7rem 1rem; border: 1px solid #d1d5db; border-radius: 14px; background: #fafafa; }
    .position-dialog-list li span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .position-dialog-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.25rem; }
    .position-dialog-pages { display: flex; align-items: center; gap: .75rem; }
    .position-dialog-pages strong { min-width: 110px; text-align: center; color: #111827; }
    :root[data-theme="dark"] #position-dialog { color: #e2e8f0; background: #1e293b; border-color: #475569; }
    :root[data-theme="dark"] #position-dialog h2,
    :root[data-theme="dark"] .position-dialog-pages strong { color: #f8fafc; }
    :root[data-theme="dark"] .position-dialog-add select { color: #e2e8f0; background: #0f172a; border-color: #64748b; color-scheme: dark; }
    :root[data-theme="dark"] .position-dialog-add select option { color: #e2e8f0; background: #0f172a; }
    :root[data-theme="dark"] .position-dialog-list li { color: #e2e8f0; background: #172033; border-color: #475569; }
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
    const notificationUrl = new URL(window.location.href);
    if (notificationUrl.searchParams.has('pesan') || notificationUrl.searchParams.has('error')) {
        notificationUrl.searchParams.delete('pesan');
        notificationUrl.searchParams.delete('error');
        window.history.replaceState({}, document.title, notificationUrl.pathname + notificationUrl.search + notificationUrl.hash);
    }

    const masterPageSize = 5;
    document.querySelectorAll('[data-master-card]').forEach(function (card) {
        const items = Array.from(card.querySelectorAll('[data-master-item]'));
        const previous = card.querySelector('.master-page-previous');
        const next = card.querySelector('.master-page-next');
        const reset = card.querySelector('.master-page-reset');
        const label = card.querySelector('.master-page-label');
        const addForm = card.querySelector('.master-add-form');
        let page = 1;

        const renderMasterPage = function () {
            const totalPages = Math.max(1, Math.ceil(items.length / masterPageSize));
            page = Math.min(Math.max(page, 1), totalPages);
            const start = (page - 1) * masterPageSize;

            items.forEach(function (item, index) {
                item.hidden = index < start || index >= start + masterPageSize;
            });
            label.textContent = 'Halaman ' + page + ' dari ' + totalPages;
            previous.disabled = page <= 1;
            next.disabled = page >= totalPages;
            if (reset) {
                reset.hidden = page <= 1;
                reset.style.display = page <= 1 ? 'none' : '';
            }
        };

        previous.addEventListener('click', function () {
            page--;
            renderMasterPage();
        });
        next.addEventListener('click', function () {
            page++;
            renderMasterPage();
        });
        if (reset) {
            reset.addEventListener('click', function () {
                addForm.reset();
                page = 1;
                renderMasterPage();
            });
        }

        renderMasterPage();
    });

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

<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
