
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
        const root = document.documentElement;
        
        function updateThemeIcons(theme) {
            document.querySelectorAll('.theme-toggle').forEach(btn => {
                const icon = btn.querySelector('i');
                const text = btn.querySelector('span');
                
                if (theme === 'dark') {
                    if (icon) {
                        icon.classList.remove('fa-moon');
                        icon.classList.add('fa-sun');
                    }
                    if (text) text.textContent = 'Mode Terang';
                } else {
                    if (icon) {
                        icon.classList.remove('fa-sun');
                        icon.classList.add('fa-moon');
                    }
                    if (text) text.textContent = 'Mode Gelap';
                }
            });
        }

        // Initial State
        if (root.getAttribute('data-theme') === 'dark') {
            updateThemeIcons('dark');
        }

        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
            const currentTheme = root.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            root.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            updateThemeIcons(newTheme);
            });
        });
    </script>
</body>
</html>
