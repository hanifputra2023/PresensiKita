<?php
$page = 'admin_log';

// Handle Export Log to CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Build filter conditions for export
    $where_clauses = [];
    $search = isset($_GET['search']) ? escape($_GET['search']) : '';
    $start_date = isset($_GET['start_date']) ? escape($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? escape($_GET['end_date']) : '';
    $filter_aksi = isset($_GET['filter_aksi']) ? escape($_GET['filter_aksi']) : '';
    
    if ($search) $where_clauses[] = "(l.aksi LIKE '%$search%' OR l.tabel LIKE '%$search%' OR l.detail LIKE '%$search%' OR u.username LIKE '%$search%')";
    if ($start_date) $where_clauses[] = "DATE(l.created_at) >= '$start_date'";
    if ($end_date) $where_clauses[] = "DATE(l.created_at) <= '$end_date'";
    if ($filter_aksi) $where_clauses[] = "l.aksi LIKE '%$filter_aksi%'";
    
    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    $export_query = "SELECT l.*, u.username FROM log_presensi l 
                     LEFT JOIN users u ON l.user_id = u.id 
                     $where_sql ORDER BY l.created_at DESC";
    $export_result = mysqli_query($conn, $export_query);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=log_aktivitas_' . date('Y-m-d_His') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV Header
    fputcsv($output, ['No', 'Tanggal & Waktu', 'Username', 'Aksi', 'Tabel', 'ID Record', 'Detail', 'IP Address']);
    
    // CSV Data
    $no = 1;
    while ($row = mysqli_fetch_assoc($export_result)) {
        fputcsv($output, [
            $no++,
            date('d/m/Y H:i:s', strtotime($row['created_at'])),
            $row['username'] ?? 'System',
            $row['aksi'],
            $row['tabel'] ?? '-',
            $row['id_record'] ?? '-',
            $row['detail'],
            $row['ip_address'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit;
}

// Handle Delete Log
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi']) && $_POST['aksi'] == 'hapus_log') {
    $id = (int)$_POST['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM log_presensi WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        set_alert('success', 'Log aktivitas berhasil dihapus.');
    } else {
        set_alert('danger', 'Gagal menghapus log aktivitas.');
    }
    header("Location: index.php?page=admin_log");
    exit;
}

// Pagination
$per_page = 20;
$current_page = get_current_page();

// Filter Variables
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? escape($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? escape($_GET['end_date']) : '';
$filter_aksi = isset($_GET['filter_aksi']) ? escape($_GET['filter_aksi']) : '';

// Build Query Conditions
$where_clauses = [];
$params = [];
$types = "";

if ($search) {
    $where_clauses[] = "(l.aksi LIKE ? OR l.tabel LIKE ? OR l.detail LIKE ? OR u.username LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
    $types .= "ssss";
}

if ($start_date) {
    $where_clauses[] = "DATE(l.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if ($end_date) {
    $where_clauses[] = "DATE(l.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if ($filter_aksi) {
    $where_clauses[] = "l.aksi LIKE ?";
    $params[] = "%" . $filter_aksi . "%";
    $types .= "s";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Hitung Total Data
$sql_count = "SELECT COUNT(*) as total FROM log_presensi l LEFT JOIN users u ON l.user_id = u.id $where_sql";
$stmt_count = mysqli_prepare($conn, $sql_count);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$total_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];

$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Fetch Data Logs
$sql_data = "SELECT l.*, u.username, COALESCE(m.foto, a.foto) as foto FROM log_presensi l 
             LEFT JOIN users u ON l.user_id = u.id 
             LEFT JOIN mahasiswa m ON u.id = m.user_id
             LEFT JOIN asisten a ON u.id = a.user_id
             $where_sql
             ORDER BY l.created_at DESC LIMIT ?, ?";

// Add pagination params
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";

$stmt_logs = mysqli_prepare($conn, $sql_data);
mysqli_stmt_bind_param($stmt_logs, $types, ...$params);
mysqli_stmt_execute($stmt_logs);
$logs = mysqli_stmt_get_result($stmt_logs);
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Welcome Banner - Modern Design */
    .welcome-banner-log {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.2);
    }
    
    .welcome-banner-log::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(78, 115, 223, 0.5) 0%, transparent 70%);
        animation: pulse-glow 4s ease-in-out infinite;
    }
    
    .welcome-banner-log::after {
        content: '';
        position: absolute;
        bottom: -150px;
        left: -100px;
        width: 350px;
        height: 350px;
        background: radial-gradient(circle, rgba(54, 185, 204, 0.3) 0%, transparent 70%);
        animation: pulse-glow 4s ease-in-out infinite 2s;
    }
    
    @keyframes pulse-glow {
        0%, 100% { transform: scale(1); opacity: 0.4; }
        50% { transform: scale(1.05); opacity: 0.6; }
    }
    
    .welcome-content-log h1 {
        color: white;
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
        position: relative;
        z-index: 2;
    }
    
    .welcome-content-log .subtitle {
        color: rgba(255, 255, 255, 0.85);
        font-size: 16px;
        margin: 0;
        font-weight: 400;
        position: relative;
        z-index: 2;
    }
    
    .welcome-badge-log {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 8px 16px;
        border-radius: 20px;
        color: white;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 16px;
        position: relative;
        z-index: 2;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .welcome-badge-log i {
        font-size: 8px;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* Stats Cards - Match Dashboard Style */
    .stat-card-log {
        background: var(--bg-card);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: var(--card-shadow);
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card-log::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 60px;
        border-radius: 0 0 0 60px;
        opacity: 0.1;
    }
    
    .stat-card-log.blue::after { background: #0066cc; }
    .stat-card-log.green::after { background: #66cc00; }
    .stat-card-log.yellow::after { background: #ffaa00; }
    .stat-card-log.cyan::after { background: #00ccff; }
    
    .stat-card-log:hover {
        box-shadow: 0 6px 16px rgba(78, 115, 223, 0.12);
        transform: translateY(-2px);
    }
    
    .stat-icon-log {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    
    .stat-icon-log.blue { background: linear-gradient(135deg, #eaecf4, #d1d3e2); color: #0066cc; }
    .stat-icon-log.green { background: linear-gradient(135deg, #d4edda, #b8e0c4); color: #66cc00; }
    .stat-icon-log.yellow { background: linear-gradient(135deg, #fff3cd, #ffe69c); color: #ffaa00; }
    .stat-icon-log.cyan { background: linear-gradient(135deg, #d1ecf1, #b8e5eb); color: #00ccff; }
    
    .stat-info-log h3 {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
        line-height: 1;
    }
    
    .stat-info-log p {
        color: var(--text-muted);
        margin: 4px 0 0 0;
        font-size: 0.8rem;
    }
    
    /* Filter Bar Modern */
    .filter-bar {
        background: var(--bg-card);
        padding: 24px;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        margin-bottom: 24px;
        border: 1px solid var(--border-color);
    }
    
    .filter-bar .form-label {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-bar .form-control,
    .filter-bar .form-select {
        border-radius: 12px;
        border: 2px solid var(--border-color);
        padding: 10px 14px;
        transition: all 0.3s ease;
    }
    
    .filter-bar .form-control:focus,
    .filter-bar .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    
    .filter-bar .input-group-text {
        border-radius: 12px 0 0 12px;
        border: 2px solid var(--border-color);
        border-right: none;
        background: var(--bg-card);
    }
    
    .filter-bar .btn-primary {
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 20px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .filter-bar .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }
    
    /* Timeline-like style for mobile logs */
    .log-timeline-item {
        position: relative;
        padding-left: 2rem;
        border-left: 2px solid var(--border-color);
        padding-bottom: 1.5rem;
    }
    .log-timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    .log-timeline-icon {
        position: absolute;
        left: -0.6rem;
        top: 0;
        width: 1.2rem;
        height: 1.2rem;
        border-radius: 50%;
        background: var(--bg-card);
        border: 2px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
    }
    .log-timeline-item.create .log-timeline-icon { border-color: var(--success-color); color: var(--success-color); }
    .log-timeline-item.update .log-timeline-icon { border-color: var(--primary-color); color: var(--primary-color); }
    .log-timeline-item.delete .log-timeline-icon { border-color: var(--danger-color); color: var(--danger-color); }
    .log-timeline-item.login .log-timeline-icon { border-color: var(--info-color); color: var(--info-color); }
    
    /* Elegant Table Styles */
    .card.shadow-sm {
        border: none;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
        overflow: hidden;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
    }
    
    .card.shadow-sm .card-body {
        padding: 0;
        overflow: visible;
    }
    
    .card.shadow-sm .card-footer {
        border-radius: 0 0 20px 20px;
        background: var(--bg-card);
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 20px 20px 0 0;
    }

    .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    
    /* Custom Scrollbar */
    .table-responsive::-webkit-scrollbar {
        height: 0px;
        display: none;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: var(--bg-card);
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: var(--text-muted);
    }
    
    /* Hide scrollbar for all browsers */
    .table-responsive {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }

    .table thead th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #6c757d;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.8px;
        padding: 18px 20px;
        border-bottom: 2px solid var(--border-color);
        border-top: none;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    [data-theme="dark"] .table thead th {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
        color: var(--text-muted);
    }

    .table tbody td {
        padding: 18px 20px;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-main);
        font-size: 0.9rem;
    }

    .table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Enhanced Row Styling */
    .table tbody tr {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 4px solid transparent;
    }

    .table tbody tr:hover {
        background-color: var(--bg-body, #f8f9fa);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    
    .table tbody tr.row-create { 
        border-left-color: #10b981; 
        background: linear-gradient(90deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.02) 100%);
    }
    .table tbody tr.row-update { 
        border-left-color: #6366f1; 
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.08) 0%, rgba(99, 102, 241, 0.02) 100%);
    }
    .table tbody tr.row-delete { 
        border-left-color: #ef4444; 
        background: linear-gradient(90deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.02) 100%);
    }
    .table tbody tr.row-login { 
        border-left-color: #06b6d4; 
        background: linear-gradient(90deg, rgba(6, 182, 212, 0.08) 0%, rgba(6, 182, 212, 0.02) 100%);
    }

    .avatar-circle {
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, #e9ecef, #dee2e6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: #495057;
        font-weight: 700;
        margin-right: 14px;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
    }
    
    .avatar-circle::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
        transform: rotate(45deg);
        animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    .avatar-circle img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        position: relative;
        z-index: 1;
    }
    
    /* Disable shimmer animation to reduce lag */
    .avatar-circle::before {
        animation: none;
    }

    /* Soft Badge Styles */
    .badge-soft {
        padding: 0.5em 1em;
        font-weight: 600;
        border-radius: 50rem !important;
        font-size: 0.8rem;
    }
    .badge {
        padding: 0.5em 1em;
        font-weight: 600;
        border-radius: 50rem;
        font-size: 0.8rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .badge-soft-success { background-color: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); }
    .badge-soft-primary { background-color: rgba(99, 102, 241, 0.15); color: #4f46e5; border: 1px solid rgba(99, 102, 241, 0.3); }
    .badge-soft-danger { background-color: rgba(239, 68, 68, 0.15); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-soft-info { background-color: rgba(6, 182, 212, 0.15); color: #0891b2; border: 1px solid rgba(6, 182, 212, 0.3); }
    .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.15); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.3); }

    [data-theme="dark"] .badge-soft-success { background-color: rgba(40, 167, 69, 0.2); color: #75b798; }
    [data-theme="dark"] .badge-soft-primary { background-color: rgba(13, 110, 253, 0.2); color: #6ea8fe; }
    [data-theme="dark"] .badge-soft-danger { background-color: rgba(220, 53, 69, 0.2); color: #ea868f; }
    [data-theme="dark"] .badge-soft-info { background-color: rgba(13, 202, 240, 0.2); color: #6edff6; }
    [data-theme="dark"] .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.2); color: #a7acb1; }
    
    [data-theme="dark"] .log-timeline-item .card { background-color: var(--bg-card) !important; }
    [data-theme="dark"] .log-timeline-item .text-dark { color: var(--text-main) !important; }
    
    [data-theme="dark"] .table thead th {
        background-color: rgba(255, 255, 255, 0.03);
        color: var(--text-muted);
        border-bottom-color: var(--border-color);
    }
    [data-theme="dark"] .table tbody tr.row-create { background-color: rgba(40, 167, 69, 0.05); }
    [data-theme="dark"] .table tbody tr.row-update { background-color: rgba(13, 110, 253, 0.05); }
    [data-theme="dark"] .table tbody tr.row-delete { background-color: rgba(220, 53, 69, 0.05); }
    [data-theme="dark"] .table tbody tr.row-login { background-color: rgba(13, 202, 240, 0.05); }
    
    [data-theme="dark"] .table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03);
    }
    [data-theme="dark"] .avatar-circle {
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--text-main);
        border-color: var(--bg-card);
    }
    [data-theme="dark"] .text-dark { color: var(--text-main) !important; }
    
    /* Live Search Highlight */
    .highlight-search {
        background-color: #ffeb3b;
        color: #000;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
    }
    
    [data-theme="dark"] .highlight-search {
        background-color: #fbc02d;
        color: #000;
    }
    
    .no-results-row {
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Custom Delete Button */
    .btn-delete-log {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #dc2626;
        border: 1px solid #fca5a5;
        padding: 0.375rem 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.875rem;
    }
    
    .btn-delete-log:hover {
        background: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
        color: white;
        border-color: #ef4444;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    
    .btn-delete-log:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
    }
    
    [data-theme="dark"] .btn-delete-log {
        background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.15) 100%);
        color: #fca5a5;
        border-color: rgba(239, 68, 68, 0.3);
    }
    
    [data-theme="dark"] .btn-delete-log:hover {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.3) 0%, rgba(220, 38, 38, 0.25) 100%);
        color: #fecaca;
        border-color: rgba(239, 68, 68, 0.5);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
    }
    
    /* Loading Spinner */
    .spinner-border {
        width: 2rem;
        height: 2rem;
        border-width: 0.25em;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <!-- Welcome Banner -->
                <div class="welcome-banner-log">
                    <div class="welcome-content-log">
                        <div class="welcome-badge-log">
                            <i class="fas fa-circle"></i>
                            <?= date('l, d F Y') ?>
                        </div>
                        <h1><i class="fas fa-history me-3"></i>Log Aktivitas Sistem</h1>
                        <p class="subtitle">Pantau semua aktivitas dan perubahan data secara real-time dengan detail lengkap</p>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card-log blue">
                            <div class="stat-icon-log blue">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="stat-info-log">
                                <h3><?= number_format($total_data) ?></h3>
                                <p>Total Records</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-log green">
                            <div class="stat-icon-log green">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="stat-info-log">
                                <h3>
                                    <?php
                                    $today_query = mysqli_query($conn, "SELECT COUNT(*) as today FROM log_presensi WHERE DATE(created_at) = CURDATE()");
                                    $today_count = mysqli_fetch_assoc($today_query)['today'];
                                    echo number_format($today_count);
                                    ?>
                                </h3>
                                <p>Aktif Hari Ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-log yellow">
                            <div class="stat-icon-log yellow">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info-log">
                                <h3>
                                    <?php
                                    $week_query = mysqli_query($conn, "SELECT COUNT(*) as week FROM log_presensi WHERE YEARWEEK(created_at) = YEARWEEK(NOW())");
                                    $week_count = mysqli_fetch_assoc($week_query)['week'];
                                    echo number_format($week_count);
                                    ?>
                                </h3>
                                <p>Minggu Ini</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card-log cyan">
                            <div class="stat-icon-log cyan">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info-log">
                                <h3>
                                    <?php
                                    $month_query = mysqli_query($conn, "SELECT COUNT(*) as month FROM log_presensi WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
                                    $month_count = mysqli_fetch_assoc($month_query)['month'];
                                    echo number_format($month_count);
                                    ?>
                                </h3>
                                <p>Bulan Ini</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Search Filter -->
                <div class="filter-bar">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="admin_log">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-1">Tipe Aksi</label>
                                <select name="filter_aksi" class="form-select">
                                    <option value="">Semua</option>
                                    <option value="LOGIN" <?= $filter_aksi == 'LOGIN' ? 'selected' : '' ?>>Login</option>
                                    <option value="INSERT" <?= $filter_aksi == 'INSERT' ? 'selected' : '' ?>>Insert</option>
                                    <option value="UPDATE" <?= $filter_aksi == 'UPDATE' ? 'selected' : '' ?>>Update</option>
                                    <option value="DELETE" <?= $filter_aksi == 'DELETE' ? 'selected' : '' ?>>Delete</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Pencarian</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" id="liveSearchInput" name="search" class="form-control border-start-0 ps-0" placeholder="Cari username..." value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i></button>
                            </div>
                        </form>
                </div>
                
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Aktivitas</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="logTableBody">
                                    <?php if (mysqli_num_rows($logs) > 0): ?>
                                        <?php while ($l = mysqli_fetch_assoc($logs)): 
                                            $aksi = strtoupper($l['aksi']);
                                            $badgeClass = 'bg-secondary';
                                            $statusClass = 'bg-secondary';
                                            $icon = 'fa-info';
                                            $aksiLabel = $l['aksi'];
                                            $rowClass = '';
                                            
                                            if (strpos($aksi, 'LOGIN') !== false) {
                                                $badgeClass = 'bg-info text-dark';
                                                $statusClass = 'status-login';
                                                $icon = 'fa-sign-in-alt';
                                                $aksiLabel = 'Login Sistem';
                                                $rowClass = 'row-login';
                                            } elseif (strpos($aksi, 'PRESENSI_QR') !== false) {
                                                $badgeClass = 'bg-primary';
                                                $statusClass = 'bg-primary';
                                                $icon = 'fa-qrcode';
                                                $aksiLabel = 'Scan QR';
                                            } elseif (strpos($aksi, 'BROADCAST') !== false) {
                                                $badgeClass = 'bg-success';
                                                $statusClass = 'bg-success';
                                                $icon = 'fa-bullhorn';
                                                $aksiLabel = 'Broadcast WA';
                                            } elseif (strpos($aksi, 'INSERT') !== false || strpos($aksi, 'ADD') !== false || strpos($aksi, 'CREATE') !== false || strpos($aksi, 'GENERATE') !== false) {
                                                $badgeClass = 'bg-success';
                                                $statusClass = 'status-create';
                                                $icon = 'fa-plus';
                                                $aksiLabel = 'Tambah Data';
                                                $rowClass = 'row-create';
                                            } elseif (strpos($aksi, 'UPDATE') !== false || strpos($aksi, 'EDIT') !== false || strpos($aksi, 'APPROVE') !== false) {
                                                $badgeClass = 'bg-warning text-dark';
                                                $statusClass = 'status-update';
                                                $icon = 'fa-pen';
                                                $aksiLabel = 'Ubah Data';
                                                $rowClass = 'row-update';
                                            } elseif (strpos($aksi, 'DELETE') !== false || strpos($aksi, 'REMOVE') !== false || strpos($aksi, 'REJECT') !== false) {
                                                $badgeClass = 'bg-danger';
                                                $statusClass = 'status-delete';
                                                $icon = 'fa-trash';
                                                $aksiLabel = 'Hapus Data';
                                                $rowClass = 'row-delete';
                                            } else {
                                                $aksiLabel = ucfirst(strtolower(str_replace('_', ' ', $aksi)));
                                            }
                                        ?>
                                        <tr class="<?= $rowClass ?>" data-searchable="<?= strtolower(htmlspecialchars($l['username'] ?? 'System')) ?>">
                                            <td class="text-nowrap">
                                                <div class="d-flex align-items-center">
                                                    <span class="status-indicator <?= $statusClass ?>"></span>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= date('d M Y', strtotime($l['created_at'])) ?></div>
                                                        <div class="small text-muted" style="font-size: 0.75rem;"><?= date('H:i:s', strtotime($l['created_at'])) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($l['foto']) && file_exists($l['foto'])): ?>
                                                        <div class="avatar-circle"><img src="<?= htmlspecialchars($l['foto']) ?>" alt="User"></div>
                                                    <?php else: 
                                                        $username = $l['username'] ?? 'System';
                                                        $initial = strtoupper(substr($username, 0, 1));
                                                        if (strtolower($username) === 'admin') {
                                                            $avatarBg = '#212529'; // Warna khusus Admin (Dark)
                                                        } else {
                                                            $bgColors = ['#0066cc', '#66cc00', '#ff9900', '#ff3333', '#00ccff', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6c757d'];
                                                            $bgIndex = ord($initial) % count($bgColors);
                                                            $avatarBg = $bgColors[$bgIndex];
                                                        }
                                                    ?>
                                                        <div class="avatar-circle" style="background-color: <?= $avatarBg ?>; color: #fff;">
                                                            <?= $initial ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($l['username'] ?? 'System') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column align-items-start">
                                                    <span class="badge <?= $badgeClass ?> mb-1" title="Target: <?= $l['tabel'] ?> #<?= $l['id_record'] ?> (<?= $aksi ?>)">
                                                        <i class="fas <?= $icon ?> me-1"></i><?= $aksiLabel ?>
                                                    </span>
                                                    <span class="text-dark small text-break">
                                                        <?= htmlspecialchars($l['detail']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus log ini?');" class="d-inline">
                                                    <input type="hidden" name="aksi" value="hapus_log">
                                                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-delete-log" title="Hapus Log">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-search fa-2x mb-3 d-block"></i>
                                                Tidak ada data log ditemukan.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View -->
                        <div class="d-lg-none p-3">
                            <?php 
                            if (mysqli_num_rows($logs) > 0):
                                mysqli_data_seek($logs, 0);
                                while ($l = mysqli_fetch_assoc($logs)): 
                                    $aksi = strtoupper($l['aksi']);
                                    $type = 'info';
                                    $icon = 'fa-info';
                                    $badgeClass = 'bg-secondary';
                                    $aksiLabel = $l['aksi'];
                                    
                                    if (strpos($aksi, 'LOGIN') !== false) {
                                        $type = 'login';
                                        $icon = 'fa-sign-in-alt';
                                        $badgeClass = 'bg-info text-dark';
                                        $aksiLabel = 'Login Sistem';
                                    } elseif (strpos($aksi, 'PRESENSI_QR') !== false) {
                                        $type = 'update';
                                        $icon = 'fa-qrcode';
                                        $badgeClass = 'bg-primary';
                                        $aksiLabel = 'Scan QR';
                                    } elseif (strpos($aksi, 'BROADCAST') !== false) {
                                        $type = 'create';
                                        $icon = 'fa-bullhorn';
                                        $badgeClass = 'bg-success';
                                        $aksiLabel = 'Broadcast WA';
                                    } elseif (strpos($aksi, 'INSERT') !== false || strpos($aksi, 'ADD') !== false || strpos($aksi, 'CREATE') !== false || strpos($aksi, 'GENERATE') !== false) {
                                        $type = 'create';
                                        $icon = 'fa-plus';
                                        $badgeClass = 'bg-success';
                                        $aksiLabel = 'Tambah Data';
                                    } elseif (strpos($aksi, 'UPDATE') !== false || strpos($aksi, 'EDIT') !== false || strpos($aksi, 'APPROVE') !== false) {
                                        $type = 'update';
                                        $icon = 'fa-pen';
                                        $badgeClass = 'bg-warning text-dark';
                                        $aksiLabel = 'Ubah Data';
                                    } elseif (strpos($aksi, 'DELETE') !== false || strpos($aksi, 'REMOVE') !== false || strpos($aksi, 'REJECT') !== false) {
                                        $type = 'delete';
                                        $icon = 'fa-trash';
                                        $badgeClass = 'bg-danger';
                                        $aksiLabel = 'Hapus Data';
                                    } else {
                                        $badgeClass = 'bg-secondary';
                                        $aksiLabel = ucfirst(strtolower($aksi));
                                    }
                            ?>
                            <div class="log-timeline-item <?= $type ?>">
                                <div class="log-timeline-icon">
                                    <i class="fas <?= $icon ?>"></i>
                                </div>
                                <div class="card border-0 shadow-sm bg-light">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge <?= $badgeClass ?>" title="Target: <?= $l['tabel'] ?> #<?= $l['id_record'] ?>"><?= $aksiLabel ?></span>
                                                <small class="text-muted"><?= date('d M H:i', strtotime($l['created_at'])) ?></small>
                                            </div>
                                            <small class="fw-bold text-dark"><?= htmlspecialchars($l['username'] ?? 'System') ?></small>
                                        </div>
                                        <p class="mb-0 small text-dark"><?= htmlspecialchars($l['detail']) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-search fa-2x mb-3 d-block"></i>
                                    Tidak ada data log ditemukan.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_data > 0): ?>
                    <div class="card-footer bg-transparent border-top py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                            <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                            <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_log', ['search' => $search, 'start_date' => $start_date, 'end_date' => $end_date, 'filter_aksi' => $filter_aksi]) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live Search Functionality with AJAX (search all data, not just current page)
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearchInput');
    const tableBody = document.getElementById('logTableBody');
    
    if (searchInput && tableBody) {
        let searchTimeout;
        let originalTableContent = tableBody.innerHTML; // Store original content
        let isSearchActive = false;
        
        searchInput.addEventListener('input', function() {
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            const searchTerm = searchInput.value.trim();
            
            // If empty, restore original content
            if (searchTerm === '') {
                tableBody.innerHTML = originalTableContent;
                isSearchActive = false;
                searchInput.parentElement.classList.remove('border-primary');
                searchInput.parentElement.style.boxShadow = '';
                return;
            }
            
            // Add small delay for better performance
            searchTimeout = setTimeout(function() {
                isSearchActive = true;
                
                // Show loading state
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 mb-0 text-muted">Mencari "${searchTerm}"...</p>
                        </td>
                    </tr>
                `;
                
                // AJAX request to search API
                fetch('api/live_search_log.php?q=' + encodeURIComponent(searchTerm))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            // Build table rows from results
                            let html = '';
                            data.data.forEach(function(log) {
                                const dateObj = new Date(log.created_at);
                                const dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                                const timeStr = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                                
                                // Avatar HTML
                                let avatarHtml = '';
                                if (log.foto) {
                                    avatarHtml = `<div class="avatar-circle"><img src="${log.foto}" alt="User"></div>`;
                                } else {
                                    avatarHtml = `<div class="avatar-circle" style="background-color: ${log.avatarBg}; color: #fff;">${log.initial}</div>`;
                                }
                                
                                // Highlight username
                                const usernameHighlighted = highlightSearchTerm(log.username, searchTerm);
                                
                                html += `
                                    <tr class="${log.rowClass}">
                                        <td class="text-nowrap">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">${dateStr}</div>
                                                    <div class="small text-muted" style="font-size: 0.75rem;">${timeStr}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                ${avatarHtml}
                                                <span class="fw-bold text-dark">${usernameHighlighted}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column align-items-start">
                                                <span class="badge ${log.badgeClass} mb-1" title="Target: ${log.tabel} #${log.id_record} (${log.aksi})">
                                                    <i class="fas ${log.icon} me-1"></i>${log.aksiLabel}
                                                </span>
                                                <span class="text-dark small text-break">${escapeHtml(log.detail)}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus log ini?');" class="d-inline">
                                                <input type="hidden" name="aksi" value="hapus_log">
                                                <input type="hidden" name="id" value="${log.id}">
                                                <button type="submit" class="btn btn-sm btn-delete-log" title="Hapus Log">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                `;
                            });
                            
                            tableBody.innerHTML = html;
                            
                            // Show result count
                            if (data.count >= 50) {
                                const infoRow = document.createElement('tr');
                                infoRow.className = 'bg-light';
                                infoRow.innerHTML = `
                                    <td colspan="4" class="text-center py-2 small text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Menampilkan 50 hasil teratas dari pencarian "${searchTerm}"
                                    </td>
                                `;
                                tableBody.appendChild(infoRow);
                            }
                            
                        } else {
                            // No results found
                            tableBody.innerHTML = `
                                <tr class="no-results-row">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-search fa-2x mb-3 d-block"></i>
                                        <strong>Tidak ada hasil untuk "${searchTerm}"</strong>
                                        <p class="small mb-0 mt-2">Coba kata kunci lain atau hapus filter</p>
                                    </td>
                                </tr>
                            `;
                        }
                        
                        // Update visual feedback
                        searchInput.parentElement.classList.add('border-primary');
                        searchInput.parentElement.style.boxShadow = '0 0 0 4px rgba(0, 102, 204, 0.1)';
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center py-5 text-danger">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>
                                    <strong>Terjadi kesalahan saat mencari data</strong>
                                    <p class="small mb-0 mt-2">Silakan coba lagi</p>
                                </td>
                            </tr>
                        `;
                    });
                
            }, 400); // 400ms delay for better performance
        });
        
        // Helper function to highlight search term
        function highlightSearchTerm(text, searchTerm) {
            const regex = new RegExp(`(${escapeRegex(searchTerm)})`, 'gi');
            return text.replace(regex, '<span class="highlight-search">$1</span>');
        }
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        // Clear search on ESC key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.blur();
            }
        });
        
        // Focus search on Ctrl/Cmd + K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
