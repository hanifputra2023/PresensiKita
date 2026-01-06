<?php
$page = 'asisten_pengajuan_izin';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

// Ambil daftar asisten lain untuk pengganti - prepared statement
$stmt_asisten_lain = mysqli_prepare($conn, "SELECT kode_asisten, nama FROM asisten 
                                      WHERE kode_asisten != ? AND status = 'aktif'");
mysqli_stmt_bind_param($stmt_asisten_lain, "s", $kode_asisten);
mysqli_stmt_execute($stmt_asisten_lain);
$asisten_lain = mysqli_stmt_get_result($stmt_asisten_lain);

// Proses form izin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_izin'])) {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $status = escape($_POST['status']);
    $pengganti = !empty($_POST['pengganti']) ? escape($_POST['pengganti']) : null;
    $catatan = escape($_POST['catatan']);
    
    // Validasi: Cek jadwal valid dan milik asisten ini
    $now = date('Y-m-d H:i:s');
    $stmt_jadwal_cek = mysqli_prepare($conn, "SELECT j.*, 
                                                           CONCAT(j.tanggal, ' ', j.jam_mulai) as jadwal_datetime
                                                           FROM jadwal j 
                                                           WHERE j.id = ? 
                                                           AND (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)");
    mysqli_stmt_bind_param($stmt_jadwal_cek, "iss", $jadwal_id, $kode_asisten, $kode_asisten);
    mysqli_stmt_execute($stmt_jadwal_cek);
    $jadwal_cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jadwal_cek));
    
    if (!$jadwal_cek) {
        set_alert('danger', 'Jadwal tidak ditemukan atau bukan jadwal Anda!');
        header("Location: index.php?page=asisten_pengajuan_izin");
        exit;
    }
    
    // Cek apakah jadwal sudah lewat (sudah mulai)
    if (strtotime($jadwal_cek['jadwal_datetime']) <= strtotime($now)) {
        set_alert('danger', 'Tidak bisa mengajukan izin untuk jadwal yang sudah dimulai!');
        header("Location: index.php?page=asisten_pengajuan_izin");
        exit;
    }
    
    // Cek apakah sudah hadir
    $stmt_existing = mysqli_prepare($conn, "SELECT id, status, status_approval FROM absen_asisten 
                                                         WHERE jadwal_id = ? AND kode_asisten = ?");
    mysqli_stmt_bind_param($stmt_existing, "is", $jadwal_id, $kode_asisten);
    mysqli_stmt_execute($stmt_existing);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_existing));
    
    if ($existing && $existing['status'] == 'hadir') {
        set_alert('danger', 'Anda sudah tercatat hadir di jadwal ini!');
        header("Location: index.php?page=asisten_pengajuan_izin");
        exit;
    }
    
    // VALIDASI BARU: Cek apakah asisten pengganti sudah punya jadwal di waktu yang sama
    if ($pengganti) {
        $stmt_konflik = mysqli_prepare($conn, "SELECT j.id, j.tanggal, j.jam_mulai, j.jam_selesai, 
                                                           mk.nama_mk, k.nama_kelas
                                                    FROM jadwal j
                                                    LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                    LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                    WHERE j.tanggal = ?
                                                    AND (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)
                                                    AND (
                                                        (j.jam_mulai < ? AND j.jam_selesai > ?) OR
                                                        (j.jam_mulai >= ? AND j.jam_mulai < ?) OR
                                                        (j.jam_selesai > ? AND j.jam_selesai <= ?)
                                                    )");
        mysqli_stmt_bind_param($stmt_konflik, "sssssssss", 
            $jadwal_cek['tanggal'], $pengganti, $pengganti,
            $jadwal_cek['jam_selesai'], $jadwal_cek['jam_mulai'],
            $jadwal_cek['jam_mulai'], $jadwal_cek['jam_selesai'],
            $jadwal_cek['jam_mulai'], $jadwal_cek['jam_selesai']
        );
        mysqli_stmt_execute($stmt_konflik);
        $result_konflik = mysqli_stmt_get_result($stmt_konflik);
        
        if (mysqli_num_rows($result_konflik) > 0) {
            $konflik = mysqli_fetch_assoc($result_konflik);
            $nama_pengganti_temp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM asisten WHERE kode_asisten = '$pengganti'"))['nama'];
            set_alert('danger', "Asisten pengganti ($nama_pengganti_temp) sudah memiliki jadwal lain pada waktu yang sama: {$konflik['nama_mk']} - {$konflik['nama_kelas']} ({$konflik['jam_mulai']} - {$konflik['jam_selesai']})");
            header("Location: index.php?page=asisten_pengajuan_izin");
            exit;
        }
        
        // Cek juga apakah pengganti sudah jadi pengganti asisten lain di waktu yang sama
        $stmt_konflik_pengganti = mysqli_prepare($conn, "SELECT aa.*, j.tanggal, j.jam_mulai, j.jam_selesai,
                                                                  mk.nama_mk, k.nama_kelas, a.nama as nama_asisten_izin
                                                           FROM absen_asisten aa
                                                           JOIN jadwal j ON aa.jadwal_id = j.id
                                                           LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                           LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                           LEFT JOIN asisten a ON aa.kode_asisten = a.kode_asisten
                                                           WHERE aa.pengganti = ?
                                                           AND aa.status IN ('izin', 'sakit')
                                                           AND aa.status_approval != 'rejected'
                                                           AND j.tanggal = ?
                                                           AND (
                                                               (j.jam_mulai < ? AND j.jam_selesai > ?) OR
                                                               (j.jam_mulai >= ? AND j.jam_mulai < ?) OR
                                                               (j.jam_selesai > ? AND j.jam_selesai <= ?)
                                                           )");
        mysqli_stmt_bind_param($stmt_konflik_pengganti, "ssssssss", 
            $pengganti, $jadwal_cek['tanggal'],
            $jadwal_cek['jam_selesai'], $jadwal_cek['jam_mulai'],
            $jadwal_cek['jam_mulai'], $jadwal_cek['jam_selesai'],
            $jadwal_cek['jam_mulai'], $jadwal_cek['jam_selesai']
        );
        mysqli_stmt_execute($stmt_konflik_pengganti);
        $result_konflik_pengganti = mysqli_stmt_get_result($stmt_konflik_pengganti);
        
        if (mysqli_num_rows($result_konflik_pengganti) > 0) {
            $konflik_pg = mysqli_fetch_assoc($result_konflik_pengganti);
            $nama_pengganti_temp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama FROM asisten WHERE kode_asisten = '$pengganti'"))['nama'];
            set_alert('danger', "Asisten pengganti ($nama_pengganti_temp) sudah menjadi pengganti untuk {$konflik_pg['nama_asisten_izin']} di jadwal {$konflik_pg['nama_mk']} - {$konflik_pg['nama_kelas']} ({$konflik_pg['jam_mulai']} - {$konflik_pg['jam_selesai']})");
            header("Location: index.php?page=asisten_pengajuan_izin");
            exit;
        }
    }
    
    // Status approval default pending - perlu persetujuan admin
    $status_approval = 'pending';
    
    if ($existing) {
        // Update izin yang sudah ada
        $stmt_upd = mysqli_prepare($conn, "UPDATE absen_asisten SET status = ?, pengganti = ?, catatan = ?, status_approval = 'pending', approved_by = NULL, approved_at = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt_upd, "sssi", $status, $pengganti, $catatan, $existing['id']);
        $success = mysqli_stmt_execute($stmt_upd);
    } else {
        // Insert baru dengan status_approval = pending
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO absen_asisten (jadwal_id, kode_asisten, status, pengganti, catatan, status_approval) 
                VALUES (?, ?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt_ins, "issss", $jadwal_id, $kode_asisten, $status, $pengganti, $catatan);
        $success = mysqli_stmt_execute($stmt_ins);
    }
    
    if ($success) {
        log_aktivitas($_SESSION['user_id'], 'IZIN_ASISTEN', 'absen_asisten', $jadwal_id, "Asisten $kode_asisten mengajukan $status (menunggu approval admin)");
        set_alert('success', 'Pengajuan izin berhasil dikirim! Menunggu persetujuan dari admin.');
    } else {
        set_alert('danger', 'Gagal menyimpan: ' . mysqli_error($conn));
    }
    
    header("Location: index.php?page=asisten_pengajuan_izin");
    exit;
}

// Batalkan izin
if (isset($_GET['batal'])) {
    $id = (int)$_GET['batal'];
    
    // Cek izin valid dan jadwal belum lewat
    $now = date('Y-m-d H:i:s');
    $stmt_izin_cek = mysqli_prepare($conn, "SELECT aa.*, 
                                                         CONCAT(j.tanggal, ' ', j.jam_mulai) as jadwal_datetime
                                                         FROM absen_asisten aa
                                                         JOIN jadwal j ON aa.jadwal_id = j.id
                                                         WHERE aa.id = ? AND aa.kode_asisten = ?");
    mysqli_stmt_bind_param($stmt_izin_cek, "is", $id, $kode_asisten);
    mysqli_stmt_execute($stmt_izin_cek);
    $izin_cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_izin_cek));
    
    if (!$izin_cek) {
        set_alert('danger', 'Data izin tidak ditemukan!');
    } elseif ($izin_cek['status'] == 'hadir') {
        set_alert('danger', 'Tidak bisa membatalkan karena Anda sudah tercatat hadir!');
    } elseif ($izin_cek['status_approval'] == 'approved') {
        set_alert('danger', 'Tidak bisa membatalkan izin yang sudah disetujui admin!');
    } elseif (strtotime($izin_cek['jadwal_datetime']) <= strtotime($now)) {
        set_alert('danger', 'Tidak bisa membatalkan izin untuk jadwal yang sudah lewat!');
    } else {
        $stmt_del = mysqli_prepare($conn, "DELETE FROM absen_asisten WHERE id = ? AND kode_asisten = ?");
        mysqli_stmt_bind_param($stmt_del, "is", $id, $kode_asisten);
        mysqli_stmt_execute($stmt_del);
        log_aktivitas($_SESSION['user_id'], 'BATAL_IZIN_ASISTEN', 'absen_asisten', $id, "Asisten $kode_asisten membatalkan pengajuan izin");
        set_alert('info', 'Pengajuan izin dibatalkan.');
    }
    
    header("Location: index.php?page=asisten_pengajuan_izin");
    exit;
}

// Jadwal mendatang (yang bisa diajukan izin)
$today = date('Y-m-d');
$now_time = date('H:i:s');

// Filter dan Pencarian
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$search = isset($_GET['search']) ? escape($_GET['search']) : '';

// Build prepared statement dynamically
$bind_types = "ss"; // for today and now_time (used in subquery), also for kode_asisten used twice
$bind_values = [];
$where_base = "WHERE (j.tanggal > ? OR (j.tanggal = ? AND j.jam_selesai >= ?))
               AND (j.kode_asisten_1 = ? OR j.kode_asisten_2 = ?)";
$bind_types = "sssss";
$bind_values = [$today, $today, $now_time, $kode_asisten, $kode_asisten];

$additional_where = "";
if ($filter_kelas) {
    $additional_where .= " AND j.kode_kelas = ?";
    $bind_types .= "s";
    $bind_values[] = $filter_kelas;
}
if ($search) {
    $additional_where .= " AND (mk.nama_mk LIKE ? OR j.materi LIKE ?)";
    $bind_types .= "ss";
    $search_param = '%' . $search . '%';
    $bind_values[] = $search_param;
    $bind_values[] = $search_param;
}

$stmt_jadwal_mend = mysqli_prepare($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk,
                                          aa.id as izin_id, aa.status as izin_status, aa.pengganti, aa.catatan, aa.jam_masuk,
                                          aa.status_approval, aa.alasan_reject,
                                          ap.nama as nama_pengganti,
                                          CASE 
                                            WHEN j.tanggal = CURDATE() AND j.jam_mulai <= CURTIME() THEN 1 
                                            ELSE 0 
                                          END as sudah_mulai
                                          FROM jadwal j 
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                          LEFT JOIN absen_asisten aa ON aa.jadwal_id = j.id AND aa.kode_asisten = ?
                                          LEFT JOIN asisten ap ON aa.pengganti = ap.kode_asisten
                                          $where_base $additional_where
                                          ORDER BY j.tanggal, j.jam_mulai");
$full_bind_types = "s" . $bind_types; // add type for kode_asisten in LEFT JOIN
$full_bind_values = array_merge([$kode_asisten], $bind_values);
mysqli_stmt_bind_param($stmt_jadwal_mend, $full_bind_types, ...$full_bind_values);
mysqli_stmt_execute($stmt_jadwal_mend);
$jadwal_mendatang = mysqli_stmt_get_result($stmt_jadwal_mend);

$stmt_kelas_list = mysqli_prepare($conn, "SELECT DISTINCT k.kode_kelas, k.nama_kelas FROM jadwal j JOIN kelas k ON j.kode_kelas = k.kode_kelas WHERE j.kode_asisten_1 = ? OR j.kode_asisten_2 = ? ORDER BY k.nama_kelas");
mysqli_stmt_bind_param($stmt_kelas_list, "ss", $kode_asisten, $kode_asisten);
mysqli_stmt_execute($stmt_kelas_list);
$kelas_list = mysqli_stmt_get_result($stmt_kelas_list);
// Riwayat izin (hanya yang izin/sakit) - prepared statement
$stmt_riwayat = mysqli_prepare($conn, "SELECT aa.*, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi,
                                      k.nama_kelas, mk.nama_mk, ap.nama as nama_pengganti,
                                      u.username as approved_by_name
                                      FROM absen_asisten aa
                                      JOIN jadwal j ON aa.jadwal_id = j.id
                                      LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                      LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                      LEFT JOIN asisten ap ON aa.pengganti = ap.kode_asisten
                                      LEFT JOIN users u ON aa.approved_by = u.id
                                      WHERE aa.kode_asisten = ?
                                      AND aa.status IN ('izin', 'sakit')
                                      ORDER BY j.tanggal DESC
                                      LIMIT 10");
mysqli_stmt_bind_param($stmt_riwayat, "s", $kode_asisten);
mysqli_stmt_execute($stmt_riwayat);
$riwayat_izin = mysqli_stmt_get_result($stmt_riwayat);

// Handle AJAX Search
if (isset($_GET['ajax_search'])) {
    ?>
    <!-- Desktop Table -->
    <div class="table-responsive d-none d-lg-block">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Kelas</th>
                    <th>Materi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (mysqli_num_rows($jadwal_mendatang) > 0):
                    mysqli_data_seek($jadwal_mendatang, 0);
                    while ($j = mysqli_fetch_assoc($jadwal_mendatang)): ?>
                        <tr>
                            <td>
                                <?= format_tanggal($j['tanggal']) ?>
                                <?php if ($j['tanggal'] == $today): ?>
                                    <span class="badge bg-warning text-dark">Hari Ini</span>
                                <?php endif; ?>
                            </td>
                            <td><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></td>
                            <td><?= $j['nama_kelas'] ?></td>
                            <td>
                                <strong><?= $j['nama_mk'] ?></strong><br>
                                <small class="text-muted"><?= $j['materi'] ?> - <?= $j['nama_lab'] ?></small>
                            </td>
                            <td>
                                <?php if ($j['izin_status'] == 'hadir'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Hadir</span>
                                    <?php if ($j['jam_masuk']): ?>
                                        <br><small class="text-muted">Masuk: <?= format_waktu($j['jam_masuk']) ?></small>
                                    <?php endif; ?>
                                <?php elseif ($j['izin_status'] == 'izin' || $j['izin_status'] == 'sakit'): ?>
                                    <span class="badge bg-<?= $j['izin_status'] == 'izin' ? 'warning' : 'info' ?>">
                                        <?= ucfirst($j['izin_status']) ?>
                                    </span>
                                    <?php if ($j['status_approval'] == 'pending'): ?>
                                        <br><span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Menunggu Approval</span>
                                    <?php elseif ($j['status_approval'] == 'approved'): ?>
                                        <br><span class="badge bg-success"><i class="fas fa-check me-1"></i>Disetujui</span>
                                    <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                        <br><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Ditolak</span>
                                        <?php if ($j['alasan_reject']): ?>
                                            <br><small class="text-danger">Alasan: <?= $j['alasan_reject'] ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($j['nama_pengganti']): ?>
                                        <br><small class="text-muted">Pengganti: <?= $j['nama_pengganti'] ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum Hadir</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($j['izin_status'] == 'hadir'): ?>
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Sudah hadir</span>
                                <?php elseif ($j['sudah_mulai']): ?>
                                    <span class="text-muted"><i class="fas fa-clock"></i> Sedang berlangsung</span>
                                <?php elseif ($j['izin_status'] && $j['izin_status'] != 'hadir'): ?>
                                    <?php if ($j['status_approval'] == 'pending'): ?>
                                        <a href="index.php?page=asisten_pengajuan_izin&batal=<?= $j['izin_id'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Batalkan pengajuan izin?')">
                                            <i class="fas fa-times"></i> Batal
                                        </a>
                                    <?php elseif ($j['status_approval'] == 'approved'): ?>
                                        <span class="text-success small"><i class="fas fa-check-circle"></i> Disetujui</span>
                                    <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                data-bs-target="#modalIzin<?= $j['id'] ?>">
                                            <i class="fas fa-redo"></i> Ajukan Ulang
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                            data-bs-target="#modalIzin<?= $j['id'] ?>">
                                        <i class="fas fa-paper-plane"></i> Ajukan Izin
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada jadwal yang cocok dengan filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Mobile Cards -->
    <div class="d-lg-none">
        <?php 
        if (mysqli_num_rows($jadwal_mendatang) > 0):
            mysqli_data_seek($jadwal_mendatang, 0);
            while ($j = mysqli_fetch_assoc($jadwal_mendatang)): ?>
                <div class="card mb-2 <?= $j['tanggal'] == $today ? 'border-warning' : '' ?>">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong class="small"><?= $j['nama_mk'] ?></strong>
                                <br><small class="text-muted"><?= $j['nama_kelas'] ?> - <?= $j['nama_lab'] ?></small>
                            </div>
                            <div class="text-end">
                                <?php if ($j['izin_status'] == 'hadir'): ?>
                                    <span class="badge bg-success">Hadir</span>
                                <?php elseif ($j['izin_status'] == 'izin' || $j['izin_status'] == 'sakit'): ?>
                                    <span class="badge bg-<?= $j['izin_status'] == 'izin' ? 'warning' : 'info' ?>">
                                        <?= ucfirst($j['izin_status']) ?>
                                    </span>
                                    <?php if ($j['status_approval'] == 'pending'): ?>
                                        <br><span class="badge bg-secondary small mt-1">Pending</span>
                                    <?php elseif ($j['status_approval'] == 'approved'): ?>
                                        <br><span class="badge bg-success small mt-1">Disetujui</span>
                                    <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                        <br><span class="badge bg-danger small mt-1">Ditolak</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center small mb-2">
                            <span>
                                <i class="fas fa-calendar me-1 text-muted"></i><?= format_tanggal($j['tanggal']) ?>
                                <?php if ($j['tanggal'] == $today): ?>
                                    <span class="badge bg-warning text-dark">Hari Ini</span>
                                <?php endif; ?>
                            </span>
                            <span><i class="fas fa-clock me-1 text-muted"></i><?= format_waktu($j['jam_mulai']) ?></span>
                        </div>
                        <div class="text-end">
                            <?php if ($j['izin_status'] == 'hadir'): ?>
                                <span class="text-success small"><i class="fas fa-check-circle"></i> Sudah hadir</span>
                            <?php elseif ($j['sudah_mulai']): ?>
                                <span class="text-muted small"><i class="fas fa-clock"></i> Sedang berlangsung</span>
                            <?php elseif ($j['izin_status'] && $j['izin_status'] != 'hadir'): ?>
                                <?php if ($j['status_approval'] == 'pending'): ?>
                                    <a href="index.php?page=asisten_pengajuan_izin&batal=<?= $j['izin_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan pengajuan izin?')"><i class="fas fa-times"></i> Batal</a>
                                <?php elseif ($j['status_approval'] == 'approved'): ?>
                                    <span class="text-success small"><i class="fas fa-check-circle"></i> Disetujui</span>
                                <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                    <button class="btn btn-sm btn-warning w-100" data-bs-toggle="modal" data-bs-target="#modalIzinMobile<?= $j['id'] ?>"><i class="fas fa-redo"></i> Ajukan Ulang</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-sm btn-warning w-100" data-bs-toggle="modal" data-bs-target="#modalIzinMobile<?= $j['id'] ?>"><i class="fas fa-paper-plane"></i> Ajukan Izin</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center text-muted py-4">Tidak ada jadwal yang cocok dengan filter.</div>
        <?php endif; ?>
    </div>
    <?php
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* Dark Mode Fixes */
[data-theme="dark"] .bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}
[data-theme="dark"] .modal-header.bg-warning .btn-close {
    filter: none;
}
[data-theme="dark"] .modal-header.bg-warning .modal-title {
    color: #212529 !important;
}
[data-theme="dark"] .card-header.bg-primary {
    background-color: rgba(0, 102, 204, 0.2) !important;
    color: #66b0ff !important;
    border-bottom: 1px solid rgba(0, 102, 204, 0.3);
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-user-clock me-2"></i>Pengajuan Izin/Sakit Asisten</h4>
                
                <?= show_alert() ?>
                
                <!-- Filter -->
                <div class="card mb-4">
                    <div class="card-body p-2 p-md-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="page" value="asisten_pengajuan_izin">
                            <div class="col-12 col-md-6">
                                <label class="form-label small">Cari Materi / MK</label>
                                <input type="text" name="search" id="searchInput" class="form-control form-control-sm" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan kata kunci...">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small">Filter Kelas</label>
                                <select name="kelas" id="kelasSelect" class="form-select form-select-sm">
                                    <option value="">Semua Kelas</option>
                                    <?php mysqli_data_seek($kelas_list, 0); while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                        <option value="<?= $k['kode_kelas'] ?>" <?= $filter_kelas == $k['kode_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Jadwal Mendatang -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-calendar-alt me-2"></i>Jadwal Mendatang
                    </div>
                    <div class="card-body p-2 p-md-3" id="jadwalContainer">
                        <?php if (mysqli_num_rows($jadwal_mendatang) > 0): ?>
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-lg-block">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Waktu</th>
                                            <th>Kelas</th>
                                            <th>Materi</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($jadwal_mendatang, 0);
                                        while ($j = mysqli_fetch_assoc($jadwal_mendatang)): ?>
                                            <tr>
                                                <td>
                                                    <?= format_tanggal($j['tanggal']) ?>
                                                    <?php if ($j['tanggal'] == $today): ?>
                                                        <span class="badge bg-warning text-dark">Hari Ini</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></td>
                                                <td><?= $j['nama_kelas'] ?></td>
                                                <td>
                                                    <strong><?= $j['nama_mk'] ?></strong><br>
                                                    <small class="text-muted"><?= $j['materi'] ?> - <?= $j['nama_lab'] ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($j['izin_status'] == 'hadir'): ?>
                                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Hadir</span>
                                                        <?php if ($j['jam_masuk']): ?>
                                                            <br><small class="text-muted">Masuk: <?= format_waktu($j['jam_masuk']) ?></small>
                                                        <?php endif; ?>
                                                    <?php elseif ($j['izin_status'] == 'izin' || $j['izin_status'] == 'sakit'): ?>
                                                        <span class="badge bg-<?= $j['izin_status'] == 'izin' ? 'warning' : 'info' ?>">
                                                            <?= ucfirst($j['izin_status']) ?>
                                                        </span>
                                                        <?php if ($j['status_approval'] == 'pending'): ?>
                                                            <br><span class="badge bg-secondary"><i class="fas fa-clock me-1"></i>Menunggu Approval</span>
                                                        <?php elseif ($j['status_approval'] == 'approved'): ?>
                                                            <br><span class="badge bg-success"><i class="fas fa-check me-1"></i>Disetujui</span>
                                                        <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                                            <br><span class="badge bg-danger"><i class="fas fa-times me-1"></i>Ditolak</span>
                                                            <?php if ($j['alasan_reject']): ?>
                                                                <br><small class="text-danger">Alasan: <?= $j['alasan_reject'] ?></small>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <?php if ($j['nama_pengganti']): ?>
                                                            <br><small class="text-muted">Pengganti: <?= $j['nama_pengganti'] ?></small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Belum Hadir</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($j['izin_status'] == 'hadir'): ?>
                                                        <span class="text-success"><i class="fas fa-check-circle"></i> Sudah hadir</span>
                                                    <?php elseif ($j['sudah_mulai']): ?>
                                                        <span class="text-muted"><i class="fas fa-clock"></i> Sedang berlangsung</span>
                                                    <?php elseif ($j['izin_status'] && $j['izin_status'] != 'hadir'): ?>
                                                        <?php if ($j['status_approval'] == 'pending'): ?>
                                                            <a href="index.php?page=asisten_pengajuan_izin&batal=<?= $j['izin_id'] ?>" 
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('Batalkan pengajuan izin?')">
                                                                <i class="fas fa-times"></i> Batal
                                                            </a>
                                                        <?php elseif ($j['status_approval'] == 'approved'): ?>
                                                            <span class="text-success"><i class="fas fa-check-circle"></i> Disetujui</span>
                                                        <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                                    data-bs-target="#modalIzin<?= $j['id'] ?>">
                                                                <i class="fas fa-redo"></i> Ajukan Ulang
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                                data-bs-target="#modalIzin<?= $j['id'] ?>">
                                                            <i class="fas fa-paper-plane"></i> Ajukan Izin
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-lg-none">
                                <?php 
                                mysqli_data_seek($jadwal_mendatang, 0);
                                while ($j = mysqli_fetch_assoc($jadwal_mendatang)): ?>
                                    <div class="card mb-2 <?= $j['tanggal'] == $today ? 'border-warning' : '' ?>">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <strong class="small"><?= $j['nama_mk'] ?></strong>
                                                    <br><small class="text-muted"><?= $j['nama_kelas'] ?> - <?= $j['nama_lab'] ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <?php if ($j['izin_status'] == 'hadir'): ?>
                                                        <span class="badge bg-success">Hadir</span>
                                                    <?php elseif ($j['izin_status'] == 'izin' || $j['izin_status'] == 'sakit'): ?>
                                                        <span class="badge bg-<?= $j['izin_status'] == 'izin' ? 'warning' : 'info' ?>">
                                                            <?= ucfirst($j['izin_status']) ?>
                                                        </span>
                                                        <?php if ($j['status_approval'] == 'pending'): ?>
                                                            <br><span class="badge bg-secondary small mt-1">Pending</span>
                                                        <?php elseif ($j['status_approval'] == 'approved'): ?>
                                                            <br><span class="badge bg-success small mt-1">Disetujui</span>
                                                        <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                                            <br><span class="badge bg-danger small mt-1">Ditolak</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Belum</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center small mb-2">
                                                <span>
                                                    <i class="fas fa-calendar me-1 text-muted"></i><?= format_tanggal($j['tanggal']) ?>
                                                    <?php if ($j['tanggal'] == $today): ?>
                                                        <span class="badge bg-warning text-dark">Hari Ini</span>
                                                    <?php endif; ?>
                                                </span>
                                                <span><i class="fas fa-clock me-1 text-muted"></i><?= format_waktu($j['jam_mulai']) ?></span>
                                            </div>
                                            <div class="text-end">
                                                <?php if ($j['izin_status'] == 'hadir'): ?>
                                                    <span class="text-success small"><i class="fas fa-check-circle"></i> Sudah hadir</span>
                                                <?php elseif ($j['sudah_mulai']): ?>
                                                    <span class="text-muted small"><i class="fas fa-clock"></i> Sedang berlangsung</span>
                                                <?php elseif ($j['izin_status'] && $j['izin_status'] != 'hadir'): ?>
                                                    <?php if ($j['status_approval'] == 'pending'): ?>
                                                        <a href="index.php?page=asisten_pengajuan_izin&batal=<?= $j['izin_id'] ?>" 
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Batalkan pengajuan izin?')">
                                                            <i class="fas fa-times"></i> Batal
                                                        </a>
                                                    <?php elseif ($j['status_approval'] == 'approved'): ?>
                                                        <span class="text-success small"><i class="fas fa-check-circle"></i> Disetujui</span>
                                                    <?php elseif ($j['status_approval'] == 'rejected'): ?>
                                                        <button class="btn btn-sm btn-warning w-100" data-bs-toggle="modal" 
                                                                data-bs-target="#modalIzinMobile<?= $j['id'] ?>">
                                                            <i class="fas fa-redo"></i> Ajukan Ulang
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-warning w-100" data-bs-toggle="modal" 
                                                            data-bs-target="#modalIzinMobile<?= $j['id'] ?>">
                                                        <i class="fas fa-paper-plane"></i> Ajukan Izin
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Izin Mobile -->
                                    <div class="modal fade" id="modalIzinMobile<?= $j['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header bg-warning">
                                                        <h6 class="modal-title">
                                                            <i class="fas fa-user-clock me-2"></i>Ajukan Izin/Sakit
                                                        </h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="jadwal_id" value="<?= $j['id'] ?>">
                                                        
                                                        <div class="alert alert-info small py-2">
                                                            <strong><?= format_tanggal($j['tanggal']) ?></strong><br>
                                                            <?= $j['nama_mk'] ?> - <?= $j['nama_kelas'] ?>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label small">Status</label>
                                                            <select name="status" class="form-select form-select-sm" required>
                                                                <option value="izin">Izin</option>
                                                                <option value="sakit">Sakit</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label small">Asisten Pengganti</label>
                                                            <select name="pengganti" class="form-select form-select-sm">
                                                                <option value="">-- Tidak ada --</option>
                                                                <?php 
                                                                mysqli_data_seek($asisten_lain, 0);
                                                                while ($a = mysqli_fetch_assoc($asisten_lain)): 
                                                                ?>
                                                                    <option value="<?= $a['kode_asisten'] ?>"><?= $a['nama'] ?></option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label small">Alasan</label>
                                                            <textarea name="catatan" class="form-control form-control-sm" rows="2"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" name="ajukan_izin" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-paper-plane me-1"></i>Ajukan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Desktop Modal - Keep existing -->
                            <?php 
                            mysqli_data_seek($jadwal_mendatang, 0);
                            while ($j = mysqli_fetch_assoc($jadwal_mendatang)): ?>
                            <!-- Modal Izin -->
                            <div class="modal fade" id="modalIzin<?= $j['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-user-clock me-2"></i>Ajukan Izin/Sakit
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="jadwal_id" value="<?= $j['id'] ?>">
                                                
                                                <div class="alert alert-info">
                                                    <strong><?= format_tanggal($j['tanggal']) ?></strong><br>
                                                    <?= $j['nama_mk'] ?> - <?= $j['nama_kelas'] ?><br>
                                                    <?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?><br>
                                                    <small><?= $j['nama_lab'] ?></small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="izin">Izin</option>
                                                        <option value="sakit">Sakit</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Asisten Pengganti</label>
                                                    <select name="pengganti" class="form-select">
                                                        <option value="">-- Tidak ada pengganti --</option>
                                                        <?php 
                                                        mysqli_data_seek($asisten_lain, 0);
                                                        while ($a = mysqli_fetch_assoc($asisten_lain)): 
                                                        ?>
                                                            <option value="<?= $a['kode_asisten'] ?>"><?= $a['nama'] ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    <small class="text-muted">Pilih asisten lain jika ada yang bersedia menggantikan</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Alasan/Keterangan</label>
                                                    <textarea name="catatan" class="form-control" rows="3" 
                                                              placeholder="Jelaskan alasan izin/sakit..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="ajukan_izin" class="btn btn-warning">
                                                    <i class="fas fa-paper-plane me-1"></i>Ajukan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                                <p>Tidak ada jadwal mendatang</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Riwayat Izin -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history me-2"></i>Riwayat Pengajuan Izin
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <?php if (mysqli_num_rows($riwayat_izin) > 0): ?>
                            <!-- Desktop Table -->
                            <div class="table-responsive d-none d-lg-block">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jadwal</th>
                                            <th>Status</th>
                                            <th>Approval</th>
                                            <th>Pengganti</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        mysqli_data_seek($riwayat_izin, 0);
                                        while ($r = mysqli_fetch_assoc($riwayat_izin)): ?>
                                            <tr>
                                                <td><?= format_tanggal($r['tanggal']) ?></td>
                                                <td>
                                                    <?= $r['nama_mk'] ?> - <?= $r['nama_kelas'] ?><br>
                                                    <small class="text-muted"><?= $r['materi'] ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($r['status'] == 'izin'): ?>
                                                        <span class="badge bg-warning">Izin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">Sakit</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($r['status_approval'] == 'pending'): ?>
                                                        <span class="badge bg-secondary">Menunggu</span>
                                                    <?php elseif ($r['status_approval'] == 'approved'): ?>
                                                        <span class="badge bg-success">Disetujui</span>
                                                        <?php if ($r['approved_by_name']): ?>
                                                            <br><small class="text-muted">oleh <?= $r['approved_by_name'] ?></small>
                                                        <?php endif; ?>
                                                    <?php elseif ($r['status_approval'] == 'rejected'): ?>
                                                        <span class="badge bg-danger">Ditolak</span>
                                                        <?php if ($r['alasan_reject']): ?>
                                                            <br><small class="text-danger"><?= $r['alasan_reject'] ?></small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $r['nama_pengganti'] ?: '-' ?></td>
                                                <td><?= $r['catatan'] ?: '-' ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Mobile Cards -->
                            <div class="d-lg-none">
                                <?php 
                                mysqli_data_seek($riwayat_izin, 0);
                                while ($r = mysqli_fetch_assoc($riwayat_izin)): ?>
                                    <div class="card mb-2">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <strong class="small"><?= $r['nama_mk'] ?></strong>
                                                    <br><small class="text-muted"><?= $r['nama_kelas'] ?></small>
                                                </div>
                                                <div class="text-end">
                                                    <?php if ($r['status'] == 'izin'): ?>
                                                        <span class="badge bg-warning">Izin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">Sakit</span>
                                                    <?php endif; ?>
                                                    <?php if ($r['status_approval'] == 'pending'): ?>
                                                        <br><span class="badge bg-secondary small">Menunggu</span>
                                                    <?php elseif ($r['status_approval'] == 'approved'): ?>
                                                        <br><span class="badge bg-success small">Disetujui</span>
                                                    <?php elseif ($r['status_approval'] == 'rejected'): ?>
                                                        <br><span class="badge bg-danger small">Ditolak</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i><?= format_tanggal($r['tanggal']) ?>
                                                <?php if ($r['nama_pengganti']): ?>
                                                    | Pengganti: <?= $r['nama_pengganti'] ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <p class="mb-0">Belum ada riwayat izin</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

 <script>
 // Live Search
 let searchTimeout = null;
 const searchInput = document.getElementById('searchInput');
 const kelasSelect = document.getElementById('kelasSelect');
 
 function performSearch() {
     clearTimeout(searchTimeout);
     const searchValue = searchInput.value;
     const kelasValue = kelasSelect.value;
     const container = document.getElementById('jadwalContainer');
     
     searchTimeout = setTimeout(function() {
         fetch(`index.php?page=asisten_pengajuan_izin&ajax_search=1&search=${encodeURIComponent(searchValue)}&kelas=${encodeURIComponent(kelasValue)}`)
             .then(response => response.text())
             .then(html => {
                 container.innerHTML = html;
             })
             .catch(error => console.error('Error:', error));
     }, 300);
 }
 
 searchInput.addEventListener('input', performSearch);
 kelasSelect.addEventListener('change', performSearch);
 </script>

<?php include 'includes/footer.php'; ?>
