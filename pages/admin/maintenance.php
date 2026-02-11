<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .maintenance-card {
            max-width: 500px;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }
        .icon-box {
            width: 100px;
            height: 100px;
            background: #fff3cd;
            color: #ffc107;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="maintenance-card mx-auto">
            <div class="icon-box">
                <i class="fas fa-tools"></i>
            </div>
            <h2 class="fw-bold mb-3">Sedang Dalam Perbaikan</h2>
            <p class="text-muted mb-4">
                Sistem sedang dalam mode pemeliharaan untuk peningkatan performa. 
                Silakan coba beberapa saat lagi.
            </p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="alert alert-info small">
                    Login sebagai: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong>
                </div>
                <a href="index.php?page=logout" class="btn btn-outline-danger mt-2">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            <?php else: ?>
                <a href="index.php?page=login" class="btn btn-primary mt-2">
                    <i class="fas fa-sign-in-alt me-2"></i>Login Admin
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>