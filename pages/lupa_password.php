<?php
// Halaman Lupa Password

$error = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = escape($_POST['username']);

    if (empty($username)) {
        $error = "Username atau NIM tidak boleh kosong.";
    } else {
        // Cari user di tabel mahasiswa atau asisten berdasarkan username atau NIM/kode
        $stmt = mysqli_prepare($conn, "
            SELECT m.nama, m.nim, m.no_hp, u.username 
            FROM mahasiswa m
            JOIN users u ON m.user_id = u.id
            WHERE m.nim = ? OR u.username = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);

            // Ambil semua pengaturan dari database
            $q_settings = mysqli_query($conn, "SELECT setting_key, setting_value FROM app_settings");
            $app_settings = [];
            while ($row = mysqli_fetch_assoc($q_settings)) {
                $app_settings[$row['setting_key']] = $row['setting_value'];
            }
            $admin_wa = $app_settings['contact_wa'] ?? '';

            if (empty($admin_wa)) {
                $error = "Nomor WhatsApp admin belum diatur. Silakan hubungi admin secara manual.";
            } else {
                // Format nomor WA
                if (substr($admin_wa, 0, 1) == '0') {
                    $admin_wa = '62' . substr($admin_wa, 1);
                }

                // Buat pesan
                $pesan = "Halo Admin,\n\nSaya ingin mengajukan reset password untuk akun presensi dengan detail berikut:\n\n";
                $pesan .= "Nama: " . $user_data['nama'] . "\n";
                $pesan .= "NIM: " . $user_data['nim'] . "\n";
                $pesan .= "Username: " . $user_data['username'] . "\n";
                $pesan .= "No. HP Terdaftar: " . ($user_data['no_hp'] ?: '-') . "\n\n";
                $pesan .= "Mohon bantuannya untuk mereset password saya.\n\nTerima kasih.";

                // URL-encode pesan
                $pesan_encoded = urlencode($pesan);

                // Buat link WhatsApp
                $wa_link = "https://wa.me/{$admin_wa}?text={$pesan_encoded}";

                // Redirect ke WhatsApp
                echo "<html><head><meta http-equiv='refresh' content='0;url=$wa_link'></head><body>";
                echo "<script>window.location.href = '$wa_link';</script>";
                echo "<div style='text-align:center;padding:20px;'>Sedang mengalihkan ke WhatsApp...<br><br><a href='$wa_link'>Klik di sini jika tidak dialihkan otomatis</a></div>";
                echo "</body></html>";
                exit;
            }
        } else {
            $error = "Username atau NIM tidak ditemukan. Pastikan Anda memasukkan data yang benar.";
        }
    }
}

// Fetch settings untuk tampilan jika belum diambil di atas
if (!isset($app_settings)) {
    $q_settings = mysqli_query($conn, "SELECT setting_key, setting_value FROM app_settings");
    while ($row = mysqli_fetch_assoc($q_settings)) {
        $app_settings[$row['setting_key']] = $row['setting_value'];
    }
}
$instansi_name = $app_settings['instansi_name'] ?? 'Universitas AKPRIND';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - <?= APP_NAME ?></title>
    
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
            top: -50%;
            left: -50%;
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
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-50px, -50px); }
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
            animation: spinLoader 1s linear infinite;
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

        :root {
            --primary-blue: #0066cc;
            --primary-blue-dark: #0052a3;
            --primary-blue-light: #0099ff;
            --bg-gradient: linear-gradient(135deg, #0052a3 0%, #0066cc 50%, #0099ff 100%);
            
            /* Glassmorphism Variables - TEMA TERANG */
            --container-bg: rgba(255, 255, 255, 0.1);
            --text-main: #ffffff;
            --text-secondary: #f8fafc;
            --text-muted: #cbd5e1;
            --input-bg: rgba(255, 255, 255, 0.1);
            --input-border: rgba(255, 255, 255, 0.2);
            --input-border: rgba(0, 102, 204, 0.5);
            --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        [data-theme="dark"] {
            --container-bg: rgba(0, 0, 0, 0.4);
            --text-main: #f1f5f9;
            --text-secondary: #e2e8f0;
            --text-muted: #94a3b8;
            --input-bg: rgba(0, 0, 0, 0.3);
            --input-border: rgba(255, 255, 255, 0.1);
            --card-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
            background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url("uploads/logo/Kampus-I-Balapan-1.png");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        [data-theme="dark"] body {
            background-image: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)), url("uploads/logo/Kampus-I-Balapan-1.png");
        }

        @keyframes gradientBG {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
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

        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; top: 20%; animation-delay: 0s; animation-duration: 25s; }
        .particle:nth-child(2) { width: 60px; height: 60px; right: 15%; top: 60%; animation-delay: 2s; animation-duration: 20s; }
        .particle:nth-child(3) { width: 100px; height: 100px; left: 70%; top: 30%; animation-delay: 4s; animation-duration: 30s; }
        .particle:nth-child(4) { width: 50px; height: 50px; left: 20%; bottom: 20%; animation-delay: 1s; animation-duration: 22s; }
        .particle:nth-child(5) { width: 70px; height: 70px; right: 25%; bottom: 30%; animation-delay: 3s; animation-duration: 28s; }

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
            margin: auto;
            position: relative;
            width: 100%;
            max-width: 460px;
            background: var(--container-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 48px 44px 52px;
            box-shadow: 
                0 0 0 1px rgba(0, 102, 204, 0.3),
                var(--card-shadow),
                0 0 60px rgba(0, 102, 204, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
            opacity: 1;
            transform: translateY(30px) scale(0.98);
            transition: all 0.9s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }

        .login-container.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 32px;
            padding: 6px;
            background: linear-gradient(135deg, rgba(0, 153, 255, 0.92) 0%, rgba(0, 122, 204, 0.85) 50%, rgba(0, 82, 163, 0.9) 100%);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            z-index: -1;
            opacity: 0.95;
            filter: blur(0.6px);
        }

        [data-theme="dark"] .login-container::before {
            background: linear-gradient(135deg, rgba(0, 153, 255, 0.9), rgba(0, 82, 163, 0.9), rgba(0, 102, 204, 0.9), rgba(0, 153, 255, 0.9));
        }

        .login-container::after {
            content: '';
            position: absolute;
            top: -15px;
            left: -15px;
            right: -15px;
            bottom: -15px;
            background: radial-gradient(circle at 20% 30%, rgba(0, 153, 255, 0.15) 0%, transparent 60%), radial-gradient(circle at 80% 70%, rgba(0, 102, 204, 0.1) 0%, transparent 60%), radial-gradient(circle at 40% 80%, rgba(0, 82, 163, 0.08) 0%, transparent 60%);
            border-radius: 45px;
            z-index: -2;
            filter: blur(25px);
        }

        [data-theme="dark"] .login-container::after {
            background: radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.1) 0%, transparent 60%), radial-gradient(circle at 80% 70%, rgba(96, 165, 250, 0.08) 0%, transparent 60%), radial-gradient(circle at 40% 80%, rgba(37, 99, 235, 0.06) 0%, transparent 60%);
        }

        .corner-glow {
            position: absolute;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(0, 153, 255, 0.35) 0%, transparent 70%);
            filter: blur(20px);
            z-index: -1;
            opacity: 0.8;
        }

        .corner-glow.top-left { top: -40px; left: -40px; }
        .corner-glow.top-right { top: -40px; right: -40px; }
        .corner-glow.bottom-left { bottom: -40px; left: -40px; }
        .corner-glow.bottom-right { bottom: -40px; right: -40px; }

        [data-theme="dark"] .corner-glow {
            background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
        }

        /* Theme Switch */
        .theme-switch-wrapper {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            z-index: 100;
        }

        .theme-switch {
            display: inline-block;
            height: 30px;
            position: relative;
            width: 60px;
        }

        .theme-switch input { display: none; }

        .slider {
            background-color: rgba(255, 255, 255, 0.25);
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: .4s;
            border-radius: 34px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(0, 102, 204, 0.5);
            backdrop-filter: blur(4px);
        }

        .slider:before {
            background-color: #fff;
            bottom: 3px;
            content: "";
            height: 22px;
            left: 4px;
            position: absolute;
            transition: .4s;
            width: 22px;
            border-radius: 50%;
            z-index: 2;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .slider { background-color: rgba(15, 23, 42, 0.6); border-color: rgba(255, 255, 255, 0.1); }
        input:checked + .slider:before { transform: translateX(30px); background-color: var(--primary-blue); }

        .slider .fas { position: absolute; top: 50%; transform: translateY(-50%); font-size: 14px; transition: opacity 0.3s; z-index: 1; }
        .slider .fa-sun { left: 8px; color: #fbbf24; opacity: 0; }
        .slider .fa-moon { right: 8px; color: #f1f5f9; opacity: 1; }
        input:checked + .slider .fa-sun { opacity: 1; }
        input:checked + .slider .fa-moon { opacity: 0; }

        .logo-section { text-align: center; margin-bottom: 40px; padding-bottom: 20px; position: relative; }
        .logo-wrapper { display: inline-block; position: relative; margin-bottom: 20px; width: 100%; max-width: 250px; }
        .logo-wrapper img { width: 100%; height: auto; position: relative; z-index: 2; filter: drop-shadow(0 8px 20px rgba(0, 102, 204, 0.2)); animation: logoFloat 5s ease-in-out infinite; }
        @keyframes logoFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-8px); } }
        .system-title { font-size: 25px; font-weight: 600; color: #ffffff !important; letter-spacing: 0.5px; text-transform: uppercase; margin-top: 8px; text-shadow: 0 0 20px rgba(255, 255, 255, 0.3), 0 0 40px rgba(0, 153, 255, 0.2); }
        .login-section { position: relative; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; margin-bottom: 10px; color: #ffffff !important; font-weight: 600; font-size: 13px; letter-spacing: 0.3px; text-transform: uppercase; text-shadow: 0 0 10px rgba(255, 255, 255, 0.2); }
        .input-group { position: relative; }
        .form-control { width: 100%; padding: 16px 18px 16px 52px; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 14px; background: var(--input-bg); color: #ffffff !important; font-size: 15px; font-weight: 500; transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.6) !important; opacity: 1; }
        .form-control:focus { outline: none; border-color: rgba(255, 255, 255, 0.5); background: rgba(255, 255, 255, 0.15); box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1), 0 0 30px rgba(0, 153, 255, 0.2); transform: translateY(-2px); color: #ffffff !important; }
        .input-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #ffffff !important; font-size: 18px; opacity: 0.9; transition: all 0.35s ease; pointer-events: none; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3); }
        .form-control:focus ~ .input-icon { opacity: 1; transform: translateY(-50%) scale(1.1); text-shadow: 0 0 15px rgba(255, 255, 255, 0.6), 0 0 30px rgba(0, 153, 255, 0.4); }
        .alert { display: flex; align-items: center; padding: 14px 18px; margin-bottom: 24px; border-radius: 12px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; border: 2px solid rgba(220, 38, 38, 0.15); font-size: 14px; font-weight: 500; }
        .alert i { margin-right: 12px; font-size: 18px; }
        [data-theme="dark"] .alert { background: rgba(220, 38, 38, 0.15); color: #fca5a5; border-color: rgba(220, 38, 38, 0.25); }
        .btn-submit { width: 100%; padding: 17px; border: none; border-radius: 14px; background: #0066cc; color: white !important; font-size: 15px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; cursor: pointer; transition: none; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3); position: relative; overflow: hidden; }
        .btn-submit i { color: #ffffff !important; margin-right: 10px; text-shadow: 0 0 10px rgba(255, 255, 255, 0.4); }
        [data-theme="dark"] .btn-submit { background: #1e293b; box-shadow: 0 4px 12px rgba(30, 41, 59, 0.5); }
        .login-footer { margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255, 255, 255, 0.1); border-top: 1px solid rgba(0, 102, 204, 0.3); text-align: center; }
        .footer-link { font-size: 13px; color: var(--primary-blue-light); text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 6px; }
        .footer-link:hover { color: var(--primary-blue-light); transform: translateY(-2px); }
        .footer-link i { font-size: 14px; }
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

    <!-- Corner Glow Effects -->
    <div class="corner-glow top-left"></div>
    <div class="corner-glow top-right"></div>
    <div class="corner-glow bottom-left"></div>
    <div class="corner-glow bottom-right"></div>

    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="logo-container">
            <div class="logo-frame"></div>
            <div class="logo-image">
                <img id="loadingLogo" src="includes/logo-AU.png" alt="Logo Universitas AKPRIND">
            </div>
        </div>
        <div class="loading-content">
            <h1 class="loading-title"><?= htmlspecialchars($instansi_name) ?></h1>
            <p class="loading-subtitle">Menyiapkan Sistem Presensi Lab</p>
            <div class="loading-message" id="loadingMessage">Memuat antarmuka...</div>
        </div>
    </div>

    <!-- Theme Switch -->
    <div class="theme-switch-wrapper" title="Ganti Tema">
        <label class="theme-switch" for="checkbox-theme">
            <input type="checkbox" id="checkbox-theme" />
            <div class="slider round">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
            </div>
        </label>
    </div>

    <div class="login-container" id="loginContainer">
        <div class="logo-section">
            <div class="logo-wrapper">
                <img id="mainLogo" src="includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png" alt="Logo <?= htmlspecialchars($instansi_name) ?>">
            </div>
            <div class="system-title">Lupa Password</div>
        </div>

        <div class="login-section">
            <?php if (!empty($error)): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <p style="color: var(--text-main); text-align: center; margin-bottom: 24px; font-size: 14px; opacity: 0.9;">
                Masukkan NIM atau Username Anda. Kami akan mengarahkan Anda ke WhatsApp untuk menghubungi Admin.
            </p>

            <form id="forgotPasswordForm" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Username / NIM</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username atau NIM" required autofocus>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btnSubmit">
                    <i class="fab fa-whatsapp"></i> Hubungi Admin
                </button>
            </form>

            <div class="login-footer">
                <a href="index.php?page=login" class="footer-link">
                    <i class="fas fa-arrow-left"></i> Kembali ke Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Loading Screen Logic
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            const loginContainer = document.getElementById('loginContainer');
            
            // Sembunyikan loading screen setelah 1.5 detik
            setTimeout(() => {
                loadingScreen.classList.add('hidden');
                setTimeout(() => {
                    loginContainer.classList.add('visible');
                }, 100);
            }, 1500);

            // Theme Toggle
            const themeSwitch = document.getElementById('checkbox-theme');
            const root = document.documentElement;
            const mainLogo = document.getElementById('mainLogo');
            const loadingLogo = document.getElementById('loadingLogo');

            function updateLogo(theme) {
                const loadingPath = theme === 'dark' ? 'includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png' : 'includes/logo-AU.png';
                mainLogo.src = 'includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png';
                loadingLogo.src = loadingPath;
            }

            function setTheme(theme) {
                root.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                if (themeSwitch) themeSwitch.checked = (theme === 'dark');
                updateLogo(theme);
            }
            
            if (themeSwitch) {
                themeSwitch.addEventListener('change', function(e) { setTheme(e.target.checked ? 'dark' : 'light'); });
            }

            const currentTheme = localStorage.getItem('theme') || 'light';
            setTheme(currentTheme);
        });

        document.getElementById('forgotPasswordForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmit');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            btn.disabled = true;
        });
    </script>
</body>
</html>