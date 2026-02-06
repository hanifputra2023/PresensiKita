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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Loading Screen (dipertahankan sama persis) */
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

        /* ===== REDESIGN LOGIN PAGE ===== */
        :root {
            --primary: #0066cc;
            --primary-dark: #0052a3;
            --primary-light: #0099ff;
            --primary-extra-light: #e6f2ff;
            --secondary: #7c3aed;
            --accent: #00d4aa;
            --white: #ffffff;
            --light-bg: #f8fafc;
            --dark-bg: #0f172a;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-blue: 0 10px 30px rgba(0, 102, 204, 0.2);
        }

        [data-theme="dark"] {
            --light-bg: #0f172a;
            --dark-bg: #1e293b;
            --text-dark: #f1f5f9;
            --text-light: #94a3b8;
            --border: #334155;
            --glass-bg: rgba(30, 41, 59, 0.3);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            transition: background 0.5s ease;
        }

        /* Background Elements */
        .background-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            opacity: 0.1;
            filter: blur(40px);
            animation: float 15s infinite ease-in-out;
        }

        .floating-shape:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .floating-shape:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -50px;
            right: -50px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent) 100%);
            animation-delay: -5s;
        }

        .floating-shape:nth-child(3) {
            width: 250px;
            height: 250px;
            top: 40%;
            right: 20%;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            25% {
                transform: translate(30px, -40px) scale(1.1);
            }
            50% {
                transform: translate(-20px, 30px) scale(0.9);
            }
            75% {
                transform: translate(-30px, -20px) scale(1.05);
            }
        }

        /* Grid Pattern */
        .grid-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 50px 50px;
            opacity: 0.1;
            z-index: -1;
        }

        /* Main Container */
        .login-main-container {
            width: 100%;
            max-width: 1400px;
            min-height: 800px;
            display: flex;
            border-radius: 40px;
            overflow: hidden;
            position: relative;
            opacity: 0;
            transform: translateY(40px);
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: var(--shadow-xl);
        }

        .login-main-container.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Left Side - Branding */
        .brand-side {
            flex: 0 0 45%;
            background: linear-gradient(135deg, #0052a3 0%, #0066cc 50%, #0099ff 100%);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        [data-theme="dark"] .brand-side {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #3b82f6 100%);
        }

        .brand-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }

        .brand-header {
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .brand-logo img {
            width: 60px;
            filter: brightness(0) invert(1);
        }

        .brand-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 15px;
            font-family: 'Montserrat', sans-serif;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 300;
            line-height: 1.6;
        }

        .brand-features {
            position: relative;
            z-index: 2;
            margin-top: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            color: rgba(255, 255, 255, 0.9);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-text h4 {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .feature-text p {
            font-size: 0.9rem;
            opacity: 0.8;
            font-weight: 300;
        }

        .brand-footer {
            position: relative;
            z-index: 2;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            font-weight: 300;
        }

        /* Right Side - Login Form */
        .login-side {
            flex: 0 0 55%;
            background: var(--white);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        [data-theme="dark"] .login-side {
            background: var(--dark-bg);
        }

        .login-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
        }

        .login-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .login-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 400;
        }

        /* Form Container */
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }

        /* Error Alert */
        .error-alert {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--error);
            font-weight: 500;
            animation: slideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            backdrop-filter: blur(10px);
        }

        .error-alert i {
            font-size: 1.2rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 18px 20px 18px 56px;
            background: var(--light-bg);
            border: 2px solid var(--border);
            border-radius: 16px;
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 400;
            transition: all 0.3s ease;
            outline: none;
            font-family: 'Poppins', sans-serif;
        }

        .form-input:focus {
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
            transform: translateY(-2px);
        }

        [data-theme="dark"] .form-input:focus {
            background: var(--dark-bg);
        }

        .form-input::placeholder {
            color: var(--text-light);
            font-weight: 300;
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus + .input-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            background: rgba(0, 102, 204, 0.1);
            color: var(--primary);
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }

        .custom-checkbox::after {
            content: '';
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 3px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .checkbox-container input:checked + .custom-checkbox {
            border-color: var(--primary);
            background: var(--primary-extra-light);
        }

        .checkbox-container input:checked + .custom-checkbox::after {
            opacity: 1;
        }

        .checkbox-label {
            color: var(--text-dark);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .forgot-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .forgot-link:hover::after {
            width: 100%;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: var(--shadow-blue);
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 102, 204, 0.3);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .btn-loading {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 40px 0;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 400;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border), transparent);
        }

        .divider::before {
            margin-right: 20px;
        }

        .divider::after {
            margin-left: 20px;
        }

        /* Social Login */
        .social-login {
            display: flex;
            gap: 16px;
            margin-bottom: 30px;
        }

        .social-btn {
            flex: 1;
            padding: 16px;
            border: 2px solid var(--border);
            border-radius: 14px;
            background: var(--light-bg);
            color: var(--text-dark);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .social-btn:hover {
            border-color: var(--primary);
            background: var(--primary-extra-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .social-btn i {
            font-size: 1.2rem;
        }

        .social-btn.google:hover {
            color: #DB4437;
            border-color: #DB4437;
        }

        .social-btn.microsoft:hover {
            color: #00A4EF;
            border-color: #00A4EF;
        }

        /* Signup Link */
        .signup-link {
            text-align: center;
            color: var(--text-light);
            font-size: 0.95rem;
            margin-top: 30px;
        }

        .signup-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            margin-left: 5px;
            transition: all 0.3s ease;
            position: relative;
        }

        .signup-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .signup-link a:hover::after {
            width: 100%;
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--white);
            border: none;
            color: var(--primary);
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
        }

        [data-theme="dark"] .theme-toggle {
            background: var(--dark-bg);
            color: var(--primary-light);
        }

        .theme-toggle:hover {
            transform: rotate(30deg) scale(1.1);
            box-shadow: var(--shadow-xl);
        }

        /* Particles Animation */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: var(--primary-light);
            border-radius: 50%;
            opacity: 0.5;
            animation: particleFloat 20s infinite linear;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.5;
            }
            90% {
                opacity: 0.5;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .login-main-container {
                max-width: 95%;
                min-height: 700px;
            }
            
            .brand-side, .login-side {
                padding: 50px 40px;
            }
            
            .brand-title {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 992px) {
            .login-main-container {
                flex-direction: column;
                max-width: 600px;
                min-height: auto;
            }
            
            .brand-side {
                flex: 0 0 300px;
                padding: 40px 30px;
            }
            
            .login-side {
                flex: 1;
                padding: 40px 30px;
            }
            
            .brand-features {
                display: none;
            }
            
            .theme-toggle {
                top: 20px;
                right: 20px;
                width: 48px;
                height: 48px;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .brand-side, .login-side {
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 1.8rem;
            }
            
            .form-input {
                padding: 16px 20px 16px 52px;
                font-size: 0.95rem;
            }
            
            .social-login {
                flex-direction: column;
            }
            
            .social-btn {
                padding: 14px;
            }
            
            .brand-title {
                font-size: 1.8rem;
            }
            
            .brand-subtitle {
                font-size: 1rem;
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

    <!-- Background Elements -->
    <div class="background-elements">
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="grid-pattern"></div>
        <div class="particles" id="particles"></div>
    </div>

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" title="Ganti Tema">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Main Login Container -->
    <div class="login-main-container" id="loginContainer">
        <!-- Left Side - Branding -->
        <div class="brand-side">
            <div class="brand-header">
                <div class="brand-logo">
                    <img id="brandLogo" src="includes/logo-AU.png" alt="Logo AKPRIND">
                </div>
                <h1 class="brand-title">Sistem Presensi<br>Laboratorium</h1>
                <p class="brand-subtitle">Platform terintegrasi untuk mengelola kehadiran mahasiswa di laboratorium dengan sistem yang modern dan efisien.</p>
            </div>
            
            <div class="brand-features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Keamanan Terjamin</h4>
                        <p>Sistem enkripsi tingkat tinggi untuk data sensitif</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Real-time Tracking</h4>
                        <p>Pantau kehadiran secara langsung dan akurat</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Analytics Lengkap</h4>
                        <p>Laporan dan statistik kehadiran yang detail</p>
                    </div>
                </div>
            </div>
            
            <div class="brand-footer">
                © 2024 Universitas AKPRIND Yogyakarta. All rights reserved.
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-side">
            <div class="login-header">
                <h2 class="login-title">Masuk ke Akun</h2>
                <p class="login-subtitle">Gunakan kredensial Anda untuk mengakses sistem</p>
            </div>

            <div class="form-container">
                <?php if (!empty($error)): ?>
                    <div class="error-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <form id="loginForm" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="username">Username / NIM</label>
                        <div class="input-wrapper">
                            <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username atau NIM" required autocomplete="username">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password Anda" required autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()" title="Tampilkan/Sembunyikan Password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember" id="remember" hidden>
                            <span class="custom-checkbox"></span>
                            <span class="checkbox-label">Ingat saya</span>
                        </label>
                        <a href="#" class="forgot-link">Lupa password?</a>
                    </div>

                    <button type="submit" class="submit-btn" id="btnLogin">
                        <span>Masuk ke Sistem</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="divider">Atau lanjutkan dengan</div>

                    <div class="social-login">
                        <button type="button" class="social-btn google">
                            <i class="fab fa-google"></i>
                            <span>Google</span>
                        </button>
                        <button type="button" class="social-btn microsoft">
                            <i class="fab fa-microsoft"></i>
                            <span>Microsoft</span>
                        </button>
                    </div>

                    <div class="signup-link">
                        Tidak punya akun? <a href="#">Daftar sekarang</a>
                    </div>
                </form>
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
            
            // Update loading message
            messageInterval = setInterval(() => {
                if (currentMessageIndex < loadingMessages.length - 1) {
                    currentMessageIndex++;
                    loadingMessage.textContent = loadingMessages[currentMessageIndex];
                }
            }, 400);
            
            // Hide loading screen
            setTimeout(() => {
                clearInterval(messageInterval);
                loadingScreen.classList.add('hidden');
                setTimeout(() => {
                    loginContainer.classList.add('visible');
                    createParticles();
                }, 100);
            }, 2500);

            // Theme Toggle
            const themeToggleBtn = document.getElementById('themeToggle');
            const root = document.documentElement;
            const icon = themeToggleBtn.querySelector('i');
            const brandLogo = document.getElementById('brandLogo');
            const loadingLogo = document.getElementById('loadingLogo');

            function updateLogo(theme) {
                const logoPath = theme === 'dark' 
                    ? 'includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png'
                    : 'includes/logo-AU.png';
                
                brandLogo.src = logoPath;
                loadingLogo.src = logoPath;
            }

            function setTheme(theme) {
                root.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                icon.className = `fas ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}`;
                updateLogo(theme);
                
                // Animation effect
                themeToggleBtn.style.transform = 'rotate(360deg) scale(1.2)';
                setTimeout(() => {
                    themeToggleBtn.style.transform = '';
                }, 300);
            }
            
            themeToggleBtn.addEventListener('click', () => {
                const newTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
            });

            // Set initial theme
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
                toggleIcon.title = "Sembunyikan password";
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleIcon.title = "Tampilkan password";
            }
        }

        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('btnLogin');
            const originalText = loginBtn.innerHTML;
            
            loginBtn.innerHTML = '<i class="fas fa-spinner btn-loading"></i> Memproses...';
            loginBtn.disabled = true;
            
            // Optional: Add a small delay to show the loading state
            setTimeout(() => {
                loginBtn.innerHTML = originalText;
                loginBtn.disabled = false;
            }, 3000);
        });

        // Create particles animation
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random position and animation
                const size = Math.random() * 3 + 1;
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 20 + 10;
                const animationDelay = Math.random() * 10;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}%`;
                particle.style.animationDuration = `${animationDuration}s`;
                particle.style.animationDelay = `${animationDelay}s`;
                particle.style.opacity = Math.random() * 0.5 + 0.2;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Add hover effects to social buttons
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Add floating animation to background shapes
        document.querySelectorAll('.floating-shape').forEach((el, index) => {
            el.style.animationDelay = `${index * 5}s`;
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