<?php

declare(strict_types=1);

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../Services/Auth/auth.php';
require __DIR__ . '/../Services/Employee/analisis-karyawan.php';
require __DIR__ . '/../Services/Employee/tanggal-keluar-karyawan.php';
require_once __DIR__ . '/../Services/Settings/pengaturan-publik.php';

wajibLogin();
$pengaturanDashboard = ambilPengaturanPublik($conn);

try {
    $analisis = ambilAnalisisKaryawan($conn);
} catch (Throwable $exception) {
    error_log("Analisis karyawan gagal: " . $exception->getMessage());
    http_response_code(500);
    exit("Data analisis tidak dapat dimuat.");
}

$filter = $analisis["filter"];
$pilihan = $analisis["pilihan"];
$kpi = $analisis["kpi"];

$judulHalaman = "Analisis Karyawan";
$subjudulHalaman = "Ringkasan agregat dari seluruh data pada tabel karyawan.";
$halamanAktif = "analisis";

require __DIR__ . '/../../resources/views/layouts/atas.php';
?>
<section class="analysis-page" style="--analysis-primary: <?= htmlspecialchars($pengaturanDashboard["warna_bar_awal"] ?? "#2563eb"); ?>; --analysis-secondary: <?= htmlspecialchars($pengaturanDashboard["warna_bar_akhir"] ?? "#93c5fd"); ?>; --analysis-male: <?= htmlspecialchars($pengaturanDashboard["warna_pie_laki"] ?? "#2563eb"); ?>; --analysis-female: <?= htmlspecialchars($pengaturanDashboard["warna_pie_perempuan"] ?? "#ec4899"); ?>; --analysis-status: <?= htmlspecialchars($pengaturanDashboard["warna_grafik_status"] ?? "#2563eb"); ?>; --analysis-trend: <?= htmlspecialchars($pengaturanDashboard["warna_grafik_tren"] ?? "#2563eb"); ?>; --analysis-position: <?= htmlspecialchars($pengaturanDashboard["warna_grafik_posisi"] ?? "#2563eb"); ?>; --analysis-department: <?= htmlspecialchars($pengaturanDashboard["warna_grafik_departemen"] ?? "#2563eb"); ?>; --analysis-salary: <?= htmlspecialchars($pengaturanDashboard["warna_grafik_gaji"] ?? "#2563eb"); ?>; --analysis-performance: <?= htmlspecialchars($pengaturanDashboard["warna_grafik_performa"] ?? "#2563eb"); ?>;">
    <form class="analysis-filter-card" method="GET" action="analisis.php">
        <div class="analysis-filter-heading">
            <div>
                <h2>Filter Analisis</h2>
                <p>Seluruh kartu, grafik, dan tabel akan mengikuti filter berikut.</p>
            </div>
            <a class="btn btn-secondary" href="analisis.php">Reset Filter</a>
        </div>

        <div class="analysis-filter-grid">
            <?php if (!roleOperasional()): ?>
            <label>Departemen
                <select name="department">
                    <option value="">Semua departemen</option>
                    <?php foreach ($pilihan["department"] as $nilai): ?>
                        <option value="<?= htmlspecialchars($nilai); ?>" <?= $filter["department"] === $nilai ? "selected" : ""; ?>><?= htmlspecialchars($nilai); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
            <label>Posisi
                <select name="position">
                    <option value="">Semua posisi</option>
                    <?php foreach ($pilihan["position"] as $nilai): ?>
                        <option value="<?= htmlspecialchars($nilai); ?>" <?= $filter["position"] === $nilai ? "selected" : ""; ?>><?= htmlspecialchars($nilai); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Status Kerja
                <select name="employment_status">
                    <option value="">Semua status</option>
                    <?php foreach ($pilihan["employment_status"] as $nilai): ?>
                        <option value="<?= htmlspecialchars($nilai); ?>" <?= $filter["employment_status"] === $nilai ? "selected" : ""; ?>><?= htmlspecialchars($nilai); ?></option>
                    <?php endforeach; ?>
                    <?php if (!in_array('Nonaktif', $pilihan["employment_status"], true)): ?>
                        <option value="Nonaktif" <?= $filter["employment_status"] === "Nonaktif" ? "selected" : ""; ?>>Nonaktif</option>
                    <?php endif; ?>
                </select>
            </label>
            <label>Jenis Kelamin
                <select name="gender">
                    <option value="">Semua gender</option>
                    <option value="M" <?= $filter["gender"] === "M" ? "selected" : ""; ?>>Laki-laki</option>
                    <option value="F" <?= $filter["gender"] === "F" ? "selected" : ""; ?>>Perempuan</option>
                </select>
            </label>
            <label>Tanggal Masuk Mulai
                <input type="date" name="date_from" value="<?= htmlspecialchars($filter["date_from"]); ?>">
            </label>
            <label>Tanggal Masuk Sampai
                <input type="date" name="date_to" value="<?= htmlspecialchars($filter["date_to"]); ?>">
            </label>
        </div>
        <div class="analysis-filter-actions">
            <button class="btn btn-primary" type="submit">Terapkan Filter</button>
        </div>
    </form>

    <div class="analysis-kpi-grid">
        <article class="analysis-kpi-card"><span>Total karyawan keseluruhan</span><strong><?= number_format($kpi["total"], 0, ",", "."); ?></strong><small>Jumlah karyawan berstatus Aktif dan Nonaktif</small></article>
        <article class="analysis-kpi-card"><span>Total karyawan aktif</span><strong><?= number_format($kpi["aktif"], 0, ",", "."); ?></strong><small>Seluruh tipe kerja berstatus Aktif</small></article>
        <article class="analysis-kpi-card"><span>Total karyawan nonaktif</span><strong><?= number_format($kpi["nonaktif"], 0, ",", "."); ?></strong><small>Seluruh tipe kerja berstatus Nonaktif</small></article>
        <article class="analysis-kpi-card"><span>Total Karyawan Laki-laki</span><strong><?= number_format($kpi["laki_laki"], 0, ",", "."); ?></strong><small>Karyawan laki-laki berstatus Aktif</small></article>
        <article class="analysis-kpi-card"><span>Total Karyawan Perempuan</span><strong><?= number_format($kpi["perempuan"], 0, ",", "."); ?></strong><small>Karyawan perempuan berstatus Aktif</small></article>
        <article class="analysis-kpi-card"><span>Rata-rata Gaji</span><strong>Rp <?= number_format($kpi["rata_gaji"], 0, ",", "."); ?></strong><small>Rata-rata data gaji valid</small></article>
        <article class="analysis-kpi-card"><span>Rata-rata Performa</span><strong><?= $kpi["rata_performa"] === null ? "Belum dinilai" : number_format($kpi["rata_performa"], 1, ",", "."); ?></strong><small>Rata-rata skor performa yang sudah dinilai</small></article>
    </div>

    <div class="analysis-chart-grid">
        <article class="analysis-chart-card analysis-status-chart-card"><h2>Status dan Tipe Kerja</h2><p>Komposisi tipe kerja serta status kerja karyawan.</p><div class="analysis-status-chart-grid"><div class="analysis-status-chart"><h3>Tipe Kerja</h3><div class="analysis-chart-wrap"><canvas id="chartWorkType"></canvas></div></div><div class="analysis-status-chart"><h3>Status Kerja</h3><div class="analysis-chart-wrap"><canvas id="chartStatus"></canvas></div></div></div></article>
        <?php if (roleOperasional()): ?>
            <article class="analysis-chart-card analysis-chart-wide"><h2>Karyawan per Posisi</h2><p>Jumlah karyawan pada seluruh posisi di departemen Anda.</p><div class="analysis-chart-wrap analysis-chart-dynamic" style="height: <?= max(320, count($analisis["posisi"]["label"]) * 34); ?>px"><canvas id="chartPosition"></canvas></div></article>
        <?php endif; ?>
        <article class="analysis-chart-card analysis-chart-wide"><h2>Karyawan Masuk dan Keluar</h2><p>Perbandingan jumlah karyawan masuk dan keluar setiap bulan untuk seluruh tahun yang tersedia.</p><div class="analysis-chart-wrap"><canvas id="chartEmployeeMovement"></canvas></div></article>
        <?php if (roleOperasional()): ?>
            <article class="analysis-chart-card"><h2>Jenis Kelamin</h2><p>Komposisi jenis kelamin karyawan.</p><div class="analysis-chart-wrap"><canvas id="chartGender"></canvas></div></article>
        <?php else: ?>
            <div class="analysis-chart-pair">
                <article class="analysis-chart-card"><h2>Jenis Kelamin</h2><p>Komposisi jenis kelamin karyawan.</p><div class="analysis-chart-wrap"><canvas id="chartGender"></canvas></div></article>
                <article class="analysis-chart-card"><h2>Karyawan per Departemen</h2><p>Jumlah karyawan pada seluruh departemen yang tersedia.</p><div class="analysis-chart-wrap"><canvas id="chartDepartment"></canvas></div></article>
            </div>
        <?php endif; ?>
        <?php if (!roleOperasional()): ?>
            <article class="analysis-chart-card"><h2>Rata-rata Gaji per Departemen</h2><p>Perbandingan rata-rata gaji antar departemen.</p><div class="analysis-chart-wrap"><canvas id="chartSalary"></canvas></div></article>
            <article class="analysis-chart-card"><h2>Rata-rata Performa per Departemen</h2><p>Perbandingan skor performa antar departemen.</p><div class="analysis-chart-wrap"><canvas id="chartPerformance"></canvas></div></article>
        <?php endif; ?>
    </div>

    <article class="analysis-summary-card">
        <div class="analysis-summary-heading"><h2>Ringkasan Departemen</h2><p>Agregasi jumlah, status aktif, gaji, dan performa.</p></div>
        <div class="table-responsive">
            <table class="analysis-summary-table">
                <thead><tr><th>Departemen</th><th>Jumlah Karyawan</th><th>Karyawan Aktif</th><th>Rata-rata Gaji</th><th>Rata-rata Performa</th></tr></thead>
                <tbody>
                <?php if ($analisis["ringkasan"] === []): ?>
                    <tr><td colspan="5" class="empty-data">Tidak ada data yang sesuai dengan filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($analisis["ringkasan"] as $baris): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $baris["department"]); ?></td>
                            <td><?= number_format((int) $baris["jumlah"], 0, ",", "."); ?></td>
                            <td><?= number_format((int) $baris["aktif"], 0, ",", "."); ?></td>
                            <td>Rp <?= number_format((float) $baris["rata_gaji"], 0, ",", "."); ?></td>
                            <td><?= $baris["rata_performa"] === null ? "-" : number_format((float) $baris["rata_performa"], 1, ",", "."); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<script>
(() => {
    if (typeof Chart === 'undefined') return;

    const datasets = <?= json_encode([
        "department" => $analisis["departemen"], "position" => $analisis["posisi"],
        "status" => $analisis["status"], "tipeKerja" => $analisis["tipe_kerja"], "gender" => $analisis["gender"],
        "salary" => $analisis["gaji"],
        "movement" => $analisis["masuk_keluar"],
        "performance" => $analisis["performa"],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const root = getComputedStyle(document.querySelector('.analysis-page'));
    const primary = root.getPropertyValue('--analysis-primary').trim() || '#2563eb';
    const secondary = root.getPropertyValue('--analysis-secondary').trim() || '#93c5fd';
    const male = root.getPropertyValue('--analysis-male').trim() || '#2563eb';
    const female = root.getPropertyValue('--analysis-female').trim() || '#ec4899';
    const statusColor = root.getPropertyValue('--analysis-status').trim() || primary;
    const trendColor = root.getPropertyValue('--analysis-trend').trim() || primary;
    const positionColor = root.getPropertyValue('--analysis-position').trim() || primary;
    const departmentColor = root.getPropertyValue('--analysis-department').trim() || primary;
    const salaryColor = root.getPropertyValue('--analysis-salary').trim() || primary;
    const performanceColor = root.getPropertyValue('--analysis-performance').trim() || primary;
    const palette = [primary, secondary, male, female];
    const numberId = new Intl.NumberFormat('id-ID');
    const currencyId = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const charts = [];

    const themeColors = () => {
        const dark = document.documentElement.dataset.theme === 'dark';
        return {
            text: dark ? '#cbd5e1' : '#64748b',
            grid: dark ? 'rgba(148, 163, 184, .16)' : '#e2e8f0',
            surface: dark ? '#1e293b' : '#ffffff'
        };
    };
    const hexToRgba = (hex, alpha) => {
        const normalized = hex.replace('#', '');
        if (!/^[0-9a-f]{6}$/i.test(normalized)) return `rgba(37, 99, 235, ${alpha})`;
        const value = parseInt(normalized, 16);
        return `rgba(${(value >> 16) & 255}, ${(value >> 8) & 255}, ${value & 255}, ${alpha})`;
    };

    const initialTheme = themeColors();
    Chart.defaults.color = initialTheme.text;
    Chart.defaults.borderColor = initialTheme.grid;

    const commonScales = { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } };
    const bar = (id, data, horizontal = false, formatter = null, color = primary) => {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        charts.push(new Chart(canvas, {
            type: 'bar',
            data: { labels: data.label, datasets: [{ data: data.nilai, backgroundColor: color, borderColor: secondary, borderWidth: 1, borderRadius: 6 }] },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: horizontal ? 'y' : 'x',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => formatter ? formatter(ctx.raw) : numberId.format(ctx.raw) + ' karyawan' } } },
                scales: horizontal ? { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } } : commonScales
            }
        }));
    };
    const doughnut = (id, data, colors = palette) => {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        charts.push(new Chart(canvas, { type: 'doughnut', data: { labels: data.label, datasets: [{ data: data.nilai, backgroundColor: colors, borderColor: initialTheme.surface, borderWidth: 3 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom' } } } }));
    };

    <?php if (roleOperasional()): ?>
    bar('chartPosition', datasets.position, true, null, positionColor);
    <?php else: ?>
    bar('chartDepartment', datasets.department, false, null, departmentColor);
    <?php endif; ?>
    doughnut('chartWorkType', datasets.tipeKerja, datasets.tipeKerja.label.map((_, index) => [primary, secondary, male, female][index % 4]));
    doughnut('chartStatus', datasets.status, datasets.status.label.map((_, index) => [statusColor, secondary, female, male][index % 4]));
    doughnut('chartGender', datasets.gender, datasets.gender.label.map(label => label === 'Perempuan' ? female : male));
    <?php if (!roleOperasional()): ?>
    bar('chartSalary', datasets.salary, false, value => currencyId.format(value), salaryColor);
    bar('chartPerformance', datasets.performance, false, value => Number(value).toFixed(1), performanceColor);
    <?php endif; ?>

    const movement = document.getElementById('chartEmployeeMovement');
    if (movement) charts.push(new Chart(movement, { type: 'line', data: { labels: datasets.movement.label, datasets: [
        { label: 'Karyawan Masuk', data: datasets.movement.masuk, borderColor: trendColor, backgroundColor: hexToRgba(trendColor, .14), fill: true, tension: .3, pointRadius: 3, pointBackgroundColor: secondary },
        { label: 'Karyawan Keluar', data: datasets.movement.keluar, borderColor: female, backgroundColor: hexToRgba(female, .12), fill: true, tension: .3, pointRadius: 3, pointBackgroundColor: female }
    ] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: commonScales } }));

    const synchronizeChartTheme = () => {
        const colors = themeColors();
        Chart.defaults.color = colors.text;
        Chart.defaults.borderColor = colors.grid;
        charts.forEach(chart => {
            if (chart.config.type === 'doughnut') chart.data.datasets.forEach(dataset => { dataset.borderColor = colors.surface; });
            if (chart.options.scales) Object.values(chart.options.scales).forEach(scale => {
                if (scale.grid) scale.grid.color = colors.grid;
                if (scale.ticks) scale.ticks.color = colors.text;
                if (scale.title) scale.title.color = colors.text;
            });
            if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = colors.text;
            chart.update();
        });
    };

    new MutationObserver(mutations => {
        if (mutations.some(item => item.attributeName === 'data-theme')) synchronizeChartTheme();
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
})();
</script>
<?php require __DIR__ . '/../../resources/views/layouts/bawah.php'; ?>
