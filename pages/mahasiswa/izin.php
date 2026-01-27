<?php
$page = 'mahasiswa_izin';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kelas = $mahasiswa['kode_kelas'];

// Proses hapus pengajuan (hanya yang masih pending)
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    
    // Cek apakah pengajuan milik mahasiswa ini dan masih pending - prepared statement
    $stmt_cek = mysqli_prepare($conn, "SELECT pi.*, j.id as jadwal_id FROM penggantian_inhall pi 
         JOIN jadwal j ON pi.jadwal_asli_id = j.id 
         WHERE pi.id = ? AND pi.nim = ? AND pi.status_approval = 'pending'");
    mysqli_stmt_bind_param($stmt_cek, "is", $hapus_id, $nim);
    mysqli_stmt_execute($stmt_cek);
    $cek_pengajuan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));
    
    if ($cek_pengajuan) {
        // Hapus file bukti jika ada
        if (!empty($cek_pengajuan['bukti_file'])) {
            $file_path = 'uploads/' . $cek_pengajuan['bukti_file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Hapus record presensi yang terkait (kembalikan ke status belum/kosong)
        $stmt_del_presensi = mysqli_prepare($conn, "DELETE FROM presensi_mahasiswa 
                             WHERE jadwal_id = ? AND nim = ? AND status IN ('izin', 'sakit')");
        mysqli_stmt_bind_param($stmt_del_presensi, "is", $cek_pengajuan['jadwal_asli_id'], $nim);
        mysqli_stmt_execute($stmt_del_presensi);
        
        // Hapus pengajuan - prepared statement
        $stmt_del_pengajuan = mysqli_prepare($conn, "DELETE FROM penggantian_inhall WHERE id = ?");
        mysqli_stmt_bind_param($stmt_del_pengajuan, "i", $hapus_id);
        mysqli_stmt_execute($stmt_del_pengajuan);
        
        log_aktivitas($_SESSION['user_id'], 'HAPUS_PENGAJUAN', 'penggantian_inhall', $hapus_id, 
                      "Mahasiswa $nim menghapus pengajuan izin/sakit");
        set_alert('success', 'Pengajuan berhasil dihapus!');
    } else {
        set_alert('danger', 'Pengajuan tidak ditemukan atau sudah diproses!');
    }
    
    header("Location: index.php?page=mahasiswa_izin");
    exit;
}

// Proses pengajuan izin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $status = escape($_POST['status']); // izin atau sakit
    $alasan = escape($_POST['alasan']);
    
    // Cek sudah ada presensi - prepared statement
    $stmt_cek_presensi = mysqli_prepare($conn, "SELECT * FROM presensi_mahasiswa WHERE jadwal_id = ? AND nim = ?");
    mysqli_stmt_bind_param($stmt_cek_presensi, "is", $jadwal_id, $nim);
    mysqli_stmt_execute($stmt_cek_presensi);
    $cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_presensi));
    
    // Jika sudah ada record dan bukan status 'belum', tolak
    if ($cek && $cek['status'] != 'belum') {
        set_alert('danger', 'Anda sudah melakukan presensi untuk jadwal ini!');
    } else {
        // Handle upload bukti
        $bukti_file = null;
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $filename = $_FILES['bukti']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed) && $_FILES['bukti']['size'] <= 5 * 1024 * 1024) { // Max 5MB
                $new_filename = 'bukti_' . $nim . '_' . time() . '.' . $ext;
                
                // Pastikan folder uploads ada
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                $upload_path = 'uploads/' . $new_filename;
                
                if (move_uploaded_file($_FILES['bukti']['tmp_name'], $upload_path)) {
                    $bukti_file = $new_filename;
                }
            } else {
                set_alert('danger', 'File bukti tidak valid! Gunakan JPG/PNG/PDF, maksimal 5MB.');
                header("Location: index.php?page=mahasiswa_izin");
                exit;
            }
        }
        
        // Update atau insert presensi dengan status LANGSUNG izin/sakit - prepared statement
        if ($cek && $cek['status'] == 'belum') {
            // Update record yang sudah ada
            $stmt_upd = mysqli_prepare($conn, "UPDATE presensi_mahasiswa SET status = ?, waktu_presensi = NOW(), metode = 'manual' WHERE jadwal_id = ? AND nim = ?");
            mysqli_stmt_bind_param($stmt_upd, "sis", $status, $jadwal_id, $nim);
            mysqli_stmt_execute($stmt_upd);
        } else {
            // Insert record baru dengan status izin/sakit
            $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, waktu_presensi) VALUES (?, ?, ?, 'manual', NOW())");
            mysqli_stmt_bind_param($stmt_ins, "iss", $jadwal_id, $nim, $status);
            mysqli_stmt_execute($stmt_ins);
        }
        
        // Simpan ke penggantian_inhall dengan status pending (untuk approval asisten) - prepared statement
        $stmt_inhall = mysqli_prepare($conn, "INSERT INTO penggantian_inhall (nim, jadwal_asli_id, alasan_izin, bukti_file, status_approval, materi_diulang) VALUES (?, ?, ?, ?, 'pending', ?)");
        mysqli_stmt_bind_param($stmt_inhall, "sisss", $nim, $jadwal_id, $alasan, $bukti_file, $status);
        mysqli_stmt_execute($stmt_inhall);
        
        $last_id = mysqli_insert_id($conn);
        
        log_aktivitas($_SESSION['user_id'], 'PENGAJUAN_IZIN', 'penggantian_inhall', $last_id, "Mahasiswa $nim mengajukan $status (pending approval): $alasan");
        
        // [FITUR BARU] Kirim Notifikasi ke Asisten (Real)
        // Ambil nomor HP asisten 1 dari jadwal tersebut DAN detail jadwal
        $q_info = mysqli_query($conn, "SELECT a.no_hp, a.nama as nama_asisten, mk.nama_mk, j.tanggal, j.jam_mulai 
                                          FROM jadwal j 
                                          LEFT JOIN asisten a ON j.kode_asisten_1 = a.kode_asisten 
                                          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                          WHERE j.id = '$jadwal_id'");
        $d_info = mysqli_fetch_assoc($q_info);
        
        // Validasi nomor HP: harus ada, tidak kosong, dan bukan '-'
        $no_hp_asisten = ($d_info && isset($d_info['no_hp'])) ? trim($d_info['no_hp']) : '';
        $is_valid_hp = !empty($no_hp_asisten) && $no_hp_asisten !== '-' && strlen($no_hp_asisten) > 5;
        
        // Gunakan nomor asisten jika valid, jika tidak gunakan nomor default/admin
        $target_wa = $is_valid_hp ? $no_hp_asisten : '6283841426400';
        $nama_sapaan = ($d_info && !empty($d_info['nama_asisten'])) ? "Asisten " . $d_info['nama_asisten'] : "Admin";
        
        // Format Pesan Lengkap
        $pesan_wa = "Halo $nama_sapaan,\n\n";
        $pesan_wa .= "Ada pengajuan *" . strtoupper($status) . "* baru:\n";
        $pesan_wa .= "👤 Nama: " . $mahasiswa['nama'] . "\n";
        $pesan_wa .= "🆔 NIM: " . $nim . "\n";
        $pesan_wa .= "🎓 Kelas: " . $mahasiswa['nama_kelas'] . "\n\n";
        $pesan_wa .= "📚 Matkul: " . ($d_info['nama_mk'] ?? '-') . "\n";
        $pesan_wa .= "📅 Jadwal: " . format_tanggal($d_info['tanggal']) . " (" . format_waktu($d_info['jam_mulai']) . ")\n";
        $pesan_wa .= "📝 Alasan: " . $alasan . "\n\n";
        $pesan_wa .= "Mohon cek dashboard untuk validasi.";
        
        kirim_notifikasi($target_wa, $pesan_wa);
        
        // Tampilkan info nomor tujuan di alert agar mudah dicek saat testing
        set_alert('success', 'Pengajuan ' . $status . ' berhasil! Notifikasi WA dikirim ke: ' . $target_wa);
    }
    
    header("Location: index.php?page=mahasiswa_izin");
    exit;
}

// Jadwal yang bisa diajukan izin (hari ini atau besok)
// Exclude jadwal yang sudah ada status selain 'belum' - prepared statement
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$stmt_jadwal = mysqli_prepare($conn, "SELECT j.*, mk.nama_mk FROM jadwal j 
                                          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                          LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = ?
                                          WHERE j.kode_kelas = ? 
                                          AND j.tanggal BETWEEN ? AND ?
                                          AND (p.status IS NULL OR p.status = 'belum')
                                          ORDER BY j.tanggal, j.jam_mulai");
mysqli_stmt_bind_param($stmt_jadwal, "ssss", $nim, $kelas, $today, $tomorrow);
mysqli_stmt_execute($stmt_jadwal);
$jadwal_available = mysqli_stmt_get_result($stmt_jadwal);

// Pagination untuk riwayat izin - prepared statement
$per_page = 10;
$current_page = get_current_page();

$stmt_count = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM penggantian_inhall WHERE nim = ?");
mysqli_stmt_bind_param($stmt_count, "s", $nim);
mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
$total_data = mysqli_fetch_assoc($count_result)['total'];
$total_pages = get_total_pages($total_data, $per_page);
$offset = get_offset($current_page, $per_page);

// Riwayat izin (termasuk yang pending) - prepared statement
$stmt_riwayat = mysqli_prepare($conn, "SELECT p.*, j.tanggal, j.materi, mk.nama_mk, 
                                      pi.id as pengajuan_id, pi.alasan_izin, pi.status as inhall_status,
                                      pi.status_approval, pi.alasan_reject, pi.materi_diulang as jenis_pengajuan
                                      FROM penggantian_inhall pi
                                      JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                      LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                      LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = pi.nim
                                      WHERE pi.nim = ?
                                      ORDER BY pi.tanggal_daftar DESC
                                      LIMIT ?, ?");
mysqli_stmt_bind_param($stmt_riwayat, "sii", $nim, $offset, $per_page);
mysqli_stmt_execute($stmt_riwayat);
$riwayat_izin = mysqli_stmt_get_result($stmt_riwayat);
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-envelope me-2"></i>Pengajuan Izin/Sakit</h4>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <!-- Form Izin -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-plus me-2"></i>Ajukan Izin
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($jadwal_available) > 0): ?>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label class="form-label">Pilih Jadwal</label>
                                            <select name="jadwal_id" class="form-select" required>
                                                <option value="">-- Pilih Jadwal --</option>
                                                <?php while ($j = mysqli_fetch_assoc($jadwal_available)): ?>
                                                    <option value="<?= $j['id'] ?>">
                                                        <?= format_tanggal($j['tanggal']) ?> - <?= $j['nama_mk'] ?> (<?= $j['materi'] ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Jenis</label>
                                            <select name="status" class="form-select" id="statusIzin" required onchange="toggleBuktiRequired()">
                                                <option value="izin">Izin</option>
                                                <option value="sakit">Sakit</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Alasan</label>
                                            <textarea name="alasan" class="form-control" rows="3" required placeholder="Jelaskan alasan izin/sakit..."></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Bukti <span id="buktiLabel" class="text-muted">(Surat Izin/Surat Dokter)</span>
                                            </label>
                                            <input type="file" name="bukti" id="buktiFile" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf">
                                            <small class="text-muted">Format: JPG, PNG, PDF. Maksimal 5MB. <span id="buktiNote" class="text-danger">*Wajib untuk sakit</span></small>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Kirim Pengajuan
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Tidak ada jadwal yang dapat diajukan izin</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Riwayat Izin -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-history me-2"></i>Riwayat Izin/Sakit
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($riwayat_izin) > 0): ?>
                                    
                                    <!-- Desktop View -->
                                    <ul class="list-group list-group-flush d-none d-md-block">
                                        <?php 
                                        $riwayat_data = [];
                                        while ($r = mysqli_fetch_assoc($riwayat_izin)) {
                                            $riwayat_data[] = $r;
                                        }
                                        foreach ($riwayat_data as $r): 
                                        ?>
                                            <li class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong><?= $r['nama_mk'] ?></strong>
                                                        <br><small class="text-muted"><?= format_tanggal($r['tanggal']) ?> - <?= $r['materi'] ?></small>
                                                        <br><small><?= $r['alasan_izin'] ?></small>
                                                        <?php if ($r['status_approval'] == 'rejected' && $r['alasan_reject']): ?>
                                                            <br><small class="text-danger"><i class="fas fa-times-circle me-1"></i>Ditolak: <?= $r['alasan_reject'] ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-end">
                                                        <?php 
                                                        $jenis = $r['jenis_pengajuan'] ?: $r['status'];
                                                        ?>
                                                        <span class="badge bg-<?= $jenis == 'izin' ? 'warning' : 'info' ?>">
                                                            <?= ucfirst($jenis) ?>
                                                        </span>
                                                        <br>
                                                        <?php if ($r['status_approval'] == 'pending'): ?>
                                                            <span class="badge bg-secondary mt-1">Menunggu Approval</span>
                                                            <br>
                                                            <a href="index.php?page=mahasiswa_izin&hapus=<?= $r['pengajuan_id'] ?>" 
                                                               class="btn btn-outline-danger btn-sm mt-1"
                                                               onclick="return confirm('Yakin ingin menghapus pengajuan ini?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        <?php elseif ($r['status_approval'] == 'rejected'): ?>
                                                            <span class="badge bg-danger mt-1">Ditolak</span>
                                                        <?php elseif ($r['status_approval'] == 'approved'): ?>
                                                            <span class="badge bg-success mt-1">Disetujui</span>
                                                            <br>
                                                            <small class="text-<?= $r['inhall_status'] == 'hadir' ? 'success' : 'muted' ?>">
                                                                <?= $r['inhall_status'] == 'hadir' ? 'Sudah Inhall' : 'Belum Inhall' ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <!-- Mobile View -->
                                    <div class="d-md-none">
                                        <?php foreach ($riwayat_data as $r): 
                                            $jenis = $r['jenis_pengajuan'] ?: $r['status'];
                                        ?>
                                            <div class="card mb-3 border <?= $r['status_approval'] == 'pending' ? 'border-warning' : ($r['status_approval'] == 'rejected' ? 'border-danger' : 'border-success') ?>">
                                                <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light">
                                                    <span class="badge bg-<?= $jenis == 'izin' ? 'warning' : 'info' ?>">
                                                        <?= ucfirst($jenis) ?>
                                                    </span>
                                                    <?php if ($r['status_approval'] == 'pending'): ?>
                                                        <span class="badge bg-secondary">Menunggu</span>
                                                    <?php elseif ($r['status_approval'] == 'rejected'): ?>
                                                        <span class="badge bg-danger">Ditolak</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Disetujui</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body py-2">
                                                    <h6 class="card-title mb-1"><?= $r['nama_mk'] ?></h6>
                                                    <p class="card-text small text-muted mb-1">
                                                        <i class="fas fa-calendar me-1"></i><?= format_tanggal($r['tanggal']) ?>
                                                    </p>
                                                    <p class="card-text small mb-2">
                                                        <i class="fas fa-book me-1 text-muted"></i><?= $r['materi'] ?>
                                                    </p>
                                                    <p class="card-text small mb-2">
                                                        <i class="fas fa-comment me-1 text-muted"></i><?= $r['alasan_izin'] ?>
                                                    </p>
                                                    
                                                    <?php if ($r['status_approval'] == 'rejected' && $r['alasan_reject']): ?>
                                                        <div class="alert alert-danger py-1 px-2 mb-2 small">
                                                            <i class="fas fa-times-circle me-1"></i>Ditolak: <?= $r['alasan_reject'] ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($r['status_approval'] == 'approved'): ?>
                                                        <p class="card-text small mb-0">
                                                            <i class="fas fa-sync me-1"></i>
                                                            <span class="text-<?= $r['inhall_status'] == 'hadir' ? 'success' : 'muted' ?>">
                                                                <?= $r['inhall_status'] == 'hadir' ? 'Sudah Inhall' : 'Belum Inhall' ?>
                                                            </span>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($r['status_approval'] == 'pending'): ?>
                                                        <div class="text-end mt-2">
                                                            <a href="index.php?page=mahasiswa_izin&hapus=<?= $r['pengajuan_id'] ?>" 
                                                               class="btn btn-outline-danger btn-sm"
                                                               onclick="return confirm('Yakin ingin menghapus pengajuan ini?')">
                                                                <i class="fas fa-trash-alt me-1"></i>Hapus
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <div class="d-flex flex-column align-items-center mt-3 gap-2">
                                        <?= render_pagination_info($current_page, $per_page, $total_data) ?>
                                        <?= render_pagination($current_page, $total_pages, 'index.php?page=mahasiswa_izin', []) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada riwayat izin/sakit</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function toggleBuktiRequired() {
    var status = document.getElementById('statusIzin').value;
    var buktiFile = document.getElementById('buktiFile');
    var buktiLabel = document.getElementById('buktiLabel');
    var buktiNote = document.getElementById('buktiNote');
    
    if (status === 'sakit') {
        buktiFile.required = true;
        buktiLabel.innerHTML = '(Surat Dokter)';
        buktiNote.innerHTML = '*Wajib untuk sakit';
        buktiNote.style.display = 'inline';
    } else {
        buktiFile.required = false;
        buktiLabel.innerHTML = '(Surat Izin - Opsional)';
        buktiNote.innerHTML = '*Opsional';
        buktiNote.className = 'text-muted';
    }
}
// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleBuktiRequired);
</script>
