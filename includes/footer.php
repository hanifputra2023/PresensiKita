    <!-- Global Theme Toggle Button -->
    <button class="theme-toggle-btn" id="themeToggle" title="Ganti Tema">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <!-- Html5QrcodeScanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
        // Global Theme Toggle Logic
        const toggleBtn = document.getElementById('themeToggle');
        const root = document.documentElement;
        const icon = toggleBtn.querySelector('i');

        // Set initial icon based on current theme
        if (root.getAttribute('data-theme') === 'dark') {
            icon.classList.replace('fa-moon', 'fa-sun');
        }

        toggleBtn.addEventListener('click', () => {
            const currentTheme = root.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            root.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        });
    </script>
</body>
</html>
