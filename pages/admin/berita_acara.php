<?php
$page = 'admin_berita_acara';

// Filter Tanggal (Default: Bulan ini)
$start_date = isset($_GET['start_date']) ? escape($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? escape($_GET['end_date']) : date('Y-m-t');

// Pagination
$per_page = 10;
$current_page = get_current_page();

// Hitung total data untuk pagination
$count_query = "SELECT COUNT(*) as total FROM berita_acara ba JOIN jadwal j ON ba.jadwal_id = j.id WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'";
$count_result = mysqli_query($conn, $count_query);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Query untuk mengambil data BAP beserta detail jadwalnya dengan LIMIT
$query = "SELECT ba.*, j.tanggal, j.jam_mulai, j.jam_selesai, 
          k.nama_kelas, mk.nama_mk, l.nama_lab, a.nama as nama_asisten
          FROM berita_acara ba
          JOIN jadwal j ON ba.jadwal_id = j.id
          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
          LEFT JOIN asisten a ON ba.kode_asisten = a.kode_asisten
          WHERE j.tanggal BETWEEN '$start_date' AND '$end_date'
          ORDER BY j.tanggal DESC, j.jam_mulai DESC
          LIMIT $offset, $per_page";

$result = mysqli_query($conn, $query);
?>

<?php include 'includes/header.php'; ?>

<style>
/* Welcome Banner Modern */
.welcome-banner-berita-acara {
    background: var(--banner-gradient);
    border-radius: 24px;
    padding: 40px;
    color: white;
    box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
    animation: fadeInUp 0.5s ease;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.welcome-banner-berita-acara::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse-glow-berita-acara 4s ease-in-out infinite;
}

@keyframes pulse-glow-berita-acara {
    0%, 100% {
        transform: scale(1);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.6;
    }
}

@keyframes pulse-badge-berita-acara {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 8px rgba(255, 255, 255, 0);
    }
}

.welcome-banner-berita-acara h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
}

.welcome-banner-berita-acara .banner-subtitle {
    font-size: 16px;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

.welcome-banner-berita-acara .banner-icon {
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

.welcome-banner-berita-acara .banner-badge {
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
    animation: pulse-badge-berita-acara 2s ease-in-out infinite;
}

.welcome-banner-berita-acara .banner-badge i {
    font-size: 8px;
    margin-right: 6px;
    animation: pulse 1.5s ease-in-out infinite;
}

.welcome-banner-berita-acara .btn-banner {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 1;
    font-size: 15px;
}

.welcome-banner-berita-acara .btn-banner:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    color: white;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark Mode Support */
[data-theme="dark"] .welcome-banner-berita-acara {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* Responsive Design */
@media (max-width: 768px) {
    .welcome-banner-berita-acara {
        padding: 24px;
        border-radius: 16px;
    }
    
    .welcome-banner-berita-acara h1 {
        font-size: 22px;
    }
    
    .welcome-banner-berita-acara .banner-subtitle {
        font-size: 14px;
    }
    
    .welcome-banner-berita-acara .banner-icon {
        width: 50px;
        height: 50px;
        font-size: 22px;
    }
    
    .welcome-banner-berita-acara .banner-badge {
        font-size: 11px;
        padding: 6px 14px;
    }
    
    .welcome-banner-berita-acara .btn-banner {
        padding: 10px 20px;
        font-size: 14px;
    }
}

@media (max-width: 576px) {
    .welcome-banner-berita-acara {
        padding: 20px;
    }
    
    .welcome-banner-berita-acara h1 {
        font-size: 19px;
    }
    
    .welcome-banner-berita-acara .banner-subtitle {
        font-size: 13px;
    }
    
    .welcome-banner-berita-acara .banner-icon {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }
    
    .welcome-banner-berita-acara .banner-badge {
        font-size: 10px;
        padding: 5px 12px;
    }
    
    .welcome-banner-berita-acara .btn-banner {
        padding: 10px 18px;
        font-size: 13px;
    }
}

@media (min-width: 577px) {
    .welcome-banner-berita-acara .btn-banner {
        width: auto;
    }
}

@media print {
    .sidebar, .no-print { display: none !important; }
    .content-wrapper { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
    .card { border: none !important; shadow: none !important; }
    .welcome-banner-berita-acara { display: none; }
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
                <div class="welcome-banner-berita-acara no-print">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="banner-icon">
                                    <i class="fas fa-file-signature"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Berita Acara Praktikum</h1>
                                    <p class="banner-subtitle mb-0">Rekap dan monitoring berita acara pelaksanaan praktikum</p>
                                </div>
                            </div>
                            <span class="banner-badge">
                                <i class="fas fa-circle me-1"></i>DOCUMENTATION
                            </span>
                        </div>
                        <button onclick="window.print()" class="btn btn-banner">
                            <i class="fas fa-print me-2"></i>Cetak Laporan
                        </button>
                    </div>
                </div>

                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <input type="hidden" name="page" value="admin_berita_acara">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="d-none d-print-block text-center mb-4">Laporan Berita Acara Praktikum<br><small><?= format_tanggal($start_date) ?> s/d <?= format_tanggal($end_date) ?></small></h5>
                        
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Asisten</th>
                                            <th>Mata Kuliah & Kelas</th>
                                            <th>Waktu Real</th>
                                            <th class="text-center">Detail Laporan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?= format_tanggal($row['tanggal']) ?></td>
                                                <td><?= $row['nama_asisten'] ?></td>
                                                <td>
                                                    <strong><?= $row['nama_mk'] ?></strong><br>
                                                    <small class="text-muted"><?= $row['nama_kelas'] ?> - <?= $row['nama_lab'] ?></small>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $start_real = $row['waktu_mulai_real'] ? date('H:i', strtotime($row['waktu_mulai_real'])) : '-';
                                                    $end_real = $row['waktu_selesai_real'] ? date('H:i', strtotime($row['waktu_selesai_real'])) : '-';
                                                    echo "$start_real - $end_real";
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-info text-white btn-detail"
                                                        data-bs-toggle="modal" data-bs-target="#modalDetail"
                                                        data-mk="<?= htmlspecialchars($row['nama_mk']) ?>"
                                                        data-kelas="<?= htmlspecialchars($row['nama_kelas']) ?>"
                                                        data-asisten="<?= htmlspecialchars($row['nama_asisten']) ?>"
                                                        data-tanggal="<?= format_tanggal($row['tanggal']) ?>"
                                                        data-catatan="<?= htmlspecialchars($row['catatan']) ?>"
                                                        data-foto="<?= htmlspecialchars($row['foto_bukti']) ?>">
                                                        <i class="fas fa-eye me-1"></i> Lihat
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                <p>Belum ada berita acara pada periode ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pagination Controls -->
                <?php if ($total_data > 0): ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2 no-print">
                    <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                    <?= render_pagination($current_page, $total_pages, 'index.php?page=admin_berita_acara', ['start_date' => $start_date, 'end_date' => $end_date]) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail BAP -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Detail Berita Acara</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold small text-muted">Informasi Praktikum</label>
                    <div id="detailInfo"></div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold small text-muted">Catatan Kejadian</label>
                    <div class="p-3 bg-light border rounded" id="detailCatatan" style="white-space: pre-wrap;"></div>
                </div>
                <div>
                    <label class="fw-bold small text-muted">Bukti Foto</label>
                    <div id="detailFoto" class="text-center mt-2"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalDetail = document.getElementById('modalDetail');
    modalDetail.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        const mk = button.getAttribute('data-mk');
        const kelas = button.getAttribute('data-kelas');
        const asisten = button.getAttribute('data-asisten');
        const tanggal = button.getAttribute('data-tanggal');
        const catatan = button.getAttribute('data-catatan');
        const foto = button.getAttribute('data-foto');
        
        document.getElementById('detailInfo').innerHTML = `
            <strong>${mk}</strong><br>
            ${kelas}<br>
            <small>${tanggal} &bull; Asisten: ${asisten}</small>
        `;
        
        document.getElementById('detailCatatan').textContent = catatan || '- Tidak ada catatan -';
        
        const fotoContainer = document.getElementById('detailFoto');
        if (foto) {
            fotoContainer.innerHTML = `
                <img src="${foto}" class="img-fluid rounded border mb-2" style="max-height: 300px;">
                <br>
                <a href="${foto}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt me-1"></i>Buka Ukuran Penuh
                </a>
            `;
        } else {
            fotoContainer.innerHTML = '<span class="text-muted fst-italic">- Tidak ada bukti foto -</span>';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>