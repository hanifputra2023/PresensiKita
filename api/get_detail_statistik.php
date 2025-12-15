<?php
require_once '../config/koneksi.php';
require_once '../includes/fungsi.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

// Get parameters
$status = isset($_GET['status']) ? escape($_GET['status']) : '';
$filter_bulan = isset($_GET['bulan']) ? escape($_GET['bulan']) : date('Y-m');
$filter_kelas = isset($_GET['kelas']) ? escape($_GET['kelas']) : '';
$filter_mk = isset($_GET['mk']) ? escape($_GET['mk']) : '';
$filter_lab = isset($_GET['lab']) ? escape($_GET['lab']) : '';

// Build WHERE conditions
$where_kelas = $filter_kelas ? "AND j.kode_kelas = '$filter_kelas'" : '';
$where_mk = $filter_mk ? "AND j.kode_mk = '$filter_mk'" : '';
$where_lab = $filter_lab ? "AND j.kode_lab = '$filter_lab'" : '';

// Determine status condition
if ($status == 'belum') {
    $status_condition = "(p.status IS NULL OR p.status = 'belum')";
} else {
    $status_condition = "p.status = '$status'";
}

// Query detail mahasiswa
$query = "SELECT DISTINCT 
          m.nim, 
          m.nama, 
          k.nama_kelas,
          mk.nama_mk,
          j.tanggal,
          j.jam_mulai,
          j.jam_selesai,
          j.materi,
          p.status,
          l.nama_lab
          FROM mahasiswa m
          LEFT JOIN kelas k ON m.kode_kelas = k.kode_kelas
          LEFT JOIN jadwal j ON j.kode_kelas = m.kode_kelas 
              AND DATE_FORMAT(j.tanggal, '%Y-%m') = '$filter_bulan'
              AND j.tanggal <= CURDATE()
              AND j.jenis != 'inhall'
              $where_mk $where_lab
          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
          LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = m.nim
          WHERE $status_condition
          $where_kelas
          ORDER BY m.nama, j.tanggal";

$result = mysqli_query($conn, $query);
$mahasiswa_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    if (!isset($mahasiswa_list[$row['nim']])) {
        $mahasiswa_list[$row['nim']] = [
            'nim' => $row['nim'],
            'nama' => $row['nama'],
            'kelas' => $row['nama_kelas'],
            'jadwal' => []
        ];
    }
    
    // Only add jadwal if exists
    if ($row['tanggal']) {
        $mahasiswa_list[$row['nim']]['jadwal'][] = [
            'tanggal' => format_tanggal($row['tanggal']),
            'waktu' => format_waktu($row['jam_mulai']) . ' - ' . format_waktu($row['jam_selesai']),
            'mata_kuliah' => $row['nama_mk'],
            'lab' => $row['nama_lab'],
            'materi' => $row['materi'],
            'status' => $row['status'] ?: 'belum'
        ];
    }
}

// Generate HTML
?>

<style>
.detail-mahasiswa-table {
    width: 100%;
}

.detail-mahasiswa-table thead th {
    background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
    color: #fff;
    font-weight: 600;
    padding: 12px 16px;
    border: none;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.detail-mahasiswa-table tbody tr {
    border-bottom: 1px solid #e3e6f0;
    transition: background-color 0.2s ease;
}

.detail-mahasiswa-table tbody tr:hover {
    background-color: #f8f9fc;
}

.detail-mahasiswa-table tbody tr:last-child {
    border-bottom: none;
}

.detail-mahasiswa-table td {
    padding: 14px 16px;
    vertical-align: top;
}

.detail-mahasiswa-table .nim-code {
    font-family: 'Courier New', monospace;
    background-color: #f8f9fc;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    color: #0066cc;
}

.detail-mahasiswa-table .nama-mhs {
    font-weight: 600;
    color: #2d3748;
}

.detail-mahasiswa-table .kelas-badge {
    display: inline-block;
    background-color: #e7f1ff;
    color: #0066cc;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 500;
}

.detail-jadwal {
    padding: 12px;
    background-color: #f8f9fc;
    border-left: 3px solid #0066cc;
    border-radius: 4px;
    margin-bottom: 10px;
}

.detail-jadwal:last-child {
    margin-bottom: 0;
}

.detail-jadwal .tanggal-jadwal {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 4px;
}

.detail-jadwal .waktu-jadwal {
    color: #858796;
    font-size: 0.9rem;
    margin-bottom: 6px;
}

.detail-jadwal .matakuliah-jadwal {
    color: #2d3748;
    margin-bottom: 4px;
}

.detail-jadwal .status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 6px;
}

.status-badge.hadir {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.izin {
    background-color: #fff3cd;
    color: #856404;
}

.status-badge.sakit {
    background-color: #d1ecf1;
    color: #0c5460;
}

.status-badge.belum {
    background-color: #e2e3e5;
    color: #383d41;
}

.status-badge.alpha {
    background-color: #f8d7da;
    color: #721c24;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #858796;
}

.empty-state i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 16px;
}

.empty-state h5 {
    color: #2d3748;
    margin-bottom: 8px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .detail-mahasiswa-table {
        font-size: 0.9rem;
    }
    
    .detail-mahasiswa-table thead th {
        padding: 10px 12px;
        font-size: 0.75rem;
    }
    
    .detail-mahasiswa-table td {
        padding: 12px 10px;
    }
    
    .detail-mahasiswa-table .nim-code {
        font-size: 0.85rem;
        padding: 3px 6px;
    }
    
    .detail-mahasiswa-table .kelas-badge {
        padding: 4px 8px;
        font-size: 0.8rem;
    }
    
    .detail-jadwal {
        padding: 10px;
        margin-bottom: 8px;
    }
    
    .detail-jadwal .tanggal-jadwal {
        font-size: 0.95rem;
    }
    
    .detail-jadwal .waktu-jadwal {
        font-size: 0.85rem;
    }
    
    .detail-jadwal .matakuliah-jadwal {
        font-size: 0.9rem;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 3px 8px;
    }
}

@media (max-width: 576px) {
    .detail-mahasiswa-table {
        font-size: 0.85rem;
    }
    
    .detail-mahasiswa-table thead {
        display: none;
    }
    
    .detail-mahasiswa-table tbody tr {
        display: block;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        margin-bottom: 12px;
        padding: 12px;
        background: #fff;
        border-bottom: none;
    }
    
    .detail-mahasiswa-table tbody tr:hover {
        background-color: #f8f9fc;
    }
    
    .detail-mahasiswa-table td {
        display: block;
        padding: 8px 0;
        border: none;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .detail-mahasiswa-table td:first-child {
        padding-top: 0;
    }
    
    .detail-mahasiswa-table td:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .detail-mahasiswa-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #0066cc;
        display: block;
        margin-bottom: 4px;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    
    .detail-mahasiswa-table .nim-code {
        background: transparent;
        color: #2d3748;
        padding: 0;
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .detail-mahasiswa-table .nim-code::before {
        content: "NIM: ";
    }
    
    .detail-mahasiswa-table .nama-mhs::before {
        content: "Nama: ";
    }
    
    .detail-mahasiswa-table .kelas-badge {
        display: block;
        width: fit-content;
    }
    
    .detail-mahasiswa-table .kelas-badge::before {
        content: "Kelas: ";
        font-weight: 600;
        color: #0066cc;
        font-size: 0.75rem;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
    }
    
    .detail-jadwal {
        margin-bottom: 10px;
        padding: 10px;
    }
    
    .empty-state {
        padding: 30px 15px;
    }
    
    .empty-state i {
        font-size: 2.5rem;
    }
}
</style>

<div class="table-responsive">
    <table class="table detail-mahasiswa-table">
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama Mahasiswa</th>
                <th>Kelas</th>
                <th>Detail Jadwal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mahasiswa_list)): ?>
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h5>Tidak Ada Data</h5>
                            <p>Tidak ada mahasiswa dengan status ini untuk periode yang dipilih</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($mahasiswa_list as $mhs): ?>
                    <tr>
                        <td><span class="nim-code"><?= $mhs['nim'] ?></span></td>
                        <td><span class="nama-mhs"><?= $mhs['nama'] ?></span></td>
                        <td><span class="kelas-badge"><?= $mhs['kelas'] ?></span></td>
                        <td>
                            <?php if (!empty($mhs['jadwal'])): ?>
                                <div>
                                    <?php foreach ($mhs['jadwal'] as $index => $jadwal): ?>
                                        <div class="detail-jadwal">
                                            <div class="tanggal-jadwal">
                                                <i class="fas fa-calendar-alt me-1" style="color: #0066cc;"></i>
                                                <?= $jadwal['tanggal'] ?>
                                            </div>
                                            <div class="waktu-jadwal">
                                                <i class="fas fa-clock me-1"></i><?= $jadwal['waktu'] ?>
                                            </div>
                                            <div class="matakuliah-jadwal">
                                                <i class="fas fa-book me-1" style="color: #0066cc;"></i>
                                                <strong><?= $jadwal['mata_kuliah'] ?></strong> <small class="text-muted">(<?= $jadwal['lab'] ?>)</small>
                                            </div>
                                            <span class="status-badge <?= strtolower($jadwal['status']) ?>">
                                                <i class="fas fa-<?= 
                                                    $jadwal['status'] == 'hadir' ? 'check-circle' : 
                                                    ($jadwal['status'] == 'izin' ? 'clock' : 
                                                    ($jadwal['status'] == 'sakit' ? 'heartbeat' : 
                                                    ($jadwal['status'] == 'belum' ? 'hourglass-half' : 'times-circle')))
                                                ?> me-1"></i>
                                                <?= ucfirst($jadwal['status']) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">
                                    <i class="fas fa-minus-circle me-1"></i>Tidak ada jadwal
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
