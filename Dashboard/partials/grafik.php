<?php

/*
|--------------------------------------------------------------------------
| Bagian grafik dashboard & analisis.
| Variabel yang diharapkan:
|   $labelDepartemen, $jumlahDepartemen, $labelPerforma, $jumlahPerforma
|--------------------------------------------------------------------------
*/

?>
    <section class="dashboard-chart" style="--dashboard-start: <?= htmlspecialchars($pengaturanDashboard["warna_dashboard_awal"] ?? "#1e3a8a"); ?>; --dashboard-end: <?= htmlspecialchars($pengaturanDashboard["warna_dashboard_akhir"] ?? "#2563eb"); ?>; --pie-male: <?= htmlspecialchars($pengaturanDashboard["warna_pie_laki"] ?? "#2563eb"); ?>; --pie-female: <?= htmlspecialchars($pengaturanDashboard["warna_pie_perempuan"] ?? "#ec4899"); ?>; --bar-start: <?= htmlspecialchars($pengaturanDashboard["warna_bar_awal"] ?? "#2563eb"); ?>; --bar-end: <?= htmlspecialchars($pengaturanDashboard["warna_bar_akhir"] ?? "#93c5fd"); ?>;">

        <div class="chart-card">
            <h2>Jumlah Karyawan per Departemen</h2>

            <p>
                Perbandingan jumlah karyawan pada setiap departemen.
            </p>

            <div class="chart-wrapper">
                <canvas id="grafikDepartemen"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h2>Perbandingan Jenis Kelamin</h2>

            <p>
                Perbandingan jumlah karyawan laki-laki dan perempuan.
            </p>

            <div class="chart-wrapper">
                <canvas id="grafikGender"></canvas>
            </div>
        </div>

    </section>

<script>
    // Pastikan teks label Chart.js tetap terbaca pada kedua tema.
    Chart.defaults.color = document.documentElement.dataset.theme === "dark" ? "#cbd5e1" : "#64748b";
    Chart.defaults.borderColor = document.documentElement.dataset.theme === "dark" ? "#334155" : "#e2e8f0";
    const labelDepartemen =
        <?= json_encode(
            $labelDepartemen,
            JSON_UNESCAPED_UNICODE
        ); ?>;

    const jumlahDepartemen =
        <?= json_encode(
            $jumlahDepartemen
        ); ?>;

    const labelPerforma =
        <?= json_encode(
            $labelPerforma,
            JSON_UNESCAPED_UNICODE
        ); ?>;

    const jumlahPerforma =
        <?= json_encode(
            $jumlahPerforma
        ); ?>;

    const labelGender = <?= json_encode($labelGender ?? ["Laki-laki", "Perempuan"], JSON_UNESCAPED_UNICODE); ?>;
    const jumlahGender = <?= json_encode($jumlahGender ?? [0, 0]); ?>;

    const elemenGrafikDepartemen =
        document.getElementById(
            "grafikDepartemen"
        );

    const chartColors = getComputedStyle(document.querySelector('.dashboard-chart'));

    if (
        elemenGrafikDepartemen
        && labelDepartemen.length > 0
    ) {
        const gradientDepartemen = elemenGrafikDepartemen.getContext("2d").createLinearGradient(0, 0, 0, 320);
        gradientDepartemen.addColorStop(0, chartColors.getPropertyValue('--bar-start').trim() || '#2563eb');
        gradientDepartemen.addColorStop(1, chartColors.getPropertyValue('--bar-end').trim() || '#93c5fd');
        new Chart(
            elemenGrafikDepartemen,
            {
                type: "bar",

                data: {
                    labels: labelDepartemen,

                    datasets: [
                        {
                            label: "Jumlah Karyawan",
                            data: jumlahDepartemen,
                            backgroundColor: gradientDepartemen,
                            borderColor:
                                "rgb(37, 99, 235)",
                            borderWidth: 1,
                            borderRadius: 6
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return (
                                        context.raw
                                        + " karyawan"
                                    );
                                }
                            }
                        }
                    },

                    scales: {
                        y: {
                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            title: {
                                display: true,
                                text: "Jumlah Karyawan"
                            }
                        },

                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 0
                            }
                        }
                    }
                }
            }
        );
    }

    const elemenGrafikGender =
        document.getElementById(
            "grafikGender"
        );

    if (
        elemenGrafikGender
        && labelGender.length > 0
    ) {
        new Chart(
            elemenGrafikGender,
            {
                type: "pie",

                data: {
                    labels: labelGender,

                    datasets: [
                        {
                            label: "Jumlah Karyawan",
                            data: jumlahGender,

                            backgroundColor: [chartColors.getPropertyValue('--pie-male').trim() || '#2563eb', chartColors.getPropertyValue('--pie-female').trim() || '#ec4899'],

                            borderColor: "#ffffff",
                            borderWidth: 2
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "58%",

                    plugins: {
                        legend: {
                            position: "bottom"
                        },

                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return (
                                        context.label
                                        + ": "
                                        + context.raw
                                        + " karyawan"
                                    );
                                }
                            }
                        }
                    }
                }
            }
        );
    }

</script>
