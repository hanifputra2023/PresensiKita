<?php
$page = 'asisten_izin';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

// Proses Approve/Reject - HARUS DI ATAS SEBELUM OUTPUT HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $pengajuan_id = (int)($_POST['pengajuan_id'] ?? 0);
    
    if ($pengajuan_id > 0) {
        // Ambil data pengajuan
        $pengajuan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pi.*, j.kode_asisten_1, j.kode_asisten_2 
                                                              FROM penggantian_inhall pi
                                                              JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                                              WHERE pi.id = '$pengajuan_id'"));
        
        // Validasi asisten yang berhak
        if ($pengajuan && ($pengajuan['kode_asisten_1'] == $kode_asisten || $pengajuan['kode_asisten_2'] == $kode_asisten)) {
            $nim = $pengajuan['nim'];
            $jadwal_id = $pengajuan['jadwal_asli_id'];
            $jenis_izin = $pengajuan['materi_diulang']; // izin atau sakit
            
            if ($action == 'approve') {
                // Update status approval
                mysqli_query($conn, "UPDATE penggantian_inhall SET 
                                     status_approval = 'approved', 
                                     approved_by = '$kode_asisten', 
                                     approved_at = NOW() 
                                     WHERE id = '$pengajuan_id'");
                
                // Update status presensi mahasiswa menjadi izin/sakit
                mysqli_query($conn, "UPDATE presensi_mahasiswa SET 
                                     status = '$jenis_izin', 
                                     waktu_presensi = NOW(), 
                                     metode = 'manual',
                                     validated_by = '$kode_asisten'
                                     WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'");
                
                log_aktivitas($_SESSION['user_id'], 'APPROVE_IZIN', 'penggantian_inhall', $pengajuan_id, 
                              "Asisten $kode_asisten menyetujui $jenis_izin mahasiswa $nim");
                set_alert('success', 'Pengajuan izin berhasil disetujui!');
                
            } elseif ($action == 'reject') {
                $alasan_reject = escape($_POST['alasan_reject'] ?? 'Tidak memenuhi syarat');
                
                // Update status approval menjadi rejected
                mysqli_query($conn, "UPDATE penggantian_inhall SET 
                                     status_approval = 'rejected', 
                                     approved_by = '$kode_asisten', 
                                     approved_at = NOW(),
                                     alasan_reject = '$alasan_reject'
                                     WHERE id = '$pengajuan_id'");
                
                // Update status presensi mahasiswa menjadi alpha
                mysqli_query($conn, "UPDATE presensi_mahasiswa SET 
                                     status = 'alpha', 
                                     waktu_presensi = NOW(), 
                                     metode = 'manual',
                                     validated_by = '$kode_asisten'
                                     WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'");
                
                log_aktivitas($_SESSION['user_id'], 'REJECT_IZIN', 'penggantian_inhall', $pengajuan_id, 
                              "Asisten $kode_asisten menolak $jenis_izin mahasiswa $nim: $alasan_reject");
                set_alert('warning', 'Pengajuan izin ditolak dan status diubah menjadi Alpha.');
            }
        } else {
            set_alert('danger', 'Anda tidak berhak memproses pengajuan ini!');
        }
    }
    
    header("Location: index.php?page=asisten_izin&tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
}

// Tab aktif
$active_tab = $_GET['tab'] ?? 'pending';

// Filter
$filter_status = isset($_GET['jenis']) ? escape($_GET['jenis']) : '';

// Query berdasarkan tab
$where_approval = "";
if ($active_tab == 'pending') {
    $where_approval = "AND pi.status_approval = 'pending'";
} elseif ($active_tab == 'approved') {
    $where_approval = "AND pi.status_approval = 'approved'";
} elseif ($active_tab == 'rejected') {
    $where_approval = "AND pi.status_approval = 'rejected'";
}

$where_jenis = $filter_status ? "AND pi.materi_diulang = '$filter_status'" : "";
?>

<style>
.modal-header.custom-gradient {
    background: linear-gradient(135deg, #0066ccff 0%, #3b5ca5ff 50%, #0066cc 100%);
    border-radius: 20px 20px 0 0 !important;
    color: white !important;
}
.modal-content {
    border-radius: 20px !important;
}
.izin-page .nav-pills .nav-link.active {
    background-color: #0066cc;
}
.izin-page .nav-pills .nav-link {
    color: #0066cc;
}
.badge-pending {
    background-color: #ffaa00;
    color: #000;
}

/* ==================== RESPONSIVE DESIGN ==================== */

/* Tablet (max-width: 991px) */
@media (max-width: 991.98px) {
    .izin-page .content-wrapper {
        padding: 20px 15px !important;
    }
    
    .izin-page .content-wrapper h4 {
        font-size: 1.2rem;
    }
    
    /* Nav Pills Responsive */
    .izin-page .nav-pills {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        gap: 8px;
    }
    
    .izin-page .nav-pills::-webkit-scrollbar {
        display: none;
    }
    
    .izin-page .nav-pills .nav-item {
        flex-shrink: 0;
    }
    
    .izin-page .nav-pills .nav-link {
        padding: 10px 16px;
        font-size: 0.9rem;
        white-space: nowrap;
    }
}

/* Mobile (max-width: 767px) */
@media (max-width: 767.98px) {
    .izin-page .content-wrapper {
        padding: 16px 12px !important;
    }
    
    .izin-page .content-wrapper h4 {
        font-size: 1.1rem;
        margin-bottom: 16px !important;
    }
    
    /* Nav Pills Mobile - Full Width */
    .izin-page .nav-pills {
        display: flex;
        flex-wrap: nowrap;
        gap: 6px;
        margin-bottom: 16px !important;
        padding-bottom: 4px;
    }
    
    .izin-page .nav-pills .nav-item {
        flex: 1;
        min-width: 0;
    }
    
    .izin-page .nav-pills .nav-link {
        padding: 10px 8px;
        font-size: 0.8rem;
        text-align: center;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        width: 100%;
    }
    
    .izin-page .nav-pills .nav-link i {
        font-size: 0.85rem;
    }
    
    .izin-page .nav-pills .nav-link .badge {
        font-size: 0.65rem;
        padding: 2px 5px;
    }
    
    /* Card Filter */
    .izin-page .card.mb-4 .card-body {
        padding: 10px !important;
    }
    
    .izin-page .card.mb-4 .form-label {
        font-size: 0.75rem;
        margin-bottom: 4px;
    }
    
    .izin-page .card.mb-4 .form-select-sm {
        font-size: 0.85rem;
        padding: 6px 10px;
    }
    
    .izin-page .card.mb-4 .btn-sm {
        font-size: 0.8rem;
        padding: 6px 12px;
    }
    
    /* Main Card */
    .izin-page .card > .card-body {
        padding: 12px;
    }
    
    /* Mobile Cards Styling */
    .izin-page .mobile-cards .card.mb-3 {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    
    .izin-page .mobile-cards .card-header {
        padding: 12px 14px;
        border-radius: 12px 12px 0 0;
    }
    
    .izin-page .mobile-cards .card-header strong {
        font-size: 0.95rem;
    }
    
    .izin-page .mobile-cards .card-body {
        padding: 12px 14px;
    }
    
    .izin-page .mobile-cards .card-body .small {
        font-size: 0.8rem;
    }
    
    .izin-page .mobile-cards .btn-group-sm .btn {
        font-size: 0.75rem;
        padding: 6px 12px;
    }
    
    /* Empty State */
    .izin-page .text-center.py-5 {
        padding: 30px 15px !important;
    }
    
    .izin-page .text-center.py-5 i {
        font-size: 2.5rem !important;
    }
    
    .izin-page .text-center.py-5 p {
        font-size: 0.9rem;
    }
    
    /* Modal Responsive */
    .modal-dialog {
        margin: 10px;
    }
    
    .modal-header.custom-gradient,
    .modal-header.bg-danger {
        padding: 14px 16px;
        border-radius: 16px 16px 0 0 !important;
    }
    
    .modal-header .modal-title {
        font-size: 1rem;
    }
    
    .modal-body {
        padding: 16px;
    }
    
    .modal-body .row.mb-2 {
        margin-bottom: 10px !important;
    }
    
    .modal-body .row.mb-2 .col-4 {
        font-size: 0.8rem;
        padding-right: 5px;
    }
    
    .modal-body .row.mb-2 .col-8 {
        font-size: 0.85rem;
    }
    
    .modal-body hr {
        margin: 12px 0;
    }
    
    .modal-body .alert {
        padding: 10px 12px;
        font-size: 0.85rem;
    }
    
    .modal-body textarea.form-control {
        font-size: 0.9rem;
    }
    
    .modal-footer {
        padding: 12px 16px;
        gap: 8px;
    }
    
    .modal-footer .btn {
        font-size: 0.85rem;
        padding: 8px 14px;
    }
    
    /* Modal Bukti */
    .modal-lg {
        max-width: calc(100% - 20px);
    }
    
    .modal-lg .modal-body {
        padding: 10px;
    }
    
    .modal-lg .modal-body img {
        max-height: 60vh;
    }
}

/* Extra Small Mobile (max-width: 575px) */
@media (max-width: 575.98px) {
    .izin-page .content-wrapper {
        padding: 12px 10px !important;
    }
    
    .izin-page .content-wrapper h4 {
        font-size: 1rem;
        margin-bottom: 12px !important;
    }
    
    .izin-page .content-wrapper h4 i {
        font-size: 0.9rem;
    }
    
    /* Nav Pills Extra Small */
    .izin-page .nav-pills {
        gap: 4px;
        margin-bottom: 12px !important;
    }
    
    .izin-page .nav-pills .nav-link {
        padding: 8px 6px;
        font-size: 0.75rem;
        flex-direction: column;
        gap: 2px;
    }
    
    .izin-page .nav-pills .nav-link i {
        margin-right: 0 !important;
        font-size: 0.9rem;
    }
    
    .izin-page .nav-pills .nav-link .badge {
        font-size: 0.6rem;
        padding: 1px 4px;
    }
    
    /* Filter Card */
    .izin-page .card.mb-4 {
        margin-bottom: 12px !important;
    }
    
    .izin-page .card.mb-4 .card-body {
        padding: 8px !important;
    }
    
    .izin-page .card.mb-4 .form-select-sm {
        font-size: 0.8rem;
        padding: 5px 8px;
    }
    
    .izin-page .card.mb-4 .btn-sm {
        font-size: 0.75rem;
        padding: 5px 10px;
    }
    
    .izin-page .card.mb-4 .btn-sm i {
        margin-right: 2px !important;
    }
    
    /* Mobile Cards Extra Small */
    .izin-page .mobile-cards .card.mb-3 {
        margin-bottom: 10px !important;
        border-radius: 10px;
    }
    
    .izin-page .mobile-cards .card-header {
        padding: 10px 12px;
    }
    
    .izin-page .mobile-cards .card-header strong {
        font-size: 0.9rem;
    }
    
    .izin-page .mobile-cards .card-header small {
        font-size: 0.7rem;
    }
    
    .izin-page .mobile-cards .card-body {
        padding: 10px 12px;
    }
    
    .izin-page .mobile-cards .card-body .small {
        font-size: 0.75rem;
    }
    
    .izin-page .mobile-cards .card-body .badge {
        font-size: 0.65rem;
        padding: 3px 6px;
    }
    
    .izin-page .mobile-cards .btn-group-sm .btn {
        font-size: 0.7rem;
        padding: 5px 10px;
    }
    
    .izin-page .mobile-cards .btn-sm {
        font-size: 0.7rem;
        padding: 5px 10px;
    }
    
    /* Empty State Extra Small */
    .izin-page .text-center.py-5 {
        padding: 25px 12px !important;
    }
    
    .izin-page .text-center.py-5 i {
        font-size: 2rem !important;
        margin-bottom: 12px !important;
    }
    
    .izin-page .text-center.py-5 p {
        font-size: 0.85rem;
    }
    
    /* Modal Extra Small */
    .modal-dialog {
        margin: 8px;
    }
    
    .modal-header.custom-gradient,
    .modal-header.bg-danger {
        padding: 12px 14px;
        border-radius: 14px 14px 0 0 !important;
    }
    
    .modal-header .modal-title {
        font-size: 0.95rem;
    }
    
    .modal-header .modal-title i {
        font-size: 0.9rem;
    }
    
    .modal-header .btn-close {
        padding: 8px;
    }
    
    .modal-body {
        padding: 14px;
    }
    
    .modal-body .row.mb-2 .col-4 {
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .modal-body .row.mb-2 .col-8 {
        font-size: 0.8rem;
    }
    
    .modal-body .alert {
        padding: 8px 10px;
        font-size: 0.8rem;
        border-radius: 8px;
    }
    
    .modal-body .alert i {
        font-size: 0.85rem;
    }
    
    .modal-body .form-label {
        font-size: 0.85rem;
    }
    
    .modal-body textarea.form-control {
        font-size: 0.85rem;
        padding: 10px;
    }
    
    .modal-footer {
        padding: 10px 14px;
        flex-wrap: wrap;
        gap: 6px;
    }
    
    .modal-footer .btn {
        font-size: 0.8rem;
        padding: 7px 12px;
        flex: 1;
        min-width: 80px;
    }
}

/* Very Small Mobile (max-width: 400px) */
@media (max-width: 400px) {
    .izin-page .nav-pills .nav-link {
        padding: 6px 4px;
        font-size: 0.7rem;
    }
    
    .izin-page .nav-pills .nav-link i {
        font-size: 0.8rem;
    }
    
    .izin-page .mobile-cards .card-header strong {
        font-size: 0.85rem;
    }
    
    .izin-page .mobile-cards .btn-group-sm {
        flex-direction: column;
        width: 100%;
    }
    
    .izin-page .mobile-cards .btn-group-sm .btn {
        width: 100%;
        border-radius: 6px !important;
        margin-bottom: 4px;
    }
    
    .modal-footer .btn {
        font-size: 0.75rem;
        padding: 6px 10px;
    }
}

/* ==================== DARK MODE SUPPORT ==================== */
[data-theme="dark"] .izin-page .nav-pills .nav-link {
    color: #66b0ff;
}
[data-theme="dark"] .izin-page .nav-pills .nav-link.active {
    background-color: #3a8fd9;
    color: #fff;
}
[data-theme="dark"] .izin-page .nav-pills .nav-link:hover:not(.active) {
    background-color: rgba(255,255,255,0.1);
}

/* Mobile Cards Dark Mode */
[data-theme="dark"] .izin-page .mobile-cards .card-header.bg-light {
    background-color: rgba(255,255,255,0.1) !important;
    color: var(--text-main);
    border-bottom: 1px solid var(--border-color);
}
[data-theme="dark"] .izin-page .mobile-cards .card {
    background-color: var(--bg-card);
    border-color: var(--border-color) !important;
}

/* Modal & Alerts Dark Mode */
[data-theme="dark"] .alert-light {
    background-color: rgba(255,255,255,0.05);
    color: var(--text-main);
    border-color: var(--border-color);
}
[data-theme="dark"] .btn-outline-info {
    color: #33d6ff;
    border-color: #33d6ff;
}
[data-theme="dark"] .btn-outline-info:hover {
    background-color: #33d6ff;
    color: #212529;
}
[data-theme="dark"] .btn-outline-primary {
    color: #66b0ff;
    border-color: #66b0ff;
}
[data-theme="dark"] .btn-outline-primary:hover {
    background-color: #66b0ff;
    color: #212529;
}
</style>

<?php
$daftar_izin = mysqli_query($conn, "SELECT pi.*, 
                                     pi.materi_diulang as jenis_izin,
                                     m.nama as nama_mahasiswa, m.no_hp,
                                     j.tanggal, j.pertemuan_ke, j.materi, j.jam_mulai, j.jam_selesai,
                                     mk.nama_mk, k.nama_kelas,
                                     ji.tanggal as tanggal_inhall, ji.materi as materi_inhall,
                                     a.nama as nama_approver
                                     FROM penggantian_inhall pi
                                     JOIN mahasiswa m ON pi.nim = m.nim
                                     JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                     LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                     LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                     LEFT JOIN jadwal ji ON pi.jadwal_inhall_id = ji.id
                                     LEFT JOIN asisten a ON pi.approved_by = a.kode_asisten
                                     WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                     $where_approval
                                     $where_jenis
                                     ORDER BY pi.tanggal_daftar DESC");

// Count untuk badge
$count_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penggantian_inhall pi
                                                          JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                                          WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                                          AND pi.status_approval = 'pending'"))['total'];
$count_approved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penggantian_inhall pi
                                                          JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                                          WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                                          AND pi.status_approval = 'approved'"))['total'];
$count_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penggantian_inhall pi
                                                          JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                                          WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                                          AND pi.status_approval = 'rejected'"))['total'];

// Simpan data ke array
$data_izin = [];
while ($row = mysqli_fetch_assoc($daftar_izin)) {
    $data_izin[] = $row;
}
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="izin-page">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-file-alt me-2"></i>Persetujuan Izin & Sakit Mahasiswa</h4>
                
                <?= show_alert() ?>
                
                <!-- Tab Navigation -->
                <ul class="nav nav-pills mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'pending' ? 'active' : '' ?>" href="index.php?page=asisten_izin&tab=pending">
                            <i class="fas fa-clock me-1"></i>Menunggu 
                            <?php if ($count_pending > 0): ?>
                                <span class="badge bg-danger"><?= $count_pending ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'approved' ? 'active' : '' ?>" href="index.php?page=asisten_izin&tab=approved">
                            <i class="fas fa-check me-1"></i>Disetujui 
                            <span class="badge bg-secondary"><?= $count_approved ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $active_tab == 'rejected' ? 'active' : '' ?>" href="index.php?page=asisten_izin&tab=rejected">
                            <i class="fas fa-times me-1"></i>Ditolak 
                            <span class="badge bg-secondary"><?= $count_rejected ?></span>
                        </a>
                    </li>
                </ul>
                
                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="asisten_izin">
                            <input type="hidden" name="tab" value="<?= $active_tab ?>">
                            <div class="col-8 col-md-3">
                                <label class="form-label small mb-1">Filter Jenis</label>
                                <select name="jenis" class="form-select form-select-sm">
                                    <option value="">Semua (Izin & Sakit)</option>
                                    <option value="izin" <?= $filter_status == 'izin' ? 'selected' : '' ?>>Izin</option>
                                    <option value="sakit" <?= $filter_status == 'sakit' ? 'selected' : '' ?>>Sakit</option>
                                </select>
                            </div>
                            <div class="col-4 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-filter me-1"></i>Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (count($data_izin) > 0): ?>
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-lg-block">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal Pengajuan</th>
                                            <th>Mahasiswa</th>
                                            <th>Kelas</th>
                                            <th>Mata Kuliah</th>
                                            <th>Jenis</th>
                                            <?php if ($active_tab == 'pending'): ?>
                                                <th>Aksi</th>
                                            <?php else: ?>
                                                <th>Status</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data_izin as $idx => $r): ?>
                                            <tr>
                                                <td>
                                                    <?= date('d/m/Y H:i', strtotime($r['tanggal_daftar'])) ?>
                                                    <br><small class="text-muted">Jadwal: <?= format_tanggal($r['tanggal']) ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= $r['nama_mahasiswa'] ?></strong>
                                                    <br><small class="text-muted"><?= $r['nim'] ?></small>
                                                </td>
                                                <td><span class="badge bg-primary"><?= $r['nama_kelas'] ?></span></td>
                                                <td>
                                                    <?= $r['nama_mk'] ?>
                                                    <br><small class="text-muted">P<?= $r['pertemuan_ke'] ?> - <?= $r['materi'] ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $r['jenis_izin'] == 'izin' ? 'warning' : 'info' ?>">
                                                        <?= ucfirst($r['jenis_izin']) ?>
                                                    </span>
                                                </td>
                                                <?php if ($active_tab == 'pending'): ?>
                                                    <td>
                                                        <button class="btn btn-sm btn-info me-1" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $idx ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-success me-1" onclick="approveIzin(<?= $r['id'] ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalReject<?= $idx ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </td>
                                                <?php else: ?>
                                                    <td>
                                                        <?php if ($r['status_approval'] == 'approved'): ?>
                                                            <span class="badge bg-success">Disetujui</span>
                                                            <br><small class="text-muted">oleh <?= $r['nama_approver'] ?></small>
                                                        <?php elseif ($r['status_approval'] == 'rejected'): ?>
                                                            <span class="badge bg-danger">Ditolak</span>
                                                            <br><small class="text-muted"><?= $r['alasan_reject'] ?></small>
                                                        <?php endif; ?>
                                                        <br>
                                                        <button class="btn btn-sm btn-outline-info mt-1" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $idx ?>">
                                                            <i class="fas fa-eye"></i> Detail
                                                        </button>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-lg-none mobile-cards">
                                <?php foreach ($data_izin as $idx => $r): ?>
                                    <div class="card mb-3 border">
                                        <div class="card-header bg-light py-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong><?= $r['nama_mahasiswa'] ?></strong>
                                                <span class="badge bg-<?= $r['jenis_izin'] == 'izin' ? 'warning' : 'info' ?>">
                                                    <?= ucfirst($r['jenis_izin']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted"><?= $r['nim'] ?> - <?= $r['nama_kelas'] ?></small>
                                        </div>
                                        <div class="card-body py-3">
                                            <div class="row small mb-2">
                                                <div class="col-6">
                                                    <i class="fas fa-calendar me-1 text-muted"></i><?= format_tanggal($r['tanggal']) ?>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <span class="badge bg-secondary">P<?= $r['pertemuan_ke'] ?></span>
                                                </div>
                                            </div>
                                            <div class="small mb-2">
                                                <i class="fas fa-book me-1 text-muted"></i><?= $r['nama_mk'] ?>
                                            </div>
                                            <div class="small mb-2 text-muted">
                                                <i class="fas fa-comment me-1"></i><?= $r['alasan_izin'] ?: 'Tidak ada alasan' ?>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php if ($active_tab == 'pending'): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-success" onclick="approveIzin(<?= $r['id'] ?>)">
                                                            <i class="fas fa-check me-1"></i>Setujui
                                                        </button>
                                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalReject<?= $idx ?>">
                                                            <i class="fas fa-times me-1"></i>Tolak
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <div>
                                                        <?php if ($r['status_approval'] == 'approved'): ?>
                                                            <span class="badge bg-success">Disetujui</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Ditolak</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $idx ?>">
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">
                                    <?php if ($active_tab == 'pending'): ?>
                                        Tidak ada pengajuan yang menunggu persetujuan
                                    <?php elseif ($active_tab == 'approved'): ?>
                                        Belum ada pengajuan yang disetujui
                                    <?php else: ?>
                                        Belum ada pengajuan yang ditolak
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div><!-- end izin-page -->
        </div>
    </div>
</div>

<!-- Form Hidden untuk Approve -->
<form id="formApprove" method="POST" style="display:none;">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="pengajuan_id" id="approveId">
</form>

<!-- Modal Detail -->
<?php foreach ($data_izin as $idx => $r): ?>
<div class="modal fade" id="modalDetail<?= $idx ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header custom-gradient">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt me-2"></i>Detail Pengajuan <?= ucfirst($r['jenis_izin']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-4"><strong>Mahasiswa</strong></div>
                    <div class="col-8"><?= $r['nama_mahasiswa'] ?> (<?= $r['nim'] ?>)</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>Kelas</strong></div>
                    <div class="col-8"><?= $r['nama_kelas'] ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>No. HP</strong></div>
                    <div class="col-8"><?= $r['no_hp'] ?: '-' ?></div>
                </div>
                <hr>
                <div class="row mb-2">
                    <div class="col-4"><strong>Tanggal Jadwal</strong></div>
                    <div class="col-8"><?= format_tanggal($r['tanggal']) ?> (P<?= $r['pertemuan_ke'] ?>)</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>Mata Kuliah</strong></div>
                    <div class="col-8"><?= $r['nama_mk'] ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>Materi</strong></div>
                    <div class="col-8"><?= $r['materi'] ?></div>
                </div>
                <hr>
                <div class="row mb-2">
                    <div class="col-4"><strong>Jenis</strong></div>
                    <div class="col-8">
                        <span class="badge bg-<?= $r['jenis_izin'] == 'izin' ? 'warning' : 'info' ?>"><?= ucfirst($r['jenis_izin']) ?></span>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>Alasan</strong></div>
                    <div class="col-8"><?= $r['alasan_izin'] ?: '-' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>Bukti</strong></div>
                    <div class="col-8">
                        <?php if (!empty($r['bukti_file'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalBukti<?= $idx ?>">
                                <i class="fas fa-file-image me-1"></i>Lihat Bukti
                            </button>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Tidak ada bukti</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-4"><strong>Diajukan</strong></div>
                    <div class="col-8"><?= date('d/m/Y H:i', strtotime($r['tanggal_daftar'])) ?></div>
                </div>
                <?php if ($r['status_approval'] != 'pending'): ?>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Status</strong></div>
                        <div class="col-8">
                            <?php if ($r['status_approval'] == 'approved'): ?>
                                <span class="badge bg-success">Disetujui</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Ditolak</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Diproses oleh</strong></div>
                        <div class="col-8"><?= $r['nama_approver'] ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Waktu Proses</strong></div>
                        <div class="col-8"><?= $r['approved_at'] ? date('d/m/Y H:i', strtotime($r['approved_at'])) : '-' ?></div>
                    </div>
                    <?php if ($r['status_approval'] == 'rejected' && $r['alasan_reject']): ?>
                        <div class="row mb-2">
                            <div class="col-4"><strong>Alasan Tolak</strong></div>
                            <div class="col-8 text-danger"><?= $r['alasan_reject'] ?></div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <?php if ($active_tab == 'pending'): ?>
                    <button type="button" class="btn btn-success" onclick="approveIzin(<?= $r['id'] ?>)">
                        <i class="fas fa-check me-1"></i>Setujui
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalReject<?= $idx ?>">
                        <i class="fas fa-times me-1"></i>Tolak
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade" id="modalReject<?= $idx ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="pengajuan_id" value="<?= $r['id'] ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Tolak Pengajuan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan menolak pengajuan <strong><?= $r['jenis_izin'] ?></strong> dari:</p>
                    <div class="alert alert-light">
                        <strong><?= $r['nama_mahasiswa'] ?></strong> (<?= $r['nim'] ?>)<br>
                        <small><?= $r['nama_mk'] ?> - <?= format_tanggal($r['tanggal']) ?></small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Status presensi mahasiswa akan diubah menjadi <strong>ALPHA</strong>!
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="alasan_reject" class="form-control" rows="3" required placeholder="Contoh: Bukti tidak valid, surat dokter palsu, dll..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Tolak & Jadikan Alpha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bukti -->
<?php if (!empty($r['bukti_file'])): ?>
<div class="modal fade" id="modalBukti<?= $idx ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header custom-gradient">
                <h5 class="modal-title">
                    <i class="fas fa-image me-2"></i>Bukti <?= ucfirst($r['jenis_izin']) ?> - <?= $r['nama_mahasiswa'] ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img src="uploads/<?= $r['bukti_file'] ?>" alt="Bukti" class="img-fluid rounded" style="max-height: 70vh; object-fit: contain;">
            </div>
            <div class="modal-footer">
                <a href="uploads/<?= $r['bukti_file'] ?>" download class="btn btn-success">
                    <i class="fas fa-download me-1"></i>Download
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<script>
function approveIzin(id) {
    if (confirm('Setujui pengajuan izin ini?')) {
        document.getElementById('approveId').value = id;
        document.getElementById('formApprove').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
