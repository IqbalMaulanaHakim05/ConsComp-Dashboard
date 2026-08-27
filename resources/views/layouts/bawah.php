
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

    (() => {
        const wrappers = document.querySelectorAll(".table-wrapper");

        wrappers.forEach((wrapper) => {
            const table = wrapper.querySelector("table");
            if (!table) return;

            const header = table.querySelector("thead th:last-child");
            const hasActionHeader = /aksi/i.test(header?.textContent?.trim() || "");
            const hasActionCell = Boolean(table.querySelector(
                "tbody td:last-child button, tbody td:last-child form, tbody td:last-child .btn, tbody td:last-child .action-buttons"
            ));
            const hasActionStructure = table.matches(".overtime-report-table") || Boolean(wrapper.closest(".izin-data-card"));

            if (!wrapper.classList.contains("has-actions") && !hasActionHeader && !hasActionCell && !hasActionStructure) return;

            wrapper.classList.add("has-actions");

            const updateActionColumnBorder = () => {
                const maxScrollLeft = Math.max(0, wrapper.scrollWidth - wrapper.clientWidth);
                const atRight = maxScrollLeft <= 1 || wrapper.scrollLeft >= maxScrollLeft - 2;
                wrapper.classList.toggle("is-scroll-end", atRight);
            };

            wrapper.addEventListener("scroll", updateActionColumnBorder, { passive: true });
            window.addEventListener("resize", updateActionColumnBorder, { passive: true });
            requestAnimationFrame(() => {
                updateActionColumnBorder();
                requestAnimationFrame(updateActionColumnBorder);
            });

            if (window.ResizeObserver) {
                const observer = new ResizeObserver(updateActionColumnBorder);
                observer.observe(wrapper);
                observer.observe(table);
            }
        });
    })();

    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("show");
    }
</script>

</body>
</html>
