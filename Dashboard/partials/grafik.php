<?php

/*
|--------------------------------------------------------------------------
| Bagian grafik dashboard & analisis.
| Variabel yang diharapkan:
|   $labelDepartemen, $jumlahDepartemen, $labelPerforma, $jumlahPerforma
|--------------------------------------------------------------------------
*/

?>
    <section class="dashboard-chart">

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
            <h2>Komposisi Performa</h2>

            <p>
                Jumlah karyawan berdasarkan skor performa.
            </p>

            <div class="chart-wrapper">
                <canvas id="grafikPerforma"></canvas>
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

    const elemenGrafikDepartemen =
        document.getElementById(
            "grafikDepartemen"
        );

    if (
        elemenGrafikDepartemen
        && labelDepartemen.length > 0
    ) {
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
                            backgroundColor:
                                "rgba(37, 99, 235, 0.75)",
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

    const elemenGrafikPerforma =
        document.getElementById(
            "grafikPerforma"
        );

    if (
        elemenGrafikPerforma
        && labelPerforma.length > 0
    ) {
        new Chart(
            elemenGrafikPerforma,
            {
                type: "doughnut",

                data: {
                    labels: labelPerforma,

                    datasets: [
                        {
                            label: "Jumlah Karyawan",
                            data: jumlahPerforma,

                            backgroundColor: [
                                "#2563eb",
                                "#16a34a",
                                "#f59e0b",
                                "#dc2626",
                                "#7c3aed",
                                "#0891b2",
                                "#ea580c"
                            ],

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
