
</main>

<script>
    function toggleTheme() {
        const next = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
        document.documentElement.dataset.theme = next;
        localStorage.setItem("employee-theme", next);
        if (window.Chart) {
            const textColor = next === "dark" ? "#cbd5e1" : "#64748b";
            const gridColor = next === "dark" ? "rgba(148,163,184,.16)" : "#e2e8f0";
            Chart.defaults.color = textColor;
            Chart.defaults.borderColor = gridColor;
            Object.values(Chart.instances).forEach((chart) => {
                if (chart.options.scales) Object.values(chart.options.scales).forEach((scale) => {
                    if (scale.grid) scale.grid.color = gridColor;
                    if (scale.ticks) scale.ticks.color = textColor;
                    if (scale.title) scale.title.color = textColor;
                });
                if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = textColor;
                chart.update();
            });
        }
        document.querySelectorAll(".theme-toggle").forEach((button) => button.textContent = next === "dark" ? "☀️ Light" : "🌙 Dark");
    }
    document.querySelectorAll(".theme-toggle").forEach((button) => button.textContent = document.documentElement.dataset.theme === "dark" ? "☀️ Light" : "🌙 Dark");
    document.querySelectorAll("[data-password-toggle]").forEach((button) => {
        button.addEventListener("click", () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;

            const terlihat = input.type === "text";
            input.type = terlihat ? "password" : "text";
            button.classList.toggle("is-visible", !terlihat);
            button.setAttribute("aria-label", terlihat ? "Tampilkan password" : "Sembunyikan password");
            if (!button.querySelector(".password-toggle-icon")) {
                button.textContent = terlihat ? "Lihat" : "Sembunyikan";
            }
            button.setAttribute("aria-pressed", String(!terlihat));
        });
    });
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("show");
    }

    // Manajemen indikator scroll dan petunjuk visual kolom sticky
    (() => {
        const perbaruiStatusScrollTabel = (wrapper) => {
            if (!wrapper || wrapper.classList.contains("no-actions")) return;
            const maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
            if (maxScroll <= 2) {
                wrapper.classList.add("no-scroll");
                wrapper.classList.remove("is-scrolled-end", "can-scroll-right");
                return;
            }
            wrapper.classList.remove("no-scroll");
            const sudahSampaiUjungKanan = (wrapper.scrollLeft + wrapper.clientWidth >= wrapper.scrollWidth - 12);
            wrapper.classList.toggle("is-scrolled-end", sudahSampaiUjungKanan);
            wrapper.classList.toggle("can-scroll-right", !sudahSampaiUjungKanan);
        };

        const inisialisasiScrollTabel = () => {
            document.querySelectorAll(".table-wrapper").forEach((wrapper) => {
                perbaruiStatusScrollTabel(wrapper);
                wrapper.addEventListener("scroll", () => perbaruiStatusScrollTabel(wrapper), { passive: true });
            });
        };

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", inisialisasiScrollTabel);
        } else {
            inisialisasiScrollTabel();
        }
        window.addEventListener("resize", () => {
            document.querySelectorAll(".table-wrapper").forEach(perbaruiStatusScrollTabel);
        }, { passive: true });
        window.addEventListener("load", () => {
            document.querySelectorAll(".table-wrapper").forEach(perbaruiStatusScrollTabel);
        });
    })();
</script>

</body>
</html>
