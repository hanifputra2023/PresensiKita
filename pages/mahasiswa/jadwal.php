<?php
$page = 'mahasiswa_jadwal';
$mahasiswa = get_mahasiswa_login();
$kelas = $mahasiswa['kode_kelas'];
$nim = $mahasiswa['nim'];
$sesi = $mahasiswa['sesi'] ?? 1;

// Variabel waktu untuk cek status jadwal
$today = date('Y-m-d');
$now_time = date('H:i:s');
$toleransi_sebelum = TOLERANSI_SEBELUM;
$list_mk = mysqli_query($conn, "SELECT DISTINCT mk.kode_mk, mk.nama_mk 
                                FROM jadwal j
                                JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                WHERE j.kode_kelas = '$kelas'
                                AND (j.sesi = 0 OR j.sesi = '$sesi')
                                ORDER BY mk.nama_mk");

// Ambil daftar bulan yang tersedia di jadwal
$list_bulan_query = mysqli_query($conn, "SELECT DISTINCT DATE_FORMAT(tanggal, '%Y-%m') as bulan 
                                         FROM jadwal 
                                         WHERE kode_kelas = '$kelas' 
                                         AND (sesi = 0 OR sesi = '$sesi')
                                         ORDER BY bulan DESC");

$filter_mk = isset($_GET['mk']) ? mysqli_real_escape_string($conn, $_GET['mk']) : '';
$filter_bulan = isset($_GET['bulan']) ? mysqli_real_escape_string($conn, $_GET['bulan']) : '';

// [FIX] Logika Tukar Sesi / Shift
// Menampilkan jadwal jika:
// 1. Mahasiswa punya record presensi di jadwal tersebut (meskipun sesi lama/beda)
// 2. ATAU (Jadwal sesuai sesi saat ini DAN Mahasiswa TIDAK punya record di jadwal lain untuk pertemuan yang sama)
// 3. ATAU Mahasiswa telah di-ACC pindah/tukar jadwal ke sesi ini lewat tukar_jadwal_sementara
$session_swap_logic = "
    AND (
        p.id IS NOT NULL
        OR
        (
            (j.sesi = 0 OR j.sesi = '$sesi')
            AND NOT EXISTS (
                SELECT 1 FROM presensi_mahasiswa pm2
                JOIN jadwal j2 ON pm2.jadwal_id = j2.id
                WHERE pm2.nim = '$nim'
                AND j2.kode_mk = j.kode_mk
                AND j2.pertemuan_ke = j.pertemuan_ke
                AND j2.id != j.id
            )
            AND NOT EXISTS (
                SELECT 1 FROM tukar_jadwal_sementara tjs 
                WHERE tjs.status = 'disetujui' 
                AND ((tjs.nim_pengaju = '$nim' AND tjs.jadwal_awal_id = j.id) OR (tjs.nim_dituju = '$nim' AND tjs.jadwal_tujuan_id = j.id))
            )
        )
        OR EXISTS (
            SELECT 1 FROM tukar_jadwal_sementara tjs2
            WHERE tjs2.status = 'disetujui'
            AND ((tjs2.nim_pengaju = '$nim' AND tjs2.jadwal_tujuan_id = j.id) OR (tjs2.nim_dituju = '$nim' AND tjs2.jadwal_awal_id = j.id))
        )
    )
";

$where_clause = "WHERE j.kode_kelas = '$kelas'
                 $session_swap_logic
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
                 )";

if (!empty($filter_mk)) $where_clause .= " AND j.kode_mk = '$filter_mk'";
if (!empty($filter_bulan)) $where_clause .= " AND DATE_FORMAT(j.tanggal, '%Y-%m') = '$filter_bulan'";

// Ambil jadwal MATERI dan UJIKOM (bukan inhall)
// Inhall hanya ditampilkan jika mahasiswa terdaftar di penggantian_inhall
$jadwal = mysqli_query($conn, "SELECT j.*, l.nama_lab, mk.nama_mk, p.status as presensi_status,
                                a1.nama as asisten1_nama, a2.nama as asisten2_nama,
                                (SELECT COUNT(*) FROM materi_perkuliahan mp WHERE mp.id_jadwal = j.id) as jumlah_materi,
                                (SELECT p2.status FROM presensi_mahasiswa p2 
                                 JOIN jadwal j2 ON p2.jadwal_id = j2.id 
                                 WHERE p2.nim = '$nim' 
                                 AND j2.kode_mk = j.kode_mk 
                                 AND j2.pertemuan_ke = j.pertemuan_ke 
                                 AND j2.id != j.id LIMIT 1) as status_other_session
                                FROM jadwal j 
                                LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                LEFT JOIN presensi_mahasiswa p ON p.jadwal_id = j.id AND p.nim = '$nim'
                                LEFT JOIN asisten a1 ON j.kode_asisten_1 = a1.kode_asisten
                                LEFT JOIN asisten a2 ON j.kode_asisten_2 = a2.kode_asisten
                                $where_clause
                                ORDER BY j.tanggal, j.jam_mulai");

// Ambil tanggal daftar mahasiswa
$tanggal_daftar = $mahasiswa['tanggal_daftar'];

// Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $filename = 'jadwal_praktikum_' . $nim . '_' . date('Y-m-d_His') . '.xls';
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    echo '<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000000; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th>Pertemuan</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Lab</th>
                    <th>Mata Kuliah</th>
                    <th>Materi</th>
                    <th>Asisten</th>
                    <th>Jenis</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

    mysqli_data_seek($jadwal, 0);
    while ($row = mysqli_fetch_assoc($jadwal)) {
        $asisten = $row['asisten1_nama'] ?: '-';
        if ($row['asisten2_nama']) $asisten .= ', ' . $row['asisten2_nama'];
        $status = $row['presensi_status'] ? ucfirst($row['presensi_status']) : 'Belum';
        
        echo '<tr>
            <td style="text-align:center;">' . $row['pertemuan_ke'] . '</td>
            <td>' . format_tanggal($row['tanggal']) . '</td>
            <td>' . format_waktu($row['jam_mulai']) . ' - ' . format_waktu($row['jam_selesai']) . '</td>
            <td>' . $row['nama_lab'] . '</td>
            <td>' . $row['nama_mk'] . '</td>
            <td>' . $row['materi'] . '</td>
            <td>' . $asisten . '</td>
            <td>' . ucfirst($row['jenis']) . '</td>
            <td>' . $status . '</td>
        </tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

// Export ICS (Calendar)
if (isset($_GET['export']) && $_GET['export'] == 'ics') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $filename = 'jadwal_praktikum_' . $nim . '.ics';
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//" . APP_NAME . "//Mahasiswa//ID\r\n";
    echo "CALSCALE:GREGORIAN\r\n";
    echo "METHOD:PUBLISH\r\n";
    
    mysqli_data_seek($jadwal, 0);
    while ($row = mysqli_fetch_assoc($jadwal)) {
        // Format waktu untuk ICS: YYYYMMDDTHHMMSS
        $start_time = date('Ymd\THis', strtotime($row['tanggal'] . ' ' . $row['jam_mulai']));
        $end_time = date('Ymd\THis', strtotime($row['tanggal'] . ' ' . $row['jam_selesai']));
        $now = date('Ymd\THis\Z');
        
        $asisten = $row['asisten1_nama'] ?: '-';
        if ($row['asisten2_nama']) $asisten .= ', ' . $row['asisten2_nama'];
        
        $description = "Mata Kuliah: " . $row['nama_mk'] . "\\n";
        $description .= "Materi: " . $row['materi'] . "\\n";
        $description .= "Asisten: " . $asisten . "\\n";
        $description .= "Pertemuan ke: " . $row['pertemuan_ke'];
        
        echo "BEGIN:VEVENT\r\n";
        echo "UID:jadwal-" . $row['id'] . "@" . $_SERVER['HTTP_HOST'] . "\r\n";
        echo "DTSTAMP:" . $now . "\r\n";
        echo "DTSTART:" . $start_time . "\r\n";
        echo "DTEND:" . $end_time . "\r\n";
        echo "SUMMARY:" . $row['nama_mk'] . " (" . $row['nama_lab'] . ")\r\n";
        echo "DESCRIPTION:" . $description . "\r\n";
        echo "LOCATION:" . $row['nama_lab'] . "\r\n";
        echo "END:VEVENT\r\n";
    }
    
    echo "END:VCALENDAR";
    exit;
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* ===== WELCOME BANNER JADWAL ===== */
.welcome-banner-jadwal {
    background: var(--banner-gradient);
    border-radius: 24px;
    padding: 40px;
    color: white;
    box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
    animation: fadeInUp 0.5s ease;
    position: relative;
    overflow: hidden;
}

.welcome-banner-jadwal::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse-glow-jadwal 4s ease-in-out infinite;
}

@keyframes pulse-glow-jadwal {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 0.6; }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.welcome-banner-jadwal h1 {
    font-size: 32px;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
}

.welcome-banner-jadwal .banner-subtitle {
    font-size: 16px;
    opacity: 0.95;
    position: relative;
    z-index: 1;
}

.welcome-banner-jadwal .banner-icon {
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

.welcome-banner-jadwal .banner-badge {
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
}

.welcome-banner-jadwal .btn-banner {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
}

.welcome-banner-jadwal .btn-banner:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    color: white;
}

/* Dark Mode Support */
[data-theme="dark"] .welcome-banner-jadwal {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

/* Responsive Design */
@media (max-width: 576px) {
    .welcome-banner-jadwal {
        padding: 24px;
        border-radius: 16px;
    }
    
    .welcome-banner-jadwal h1 {
        font-size: 24px;
    }
    
    .welcome-banner-jadwal .banner-icon {
        width: 50px;
        height: 50px;
        font-size: 22px;
    }
    
    .welcome-banner-jadwal .banner-buttons {
        flex-direction: column;
        width: 100%;
        gap: 10px;
    }
    
    .welcome-banner-jadwal .btn-banner {
        width: 100%;
        text-align: center;
    }
}

/* Fix Hover Table di Dark Mode - Override Bootstrap table-hover */
[data-theme="dark"] .table-hover > tbody > tr:hover {
    --bs-table-hover-bg: rgba(255, 255, 255, 0.08);
    --bs-table-accent-bg: rgba(255, 255, 255, 0.08);
    background-color: rgba(255, 255, 255, 0.08) !important;
}
[data-theme="dark"] .table-hover > tbody > tr:hover > * {
    background-color: rgba(255, 255, 255, 0.08) !important;
    color: var(--text-main, #e2e8f0);
}

/* Override Bootstrap Contextual Classes for Dark Mode */
[data-theme="dark"] .table-success,
[data-theme="dark"] .table-success > * {
    --bs-table-bg: rgba(25, 135, 84, 0.15);
    background-color: rgba(25, 135, 84, 0.15) !important;
    color: #75b798 !important;
    border-color: rgba(25, 135, 84, 0.2);
}
[data-theme="dark"] .table-hover > tbody > tr.table-success:hover,
[data-theme="dark"] .table-hover > tbody > tr.table-success:hover > * {
    --bs-table-hover-bg: rgba(25, 135, 84, 0.3);
    --bs-table-accent-bg: rgba(25, 135, 84, 0.3);
    background-color: rgba(25, 135, 84, 0.3) !important;
    color: #a3cfbb !important;
}

[data-theme="dark"] .table-warning,
[data-theme="dark"] .table-warning > * {
    --bs-table-bg: rgba(255, 193, 7, 0.1);
    background-color: rgba(255, 193, 7, 0.1) !important;
    color: #ffda6a !important;
    border-color: rgba(255, 193, 7, 0.2);
}
[data-theme="dark"] .table-hover > tbody > tr.table-warning:hover,
[data-theme="dark"] .table-hover > tbody > tr.table-warning:hover > * {
    --bs-table-hover-bg: rgba(255, 193, 7, 0.25);
    --bs-table-accent-bg: rgba(255, 193, 7, 0.25);
    background-color: rgba(255, 193, 7, 0.25) !important;
    color: #ffe69c !important;
}

[data-theme="dark"] .table-primary,
[data-theme="dark"] .table-primary > * {
    --bs-table-bg: rgba(13, 110, 253, 0.1);
    background-color: rgba(13, 110, 253, 0.1) !important;
    color: #6ea8fe !important;
    border-color: rgba(13, 110, 253, 0.2);
}
[data-theme="dark"] .table-hover > tbody > tr.table-primary:hover,
[data-theme="dark"] .table-hover > tbody > tr.table-primary:hover > * {
    --bs-table-hover-bg: rgba(13, 110, 253, 0.25);
    --bs-table-accent-bg: rgba(13, 110, 253, 0.25);
    background-color: rgba(13, 110, 253, 0.25) !important;
    color: #9ec5fe !important;
}

/* Row with text-muted (past/ended schedules) hover */
[data-theme="dark"] .table-hover > tbody > tr.text-muted:hover,
[data-theme="dark"] .table-hover > tbody > tr.text-muted:hover > * {
    --bs-table-hover-bg: rgba(255, 255, 255, 0.05);
    --bs-table-accent-bg: rgba(255, 255, 255, 0.05);
    background-color: rgba(255, 255, 255, 0.05) !important;
    color: #94a3b8 !important;
}

/* Fix text muted in dark mode table */
[data-theme="dark"] .table .text-muted {
    color: #94a3b8 !important;
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
                <div class="welcome-banner-jadwal mb-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="banner-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <h1 class="mb-1">Jadwal Praktikum</h1>
                                    <p class="banner-subtitle mb-0">Kelas <?= $mahasiswa['nama_kelas'] ?></p>
                                </div>
                            </div>
                            <span class="banner-badge">
                                <i class="fas fa-clock me-1"></i>Semester Aktif
                            </span>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap banner-buttons">
                            <a href="index.php?page=mahasiswa_jadwal&export=excel&mk=<?= $filter_mk ?>&bulan=<?= $filter_bulan ?>" class="btn btn-banner">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </a>
                            <a href="index.php?page=mahasiswa_jadwal&export=ics&mk=<?= $filter_mk ?>&bulan=<?= $filter_bulan ?>" class="btn btn-banner">
                            <i class="fas fa-calendar-plus me-1"></i>Kalender
                        </a>
                            <button onclick="exportPDF()" class="btn btn-banner">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                    </div>
                </div>
                </div>
                
                <!-- Filter Card -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="index.php">
                            <input type="hidden" name="page" value="mahasiswa_jadwal">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Mata Kuliah</label>
                                    <select name="mk" class="form-select">
                                        <option value="">Semua Mata Kuliah</option>
                                        <?php while($mk = mysqli_fetch_assoc($list_mk)): ?>
                                            <option value="<?= $mk['kode_mk'] ?>" <?= $filter_mk == $mk['kode_mk'] ? 'selected' : '' ?>>
                                                <?= $mk['nama_mk'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Bulan</label>
                                    <select name="bulan" class="form-select">
                                        <option value="">Semua Bulan</option>
                                        <?php 
                                        $bulan_indo = [
                                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                        ];
                                        while($b = mysqli_fetch_assoc($list_bulan_query)): 
                                            $parts = explode('-', $b['bulan']);
                                            $label = $bulan_indo[$parts[1]] . ' ' . $parts[0];
                                        ?>
                                            <option value="<?= $b['bulan'] ?>" <?= $filter_bulan == $b['bulan'] ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted d-none d-md-block">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary flex-grow-1">
                                            <i class="fas fa-filter me-1"></i>Filter
                                        </button>
                                        <a href="index.php?page=mahasiswa_jadwal" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" title="Reset Filter" style="width: 38px; height: 38px;">
                                            <i class="fas fa-redo"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
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
                                        
                                        // [MODIFIKASI] Cek kehadiran di sesi lain
                                        $status_display = $j['presensi_status'];
                                        if (empty($status_display) && !empty($j['status_other_session'])) {
                                            $status_display = $j['status_other_session'];
                                        }
                                        
                                        // Tentukan class row
                                        $row_class = '';
                                        if ($is_ended && !$jadwal_sebelum_daftar) {
                                            // Jika sudah hadir di sesi lain, jangan dimute
                                            $row_class = ($status_display) ? '' : 'text-muted';
                                        } elseif ($sedang_aktif) {
                                            $row_class = 'table-success';
                                        } elseif ($belum_waktunya) {
                                            $row_class = 'table-warning';
                                        } elseif ($is_today) {
                                            $row_class = 'table-primary';
                                        }
                                    
                                    // Cek Eligibilitas Responsi
                                    $eligibility = ['eligible' => true];
                                    $eligibility_msg = '';
                                    if ($j['jenis'] == 'responsi') {
                                        $eligibility = cek_eligibilitas_responsi($nim, $j['kode_mk'], $kelas);
                                        if (!$eligibility['eligible']) {
                                            $eligibility_msg = "Kehadiran " . round($eligibility['percentage']) . "%. Wajib Inhall (Min 75%)";
                                        }
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
                                                <?php if ($status_display && $status_display != 'belum'): ?>
                                                    <span class="badge bg-<?= $status_display == 'hadir' ? 'success' : ($status_display == 'izin' ? 'warning' : ($status_display == 'sakit' ? 'info' : 'danger')) ?>">
                                                        <?= ucfirst($status_display) ?>
                                                        <?= !empty($j['status_other_session']) && empty($j['presensi_status']) ? '*' : '' ?>
                                                    </span>
                                                <?php elseif ($jadwal_sebelum_daftar): ?>
                                                    <span class="badge bg-secondary" title="Jadwal sebelum tanggal pendaftaran">-</span>
                                                <?php elseif ($is_ended && empty($status_display)): ?>
                                                    <span class="badge bg-danger">Alpha</span>
                                            <?php elseif (!$eligibility['eligible']): ?>
                                                <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Dilarang</span>
                                                <div class="small text-danger mt-1" style="font-size: 0.65rem;"><?= $eligibility_msg ?></div>
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
                                
                                // Cek Eligibilitas Responsi
                                $eligibility = ['eligible' => true];
                                $eligibility_msg = '';
                                if ($j['jenis'] == 'responsi') {
                                    $eligibility = cek_eligibilitas_responsi($nim, $j['kode_mk'], $kelas);
                                    if (!$eligibility['eligible']) {
                                        $eligibility_msg = "Kehadiran " . round($eligibility['percentage']) . "%. Wajib Inhall (Min 75%)";
                                    }
                                }
                                
                                // Cek apakah jadwal sebelum tanggal daftar mahasiswa
                                $jadwal_sebelum_daftar = strtotime($j['tanggal']) < strtotime($tanggal_daftar);
                                
                                // [MODIFIKASI] Cek kehadiran di sesi lain
                                $status_display = $j['presensi_status'];
                                if (empty($status_display) && !empty($j['status_other_session'])) {
                                    $status_display = $j['status_other_session'];
                                }
                                
                                // Tentukan border dan style
                                $border_class = '';
                                $card_style = '';
                                if ($is_ended && !$jadwal_sebelum_daftar && empty($status_display)) {
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
                                                <i class="fas fa-clock me-1 text-muted"></i><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?>
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
                                            <?php if ($status_display && $status_display != 'belum'): ?>
                                                <span class="badge bg-<?= $status_display == 'hadir' ? 'success' : ($status_display == 'izin' ? 'warning' : ($status_display == 'sakit' ? 'info' : 'danger')) ?>">
                                                    <?= ucfirst($status_display) ?>
                                                    <?= !empty($j['status_other_session']) && empty($j['presensi_status']) ? '*' : '' ?>
                                                </span>
                                            <?php elseif ($jadwal_sebelum_daftar): ?>
                                                <span class="badge bg-secondary" title="Jadwal sebelum tanggal pendaftaran">-</span>
                                            <?php elseif ($is_ended && empty($status_display)): ?>
                                                <span class="badge bg-danger">Alpha</span>
                                        <?php elseif (!$eligibility['eligible']): ?>
                                            <span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Dilarang</span>
                                            <div class="small text-danger mt-1" style="font-size: 0.65rem;"><?= $eligibility_msg ?></div>
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

<!-- Print Only Content for PDF -->
<div class="print-only" style="display: none;">
    <div class="text-center mb-4">
        <h3>Jadwal Praktikum</h3>
        <p class="mb-1">Nama: <?= $mahasiswa['nama'] ?> (<?= $mahasiswa['nim'] ?>)</p>
        <p>Kelas: <?= $mahasiswa['nama_kelas'] ?></p>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>P</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Mata Kuliah</th>
                <th>Lab</th>
                <th>Materi</th>
                <th>Asisten</th>
                <th>Jenis</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            mysqli_data_seek($jadwal, 0);
            while ($j = mysqli_fetch_assoc($jadwal)): 
                $status_pdf = $j['presensi_status'] ? ucfirst($j['presensi_status']) : 'Belum';
                $asisten_pdf = $j['asisten1_nama'] ?: '-';
                if ($j['asisten2_nama']) $asisten_pdf .= ', ' . $j['asisten2_nama'];
            ?>
            <tr>
                <td><?= $j['pertemuan_ke'] ?></td>
                <td><?= format_tanggal($j['tanggal']) ?></td>
                <td><?= format_waktu($j['jam_mulai']) ?> - <?= format_waktu($j['jam_selesai']) ?></td>
                <td><?= $j['nama_mk'] ?></td>
                <td><?= $j['nama_lab'] ?></td>
                <td><?= $j['materi'] ?></td>
                <td><?= $asisten_pdf ?></td>
                <td><?= ucfirst($j['jenis']) ?></td>
                <td><?= $status_pdf ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Library html2pdf.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    const originalElement = document.querySelector('.print-only');
    const elementToPrint = originalElement.cloneNode(true);
    
    // Force light theme styles for PDF
    elementToPrint.style.display = 'block';
    elementToPrint.style.backgroundColor = '#ffffff';
    elementToPrint.style.color = '#000000';
    elementToPrint.style.padding = '20px';
    elementToPrint.style.fontSize = '10px'; // Smaller font for landscape

    // Force all child elements to have black text
    elementToPrint.querySelectorAll('*').forEach(el => {
        el.style.color = '#000000';
    });
    
    // Style the table header specifically
    const tableHeader = elementToPrint.querySelector('thead tr');
    if (tableHeader) {
        tableHeader.style.backgroundColor = '#0066cc'; // A nice blue header
        tableHeader.querySelectorAll('th').forEach(th => {
            th.style.color = '#020202ff';
            th.style.padding = '5px';
            th.style.border = '1px solid #8a8a8aff';
        });
    }

    // Style table cells
    elementToPrint.querySelectorAll('td, th').forEach(cell => {
        cell.style.padding = '5px';
        cell.style.border = '1px solid #dddddd';
    });

    // Create a temporary wrapper to render the element off-screen
    const wrapper = document.createElement('div');
    wrapper.style.position = 'fixed';
    wrapper.style.left = '-10000px';
    wrapper.style.top = '0';
    wrapper.style.width = '1100px'; // A reasonable width for landscape A4
    wrapper.appendChild(elementToPrint);
    document.body.appendChild(wrapper);
    
    const opt = {
        margin: 10,
        filename: 'jadwal_praktikum_<?= $nim ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
        pagebreak: { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(elementToPrint).save().then(function() {
        // Clean up by removing the temporary wrapper
        document.body.removeChild(wrapper);
    });
}
</script>

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



