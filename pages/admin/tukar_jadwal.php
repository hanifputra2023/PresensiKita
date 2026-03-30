<?php
cek_login();
cek_role(['admin']);
$page = 'admin_tukar_jadwal';

// Handle pembuatan DB jika belum ada
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tukar_jadwal_sementara'");
if (mysqli_num_rows($check_table) == 0) {
    mysqli_query($conn, "CREATE TABLE `tukar_jadwal_sementara` (
        `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `nim_pengaju` varchar(15) NOT NULL,
        `jadwal_awal_id` int(11) NOT NULL,
        `nim_dituju` varchar(15) DEFAULT NULL,
        `jadwal_tujuan_id` int(11) NOT NULL,
        `alasan` text NOT NULL,
        `status` enum('menunggu_teman', 'menunggu_admin', 'disetujui', 'ditolak', 'dibatalkan') DEFAULT 'menunggu_teman',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// Approval Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $tukar_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    // Pastikan status sedang menunggu admin
    $cek = mysqli_query($conn, "SELECT * FROM tukar_jadwal_sementara WHERE id = $tukar_id AND status = 'menunggu_admin'");
    if (mysqli_num_rows($cek) > 0) {
        if ($action == 'approve') {
            mysqli_query($conn, "UPDATE tukar_jadwal_sementara SET status = 'disetujui' WHERE id = $tukar_id");
            set_alert('success', 'Tukar jadwal berhasil disetujui.');
        } elseif ($action == 'reject') {
            mysqli_query($conn, "UPDATE tukar_jadwal_sementara SET status = 'ditolak' WHERE id = $tukar_id");
            set_alert('warning', 'Tukar jadwal ditolak.');
        }
        header("Location: index.php?page=admin_tukar_jadwal");
        exit;
    }
}

// Data Tukar Jadwal yang menunggu admin
$q_pending = mysqli_query($conn, "
    SELECT t.*, 
           m1.nama as nama_pengaju, m1.kode_kelas as kelas_pengaju,
           m2.nama as nama_dituju, m2.kode_kelas as kelas_dituju,
           j1.tanggal as tgl_awal, j1.jam_mulai as jam_awal, j1.sesi as sesi_awal, mk1.nama_mk as mk_awal,
           j2.tanggal as tgl_tujuan, j2.jam_mulai as jam_tujuan, j2.sesi as sesi_tujuan, mk2.nama_mk as mk_tujuan
    FROM tukar_jadwal_sementara t
    JOIN mahasiswa m1 ON t.nim_pengaju = m1.nim
    LEFT JOIN mahasiswa m2 ON t.nim_dituju = m2.nim
    JOIN jadwal j1 ON t.jadwal_awal_id = j1.id
    JOIN jadwal j2 ON t.jadwal_tujuan_id = j2.id
    JOIN mata_kuliah mk1 ON j1.kode_mk = mk1.kode_mk
    JOIN mata_kuliah mk2 ON j2.kode_mk = mk2.kode_mk
    WHERE t.status = 'menunggu_admin'
    ORDER BY t.created_at ASC
");

// Data Riwayat (Sudah disetujui / ditolak)
$q_history = mysqli_query($conn, "
    SELECT t.*, 
           m1.nama as nama_pengaju, m2.nama as nama_dituju,
           j1.tanggal as tgl_awal, mk1.nama_mk as mk_awal, j1.sesi as sesi_awal,
           j2.tanggal as tgl_tujuan, mk2.nama_mk as mk_tujuan, j2.sesi as sesi_tujuan
    FROM tukar_jadwal_sementara t
    JOIN mahasiswa m1 ON t.nim_pengaju = m1.nim
    LEFT JOIN mahasiswa m2 ON t.nim_dituju = m2.nim
    JOIN jadwal j1 ON t.jadwal_awal_id = j1.id
    JOIN jadwal j2 ON t.jadwal_tujuan_id = j2.id
    JOIN mata_kuliah mk1 ON j1.kode_mk = mk1.kode_mk
    JOIN mata_kuliah mk2 ON j2.kode_mk = mk2.kode_mk
    WHERE t.status IN ('disetujui', 'ditolak')
    ORDER BY t.created_at DESC LIMIT 50
");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <!-- Welcome Banner Modern -->
                <style>
                    .welcome-banner-tukar {
                        background: var(--banner-gradient, linear-gradient(90deg, #0066cc, #0099ff, #16a1fdff));
                        border-radius: 24px;
                        padding: 40px;
                        color: white;
                        position: relative;
                        overflow: hidden;
                    }

                    .welcome-banner-tukar::before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        left: -50%;
                        width: 200%;
                        height: 200%;
                        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
                        animation: rotateBanner 20s linear infinite;
                    }

                    @keyframes rotateBanner {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }

                    .welcome-banner-tukar h1 {
                        font-size: 32px;
                        font-weight: 700;
                        margin: 0;
                        position: relative;
                        z-index: 1;
                    }

                    .welcome-banner-tukar .banner-subtitle {
                        font-size: 16px;
                        opacity: 0.95;
                        position: relative;
                        z-index: 1;
                    }

                    .welcome-banner-tukar .banner-icon {
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

                    .welcome-banner-tukar .banner-badge {
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
                        margin-top: 15px;
                    }
                    
                    /* Theme Dark adjustments context */
                    [data-theme="dark"] .welcome-banner-tukar {
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                    }

                    /* For Mobile banner adjustments */
                    @media (max-width: 768px) {
                        .welcome-banner-tukar {
                            padding: 24px;
                            border-radius: 16px;
                        }
                        .welcome-banner-tukar h1 {
                            font-size: 24px;
                        }
                        .welcome-banner-tukar .banner-icon {
                            width: 48px;
                            height: 48px;
                            font-size: 20px;
                        }
                    }
                </style>

                <div class="welcome-banner-tukar mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Manajemen Tukar Jadwal</h1>
                                    <p class="banner-subtitle mb-0">Kelola persetujuan tukar jadwal praktikum mahasiswa</p>
                                </div>
                            </div>
                            <span class="banner-badge">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 6px;"></i>ADMINISTRATOR
                            </span>
                        </div>
                    </div>
                </div>

                <?= show_alert() ?>

                <div class="card shadow mb-4 border-bottom-primary">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Menunggu Persetujuan Admin</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Pengaju (Pihak 1)</th>
                                        <th>Dituju (Pihak 2)</th>
                                        <th>Detail Tukar (Jadwal 1 -> Jadwal 2)</th>
                                        <th>Alasan</th>
                                        <th>Waktu Pengajuan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row = mysqli_fetch_assoc($q_pending)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <b><?= htmlspecialchars($row['nama_pengaju']) ?></b><br>
                                            <span class="small text-muted"><?= $row['nim_pengaju'] ?> (Kls <?= $row['kelas_pengaju'] ?>)</span>
                                        </td>
                                        <td>
                                            <b><?= htmlspecialchars($row['nama_dituju'] ?? '-') ?></b><br>
                                            <?php if($row['nim_dituju']): ?>
                                            <span class="small text-muted"><?= $row['nim_dituju'] ?> (Kls <?= $row['kelas_dituju'] ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <b class="text-danger">Pihak 1 Pindah Ke:</b> <?= $row['mk_tujuan'] ?> - Sesi <?= $row['sesi_tujuan'] ?> (<?= date('d M Y', strtotime($row['tgl_tujuan'])) ?>)<br>
                                            <b class="text-primary">Pihak 2 Pindah Ke:</b> <?= $row['mk_awal'] ?> - Sesi <?= $row['sesi_awal'] ?> (<?= date('d M Y', strtotime($row['tgl_awal'])) ?>)
                                        </td>
                                        <td><?= htmlspecialchars($row['alasan']) ?></td>
                                        <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="index.php?page=admin_tukar_jadwal&action=approve&id=<?= $row['id'] ?>" class="btn btn-success btn-sm" title="Setujui" onclick="return confirm('Yakin ingin menyetujui pertukaran ini?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="index.php?page=admin_tukar_jadwal&action=reject&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" title="Tolak" onclick="return confirm('Yakin ingin menolak?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if(mysqli_num_rows($q_pending) == 0): ?>
                                    <tr><td colspan="7" class="text-center text-muted">Tidak ada pengajuan baru.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Riwayat -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-secondary">Riwayat Persetujuan</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle" id="dataTableRiwayat" width="100%" cellspacing="0">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Pihak 1</th>
                                        <th>Pihak 2</th>
                                        <th>Status</th>
                                        <th>Waktu Pengajuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row = mysqli_fetch_assoc($q_history)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama_pengaju']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_dituju'] ?? '-') ?></td>
                                        <td>
                                            <?php if($row['status'] == 'disetujui'): ?>
                                                <span class="badge bg-success">Disetujui</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#dataTableRiwayat').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>