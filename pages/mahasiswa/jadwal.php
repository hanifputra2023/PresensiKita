<?php
$page = 'mahasiswa_jadwal';
$mahasiswa = get_mahasiswa_login();
$kelas = $mahasiswa['kode_kelas'];
$nim = $mahasiswa['nim'];

// Variabel waktu untuk cek status jadwal
$today = date('Y-m-d');
$now_time = date('H:i:s');
$toleransi_sebelum = TOLERANSI_SEBELUM;
$toleransi_sesudah = TOLERANSI_SESUDAH;

// Ambil jadwal MATERI dan UJIKOM (bukan inhall)
// Inhall hanya ditampilkan jika mahasiswa terdaftar di penggantian_inhall
$jadwal = mysqli_query($conn, "SELECT j.*, l.nama_lab, mk.nama_mk, p.status as presensi_status,
                                a1.nama as asisten1_nama, a2.nama as asisten2_nama,
                                (SELECT COUNT(*) FROM materi_perkuliahan mp WHERE mp.id_jadwal = j.id) as jumlah_materi
                                FROM jadwal j 
                                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = '$nim'
                                LEFT JOIN asisten a1 ON j.kode_asisten_1 = a1.kode_asisten
                                LEFT JOIN asisten a2 ON j.kode_asisten_2 = a2.kode_asisten
                                WHERE j.kode_kelas = '$kelas'
                                AND (
                                    j.jenis != 'inhall'
                                    OR EXISTS (
                                        SELECT 1 FROM penggantian_inhall pi 
                                        JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                        WHERE pi.nim = '$nim' 
                                        AND pi.status IN ('terdaftar', 'hadir')
                                        AND pi.status_approval = 'approved'
                                        AND jx.kode_mk = j.kode_mk
                                    )
                                )
                                ORDER BY j.tanggal, j.jam_mulai");

// Ambil tanggal daftar mahasiswa
$tanggal_daftar = $mahasiswa['tanggal_daftar'];
?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_mahasiswa.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4 pt-2"><i class="fas fa-calendar-alt me-2"></i>Jadwal Praktikum - <?= $mahasiswa['nama_kelas'] ?></h4>
                
                <div class="card">
                    <div class="card-body">
                        <!-- Desktop Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pertemuan</th>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Lab</th>
                                        <th>Mata Kuliah</th>
                                        <th>Asisten</th>
                                        <th>Materi</th>
                                        <th>Jenis</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($jadwal, 0);
                                    while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                        <?php 
                                        $is_past = strtotime($j['tanggal']) < strtotime($today);
                                        $is_today = $j['tanggal'] == $today;
                                        
                                        // Cek apakah jadwal sudah bisa diakses (dalam rentang toleransi)
                                        $waktu_buka = strtotime($j['jam_mulai']) - ($toleransi_sebelum * 60);
                                        $waktu_selesai = strtotime($j['jam_selesai']); // Jadwal berakhir tepat saat jam_selesai
                                        $waktu_sekarang = strtotime($now_time);
                                        
                                        $sudah_buka = $is_today && ($waktu_sekarang >= $waktu_buka);
                                        $sudah_selesai = $is_today && ($waktu_sekarang >= $waktu_selesai); // Berakhir tepat jam_selesai
                                        $sedang_aktif = $is_today && $sudah_buka && !$sudah_selesai;
                                        $belum_waktunya = $is_today && !$sudah_buka;
                                        $is_ended = $is_past || $sudah_selesai; // Jadwal sudah berakhir
                                        
                                        // Hitung sisa waktu
                                        $sisa_menit = 0;
                                        if ($belum_waktunya) {
                                            $sisa_menit = ceil(($waktu_buka - $waktu_sekarang) / 60);
                                        }
                                        
                                        // Cek apakah jadwal sebelum tanggal daftar mahasiswa
                                        $jadwal_sebelum_daftar = strtotime($j['tanggal']) < strtotime($tanggal_daftar);
                                        
                                        // Tentukan class row
                                        $row_class = '';
                                        if ($is_ended && !$jadwal_sebelum_daftar) {
                                            $row_class = 'text-muted';
                                        } elseif ($sedang_aktif) {
                                            $row_class = 'table-success';
                                        } elseif ($belum_waktunya) {
                                            $row_class = 'table-warning';
                                        } elseif ($is_today) {
                                            $row_class = 'table-primary';
                                        }
                                        ?>
                                        <tr class="<?= $row_class ?>">
                                            <td><span class="badge bg-secondary"><?= $j['pertemuan_ke'] ?></span></td>
                                            <td>
                                                <?= format_tanggal($j['tanggal']) ?>
                                                <?php if ($sedang_aktif): ?>
                                                    <span class="badge bg-success"><i class="fas fa-broadcast-tower me-1"></i>Aktif</span>
                                                <?php elseif ($belum_waktunya): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>Menunggu</span>
                                                <?php elseif ($is_today): ?>
                                                    <span class="badge bg-primary">Hari Ini</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></td>
                                            <td><?= $j['nama_lab'] ?></td>
                                            <td><?= $j['nama_mk'] ?></td>
                                            <td>
                                                <small>
                                                    <?= $j['asisten1_nama'] ?: '-' ?>
                                                    <?php if ($j['asisten2_nama']): ?>
                                                        <br><span class="text-muted"><?= $j['asisten2_nama'] ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($j['materi']) ?>
                                                <?php if ($j['jumlah_materi'] > 0): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary lihat-materi-btn" data-jadwal-id="<?= $j['id'] ?>" title="Lihat Materi">
                                                        <i class="fas fa-book-open"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $j['jenis'] == 'materi' ? 'info' : ($j['jenis'] == 'inhall' ? 'warning' : 'danger') ?>">
                                                    <?= ucfirst($j['jenis']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($j['presensi_status'] && $j['presensi_status'] != 'belum'): ?>
                                                    <span class="badge bg-<?= $j['presensi_status'] == 'hadir' ? 'success' : ($j['presensi_status'] == 'izin' ? 'warning' : ($j['presensi_status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                        <?= ucfirst($j['presensi_status']) ?>
                                                    </span>
                                                <?php elseif ($jadwal_sebelum_daftar): ?>
                                                    <span class="badge bg-secondary" title="Jadwal sebelum tanggal pendaftaran">-</span>
                                                <?php elseif ($is_ended): ?>
                                                    <span class="badge bg-danger">Alpha</span>
                                                <?php elseif ($sedang_aktif): ?>
                                                    <a href="index.php?page=mahasiswa_scanner" class="btn btn-sm btn-success">
                                                        <i class="fas fa-qrcode me-1"></i>Scan
                                                    </a>
                                                <?php elseif ($belum_waktunya): ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>
                                                        <i class="fas fa-lock me-1"></i>
                                                        <?php if ($sisa_menit >= 60): ?>
                                                            <?= floor($sisa_menit/60) ?>j <?= $sisa_menit % 60 ?>m
                                                        <?php else: ?>
                                                            <?= $sisa_menit ?>m lagi
                                                        <?php endif; ?>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Belum</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mobile Cards -->
                        <div class="d-md-none">
                            <?php 
                            mysqli_data_seek($jadwal, 0);
                            while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                <?php 
                                $is_past = strtotime($j['tanggal']) < strtotime($today);
                                $is_today = $j['tanggal'] == $today;
                                
                                // Cek apakah jadwal sudah bisa diakses
                                $waktu_buka = strtotime($j['jam_mulai']) - ($toleransi_sebelum * 60);
                                $waktu_selesai = strtotime($j['jam_selesai']); // Berakhir tepat jam_selesai
                                $waktu_sekarang = strtotime($now_time);
                                
                                $sudah_buka = $is_today && ($waktu_sekarang >= $waktu_buka);
                                $sudah_selesai = $is_today && ($waktu_sekarang >= $waktu_selesai);
                                $sedang_aktif = $is_today && $sudah_buka && !$sudah_selesai;
                                $belum_waktunya = $is_today && !$sudah_buka;
                                $is_ended = $is_past || $sudah_selesai;
                                
                                // Hitung sisa waktu
                                $sisa_menit = 0;
                                if ($belum_waktunya) {
                                    $sisa_menit = ceil(($waktu_buka - $waktu_sekarang) / 60);
                                }
                                
                                // Cek apakah jadwal sebelum tanggal daftar mahasiswa
                                $jadwal_sebelum_daftar = strtotime($j['tanggal']) < strtotime($tanggal_daftar);
                                
                                // Tentukan border dan style
                                $border_class = '';
                                $card_style = '';
                                if ($is_ended && !$jadwal_sebelum_daftar) {
                                    $card_style = 'opacity: 0.6;';
                                } elseif ($sedang_aktif) {
                                    $border_class = 'border-success';
                                    $card_style = 'border-left: 4px solid #66cc00 !important;';
                                } elseif ($belum_waktunya) {
                                    $border_class = 'border-warning';
                                    $card_style = 'border-left: 4px solid #ffaa00 !important; background: linear-gradient(to right, rgba(246, 194, 62, 0.08), transparent);';
                                } elseif ($is_today) {
                                    $border_class = 'border-primary';
                                }
                                ?>
                                <div class="card mb-3 <?= $border_class ?>" style="<?= $card_style ?>">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1"><?= $j['nama_mk'] ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($j['materi']) ?></small>
                                            </div>
                                            <span class="badge bg-secondary">P<?= $j['pertemuan_ke'] ?></span>
                                        </div>

                                        <?php if ($j['jumlah_materi'] > 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary lihat-materi-btn mb-2 w-100" data-jadwal-id="<?= $j['id'] ?>">
                                                <i class="fas fa-book-open me-1"></i>Lihat Materi
                                            </button>
                                        <?php endif; ?>

                                        <hr class="my-2">
                                        <div class="row small mb-1">
                                            <div class="col-6">
                                                <i class="fas fa-calendar me-1 text-muted"></i><?= format_tanggal($j['tanggal']) ?>
                                                <?php if ($sedang_aktif): ?>
                                                    <span class="badge bg-success ms-1"><i class="fas fa-broadcast-tower"></i></span>
                                                <?php elseif ($belum_waktunya): ?>
                                                    <span class="badge bg-warning text-dark ms-1"><i class="fas fa-hourglass-half"></i></span>
                                                <?php elseif ($is_today): ?>
                                                    <span class="badge bg-primary ms-1">Hari Ini</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-6 text-end">
                                                <i class="fas fa-clock me-1 text-muted"></i><?= format_waktu($j['jam_mulai']) ?>
                                            </div>
                                        </div>
                                        <div class="row small mb-2">
                                            <div class="col-6">
                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i><?= $j['nama_lab'] ?>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span class="badge bg-<?= $j['jenis'] == 'materi' ? 'info' : ($j['jenis'] == 'inhall' ? 'warning' : 'danger') ?>">
                                                    <?= ucfirst($j['jenis']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row small mb-2">
                                            <div class="col-12">
                                                <i class="fas fa-user-tie me-1 text-muted"></i>
                                                <span class="text-muted">Asisten:</span> 
                                                <?= $j['asisten1_nama'] ?: '-' ?>
                                                <?php if ($j['asisten2_nama']): ?>
                                                    , <?= $j['asisten2_nama'] ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-muted">Status:</span>
                                            <?php if ($j['presensi_status'] && $j['presensi_status'] != 'belum'): ?>
                                                <span class="badge bg-<?= $j['presensi_status'] == 'hadir' ? 'success' : ($j['presensi_status'] == 'izin' ? 'warning' : ($j['presensi_status'] == 'sakit' ? 'info' : 'danger')) ?>">
                                                    <?= ucfirst($j['presensi_status']) ?>
                                                </span>
                                            <?php elseif ($jadwal_sebelum_daftar): ?>
                                                <span class="badge bg-secondary" title="Jadwal sebelum tanggal pendaftaran">-</span>
                                            <?php elseif ($is_ended): ?>
                                                <span class="badge bg-danger">Alpha</span>
                                            <?php elseif ($sedang_aktif): ?>
                                                <a href="index.php?page=mahasiswa_scanner" class="btn btn-sm btn-success">
                                                    <i class="fas fa-qrcode me-1"></i>Scan Presensi
                                                </a>
                                            <?php elseif ($belum_waktunya): ?>
                                                <button class="btn btn-sm btn-secondary" disabled>
                                                    <i class="fas fa-lock me-1"></i>
                                                    <?php if ($sisa_menit >= 60): ?>
                                                        Buka <?= floor($sisa_menit/60) ?>j <?= $sisa_menit % 60 ?>m
                                                    <?php else: ?>
                                                        Buka <?= $sisa_menit ?>m lagi
                                                    <?php endif; ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Belum</span>
                                            <?php endif; ?>
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

<!-- Modal Lihat Materi -->
<div class="modal fade" id="materiModal" tabindex="-1" aria-labelledby="materiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="materiModalLabel"><i class="fas fa-book-open me-2"></i>Materi Perkuliahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="materiModalBody">
                <!-- Content will be loaded here via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
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
    const materiModal = new bootstrap.Modal(document.getElementById('materiModal'));
    const materiModalBody = document.getElementById('materiModalBody');

    document.querySelectorAll('.lihat-materi-btn').forEach(button => {
        button.addEventListener('click', function() {
            const jadwalId = this.getAttribute('data-jadwal-id');
            
            // Show modal and loading spinner
            materiModal.show();
            materiModalBody.innerHTML = `<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

            // Fetch materi content
            fetch(`api/get_materi_detail.php?jadwal_id=${jadwalId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    materiModalBody.innerHTML = html;
                })
                .catch(error => {
                    materiModalBody.innerHTML = `<div class="alert alert-danger">Gagal memuat materi. Silakan coba lagi.</div>`;
                    console.error('Error fetching materi:', error);
                });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
