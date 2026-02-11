<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        // Deteksi tema dari localStorage agar sesuai dengan preferensi user sebelumnya
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    </script>
    <style>
        :root {
            --primary-color: #0066cc;
            --bg-body: #f0f2f5;
            --bg-card: #ffffff;
            --text-main: #333333;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
            --gradient-icon: linear-gradient(135deg, #0066cc 0%, #00ccff 100%);
        }

        [data-theme="dark"] {
            --primary-color: #3a8fd9;
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --shadow: 0 10px 30px rgba(0,0,0,0.3);
            --gradient-icon: linear-gradient(135deg, #3a8fd9 0%, #60a5fa 100%);
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow-x: hidden;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Background decoration */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.6;
        }
        body::before {
            width: 300px;
            height: 300px;
            background: rgba(0, 102, 204, 0.15);
            top: -50px;
            left: -50px;
        }
        body::after {
            width: 250px;
            height: 250px;
            background: rgba(0, 204, 255, 0.1);
            bottom: -50px;
            right: -50px;
        }

        .maintenance-card {
            max-width: 480px;
            width: 90%;
            padding: 48px 40px;
            background: var(--bg-card);
            border-radius: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .icon-wrapper {
            width: 110px;
            height: 110px;
            background: rgba(0, 102, 204, 0.08);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            position: relative;
        }

        [data-theme="dark"] .icon-wrapper {
            background: rgba(58, 143, 217, 0.1);
        }

        .icon-wrapper i {
            font-size: 3.5rem;
            background: var(--gradient-icon);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: wrench-pulse 3s ease-in-out infinite;
        }

        @keyframes wrench-pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(-10deg); }
        }

        h2 {
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
            font-size: 1.75rem;
        }

        p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 32px;
            font-size: 1rem;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: rgba(0, 102, 204, 0.08);
            border-radius: 50px;
            color: var(--primary-color);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 24px;
            border: 1px solid rgba(0, 102, 204, 0.1);
        }
        
        [data-theme="dark"] .user-badge {
            background: rgba(58, 143, 217, 0.15);
            border-color: rgba(58, 143, 217, 0.2);
            color: #66b0ff;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="icon-wrapper">
            <i class="fas fa-tools"></i>
        </div>
        
        <h2>Sedang Dalam Perbaikan</h2>
        <p>
            Sistem sedang menjalani pemeliharaan terjadwal untuk peningkatan performa dan keamanan. 
            Kami akan segera kembali.
        </p>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-badge">
                <i class="fas fa-user-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
            </div>
            <div>
                <a href="index.php?page=logout" class="btn btn-outline-danger px-4 py-2 rounded-pill fw-bold">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        <?php else: ?>
            <a href="index.php?page=login" class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm">
                <i class="fas fa-sign-in-alt me-2"></i>Login Admin
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
