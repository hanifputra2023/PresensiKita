<?php
$page = 'asisten_jadwal';
$asisten = get_asisten_login();

if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Data asisten tidak ditemukan. Pastikan akun Anda sudah terdaftar di tabel asisten.</div>';
    return;
}

$kode_asisten = $asisten['kode_asisten'];

// Fungsi untuk cek konflik jadwal lab
function cekKonflikLabAsisten($conn, $tanggal, $jam_mulai, $jam_selesai, $kode_lab, $exclude_id = null) {
    if (empty($kode_lab)) return false;
    $exclude_sql = $exclude_id ? "AND j.id != '$exclude_id'" : "";
    $query = "SELECT j.*, k.nama_kelas, l.nama_lab 
              FROM jadwal j 
              LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
              LEFT JOIN lab l ON j.kode_lab = l.kode_lab
              WHERE j.kode_lab = '$kode_lab' 
              AND j.tanggal = '$tanggal' 
              AND j.jam_mulai < '$jam_selesai' 
              AND j.jam_selesai > '$jam_mulai'
              $exclude_sql
              LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    
    if ($aksi == 'edit') {
        $id = (int)$_POST['id'];
        
        // Security Check: Pastikan asisten ini berhak mengedit jadwal ini - prepared statement
        $stmt_cek = mysqli_prepare($conn, "SELECT id FROM jadwal WHERE id = ? AND (kode_asisten_1 = ? OR kode_asisten_2 = ?)");
        mysqli_stmt_bind_param($stmt_cek, "iss", $id, $kode_asisten, $kode_asisten);
        mysqli_stmt_execute($stmt_cek);
        $cek_kepemilikan = mysqli_stmt_get_result($stmt_cek);
        if (mysqli_num_rows($cek_kepemilikan) == 0) {
            set_alert('danger', 'Error! Anda tidak memiliki hak untuk mengubah jadwal ini.');
        } else {
            $materi = escape($_POST['materi']);
            $sesi = (int)$_POST['sesi'];
            
            // Hanya update materi, tidak semua field seperti admin - prepared statement
            $stmt_upd = mysqli_prepare($conn, "UPDATE jadwal SET materi=?, sesi=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_upd, "sii", $materi, $sesi, $id);
            mysqli_stmt_execute($stmt_upd);
            set_alert('success', 'Jadwal berhasil diupdate!');
        }
    }
    
    header("Location: index.php?page=asisten_jadwal");
    exit;
}

// Filter dan Pencarian - prepared statement
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$search_param = '%' . $search . '%';

// Build prepared statement untuk jadwal
if ($filter_kelas && $search) {
    $stmt_jadwal = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk 
                                FROM jadwal j 
                                LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                WHERE (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)
                                AND j.kode_kelas = ?
                                AND (j.materi LIKE ? OR mk.nama_mk LIKE ?)
                                ORDER BY j.tanggal ASC, j.jam_mulai ASC");
    mysqli_stmt_bind_param($stmt_jadwal, "sssss", $kode_asisten, $kode_asisten, $filter_kelas, $search_param, $search_param);
} elseif ($filter_kelas) {
    $stmt_jadwal = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk 
                                FROM jadwal j 
                                LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                WHERE (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)
                                AND j.kode_kelas = ?
                                ORDER BY j.tanggal ASC, j.jam_mulai ASC");
    mysqli_stmt_bind_param($stmt_jadwal, "sss", $kode_asisten, $kode_asisten, $filter_kelas);
} elseif ($search) {
    $stmt_jadwal = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk 
                                FROM jadwal j 
                                LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                WHERE (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)
                                AND (j.materi LIKE ? OR mk.nama_mk LIKE ?)
                                ORDER BY j.tanggal ASC, j.jam_mulai ASC");
    mysqli_stmt_bind_param($stmt_jadwal, "ssss", $kode_asisten, $kode_asisten, $search_param, $search_param);
} else {
    $stmt_jadwal = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk 
                                FROM jadwal j 
                                LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                WHERE (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)
                                ORDER BY j.tanggal ASC, j.jam_mulai ASC");
    mysqli_stmt_bind_param($stmt_jadwal, "ss", $kode_asisten, $kode_asisten);
}
mysqli_stmt_execute($stmt_jadwal);
$jadwal = mysqli_stmt_get_result($stmt_jadwal);

$stmt_kelas = mysqli_prepare($conn, "SELECT DISTINCT k.kode_kelas, k.nama_kelas FROM jadwal j JOIN kelas k ON j.kode_kelas = k.kode_kelas WHERE j.kode_asisten_1 = ? OR j.kode_asisten_2 = ? ORDER BY k.nama_kelas");
mysqli_stmt_bind_param($stmt_kelas, "ss", $kode_asisten, $kode_asisten);
mysqli_stmt_execute($stmt_kelas);
$kelas_list = mysqli_stmt_get_result($stmt_kelas);

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <!-- Desktop Table -->
    <div class="table-responsive d-none d-lg-block">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Pertemuan</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Kelas</th>
                    <th>Lab</th>
                    <th>Materi</th>
                    <th>Jenis</th>
                    <th>Sesi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($jadwal) === 0) {
                    echo '<tr><td colspan="8" class="text-center">Tidak ada jadwal mengajar.</td></tr>';
                }
                $now_time = date('H:i:s');
                while ($j = mysqli_fetch_assoc($jadwal)): ?>
                    <?php 
                    $is_past = strtotime($j['tanggal']) < strtotime(date('Y-m-d'));
                    $is_today = $j['tanggal'] == date('Y-m-d');
                    $is_finished_today = $is_today && (strtotime($j['jam_selesai']) < strtotime($now_time));
                    $is_ended = $is_past || $is_finished_today;
                    $status_class = $is_ended ? 'jadwal-expired' : ($is_today ? 'jadwal-today table-primary' : 'jadwal-upcoming');
                    $data_status = $is_ended ? 'expired' : 'upcoming';
                    ?>
                    <tr class="<?= $status_class ?>" data-jadwal-status="<?= $data_status ?>">
                        <td><span class="badge bg-secondary"><?= $j['pertemuan_ke'] ?></span></td>
                        <td><?= format_tanggal($j['tanggal']) ?></td>
                        <td><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></td>
                        <td><span class="badge bg-primary"><?= $j['nama_kelas'] ?></span></td>
                        <td><?= $j['nama_lab'] ?></td>
                        <td><?= $j['materi'] ?></td>
                        <td>
                            <span class="badge bg-<?= ($j['jenis'] == 'responsi' || $j['jenis'] == 'ujikom') ? 'danger' : ($j['jenis'] == 'inhall' ? 'warning' : ($j['jenis'] == 'praresponsi' ? 'primary' : 'info')) ?>">
                                <?= ucfirst($j['jenis']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= $j['sesi'] == 0 ? 'Semua' : 'Sesi ' . $j['sesi'] ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="index.php?page=asisten_materi&jadwal=<?= $j['id'] ?>" class="btn btn-info" title="Kelola Materi (File/Teks)">
                                    <i class="fas fa-book"></i>
                                </a>
                                <button class="btn btn-warning" onclick='editJadwal(<?= json_encode($j) ?>)' title="Edit Judul Materi">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!$is_ended): ?>
                                    <a href="index.php?page=asisten_qrcode&jadwal=<?= $j['id'] ?>" class="btn btn-primary" title="Generate QR">
                                        <i class="fas fa-qrcode"></i>
                                    </a>
                                    <a href="index.php?page=asisten_monitoring&jadwal=<?= $j['id'] ?>" class="btn btn-success" title="Monitoring Presensi">
                                        <i class="fas fa-tv"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Mobile Cards -->
    <div class="d-lg-none">
        <?php 
        mysqli_data_seek($jadwal, 0);
        if (mysqli_num_rows($jadwal) === 0) {
            echo '<div class="text-center text-muted py-4">Tidak ada jadwal mengajar.</div>';
        }
        while ($j = mysqli_fetch_assoc($jadwal)): ?>
            <?php 
            $is_past = strtotime($j['tanggal']) < strtotime(date('Y-m-d'));
            $is_today = $j['tanggal'] == date('Y-m-d');
            $is_finished_today = $is_today && (strtotime($j['jam_selesai']) < strtotime($now_time));
            $is_ended = $is_past || $is_finished_today;
            $status_class = $is_ended ? 'jadwal-expired' : ($is_today ? 'jadwal-today border-primary' : 'jadwal-upcoming');
            $data_status = $is_ended ? 'expired' : 'upcoming';
            ?>
            <div class="card mb-2 <?= $status_class ?>" data-jadwal-status="<?= $data_status ?>">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-secondary">P<?= $j['pertemuan_ke'] ?></span>
                                <span class="badge bg-primary"><?= $j['nama_kelas'] ?></span>
                                <span class="badge bg-<?= ($j['jenis'] == 'responsi' || $j['jenis'] == 'ujikom') ? 'danger' : ($j['jenis'] == 'inhall' ? 'warning' : ($j['jenis'] == 'praresponsi' ? 'primary' : 'info')) ?>">
                                    <?= ucfirst($j['jenis']) ?>
                                </span>
                            </div>
                            <h6 class="mb-1"><?= $j['nama_mk'] ?></h6>
                            <div class="small text-muted"><?= $j['materi'] ?></div>
                            <div class="small mt-2">
                                <i class="fas fa-calendar me-1"></i><?= format_tanggal($j['tanggal']) ?>
                                <br><i class="fas fa-clock me-1"></i><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?>
                                <br><i class="fas fa-map-marker-alt me-1"></i><?= $j['nama_lab'] ?>
                            </div>
                        </div>
                        <div class="btn-group-vertical">
                                <a href="index.php?page=asisten_materi&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-info" title="Kelola Materi (File/Teks)">
                                <i class="fas fa-book"></i>
                            </a>
                            <button class="btn btn-sm btn-warning" onclick='editJadwal(<?= json_encode($j) ?>)' title="Edit Judul Materi">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if (!$is_ended): ?>
                                <a href="index.php?page=asisten_qrcode&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-primary" title="Generate QR">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <a href="index.php?page=asisten_monitoring&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-success" title="Monitoring Presensi">
                                    <i class="fas fa-tv"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* Dark Mode Button Fixes */
[data-theme="dark"] .btn-warning, 
[data-theme="dark"] .btn-info {
    color: #212529 !important;
}
/* Dark Mode Table Highlight Fix */
[data-theme="dark"] .table-primary {
    --bs-table-bg: rgba(0, 102, 204, 0.2);
    --bs-table-color: var(--text-main);
    border-color: rgba(0, 102, 204, 0.3);
}
/* Jadwal Status Styling */
.jadwal-expired {
    background-color: rgba(108, 117, 125, 0.1) !important;
}
.jadwal-expired .card-body,
.jadwal-expired td {
    color: #6c757d !important;
}
.jadwal-expired .badge {
    opacity: 0.7;
}
.jadwal-upcoming {
    background-color: rgba(25, 135, 84, 0.05) !important;
}
.jadwal-today {
    border-left: 4px solid #0d6efd !important;
}
[data-theme="dark"] .jadwal-expired {
    background-color: rgba(108, 117, 125, 0.15) !important;
}
[data-theme="dark"] .jadwal-expired .card-body,
[data-theme="dark"] .jadwal-expired td {
    color: #8c959d !important;
}
[data-theme="dark"] .jadwal-upcoming {
    background-color: rgba(25, 135, 84, 0.1) !important;
}
/* Status Filter Tabs */
.status-filter-tabs .nav-link {
    border-radius: 20px;
    padding: 0.4rem 1rem;
    font-size: 0.85rem;
    margin-right: 0.25rem;
}
.status-filter-tabs .nav-link.active {
    background-color: #0d6efd;
    color: white;
}
.status-filter-tabs .nav-link:not(.active) {
    background-color: var(--card-bg, #f8f9fa);
    color: var(--text-main, #212529);
    border: 1px solid #dee2e6;
}
[data-theme="dark"] .status-filter-tabs .nav-link:not(.active) {
    background-color: #2d3238;
    border-color: #404448;
}
/* Mobile Responsive for Status Tabs */
@media (max-width: 575.98px) {
    .status-filter-tabs .nav {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .status-filter-tabs .nav::-webkit-scrollbar {
        display: none;
    }
    .status-filter-tabs .nav-link {
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        white-space: nowrap;
    }
    .status-filter-tabs .nav-link i {
        margin-right: 0.25rem !important;
    }
    .status-filter-tabs .nav-link .badge {
        font-size: 0.7rem;
        padding: 0.2em 0.5em;
    }
}
</style>

<?php
// Count expired and upcoming jadwal for tabs
mysqli_data_seek($jadwal, 0);
$count_expired = 0;
$count_upcoming = 0;
$now_time_count = date('H:i:s');
while ($count_j = mysqli_fetch_assoc($jadwal)) {
    $is_past_c = strtotime($count_j['tanggal']) < strtotime(date('Y-m-d'));
    $is_today_c = $count_j['tanggal'] == date('Y-m-d');
    $is_finished_today_c = $is_today_c && (strtotime($count_j['jam_selesai']) < strtotime($now_time_count));
    if ($is_past_c || $is_finished_today_c) {
        $count_expired++;
    } else {
        $count_upcoming++;
    }
}
mysqli_data_seek($jadwal, 0);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-calendar-alt me-2"></i>Jadwal Mengajar Saya</h4>
                
                <?= show_alert() ?>

                <div class="card mb-4">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="asisten_jadwal">
                            <div class="col-12 col-md-5">
                                <label class="form-label small">Cari Materi / MK</label>
                                <input type="text" name="search" id="searchInput" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan kata kunci...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small">Filter Kelas</label>
                                <select name="kelas" id="kelasSelect" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    <?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                        <option value="<?= $k['kode_kelas'] ?>" <?= $filter_kelas == $k['kode_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                            </div>
                        </form>
                        
                        <!-- Status Filter Tabs -->
                        <div class="mt-3">
                            <nav class="status-filter-tabs">
                                <ul class="nav nav-pills" id="statusTabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#" data-status="semua">
                                            Semua <span class="badge bg-secondary ms-1"><?= $count_expired + $count_upcoming ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-status="upcoming">
                                            <i class="fas fa-clock me-1"></i>Akan Datang <span class="badge bg-success ms-1"><?= $count_upcoming ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-status="expired">
                                            <i class="fas fa-history me-1"></i>Sudah Lewat <span class="badge bg-secondary ms-1"><?= $count_expired ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body p-2 p-md-3" id="jadwalContainer">
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pertemuan</th>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Kelas</th>
                                        <th>Lab</th>
                                        <th>Materi</th>
                                        <th>Jenis</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($jadwal, 0);
                                    if (mysqli_num_rows($jadwal) === 0) {
                                        echo '<tr><td colspan="8" class="text-center">Tidak ada jadwal mengajar.</td></tr>';
                                    }
                                    $now_time = date('H:i:s');
                                    while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                        <?php 
                                        $is_past = strtotime($j['tanggal']) < strtotime(date('Y-m-d'));
                                        $is_today = $j['tanggal'] == date('Y-m-d');
                                        $is_finished_today = $is_today && (strtotime($j['jam_selesai']) < strtotime($now_time));
                                        $is_ended = $is_past || $is_finished_today;
                                        $status_class = $is_ended ? 'jadwal-expired' : ($is_today ? 'jadwal-today table-primary' : 'jadwal-upcoming');
                                        $data_status = $is_ended ? 'expired' : 'upcoming';
                                        ?>
                                        <tr class="<?= $status_class ?>" data-jadwal-status="<?= $data_status ?>">
                                            <td><span class="badge bg-secondary"><?= $j['pertemuan_ke'] ?></span></td>
                                            <td><?= format_tanggal($j['tanggal']) ?></td>
                                            <td><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></td>
                                            <td><span class="badge bg-primary"><?= $j['nama_kelas'] ?></span></td>
                                            <td><?= $j['nama_lab'] ?></td>
                                            <td><?= $j['materi'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= ($j['jenis'] == 'responsi' || $j['jenis'] == 'ujikom') ? 'danger' : ($j['jenis'] == 'inhall' ? 'warning' : ($j['jenis'] == 'praresponsi' ? 'primary' : 'info')) ?>">
                                                    <?= ucfirst($j['jenis']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="index.php?page=asisten_materi&jadwal=<?= $j['id'] ?>" class="btn btn-info" title="Kelola Materi (File/Teks)">
                                                        <i class="fas fa-book"></i>
                                                    </a>
                                                    <button class="btn btn-warning" onclick='editJadwal(<?= json_encode($j) ?>)' title="Edit Judul Materi">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (!$is_ended): ?>
                                                        <a href="index.php?page=asisten_qrcode&jadwal=<?= $j['id'] ?>" class="btn btn-primary" title="Generate QR">
                                                            <i class="fas fa-qrcode"></i>
                                                        </a>
                                                        <a href="index.php?page=asisten_monitoring&jadwal=<?= $j['id'] ?>" class="btn btn-success" title="Monitoring Presensi">
                                                            <i class="fas fa-tv"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile Cards -->
                        <div class="d-lg-none">
                            <?php 
                            mysqli_data_seek($jadwal, 0);
                            if (mysqli_num_rows($jadwal) === 0) {
                                echo '<div class="text-center text-muted py-4">Tidak ada jadwal mengajar.</div>';
                            }
                            while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                <?php 
                                $is_past = strtotime($j['tanggal']) < strtotime(date('Y-m-d'));
                                $is_today = $j['tanggal'] == date('Y-m-d');
                                $is_finished_today = $is_today && (strtotime($j['jam_selesai']) < strtotime($now_time));
                                $is_ended = $is_past || $is_finished_today;
                                $status_class = $is_ended ? 'jadwal-expired' : ($is_today ? 'jadwal-today border-primary' : 'jadwal-upcoming');
                                $data_status = $is_ended ? 'expired' : 'upcoming';
                                ?>
                                <div class="card mb-2 <?= $status_class ?>" data-jadwal-status="<?= $data_status ?>">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge bg-secondary">P<?= $j['pertemuan_ke'] ?></span>
                                                    <span class="badge bg-primary"><?= $j['nama_kelas'] ?></span>
                                                    <span class="badge bg-<?= ($j['jenis'] == 'responsi' || $j['jenis'] == 'ujikom') ? 'danger' : ($j['jenis'] == 'inhall' ? 'warning' : ($j['jenis'] == 'praresponsi' ? 'primary' : 'info')) ?>">
                                                        <?= ucfirst($j['jenis']) ?>
                                                    </span>
                                                </div>
                                                <h6 class="mb-1"><?= $j['nama_mk'] ?></h6>
                                                <div class="small text-muted"><?= $j['materi'] ?></div>
                                                <div class="small mt-2">
                                                    <i class="fas fa-calendar me-1"></i><?= format_tanggal($j['tanggal']) ?>
                                                    <br><i class="fas fa-clock me-1"></i><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?>
                                                    <br><i class="fas fa-map-marker-alt me-1"></i><?= $j['nama_lab'] ?>
                                                </div>
                                            </div>
                                            <div class="btn-group-vertical">
                                                 <a href="index.php?page=asisten_materi&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-info" title="Kelola Materi (File/Teks)">
                                                    <i class="fas fa-book"></i>
                                                </a>
                                                <button class="btn btn-sm btn-warning" onclick='editJadwal(<?= json_encode($j) ?>)' title="Edit Judul Materi">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$is_ended): ?>
                                                    <a href="index.php?page=asisten_qrcode&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-primary" title="Generate QR">
                                                        <i class="fas fa-qrcode"></i>
                                                    </a>
                                                    <a href="index.php?page=asisten_monitoring&jadwal=<?= $j['id'] ?>" class="btn btn-sm btn-success" title="Monitoring Presensi">
                                                        <i class="fas fa-tv"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Materi -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Judul Materi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Materi</label>
                        <input type="text" name="materi" id="edit_materi" class="form-control" required>
                        <small class="text-muted">Anda hanya dapat mengubah judul materi di sini.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sesi</label>
                        <select name="sesi" id="edit_sesi" class="form-select">
                            <option value="0">Semua Sesi (0)</option>
                            <option value="1">Sesi 1</option>
                            <option value="2">Sesi 2</option>
                            <option value="3">Sesi 3</option>
                        </select>
                    </div>
                    <div class="alert alert-info small">
                        Untuk mengubah detail jadwal lain seperti jam, tanggal, atau lab, silakan hubungi Admin.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Materi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editJadwal(j) {
    document.getElementById('edit_id').value = j.id;
    document.getElementById('edit_materi').value = j.materi;
    document.getElementById('edit_sesi').value = j.sesi;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>

<script>
// Live Search & Status Filter
let searchTimeout = null;
let currentStatusFilter = 'semua';
const searchInput = document.getElementById('searchInput');
const kelasSelect = document.getElementById('kelasSelect');

function performSearch() {
    clearTimeout(searchTimeout);
    const searchValue = searchInput.value;
    const kelasValue = kelasSelect.value;
    const container = document.getElementById('jadwalContainer');
    
    searchTimeout = setTimeout(function() {
        fetch(`index.php?page=asisten_jadwal&ajax_search=1&search=${encodeURIComponent(searchValue)}&kelas=${encodeURIComponent(kelasValue)}`)
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                // Re-apply status filter after AJAX load
                applyStatusFilter(currentStatusFilter);
            })
            .catch(error => console.error('Error:', error));
    }, 300);
}

function applyStatusFilter(status) {
    currentStatusFilter = status;
    
    // Filter table rows (desktop)
    const tableRows = document.querySelectorAll('#jadwalContainer tbody tr[data-jadwal-status]');
    tableRows.forEach(row => {
        if (status === 'semua') {
            row.style.display = '';
        } else {
            row.style.display = row.dataset.jadwalStatus === status ? '' : 'none';
        }
    });
    
    // Filter cards (mobile)
    const cards = document.querySelectorAll('#jadwalContainer .card[data-jadwal-status]');
    cards.forEach(card => {
        if (status === 'semua') {
            card.style.display = '';
        } else {
            card.style.display = card.dataset.jadwalStatus === status ? '' : 'none';
        }
    });
    
    // Check if no results visible
    const visibleTableRows = document.querySelectorAll('#jadwalContainer tbody tr[data-jadwal-status]:not([style*="display: none"])');
    const visibleCards = document.querySelectorAll('#jadwalContainer .card[data-jadwal-status]:not([style*="display: none"])');
    
    // Show/hide empty message
    let emptyMsgDesktop = document.querySelector('.jadwal-empty-desktop');
    let emptyMsgMobile = document.querySelector('.jadwal-empty-mobile');
    
    if (visibleTableRows.length === 0 && tableRows.length > 0) {
        if (!emptyMsgDesktop) {
            const tbody = document.querySelector('#jadwalContainer tbody');
            const tr = document.createElement('tr');
            tr.className = 'jadwal-empty-desktop';
            tr.innerHTML = '<td colspan="8" class="text-center text-muted py-3">Tidak ada jadwal untuk filter ini.</td>';
            tbody.appendChild(tr);
        }
    } else if (emptyMsgDesktop) {
        emptyMsgDesktop.remove();
    }
    
    if (visibleCards.length === 0 && cards.length > 0) {
        if (!emptyMsgMobile) {
            const mobileContainer = document.querySelector('#jadwalContainer .d-lg-none');
            const div = document.createElement('div');
            div.className = 'jadwal-empty-mobile text-center text-muted py-3';
            div.textContent = 'Tidak ada jadwal untuk filter ini.';
            mobileContainer.appendChild(div);
        }
    } else if (emptyMsgMobile) {
        emptyMsgMobile.remove();
    }
}

// Status Tab Handlers
document.querySelectorAll('#statusTabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Update active tab
        document.querySelectorAll('#statusTabs .nav-link').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Apply filter
        const status = this.dataset.status;
        applyStatusFilter(status);
    });
});

searchInput.addEventListener('input', performSearch);
kelasSelect.addEventListener('change', performSearch);
</script>

<?php include 'includes/footer.php'; ?>
