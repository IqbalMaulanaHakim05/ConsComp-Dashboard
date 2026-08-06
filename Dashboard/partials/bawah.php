
</main>

<script>
    function toggleTheme() {
        const next = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
        document.documentElement.dataset.theme = next;
        localStorage.setItem("employee-theme", next);
        document.querySelectorAll(".theme-toggle").forEach((button) => button.textContent = next === "dark" ? "☀️ Light" : "🌙 Dark");
    }
    document.querySelectorAll(".theme-toggle").forEach((button) => button.textContent = document.documentElement.dataset.theme === "dark" ? "☀️ Light" : "🌙 Dark");
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        sidebar.classList.toggle("show");
    }

    const posisiSort = sessionStorage.getItem("posisi-sort-tabel");
    if (posisiSort !== null) {
        sessionStorage.removeItem("posisi-sort-tabel");
        requestAnimationFrame(() => window.scrollTo(0, Number(posisiSort)));
    }

    document.querySelectorAll("[data-sort-link]").forEach((tautan) => {
        tautan.addEventListener("click", () => {
            sessionStorage.setItem("posisi-sort-tabel", String(window.scrollY));
        });
    });
</script>

</body>
</html>
