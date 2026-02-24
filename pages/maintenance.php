<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedang Dalam Pemeliharaan - <?= defined('APP_NAME') ? APP_NAME : 'Sistem Presensi' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#0066cc">
    <link rel="apple-touch-icon" href="includes/icon-192.png">

    <script>
        // Cek tema yang tersimpan di localStorage agar sesuai dengan preferensi user sebelumnya
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --card-bg: white;
            --text-color: #333;
            --text-muted: #666;
            --icon-bg: #fff8e1;
            --border-color: transparent;
        }
        [data-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --card-bg: #1e293b;
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --icon-bg: rgba(255, 170, 0, 0.1);
            --border-color: #334155;
        }
        body {
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            overflow: hidden;
            color: var(--text-color);
            transition: background 0.3s, color 0.3s;
        }
        .maintenance-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: background 0.3s, border-color 0.3s;
        }
        .maintenance-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #ffaa00, #ffcc00);
        }
        .icon-box {
            width: 100px;
            height: 100px;
            background: var(--icon-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: #ffaa00;
            font-size: 40px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 170, 0, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(255, 170, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 170, 0, 0); }
        }
        h1 {
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 15px;
            font-size: 1.8rem;
        }
        p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-login {
            background: #0066cc;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-login:hover {
            background: #0052a3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
            color: white;
        }
        .footer-text {
            margin-top: 30px;
            font-size: 12px;
            color: var(--text-muted);
            opacity: 0.7;
        }
        .btn-logout {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: block;
            width: 100%;
            box-shadow: 0 4px 15px rgba(255, 75, 43, 0.3);
        }
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 75, 43, 0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-box">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Sedang Dalam Pemeliharaan</h1>
        <p>
            Maaf, sistem sedang dalam perbaikan atau pembaruan rutin. 
            Silakan kembali lagi nanti. Kami sedang bekerja keras untuk meningkatkan layanan kami.
        </p>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- Tombol Login untuk Admin -->
            <a href="index.php?page=login" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Login Admin
            </a>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] != 'admin'): ?>
            <!-- Tombol Logout untuk user non-admin yang terjebak login -->
            <a href="index.php?page=logout" class="btn-logout">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        <?php endif; ?>
        
        <div class="footer-text">
            &copy; <?= date('Y') ?> <?= defined('APP_NAME') ? APP_NAME : 'Sistem Presensi' ?>. All rights reserved.
        </div>
    </div>
</body>
</html>