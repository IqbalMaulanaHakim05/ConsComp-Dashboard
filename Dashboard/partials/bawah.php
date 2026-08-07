
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
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("show");
    }
</script>

</body>
</html>
