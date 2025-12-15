<?php
// Halaman Login Terpadu (Admin, Asisten, Mahasiswa)
if (isset($_SESSION['user_id'])) {
    // Sudah login, redirect ke dashboard sesuai role
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: index.php?page=admin_dashboard");
            break;
        case 'asisten':
            header("Location: index.php?page=asisten_dashboard");
            break;
        case 'mahasiswa':
            header("Location: index.php?page=mahasiswa_dashboard");
            break;
    }
    exit;
}

// Proses login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Query untuk semua role (admin, asisten, mahasiswa)
    $query = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($query) == 1) {
        $user = mysqli_fetch_assoc($query);
        
        // Cek password (untuk demo, password plain text. Production harus pakai password_hash)
        if ($password == $user['password'] || password_verify($password, $user['password'])) {
            // Regenerate session ID untuk keamanan
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Remember Me - simpan token di cookie selama 30 hari
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 hari
                
                // Simpan token di database
                mysqli_query($conn, "UPDATE users SET remember_token = '$token', token_expires = FROM_UNIXTIME($expires) WHERE id = '{$user['id']}'");
                
                // Set cookie
                setcookie('remember_token', $token, $expires, '/', '', false, true);
                setcookie('remember_user', $user['id'], $expires, '/', '', false, true);
            }
            
            log_aktivitas($user['id'], 'LOGIN', 'users', $user['id'], 'User login berhasil sebagai ' . $user['role']);
            
            // Redirect sesuai role
            switch ($user['role']) {
                case 'admin':
                    header("Location: index.php?page=admin_dashboard");
                    break;
                case 'asisten':
                    header("Location: index.php?page=asisten_dashboard");
                    break;
                case 'mahasiswa':
                    header("Location: index.php?page=mahasiswa_dashboard");
                    break;
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username/NIM tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Loading Screen Styles */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #001f5c 0%, #003399 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }

        .loading-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .logo-container {
            position: relative;
            width: 220px;
            height: 220px;
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-frame {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(45deg, #0066cc, #0099ff, #66cc00, #0066cc);
            animation: rotate 8s linear infinite;
            box-shadow: 0 0 40px rgba(0, 102, 204, 0.6);
        }

        .logo-frame::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            bottom: 8px;
            background: #001f5c;
            border-radius: 50%;
        }

        .logo-image {
            position: relative;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .logo-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .loading-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
            background: linear-gradient(90deg, #0099ff, #66cc00);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .loading-subtitle {
            font-size: 1rem;
            margin-bottom: 25px;
            text-align: center;
            color: #99ccff;
            font-weight: 300;
        }

        .loading-bar-container {
            width: 250px;
            height: 6px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

        .loading-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #0066cc, #0099ff, #66cc00);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .loading-message {
            font-size: 0.9rem;
            color: #99ccff;
            text-align: center;
            height: 20px;
            margin-top: 10px;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        /* Existing Login Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url("uploads/logo/Kampus-I-Balapan-1.png");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 20, 60, 0.35);
            z-index: 0;
            pointer-events: none;
        }
        
        .container {
            display: flex;
            width: 100%;
            max-width: 950px;
            min-height: 550px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.15), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.4);
            
            animation: fadeIn 0.8s ease-out;
            position: relative;
            z-index: 1;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .container.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .login-side {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(255, 255, 255, 0.95);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .welcome-side {
            flex: 1;
            background: linear-gradient(90deg, #0066cc, #0099ff, #16a1fdff);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
            
        }
        
        .welcome-side::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            top: -100px;
            right: -100px;
        }
        
        .welcome-side::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            bottom: -80px;
            left: -80px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            color: #0066cc;
        }
        
        .logo i {
            font-size: 28px;
            margin-right: 10px;
        }
        
        .logo span {
            font-size: 24px;
            font-weight: 700;
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #444;
            font-weight: 500;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #0066cc;
            font-size: 18px;
        }
        
        input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e8ff;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #f8fbff;
        }
        
        input:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
            background-color: white;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-danger {
            background: #ffe0e0;
            color: #d63031;
            border: 1px solid #ffb8b8;
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #0066cc 0%, #0099ff 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(78, 115, 223, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .demo-info {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fc;
            border-radius: 10px;
            font-size: 13px;
            color: #666;
        }
        
        .demo-info p {
            margin: 5px 0;
        }
        
        .demo-info strong {
            color: #0066cc;
        }
        
        .welcome-title {
            font-size: 36px;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        
        .welcome-text {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
            max-width: 400px;
        }
        
        .features {
            list-style: none;
            text-align: left;
            margin-top: 30px;
            position: relative;
            z-index: 2;
        }
        
        .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .features i {
            margin-right: 12px;
            font-size: 20px;
            background: rgba(255, 255, 255, 0.2);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .floating-element {
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            background: rgba(255, 255, 255, 0.15);
            z-index: 1;
            animation: float 6s ease-in-out infinite;
        }
        
        .element1 {
            top: 20%;
            left: 10%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }
        
        .element2 {
            bottom: 15%;
            right: 15%;
            width: 120px;
            height: 120px;
            animation-delay: 2s;
        }
        
        .welcome-icon {
            font-size: 80px;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        
        @media (max-width: 900px) {
            body {
                background-attachment: scroll;
            }
            
            .container {
                flex-direction: column;
                height: auto;
                max-width: 500px;
                min-height: auto;
            }
            
            .welcome-side {
                order: -1;
                padding: 40px 30px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            }
            
            .login-side {
                padding: 40px 30px;
                border-right: none;
            }
            
            .welcome-side::before, 
            .welcome-side::after {
                display: none;
            }
            
            .welcome-icon {
                font-size: 60px;
            }
            
            .welcome-title {
                font-size: 28px;
            }
            
            .features {
                display: none;
            }

            /* Loading screen responsive */
            .logo-container {
                width: 180px;
                height: 180px;
            }

            .logo-image {
                width: 150px;
                height: 150px;
            }

            .loading-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                border-radius: 15px;
                max-width: 90vw;
            }
            
            .login-side, .welcome-side {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .welcome-title {
                font-size: 24px;
            }
            
            .logo span {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="logo-container">
            <div class="logo-frame"></div>
            <div class="logo-image">
                <!-- Logo Universitas AKPRIND - diganti dengan Logo-AU.png -->
                <img src="includes/logo-AU.png" alt="Logo Universitas AKPRIND" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
        </div>
        
        <div class="loading-content">
            <h1 class="loading-title">Universitas AKPRIND</h1>
            <p class="loading-subtitle">Menyiapkan Sistem Presensi Lab</p>
            
            <div class="loading-bar-container">
                <div class="loading-bar" id="loadingBar"></div>
            </div>
            
            <div class="loading-message" id="loadingMessage">
                Memuat antarmuka login...
            </div>
        </div>
    </div>

    <!-- Main Login Container -->
    <div class="container" id="loginContainer">
        <div class="login-side">
            
            
            <h1>Masuk ke Sistem</h1>
            <p class="subtitle">Silakan login untuk mengakses sistem presensi.</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="username">Username / NIM</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Masukkan Username atau NIM" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; cursor: pointer; font-weight: normal;">
                        <input type="checkbox" name="remember" id="remember" style="width: auto; margin-right: 10px; padding: 0;">
                        <span>Ingat saya selama 30 hari</span>
                    </label>
                </div>
                
                <button type="submit" class="login-btn" id="btnLogin">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>
            
            
        </div>
        
        <div class="welcome-side">
            <div class="floating-element element1"></div>
            <div class="floating-element element2"></div>
            
            <!-- Logo di welcome side - juga diganti dengan Logo-AU.png -->
            <img src="includes/logo-AU.png" 
     alt="Logo" 
     style="height: 60px; width: auto; margin-bottom: 20px;">
            <h2 class="welcome-title">Selamat Datang!</h2>
            <p class="welcome-text">
                Sistem Presensi Lab Kampus untuk memudahkan pengelolaan kehadiran praktikum mahasiswa.
            </p>
            
            <ul class="features">
                <li>
                    <i class="fas fa-qrcode"></i>
                    <span>Presensi cepat dengan QR Code</span>
                </li>
                <li>
                    <i class="fas fa-chart-bar"></i>
                    <span>Monitoring kehadiran real-time</span>
                </li>
                <li>
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan dan rekap otomatis</span>
                </li>
                <li>
                    <i class="fas fa-mobile-alt"></i>
                    <span>Akses dari perangkat manapun</span>
                </li>
            </ul>
        </div>
    </div>

    <script>
        // Loading screen simulation
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            const loginContainer = document.getElementById('loginContainer');
            const loadingBar = document.getElementById('loadingBar');
            const loadingMessage = document.getElementById('loadingMessage');
            
            // Data untuk simulasi loading
            const loadingMessages = [
                "Memuat antarmuka login...",
                "Menyiapkan sistem keamanan...",
                "Memuat komponen formulir...",
                "Menyiapkan database...",
                "Hampir selesai...",
                "Sistem siap!"
            ];
            
            let progress = 0;
            let currentMessageIndex = 0;
            
            // Fungsi untuk update progress
            function updateProgress() {
                if (progress >= 100) {
                    // Loading selesai, tampilkan login container dan sembunyikan loading screen
                    setTimeout(() => {
                        loadingScreen.classList.add('hidden');
                        loginContainer.classList.add('visible');
                    }, 500);
                    return;
                }
                
                // Increment progress
                let increment = Math.random() * 5 + 2; // 2-7%
                progress = Math.min(progress + increment, 100);
                
                // Update progress bar
                loadingBar.style.width = progress + '%';
                
                // Update pesan loading setiap 20% progress
                if (progress >= currentMessageIndex * (100 / loadingMessages.length)) {
                    loadingMessage.textContent = loadingMessages[currentMessageIndex];
                    if (currentMessageIndex < loadingMessages.length - 1) {
                        currentMessageIndex++;
                    }
                }
                
                // Jadwalkan update berikutnya
                let delay = 80 + Math.random() * 120; // 80-200ms
                setTimeout(updateProgress, delay);
            }
            
            // Mulai proses loading setelah sedikit delay
            setTimeout(updateProgress, 300);
        });

        // Form submission handling dengan loading
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('btnLogin');
            const originalText = loginBtn.innerHTML;
            
            // Tampilkan loading
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            loginBtn.disabled = true;
        });
        
        // Efek hover pada input fields
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Animasi tambahan untuk elemen floating
        const floatingElements = document.querySelectorAll('.floating-element');
        floatingElements.forEach((el, index) => {
            el.style.animationDuration = (6 + index * 2) + 's';
        });
    </script>
</body>
</html>
