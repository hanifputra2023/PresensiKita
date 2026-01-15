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
    
    // [SECURITY] Gunakan Prepared Statement untuk mencegah SQL Injection
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // [MIGRASI OTOMATIS] Cek password dengan dua metode
        $password_match = false;

        // 1. Coba verifikasi sebagai HASH (metode modern dan aman)
        if (password_verify($password, $user['password'])) {
            $password_match = true;
        } 
        // 2. Jika gagal, coba verifikasi sebagai PLAIN TEXT (untuk migrasi)
        // dan langsung update ke hash jika cocok.
        elseif ($password === $user['password']) {
            $password_match = true;
            // [SECURITY] Update password lama (plain text) ke hash - prepared statement
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_upd_pass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_upd_pass, "si", $new_hash, $user['id']);
            mysqli_stmt_execute($stmt_upd_pass);
        }

        if ($password_match) {
            session_regenerate_id(true); // Regenerate session ID untuk keamanan
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60);
                
                // [SECURITY] Gunakan Prepared Statement juga di sini
                $stmt_token = mysqli_prepare($conn, "UPDATE users SET remember_token = ?, token_expires = FROM_UNIXTIME(?) WHERE id = ?");
                mysqli_stmt_bind_param($stmt_token, "sii", $token, $expires, $user['id']);
                mysqli_stmt_execute($stmt_token);

                setcookie('remember_token', $token, $expires, '/', '', false, true);
                setcookie('remember_user', $user['id'], $expires, '/', '', false, true);
            }
            
            log_aktivitas($user['id'], 'LOGIN', 'users', $user['id'], 'User login berhasil sebagai ' . $user['role']);
            
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
    
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#0066cc">
    <link rel="icon" type="image/png" sizes="192x192" href="includes/icon-192.png">
    <link rel="apple-touch-icon" href="includes/icon-192.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    </script>
    <style>
        /* Loading Screen */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0052a3 0%, #0066cc 50%, #0099ff 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.8s ease, visibility 0.8s ease;
            overflow: hidden;
        }

        [data-theme="dark"] .loading-screen {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        }

        .loading-screen::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 102, 204, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 153, 255, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(0, 82, 163, 0.3) 0%, transparent 50%);
            animation: gradientShift 15s ease infinite;
        }

        [data-theme="dark"] .loading-screen::before {
            background: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(96, 165, 250, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(37, 99, 235, 0.15) 0%, transparent 50%);
        }

        @keyframes gradientShift {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-50px, -50px) rotate(180deg); }
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
            z-index: 2;
        }

        .logo-frame {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 8px solid transparent;
            border-top-color: #0066cc;
            border-right-color: #0099ff;
            animation: spinLoader 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
            box-shadow: 0 0 40px rgba(0, 102, 204, 0.6),
                        0 0 80px rgba(0, 153, 255, 0.4),
                        inset 0 0 30px rgba(0, 102, 204, 0.2);
        }

        [data-theme="dark"] .logo-frame {
            border-top-color: #3b82f6;
            border-right-color: #60a5fa;
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.4),
                        0 0 80px rgba(96, 165, 250, 0.3),
                        inset 0 0 30px rgba(59, 130, 246, 0.15);
        }

        .logo-frame::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border-radius: 50%;
            border: 4px solid transparent;
            border-bottom-color: rgba(0, 153, 255, 0.5);
            border-left-color: rgba(0, 82, 163, 0.5);
            animation: spinLoaderReverse 2s linear infinite;
        }

        [data-theme="dark"] .logo-frame::before {
            border-bottom-color: rgba(96, 165, 250, 0.4);
            border-left-color: rgba(59, 130, 246, 0.4);
        }

        @keyframes spinLoader {
            0% { 
                transform: rotate(0deg);
                border-top-color: #0066cc;
                border-right-color: #0099ff;
            }
            50% {
                border-top-color: #0099ff;
                border-right-color: #0052a3;
            }
            100% { 
                transform: rotate(360deg);
                border-top-color: #0066cc;
                border-right-color: #0099ff;
            }
        }

        [data-theme="dark"] .logo-frame {
            animation-name: spinLoaderDark;
        }

        @keyframes spinLoaderDark {
            0% { 
                transform: rotate(0deg);
                border-top-color: #3b82f6;
                border-right-color: #60a5fa;
            }
            50% {
                border-top-color: #60a5fa;
                border-right-color: #2563eb;
            }
            100% { 
                transform: rotate(360deg);
                border-top-color: #3b82f6;
                border-right-color: #60a5fa;
            }
        }

        @keyframes spinLoaderReverse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }

        .logo-image {
            position: relative;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.98);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3),
                        0 0 0 4px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            backdrop-filter: blur(12px);
            animation: logoPulseLoading 2s ease-in-out infinite;
        }

        [data-theme="dark"] .logo-image {
            background: rgba(30, 41, 59, 0.95);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.5),
                        0 0 0 4px rgba(59, 130, 246, 0.2);
        }

        @keyframes logoPulseLoading {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3),
                            0 0 0 4px rgba(255, 255, 255, 0.1);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 20px 60px rgba(0, 102, 204, 0.4),
                            0 0 0 6px rgba(0, 153, 255, 0.2);
            }
        }

        [data-theme="dark"] .logo-image {
            animation-name: logoPulseLoadingDark;
        }

        @keyframes logoPulseLoadingDark {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.5),
                            0 0 0 4px rgba(59, 130, 246, 0.2);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 20px 60px rgba(59, 130, 246, 0.3),
                            0 0 0 6px rgba(96, 165, 250, 0.3);
            }
        }

        .logo-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            animation: logoFloatLoading 3s ease-in-out infinite;
        }

        @keyframes logoFloatLoading {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(2deg); }
        }

        .loading-content {
            text-align: center;
            z-index: 2;
        }

        .loading-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
            background: linear-gradient(90deg, #ffffff, #e6f2ff, #ffffff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.5);
        }

        .loading-subtitle {
            font-size: 1rem;
            margin-bottom: 40px;
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
        }

        .loading-message {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.95);
            text-align: center;
            height: 24px;
            margin-top: 10px;
            font-weight: 500;
            letter-spacing: 0.3px;
            animation: messageGlow 2s ease-in-out infinite;
        }

        @keyframes messageGlow {
            0%, 100% { 
                opacity: 0.8;
                text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
            }
            50% { 
                opacity: 1;
                text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            }
        }

        /* Modern Elegant Login Design */
        
        :root {
            --primary-blue: #0066cc;
            --primary-blue-dark: #0052a3;
            --primary-blue-light: #0099ff;
            --bg-gradient: linear-gradient(135deg, #0052a3 0%, #0066cc 50%, #0099ff 100%);
            
            --container-bg: rgba(255, 255, 255, 0.96);
            --text-main: #1e293b;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --card-shadow: 0 25px 50px -12px rgba(0, 102, 204, 0.15);
        }

        [data-theme="dark"] {
            --container-bg: rgba(15, 23, 42, 0.95);
            --text-main: #f1f5f9;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --input-bg: rgba(30, 41, 59, 0.5);
            --input-border: rgba(100, 116, 139, 0.2);
            --card-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: var(--bg-gradient);
            background-size: 400% 400%;
            animation: gradientBG 25s ease infinite;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        }

        @keyframes gradientBG {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 20%, rgba(0, 102, 204, 0.15) 0%, transparent 60%),
                radial-gradient(circle at 80% 80%, rgba(0, 153, 255, 0.15) 0%, transparent 60%);
            pointer-events: none;
            animation: bgPulse 12s ease infinite;
        }

        [data-theme="dark"] body::before {
            background: 
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 80% 80%, rgba(96, 165, 250, 0.08) 0%, transparent 60%);
        }

        @keyframes bgPulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            animation: float 20s infinite;
        }

        [data-theme="dark"] .particle {
            background: rgba(59, 130, 246, 0.1);
        }

        .particle:nth-child(1) {
            width: 80px;
            height: 80px;
            left: 10%;
            top: 20%;
            animation-delay: 0s;
            animation-duration: 25s;
        }

        .particle:nth-child(2) {
            width: 60px;
            height: 60px;
            right: 15%;
            top: 60%;
            animation-delay: 2s;
            animation-duration: 20s;
        }

        .particle:nth-child(3) {
            width: 100px;
            height: 100px;
            left: 70%;
            top: 30%;
            animation-delay: 4s;
            animation-duration: 30s;
        }

        .particle:nth-child(4) {
            width: 50px;
            height: 50px;
            left: 20%;
            bottom: 20%;
            animation-delay: 1s;
            animation-duration: 22s;
        }

        .particle:nth-child(5) {
            width: 70px;
            height: 70px;
            right: 25%;
            bottom: 30%;
            animation-delay: 3s;
            animation-duration: 28s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.3;
            }
            25% {
                transform: translate(30px, -30px) scale(1.1);
                opacity: 0.5;
            }
            50% {
                transform: translate(-20px, 40px) scale(0.9);
                opacity: 0.2;
            }
            75% {
                transform: translate(40px, 20px) scale(1.05);
                opacity: 0.4;
            }
        }
        
        .login-container {
            width: 100%;
            max-width: 460px;
            background: var(--container-bg);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border-radius: 32px;
            padding: 48px 44px 52px;
            box-shadow: var(--card-shadow),
                        0 0 0 1px rgba(0, 102, 204, 0.06);
            position: relative;
            z-index: 1;
            opacity: 0;
            transform: translateY(30px) scale(0.98);
            transition: all 0.9s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .login-container.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        
        .theme-toggle-btn {
            position: absolute;
            top: 24px;
            right: 24px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
        }

        .theme-toggle-btn:active {
            transform: scale(0.95);
        }

        [data-theme="dark"] .theme-toggle-btn {
            background: rgba(0, 102, 204, 0.2);
            border-color: rgba(0, 153, 255, 0.3);
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 36px;
            position: relative;
        }

        .logo-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, transparent, var(--primary-blue), transparent);
            border-radius: 4px;
            opacity: 0.4;
        }

        .logo-wrapper {
            display: inline-block;
            position: relative;
            margin-bottom: 24px;
        }

        .logo-wrapper::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-light));
            border-radius: 50%;
            opacity: 0.08;
            animation: logoPulse 5s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { 
                transform: translate(-50%, -50%) scale(1); 
                opacity: 0.08; 
            }
            50% { 
                transform: translate(-50%, -50%) scale(1.12); 
                opacity: 0.12; 
            }
        }

        .logo-wrapper img {
            width: 88px;
            height: auto;
            position: relative;
            z-index: 2;
            animation: logoFloat 5s ease-in-out infinite;
            filter: drop-shadow(0 8px 20px rgba(0, 102, 204, 0.2));
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .university-name {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 8px;
            letter-spacing: -0.4px;
            line-height: 1.2;
        }

        .system-name {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* Login Section */
        .login-section {
            position: relative;
        }

        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        
        .input-group {
            position: relative;
        }
        
        .form-control {
            width: 100%;
            padding: 16px 18px 16px 52px;
            border: 2px solid var(--input-border);
            border-radius: 14px;
            background: var(--input-bg);
            color: var(--text-main);
            font-size: 15px;
            font-weight: 500;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.08),
                        0 2px 8px rgba(0, 102, 204, 0.04);
            transform: translateY(-2px);
        }

        [data-theme="dark"] .form-control:focus {
            background: var(--input-bg);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-blue);
            font-size: 18px;
            opacity: 0.4;
            transition: all 0.35s ease;
            pointer-events: none;
        }

        .form-control:focus ~ .input-icon {
            opacity: 1;
            transform: translateY(-50%) scale(1.1);
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary-blue);
            cursor: pointer;
            font-size: 18px;
            padding: 8px;
            border-radius: 8px;
            opacity: 0.4;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            opacity: 1;
            background: rgba(0, 102, 204, 0.08);
            transform: translateY(-50%) scale(1.1);
        }

        .form-control[type="password"] {
            padding-right: 54px;
        }

        /* Alert */
        .alert {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            margin-bottom: 24px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border: 2px solid rgba(220, 38, 38, 0.15);
            font-size: 14px;
            font-weight: 500;
            animation: alertSlide 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes alertSlide {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .alert i {
            margin-right: 12px;
            font-size: 18px;
        }
        
        [data-theme="dark"] .alert {
            background: rgba(220, 38, 38, 0.15);
            color: #fca5a5;
            border-color: rgba(220, 38, 38, 0.25);
        }
        
        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
            accent-color: var(--primary-blue);
        }

        .form-check-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 17px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #1a2332 0%, #2d3e50 100%);
            color: white;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: none;
            box-shadow: 0 12px 28px rgba(26, 35, 50, 0.4);
            position: relative;
            overflow: hidden;
        }

        [data-theme="dark"] .btn-submit {
            background: linear-gradient(135deg, #1a2332 0%, #2d3e50 100%);
            box-shadow: 0 12px 28px rgba(26, 35, 50, 0.5);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-submit .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Login Footer */
        .login-footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid rgba(0, 102, 204, 0.1);
            text-align: center;
        }

        .login-footer-text {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .login-footer-version {
            font-size: 11px;
            color: var(--text-muted);
            opacity: 0.7;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .login-footer-version i {
            font-size: 10px;
        }

        .login-footer-links {
            margin-top: 16px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .footer-link {
            font-size: 12px;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .footer-link:hover {
            color: var(--primary-blue-light);
            transform: translateY(-2px);
        }

        .footer-link i {
            font-size: 13px;
        }

        /* Stats Badge */
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(0, 102, 204, 0.08);
            border-radius: 20px;
            margin-top: 16px;
            font-size: 11px;
            color: var(--primary-blue);
            font-weight: 600;
        }

        .stats-badge i {
            font-size: 12px;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Responsive */
        @media (max-width: 600px) {
            body {
                padding: 30px 16px;
            }

            .login-container {
                padding: 40px 36px 44px;
                border-radius: 28px;
                max-width: 95%;
            }

            .logo-section {
                margin-bottom: 36px;
                padding-bottom: 32px;
            }

            .logo-wrapper img {
                width: 78px;
            }

            .logo-wrapper::before {
                width: 120px;
                height: 120px;
            }

            .university-name {
                font-size: 20px;
            }

            .system-name {
                font-size: 12px;
            }

            .login-title {
                font-size: 23px;
            }

            .theme-toggle-btn {
                width: 44px;
                height: 44px;
                top: 20px;
                right: 20px;
            }

            .form-control {
                padding: 15px 16px 15px 48px;
                font-size: 14px;
            }

            .input-icon {
                left: 16px;
                font-size: 17px;
            }

            .toggle-password {
                right: 16px;
            }

            .btn-submit {
                padding: 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 36px 32px 40px;
                border-radius: 24px;
            }

            .logo-wrapper img {
                width: 72px;
            }

            .university-name {
                font-size: 19px;
            }

            .login-title {
                font-size: 22px;
            }

            .login-footer {
                margin-top: 24px;
                padding-top: 20px;
            }

            .login-footer-links {
                gap: 16px;
            }

            .footer-link {
                font-size: 11px;
            }
        }

    </style>
</head>
<body>
    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="logo-container">
            <div class="logo-frame"></div>
            <div class="logo-image">
                <img id="loadingLogo" src="includes/logo-AU.png" alt="Logo Universitas AKPRIND">
            </div>
        </div>
        
        <div class="loading-content">
            <h1 class="loading-title">Universitas AKPRIND</h1>
            <p class="loading-subtitle">Menyiapkan Sistem Presensi Lab</p>
            
            <div class="loading-message" id="loadingMessage">
                Memuat antarmuka login...
            </div>
        </div>
    </div>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle-btn" id="themeToggle" title="Ganti Tema">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Login Container -->
    <div class="login-container" id="loginContainer">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-wrapper">
                <img id="mainLogo" src="includes/logo-AU.png" alt="Logo Universitas AKPRIND">
            </div>
            <h2 class="university-name">Universitas AKPRIND</h2>
            <p class="system-name">Sistem Presensi Lab</p>
        </div>

        <!-- Login Section -->
        <div class="login-section">
            <?php if (!empty($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Username / NIM</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username atau NIM" required autocomplete="username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()" title="Tampilkan/Sembunyikan Password">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">Ingat saya selama 30 hari</label>
                </div>

                <button type="submit" class="btn-submit" id="btnLogin">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>

            <!-- Login Footer -->
            <div class="login-footer">
                <p class="login-footer-text">© 2026 Universitas AKPRIND Yogyakarta</p>
                <div class="login-footer-version">
                    <i class="fas fa-shield-alt"></i>
                    <span>Sistem Presensi Lab v2.0</span>
                </div>
                <div class="stats-badge">
                    <i class="fas fa-circle"></i>
                    <span>Sistem Aktif & Aman</span>
                </div>
                <div class="login-footer-links">
                    <a href="#" class="footer-link" title="Bantuan">
                        <i class="fas fa-question-circle"></i>
                        <span>Bantuan</span>
                    </a>
                    <a href="#" class="footer-link" title="Kontak Admin">
                        <i class="fas fa-headset"></i>
                        <span>Kontak</span>
                    </a>
                    <a href="#" class="footer-link" title="Panduan">
                        <i class="fas fa-book"></i>
                        <span>Panduan</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Loading Screen Logic
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            const loginContainer = document.getElementById('loginContainer');
            const loadingMessage = document.getElementById('loadingMessage');
            
            const loadingMessages = [
                "Memuat antarmuka login...",
                "Menyiapkan sistem keamanan...",
                "Memuat komponen formulir...",
                "Menyiapkan koneksi database...",
                "Hampir selesai...",
                "Sistem siap!"
            ];
            
            let currentMessageIndex = 0;
            let messageInterval;
            
            // Update loading message setiap 400ms
            messageInterval = setInterval(() => {
                if (currentMessageIndex < loadingMessages.length - 1) {
                    currentMessageIndex++;
                    loadingMessage.textContent = loadingMessages[currentMessageIndex];
                }
            }, 400);
            
            // Sembunyikan loading screen setelah 2.5 detik
            setTimeout(() => {
                clearInterval(messageInterval);
                loadingScreen.classList.add('hidden');
                setTimeout(() => {
                    loginContainer.classList.add('visible');
                }, 100);
            }, 2500);

            // Theme Toggle
            const themeToggleBtn = document.getElementById('themeToggle');
            const root = document.documentElement;
            const icon = themeToggleBtn.querySelector('i');
            const mainLogo = document.getElementById('mainLogo');
            const loadingLogo = document.getElementById('loadingLogo');

            function updateLogo(theme) {
                const logoPath = theme === 'dark' 
                    ? 'includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png'
                    : 'includes/logo-AU.png';
                
                mainLogo.src = logoPath;
                loadingLogo.src = logoPath;
            }

            function setTheme(theme) {
                root.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                icon.className = `fas ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}`;
                updateLogo(theme);
            }
            
            themeToggleBtn.addEventListener('click', () => {
                const newTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
                
                themeToggleBtn.style.animation = 'pulse 0.4s ease';
                setTimeout(() => {
                    themeToggleBtn.style.animation = '';
                }, 400);
            });

            const currentTheme = localStorage.getItem('theme') || 'light';
            setTheme(currentTheme);
        });

        // Toggle Password Visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('btnLogin');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            loginBtn.disabled = true;
        });

        // PWA Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.php')
                    .then(reg => console.log('PWA ServiceWorker registered'))
                    .catch(err => console.log('PWA registration failed:', err));
            });
        }
    </script>
</body>
</html>
