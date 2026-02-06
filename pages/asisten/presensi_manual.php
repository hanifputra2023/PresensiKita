<?php
$page = 'asisten_presensi_manual';
$asisten = get_asisten_login();

// Validasi data asisten
if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Data asisten tidak ditemukan. Pastikan akun Anda sudah terdaftar sebagai asisten.</div>';
    return;
}

$kode_asisten = $asisten['kode_asisten'];

// Proses presensi manual
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $nim = escape($_POST['nim']);
    $status = escape($_POST['status']);
    
    // Cek apakah ini jadwal sebagai pengganti untuk catat hadir asisten - prepared statement
    $stmt_cek_pg = mysqli_prepare($conn, "SELECT id FROM absen_asisten 
                                                               WHERE jadwal_id = ? 
                                                               AND pengganti = ?
                                                               AND status IN ('izin', 'sakit')
                                                               AND status_approval = 'approved'");
    mysqli_stmt_bind_param($stmt_cek_pg, "is", $jadwal_id, $kode_asisten);
    mysqli_stmt_execute($stmt_cek_pg);
    $cek_pengganti_post = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_pg));
    $is_pengganti_post = $cek_pengganti_post ? true : false;
    
    // CATAT HADIR ASISTEN saat melakukan presensi manual pertama kali
    catat_hadir_asisten($kode_asisten, $jadwal_id, $is_pengganti_post);
    
    // Cek sudah ada presensi - prepared statement
    $stmt_cek = mysqli_prepare($conn, "SELECT * FROM presensi_mahasiswa WHERE jadwal_id = ? AND nim = ?");
    mysqli_stmt_bind_param($stmt_cek, "is", $jadwal_id, $nim);
    mysqli_stmt_execute($stmt_cek);
    $cek = mysqli_stmt_get_result($stmt_cek);
    
    if (mysqli_num_rows($cek) > 0) {
        $stmt_upd = mysqli_prepare($conn, "UPDATE presensi_mahasiswa SET status = ?, metode = 'manual', validated_by = ? WHERE jadwal_id = ? AND nim = ?");
        mysqli_stmt_bind_param($stmt_upd, "ssis", $status, $kode_asisten, $jadwal_id, $nim);
        mysqli_stmt_execute($stmt_upd);
    } else {
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, validated_by) VALUES (?, ?, ?, 'manual', ?)");
        mysqli_stmt_bind_param($stmt_ins, "isss", $jadwal_id, $nim, $status, $kode_asisten);
        mysqli_stmt_execute($stmt_ins);
    }
    
    log_aktivitas($_SESSION['user_id'], 'PRESENSI_MANUAL', 'presensi_mahasiswa', $jadwal_id, "Presensi manual: $nim - $status");
    set_alert('success', 'Presensi berhasil dicatat!');
    
    header("Location: index.php?page=asisten_presensi_manual&jadwal=$jadwal_id");
    exit;
}

$jadwal_id = isset($_GET['jadwal']) ? (int)$_GET['jadwal'] : 0;

// Jadwal hari ini - termasuk jadwal reguler dan jadwal pengganti
// Jadwal tetap muncul SEPANJANG HARI agar asisten bisa set status (termasuk alpha) kapan saja
$today = date('Y-m-d');
$now_time = date('H:i:s');

// Query jadwal reguler - SEMUA jadwal hari ini (tanpa filter jam_selesai)
$jadwal_reguler = mysqli_query($conn, "SELECT j.*, k.nama_kelas, 0 as is_pengganti FROM jadwal j 
                                     LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                     WHERE j.tanggal = '$today' 
                                     AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                     ORDER BY j.jam_mulai");

// Query jadwal sebagai pengganti (hanya yang sudah disetujui admin) - SEMUA jadwal hari ini
$jadwal_pengganti = mysqli_query($conn, "SELECT j.*, k.nama_kelas, 1 as is_pengganti FROM jadwal j 
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          INNER JOIN absen_asisten aa ON aa.jadwal_id = j.id AND aa.pengganti = '$kode_asisten'
                                          WHERE j.tanggal = '$today' 
                                          AND aa.status IN ('izin', 'sakit')
                                          AND aa.status_approval = 'approved'
                                          ORDER BY j.jam_mulai");

// Gabungkan jadwal (hindari duplikasi)
$jadwal_list = [];
$jadwal_ids = [];

while ($row = mysqli_fetch_assoc($jadwal_reguler)) {
    // Skip jika jadwal ini adalah jadwal yang kita gantikan (sudah di-update oleh admin)
    $cek_sbg_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                                  WHERE jadwal_id = '{$row['id']}' 
                                                                  AND pengganti = '$kode_asisten' 
                                                                  AND status IN ('izin', 'sakit')
                                                                  AND status_approval = 'approved'"));
    if ($cek_sbg_pengganti) {
        continue; // Akan diambil dari query pengganti
    }
    $jadwal_list[] = $row;
    $jadwal_ids[] = $row['id'];
}

while ($row = mysqli_fetch_assoc($jadwal_pengganti)) {
    if (!in_array($row['id'], $jadwal_ids)) {
        $jadwal_list[] = $row;
        $jadwal_ids[] = $row['id'];
    }
}

$jadwal_aktif = null;
$mahasiswa_list = null;
$search = '';
if ($jadwal_id) {
    $jadwal_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.*, k.nama_kelas FROM jadwal j 
                                                             LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                                             WHERE j.id = '$jadwal_id'"));
    if ($jadwal_aktif) {
        // Cek apakah ini jadwal sebagai pengganti (hanya yang sudah approved)
        $cek_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                                   WHERE jadwal_id = '$jadwal_id' 
                                                                   AND pengganti = '$kode_asisten'
                                                                   AND status IN ('izin', 'sakit')
                                                                   AND status_approval = 'approved'"));
        $is_pengganti = $cek_pengganti ? true : false;
        
        // TIDAK otomatis catat hadir saat buka halaman
        // Hadir dicatat saat melakukan presensi manual pertama kali
        
        $kelas = $jadwal_aktif['kode_kelas'];
        $jenis_jadwal = $jadwal_aktif['jenis'];
        $kode_mk = $jadwal_aktif['kode_mk'];
        $sesi_jadwal = $jadwal_aktif['sesi'];
        
        $search = isset($_GET['search']) ? escape($_GET['search']) : '';
        $search_sql = $search ? "AND (m.nama LIKE '%$search%' OR m.nim LIKE '%$search%')" : "";
        
        // Untuk INHALL: hanya tampilkan mahasiswa yang terdaftar di penggantian_inhall
        if ($jenis_jadwal == 'inhall') {
            $mahasiswa_list = mysqli_query($conn, "SELECT DISTINCT m.*, p.status 
                                                    FROM penggantian_inhall pi
                                                    JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                                    JOIN mahasiswa m ON pi.nim = m.nim
                                                    LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = '$jadwal_id'
                                                    WHERE jx.kode_mk = '$kode_mk' AND m.kode_kelas = '$kelas'
                                                    AND pi.status IN ('terdaftar', 'hadir')
                                                    $search_sql
                                                    ORDER BY m.nama");
        } else {
            $mahasiswa_list = mysqli_query($conn, "SELECT m.*, p.status FROM mahasiswa m 
                                                    LEFT JOIN presensi_mahasiswa p ON p.nim = m.nim AND p.jadwal_id = '$jadwal_id'
                                                    WHERE m.kode_kelas = '$kelas' 
                                                    AND (m.sesi = '$sesi_jadwal' OR '$sesi_jadwal' = 0)
                                                    $search_sql
                                                    ORDER BY m.nama");
        }

        // Handle AJAX Search
        if (isset($_GET['ajax_search'])) {
            // Desktop Table
            echo '<div class="table-responsive d-none d-lg-block sticky-header">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            if ($mahasiswa_list && mysqli_num_rows($mahasiswa_list) > 0) {
                $no = 1;
                while ($m = mysqli_fetch_assoc($mahasiswa_list)) {
                    $status_badge = '';
                    if ($m['status'] && $m['status'] != 'belum') {
                        $bg = $m['status'] == 'hadir' ? 'success' : ($m['status'] == 'izin' ? 'warning' : ($m['status'] == 'sakit' ? 'info' : 'danger'));
                        $status_badge = '<span class="badge bg-'.$bg.'">'.ucfirst($m['status']).'</span>';
                    } else {
                        $status_badge = '<span class="badge bg-secondary">Belum</span>';
                    }

                    echo '<tr>
                        <td>'.($no++).'</td>
                        <td>'.$m['nim'].'</td>
                        <td>'.$m['nama'].'</td>
                        <td>'.$status_badge.'</td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="jadwal_id" value="'.$jadwal_id.'">
                                <input type="hidden" name="nim" value="'.$m['nim'].'">
                                <div class="btn-group btn-group-sm">
                                    <button type="submit" name="status" value="hadir" class="btn btn-success" title="Hadir"><i class="fas fa-check"></i></button>
                                    <button type="submit" name="status" value="izin" class="btn btn-warning" title="Izin"><i class="fas fa-envelope"></i></button>
                                    <button type="submit" name="status" value="sakit" class="btn btn-info" title="Sakit"><i class="fas fa-hospital"></i></button>
                                    <button type="submit" name="status" value="alpha" class="btn btn-danger" title="Alpha"><i class="fas fa-times"></i></button>
                                </div>
                            </form>
                        </td>
                    </tr>';
                }
            } else {
                echo '<tr><td colspan="5" class="text-center text-muted">Tidak ada data mahasiswa</td></tr>';
            }
            echo '</tbody></table></div>';

            // Mobile Cards
            echo '<div class="d-lg-none">';
            if ($mahasiswa_list && mysqli_num_rows($mahasiswa_list) > 0) {
                mysqli_data_seek($mahasiswa_list, 0);
                while ($m = mysqli_fetch_assoc($mahasiswa_list)) {
                    $border_class = $m['status'] == 'hadir' ? 'border-success' : '';
                    $status_badge = ($m['status'] && $m['status'] != 'belum') 
                        ? '<span class="badge bg-'.($m['status'] == 'hadir' ? 'success' : ($m['status'] == 'izin' ? 'warning' : ($m['status'] == 'sakit' ? 'info' : 'danger'))).'">'.ucfirst($m['status']).'</span>'
                        : '<span class="badge bg-secondary">Belum</span>';

                    echo '<div class="card mb-2 border '.$border_class.'">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div><strong class="small">'.$m['nama'].'</strong><br><small class="text-muted">'.$m['nim'].'</small></div>
                                '.$status_badge.'
                            </div>
                            <form method="POST"><input type="hidden" name="jadwal_id" value="'.$jadwal_id.'"><input type="hidden" name="nim" value="'.$m['nim'].'">
                            <div class="btn-group w-100"><button type="submit" name="status" value="hadir" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button><button type="submit" name="status" value="izin" class="btn btn-sm btn-warning"><i class="fas fa-envelope"></i></button><button type="submit" name="status" value="sakit" class="btn btn-sm btn-info"><i class="fas fa-hospital"></i></button><button type="submit" name="status" value="alpha" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button></div></form>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="text-center p-3 text-muted">Tidak ada data mahasiswa</div>';
            }
            echo '</div>';
            exit;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* Sticky Header Table */
.table-responsive.sticky-header {
    max-height: 70vh;
    overflow-y: auto;
}
.table-responsive.sticky-header thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: var(--header-bg) !important;
    box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-edit me-2"></i>Presensi Manual</h4>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <!-- Mobile: Pilih Jadwal di atas -->
                    <div class="col-12 d-md-none mb-3">
                        <div class="card">
                            <div class="card-header">Pilih Jadwal</div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($jadwal_list as $j): 
                                        $jadwal_selesai = ($j['jam_selesai'] < $now_time);
                                    ?>
                                        <a href="index.php?page=asisten_presensi_manual&jadwal=<?= $j['id'] ?>" 
                                           class="btn btn-sm <?= $jadwal_id == $j['id'] ? 'btn-primary' : ($jadwal_selesai ? 'btn-outline-secondary' : 'btn-outline-primary') ?>">
                                            <?= $j['nama_kelas'] ?>
                                            <?php if (!empty($j['is_pengganti'])): ?>
                                                <span class="badge bg-info">P</span>
                                            <?php endif; ?>
                                            <?php if ($jadwal_selesai): ?>
                                                <span class="badge bg-secondary">Selesai</span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Desktop: Sidebar Jadwal -->
                    <div class="col-md-3 mb-4 d-none d-md-block">
                        <div class="card">
                            <div class="card-header">Pilih Jadwal</div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($jadwal_list as $j): 
                                        $jadwal_selesai = ($j['jam_selesai'] < $now_time);
                                    ?>
                                        <a href="index.php?page=asisten_presensi_manual&jadwal=<?= $j['id'] ?>" 
                                           class="list-group-item list-group-item-action <?= $jadwal_id == $j['id'] ? 'active' : '' ?>">
                                            <strong>
                                                <?= $j['nama_kelas'] ?>
                                                <?php if (!empty($j['is_pengganti'])): ?>
                                                    <span class="badge bg-info">Pengganti</span>
                                                <?php endif; ?>
                                                <?php if ($jadwal_selesai): ?>
                                                    <span class="badge bg-secondary">Selesai</span>
                                                <?php endif; ?>
                                            </strong>
                                            <br><small><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-9">
                        <?php if ($jadwal_aktif && $mahasiswa_list): ?>
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span>Presensi <?= $jadwal_aktif['nama_kelas'] ?> - <?= format_tanggal($jadwal_aktif['tanggal']) ?></span>
                                    <div class="d-flex" style="max-width: 300px;">
                                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cari Nama/NIM..." value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                </div>
                                <div class="card-body p-2 p-md-3" id="mahasiswaListContainer">
                                    <!-- Desktop Table -->
                                    <div class="table-responsive d-none d-lg-block sticky-header">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>NIM</th>
                                                    <th>Nama</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                mysqli_data_seek($mahasiswa_list, 0);
                                                $no = 1; 
                                                while ($m = mysqli_fetch_assoc($mahasiswa_list)): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td><?= $m['nim'] ?></td>
                                                        <td><?= $m['nama'] ?></td>
                                                        <td>
                                                            <?php if ($m['status'] && $m['status'] != 'belum'): ?>
                                                                <span class="badge bg-<?= $m['status'] == 'hadir' ? 'success' : ($m['status'] == 'izin' ? 'warning' : ($m['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                                    <?= ucfirst($m['status']) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Belum</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                                                                <input type="hidden" name="nim" value="<?= $m['nim'] ?>">
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="submit" name="status" value="hadir" class="btn btn-success" title="Hadir">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                    <button type="submit" name="status" value="izin" class="btn btn-warning" title="Izin">
                                                                        <i class="fas fa-envelope"></i>
                                                                    </button>
                                                                    <button type="submit" name="status" value="sakit" class="btn btn-info" title="Sakit">
                                                                        <i class="fas fa-hospital"></i>
                                                                    </button>
                                                                    <button type="submit" name="status" value="alpha" class="btn btn-danger" title="Alpha">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Mobile Cards -->
                                    <div class="d-lg-none">
                                        <?php 
                                        mysqli_data_seek($mahasiswa_list, 0);
                                        while ($m = mysqli_fetch_assoc($mahasiswa_list)): ?>
                                            <div class="card mb-2 border <?= $m['status'] == 'hadir' ? 'border-success' : '' ?>">
                                                <div class="card-body p-2">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div>
                                                            <strong class="small"><?= $m['nama'] ?></strong>
                                                            <br><small class="text-muted"><?= $m['nim'] ?></small>
                                                        </div>
                                                        <?php if ($m['status'] && $m['status'] != 'belum'): ?>
                                                            <span class="badge bg-<?= $m['status'] == 'hadir' ? 'success' : ($m['status'] == 'izin' ? 'warning' : ($m['status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                                <?= ucfirst($m['status']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Belum</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <form method="POST">
                                                        <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                                                        <input type="hidden" name="nim" value="<?= $m['nim'] ?>">
                                                        <div class="btn-group w-100">
                                                            <button type="submit" name="status" value="hadir" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="submit" name="status" value="izin" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-envelope"></i>
                                                            </button>
                                                            <button type="submit" name="status" value="sakit" class="btn btn-sm btn-info">
                                                                <i class="fas fa-hospital"></i>
                                                            </button>
                                                            <button type="submit" name="status" value="alpha" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-hand-pointer fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Pilih jadwal terlebih dahulu</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout = null;
const searchInput = document.getElementById('searchInput');
const container = document.getElementById('mahasiswaListContainer');

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const val = this.value;
        searchTimeout = setTimeout(() => {
            fetch(`index.php?page=asisten_presensi_manual&jadwal=<?= $jadwal_id ?>&ajax_search=1&search=${encodeURIComponent(val)}`)
                .then(response => response.text())
                .then(html => { container.innerHTML = html; });
        }, 300);
    });
}
</script>
<?php include 'includes/footer.php'; ?>
