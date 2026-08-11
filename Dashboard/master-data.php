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
                    } elseif ($jenis === "posisi" || $jenis === "status") {
                        $kolom = $jenis === "posisi" ? "position" : "employment_status";
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
$subjudulHalaman = "Kelola departemen, posisi, dan status kerja.";
$halamanAktif = "master-data";
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
                        <?= htmlspecialchars($item["nama"]); ?>
                        <form method="POST" onsubmit="return confirm('Hapus data ini?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf()); ?>">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis); ?>">
                            <input type="hidden" name="id" value="<?= (int) $item["id"]; ?>">
                            <button class="btn btn-danger" type="submit">Hapus</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . "/partials/bawah.php"; ?>
