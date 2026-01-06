<?php
$page = 'admin_dashboard';

// Statistik
$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mahasiswa"))['total'];
$total_asisten = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM asisten"))['total'];
$total_lab = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM lab"))['total'];
$total_kelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM kelas"))['total'];
$total_matkul = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mata_kuliah"))['total'];

// Jadwal hari ini
$today = date('Y-m-d');
$now_time = date('H:i:s');
$total_jadwal_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal WHERE tanggal = '$today'"))['total'];
$jadwal_hari_ini = mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk,
                                         a1.nama as asisten1_nama
                                         FROM jadwal j 
                                         LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                         LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                         LEFT JOIN asisten a1 ON j.kode_asisten_1 = a1.kode_asisten
                                         WHERE j.tanggal = '$today' 
                                         ORDER BY (j.jam_selesai >= '$now_time') DESC, j.jam_mulai ASC
                                         LIMIT 5");

// Statistik presensi hari ini, dengan perhitungan alpha yang akurat
$today_stats_query = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN p.status = 'izin' THEN 1 ELSE 0 END) as izin,
    SUM(CASE WHEN p.status = 'sakit' THEN 1 ELSE 0 END) as sakit,
    SUM(CASE WHEN CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() AND (p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit')) AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai) THEN 1 ELSE 0 END) as alpha
    FROM jadwal j
    JOIN mahasiswa m ON j.kode_kelas = m.kode_kelas
    LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND m.nim = p.nim
    WHERE j.tanggal = '$today'");
$stat_presensi = mysqli_fetch_assoc($today_stats_query);

// Fallback untuk jika query tidak mengembalikan row (misal tidak ada jadwal)
$stat_presensi = [
    'hadir' => $stat_presensi['hadir'] ?? 0,
    'izin' => $stat_presensi['izin'] ?? 0,
    'sakit' => $stat_presensi['sakit'] ?? 0,
    'alpha' => $stat_presensi['alpha'] ?? 0,
];
$total_presensi = array_sum($stat_presensi);
$persen_hadir = $total_presensi > 0 ? round(($stat_presensi['hadir'] / $total_presensi) * 100) : 0;

// Jadwal minggu ini
$start_week = date('Y-m-d', strtotime('monday this week'));
$end_week = date('Y-m-d', strtotime('sunday this week'));
$jadwal_minggu_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal WHERE tanggal BETWEEN '$start_week' AND '$end_week'"))['total'];

// Asisten yang belum assign ke jadwal
$asisten_no_jadwal = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM asisten a 
    WHERE NOT EXISTS (
        SELECT 1 FROM jadwal j 
        WHERE (j.kode_asisten_1 = a.kode_asisten OR j.kode_asisten_2 = a.kode_asisten)
        AND j.tanggal >= '$today'
    )
"))['total'];

// Activity log (presensi terbaru)
$recent_activity = mysqli_query($conn, "
    SELECT pm.*, m.nama as nama_mhs, m.nim, j.tanggal, mk.nama_mk, pm.waktu_presensi
    FROM presensi_mahasiswa pm
    JOIN mahasiswa m ON pm.nim = m.nim
    JOIN jadwal j ON pm.jadwal_id = j.id
    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
    ORDER BY pm.waktu_presensi DESC
    LIMIT 8
");

// Top 5 mahasiswa dengan kehadiran terbaik bulan ini
$start_month = date('Y-m-01');
$end_month = date('Y-m-t');
$top_mahasiswa = mysqli_query($conn, "
    SELECT m.nama, m.nim, COUNT(*) as total_hadir
    FROM presensi_mahasiswa pm
    JOIN mahasiswa m ON pm.nim = m.nim
    JOIN jadwal j ON pm.jadwal_id = j.id
    WHERE pm.status = 'hadir' AND j.tanggal BETWEEN '$start_month' AND '$end_month'
    GROUP BY pm.nim
    ORDER BY total_hadir DESC
    LIMIT 5
");

// Statistik per lab (penggunaan)
$lab_usage = mysqli_query($conn, "
    SELECT l.nama_lab, COUNT(j.id) as total_jadwal
    FROM lab l
    LEFT JOIN jadwal j ON l.kode_lab = j.kode_lab AND j.tanggal BETWEEN '$start_month' AND '$end_month'
    GROUP BY l.kode_lab
    ORDER BY total_jadwal DESC
    LIMIT 4
");

// Greeting berdasarkan waktu
$greeting = sapaan_waktu();

// Fetch Pengumuman Terbaru (3 Teratas) untuk Admin
$pengumuman_list = mysqli_query($conn, "SELECT * FROM pengumuman
                                        WHERE target_role IN ('semua', 'admin')
                                        AND status = 'active'
                                        ORDER BY created_at DESC LIMIT 3");
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== RICH DASHBOARD STYLE ===== */
.dashboard-content {
    padding: 24px;
    max-width: 1600px;
}

/* Announcement Style */
.announcement-wrapper {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 28px;
}
.announcement-item {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    display: flex;
    gap: 16px;
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}
.announcement-item.alert {
    margin-bottom: 0;
}
.announcement-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    border-color: #0066cc;
}
.announcement-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: linear-gradient(180deg, #0066cc 0%, #00ccff 100%);
}
.announcement-icon-box {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.announcement-content {
    flex: 1;
    padding-right: 20px;
}
.announcement-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    flex-wrap: wrap;
    gap: 8px;
}
.announcement-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
}
.announcement-time {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}
.announcement-body {
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.5;
}
.announcement-close {
    position: absolute;
    top: 12px;
    right: 12px;
    background: none;
    border: none;
    color: var(--text-muted);
    opacity: 0.4;
    cursor: pointer;
    padding: 4px;
    transition: all 0.2s;
    z-index: 2;
}
.announcement-close:hover {
    opacity: 1;
    color: #ef4444;
    transform: rotate(90deg);
}
[data-theme="dark"] .announcement-icon-box {
    background: rgba(2, 132, 199, 0.2);
    color: #38bdf8;
}

/* Welcome Banner - Modern dengan tema biru */
.welcome-banner {
    background: var(--banner-gradient);
    border-radius: 24px;
    padding: 0;
    color: white;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr auto;
    min-height: 200px;
}
.welcome-banner::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(78, 115, 223, 0.5) 0%, transparent 70%);
    animation: pulse-glow 4s ease-in-out infinite;
}
.welcome-banner::after {
    content: '';
    position: absolute;
    bottom: -150px;
    left: -100px;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(54, 185, 204, 0.3) 0%, transparent 70%);
    animation: pulse-glow 4s ease-in-out infinite 2s;
}
@keyframes pulse-glow {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}
.welcome-content {
    padding: 32px 36px;
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    width: fit-content;
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.welcome-badge i {
    font-size: 0.7rem;
    color: #66cc00;
}
.welcome-banner h1 {
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 8px 0;
    color: #fff;
    line-height: 1.2;
}
.welcome-banner .subtitle {
    margin: 0;
    opacity: 0.85;
    font-size: 0.95rem;
    font-weight: 400;
}
.welcome-stats-panel {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    padding: 24px;
}
.stats-glass {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 24px 28px;
    display: flex;
    gap: 32px;
}
.stat-box {
    text-align: center;
    position: relative;
}
.stat-box:not(:last-child)::after {
    content: '';
    position: absolute;
    right: -16px;
    top: 50%;
    transform: translateY(-50%);
    height: 40px;
    width: 1px;
    background: rgba(255,255,255,0.25);
}
.stat-box .stat-num {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
    color: #fff;
}
.stat-box .stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.75;
    margin-top: 6px;
    white-space: nowrap;
}
/* Floating Elements */
.floating-shapes {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow: hidden;
    pointer-events: none;
    z-index: 1;
}
.floating-shapes span {
    position: absolute;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
.floating-shapes span:nth-child(1) {
    width: 80px;
    height: 80px;
    top: 20%;
    left: 10%;
    animation: float 8s ease-in-out infinite;
}
.floating-shapes span:nth-child(2) {
    width: 60px;
    height: 60px;
    top: 60%;
    left: 30%;
    animation: float 6s ease-in-out infinite 1s;
}
.floating-shapes span:nth-child(3) {
    width: 40px;
    height: 40px;
    top: 30%;
    right: 30%;
    animation: float 7s ease-in-out infinite 2s;
}
@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(10deg); }
}

/* Alert Cards */
.alert-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.alert-card {
    padding: 18px 20px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-left: 4px solid;
}
.alert-card.warning {
    background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
    border-color: #ffaa00;
}
.alert-card.info {
    background: linear-gradient(135deg, #e8f4fd 0%, #cce5ff 100%);
    border-color: #0066cc;
}
.alert-card.success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-color: #66cc00;
}
.alert-card i {
    font-size: 1.5rem;
}
.alert-card.warning i { color: #dda20a; }
.alert-card.info i { color: #0066cc; }
.alert-card.success i { color: #17a673; }
.alert-card .alert-content h4 {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0;
    color: #5a5c69;
}
.alert-card .alert-content p {
    font-size: 0.8rem;
    margin: 2px 0 0 0;
    color: #858796;
}

/* Stat Cards */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 60px;
    height: 60px;
    border-radius: 0 0 0 60px;
    opacity: 0.1;
}
.stat-card.blue::after { background: #0066cc; }
.stat-card.green::after { background: #66cc00; }
.stat-card.yellow::after { background: #ffaa00; }
.stat-card.cyan::after { background: #00ccff; }
.stat-card.purple::after { background: #6f42c1; }
.stat-card:hover {
    box-shadow: 0 8px 24px rgba(78, 115, 223, 0.15);
    transform: translateY(-4px);
}
.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.stat-icon.blue { background: linear-gradient(135deg, #eaecf4, #d1d3e2); color: #0066cc; }
.stat-icon.green { background: linear-gradient(135deg, #d4edda, #b8e0c4); color: #66cc00; }
.stat-icon.yellow { background: linear-gradient(135deg, #fff3cd, #ffe69c); color: #ffaa00; }
.stat-icon.cyan { background: linear-gradient(135deg, #d1ecf1, #b8e5eb); color: #00ccff; }
.stat-icon.purple { background: linear-gradient(135deg, #e2d9f3, #d4c6ec); color: #6f42c1; }

.stat-info h3 {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text-main);
    margin: 0;
    line-height: 1;
}
.stat-info p {
    color: var(--text-muted);
    margin: 4px 0 0 0;
    font-size: 0.8rem;
}

/* Main Grid */
.main-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 24px;
    margin-bottom: 24px;
}

/* Cards */
.card-box {
    background: var(--bg-card);
    border-radius: 16px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
}
.card-box .card-header {
    padding: 18px 22px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-body);
}
.card-box .card-header h2 {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-main);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-box .card-header h2 i {
    color: #0066cc;
    font-size: 1rem;
}
.card-box .card-header .btn-link {
    color: #0066cc;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
}
.card-box .card-header .btn-link:hover {
    text-decoration: underline;
}
.card-box .card-body {
    padding: 20px 22px;
}

/* Jadwal List */
.jadwal-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.jadwal-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--bg-body);
    border-radius: 12px;
    transition: background 0.2s;
}
.jadwal-item:hover {
    background: var(--border-color);
}
.jadwal-item.active-schedule {
    background: rgba(0, 102, 204, 0.08);
    border-left: 4px solid #0066cc;
}
[data-theme="dark"] .jadwal-item.active-schedule {
    background: rgba(0, 102, 204, 0.2);
}
.jadwal-time {
    background: #0066cc;
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    min-width: 60px;
    text-align: center;
}
.jadwal-info {
    flex: 1;
    min-width: 0;
}
.jadwal-info h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-main);
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.jadwal-info p {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin: 0;
}
.jadwal-info p i {
    margin-right: 4px;
    width: 14px;
}
.jadwal-badge {
    background: var(--border-color);
    color: #0066cc;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #9CA3AF;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.5;
}
.empty-state p {
    margin: 0;
    font-size: 0.95rem;
}

/* Presensi Summary */
.presensi-ring {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 0 24px;
}
.ring-container {
    position: relative;
    width: 160px;
    height: 160px;
}
.ring-container svg {
    transform: rotate(-90deg);
    width: 160px;
    height: 160px;
}
.ring-bg {
    fill: none;
    stroke: var(--border-color);
    stroke-width: 12;
}
.ring-progress {
    fill: none;
    stroke: #66cc00;
    stroke-width: 12;
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
.ring-text .value {
    font-size: 2.25rem;
    font-weight: 700;
    color: #66cc00;
    line-height: 1;
}
.ring-text .label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

/* Presensi Stats Grid */
.presensi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-top: 8px;
}
.presensi-item {
    text-align: center;
    padding: 12px 8px;
    background: var(--bg-body);
    border-radius: 10px;
}
.presensi-item .num {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
}
.presensi-item .lbl {
    font-size: 0.65rem;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-top: 4px;
}
.presensi-item.hadir .num { color: #66cc00; }
.presensi-item.izin .num { color: #ffaa00; }
.presensi-item.sakit .num { color: #00ccff; }
.presensi-item.alpha .num { color: #ff3333; }
.presensi-item.belum .num { color: #f3f299ff; }

/* Activity Log */
.activity-list {
    display: flex;
    flex-direction: column;
}
.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid var(--border-color);
}
.activity-item:last-child {
    border-bottom: none;
}
.activity-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
    flex-shrink: 0;
}
.activity-avatar.hadir { background: linear-gradient(135deg, #66cc00, #17a673); }
.activity-avatar.izin { background: linear-gradient(135deg, #ffaa00, #dda20a); }
.activity-avatar.sakit { background: linear-gradient(135deg, #00ccff, #258391); }
.activity-avatar.alpha { background: linear-gradient(135deg, #ff3333, #be2617); }
.activity-avatar.belum { background: linear-gradient(135deg, #f3f299ff, #f3f17dff); }
.activity-content {
    flex: 1;
    min-width: 0;
}
.activity-content h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-main);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.activity-content p {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 2px 0 0 0;
}
.activity-time {
    font-size: 0.7rem;
    color: #b7b9cc;
    white-space: nowrap;
}

/* Second Grid */
.second-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

/* Top Mahasiswa */
.top-list {
    display: flex;
    flex-direction: column;
}
.top-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
}
.top-item:last-child {
    border-bottom: none;
}
.top-rank {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}
.top-rank.gold { background: linear-gradient(135deg, #ffaa00, #dda20a); color: #5a5c69; }
.top-rank.silver { background: linear-gradient(135deg, #d1d3e2, #b7b9cc); color: #5a5c69; }
.top-rank.bronze { background: linear-gradient(135deg, #f8b4b4, #ff3333); color: #fff; }
.top-rank.normal { background: var(--border-color); color: var(--text-muted); }
.top-info {
    flex: 1;
    min-width: 0;
}
.top-info h4 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-main);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.top-info p {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 0;
}
.top-count {
    font-size: 0.9rem;
    font-weight: 700;
    color: #66cc00;
}

/* Lab Usage */
.lab-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.lab-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.lab-item .lab-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.lab-item .lab-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-main);
}
.lab-item .lab-count {
    font-size: 0.8rem;
    color: var(--text-muted);
}
.lab-bar {
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
}
.lab-bar .fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.6s ease;
}
.lab-bar .fill.c1 { background: linear-gradient(90deg, #0066cc, #0099ff); }
.lab-bar .fill.c2 { background: linear-gradient(90deg, #66cc00, #17a673); }
.lab-bar .fill.c3 { background: linear-gradient(90deg, #ffaa00, #dda20a); }
.lab-bar .fill.c4 { background: linear-gradient(90deg, #00ccff, #258391); }

/* Quick Actions */
.quick-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
.quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
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
    background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
    opacity: 0;
    transition: opacity 0.25s ease;
}
.quick-btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(78, 115, 223, 0.25);
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
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: #0066cc;
    transition: color 0.25s;
}
.quick-btn:hover i {
    color: white;
}
.quick-btn span {
    font-size: 0.85rem;
    font-weight: 500;
    text-align: center;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1400px) {
    .stat-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 1200px) {
    .main-grid {
        grid-template-columns: 1fr;
    }
    .second-grid {
        grid-template-columns: 1fr;
    }
    .stat-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 992px) {
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .alert-row {
        grid-template-columns: 1fr;
    }
    .welcome-banner {
        grid-template-columns: 1fr;
    }
    .welcome-stats-panel {
        padding: 0 24px 24px;
    }
    .stats-glass {
        width: 100%;
        justify-content: space-around;
    }
}
@media (max-width: 767.98px) {
    .dashboard-content {
        padding: 16px;
    }
    .welcome-banner {
        border-radius: 20px;
    }
    .welcome-content {
        padding: 24px;
    }
    .welcome-banner h1 {
        font-size: 1.5rem;
    }
    .welcome-stats-panel {
        padding: 0 16px 20px;
    }
    .stats-glass {
        padding: 16px 20px;
        gap: 16px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .stat-box .stat-num {
        font-size: 1.5rem;
    }
    .stat-box .stat-label {
        font-size: 0.65rem;
    }
    .stat-box:not(:last-child)::after {
        display: none;
    }
    .stat-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .stat-card {
        padding: 16px;
        gap: 12px;
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        font-size: 1.1rem;
        border-radius: 10px;
    }
    .stat-info h3 {
        font-size: 1.35rem;
    }
    .stat-info p {
        font-size: 0.75rem;
    }
    .card-box .card-header {
        padding: 14px 16px;
    }
    .card-box .card-body {
        padding: 16px;
    }
    .jadwal-item {
        padding: 12px;
        gap: 12px;
    }
    .jadwal-time {
        padding: 6px 10px;
        font-size: 0.75rem;
        min-width: 50px;
    }
    .jadwal-info h4 {
        font-size: 0.875rem;
    }
    .jadwal-badge {
        display: none;
    }
    .presensi-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }
    .presensi-item {
        padding: 10px 4px;
    }
    .presensi-item .num {
        font-size: 1.1rem;
    }
    .quick-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .quick-btn {
        padding: 20px 12px;
    }
    .quick-btn i {
        font-size: 1.25rem;
    }
    .quick-btn span {
        font-size: 0.8rem;
    }
    .alert-card {
        padding: 14px 16px;
    }
    .activity-item {
        padding: 12px 0;
    }
}

/* Dark Mode Dashboard Fixes */
[data-theme="dark"] .welcome-banner {
    background: var(--banner-gradient);
}
[data-theme="dark"] .alert-card.warning {
    background: rgba(255, 170, 0, 0.1);
    border-color: rgba(255, 170, 0, 0.3);
}
[data-theme="dark"] .alert-card.info {
    background: rgba(0, 102, 204, 0.1);
    border-color: rgba(0, 102, 204, 0.3);
}
[data-theme="dark"] .alert-card.success {
    background: rgba(102, 204, 0, 0.1);
    border-color: rgba(102, 204, 0, 0.3);
}
[data-theme="dark"] .alert-card .alert-content h4 {
    color: var(--text-main);
}

[data-theme="dark"] .stat-icon.blue { background: rgba(0, 102, 204, 0.2); color: #66b0ff; }
[data-theme="dark"] .stat-icon.green { background: rgba(102, 204, 0, 0.2); color: #85e085; }
[data-theme="dark"] .stat-icon.yellow { background: rgba(255, 170, 0, 0.2); color: #ffcc00; }
[data-theme="dark"] .stat-icon.cyan { background: rgba(0, 204, 255, 0.2); color: #33d6ff; }
[data-theme="dark"] .stat-icon.purple { background: rgba(111, 66, 193, 0.2); color: #a685e0; }

[data-theme="dark"] .jadwal-item:hover {
    background-color: rgba(255,255,255,0.05);
}
[data-theme="dark"] .jadwal-badge {
    background-color: rgba(255,255,255,0.1);
    color: var(--text-main);
}
[data-theme="dark"] .presensi-item {
    background-color: rgba(255,255,255,0.05);
}
[data-theme="dark"] .top-rank.normal {
    background-color: rgba(255,255,255,0.1);
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10 px-0">
            <div class="dashboard-content">
                
                <?= show_alert() ?>
                
                <!-- Pengumuman Section -->
                <?php if (mysqli_num_rows($pengumuman_list) > 0): ?>
                    <div class="announcement-wrapper">
                        <?php while($p = mysqli_fetch_assoc($pengumuman_list)): ?>
                        <div class="announcement-item alert fade show" role="alert">
                            <div class="announcement-icon-box">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="announcement-content">
                                <div class="announcement-header">
                                    <h5 class="announcement-title"><?= htmlspecialchars($p['judul']) ?></h5>
                                    <span class="announcement-time">
                                        <i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="announcement-body">
                                    <?= nl2br(htmlspecialchars($p['isi'])) ?>
                                </div>
                            </div>
                            <button type="button" class="announcement-close" data-bs-dismiss="alert" aria-label="Close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Welcome Banner - Modern -->
                <div class="welcome-banner">
                    <div class="floating-shapes">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="welcome-content">
                        <div class="welcome-badge">
                            <i class="fas fa-circle"></i>
                            <?= format_tanggal(date('Y-m-d')) ?>
                        </div>
                        <h1><?= $greeting ?>, Admin!</h1>
                        <p class="subtitle">Pantau dan kelola sistem presensi kampus dengan mudah</p>
                    </div>
                    <div class="welcome-stats-panel">
                        <div class="stats-glass">
                            <div class="stat-box">
                                <div class="stat-num"><?= $total_jadwal_hari_ini ?></div>
                                <div class="stat-label">Jadwal Hari Ini</div>
                            </div>
                            
                            <div class="stat-box">
                                <div class="stat-num"><?= $total_presensi ?></div>
                                <div class="stat-label">Presensi Hari Ini</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alert Cards -->
                <?php if ($asisten_no_jadwal > 0): ?>
                <div class="alert-row">
                    <?php if ($asisten_no_jadwal > 0): ?>
                    <div class="alert-card info">
                        <i class="fas fa-user-clock"></i>
                        <div class="alert-content">
                            <h4><?= $asisten_no_jadwal ?> Asisten Belum Terjadwal</h4>
                            <p>Belum ada jadwal mendatang</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="alert-card success">
                        <i class="fas fa-chart-line"></i>
                        <div class="alert-content">
                            <h4><?= $persen_hadir ?>% Kehadiran</h4>
                            <p>Tingkat kehadiran hari ini</p>
                        </div>
                    </div>
                    <div class="alert-card warning">
                        <i class="fas fa-calendar-week"></i>
                        <div class="alert-content">
                            <h4><?= $jadwal_minggu_ini ?> Jadwal</h4>
                            <p>Total jadwal minggu ini</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Stat Cards -->
                <div class="stat-grid">
                    <div class="stat-card blue">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_mahasiswa ?></h3>
                            <p>Mahasiswa</p>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-icon green">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_asisten ?></h3>
                            <p>Asisten</p>
                        </div>
                    </div>
                    <div class="stat-card yellow">
                        <div class="stat-icon yellow">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_lab ?></h3>
                            <p>Laboratorium</p>
                        </div>
                    </div>
                    <div class="stat-card cyan">
                        <div class="stat-icon cyan">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_kelas ?></h3>
                            <p>Kelas</p>
                        </div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-icon purple">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $total_matkul ?></h3>
                            <p>Mata Kuliah</p>
                        </div>
                    </div>
                </div>
                
                <!-- Main Grid: Jadwal + Presensi -->
                <div class="main-grid">
                    <!-- Jadwal Hari Ini -->
                    <div class="card-box">
                        <div class="card-header">
                            <h2><i class="fas fa-calendar-alt"></i> Jadwal Hari Ini</h2>
                            <a href="index.php?page=admin_jadwal" class="btn-link">Lihat Semua →</a>
                        </div>
                        <div class="card-body">
                            <?php if ($total_jadwal_hari_ini > 0): ?>
                                <div class="jadwal-list">
                                    <?php while ($j = mysqli_fetch_assoc($jadwal_hari_ini)): 
                                        $is_active = ($j['jam_mulai'] <= $now_time && $j['jam_selesai'] >= $now_time);
                                        $item_class = $is_active ? 'active-schedule' : '';
                                    ?>
                                        <div class="jadwal-item <?= $item_class ?>">
                                            <div class="jadwal-time"><?= format_waktu($j['jam_mulai']) ?></div>
                                            <div class="jadwal-info">
                                                <h4>
                                                    <?= $j['nama_mk'] ?>
                                                    <?php if($is_active): ?>
                                                        <span class="badge bg-success ms-2" style="font-size: 0.65rem;">AKTIF</span>
                                                    <?php endif; ?>
                                                </h4>
                                                <p>
                                                    <i class="fas fa-map-marker-alt"></i><?= $j['nama_lab'] ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-user"></i><?= $j['asisten1_nama'] ?: '-' ?>
                                                </p>
                                            </div>
                                            <span class="jadwal-badge"><?= $j['nama_kelas'] ?></span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>Tidak ada jadwal hari ini</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Presensi Hari Ini -->
                    <div class="card-box">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-pie"></i> Presensi Hari Ini</h2>
                        </div>
                        <div class="card-body">
                            <div class="presensi-ring">
                                <div class="ring-container">
                                    <svg viewBox="0 0 160 160">
                                        <circle class="ring-bg" cx="80" cy="80" r="65"></circle>
                                        <circle class="ring-progress" cx="80" cy="80" r="65" 
                                            stroke-dasharray="408" 
                                            stroke-dashoffset="<?= 408 - (408 * $persen_hadir / 100) ?>">
                                        </circle>
                                    </svg>
                                    <div class="ring-text">
                                        <div class="value"><?= $persen_hadir ?>%</div>
                                        <div class="label">Kehadiran</div>
                                    </div>
                                </div>
                            </div>
                            <div class="presensi-grid">
                                <div class="presensi-item hadir">
                                    <div class="num"><?= $stat_presensi['hadir'] ?></div>
                                    <div class="lbl">Hadir</div>
                                </div>
                                <div class="presensi-item izin">
                                    <div class="num"><?= $stat_presensi['izin'] ?></div>
                                    <div class="lbl">Izin</div>
                                </div>
                                <div class="presensi-item sakit">
                                    <div class="num"><?= $stat_presensi['sakit'] ?></div>
                                    <div class="lbl">Sakit</div>
                                </div>
                                <div class="presensi-item alpha">
                                    <div class="num"><?= $stat_presensi['alpha'] ?></div>
                                    <div class="lbl">Alpha</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Grid: Activity + Top Mahasiswa + Lab Usage -->
                <div class="second-grid">
                    <!-- Activity Log -->
                    <div class="card-box">
                        <div class="card-header">
                            <h2><i class="fas fa-history"></i> Aktivitas Terbaru</h2>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($recent_activity) > 0): ?>
                            <div class="activity-list">
                                <?php while ($act = mysqli_fetch_assoc($recent_activity)): ?>
                                <div class="activity-item">
                                    <div class="activity-avatar <?= $act['status'] ?>">
                                        <?= strtoupper(substr($act['nama_mhs'], 0, 2)) ?>
                                    </div>
                                    <div class="activity-content">
                                        <h4><?= $act['nama_mhs'] ?></h4>
                                        <p><?= ucfirst($act['status']) ?> - <?= $act['nama_mk'] ?></p>
                                    </div>
                                    <span class="activity-time">
                                        <?= $act['waktu_presensi'] ? date('H:i', strtotime($act['waktu_presensi'])) : '-' ?>
                                    </span>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>Belum ada aktivitas</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Top Mahasiswa + Lab Usage -->
                    <div class="card-box">
                        <div class="card-header">
                            <h2><i class="fas fa-trophy"></i> Top Kehadiran Bulan Ini</h2>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($top_mahasiswa) > 0): ?>
                            <div class="top-list">
                                <?php $rank = 1; while ($tm = mysqli_fetch_assoc($top_mahasiswa)): ?>
                                <div class="top-item">
                                    <div class="top-rank <?= $rank == 1 ? 'gold' : ($rank == 2 ? 'silver' : ($rank == 3 ? 'bronze' : 'normal')) ?>">
                                        <?= $rank ?>
                                    </div>
                                    <div class="top-info">
                                        <h4><?= $tm['nama'] ?></h4>
                                        <p><?= $tm['nim'] ?></p>
                                    </div>
                                    <span class="top-count"><?= $tm['total_hadir'] ?>x</span>
                                </div>
                                <?php $rank++; endwhile; ?>
                            </div>
                            <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-medal"></i>
                                <p>Belum ada data kehadiran</p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Lab Usage mini section -->
                            <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e3e6f0;">
                                <h5 style="font-size: 0.85rem; font-weight: 600; color: #858796; margin-bottom: 14px;">
                                    <i class="fas fa-flask me-2"></i>Penggunaan Lab Bulan Ini
                                </h5>
                                <?php 
                                $max_lab = 0;
                                $lab_data = [];
                                mysqli_data_seek($lab_usage, 0);
                                while ($lb = mysqli_fetch_assoc($lab_usage)) {
                                    $lab_data[] = $lb;
                                    if ($lb['total_jadwal'] > $max_lab) $max_lab = $lb['total_jadwal'];
                                }
                                ?>
                                <div class="lab-list">
                                    <?php $ci = 1; foreach ($lab_data as $lb): ?>
                                    <div class="lab-item">
                                        <div class="lab-head">
                                            <span class="lab-name"><?= $lb['nama_lab'] ?></span>
                                            <span class="lab-count"><?= $lb['total_jadwal'] ?> jadwal</span>
                                        </div>
                                        <div class="lab-bar">
                                            <div class="fill c<?= $ci ?>" style="width: <?= $max_lab > 0 ? round($lb['total_jadwal'] / $max_lab * 100) : 0 ?>%"></div>
                                        </div>
                                    </div>
                                    <?php $ci++; if ($ci > 4) $ci = 1; endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card-box">
                    <div class="card-header">
                        <h2><i class="fas fa-th-large"></i> Menu Cepat</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-grid">
                            <a href="index.php?page=admin_mahasiswa" class="quick-btn">
                                <i class="fas fa-user-graduate"></i>
                                <span>Kelola Mahasiswa</span>
                            </a>
                            <a href="index.php?page=admin_asisten" class="quick-btn">
                                <i class="fas fa-user-tie"></i>
                                <span>Kelola Asisten</span>
                            </a>
                            <a href="index.php?page=admin_jadwal" class="quick-btn">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Kelola Jadwal</span>
                            </a>
                            <a href="index.php?page=admin_laporan" class="quick-btn">
                                <i class="fas fa-chart-bar"></i>
                                <span>Lihat Laporan</span>
                            </a>
                            <a href="index.php?page=admin_matakuliah" class="quick-btn">
                                <i class="fas fa-book"></i>
                                <span>Mata Kuliah</span>
                            </a>
                            <a href="index.php?page=admin_kelas" class="quick-btn">
                                <i class="fas fa-users"></i>
                                <span>Kelola Kelas</span>
                            </a>
                            <a href="index.php?page=admin_lab" class="quick-btn">
                                <i class="fas fa-flask"></i>
                                <span>Kelola Lab</span>
                            </a>
                            <a href="index.php?page=admin_log" class="quick-btn">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Log Aktivitas</span>
                            </a>
                            <a href="index.php?page=admin_sync_alpha" class="quick-btn" onclick="return confirm('Proses ini akan mengubah semua jadwal yang terlewat menjadi Alpha. Lanjutkan?')">
                                <i class="fas fa-sync-alt" style="color: #fd7e14;"></i>
                                <span>Sinkronisasi Alpha</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
