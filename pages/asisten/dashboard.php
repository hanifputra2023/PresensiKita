<?php
$page = 'asisten_dashboard';
$asisten = get_asisten_login();

// Validasi data asisten
if (!$asisten) {
    echo '<div class="alert alert-danger m-4">Data asisten tidak ditemukan. Pastikan akun Anda sudah terdaftar sebagai asisten.</div>';
    return;
}

// Jadwal hari ini (yang belum selesai)
// Gunakan CURDATE() dan CURTIME() MySQL agar konsisten dengan timezone
$kode_asisten = $asisten['kode_asisten'];

// Helper clause: asisten bisa lihat jadwal sendiri ATAU jadwal yang digantikan
// Konsisten dengan rekap.php
$jadwal_asisten_clause = "(
    (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE kode_asisten = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
    OR j.id IN (SELECT jadwal_id FROM absen_asisten WHERE pengganti = '$kode_asisten' AND status IN ('izin', 'sakit') AND status_approval = 'approved')
)";

// Jadwal sendiri - hilang tepat setelah jam_selesai
$jadwal_hari_ini = mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk, 'sendiri' as tipe_jadwal, NULL as asisten_asli
                                         FROM jadwal j 
                                         LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                         LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                         LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                         WHERE j.tanggal = CURDATE() 
                                         AND (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')
                                         AND j.jam_selesai > CURTIME()
                                         ORDER BY j.jam_mulai");

// Jadwal sebagai pengganti (dari asisten lain yang izin - hanya yang sudah disetujui)
$jadwal_pengganti = mysqli_query($conn, "SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk, 'pengganti' as tipe_jadwal, a.nama as asisten_asli
                                          FROM absen_asisten aa
                                          JOIN jadwal j ON aa.jadwal_id = j.id
                                          JOIN asisten a ON aa.kode_asisten = a.kode_asisten
                                          LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
                                          LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                          LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                          WHERE aa.pengganti = '$kode_asisten'
                                          AND aa.status IN ('izin', 'sakit')
                                          AND aa.status_approval = 'approved'
                                          AND j.tanggal = CURDATE()
                                          AND j.jam_selesai > CURTIME()
                                          ORDER BY j.jam_mulai");

// Gabungkan jadwal (hindari duplikasi berdasarkan jadwal_id)
$all_jadwal = [];
$jadwal_ids = []; // Track jadwal yang sudah dimasukkan

while ($j = mysqli_fetch_assoc($jadwal_hari_ini)) {
    // Skip jika jadwal ini adalah jadwal yang kita gantikan (sudah di-update oleh admin)
    // Cek apakah ada record izin approved dimana kita sebagai pengganti untuk jadwal ini
    $cek_sbg_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM absen_asisten 
                                                                  WHERE jadwal_id = '{$j['id']}' 
                                                                  AND pengganti = '$kode_asisten' 
                                                                  AND status IN ('izin', 'sakit')
                                                                  AND status_approval = 'approved'"));
    if ($cek_sbg_pengganti) {
        // Ini jadwal pengganti, skip dari jadwal sendiri (akan diambil dari query pengganti)
        continue;
    }
    
    $all_jadwal[] = $j;
    $jadwal_ids[] = $j['id'];
}

while ($j = mysqli_fetch_assoc($jadwal_pengganti)) {
    // Hindari duplikat (seharusnya tidak terjadi, tapi untuk jaga-jaga)
    if (!in_array($j['id'], $jadwal_ids)) {
        $all_jadwal[] = $j;
        $jadwal_ids[] = $j['id'];
    }
}

// Statistik minggu ini
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

$stat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM jadwal j
                                                  WHERE j.tanggal BETWEEN '$week_start' AND '$week_end'
                                                  AND $jadwal_asisten_clause"));

// Hitung jadwal pengganti minggu ini
$stat_pengganti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total 
                                                           FROM absen_asisten aa
                                                           JOIN jadwal j ON aa.jadwal_id = j.id
                                                           WHERE aa.pengganti = '$kode_asisten'
                                                           AND aa.status IN ('izin', 'sakit')
                                                           AND j.tanggal BETWEEN '$week_start' AND '$week_end'"));

// Statistik kehadiran MAHASISWA bulan ini (di jadwal yang diajar asisten ini)
// Menggunakan logika yang sama dengan rekap.php untuk konsistensi
$start_month = date('Y-m-01');
$end_month = date('Y-m-t');
$stat_hadir = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN p.status = 'hadir' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN p.status = 'izin' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as izin,
        SUM(CASE WHEN p.status = 'sakit' AND j.jenis != 'inhall' THEN 1 ELSE 0 END) as sakit,
        SUM(CASE 
            WHEN j.jenis != 'inhall' 
                 AND (p.status = 'alpha' OR ((p.status IS NULL OR p.status NOT IN ('hadir', 'izin', 'sakit', 'alpha')) 
                 AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW() 
                 AND m.tanggal_daftar < CONCAT(j.tanggal, ' ', j.jam_selesai)))
            THEN 1 
            ELSE 0 
        END) as alpha
    FROM jadwal j
    JOIN mahasiswa m ON j.kode_kelas = m.kode_kelas
    LEFT JOIN presensi_mahasiswa p ON j.id = p.jadwal_id AND m.nim = p.nim
    WHERE $jadwal_asisten_clause
    AND j.tanggal BETWEEN '$start_month' AND '$end_month'
"));

// [BARU] Hitung Rating Rata-rata Asisten
$rating_query = mysqli_query($conn, "SELECT AVG(fp.rating) as avg_rating, COUNT(fp.id) as total_ulasan
                                     FROM feedback_praktikum fp
                                     JOIN jadwal j ON fp.jadwal_id = j.id
                                     WHERE (j.kode_asisten_1 = '$kode_asisten' OR j.kode_asisten_2 = '$kode_asisten')");
$rating_data = mysqli_fetch_assoc($rating_query);
$avg_rating = $rating_data['avg_rating'] ? number_format($rating_data['avg_rating'], 1) : '0.0';
$total_ulasan = $rating_data['total_ulasan'];


// Total presensi mahasiswa bulan ini (di jadwal asisten)
$total_jadwal_bulan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total FROM presensi_mahasiswa pm
    JOIN jadwal j ON pm.jadwal_id = j.id
    WHERE j.tanggal BETWEEN '$start_month' AND '$end_month'
    AND $jadwal_asisten_clause
    AND pm.status != 'belum'
"))['total'];

// Presensi terbaru yang di-scan di jadwal asisten ini
$recent_presensi = mysqli_query($conn, "
    SELECT pm.*, m.nama as nama_mhs, m.nim, j.tanggal, mk.nama_mk, pm.waktu_presensi
    FROM presensi_mahasiswa pm
    JOIN mahasiswa m ON pm.nim = m.nim
    JOIN jadwal j ON pm.jadwal_id = j.id
    JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
    WHERE $jadwal_asisten_clause
    ORDER BY pm.waktu_presensi DESC
    LIMIT 6
");

// Riwayat mengajar (5 terakhir)
$riwayat = mysqli_query($conn, "
    SELECT j.*, k.nama_kelas, l.nama_lab, mk.nama_mk,
           (SELECT COUNT(*) FROM presensi_mahasiswa pm WHERE pm.jadwal_id = j.id AND pm.status = 'hadir') as total_hadir,
           (SELECT COUNT(*) FROM presensi_mahasiswa pm WHERE pm.jadwal_id = j.id AND pm.status != 'belum') as total_mhs
    FROM jadwal j
    LEFT JOIN kelas k ON j.kode_kelas = k.kode_kelas
    LEFT JOIN lab l ON j.kode_lab = l.kode_lab
    LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
    WHERE $jadwal_asisten_clause
    AND j.tanggal <= CURDATE()
    ORDER BY j.tanggal DESC, j.jam_mulai DESC
    LIMIT 5
");

// Greeting berdasarkan waktu
$greeting = sapaan_waktu();

$total_kehadiran = ($stat_hadir['hadir'] ?? 0) + ($stat_hadir['izin'] ?? 0) + ($stat_hadir['sakit'] ?? 0) + ($stat_hadir['alpha'] ?? 0);
$persen_hadir = $total_kehadiran > 0 ? round((($stat_hadir['hadir'] ?? 0) / $total_kehadiran) * 100) : 0;

// Fetch Pengumuman Terbaru (3 Teratas)
$pengumuman_list = mysqli_query($conn, "SELECT * FROM pengumuman 
                                        WHERE target_role IN ('semua', 'asisten')
                                        AND status = 'active' 
                                        ORDER BY created_at DESC LIMIT 3");
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== ASISTEN DASHBOARD STYLE ===== */
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

/* Welcome Banner */
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
    min-height: 180px;
}
/* OPTIMISASI: Matikan animasi berat di mobile */
@media (max-width: 768px) {
    .welcome-banner::before, .welcome-banner::after {
        display: none;
        animation: none;
    }
    .stats-glass {
        backdrop-filter: none !important;
        background: rgba(255,255,255,0.2);
    }
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
    padding: 28px 32px;
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.welcome-content .greeting {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 6px;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.welcome-content h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}
.welcome-content .subtitle {
    font-size: 0.95rem;
    opacity: 0.85;
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
    padding: 20px 28px;
    display: flex;
    gap: 32px;
    border: 1px solid rgba(255,255,255,0.2);
}
.stats-glass .stat-item {
    text-align: center;
}
.stats-glass .stat-num {
    font-size: 1.75rem;
    font-weight: 700;
    display: block;
    line-height: 1;
}
.stats-glass .stat-label {
    font-size: 0.75rem;
    opacity: 0.85;
    margin-top: 4px;
}

/* Alert Cards */
.alert-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
    grid-template-columns: repeat(5, 1fr); /* Ubah jadi 5 kolom */
    gap: 20px;
    margin-bottom: 28px;
}
.stat-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    border: 1px solid var(--border-color);
    transition: all 0.25s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}
.stat-icon.blue { background: linear-gradient(135deg, #cce5ff, #b8daff); color: #0066cc; }
.stat-icon.green { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #66cc00; }
.stat-icon.yellow { background: linear-gradient(135deg, #fff3cd, #ffeeba); color: #ffaa00; }
.stat-icon.cyan { background: linear-gradient(135deg, #d1ecf1, #b8e5eb); color: #00ccff; }
.stat-icon.orange { background: linear-gradient(135deg, #ffe5d0, #ffccb0); color: #fd7e14; }

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
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 28px;
}

/* Card Box */
.card-box {
    background: var(--bg-card);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    border: 1px solid var(--border-color);
    overflow: hidden;
}
.card-box .card-header-custom {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--header-bg);
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

/* Jadwal Today */
.jadwal-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.jadwal-item {
    display: flex;
    align-items: stretch;
    gap: 0;
    padding: 0;
    background: var(--bg-card);
    border-radius: 16px;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.jadwal-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(78, 115, 223, 0.15);
    border-color: #0066cc;
}
.jadwal-item.pengganti {
    border-left: 4px solid #ffaa00;
}
.jadwal-time {
    background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
    color: white;
    padding: 20px 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 100px;
    position: relative;
}
.jadwal-time::after {
    content: '';
    position: absolute;
    right: -8px;
    top: 50%;
    transform: translateY(-50%);
    border: 8px solid transparent;
    border-left-color: #0099ff;
}
.jadwal-time .start-time {
    font-size: 1.3rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.5px;
}
.jadwal-time .time-divider {
    width: 20px;
    height: 2px;
    background: rgba(255,255,255,0.5);
    margin: 6px 0;
    border-radius: 2px;
}
.jadwal-time .end-time {
    font-size: 0.85rem;
    opacity: 0.9;
    font-weight: 500;
}
.jadwal-time .end {
    font-size: 0.7rem;
    opacity: 0.8;
    margin-top: 2px;
}
.jadwal-info {
    flex: 1;
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.jadwal-info h4 {
    font-size: 1.05rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    color: var(--text-main);
    letter-spacing: -0.3px;
}
.jadwal-info .badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}
.jadwal-badge {
    font-size: 0.72rem;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.jadwal-badge i {
    font-size: 0.65rem;
}
.jadwal-badge.kelas { background: linear-gradient(135deg, #e8f0fe, #d4e4ff); color: #1a56db; }
.jadwal-badge.lab { background: linear-gradient(135deg, #e0f7f7, #c5ecec); color: #0e7490; }
.jadwal-badge.jenis-materi { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; }
.jadwal-badge.jenis-inhall { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.jadwal-badge.jenis-responsi { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; }
.jadwal-badge.pengganti-badge { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.jadwal-info .materi {
    font-size: 0.82rem;
    color: var(--text-muted);
    margin-bottom: 14px;
    padding: 8px 12px;
    background: var(--bg-body);
    border-radius: 8px;
    border-left: 3px solid #0066cc;
}
.jadwal-info .pengganti-info {
    font-size: 0.78rem;
    color: #d97706;
    margin-bottom: 12px;
    padding: 8px 12px;
    background: #fffbeb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.jadwal-info .pengganti-info i {
    color: #f59e0b;
}
.jadwal-actions {
    display: flex;
    gap: 10px;
    margin-top: auto;
}
.jadwal-actions .btn {
    font-size: 0.78rem;
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}
.jadwal-actions .btn-primary {
    background: linear-gradient(135deg, #0066cc 0%, #0099ff 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(78, 115, 223, 0.3);
}
.jadwal-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(78, 115, 223, 0.4);
}
.jadwal-actions .btn-success {
    background: linear-gradient(135deg, #66cc00 0%, #17a673 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(28, 200, 138, 0.3);
}
.jadwal-actions .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(28, 200, 138, 0.4);
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
    width: 160px;
    height: 160px;
}
.ring-chart svg {
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
.ring-text .persen {
    font-size: 2rem;
    font-weight: 700;
    color: #66cc00;
    line-height: 1;
}
.ring-text .label {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* Presensi Stats */
.presensi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-top: 16px;
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
.presensi-item.izin .num { color: #0066cc; }
.presensi-item.sakit .num { color: #ffaa00; }
.presensi-item.alpha .num { color: #ff3333; }
.presensi-item.belum .num { color: #f3ec90ff; }

/* Activity Log */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.activity-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    background: var(--bg-body);
    border-radius: 10px;
    transition: background 0.2s;
}
.activity-item:hover {
    background: var(--border-color);
}
.activity-avatar {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    color: white;
}
.activity-avatar.hadir { background: linear-gradient(135deg, #66cc00, #17a673); }
.activity-avatar.izin { background: linear-gradient(135deg, #0066cc, #0099ff); }
.activity-avatar.sakit { background: linear-gradient(135deg, #ffaa00, #dda20a); }
.activity-avatar.alpha { background: linear-gradient(135deg, #ff3333, #c0392b); }
.activity-avatar.belum { background: linear-gradient(135deg, #f3f299ff, #f3f17dff); }
.activity-info {
    flex: 1;
    min-width: 0;
}
.activity-info h4 {
    font-size: 0.85rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.activity-info p {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin: 2px 0 0 0;
}
.activity-time {
    font-size: 0.7rem;
    color: var(--text-muted);
    white-space: nowrap;
}

/* Riwayat Mengajar */
.riwayat-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.riwayat-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px;
    background: var(--bg-body);
    border-radius: 12px;
    transition: all 0.2s;
}
.riwayat-item:hover {
    background: var(--border-color);
}
.riwayat-date {
    text-align: center;
    min-width: 55px;
}
.riwayat-date .day {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0066cc;
    line-height: 1;
}
.riwayat-date .month {
    font-size: 0.7rem;
    color: var(--text-muted);
    text-transform: uppercase;
}
.riwayat-info {
    flex: 1;
}
.riwayat-info h4 {
    font-size: 0.85rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-main);
}
.riwayat-info .meta {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 3px;
}
.riwayat-stat {
    text-align: right;
}
.riwayat-stat .count {
    font-size: 1rem;
    font-weight: 700;
    color: #66cc00;
}
.riwayat-stat .label {
    font-size: 0.65rem;
    color: var(--text-muted);
}

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

/* Empty State */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-muted);
    background: var(--bg-body);
    border-radius: 16px;
    border: 2px dashed var(--border-color);
}
.empty-state i {
    font-size: 3.5rem;
    margin-bottom: 18px;
    background: linear-gradient(135deg, #0066cc, #00ccff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.empty-state p {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
    color: var(--text-muted);
}
.empty-state .sub-text {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 6px;
}

/* Dark Mode Specific Overrides */
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
[data-theme="dark"] .stat-icon.orange { background: rgba(253, 126, 20, 0.2); color: #fd7e14; }

[data-theme="dark"] .jadwal-info .materi {
    background: rgba(255,255,255,0.05);
    border-left-color: #66b0ff;
    color: var(--text-muted);
}
[data-theme="dark"] .jadwal-info .pengganti-info {
    background: rgba(245, 158, 11, 0.1);
    color: #fbbf24;
}
[data-theme="dark"] .jadwal-info .pengganti-info i {
    color: #fbbf24;
}
/* Dark Mode Badges in Jadwal Info */
[data-theme="dark"] .jadwal-badge.kelas { background: rgba(26, 86, 219, 0.2); color: #93c5fd; }
[data-theme="dark"] .jadwal-badge.lab { background: rgba(14, 116, 144, 0.2); color: #67e8f9; }
[data-theme="dark"] .jadwal-badge.jenis-materi { background: rgba(30, 64, 175, 0.2); color: #93c5fd; }
[data-theme="dark"] .jadwal-badge.jenis-inhall { background: rgba(146, 64, 14, 0.2); color: #fcd34d; }
[data-theme="dark"] .jadwal-badge.jenis-responsi { background: rgba(153, 27, 27, 0.2); color: #fca5a5; }
[data-theme="dark"] .jadwal-badge.pengganti-badge { background: rgba(146, 64, 14, 0.2); color: #fcd34d; }

/* Dark Mode Jadwal Time */
[data-theme="dark"] .jadwal-time {
    background: rgba(0, 102, 204, 0.2);
    color: #66b0ff;
}
[data-theme="dark"] .jadwal-time::after {
    border-left-color: rgba(0, 102, 204, 0.2);
}
[data-theme="dark"] .jadwal-time .time-divider {
    background: rgba(102, 176, 255, 0.3);
}

/* Dark Mode Buttons in Dashboard */
[data-theme="dark"] .jadwal-actions .btn-primary {
    background: linear-gradient(135deg, #3a8fd9 0%, #2c7bc0 100%);
    box-shadow: none;
    color: #fff !important;
}
[data-theme="dark"] .jadwal-actions .btn-success {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    box-shadow: none;
    color: #fff !important;
}
[data-theme="dark"] .btn-outline-primary {
    color: #66b0ff;
    border-color: #66b0ff;
}
[data-theme="dark"] .btn-outline-primary:hover {
    background-color: #66b0ff;
    color: #212529;
}

[data-theme="dark"] .presensi-item,
[data-theme="dark"] .activity-item,
[data-theme="dark"] .riwayat-item {
    background: rgba(255,255,255,0.05);
}
[data-theme="dark"] .activity-item:hover,
[data-theme="dark"] .riwayat-item:hover {
    background: rgba(255,255,255,0.1);
}
[data-theme="dark"] .quick-btn:hover {
    background: var(--primary-color);
}

/* Responsive */
@media (max-width: 1200px) {
    .main-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 992px) {
    .stat-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .quick-grid {
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
        border-radius: 16px;
    }
    .welcome-content {
        padding: 20px 20px 10px;
    }
    .welcome-content .greeting {
        font-size: 0.8rem;
    }
    .welcome-content h1 {
        font-size: 1.4rem;
    }
    .welcome-content .subtitle {
        font-size: 0.85rem;
    }
    .welcome-stats {
        padding: 0 20px 20px;
    }
    .stats-glass {
        width: 100%;
        justify-content: space-around;
        gap: 16px;
        padding: 16px 20px;
    }
    .stats-glass .stat-num {
        font-size: 1.4rem;
    }
    .stats-glass .stat-label {
        font-size: 0.7rem;
    }
    .stat-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .stat-card {
        padding: 16px;
        border-radius: 12px;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        font-size: 1.2rem;
        border-radius: 12px;
    }
    .stat-info h3 {
        font-size: 1.3rem;
    }
    .stat-info p {
        font-size: 0.75rem;
    }
    .jadwal-item {
        flex-direction: column;
        border-radius: 14px;
    }
    .jadwal-time {
        width: 100%;
        flex-direction: row;
        padding: 14px 18px;
        gap: 10px;
        min-width: unset;
    }
    .jadwal-time::after {
        display: none;
    }
    .jadwal-time .time-divider {
        width: 2px;
        height: 16px;
        margin: 0;
    }
    .jadwal-time .start-time {
        font-size: 1.1rem;
    }
    .jadwal-time .end-time {
        font-size: 0.85rem;
    }
    .jadwal-info {
        padding: 16px;
    }
    .jadwal-info h4 {
        font-size: 0.95rem;
    }
    .jadwal-actions {
        flex-wrap: wrap;
    }
    .jadwal-actions .btn {
        flex: 1;
        justify-content: center;
    }
    .quick-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .quick-btn {
        padding: 18px 12px;
    }
    /* Card box responsive */
    .card-box .card-header-custom {
        padding: 14px 16px;
    }
    .card-box .card-header-custom h3 {
        font-size: 0.9rem;
    }
    .card-box .card-body-custom {
        padding: 16px;
    }
    /* Alert grid */
    .alert-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    .alert-card {
        padding: 14px 16px;
    }
    .alert-card i {
        font-size: 1.3rem;
    }
    .alert-card .alert-content h4 {
        font-size: 0.85rem;
    }
    .alert-card .alert-content p {
        font-size: 0.75rem;
    }
    /* Presensi grid */
    .presensi-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
    }
    .presensi-item {
        padding: 10px 6px;
    }
    .presensi-item .num {
        font-size: 1.1rem;
    }
    .presensi-item .lbl {
        font-size: 0.6rem;
    }
    /* Ring chart */
    .ring-chart {
        width: 140px;
        height: 140px;
    }
    .ring-chart svg {
        width: 140px;
        height: 140px;
    }
    .ring-text .persen {
        font-size: 1.6rem;
    }
    /* Activity */
    .activity-item {
        padding: 10px;
        gap: 12px;
    }
    .activity-avatar {
        width: 38px;
        height: 38px;
        font-size: 0.8rem;
    }
    .activity-info h4 {
        font-size: 0.8rem;
    }
    .activity-info p {
        font-size: 0.7rem;
    }
    /* Riwayat */
    .riwayat-item {
        padding: 12px;
        gap: 12px;
    }
    .riwayat-date .day {
        font-size: 1.1rem;
    }
    .riwayat-info h4 {
        font-size: 0.8rem;
    }
    .riwayat-info .meta {
        font-size: 0.7rem;
    }
}

/* Extra Small Mobile (max-width: 576px) */
@media (max-width: 576px) {
    .dashboard-content {
        padding: 12px 10px;
    }
    .welcome-banner {
        border-radius: 14px;
        margin-bottom: 16px;
    }
    .welcome-content {
        padding: 16px 16px 8px;
    }
    .welcome-content .greeting {
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    .welcome-content h1 {
        font-size: 1.2rem;
        margin-bottom: 4px;
    }
    .welcome-content .subtitle {
        font-size: 0.8rem;
    }
    .welcome-stats {
        padding: 0 16px 16px;
    }
    .stats-glass {
        padding: 14px 12px;
        gap: 8px;
        border-radius: 12px;
    }
    .stats-glass .stat-item {
        flex: 1;
    }
    .stats-glass .stat-num {
        font-size: 1.2rem;
    }
    .stats-glass .stat-label {
        font-size: 0.65rem;
    }
    /* Stat grid 2 column */
    .stat-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 16px;
    }
    .stat-card {
        padding: 14px 12px;
        gap: 12px;
        border-radius: 10px;
    }
    .stat-icon {
        width: 42px;
        height: 42px;
        font-size: 1rem;
        border-radius: 10px;
    }
    .stat-info h3 {
        font-size: 1.15rem;
    }
    .stat-info p {
        font-size: 0.7rem;
    }
    /* Alert cards */
    .alert-grid {
        margin-bottom: 16px;
    }
    .alert-card {
        padding: 12px 14px;
        border-radius: 10px;
        gap: 12px;
    }
    .alert-card i {
        font-size: 1.1rem;
    }
    .alert-card .alert-content h4 {
        font-size: 0.8rem;
    }
    .alert-card .alert-content p {
        font-size: 0.7rem;
    }
    /* Card box */
    .card-box {
        border-radius: 12px;
        margin-bottom: 16px;
    }
    .card-box .card-header-custom {
        padding: 12px 14px;
    }
    .card-box .card-header-custom h3 {
        font-size: 0.85rem;
        gap: 8px;
    }
    .card-box .card-header-custom h3 i {
        font-size: 0.9rem;
    }
    .card-box .card-body-custom {
        padding: 14px;
    }
    /* Jadwal items */
    .jadwal-list {
        gap: 14px;
    }
    .jadwal-item {
        border-radius: 12px;
    }
    .jadwal-time {
        padding: 12px 14px;
        gap: 8px;
    }
    .jadwal-time .start-time {
        font-size: 1rem;
    }
    .jadwal-time .end-time {
        font-size: 0.8rem;
    }
    .jadwal-time .time-divider {
        height: 12px;
    }
    .jadwal-info {
        padding: 14px;
    }
    .jadwal-info h4 {
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    .jadwal-info .badges {
        gap: 6px;
        margin-bottom: 10px;
    }
    .jadwal-badge {
        font-size: 0.68rem;
        padding: 4px 10px;
    }
    .jadwal-info .materi {
        font-size: 0.78rem;
        padding: 6px 10px;
        margin-bottom: 12px;
    }
    .jadwal-info .pengganti-info {
        font-size: 0.72rem;
        padding: 6px 10px;
    }
    .jadwal-actions {
        gap: 8px;
    }
    .jadwal-actions .btn {
        font-size: 0.72rem;
        padding: 8px 12px;
        border-radius: 8px;
    }
    /* Quick grid */
    .quick-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .quick-btn {
        padding: 16px 10px;
        border-radius: 10px;
    }
    .quick-btn i {
        font-size: 1.3rem;
        margin-bottom: 8px;
    }
    .quick-btn span {
        font-size: 0.75rem;
    }
    /* Ring chart */
    .ring-container {
        padding: 5px 0;
    }
    .ring-chart {
        width: 120px;
        height: 120px;
    }
    .ring-chart svg {
        width: 120px;
        height: 120px;
    }
    .ring-bg, .ring-progress {
        stroke-width: 10;
    }
    .ring-text .persen {
        font-size: 1.4rem;
    }
    .ring-text .label {
        font-size: 0.65rem;
    }
    /* Presensi grid 4 column */
    .presensi-grid {
        gap: 4px;
        margin-top: 12px;
    }
    .presensi-item {
        padding: 8px 4px;
        border-radius: 8px;
    }
    .presensi-item .num {
        font-size: 1rem;
    }
    .presensi-item .lbl {
        font-size: 0.55rem;
    }
    /* Activity list */
    .activity-list {
        gap: 10px;
    }
    .activity-item {
        padding: 10px;
        gap: 10px;
        border-radius: 8px;
    }
    .activity-avatar {
        width: 36px;
        height: 36px;
        font-size: 0.75rem;
        border-radius: 8px;
    }
    .activity-info h4 {
        font-size: 0.78rem;
    }
    .activity-info p {
        font-size: 0.68rem;
    }
    .activity-time {
        font-size: 0.65rem;
    }
    /* Riwayat list */
    .riwayat-list {
        gap: 10px;
    }
    .riwayat-item {
        padding: 10px;
        gap: 10px;
        border-radius: 10px;
    }
    .riwayat-date {
        min-width: 45px;
    }
    .riwayat-date .day {
        font-size: 1rem;
    }
    .riwayat-date .month {
        font-size: 0.65rem;
    }
    .riwayat-info h4 {
        font-size: 0.78rem;
    }
    .riwayat-info .meta {
        font-size: 0.68rem;
    }
    .riwayat-stat .count {
        font-size: 0.9rem;
    }
    .riwayat-stat .label {
        font-size: 0.6rem;
    }
    /* Empty state */
    .empty-state {
        padding: 35px 15px;
        border-radius: 12px;
    }
    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 14px;
    }
    .empty-state p {
        font-size: 0.9rem;
    }
    .empty-state .sub-text {
        font-size: 0.75rem;
    }
    /* Main grid gap */
    .main-grid {
        gap: 16px;
        margin-bottom: 16px;
    }
}

/* Extra Extra Small Mobile (max-width: 400px) */
@media (max-width: 400px) {
    .dashboard-content {
        padding: 10px 8px;
    }
    .welcome-content h1 {
        font-size: 1.1rem;
    }
    .stats-glass {
        flex-direction: row;
        flex-wrap: nowrap;
    }
    .stats-glass .stat-num {
        font-size: 1.1rem;
    }
    .stats-glass .stat-label {
        font-size: 0.6rem;
    }
    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 8px;
        padding: 12px 10px;
    }
    .stat-icon {
        margin: 0 auto;
    }
    .stat-info h3 {
        font-size: 1.1rem;
    }
    .presensi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .quick-btn {
        padding: 14px 8px;
    }
    .quick-btn i {
        font-size: 1.2rem;
    }
    .quick-btn span {
        font-size: 0.7rem;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
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

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <div class="greeting"><?= $greeting ?></div>
                        <h1><?= $asisten['nama'] ?></h1>
                        <div class="subtitle">
                            <i class="fas fa-flask me-1"></i> <?= $asisten['nama_mk'] ?: 'Asisten Laboratorium' ?>
                            &nbsp;•&nbsp; <?= format_tanggal(date('Y-m-d')) ?>
                        </div>
                    </div>
                    <div class="welcome-stats">
                        <div class="stats-glass">
                            <div class="stat-item">
                                <span class="stat-num"><?= count($all_jadwal) ?></span>
                                <span class="stat-label">Jadwal Hari Ini</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-num"><?= $stat['total'] ?></span>
                                <span class="stat-label">Minggu Ini</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-num"><?= $persen_hadir ?>%</span>
                                <span class="stat-label">Kehadiran</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alert Cards -->
                <div class="alert-grid">
                    <?php if (count($all_jadwal) > 0): ?>
                    <div class="alert-card info">
                        <i class="fas fa-calendar-check"></i>
                        <div class="alert-content">
                            <h4>Ada <?= count($all_jadwal) ?> Jadwal Hari Ini</h4>
                            <p>Jangan lupa generate QR untuk presensi mahasiswa</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($stat_pengganti['total'] > 0): ?>
                    <div class="alert-card warning">
                        <i class="fas fa-user-friends"></i>
                        <div class="alert-content">
                            <h4><?= $stat_pengganti['total'] ?> Jadwal Pengganti</h4>
                            <p>Anda menggantikan asisten lain minggu ini</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert-card success">
                        <i class="fas fa-chart-line"></i>
                        <div class="alert-content">
                            <h4>Kehadiran Bulan Ini: <?= $persen_hadir ?>%</h4>
                            <p>Total <?= $total_jadwal_bulan ?> jadwal mengajar</p>
                        </div>
                    </div>
                </div>
                
                <!-- Stat Cards -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= count($all_jadwal) ?></h3>
                            <p>Jadwal Hari Ini</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon yellow">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $stat_pengganti['total'] ?></h3>
                            <p>Sebagai Pengganti</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-jadwal-minggu"><?= $stat['total'] ?></h3>
                            <p>Jadwal Minggu Ini</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon cyan">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="stat-jadwal-bulan"><?= $total_jadwal_bulan ?></h3>
                            <p>Jadwal Bulan Ini</p>
                        </div>
                    </div>
                    <!-- [BARU] Card Rating -->
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $avg_rating ?></h3>
                            <p><?= $total_ulasan ?> Ulasan</p>
                        </div>
                    </div>
                </div>
                
                <!-- Main Grid -->
                <div class="main-grid">
                    <!-- Left Column: Jadwal Hari Ini -->
                    <div class="card-box">
                        <div class="card-header-custom">
                            <h3><i class="fas fa-calendar-day"></i> Jadwal Mengajar Hari Ini</h3>
                            <a href="index.php?page=asisten_jadwal" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body-custom">
                            <?php if (count($all_jadwal) > 0): ?>
                                <div class="jadwal-list">
                                    <?php foreach ($all_jadwal as $j): ?>
                                        <div class="jadwal-item <?= $j['tipe_jadwal'] == 'pengganti' ? 'pengganti' : '' ?>">
                                            <div class="jadwal-time">
                                                <span class="start-time"><?= format_waktu($j['jam_mulai']) ?></span>
                                                <div class="time-divider"></div>
                                                <span class="end-time"><?= format_waktu($j['jam_selesai']) ?></span>
                                            </div>
                                            <div class="jadwal-info">
                                                <h4><?= $j['nama_mk'] ?></h4>
                                                <div class="badges">
                                                    <span class="jadwal-badge kelas"><i class="fas fa-users"></i> <?= $j['nama_kelas'] ?></span>
                                                    <span class="jadwal-badge lab"><i class="fas fa-flask"></i> <?= $j['nama_lab'] ?></span>
                                                    <span class="jadwal-badge jenis-<?= $j['jenis'] ?>"><i class="fas fa-tag"></i> <?= ucfirst($j['jenis']) ?></span>
                                                    <?php if ($j['tipe_jadwal'] == 'pengganti'): ?>
                                                        <span class="jadwal-badge pengganti-badge"><i class="fas fa-user-friends"></i> Pengganti</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($j['tipe_jadwal'] == 'pengganti'): ?>
                                                    <div class="pengganti-info">
                                                        <i class="fas fa-info-circle me-1"></i>Menggantikan: <?= $j['asisten_asli'] ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($j['materi']): ?>
                                                    <div class="materi"><?= $j['materi'] ?></div>
                                                <?php endif; ?>
                                                <div class="jadwal-actions">
                                                    <a href="index.php?page=asisten_qrcode&jadwal=<?= $j['id'] ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-qrcode me-1"></i>Generate QR
                                                    </a>
                                                    <a href="index.php?page=asisten_monitoring&jadwal=<?= $j['id'] ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-tv me-1"></i>Monitoring
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>Tidak ada jadwal mengajar hari ini</p>
                                    <p class="sub-text">Nikmati waktu luang Anda!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        <!-- Kehadiran Bulan Ini -->
                        <div class="card-box">
                            <div class="card-header-custom">
                                <h3><i class="fas fa-chart-pie"></i> Kehadiran Bulan Ini</h3>
                            </div>
                            <div class="card-body-custom" id="kehadiran-container">
                                <div class="ring-container">
                                    <div class="ring-chart">
                                        <svg viewBox="0 0 160 160">
                                            <circle class="ring-bg" cx="80" cy="80" r="68"/>
                                            <circle class="ring-progress" id="ring-progress" cx="80" cy="80" r="68" 
                                                    stroke-dasharray="427" 
                                                    stroke-dashoffset="<?= 427 - (427 * $persen_hadir / 100) ?>"/>
                                        </svg>
                                        <div class="ring-text">
                                            <span class="persen" id="persen-hadir"><?= $persen_hadir ?>%</span>
                                            <span class="label">Hadir</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="presensi-grid">
                                    <div class="presensi-item hadir">
                                        <div class="num" id="stat-hadir"><?= $stat_hadir['hadir'] ?? 0 ?></div>
                                        <div class="lbl">Hadir</div>
                                    </div>
                                    <div class="presensi-item izin">
                                        <div class="num" id="stat-izin"><?= $stat_hadir['izin'] ?? 0 ?></div>
                                        <div class="lbl">Izin</div>
                                    </div>
                                    <div class="presensi-item sakit">
                                        <div class="num" id="stat-sakit"><?= $stat_hadir['sakit'] ?? 0 ?></div>
                                        <div class="lbl">Sakit</div>
                                    </div>
                                    <div class="presensi-item alpha">
                                        <div class="num" id="stat-alpha"><?= $stat_hadir['alpha'] ?? 0 ?></div>
                                        <div class="lbl">Alpha</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Presensi Terbaru -->
                        <div class="card-box">
                            <div class="card-header-custom">
                                <h3><i class="fas fa-history"></i> Presensi Terbaru</h3>
                            </div>
                            <div class="card-body-custom" id="recent-presensi-container">
                                <?php if (mysqli_num_rows($recent_presensi) > 0): ?>
                                    <div class="activity-list" id="activity-list">
                                        <?php while ($act = mysqli_fetch_assoc($recent_presensi)): ?>
                                            <div class="activity-item">
                                                <div class="activity-avatar <?= $act['status'] ?>">
                                                    <?= strtoupper(substr($act['nama_mhs'], 0, 2)) ?>
                                                </div>
                                                <div class="activity-info">
                                                    <h4><?= $act['nama_mhs'] ?></h4>
                                                    <p><?= $act['nama_mk'] ?> - <?= ucfirst($act['status']) ?></p>
                                                </div>
                                                <div class="activity-time">
                                                    <?= date('H:i', strtotime($act['waktu_presensi'])) ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state" id="empty-presensi">
                                        <i class="fas fa-inbox"></i>
                                        <p>Belum ada data presensi</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row -->
                <div class="main-grid" style="margin-bottom: 0;">
                    <!-- Riwayat Mengajar -->
                    <div class="card-box">
                        <div class="card-header-custom">
                            <h3><i class="fas fa-history"></i> Riwayat Mengajar Terakhir</h3>
                            <a href="index.php?page=asisten_rekap" class="btn btn-sm btn-outline-primary">Lihat Rekap</a>
                        </div>
                        <div class="card-body-custom">
                            <?php if (mysqli_num_rows($riwayat) > 0): ?>
                                <div class="riwayat-list">
                                    <?php while ($rw = mysqli_fetch_assoc($riwayat)): ?>
                                        <div class="riwayat-item">
                                            <div class="riwayat-date">
                                                <span class="day"><?= date('d', strtotime($rw['tanggal'])) ?></span>
                                                <span class="month"><?= date('M', strtotime($rw['tanggal'])) ?></span>
                                            </div>
                                            <div class="riwayat-info">
                                                <h4><?= $rw['nama_mk'] ?></h4>
                                                <div class="meta">
                                                    <?= $rw['nama_kelas'] ?> • <?= $rw['nama_lab'] ?> • <?= format_waktu($rw['jam_mulai']) ?>
                                                </div>
                                            </div>
                                            <div class="riwayat-stat">
                                                <div class="count"><?= $rw['total_hadir'] ?>/<?= $rw['total_mhs'] ?></div>
                                                <div class="label">Hadir</div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar"></i>
                                    <p>Belum ada riwayat mengajar</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card-box">
                        <div class="card-header-custom">
                            <h3><i class="fas fa-bolt"></i> Aksi Cepat</h3>
                        </div>
                        <div class="card-body-custom">
                            <div class="quick-grid">
                                <a href="index.php?page=asisten_qrcode" class="quick-btn">
                                    <i class="fas fa-qrcode"></i>
                                    <span>Generate QR</span>
                                </a>
                                <a href="index.php?page=asisten_monitoring" class="quick-btn">
                                    <i class="fas fa-tv"></i>
                                    <span>Monitoring</span>
                                </a>
                                <a href="index.php?page=asisten_jadwal" class="quick-btn">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Jadwal</span>
                                </a>
                                <a href="index.php?page=asisten_rekap" class="quick-btn">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Rekap</span>
                                </a>
                                <a href="index.php?page=asisten_presensi_manual" class="quick-btn">
                                    <i class="fas fa-edit"></i>
                                    <span>Presensi Manual</span>
                                </a>
                                <a href="index.php?page=asisten_pengajuan_izin" class="quick-btn">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Ajukan Izin</span>
                                </a>
                                <a href="index.php?page=asisten_izin" class="quick-btn">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Izin Mahasiswa</span>
                                </a>
                                <a href="index.php?page=logout" class="quick-btn">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time dashboard update - Polling setiap 5 detik
(function() {
    const REFRESH_INTERVAL = 5000; // 5 detik
    let refreshTimer = null;
    
    function updateDashboard() {
        fetch('api/get_dashboard_asisten.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStats(data.data);
                    updateRecentPresensi(data.data.recent_presensi);
                }
            })
            .catch(err => console.log('Dashboard refresh error:', err));
    }
    
    function updateStats(data) {
        // Update statistik cards
        const jadwalMinggu = document.getElementById('stat-jadwal-minggu');
        const jadwalBulan = document.getElementById('stat-jadwal-bulan');
        
        if (jadwalMinggu) jadwalMinggu.textContent = data.jadwal_minggu_ini;
        if (jadwalBulan) jadwalBulan.textContent = data.total_jadwal_bulan;
        
        // Update ring chart
        const ringProgress = document.getElementById('ring-progress');
        const persenHadir = document.getElementById('persen-hadir');
        
        if (ringProgress) {
            const offset = 427 - (427 * data.persen_hadir / 100);
            ringProgress.style.transition = 'stroke-dashoffset 0.5s ease';
            ringProgress.setAttribute('stroke-dashoffset', offset);
        }
        if (persenHadir) persenHadir.textContent = data.persen_hadir + '%';
        
        // Update presensi grid
        const statHadir = document.getElementById('stat-hadir');
        const statIzin = document.getElementById('stat-izin');
        const statSakit = document.getElementById('stat-sakit');
        const statAlpha = document.getElementById('stat-alpha');
        
        if (statHadir) animateNumber(statHadir, parseInt(statHadir.textContent), data.stat_hadir.hadir);
        if (statIzin) animateNumber(statIzin, parseInt(statIzin.textContent), data.stat_hadir.izin);
        if (statSakit) animateNumber(statSakit, parseInt(statSakit.textContent), data.stat_hadir.sakit);
        if (statAlpha) animateNumber(statAlpha, parseInt(statAlpha.textContent), data.stat_hadir.alpha);
    }
    
    function animateNumber(element, from, to) {
        if (from === to) return;
        
        const duration = 300;
        const startTime = performance.now();
        
        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.round(from + (to - from) * progress);
            element.textContent = current;
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }
    
    function updateRecentPresensi(recentList) {
        const container = document.getElementById('recent-presensi-container');
        if (!container) return;
        
        if (recentList.length === 0) {
            container.innerHTML = `
                <div class="empty-state" id="empty-presensi">
                    <i class="fas fa-inbox"></i>
                    <p>Belum ada data presensi</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="activity-list" id="activity-list">';
        recentList.forEach(act => {
            const initials = act.nama_mhs.substring(0, 2).toUpperCase();
            const statusCapitalized = act.status.charAt(0).toUpperCase() + act.status.slice(1);
            
            html += `
                <div class="activity-item">
                    <div class="activity-avatar ${act.status}">
                        ${initials}
                    </div>
                    <div class="activity-info">
                        <h4>${act.nama_mhs}</h4>
                        <p>${act.nama_mk} - ${statusCapitalized}</p>
                    </div>
                    <div class="activity-time">
                        ${act.waktu_presensi}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    // Start polling saat halaman ready
    function startPolling() {
        // Jalankan sekali setelah delay awal
        refreshTimer = setInterval(updateDashboard, REFRESH_INTERVAL);
    }
    
    function stopPolling() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }
    
    // Pause polling ketika tab tidak aktif untuk hemat resource
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            updateDashboard(); // Langsung update saat kembali
            startPolling();
        }
    });
    
    // Start polling saat DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startPolling);
    } else {
        startPolling();
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
