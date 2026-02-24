<?php
$page = 'admin_kelas';

// Proses tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'tambah') {
        // ... (Kode tambah sama seperti sebelumnya) ...
        $kode = escape($_POST['kode_kelas']);
        $nama = escape($_POST['nama_kelas']);
        $prodi = escape($_POST['program_studi']);
        $tahun = escape($_POST['tahun_ajaran']);
        
        $stmt_cek = mysqli_prepare($conn, "SELECT * FROM kelas WHERE kode_kelas = ?");
        mysqli_stmt_bind_param($stmt_cek, "s", $kode);
        mysqli_stmt_execute($stmt_cek);
        $cek = mysqli_stmt_get_result($stmt_cek);
        if (mysqli_num_rows($cek) > 0) {
            set_alert('danger', 'Kode kelas sudah ada!');
        } else {
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO kelas VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "ssss", $kode, $nama, $prodi, $tahun);
            mysqli_stmt_execute($stmt_ins);
            set_alert('success', 'Kelas berhasil ditambahkan!');
        }

    } elseif ($aksi == 'edit') {
        // ... (Kode edit sama seperti sebelumnya) ...
        $kode = escape($_POST['kode_kelas']);
        $nama = escape($_POST['nama_kelas']);
        $prodi = escape($_POST['program_studi']);
        $tahun = escape($_POST['tahun_ajaran']);
        
        $stmt_upd = mysqli_prepare($conn, "UPDATE kelas SET nama_kelas=?, program_studi=?, tahun_ajaran=? WHERE kode_kelas=?");
        mysqli_stmt_bind_param($stmt_upd, "ssss", $nama, $prodi, $tahun, $kode);
        mysqli_stmt_execute($stmt_upd);
        set_alert('success', 'Kelas berhasil diupdate!');

    } elseif ($aksi == 'hapus') {
        // Hapus Single
        $kode = escape($_POST['kode_kelas']);
        $stmt_del = mysqli_prepare($conn, "DELETE FROM kelas WHERE kode_kelas = ?");
        mysqli_stmt_bind_param($stmt_del, "s", $kode);
        mysqli_stmt_execute($stmt_del);
        set_alert('success', 'Kelas berhasil dihapus!');

    } elseif ($aksi == 'hapus_banyak') {
        // FITUR BARU: Hapus Bulk (Banyak sekaligus)
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $success_count = 0;
            
            $stmt_del = mysqli_prepare($conn, "DELETE FROM kelas WHERE kode_kelas = ?");
            foreach ($ids as $id) {
                $safe_id = escape($id);
                mysqli_stmt_bind_param($stmt_del, "s", $safe_id);
                if(mysqli_stmt_execute($stmt_del)){
                    $success_count++;
                }
            }
            set_alert('success', $success_count . ' Kelas berhasil dihapus!');
        }
    }
    
    echo "<script>window.location.href='index.php?page=admin_kelas';</script>";
    exit;
}

// --- [MODIFIKASI] Penambahan filter prodi dan perbaikan logika query ---
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$filter_prodi = isset($_GET['prodi']) ? escape($_GET['prodi']) : ''; // BARU
$search_param = '%' . $search . '%';

// Pagination
$per_page = 9;
$current_page = get_current_page();

$where_conditions = [];
$bind_types = "";
$bind_values = [];

if ($search) {
    $where_conditions[] = "(k.nama_kelas LIKE ? OR k.kode_kelas LIKE ? OR k.program_studi LIKE ?)";
    $bind_types .= "sss";
    array_push($bind_values, $search_param, $search_param, $search_param);
}
if ($filter_prodi) { // BARU
    $where_conditions[] = "k.program_studi = ?";
    $bind_types .= "s";
    $bind_values[] = $filter_prodi;
}

$where_sql = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

$count_query_sql = "SELECT COUNT(*) as total FROM kelas k $where_sql";
$stmt_count = mysqli_prepare($conn, $count_query_sql);
if (!empty($bind_values)) {
    mysqli_stmt_bind_param($stmt_count, $bind_types, ...$bind_values);
}
mysqli_stmt_execute($stmt_count);
$total_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Ambil data kelas
$data_query_sql = "SELECT k.*, (SELECT COUNT(*) FROM mahasiswa m WHERE m.kode_kelas = k.kode_kelas) as jml_mahasiswa FROM kelas k $where_sql ORDER BY k.kode_kelas LIMIT ?, ?";
$stmt_kelas = mysqli_prepare($conn, $data_query_sql);
$data_bind_types = $bind_types . "ii";
$data_bind_values = array_merge($bind_values, [$offset, $per_page]);
mysqli_stmt_bind_param($stmt_kelas, $data_bind_types, ...$data_bind_values);
mysqli_stmt_execute($stmt_kelas);
$kelas = mysqli_stmt_get_result($stmt_kelas);

// [BARU] Ambil daftar prodi untuk filter
$prodi_list = mysqli_query($conn, "SELECT DISTINCT program_studi FROM kelas WHERE program_studi IS NOT NULL AND program_studi != '' ORDER BY program_studi");

// --- MODIFIKASI AJAX SEARCH (Looping Card) ---
if (isset($_GET['ajax_search'])) {
    ?>
    <div class="row">
        <?php if (mysqli_num_rows($kelas) > 0): ?>
            <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 class-card position-relative" id="card-<?= $k['kode_kelas'] ?>">
                        <div class="card-select-overlay">
                            <input type="checkbox" class="form-check-input item-checkbox" 
                                   value="<?= htmlspecialchars($k['kode_kelas']) ?>" 
                                   onchange="toggleSelection('<?= htmlspecialchars($k['kode_kelas']) ?>')">
                        </div>

                        <div class="card-body d-flex flex-column"> <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary mb-2">Kelas <?= htmlspecialchars($k['kode_kelas']) ?></span>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($k['nama_kelas']) ?></h5>
                                </div>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-users"></i> <?= htmlspecialchars($k['jml_mahasiswa']) ?>
                                </span>
                            </div>
                            <p class="text-muted mb-2"><i class="fas fa-graduation-cap me-2"></i><?= htmlspecialchars($k['program_studi']) ?></p>
                            <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i>T.A. <?= htmlspecialchars($k['tahun_ajaran']) ?></p>
                            
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-warning" onclick="editKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['program_studi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['tahun_ajaran'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', '<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash me-1"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-info text-center">Data kelas tidak ditemukan.</div></div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_data > 0): ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_kelas', ['search' => $search, 'prodi' => $filter_prodi]) ?>
    </div>
    <?php endif; ?>
    <?php
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Welcome Banner Modern */
    .welcome-banner-kelas {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner-kelas::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-glow-kelas 4s ease-in-out infinite;
    }
    
    @keyframes pulse-glow-kelas {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.6;
        }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .welcome-banner-kelas h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-kelas .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-kelas .banner-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-kelas .banner-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-kelas .btn-banner {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-kelas .btn-banner:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .welcome-banner-kelas .btn-banner-primary {
        background: white;
        color: var(--primary-color);
        border-color: white;
    }
    
    .welcome-banner-kelas .btn-banner-primary:hover {
        background: rgba(255, 255, 255, 0.95);
        color: var(--primary-color);
    }
    
    /* Dark Mode Support */
    [data-theme="dark"] .welcome-banner-kelas {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    /* Responsive Design */
    @media (max-width: 576px) {
        .welcome-banner-kelas {
            padding: 24px;
            border-radius: 16px;
        }
        
        .welcome-banner-kelas h1 {
            font-size: 24px;
        }
        
        .welcome-banner-kelas .banner-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
        }
        
        .welcome-banner-kelas .btn-banner {
            width: 100%;
            justify-content: center;
        }
    }
    
    /* Responsive untuk tablet dan mobile besar (577px - 750px) */
    @media (min-width: 577px) and (max-width: 750px) {
        .welcome-banner-kelas {
            padding: 30px;
        }
        
        .welcome-banner-kelas > div {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 15px !important;
        }
        
        .welcome-banner-kelas > div > div:first-child {
            width: 100% !important;
        }
        
        .welcome-banner-kelas h1 {
            font-size: 26px;
        }
        
        .welcome-banner-kelas .btn.btn-banner {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 16px 28px !important;
            font-size: 16px;
            margin-left: 0 !important;
            margin-right: 0 !important;
            box-sizing: border-box !important;
        }
        
        .welcome-banner-kelas .btn.btn-banner i {
            margin-right: 10px;
            font-size: 16px;
        }
    }
    
    /* Filter Bar Modern */
    .filter-bar-kelas {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px 28px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        animation: fadeInUp 0.5s ease 0.1s both;
    }
    
    .filter-bar-kelas .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }
    
    .filter-bar-kelas .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-bar-kelas .filter-group-search {
        flex: 2;
        min-width: 280px;
    }
    
    .filter-bar-kelas .filter-group-action {
        flex: 0 0 auto;
        min-width: 120px;
    }
    
    .filter-bar-kelas .form-label-modern {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted, #666);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 10px;
    }
    
    .filter-bar-kelas .form-label-modern i {
        font-size: 0.85rem;
        opacity: 0.7;
    }
    
    .filter-bar-kelas .search-input-wrapper {
        position: relative;
    }
    
    .filter-bar-kelas .search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #999);
        font-size: 15px;
        z-index: 1;
        transition: color 0.3s ease;
    }
    
    .filter-bar-kelas .search-input-wrapper:focus-within .search-icon {
        color: var(--primary-color);
    }
    
    .filter-bar-kelas .form-control-modern {
        width: 100%;
        border-radius: 12px;
        border: 2px solid var(--border-color);
        padding: 14px 16px 14px 46px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        background: var(--bg-card);
        color: var(--text-color);
    }
    
    .filter-bar-kelas .form-control-modern::placeholder {
        color: var(--text-muted, #999);
        font-weight: 400;
    }
    
    .filter-bar-kelas .form-control-modern:hover {
        border-color: var(--primary-color);
    }
    
    .filter-bar-kelas .form-control-modern:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.12);
        outline: none;
    }
    
    .filter-bar-kelas select.form-control-modern {
        padding-left: 16px;
        padding-right: 40px;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background: var(--bg-card) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 16px center;
        background-size: 14px;
    }
    
    .filter-bar-kelas select.form-control-modern::-ms-expand {
        display: none;
    }
    
    .filter-bar-kelas .btn-filter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        border: 2px solid var(--primary-color);
        background: transparent;
        color: var(--primary-color);
        white-space: nowrap;
        height: 52px;
    }
    
    .filter-bar-kelas .btn-filter:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.25);
    }
    
    .filter-bar-kelas .btn-filter.active {
        background: var(--primary-color);
        color: white;
    }
    
    .filter-bar-kelas .select-all-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: rgba(0, 102, 204, 0.08);
        border-radius: 12px;
        height: 52px;
    }
    
    .filter-bar-kelas .select-all-wrapper label {
        font-size: 14px;
        font-weight: 600;
        color: var(--primary-color);
        cursor: pointer;
        white-space: nowrap;
    }

    /* [FIX] Beri ruang lebih di atas konten card agar tidak tertimpa checkbox */
    .class-card .card-body {
        padding-top: 1.5rem;
        transition: padding-top 0.3s ease;
        
    }
    
    /* Padding tambahan saat mode select aktif */
    .select-mode .class-card .card-body {
        padding-top: 3.5rem;
    }

    /* [FIX] Styling konsisten untuk tombol aksi di dalam card */
    .class-card .action-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: auto; /* Mendorong tombol ke bawah */
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }
    .class-card .action-buttons .btn {
        flex-grow: 1; /* Membuat tombol berbagi lebar yang sama */
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .class-card .action-buttons .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Card Styling */
    .class-card { 
        transition: all 0.3s ease; 
        border: 1px solid var(--border-color);
        background-color: var(--bg-card);
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    
    .class-card:hover {
        box-shadow: 0 4px 16px rgba(0, 102, 204, 0.12);
        transform: translateY(-2px);
    }
    
    .class-card .card-title {
        color: var(--text-main);
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    .class-card .badge {
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
    }
    
    .class-card .text-muted {
        font-size: 0.9rem;
    }
    
    .class-card .text-muted i {
        color: var(--primary-color);
        width: 20px;
    }
    
    /* Style untuk Checkbox Selection */
    .class-card.selected { 
        border-color: var(--primary-color); 
        background: linear-gradient(135deg, rgba(0, 102, 204, 0.08) 0%, rgba(0, 102, 204, 0.02) 100%);
        box-shadow: 0 0 0 2px var(--primary-color), 0 4px 16px rgba(0, 102, 204, 0.2);
    }
    
    [data-theme="dark"] .class-card.selected {
        background: linear-gradient(135deg, rgba(0, 102, 204, 0.2) 0%, rgba(0, 102, 204, 0.1) 100%);
    }

    .card-select-overlay { 
        position: absolute; 
        top: 15px; 
        left: 15px; 
        z-index: 5; 
        display: none; /* Sembunyikan default */
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .select-mode .card-select-overlay {
        display: block;
        opacity: 1;
    }
    
    .item-checkbox { 
        width: 22px; 
        height: 22px; 
        cursor: pointer; 
        border: 2px solid var(--text-muted); 
        border-radius: 50%; /* [FIX] Membuat checkbox bulat */
        -webkit-appearance: none;
        appearance: none;
        transition: background-color 0.2s, border-color 0.2s;
        position: relative;
    }
    
    .item-checkbox:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .item-checkbox:checked::after {
        content: '\f00c'; /* Font Awesome check icon */
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        color: white;
        font-size: 12px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    /* Sticky Bottom Bar untuk Bulk Action */
    #bulkActionBar {
        position: fixed; 
        bottom: -100px; 
        left: 0; 
        right: 0;
        background: var(--bg-card);
        box-shadow: 0 -5px 20px rgba(0,0,0,0.15);
        padding: 20px 30px; 
        z-index: 1000;
        transition: bottom 0.3s ease-in-out;
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        border-top: 2px solid var(--primary-color);
        backdrop-filter: blur(10px);
    }
    
    #bulkActionBar.show { 
        bottom: 0; 
    }
    
    [data-theme="dark"] #bulkActionBar {
        box-shadow: 0 -5px 20px rgba(0,0,0,0.4);
    }
    
    #bulkActionBar .badge {
        font-size: 1.2rem;
        padding: 8px 16px;
        border-radius: 10px;
    }
    
    #bulkActionBar .btn {
        border-radius: 12px;
        font-weight: 600;
        padding: 10px 24px;
        transition: all 0.3s ease;
    }
    
    #bulkActionBar .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    /* Penyesuaian agar konten tidak tertutup bar */
    body { padding-bottom: 80px; } 
    
    /* Modal Modern Styling */
    .modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header {
        background: var(--banner-gradient);
        color: white;
        border-radius: 20px 20px 0 0;
        padding: 24px 30px;
        border: none;
    }
    
    .modal-header .modal-title {
        font-weight: 700;
        font-size: 1.3rem;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }
    
    .modal-header .btn-close:hover {
        opacity: 1;
    }
    
    .modal-body {
        padding: 30px;
    }
    
    .modal-body .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    
    .modal-body .form-control,
    .modal-body .form-select {
        border-radius: 12px;
        border: 2px solid var(--border-color);
        padding: 12px 16px;
        font-size: 15px;
        transition: all 0.3s ease;
    }
    
    .modal-body .form-control:focus,
    .modal-body .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.1);
    }
    
    .modal-footer {
        padding: 20px 30px;
        border-top: 2px solid var(--border-color);
    }
    
    .modal-footer .btn {
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 15px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        #bulkActionBar {
            flex-direction: column;
            gap: 10px;
            padding: 15px;
        }
        #bulkActionBar > div {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        #bulkActionBar button {
            flex: 1;
        }
        
        .welcome-banner-kelas {
            padding: 24px;
        }
        
        .welcome-content-kelas {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        
        .welcome-content-kelas h1 {
            font-size: 24px;
        }
        
        .btn-add-kelas {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
        }
        
        /* Filter Bar Mobile */
        .filter-bar-kelas {
            padding: 16px;
            border-radius: 12px;
        }
        
        .filter-bar-kelas .filter-row {
            flex-direction: column;
            gap: 12px;
        }
        
        .filter-bar-kelas .filter-group,
        .filter-bar-kelas .filter-group-search,
        .filter-bar-kelas .filter-group-action {
            flex: 1 1 100%;
            min-width: 100%;
        }
        
        .filter-bar-kelas .form-label-modern {
            font-size: 0.7rem;
            margin-bottom: 6px;
        }
        
        .filter-bar-kelas .form-control-modern {
            padding: 12px 14px 12px 42px;
            font-size: 14px;
        }
        
        .filter-bar-kelas select.form-control-modern {
            padding-left: 14px;
        }
        
        .filter-bar-kelas .btn-filter {
            width: 100%;
            padding: 12px 16px;
            height: 48px;
        }
        
        .filter-bar-kelas .action-buttons-mobile {
            display: flex;
            gap: 10px;
            width: 100%;
        }
        
        .filter-bar-kelas .action-buttons-mobile .btn-filter {
            flex: 1;
        }
        
        .filter-bar-kelas .select-all-wrapper {
            width: 100%;
            justify-content: center;
            padding: 12px 14px;
            height: 48px;
        }
    }
    
    /* Responsive untuk tablet dan mobile besar (577px - 750px) */
    @media (min-width: 577px) and (max-width: 750px) {
        .welcome-banner-kelas {
            padding: 30px;
        }
        
        .welcome-content-kelas {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 15px !important;
        }
        
        .welcome-content-kelas > div:first-child {
            width: 100% !important;
        }
        
        .welcome-content-kelas h1 {
            font-size: 26px;
        }
        
        .welcome-content-kelas .btn.btn-add-kelas {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 16px 28px !important;
            font-size: 16px;
            margin-left: 0 !important;
            margin-right: 0 !important;
            box-sizing: border-box !important;
        }
        
        .welcome-content-kelas .btn.btn-add-kelas i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        /* Filter Bar Tablet */
        .filter-bar-kelas {
            padding: 20px;
        }
        
        .filter-bar-kelas .filter-row {
            flex-wrap: wrap;
            gap: 14px;
        }
        
        .filter-bar-kelas .filter-group-search {
            flex: 1 1 100%;
            min-width: 100%;
        }
        
        .filter-bar-kelas .filter-group {
            flex: 1 1 calc(50% - 7px);
            min-width: calc(50% - 7px);
        }
        
        .filter-bar-kelas .filter-group-action {
            flex: 1 1 calc(50% - 7px);
            min-width: calc(50% - 7px);
        }
        
        .filter-bar-kelas .form-label-modern {
            display: flex !important;
        }
        
        .filter-bar-kelas .btn-filter {
            width: 100%;
            padding: 14px 20px;
            height: 52px;
            font-size: 15px;
        }
        
        .filter-bar-kelas .action-buttons-mobile {
            width: 100%;
        }
        
        .filter-bar-kelas .action-buttons-mobile .btn-filter {
            flex: 1;
        }
        
        .filter-bar-kelas .select-all-wrapper {
            flex: 1;
            justify-content: center;
            height: 52px;
            padding: 14px 16px;
        }
    }

    
    /* Responsive untuk tablet besar (768px - 991px) */
    @media (min-width: 768px) and (max-width: 991px) {
        .welcome-banner-kelas {
            padding: 35px;
        }
        
        .welcome-content-kelas h1 {
            font-size: 28px;
        }
        
        .btn-add-kelas {
            padding: 12px 24px;
            font-size: 14px;
            white-space: nowrap;
        }
        
        /* Filter Bar Tablet Large */
        .filter-bar-kelas {
            padding: 22px;
        }
        
        .filter-bar-kelas .filter-row {
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .filter-bar-kelas .filter-group-search {
            flex: 1 1 100%;
            min-width: 100%;
        }
        
        .filter-bar-kelas .filter-group {
            flex: 1 1 auto;
            min-width: 180px;
        }
        
        .filter-bar-kelas .filter-group-action {
            flex: 0 0 auto;
            min-width: auto;
        }
    }

    
    /* Dark mode adjustments */
    [data-theme="dark"] .filter-bar-kelas {
        background: rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        border-color: rgba(255, 255, 255, 0.1);
    }
    
    [data-theme="dark"] .filter-bar-kelas .form-label-modern {
        color: #aaa;
    }
    
    [data-theme="dark"] .filter-bar-kelas .form-control-modern {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.15);
    }
    
    [data-theme="dark"] .filter-bar-kelas .form-control-modern:hover,
    [data-theme="dark"] .filter-bar-kelas .form-control-modern:focus {
        border-color: var(--primary-color);
    }
    
    [data-theme="dark"] .filter-bar-kelas select.form-control-modern {
        background: rgba(255, 255, 255, 0.05) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 16px center;
        background-size: 14px;
    }
    
    [data-theme="dark"] .filter-bar-kelas .select-all-wrapper {
        background: rgba(0, 102, 204, 0.15);
    }
    
    [data-theme="dark"] .class-card {
        background: rgba(255, 255, 255, 0.03);
    }
    
    [data-theme="dark"] .class-card:hover {
        box-shadow: 0 4px 16px rgba(0, 102, 204, 0.2);
    }
    
    /* Smooth transitions for all interactive elements */
    * {
        transition-property: background-color, border-color, box-shadow, transform;
        transition-duration: 0.3s;
        transition-timing-function: ease;
    }
    
    /* Page fade-in animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .class-card {
        animation: fadeInUp 0.5s ease forwards;
    }
    
    .class-card:nth-child(1) { animation-delay: 0.1s; }
    .class-card:nth-child(2) { animation-delay: 0.15s; }
    .class-card:nth-child(3) { animation-delay: 0.2s; }
    .class-card:nth-child(4) { animation-delay: 0.25s; }
    .class-card:nth-child(5) { animation-delay: 0.3s; }
    .class-card:nth-child(6) { animation-delay: 0.35s; }
    .class-card:nth-child(7) { animation-delay: 0.4s; }
    .class-card:nth-child(8) { animation-delay: 0.45s; }
    .class-card:nth-child(9) { animation-delay: 0.5s; }

    /* Slider Confirm Modern */
    .slider-container {
        position: relative;
        width: 100%;
        height: 60px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-radius: 30px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        user-select: none;
    }
    
    .slider-container.unlocked {
        background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    }
    
    .slider-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-weight: 700;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
        pointer-events: none;
        z-index: 1;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        transition: opacity 0.3s;
    }
    
    .slider-container.unlocked .slider-text {
        opacity: 0;
    }
    
    .slider-handle {
        position: absolute;
        left: 5px;
        top: 5px;
        width: 50px;
        height: 50px;
        background: white;
        border-radius: 50%;
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #dc3545;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        z-index: 2;
    }
    
    .slider-handle:active {
        cursor: grabbing;
        transform: scale(1.1);
    }
    
    .slider-container.unlocked .slider-handle {
        color: #28a745;
        width: calc(100% - 10px);
        border-radius: 30px;
    }
    
    .slider-progress {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        width: 0;
        z-index: 0;
        transition: width 0.1s;
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
                <div class="welcome-banner-kelas mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Kelola Kelas</h1>
                                    <p class="banner-subtitle mb-0">Atur dan kelola semua data kelas dengan mudah dan efisien</p>
                                </div>
                            </div>
                            <span class="banner-badge">
                                <i class="fas fa-users-cog me-1"></i>Manajemen Kelas
                            </span>
                        </div>
                        <button class="btn btn-banner" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="fas fa-plus me-2"></i>Tambah Kelas
                        </button>
                    </div>
                </div>
                
                <?= show_alert() ?>
                
                <div class="filter-bar-kelas">
                    <form method="GET" onsubmit="return false;">
                        <input type="hidden" name="page" value="admin_kelas">
                        <div class="filter-row">
                            <!-- Search Input -->
                            <div class="filter-group filter-group-search">
                                <label for="searchInput" class="form-label-modern">
                                    <i class="fas fa-search"></i>
                                    Cari Kelas
                                </label>
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" name="search" id="searchInput" class="form-control-modern" value="<?= htmlspecialchars($search) ?>" placeholder="Ketik nama atau kode kelas...">
                                </div>
                            </div>
                            
                            <!-- Filter Prodi -->
                            <div class="filter-group">
                                <label for="prodiFilter" class="form-label-modern">
                                    <i class="fas fa-filter"></i>
                                    Program Studi
                                </label>
                                <select name="prodi" id="prodiFilter" class="form-control-modern">
                                    <option value="">Semua Prodi</option>
                                    <?php mysqli_data_seek($prodi_list, 0); while ($p = mysqli_fetch_assoc($prodi_list)): ?>
                                        <option value="<?= htmlspecialchars($p['program_studi']) ?>" <?= $filter_prodi == $p['program_studi'] ? 'selected' : '' ?>><?= htmlspecialchars($p['program_studi']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="filter-group filter-group-action">
                                <label class="form-label-modern d-none d-md-flex">
                                    <i class="fas fa-cog"></i>
                                    Aksi
                                </label>
                                <div class="d-flex gap-2 action-buttons-mobile">
                                    <button type="button" class="btn-filter" id="btnSelectMode" onclick="toggleSelectMode()">
                                        <i class="fas fa-check-square"></i>
                                        <span>Pilih</span>
                                    </button>
                                    <div class="select-all-wrapper d-none" id="selectAllContainer">
                                        <input class="form-check-input item-checkbox m-0" type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="width: 20px; height: 20px; cursor: pointer; border-radius: 6px;">
                                        <label class="m-0" for="selectAll">Semua</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="kelasContainer">
                <div class="row">
                    <?php if (mysqli_num_rows($kelas) > 0): ?>
                        <?php while ($k = mysqli_fetch_assoc($kelas)): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 class-card position-relative" id="card-<?= $k['kode_kelas'] ?>">
                                    <div class="card-select-overlay">
                                        <input type="checkbox" class="form-check-input item-checkbox" 
                                               value="<?= htmlspecialchars($k['kode_kelas']) ?>" 
                                               onchange="toggleSelection('<?= htmlspecialchars($k['kode_kelas']) ?>')">
                                    </div>

                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <span class="badge bg-primary mb-2">Kelas <?= htmlspecialchars($k['kode_kelas']) ?></span>
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($k['nama_kelas']) ?></h5>
                                            </div>
                                            <span class="badge bg-secondary"><i class="fas fa-users"></i> <?= htmlspecialchars($k['jml_mahasiswa']) ?></span>
                                        </div>
                                        <p class="text-muted mb-2"><i class="fas fa-graduation-cap me-2"></i><?= htmlspecialchars($k['program_studi']) ?></p>
                                        <p class="text-muted mb-2"><i class="fas fa-calendar-alt me-2"></i>T.A. <?= htmlspecialchars($k['tahun_ajaran']) ?></p>
                                        
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-warning" onclick="editKelas('<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['program_studi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['tahun_ajaran'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmSlideDelete('single', '<?= htmlspecialchars($k['kode_kelas'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12"><div class="alert alert-info text-center">Data kelas tidak ditemukan.</div></div>
                    <?php endif; ?>
                </div>
                <?php if ($total_data > 0): ?>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                        <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_kelas', ['search' => $search, 'prodi' => $filter_prodi]) ?>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="bulkActionBar">
    <div class="d-flex align-items-center">
        <span class="badge bg-primary me-2" style="font-size: 1rem;"><span id="selectedCount">0</span></span>
        <span class="text-dark fw-bold">Kelas Dipilih</span>
    </div>
    <div>
        <button class="btn btn-secondary me-2" onclick="toggleSelectAll(false)">Batal</button>
        <button class="btn btn-danger" onclick="confirmSlideDelete('bulk')">
            <i class="fas fa-trash-alt me-2"></i>Hapus Terpilih
        </button>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahLabel">Tambah Kelas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Kelas</label>
                        <input type="text" name="kode_kelas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program Studi</label>
                        <input type="text" name="program_studi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" class="form-control" placeholder="Contoh: 2023/2024" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="kode_kelas" id="edit_kode">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditLabel">Edit Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Kelas</label>
                        <input type="text" id="edit_kode_display" class="form-control" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>
                        <input type="text" name="nama_kelas" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program Studi</label>
                        <input type="text" name="program_studi" id="edit_prodi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahun Ajaran</label>
                        <input type="text" name="tahun_ajaran" id="edit_tahun" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSlideConfirm" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                </div>
                <h4 class="fw-bold text-danger mb-2">Konfirmasi Hapus</h4>
                <p class="text-muted mb-4" id="slideConfirmMsg">Apakah Anda yakin? Data yang dihapus tidak dapat dikembalikan.</p>
                
                <div class="slider-container" id="deleteSlider">
                    <div class="slider-progress" id="sliderProgress"></div>
                    <div class="slider-text">GESER UNTUK MENGHAPUS >></div>
                    <div class="slider-handle" id="sliderHandle">
                        <i class="fas fa-trash"></i>
                    </div>
                </div>
                
                <button type="button" class="btn btn-link text-muted mt-3 text-decoration-none" data-bs-dismiss="modal" id="btnCancelSlide">Batal</button>
            </div>
        </div>
    </div>
</div>

<form id="formHapusSingle" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus">
    <input type="hidden" name="kode_kelas" id="hapus_kode_single">
</form>

<form id="formHapusBulk" method="POST" class="d-none">
    <input type="hidden" name="aksi" value="hapus_banyak">
    <div id="bulkInputs"></div>
</form>

<script>
// --- LOGIKA SELEKSI (CHECKBOX) ---
let selectedItems = new Set();
let isSelectMode = false;

function toggleSelectMode() {
    isSelectMode = !isSelectMode;
    const container = document.getElementById('kelasContainer');
    const btn = document.getElementById('btnSelectMode');
    const selectAllContainer = document.getElementById('selectAllContainer');
    
    if (isSelectMode) {
        container.classList.add('select-mode');
        btn.classList.replace('btn-outline-primary', 'btn-primary');
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Batal Pilih';
        selectAllContainer.classList.remove('d-none');
        selectAllContainer.classList.add('d-flex');
    } else {
        container.classList.remove('select-mode');
        btn.classList.replace('btn-primary', 'btn-outline-primary');
        btn.innerHTML = '<i class="fas fa-check-square me-1"></i> Mode Pilih';
        selectAllContainer.classList.add('d-none');
        selectAllContainer.classList.remove('d-flex');
        
        // Reset selection
        selectedItems.clear();
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.class-card').forEach(c => c.classList.remove('selected'));
        updateBulkUI();
    }
}

// [BARU] Fungsi untuk modal edit
function editKelas(kode, nama, prodi, tahun) {
    document.getElementById('edit_kode').value = kode;
    document.getElementById('edit_kode_display').value = kode;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_prodi').value = prodi;
    document.getElementById('edit_tahun').value = tahun;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function toggleSelection(kode) {
    const card = document.getElementById('card-' + kode);
    const checkbox = card.querySelector('.item-checkbox');
    
    if (checkbox.checked) {
        selectedItems.add(kode);
        card.classList.add('selected');
    } else {
        selectedItems.delete(kode);
        card.classList.remove('selected');
    }
    updateBulkUI();
}

function toggleSelectAll() {
    const masterCheck = document.getElementById('selectAll');
    const isChecked = masterCheck.checked;
    const itemCheckboxes = document.querySelectorAll('#kelasContainer .item-checkbox');

    itemCheckboxes.forEach(cb => {
        const kode = cb.value;
        const card = document.getElementById('card-' + kode);

        // Hanya ubah jika statusnya berbeda untuk efisiensi
        if (cb.checked !== isChecked) {
            cb.checked = isChecked;
            if (isChecked) {
                selectedItems.add(kode);
                card.classList.add('selected');
            } else {
                selectedItems.delete(kode);
                card.classList.remove('selected');
            }
        }
    });

    updateBulkUI();
}

function updateBulkUI() {
    const bar = document.getElementById('bulkActionBar');
    const countSpan = document.getElementById('selectedCount');
    const masterCheck = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('#kelasContainer .item-checkbox');
    
    countSpan.innerText = selectedItems.size;
    
    if (selectedItems.size > 0) {
        bar.classList.add('show');
        // Cek apakah semua item terpilih, jika ya, centang master checkbox
        if (itemCheckboxes.length > 0 && selectedItems.size === itemCheckboxes.length) {
            masterCheck.checked = true;
        } else {
            masterCheck.checked = false;
        }
    } else {
        bar.classList.remove('show');
    }
}

// --- LOGIKA SLIDE TO CONFIRM ---
let deleteType = ''; // 'single' atau 'bulk'
let deleteTargetId = ''; 

function confirmSlideDelete(type, id = null) {
    deleteType = type;
    deleteTargetId = id;
    
    const modal = new bootstrap.Modal(document.getElementById('modalSlideConfirm'));
    const msg = document.getElementById('slideConfirmMsg');
    
    if (type === 'bulk') {
        msg.innerHTML = `Anda akan menghapus <b>${selectedItems.size} kelas</b> terpilih.<br>Aksi ini tidak dapat dibatalkan.`;
    } else {
        msg.innerHTML = `Anda akan menghapus Kelas <b>${id}</b>.<br>Semua data mahasiswa di dalamnya juga akan terhapus.`;
    }
    
    resetSlider();
    modal.show();
}

// Slider JavaScript Logic
const sliderContainer = document.getElementById('deleteSlider');
const sliderHandle = document.getElementById('sliderHandle');
const sliderProgress = document.getElementById('sliderProgress');
let isDragging = false;

sliderHandle.addEventListener('mousedown', startDrag);
sliderHandle.addEventListener('touchstart', startDrag);

document.addEventListener('mouseup', endDrag);
document.addEventListener('touchend', endDrag);

document.addEventListener('mousemove', drag);
document.addEventListener('touchmove', drag);

function startDrag(e) {
    isDragging = true;
}

function drag(e) {
    if (!isDragging) return;
    
    // Support mouse & touch
    let clientX = e.clientX || e.touches[0].clientX;
    
    let rect = sliderContainer.getBoundingClientRect();
    let x = clientX - rect.left - (sliderHandle.offsetWidth / 2);
    let max = rect.width - sliderHandle.offsetWidth;
    
    // Batasi pergerakan
    if (x < 0) x = 0;
    if (x > max) x = max;
    
    sliderHandle.style.left = x + 'px';
    sliderProgress.style.width = (x + 20) + 'px'; // +20 for visual overlap
    
    // Jika mencapai ujung (95%)
    if (x >= max * 0.95) {
        isDragging = false;
        sliderContainer.classList.add('unlocked');
        sliderHandle.style.left = max + 'px';
        sliderProgress.style.width = '100%';
        performDelete(); // EKSEKUSI HAPUS
    }
}

function endDrag() {
    if (!isDragging) return;
    isDragging = false;
    
    // Jika dilepas sebelum selesai, kembalikan ke awal
    if (!sliderContainer.classList.contains('unlocked')) {
        sliderHandle.style.left = '5px';
        sliderProgress.style.width = '0';
    }
}

function resetSlider() {
    sliderContainer.classList.remove('unlocked');
    sliderHandle.style.left = '5px';
    sliderProgress.style.width = '0';
    document.querySelector('.slider-text').style.opacity = '1';
}

function performDelete() {
    // Beri jeda sedikit untuk efek visual selesai
    setTimeout(() => {
        if (deleteType === 'single') {
            document.getElementById('hapus_kode_single').value = deleteTargetId;
            document.getElementById('formHapusSingle').submit();
        } else if (deleteType === 'bulk') {
            const container = document.getElementById('bulkInputs');
            container.innerHTML = ''; // Reset
            selectedItems.forEach(kode => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = kode;
                container.appendChild(input);
            });
            document.getElementById('formHapusBulk').submit();
        }
    }, 300);
}

// [BARU] Live Search Logic
let searchTimeout = null;
const searchInput = document.getElementById('searchInput');
const prodiSelect = document.getElementById('prodiFilter');

function performSearch() {
    clearTimeout(searchTimeout);
    const searchValue = searchInput.value;
    const prodiValue = prodiSelect.value;
    const container = document.getElementById('kelasContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=admin_kelas&ajax_search=1&search=${encodeURIComponent(searchValue)}&prodi=${encodeURIComponent(prodiValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Re-check selected items after DOM update
                selectedItems.forEach(kode => {
                    const checkbox = container.querySelector(`.item-checkbox[value="${kode}"]`);
                    if (checkbox) checkbox.checked = true;
                    const card = document.getElementById('card-' + kode);
                    if(card) card.classList.add('selected');
                });
                updateBulkUI();
            })
            .catch(error => console.error('Error:', error));
    }, 300);
}

searchInput.addEventListener('input', performSearch);
prodiSelect.addEventListener('change', performSearch);
</script>

<?php include 'includes/footer.php'; ?>
