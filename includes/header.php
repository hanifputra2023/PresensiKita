<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#0066cc">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Presensi">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Sistem Presensi Kampus">
    
    <!-- Favicon & Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="includes/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="includes/icon-512.png">
    <link rel="apple-touch-icon" href="includes/icon-192.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            /* Brand Colors */
            --primary-color: #0066cc;
            --prima-color: #0099ffff;
            --primari-color: #0066ccff;
            --secondary-color: #00ccff;
            --success-color: #66cc00;
            --info-color: #00ccff;
            --warning-color: #ffaa00;
            --danger-color: #ff3333;

            /* Theme Variables (Light Default) */
            --bg-body: #f8f9fc;
            --bg-card: #ffffff;
            --bg-input: #ffffff;
            --text-main: #333333;
            --text-muted: #858796;
            --border-color: #e3e6f0;
            --topbar-bg: #ffffff;
            --header-bg: #f8f9fc;
            
            /* Component Variables (Light) */
            --banner-gradient: linear-gradient(90deg, #0066cc, #0099ff, #16a1fdff);
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.1);
            --sidebar-bg: linear-gradient(180deg, #0066cc 10%, #0099ff 100%);
            --sidebar-brand-bg: linear-gradient(180deg, #6abbf1ff 40%, #0066cc 100%);
        }

        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #334155;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --topbar-bg: #1e293b;
            --header-bg: #1e293b;
            
            /* Component Variables (Dark) */
            --banner-gradient: linear-gradient(90deg, #0f2027, #203a43, #2c5364);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
            --sidebar-bg: linear-gradient(180deg, #0f172a 10%, #1e293b 100%);
            --sidebar-brand-bg: linear-gradient(180deg, #0f172a 10%, #1e293b 100%);
            --bs-body-color: var(--text-main);
            --bs-body-bg: var(--bg-body);
        }

        [data-theme="dark"] .logo-light {
            display: none;
        }
        [data-theme="dark"] .logo-dark {
            display: block;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            transition: background 0.3s;
        }
        
        /* Sidebar Brand Sticky */
        .sidebar-brand {
            padding: 1.5rem 1rem;
            text-align: center;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.2rem;
            position: sticky;
            top: 0;
            
            background: var(--sidebar-brand-bg);
            z-index: 10;
            margin: -1rem -1rem 0 -1rem !important;
            width: calc(100% + 2rem);
            
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            transition: background 0.3s;
        }
        
        .sidebar-brand:hover {
            color: #fff;
        }

        /* Logo theme toggle */
        .logo-dark {
            display: none;
        }
        .logo-light {
            display: block;
        }
        
        /* Sidebar scrollable content */
        .sidebar .nav-pills {
            overflow-y: auto;
            flex: 1;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem;
            border-radius: 5px;
            margin: 2px 2px;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.2);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content-wrapper {
            min-height: 100vh;
        }
        
        .topbar {
            background-color: var(--topbar-bg);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0,0,0,.15);
            padding: 0.5rem 1rem;
        }
        
        .card {
            background-color: var(--bg-card);
            border: none;
            color: var(--text-main);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(0,0,0,.05);
            border-radius: 10px;
        }
        
        .card-header {
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            font-weight: bold;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .badge-hadir { background-color: var(--success-color); }
        .badge-izin { background-color: var(--warning-color); color: #000; }
        .badge-sakit { background-color: var(--info-color); }
        .badge-alpha { background-color: var(--danger-color); }
        
        .stat-card {
            border-left: 4px solid;
            padding: 1rem;
        }
        
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }
        
        /* Global Alert Styling (Modern Look for Announcements) */
        .alert-info {
            background-color: #f0fcff;
            border: 0;
            border-left: 5px solid #0dcaf0 !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        }

        /* Responsive button width */
        @media (min-width: 768px) {
            .w-md-auto { width: auto !important; }
            .d-md-flex .btn { width: auto; }
        }
        
        .table th {
            background-color: var(--header-bg);
            color: var(--text-main);
            border-color: var(--border-color);
        }
        
        .qr-container {
            text-align: center;
            padding: 2rem;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.1);
        }
        
        .qr-code img {
            max-width: 300px;
            border: 5px solid #4e73df;
            border-radius: 10px;
        }

        /* Dark Mode Specific Overrides */
        [data-theme="dark"] .table {
            color: var(--text-main);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .table td, 
        [data-theme="dark"] .table th {
            border-color: var(--border-color);
        }
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        [data-theme="dark"] .form-control::placeholder {
            color: var(--text-muted);
        }
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: var(--bg-input);
            border-color: #66b0ff;
            color: var(--text-main);
            box-shadow: 0 0 0 0.25rem rgba(102, 176, 255, 0.25);
        }
        [data-theme="dark"] .form-control:disabled,
        [data-theme="dark"] .form-control[readonly],
        [data-theme="dark"] .form-select:disabled {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            opacity: 1;
        }
        [data-theme="dark"] .form-control::file-selector-button {
            background-color: var(--bg-body);
            color: var(--text-main);
            border-right-color: var(--border-color);
        }
        [data-theme="dark"] .form-control:hover:not(:disabled):not([readonly])::file-selector-button {
            background-color: var(--border-color);
        }
        [data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator,
        [data-theme="dark"] input[type="datetime-local"]::-webkit-calendar-picker-indicator,
        [data-theme="dark"] input[type="time"]::-webkit-calendar-picker-indicator,
        [data-theme="dark"] input[type="month"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            opacity: 0.8;
        }
        [data-theme="dark"] select option {
            background-color: var(--bg-card);
            color: var(--text-main);
        }
        [data-theme="dark"] .dropdown-menu {
            background-color: var(--bg-card);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .dropdown-item {
            color: var(--text-main);
        }
        [data-theme="dark"] .dropdown-item:hover {
            background-color: var(--border-color);
        }
        
        /* Ensure headings and strong text are visible in dark mode */
        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3, 
        [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6,
        [data-theme="dark"] strong, [data-theme="dark"] b {
            color: var(--text-main);
        }
        
        /* Global Page Header Styling */
        .page-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .page-header h4 {
            font-weight: 700;
            color: var(--text-main);
        }
        .page-header h4 i {
            color: var(--primary-color);
        }
        
        /* Global h4 icon color - untuk semua halaman admin */
        .content-wrapper > div > h4 > i,
        .content-wrapper > h4 > i,
        .content-wrapper h4.mb-0 > i,
        .content-wrapper h4.mb-4 > i,
        .page-header-banner h4 > i {
            color: var(--primary-color) !important;
        }
        
        /* Bootstrap Overrides for Dark Mode */
        [data-theme="dark"] .bg-light {
            background-color: var(--bg-body) !important;
            color: var(--text-main) !important;
        }
        [data-theme="dark"] .bg-white {
            background-color: var(--bg-card) !important;
            color: var(--text-main) !important;
        }
        [data-theme="dark"] .text-dark {
            color: var(--text-main) !important;
        }
        /* Fix text-dark on warning backgrounds in dark mode */
        [data-theme="dark"] .bg-warning.text-dark,
        [data-theme="dark"] .badge.bg-warning {
            color: #212529 !important;
        }
        [data-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
        }
        [data-theme="dark"] .border {
            border-color: var(--border-color) !important;
        }
        [data-theme="dark"] .table-light {
            background-color: var(--header-bg);
            color: var(--text-main);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .table-light th, 
        [data-theme="dark"] .table-light td {
            background-color: var(--header-bg);
            color: var(--text-main);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .modal-content {
            background-color: var(--bg-card);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        [data-theme="dark"] .input-group-text {
            background-color: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-muted);
        }
        [data-theme="dark"] .table-hover tbody tr:hover {
            background-color: rgba(255,255,255,0.05);
            color: var(--text-main);
        }
        
        /* Global Text & Link Colors for Dark Mode */
        [data-theme="dark"] a { color: #66b0ff; }
        [data-theme="dark"] a:hover { color: #99caff; }
        [data-theme="dark"] .text-primary { color: #66b0ff !important; }
        [data-theme="dark"] .text-success { color: #85e085 !important; }
        [data-theme="dark"] .text-danger { color: #ff8080 !important; }
        [data-theme="dark"] .text-warning { color: #ffcc00 !important; }
        [data-theme="dark"] .text-info { color: #33d6ff !important; }
        [data-theme="dark"] .text-secondary { color: #a0aec0 !important; }
        [data-theme="dark"] .text-purple { color: #a685e0 !important; }
        
        /* Global Table Styles for Dark Mode */
        [data-theme="dark"] .table { 
            color: var(--text-main); 
            border-color: var(--border-color);
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            --bs-table-border-color: var(--border-color);
        }
        [data-theme="dark"] .table thead th {
            background-color: var(--header-bg);
            color: var(--text-main);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table th {
            border-color: var(--border-color);
            color: var(--text-main);
        }
        /* Override Bootstrap specific selector for cells to ensure dark mode */
        [data-theme="dark"] .table > :not(caption) > * > * {
            background-color: transparent;
            color: var(--text-main);
            border-color: var(--border-color);
        }
        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }
        [data-theme="dark"] .table-hover > tbody > tr:hover > * {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--text-main);
        }
        
        /* Dark Mode Button Fixes (Global) */
        [data-theme="dark"] .btn-warning,
        [data-theme="dark"] .btn-info,
        [data-theme="dark"] .btn-light {
            color: #212529 !important;
        }
        
        /* Dark Mode Solid Buttons */
        [data-theme="dark"] .btn-primary {
            background-color: #3a8fd9;
            border-color: #3a8fd9;
            color: #fff;
        }
        [data-theme="dark"] .btn-primary:hover {
            background-color: #2c7bc0;
            border-color: #2c7bc0;
        }
        [data-theme="dark"] .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
            color: #fff;
        }
        [data-theme="dark"] .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        [data-theme="dark"] .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
            color: #fff;
        }
        [data-theme="dark"] .btn-danger:hover {
            background-color: #be2617;
            border-color: #be2617;
        }
        [data-theme="dark"] .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #fff;
        }
        [data-theme="dark"] .btn-secondary:hover {
            background-color: #5c636a;
            border-color: #5c636a;
        }
        [data-theme="dark"] .btn-link {
            color: #66b0ff;
        }
        [data-theme="dark"] .btn-link:hover {
            color: #99caff;
        }
        
        /* Dark Mode Outline Buttons */
        [data-theme="dark"] .btn-outline-primary {
            color: #66b0ff;
            border-color: #66b0ff;
        }
        [data-theme="dark"] .btn-outline-primary:hover {
            background-color: #66b0ff;
            color: #212529;
        }
        [data-theme="dark"] .btn-outline-success {
            color: #85e085;
            border-color: #85e085;
        }
        [data-theme="dark"] .btn-outline-success:hover {
            background-color: #85e085;
            color: #212529;
        }
        [data-theme="dark"] .btn-outline-danger {
            color: #ea868f;
            border-color: #ea868f;
        }
        [data-theme="dark"] .btn-outline-danger:hover {
            background-color: #ea868f;
            color: #212529;
        }
        [data-theme="dark"] .btn-outline-secondary {
            color: #a0aec0;
            border-color: #a0aec0;
        }
        [data-theme="dark"] .btn-outline-secondary:hover {
            background-color: #a0aec0;
            color: #212529;
        }
        
        /* Dark Mode Alert Fixes (Global) */
        [data-theme="dark"] .alert-info {
            background-color: rgba(13, 202, 240, 0.15) !important;
            border-left-color: #0dcaf0 !important;
            color: #6edff6 !important;
        }
        [data-theme="dark"] .alert-info .text-muted {
            color: rgba(255,255,255,0.6) !important;
        }
        
        /* Dark Mode List Group Fixes (Global) */
        [data-theme="dark"] .list-group-item {
            background-color: var(--bg-card);
            border-color: var(--border-color);
            color: var(--text-main);
        }
        [data-theme="dark"] .list-group-item-action:hover {
            background-color: rgba(255,255,255,0.05);
            color: var(--text-main);
        }
        
        /* Dark Mode Table Variants (Global) */
        [data-theme="dark"] .table-primary {
            --bs-table-bg: rgba(0, 102, 204, 0.2);
            --bs-table-color: var(--text-main);
            border-color: rgba(0, 102, 204, 0.3);
        }
        
        /* Mobile Header Dark Mode */
        [data-theme="dark"] .mobile-header {
            
            background: var(--sidebar-brand-bg) !important;
            border-bottom: 1px solid var(--border-color);
        }

        /* ============ MOBILE RESPONSIVE STYLES ============ */
        
        /* Responsive Tables */
        .table-responsive-mobile {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 767.98px) {
            /* Card table untuk mobile */
            .table-mobile-cards {
                border: 0;
            }
            
            .table-mobile-cards thead {
                display: none;
            }
            
            .table-mobile-cards tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid var(--border-color);
                border-radius: 10px;
                padding: 0.75rem;
                background: var(--bg-card);
                color: var(--text-main);
                box-shadow: 0 0.1rem 0.5rem rgba(0,0,0,0.08);
            }
            
            .table-mobile-cards td {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 0.5rem 0;
                border: none;
                border-bottom: 1px solid var(--border-color);
            }
            
            .table-mobile-cards td:last-child {
                border-bottom: none;
            }
            
            .table-mobile-cards td::before {
                content: attr(data-label);
                font-weight: bold;
                color: var(--text-muted);
                margin-right: 1rem;
                flex-shrink: 0;
                max-width: 40%;
            }
            
            .table-mobile-cards td .btn-group,
            .table-mobile-cards td .btn {
                margin-left: auto;
            }
            
            /* Cards responsive */
            .card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            /* Content wrapper mobile */
            .content-wrapper {
                padding: 1rem !important;
            }
            
            /* Stat cards mobile */
            .stat-card .h3 {
                font-size: 1.5rem;
            }
            
            .stat-card .fa-2x {
                font-size: 1.5rem;
            }
            
            /* Header title mobile */
            h4 {
                font-size: 1.25rem;
            }
            
            /* Button groups mobile */
            .btn-group-mobile {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-group-mobile .btn {
                width: 100%;
            }
            
            /* Form inputs mobile */
            .form-control, .form-select {
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            /* Modal mobile */
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            /* Badgeh responsive */
            .badge {
                font-size: 0.7rem;
                padding: 0.35em 0.5em;
            }
            
            /* Hide columns on mobile if needed */
            .hide-mobile {
                display: none !important;
            }
            
            /* Flex direction for mobile */
            .flex-mobile-column {
                flex-direction: column !important;
            }
            
            .flex-mobile-column > * {
                margin-bottom: 0.5rem;
            }
            
            /* Full width on mobile */
            .w-mobile-100 {
                width: 100% !important;
            }
            
            /* Text alignment mobile */
            .text-mobile-center {
                text-align: center !important;
            }
            
            .text-mobile-start {
                text-align: start !important;
            }
            
            /* Action buttons in table */
            .action-buttons {
                display: flex;
                gap: 0.25rem;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            
            .action-buttons .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
            
            /* Scanner page specific */
            #reader {
                max-width: 100% !important;
            }
            
            /* Progress bar text */
            .progress-bar {
                font-size: 0.75rem;
            }
            
            /* List items mobile */
            .list-group-item {
                padding: 0.75rem;
            }
            
            .list-group-item .d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .list-group-item .text-end {
                text-align: start !important;
            }
            
            /* Jadwal card mobile */
            .card.bg-primary .row {
                text-align: center;
            }
            
            .card.bg-primary .text-end {
                text-align: center !important;
                margin-top: 1rem;
            }
            
            /* QR Code mobile */
            .qr-code img {
                max-width: 200px;
            }
            
            .qr-container {
                padding: 1rem;
            }
            
            /* Statistik grid mobile */
            .row .col-3 {
                padding: 0.25rem;
            }
            
            .row .col-3 .h3 {
                font-size: 1.25rem;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 575.98px) {
            .content-wrapper {
                padding: 0.75rem !important;
            }
            
            h4 {
                font-size: 1.1rem;
            }
            
            .card-header {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .mobile-header .brand {
                font-size: 0.9rem;
            }
            
            .stat-card .h3 {
                font-size: 1.25rem;
            }
            
            /* Two columns for stat cards */
            .col-xl-3.col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        /* Desktop: Sidebar sticky */
        @media (min-width: 992px) {
            .row > .col-md-3.col-lg-2 {
                position: fixed;
                top: 0;
                left: 0;
                width: 16.666667%;
                height: 100vh;
                overflow-y: auto;
                z-index: 1000;
                padding: 0 !important;
            }
            
            .row > .col-md-9.col-lg-10 {
                margin-left: 16.666667%;
                width: 83.333333% !important;
                max-width: 83.333333% !important;
                flex: 0 0 83.333333% !important;
            }
            
            .mobile-header {
                display: none !important;
            }
        }
        
        /* Mobile styles */
        @media (max-width: 991.98px) {
            .row > .col-md-3.col-lg-2 {
                position: fixed;
                top: 0;
                left: -280px;
                width: 280px !important;
                max-width: 280px !important;
                height: 100vh;
                z-index: 1050;
                transition: left 0.3s ease;
                padding: 0 !important;
                overflow-y: auto;
            }
            
            .row > .col-md-3.col-lg-2.show {
                left: 0;
            }
            
            /* Sidebar mobile - reset padding untuk brand full width */
            .row > .col-md-3.col-lg-2 .sidebar {
                min-height: 100%;
                overflow-y: auto;
                padding: 0 !important;
            }
            
            .row > .col-md-3.col-lg-2 .sidebar .nav-pills {
                padding: 0.5rem;
            }
            
            .row > .col-md-3.col-lg-2 .sidebar .dropdown {
                padding: 0.5rem 1rem;
            }
            
            .row > .col-md-3.col-lg-2 .sidebar hr {
                margin: 0.5rem 1rem;
            }
            
            /* Sidebar brand mobile - full width */
            .row > .col-md-3.col-lg-2 .sidebar-brand {
                margin: 0 !important;
                width: 100%;
                border-radius: 0;
            }
            
            .row > .col-md-3.col-lg-2::-webkit-scrollbar {
                width: 5px;
            }
            
            .row > .col-md-3.col-lg-2::-webkit-scrollbar-thumb {
                background: rgba(255,255,255,0.3);
                border-radius: 5px;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .row > .col-md-9.col-lg-10 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding-top: 60px !important;
            }
            
            .mobile-header {
                display: flex !important;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1030;
                background: linear-gradient(90deg, #0066cc, #0099ff, #16a1fdff);
                padding: 0.75rem 1rem;
                color: #fff;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 2px 10px rgba(0,0,0,0.15);
                margin: 0;
                width: 100%;
            }
            
            .mobile-header .brand {
                font-weight: bold;
                font-size: 1rem;
            }
            
            .mobile-header .btn-toggle {
                background: rgba(255,255,255,0.2);
                border: none;
                color: #fff;
                padding: 0.5rem 0.75rem;
                border-radius: 5px;
                cursor: pointer;
            }
            
            .mobile-header .btn-toggle:hover {
                background: rgba(255,255,255,0.3);
            }
            
            .content-wrapper {
                padding-top: 0 !important;
            }
        }
        
        /* Hide scrollbar for sidebar but allow scroll */
        .col-md-3.col-lg-2::-webkit-scrollbar {
            width: 5px;
        }
        
        .col-md-3.col-lg-2::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
        }
        
        /* Dropdown user di sidebar */
        .sidebar .dropdown-menu {
            background: #001f5c;
            border: 1px solid rgba(255,255,255,0.15);
        }
        
        .sidebar .dropdown-menu .dropdown-item {
            color: rgba(255,255,255,0.85);
        }
        
        .sidebar .dropdown-menu .dropdown-item:hover,
        .sidebar .dropdown-menu .dropdown-item:focus {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        
        .sidebar .dropdown-menu .dropdown-item-text {
            color: rgba(255,255,255,0.6) !important;
        }
        
        .sidebar .dropdown-menu .dropdown-divider {
            border-color: rgba(255,255,255,0.15);
        }
        
        /* Logout item styling */
        .sidebar .dropdown-menu .dropdown-item:last-child,
        .sidebar .dropdown-menu a[href*="logout"] {
            color: #fca5a5;
        }
        
        .sidebar .dropdown-menu .dropdown-item:last-child:hover,
        .sidebar .dropdown-menu a[href*="logout"]:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fecaca;
        }
    </style>
    <script>
        // Apply theme immediately to prevent flash
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    </script>
</head>
<body>

<!-- Sidebar Overlay (untuk mobile) -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Mobile Header (fixed position) -->
<div class="mobile-header" id="mobileHeader" style="display: none;">
    <button class="btn-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <span class="brand"><?= APP_NAME ?></span>
    <div class="d-flex align-items-center">
        <button class="btn-toggle me-3 theme-toggle" title="Ganti Tema">
            <i class="fas fa-moon"></i>
        </button>
        <a href="index.php?page=logout" class="btn-toggle" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<script>
function toggleSidebar() {
    var sidebar = document.querySelector('.col-md-3.col-lg-2');
    var overlay = document.querySelector('.sidebar-overlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }
}

// Show mobile header on mobile devices
document.addEventListener('DOMContentLoaded', function() {
    function checkMobileHeader() {
        var mobileHeader = document.getElementById('mobileHeader');
        if (mobileHeader) {
            if (window.innerWidth < 992) {
                mobileHeader.style.display = 'flex';
            } else {
                mobileHeader.style.display = 'none';
            }
        }
    }
    
    checkMobileHeader();
    window.addEventListener('resize', checkMobileHeader);
    
    // Close sidebar when clicking a link (mobile)
    var sidebarLinks = document.querySelectorAll('.col-md-3.col-lg-2 .nav-link');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                toggleSidebar();
            }
        });
    });
});
</script>
