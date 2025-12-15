<?php
$page = 'asisten_pengajuan_izin';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

// Ambil daftar asisten lain untuk pengganti
$asisten_lain = mysqli_query($conn, "SELECT kode_asisten, nama FROM asisten 
                                      WHERE kode_asisten != '$kode_asisten' AND status = 'aktif'");

// Proses form izin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_izin'])) {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $status = escape($_POST['status']);
    $pengganti = !empty($_POST['pengganti']) ? escape($_POST['pengganti']) : null;
    $catatan = escape($_POST['catatan']);
    
    // Validasi: Cek jadwal valid dan milik asisten ini
    $now = date('Y-m-d H:i:s');
    $jadwal_cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT j.*, 
                                                           CONCAT(j.tanggal, ' ', j.jam_mulai) as jadwal_datetime
                                                           FROM jadwal j 
                                                           WHERE j.id = '$jadwal_id' 
                                                           AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')"));
    
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
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, status FROM absen_asisten 
                                                         WHERE jadwal_id = '$jadwal_id' AND kode_asisten = '$kode_asisten'"));
    
    if ($existing && $existing['status'] == 'hadir') {
        set_alert('danger', 'Anda sudah tercatat hadir di jadwal ini!');
        header("Location: index.php?page=asisten_pengajuan_izin");
        exit;
    }
    
    if ($existing) {
        // Update izin yang sudah ada
        $sql = "UPDATE absen_asisten SET status = '$status', pengganti = " . ($pengganti ? "'$pengganti'" : "NULL") . ", 
                catatan = '$catatan' WHERE id = '{$existing['id']}'";
    } else {
        // Insert baru
        $sql = "INSERT INTO absen_asisten (jadwal_id, kode_asisten, status, pengganti, catatan) 
                VALUES ('$jadwal_id', '$kode_asisten', '$status', " . ($pengganti ? "'$pengganti'" : "NULL") . ", '$catatan')";
    }
    
    if (mysqli_query($conn, $sql)) {
        log_aktivitas($_SESSION['user_id'], 'IZIN_ASISTEN', 'absen_asisten', $jadwal_id, "Asisten $kode_asisten mengajukan $status");
        set_alert('success', 'Pengajuan izin berhasil disimpan!');
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
    $izin_cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT aa.*, 
                                                         CONCAT(j.tanggal, ' ', j.jam_mulai) as jadwal_datetime
                                                         FROM absen_asisten aa
                                                         JOIN jadwal j ON aa.jadwal_id = j.id
                                                         WHERE aa.id = '$id' AND aa.kode_asisten = '$kode_asisten'"));
    
    if (!$izin_cek) {
        set_alert('danger', 'Data izin tidak ditemukan!');
    } elseif ($izin_cek['status'] == 'hadir') {
        set_alert('danger', 'Tidak bisa membatalkan karena Anda sudah tercatat hadir!');
    } elseif (strtotime($izin_cek['jadwal_datetime']) <= strtotime($now)) {
        set_alert('danger', 'Tidak bisa membatalkan izin untuk jadwal yang sudah lewat!');
    } else {
        mysqli_query($conn, "DELETE FROM absen_asisten WHERE id = '$id' AND kode_asisten = '$kode_asisten'");
        set_alert('info', 'Pengajuan izin dibatalkan.');
    }
    
    header("Location: index.php?page=asisten_pengajuan_izin");
    exit;
}

// Jadwal mendatang (yang bisa diajukan izin)
$today = date('Y-m-d');
$now_time = date('H:i:s');
$jadwal_mendatang = mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk,
                                          aa.id as izin_id, aa.status as izin_status, aa.pengganti, aa.catatan, aa.jam_masuk,
                                          ap.nama as nama_pengganti,
                                          CASE 
                                            WHEN j.tanggal = '$today' AND j.jam_mulai <= '$now_time' THEN 1 
                                            ELSE 0 
                                          END as sudah_mulai
                                          FROM jadwal j 
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                          LEFT JOIN absen_asisten aa ON aa.jadwal_id = j.id AND aa.kode_asisten = '$kode_asisten'
                                          LEFT JOIN asisten ap ON aa.pengganti = ap.kode_asisten
                                          WHERE (j.tanggal > '$today' OR (j.tanggal = '$today' AND j.jam_selesai >= '$now_time'))
                                          AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                          ORDER BY j.tanggal, j.jam_mulai");

// Riwayat izin (hanya yang izin/sakit)
$riwayat_izin = mysqli_query($conn, "SELECT aa.*, j.tanggal, j.jam_mulai, j.jam_selesai, j.materi,
                                      k.nama_kelas, mk.nama_mk, ap.nama as nama_pengganti
                                      FROM absen_asisten aa
                                      JOIN jadwal j ON aa.jadwal_id = j.id
                                      LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                      LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                      LEFT JOIN asisten ap ON aa.pengganti = ap.kode_asisten
                                      WHERE aa.kode_asisten = '$kode_asisten'
                                      AND aa.status IN ('izin', 'sakit')
                                      ORDER BY j.tanggal DESC
                                      LIMIT 10");
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_asisten.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-user-clock me-2"></i>Pengajuan Izin/Sakit Asisten</h4>
                
                <?= show_alert() ?>
                
                <!-- Jadwal Mendatang -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-calendar-alt me-2"></i>Jadwal Mendatang
                    </div>
                    <div class="card-body p-2 p-md-3">
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
                                                    <?php elseif ($j['izin_status'] == 'izin'): ?>
                                                        <span class="badge bg-warning">Izin</span>
                                                        <?php if ($j['nama_pengganti']): ?>
                                                            <br><small class="text-muted">Pengganti: <?= $j['nama_pengganti'] ?></small>
                                                        <?php endif; ?>
                                                    <?php elseif ($j['izin_status'] == 'sakit'): ?>
                                                        <span class="badge bg-info">Sakit</span>
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
                                                        <a href="index.php?page=asisten_pengajuan_izin&batal=<?= $j['izin_id'] ?>" 
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Batalkan pengajuan izin?')">
                                                            <i class="fas fa-times"></i> Batal
                                                        </a>
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
                                                <?php if ($j['izin_status'] == 'hadir'): ?>
                                                    <span class="badge bg-success">Hadir</span>
                                                <?php elseif ($j['izin_status'] == 'izin'): ?>
                                                    <span class="badge bg-warning">Izin</span>
                                                <?php elseif ($j['izin_status'] == 'sakit'): ?>
                                                    <span class="badge bg-info">Sakit</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Belum</span>
                                                <?php endif; ?>
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
                                                    <a href="index.php?page=asisten_pengajuan_izin&batal=<?= $j['izin_id'] ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Batalkan pengajuan izin?')">
                                                        <i class="fas fa-times"></i> Batal
                                                    </a>
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
                                                    <?php if ($r['status'] == 'hadir'): ?>
                                                        <span class="badge bg-success">Hadir</span>
                                                    <?php elseif ($r['status'] == 'izin'): ?>
                                                        <span class="badge bg-warning">Izin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">Sakit</span>
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
                                                <?php if ($r['status'] == 'hadir'): ?>
                                                    <span class="badge bg-success">Hadir</span>
                                                <?php elseif ($r['status'] == 'izin'): ?>
                                                    <span class="badge bg-warning">Izin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Sakit</span>
                                                <?php endif; ?>
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

<?php include 'includes/footer.php'; ?>
