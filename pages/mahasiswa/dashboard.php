<?php
$page = 'mahasiswa_dashboard';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kelas = $mahasiswa['kode_kelas'];

// Jadwal hari ini yang SEDANG AKTIF (dalam rentang waktu)
$today = date('Y-m-d');
$now_time = date('H:i:s');
$toleransi_sebelum = TOLERANSI_SEBELUM; // menit sebelum jam_mulai
$toleransi_sesudah = TOLERANSI_SESUDAH; // menit setelah jam_selesai

// Jadwal aktif = sudah masuk waktu mulai (dengan toleransi sebelum) DAN belum lewat jam_selesai (TANPA toleransi)
// Langsung hilang begitu jam_selesai tercapai
// Inhall hanya ditampilkan untuk mahasiswa yang terdaftar di penggantian_inhall
$jadwal_hari_ini = mysqli_query($conn, "SELECT j.*, l.nama_lab, mk.nama_mk, p.status as presensi_status,
                                        a1.nama as asisten1_nama, a2.nama as asisten2_nama
                                        FROM jadwal j 
                                        LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                        LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                        LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = '$nim'
                                        LEFT JOIN asisten a1 ON j.kode_asisten_1 = a1.kode_asisten
                                        LEFT JOIN asisten a2 ON j.kode_asisten_2 = a2.kode_asisten
                                        WHERE j.tanggal = CURDATE() AND j.kode_kelas = '$kelas'
                                        AND SUBTIME(j.jam_mulai, SEC_TO_TIME($toleransi_sebelum * 60)) <= CURTIME()
                                        AND j.jam_selesai > CURTIME()
                                        AND (
                                            j.jenis != 'inhall'
                                            OR EXISTS (
                                                SELECT 1 FROM penggantian_inhall pi 
                                                JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                                WHERE pi.nim = '$nim' 
                                                AND pi.status IN ('terdaftar', 'hadir')
                                                AND jx.kode_mk = j.kode_mk
                                            )
                                            OR p.id IS NOT NULL
                                        )
                                        ORDER BY j.jam_mulai");

// Statistik presensi kumulatif (tidak hitung jadwal mendatang), dengan perhitungan alpha yang akurat
// EXCLUDE jadwal inhall dari statistik (inhall bersifat opsional)
$stat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
                                                 SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                                                 SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
                                                 SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                                                 SUM(CASE 
                                                    WHEN j.jenis != 'inhall' 
                                                         AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() 
                                                         AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) 
                                                    THEN 1 ELSE 0 END) as alpha,
                                                 SUM(CASE 
                                                    WHEN (j.jenis != 'inhall' AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()) 
                                                         OR p.status IS NOT NULL 
                                                    THEN 1 ELSE 0 END) as total
                                                 FROM jadwal j
                                                 LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND p.nim = '$nim'
                                                 WHERE j.kode_kelas = '$kelas'"));

// Jadwal terdekat (5 jadwal mendatang) - termasuk jadwal HARI INI yang belum aktif
// PERBAIKAN: Tampilkan jadwal hari ini yang jam_mulai-nya masih akan datang (belum masuk waktu aktif)
// DAN jadwal di hari-hari mendatang
// Inhall hanya ditampilkan untuk mahasiswa yang terdaftar di penggantian_inhall
$jadwal_terdekat = mysqli_query($conn, "SELECT j.*, l.nama_lab, mk.nama_mk, p.status as presensi_status,
                                         a1.nama as asisten1_nama, a2.nama as asisten2_nama
                                         FROM jadwal j 
                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                         LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                         LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = '$nim'
                                         LEFT JOIN asisten a1 ON j.kode_asisten_1 = a1.kode_asisten
                                         LEFT JOIN asisten a2 ON j.kode_asisten_2 = a2.kode_asisten
                                         WHERE j.kode_kelas = '$kelas'
                                         AND (
                                             j.tanggal > CURDATE()
                                             OR (
                                                 j.tanggal = CURDATE() 
                                                 AND SUBTIME(j.jam_mulai, SEC_TO_TIME($toleransi_sebelum * 60)) > CURTIME()
                                             )
                                         )
                                         AND (
                                             j.jenis != 'inhall'
                                             OR EXISTS (
                                                 SELECT 1 FROM penggantian_inhall pi 
                                                 JOIN jadwal jx ON pi.jadwal_asli_id = jx.id
                                                 WHERE pi.nim = '$nim' 
                                                 AND pi.status IN ('terdaftar', 'hadir')
                                                 AND jx.kode_mk = j.kode_mk
                                             )
                                             OR p.id IS NOT NULL
                                         )
                                         ORDER BY j.tanggal, j.jam_mulai LIMIT 5");

// Greeting berdasarkan waktu
$hour = date('H');
if ($hour < 12) {
    $greeting = "Selamat Pagi";
    $greeting_icon = "sun";
} elseif ($hour < 15) {
    $greeting = "Selamat Siang";
    $greeting_icon = "sun";
} elseif ($hour < 18) {
    $greeting = "Selamat Sore";
    $greeting_icon = "cloud-sun";
} else {
    $greeting = "Selamat Malam";
    $greeting_icon = "moon";
}

// Hitung persentase kehadiran
$total = $stat['total'] ?: 1;
$persen = round((($stat['hadir'] ?: 0) / $total) * 100);
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== MAHASISWA DASHBOARD MODERN STYLE ===== */
.dashboard-content {
    padding: 24px;
    max-width: 1400px;
}

/* Welcome Banner */
.welcome-banner {
    background: var(--banner-gradient);
    border-radius: 20px;
    padding: 0;
    color: white;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr auto;
    min-height: 160px;
}
.welcome-banner::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(78, 115, 223, 0.4) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}
.welcome-banner::after {
    content: '';
    position: absolute;
    bottom: -120px;
    left: -80px;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(28, 200, 138, 0.25) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite 3s;
}
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}
.welcome-content {
    padding: 28px 32px;
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.welcome-content .greeting {
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 4px;
    letter-spacing: 0.5px;
}
.welcome-content h1 {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.15);
}
.welcome-content .info-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 4px;
}
.welcome-content .info-badge {
    background: rgba(255,255,255,0.2);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    backdrop-filter: blur(5px);
}
.welcome-stats {
    padding: 28px 32px;
    display: flex;
    align-items: center;
    position: relative;
    z-index: 2;
}
.stats-glass {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 18px 24px;
    display: flex;
    gap: 28px;
    border: 1px solid rgba(255,255,255,0.2);
}
.stats-glass .stat-item {
    text-align: center;
}
.stats-glass .stat-num {
    font-size: 1.5rem;
    font-weight: 700;
    display: block;
    line-height: 1;
}
.stats-glass .stat-label {
    font-size: 0.7rem;
    opacity: 0.85;
    margin-top: 4px;
}

/* Jadwal Aktif Alert */
.jadwal-aktif-alert {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 1px solid #66cc00;
    border-left: 4px solid #66cc00;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
}
.jadwal-aktif-alert .pulse-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #66cc00, #17a673);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.4rem;
    animation: pulse 2s infinite;
    flex-shrink: 0;
}
@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(28, 200, 138, 0.4); }
    50% { box-shadow: 0 0 0 15px rgba(28, 200, 138, 0); }
}
.jadwal-aktif-alert .jadwal-info {
    flex: 1;
    min-width: 0;
}
.jadwal-aktif-alert .jadwal-info h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #155724;
    margin: 0 0 4px 0;
}
.jadwal-aktif-alert .jadwal-info p {
    font-size: 0.85rem;
    color: #155724;
    margin: 0;
    opacity: 0.85;
}
.jadwal-aktif-alert .jadwal-info .meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
    font-size: 0.8rem;
    color: #155724;
}
.jadwal-aktif-alert .btn-scan {
    background: linear-gradient(135deg, #66cc00, #17a673);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    flex-shrink: 0;
}
.jadwal-aktif-alert .btn-scan:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(28, 200, 138, 0.4);
    color: white;
}
.jadwal-aktif-alert .status-done {
    background: #66cc00;
    color: white;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.85rem;
    flex-shrink: 0;
}

/* Stat Cards Grid */
.stat-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: var(--bg-card);
    border-radius: 14px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    transition: all 0.25s ease;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.stat-icon.hadir { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #66cc00; }
.stat-icon.izin { background: linear-gradient(135deg, #cce5ff, #b8daff); color: #0066cc; }
.stat-icon.sakit { background: linear-gradient(135deg, #fff3cd, #ffeeba); color: #ffaa00; }
.stat-icon.alpha { background: linear-gradient(135deg, #f8d7da, #f5c6cb); color: #ff3333; }
.stat-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
    line-height: 1;
}
.stat-info p {
    color: var(--text-muted);
    margin: 4px 0 0 0;
    font-size: 0.75rem;
}

/* Main Grid */
.main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

/* Card Box */
.card-box {
    background: var(--bg-card);
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
}
.card-box .card-header-custom {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-body);
}
.card-box .card-header-custom h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-box .card-header-custom h3 i {
    color: #0066cc;
}
.card-box .card-body-custom {
    padding: 20px 24px;
}

/* Ring Chart */
.ring-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 0;
}
.ring-chart {
    position: relative;
    width: 140px;
    height: 140px;
}
.ring-chart svg {
    transform: rotate(-90deg);
    width: 140px;
    height: 140px;
}
.ring-bg {
    fill: none;
    stroke: var(--border-color);
    stroke-width: 10;
}
.ring-progress {
    fill: none;
    stroke-width: 10;
    stroke-linecap: round;
    transition: stroke-dashoffset 0.6s ease;
}
.ring-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}
.ring-text .persen {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
}
.ring-text .label {
    font-size: 0.7rem;
    color: var(--text-muted);
}

/* Quick Actions */
.quick-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
.quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px 14px;
    background: var(--bg-body);
    border-radius: 14px;
    text-decoration: none;
    color: var(--text-main);
    transition: all 0.25s ease;
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
}
.quick-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.quick-btn.scan::before { background: linear-gradient(135deg, #66cc00, #17a673); }
.quick-btn.riwayat::before { background: linear-gradient(135deg, #0066cc, #0099ff); }
.quick-btn.jadwal::before { background: linear-gradient(135deg, #00ccff, #258391); }
.quick-btn.izin::before { background: linear-gradient(135deg, #ffaa00, #dda20a); }
.quick-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    color: white;
}
.quick-btn:hover::before {
    opacity: 1;
}
.quick-btn i, .quick-btn span {
    position: relative;
    z-index: 1;
}
.quick-btn i {
    font-size: 1.4rem;
    margin-bottom: 8px;
    transition: color 0.25s;
}
.quick-btn.scan i { color: #66cc00; }
.quick-btn.riwayat i { color: #0066cc; }
.quick-btn.jadwal i { color: #00ccff; }
.quick-btn.izin i { color: #ffaa00; }
.quick-btn:hover i {
    color: white;
}
.quick-btn span {
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
}

/* Jadwal List */
.jadwal-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.jadwal-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px;
    background: var(--bg-body);
    border-radius: 12px;
    transition: all 0.2s;
    border: 1px solid transparent;
}
.jadwal-item:hover {
    background: var(--bg-card);
    border-color: #0066cc;
    box-shadow: 0 4px 12px rgba(78, 115, 223, 0.1);
}
.jadwal-item.today {
    border-left: 3px solid #66cc00;
    background: linear-gradient(to right, rgba(28, 200, 138, 0.05), #f8f9fc);
}
.jadwal-date {
    text-align: center;
    min-width: 50px;
    flex-shrink: 0;
}
.jadwal-date .day {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0066cc;
    line-height: 1;
}
.jadwal-date .month {
    font-size: 0.65rem;
    color: var(--text-muted);
    text-transform: uppercase;
}
.jadwal-info-item {
    flex: 1;
    min-width: 0;
}
.jadwal-info-item h4 {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 4px 0;
    color: var(--text-main);
}
.jadwal-info-item .meta {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.jadwal-info-item .asisten {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-top: 4px;
}
.jadwal-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    flex-shrink: 0;
}
.jadwal-badge {
    font-size: 0.65rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 500;
}
.jadwal-badge.materi { background: #cce5ff; color: #0066cc; }
.jadwal-badge.inhall { background: #fff3cd; color: #856404; }
.jadwal-badge.praresponsi { background: #f8d7da; color: #721c24; }
.jadwal-badge.responsi { background: #f8d7da; color: #721c24; }
.jadwal-badge.ujikom { background: #f8d7da; color: #721c24; }
.status-badge {
    font-size: 0.65rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 500;
}
.status-badge.hadir { background: #d4edda; color: #155724; }
.status-badge.izin { background: #cce5ff; color: #004085; }
.status-badge.sakit { background: #fff3cd; color: #856404; }
.status-badge.alpha { background: #f8d7da; color: #721c24; }
.status-badge.belum { background: #e2e3e5; color: #383d41; }
.status-badge.menunggu { background: #fff3cd; color: #856404; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.5;
}
.empty-state p {
    margin: 0;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .main-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 992px) {
    .stat-cards-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 768px) {
    .dashboard-content {
        padding: 16px;
    }
    .welcome-banner {
        grid-template-columns: 1fr;
        min-height: auto;
    }
    .welcome-content {
        padding: 20px 24px;
    }
    .welcome-content h1 {
        font-size: 1.3rem;
    }
    .welcome-stats {
        padding: 0 24px 20px;
    }
    .stats-glass {
        width: 100%;
        justify-content: space-around;
        gap: 16px;
        padding: 14px 20px;
    }
    .stats-glass .stat-num {
        font-size: 1.25rem;
    }
    .jadwal-aktif-alert {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    .jadwal-aktif-alert .jadwal-info .meta {
        justify-content: center;
    }
    .stat-cards-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .stat-card {
        padding: 16px;
    }
    .stat-icon {
        width: 42px;
        height: 42px;
        font-size: 1rem;
    }
    .stat-info h3 {
        font-size: 1.25rem;
    }
    .main-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .quick-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    .quick-btn {
        padding: 14px 10px;
    }
    .quick-btn i {
        font-size: 1.2rem;
        margin-bottom: 6px;
    }
    .quick-btn span {
        font-size: 0.7rem;
    }
    .jadwal-item {
        flex-direction: column;
        gap: 10px;
    }
    .jadwal-date {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .jadwal-status {
        flex-direction: row;
        width: 100%;
        justify-content: flex-start;
    }
    .ring-chart {
        width: 110px;
        height: 110px;
    }
    .ring-chart svg {
        width: 110px;
        height: 110px;
    }
    .ring-text .persen {
        font-size: 1.4rem;
    }
}
@media (max-width: 480px) {
    .stat-cards-grid {
        grid-template-columns: 1fr 1fr;
    }
    .quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .welcome-content .info-badges {
        gap: 8px;
    }
    .welcome-content .info-badge {
        font-size: 0.7rem;
        padding: 5px 10px;
    }
}

/* Dark Mode Fixes */
[data-theme="dark"] .welcome-banner {
    background: var(--banner-gradient);
}
[data-theme="dark"] .jadwal-aktif-alert {
    background: rgba(40, 167, 69, 0.15);
    border-color: rgba(40, 167, 69, 0.3);
    border-left-color: #2ecc71;
}
[data-theme="dark"] .jadwal-aktif-alert .jadwal-info h4,
[data-theme="dark"] .jadwal-aktif-alert .jadwal-info p,
[data-theme="dark"] .jadwal-aktif-alert .jadwal-info .meta {
    color: var(--text-main);
}
[data-theme="dark"] .jadwal-aktif-alert .pulse-icon {
    background: rgba(40, 167, 69, 0.2);
    color: #2ecc71;
    box-shadow: none;
}

[data-theme="dark"] .stat-icon.hadir { background: rgba(40, 167, 69, 0.2); color: #2ecc71; }
[data-theme="dark"] .stat-icon.izin { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
[data-theme="dark"] .stat-icon.sakit { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }
[data-theme="dark"] .stat-icon.alpha { background: rgba(220, 53, 69, 0.2); color: #dc3545; }

[data-theme="dark"] .jadwal-item:hover {
    background-color: rgba(255,255,255,0.05);
    border-color: #66b0ff;
}
[data-theme="dark"] .jadwal-item.today {
    background: linear-gradient(to right, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05));
    border-left-color: #2ecc71;
}
[data-theme="dark"] .jadwal-time {
    background: rgba(0, 102, 204, 0.3);
    color: #66b0ff;
}

[data-theme="dark"] .jadwal-badge.materi { background: rgba(13, 110, 253, 0.2); color: #6ea8fe; }
[data-theme="dark"] .jadwal-badge.inhall { background: rgba(255, 193, 7, 0.2); color: #ffda6a; }
[data-theme="dark"] .jadwal-badge.praresponsi { background: rgba(13, 202, 240, 0.2); color: #6edff6; }
[data-theme="dark"] .jadwal-badge.responsi { background: rgba(220, 53, 69, 0.2); color: #ea868f; }
[data-theme="dark"] .jadwal-badge.ujikom { background: rgba(220, 53, 69, 0.2); color: #ea868f; }

[data-theme="dark"] .status-badge.hadir { background: rgba(40, 167, 69, 0.2); color: #2ecc71; }
[data-theme="dark"] .status-badge.izin { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
[data-theme="dark"] .status-badge.sakit { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }
[data-theme="dark"] .status-badge.alpha { background: rgba(220, 53, 69, 0.2); color: #dc3545; }
[data-theme="dark"] .status-badge.belum { background: rgba(255, 255, 255, 0.1); color: #a0aec0; }
[data-theme="dark"] .status-badge.menunggu { background: rgba(255, 193, 7, 0.2); color: #ffc107; }

[data-theme="dark"] .quick-btn {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}
[data-theme="dark"] .quick-btn.scan i { color: #2ecc71; }
[data-theme="dark"] .quick-btn.riwayat i { color: #66b0ff; }
[data-theme="dark"] .quick-btn.jadwal i { color: #33d6ff; }
[data-theme="dark"] .quick-btn.izin i { color: #ffc107; }
[data-theme="dark"] .quick-btn:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    border-color: transparent;
    color: #ffffff;
}
[data-theme="dark"] .quick-btn:hover i {
    color: #ffffff;
}

[data-theme="dark"] .card-box {
    background: var(--bg-card);
    border-color: var(--border-color);
}
[data-theme="dark"] .card-header-custom {
    background: rgba(0, 0, 0, 0.2);
    border-bottom-color: var(--border-color);
}
[data-theme="dark"] .card-header-custom h3 {
    color: var(--text-main);
}
[data-theme="dark"] .card-body-custom {
    color: var(--text-main);
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar_mahasiswa.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="dashboard-content">
                <?= show_alert() ?>
                
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <div class="greeting"><i class="fas fa-<?= $greeting_icon ?> me-2"></i><?= $greeting ?>,</div>
                        <h1><?= $mahasiswa['nama'] ?></h1>
                        <div class="info-badges">
                            <span class="info-badge"><i class="fas fa-id-card me-1"></i><?= $mahasiswa['nim'] ?></span>
                            <span class="info-badge"><i class="fas fa-users me-1"></i>Kelas <?= $mahasiswa['nama_kelas'] ?></span>
                            <span class="info-badge"><i class="fas fa-calendar-alt me-1"></i><?= format_tanggal($today) ?></span>
                        </div>
                    </div>
                    <div class="welcome-stats">
                        <div class="stats-glass">
                            <div class="stat-item">
                                <span class="stat-num"><?= $stat['hadir'] ?: 0 ?></span>
                                <span class="stat-label">Hadir</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-num"><?= $stat['total'] ?: 0 ?></span>
                                <span class="stat-label">Total</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-num"><?= $persen ?>%</span>
                                <span class="stat-label">Kehadiran</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Jadwal Aktif Alert - Tampilkan SEMUA jadwal aktif hari ini -->
                <?php if (mysqli_num_rows($jadwal_hari_ini) > 0): ?>
                    <?php while ($jhi = mysqli_fetch_assoc($jadwal_hari_ini)): ?>
                    <div class="jadwal-aktif-alert">
                        <div class="pulse-icon">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <div class="jadwal-info">
                            <h4><i class="fas fa-clock me-2"></i>Praktikum Sedang Berlangsung!</h4>
                            <p><strong><?= $jhi['nama_mk'] ?></strong> - <?= $jhi['materi'] ?></p>
                            <div class="meta">
                                <span><i class="fas fa-clock me-1"></i><?= format_waktu($jhi['jam_mulai']) ?> - <?= format_waktu($jhi['jam_selesai']) ?></span>
                                <span><i class="fas fa-map-marker-alt me-1"></i><?= $jhi['nama_lab'] ?></span>
                                <span><i class="fas fa-user-tie me-1"></i><?= $jhi['asisten1_nama'] ?: '-' ?><?php if ($jhi['asisten2_nama']): ?>, <?= $jhi['asisten2_nama'] ?><?php endif; ?></span>
                            </div>
                        </div>
                        <?php if ($jhi['presensi_status'] && $jhi['presensi_status'] != 'belum'): ?>
                            <div class="status-done">
                                <i class="fas fa-check-circle me-2"></i>Sudah <?= ucfirst($jhi['presensi_status']) ?>
                            </div>
                        <?php else: ?>
                            <a href="index.php?page=mahasiswa_scanner" class="btn-scan">
                                <i class="fas fa-qrcode"></i>Scan Presensi
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                
                <!-- Stat Cards -->
                <div class="stat-cards-grid">
                    <div class="stat-card">
                        <div class="stat-icon hadir">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stat['hadir'] ?: 0 ?></h3>
                            <p>Hadir</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon izin">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stat['izin'] ?: 0 ?></h3>
                            <p>Izin</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon sakit">
                            <i class="fas fa-medkit"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stat['sakit'] ?: 0 ?></h3>
                            <p>Sakit</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon alpha">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stat['alpha'] ?: 0 ?></h3>
                            <p>Alpha</p>
                        </div>
                    </div>
                </div>
                
                <!-- Main Grid -->
                <div class="main-grid">
                    <!-- Left: Kehadiran & Quick Actions -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        <!-- Persentase Kehadiran -->
                        <div class="card-box">
                            <div class="card-header-custom">
                                <h3><i class="fas fa-chart-pie"></i> Persentase Kehadiran</h3>
                            </div>
                            <div class="card-body-custom">
                                <div class="ring-container">
                                    <div class="ring-chart">
                                        <svg viewBox="0 0 140 140">
                                            <circle class="ring-bg" cx="70" cy="70" r="60"/>
                                            <circle class="ring-progress" cx="70" cy="70" r="60" 
                                                    stroke="<?= $persen >= 75 ? '#66cc00' : ($persen >= 50 ? '#ffaa00' : '#ff3333') ?>"
                                                    stroke-dasharray="377" 
                                                    stroke-dashoffset="<?= 377 - (377 * $persen / 100) ?>"/>
                                        </svg>
                                        <div class="ring-text">
                                            <span class="persen" style="color: <?= $persen >= 75 ? '#66cc00' : ($persen >= 50 ? '#ffaa00' : '#ff3333') ?>"><?= $persen ?>%</span>
                                            <span class="label">Kehadiran</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-center text-muted small mt-3 mb-0">
                                    Total <?= $stat['total'] ?: 0 ?> pertemuan tercatat
                                </p>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="card-box">
                            <div class="card-header-custom">
                                <h3><i class="fas fa-bolt" style="color: #ffaa00;"></i> Menu Cepat</h3>
                            </div>
                            <div class="card-body-custom">
                                <div class="quick-grid">
                                    <a href="index.php?page=mahasiswa_scanner" class="quick-btn scan">
                                        <i class="fas fa-qrcode"></i>
                                        <span>Scan QR</span>
                                    </a>
                                    <a href="index.php?page=mahasiswa_riwayat" class="quick-btn riwayat">
                                        <i class="fas fa-history"></i>
                                        <span>Riwayat</span>
                                    </a>
                                    <a href="index.php?page=mahasiswa_jadwal" class="quick-btn jadwal">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Jadwal</span>
                                    </a>
                                    <a href="index.php?page=mahasiswa_izin" class="quick-btn izin">
                                        <i class="fas fa-paper-plane"></i>
                                        <span>Izin</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Jadwal Mendatang -->
                    <div class="card-box">
                        <div class="card-header-custom">
                            <h3><i class="fas fa-calendar-alt"></i> Jadwal Mendatang</h3>
                            <a href="index.php?page=mahasiswa_jadwal" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body-custom">
                            <?php if (mysqli_num_rows($jadwal_terdekat) > 0): ?>
                                <div class="jadwal-list">
                                    <?php while ($j = mysqli_fetch_assoc($jadwal_terdekat)): ?>
                                        <?php 
                                        $is_today = $j['tanggal'] == $today;
                                        $waktu_buka = strtotime($j['jam_mulai']) - ($toleransi_sebelum * 60);
                                        $waktu_sekarang = strtotime($now_time);
                                        $belum_waktunya = $is_today && ($waktu_sekarang < $waktu_buka);
                                        $sisa_menit = 0;
                                        if ($belum_waktunya) {
                                            $sisa_menit = ceil(($waktu_buka - $waktu_sekarang) / 60);
                                        }
                                        ?>
                                        <div class="jadwal-item <?= $is_today ? 'today' : '' ?>">
                                            <div class="jadwal-date">
                                                <span class="day"><?= date('d', strtotime($j['tanggal'])) ?></span>
                                                <span class="month"><?= date('M', strtotime($j['tanggal'])) ?></span>
                                            </div>
                                            <div class="jadwal-info-item">
                                                <h4><?= $j['nama_mk'] ?></h4>
                                                <div class="meta">
                                                    <span><i class="fas fa-clock me-1"></i><?= format_waktu($j['jam_mulai']) ?></span>
                                                    <span><i class="fas fa-map-marker-alt me-1"></i><?= $j['nama_lab'] ?></span>
                                                </div>
                                                <div class="asisten">
                                                    <i class="fas fa-user-tie me-1"></i><?= $j['asisten1_nama'] ?: '-' ?>
                                                    <?php if ($j['asisten2_nama']): ?>, <?= $j['asisten2_nama'] ?><?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="jadwal-status">
                                                <span class="jadwal-badge <?= $j['jenis'] ?>"><?= ucfirst($j['jenis']) ?></span>
                                                <?php if ($j['presensi_status'] && $j['presensi_status'] != 'belum'): ?>
                                                    <span class="status-badge <?= $j['presensi_status'] ?>"><?= ucfirst($j['presensi_status']) ?></span>
                                                <?php elseif ($belum_waktunya): ?>
                                                    <span class="status-badge menunggu">
                                                        <i class="fas fa-hourglass-half me-1"></i>
                                                        <?php if ($sisa_menit >= 60): ?>
                                                            <?= floor($sisa_menit/60) ?>j <?= $sisa_menit % 60 ?>m
                                                        <?php else: ?>
                                                            <?= $sisa_menit ?>m
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge belum">Belum</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>Tidak ada jadwal mendatang</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
