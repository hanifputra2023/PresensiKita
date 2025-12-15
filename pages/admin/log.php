<?php
$page = 'admin_log';

// Pagination
$per_page = 20;
$current_page = get_current_page();

// Search
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$where_sql = '';
if ($search) {
    $where_sql = "WHERE l.aksi LIKE '%$search%' OR l.tabel LIKE '%$search%' OR l.detail LIKE '%$search%' OR u.username LIKE '%$search%'";
}

// Hitung total data
$count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM log_presensi l LEFT JOIN users u ON l.user_id = u.id $where_sql");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

$logs = mysqli_query($conn, "SELECT l.*, u.username FROM log_presensi l 
                              LEFT JOIN users u ON l.user_id = u.id 
                              $where_sql
                              ORDER BY l.created_at DESC LIMIT $offset, $per_page");
?>
<?php include 'includes/header.php'; ?>

<style>
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
    
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    .avatar-circle {
        width: 30px;
        height: 30px;
        background-color: var(--border-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        color: var(--text-main);
        font-weight: bold;
    }
    
    [data-theme="dark"] .log-timeline-item .card { background-color: var(--bg-card) !important; }
    [data-theme="dark"] .log-timeline-item .text-dark { color: var(--text-main) !important; }
    [data-theme="dark"] .table thead.bg-light { background-color: var(--bg-card) !important; }
    [data-theme="dark"] .table thead.bg-light th { background-color: var(--bg-card) !important; color: var(--text-main); }
    [data-theme="dark"] .text-dark { color: var(--text-main) !important; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_admin.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 pt-2">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Log Aktivitas</h4>
                    <div class="text-muted small">
                        Total Record: <strong><?= number_format($total_data) ?></strong>
                    </div>
                </div>
                
                <!-- Search Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="page" value="admin_log">
                            <div class="col-12 col-md">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari user, aksi, atau detail..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-primary w-100 px-4"><i class="fas fa-search me-2"></i>Cari</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3">Waktu</th>
                                        <th class="py-3">User</th>
                                        <th class="py-3">Aksi</th>
                                        <th class="py-3">Target</th>
                                        <th class="pe-4 py-3">Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($logs) > 0): ?>
                                        <?php while ($l = mysqli_fetch_assoc($logs)): 
                                            $aksi = strtoupper($l['aksi']);
                                            $badgeClass = 'bg-secondary';
                                            $icon = 'fa-info';
                                            
                                            if (strpos($aksi, 'INSERT') !== false || strpos($aksi, 'ADD') !== false || strpos($aksi, 'CREATE') !== false || strpos($aksi, 'GENERATE') !== false) {
                                                $badgeClass = 'bg-success';
                                                $icon = 'fa-plus';
                                            } elseif (strpos($aksi, 'UPDATE') !== false || strpos($aksi, 'EDIT') !== false || strpos($aksi, 'APPROVE') !== false) {
                                                $badgeClass = 'bg-primary';
                                                $icon = 'fa-pen';
                                            } elseif (strpos($aksi, 'DELETE') !== false || strpos($aksi, 'REMOVE') !== false) {
                                                $badgeClass = 'bg-danger';
                                                $icon = 'fa-trash';
                                            } elseif (strpos($aksi, 'LOGIN') !== false) {
                                                $badgeClass = 'bg-info';
                                                $icon = 'fa-sign-in-alt';
                                            }
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-nowrap">
                                                <div class="fw-bold text-dark"><?= date('H:i', strtotime($l['created_at'])) ?></div>
                                                <div class="small text-muted"><?= date('d M Y', strtotime($l['created_at'])) ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle">
                                                        <?= strtoupper(substr($l['username'] ?? 'S', 0, 1)) ?>
                                                    </div>
                                                    <span class="fw-medium"><?= htmlspecialchars($l['username'] ?? 'System') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badgeClass ?> rounded-pill">
                                                    <i class="fas <?= $icon ?> me-1"></i><?= $aksi ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($l['tabel']) ?></span>
                                                <?php if($l['id_record']): ?>
                                                    <small class="text-muted ms-1">#<?= $l['id_record'] ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-muted small">
                                                <?= htmlspecialchars($l['detail']) ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
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
                                    
                                    if (strpos($aksi, 'INSERT') !== false || strpos($aksi, 'ADD') !== false || strpos($aksi, 'CREATE') !== false || strpos($aksi, 'GENERATE') !== false) {
                                        $type = 'create';
                                        $icon = 'fa-plus';
                                        $color = 'success';
                                    } elseif (strpos($aksi, 'UPDATE') !== false || strpos($aksi, 'EDIT') !== false || strpos($aksi, 'APPROVE') !== false) {
                                        $type = 'update';
                                        $icon = 'fa-pen';
                                        $color = 'primary';
                                    } elseif (strpos($aksi, 'DELETE') !== false || strpos($aksi, 'REMOVE') !== false) {
                                        $type = 'delete';
                                        $icon = 'fa-trash';
                                        $color = 'danger';
                                    } else {
                                        $color = 'secondary';
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
                                                <span class="badge bg-<?= $color ?>"><?= $aksi ?></span>
                                                <small class="text-muted"><?= date('d M H:i', strtotime($l['created_at'])) ?></small>
                                            </div>
                                            <small class="fw-bold text-dark"><?= htmlspecialchars($l['username'] ?? 'System') ?></small>
                                        </div>
                                        <p class="mb-1 small text-dark"><?= htmlspecialchars($l['detail']) ?></p>
                                        <div class="small text-muted">
                                            <i class="fas fa-database me-1"></i><?= $l['tabel'] ?> 
                                            <?php if($l['id_record']): ?> #<?= $l['id_record'] ?><?php endif; ?>
                                        </div>
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
                </div>
                
                <!-- Pagination -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_log', ['search' => $search]) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
