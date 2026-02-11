<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

// Fungsi untuk mengecek login
function cek_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?page=login");
        exit;
    }
}

// Fungsi untuk mengecek role
function cek_role($role_yang_diizinkan) {
    if (!in_array($_SESSION['role'], $role_yang_diizinkan)) {
        header("Location: index.php?page=unauthorized");
        exit;
    }
}

// Fungsi untuk mendapatkan data user yang login
function get_user_login() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Fungsi untuk mendapatkan data mahasiswa yang login
function get_mahasiswa_login() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT m.*, k.nama_kelas
                                   FROM mahasiswa m 
                                   JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                   WHERE m.user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Fungsi untuk mendapatkan data asisten yang login
function get_asisten_login() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT a.*, mk.nama_mk 
                                   FROM asisten a 
                                   LEFT JOIN mata_kuliah mk ON a.kode_mk = mk.kode_mk 
                                   WHERE a.user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Fungsi escape input
function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

// Helper: cek apakah kolom ada di sebuah tabel (cached)
function column_exists($table, $column) {
    global $conn;
    static $col_cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $col_cache)) return $col_cache[$key];
    $tbl = mysqli_real_escape_string($conn, $table);
    $col = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `" . $tbl . "` LIKE '" . $col . "'");
    $exists = ($res && mysqli_num_rows($res) > 0);
    $col_cache[$key] = $exists;
    return $exists;
}

// Fungsi format tanggal
function format_tanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Fungsi format waktu
function format_waktu($waktu) {
    return date('H:i', strtotime($waktu));
}

// Fungsi format tanggal dan waktu
function format_tanggal_waktu($datetime) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return '-';
    }
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $timestamp = strtotime($datetime);
    $tanggal = date('d', $timestamp);
    $bulan_index = (int)date('m', $timestamp);
    $tahun = date('Y', $timestamp);
    $waktu = date('H:i', $timestamp);
    
    return $tanggal . ' ' . $bulan[$bulan_index] . ' ' . $tahun . ' ' . $waktu;
}

// Fungsi untuk generate QR code unik
function generate_qr_code() {
    return bin2hex(random_bytes(16)) . '_' . time();
}

// Fungsi ambil setting dari DB (Cached)
function get_setting($key, $default = '') {
    global $conn;
    static $settings_cache = null;
    
    if ($settings_cache === null) {
        $settings_cache = [];
        // Cek tabel exists dulu untuk menghindari error jika belum setup
        $check = mysqli_query($conn, "SHOW TABLES LIKE 'app_settings'");
        if (mysqli_num_rows($check) > 0) {
            $q = mysqli_query($conn, "SELECT setting_key, setting_value FROM app_settings");
            while ($row = mysqli_fetch_assoc($q)) {
                $settings_cache[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    
    return isset($settings_cache[$key]) ? $settings_cache[$key] : $default;
}

// Fungsi untuk validasi waktu presensi
function validasi_waktu($jam_mulai, $jam_selesai) {
    $sekarang = time();
    
    // Tanpa toleransi: Waktu presensi harus tepat antara jam mulai dan jam selesai
    $mulai = strtotime($jam_mulai);
    $akhir = strtotime($jam_selesai);
    
    return ($sekarang >= $mulai && $sekarang <= $akhir);
}

// Fungsi untuk log aktivitas
function log_aktivitas($user_id, $aksi, $tabel, $id_record, $detail) {
    global $conn;
    $aksi = escape($aksi);
    $tabel = escape($tabel);
    $detail = escape($detail);
    $stmt = mysqli_prepare($conn, "INSERT INTO log_presensi (user_id, aksi, tabel, id_record, detail) 
                         VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $aksi, $tabel, $id_record, $detail);
    mysqli_stmt_execute($stmt);
}

// Fungsi alert
function set_alert($type, $message) {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
}

function show_alert() {
    if (isset($_SESSION['alert'])) {
        $type = $_SESSION['alert']['type'];
        $message = $_SESSION['alert']['message'];
        unset($_SESSION['alert']);
        return "<div class='alert alert-$type alert-dismissible fade show'>
                    $message
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}

// ============ FUNGSI HELPER TAMBAHAN ============

// Fungsi untuk mengecek menu aktif (untuk sidebar)
function is_active($check_page) {
    global $page;
    if (is_array($check_page)) {
        return in_array($page, $check_page) ? 'active' : '';
    }
    return $page == $check_page ? 'active' : '';
}

// Fungsi sapaan berdasarkan waktu
function sapaan_waktu() {
    $jam = date('H');
    if ($jam >= 5 && $jam < 11) return "Selamat Pagi";
    if ($jam >= 11 && $jam < 15) return "Selamat Siang";
    if ($jam >= 15 && $jam < 18) return "Selamat Sore";
    return "Selamat Malam";
}

// Fungsi Generate CSRF Token (Keamanan)
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fungsi Input Hidden CSRF (Helper View)
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

// Fungsi Validasi CSRF Token
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('CSRF Token Validation Failed. Harap refresh halaman.');
    }
}

/**
 * Catat kehadiran asisten saat MEMULAI sesi praktikum
 * Dipanggil saat asisten:
 * - Generate QR Code (qrcode.php)
 * - Input presensi manual pertama kali (presensi_manual.php)
 * 
 * TIDAK dipanggil saat buka halaman saja, agar asisten masih bisa izin
 * jika ada keperluan mendadak sebelum memulai sesi.
 * 
 * @param string $kode_asisten - Kode asisten
 * @param int $jadwal_id - ID jadwal
 * @param bool $is_pengganti - Apakah asisten ini sebagai pengganti (default: false)
 * @return bool - true jika berhasil/sudah hadir, false jika gagal/sudah izin
 */
function catat_hadir_asisten($kode_asisten, $jadwal_id, $is_pengganti = false) {
    global $conn;
    
    // Jika sebagai pengganti, tidak perlu buat record baru
    // Kehadiran pengganti sudah tercatat di field 'pengganti' milik asisten yang izin
    if ($is_pengganti) {
        return true; // Pengganti boleh akses, tapi tidak buat record hadir baru
    }
    
    // Cek apakah ini memang jadwal milik asisten ini - prepared statement
    $stmt_jadwal = mysqli_prepare($conn, "SELECT id FROM jadwal 
                                                       WHERE id = ? 
                                                       AND (kode_asisten_1 = ? OR kode_asisten_2 = ?)");
    mysqli_stmt_bind_param($stmt_jadwal, "iss", $jadwal_id, $kode_asisten, $kode_asisten);
    mysqli_stmt_execute($stmt_jadwal);
    $jadwal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jadwal));
    
    if (!$jadwal) {
        // Bukan jadwal milik asisten ini, jangan catat hadir
        return false;
    }
    
    // Cek apakah sudah ada record untuk jadwal ini - prepared statement
    $stmt_existing = mysqli_prepare($conn, "SELECT id, status FROM absen_asisten 
                                                         WHERE jadwal_id = ? AND kode_asisten = ?");
    mysqli_stmt_bind_param($stmt_existing, "is", $jadwal_id, $kode_asisten);
    mysqli_stmt_execute($stmt_existing);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_existing));
    
    $jam_masuk = date('H:i:s');
    
    if ($existing) {
        // Jika statusnya bukan izin/sakit, update ke hadir
        if ($existing['status'] == 'hadir') {
            // Sudah hadir, tidak perlu update
            return true;
        }
        // Jika izin/sakit, jangan override (asisten sudah mengajukan izin)
        return false;
    } else {
        // Insert baru sebagai hadir - prepared statement
        $status = 'hadir';
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO absen_asisten (jadwal_id, kode_asisten, status, jam_masuk) 
                              VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_ins, "isss", $jadwal_id, $kode_asisten, $status, $jam_masuk);
        mysqli_stmt_execute($stmt_ins);
        return true;
    }
}

/**
 * Update jam keluar asisten
 */
function update_jam_keluar_asisten($kode_asisten, $jadwal_id) {
    global $conn;
    $jam_keluar = date('H:i:s');
    $stmt = mysqli_prepare($conn, "UPDATE absen_asisten SET jam_keluar = ? 
                          WHERE jadwal_id = ? AND kode_asisten = ? AND status = 'hadir'");
    mysqli_stmt_bind_param($stmt, "sis", $jam_keluar, $jadwal_id, $kode_asisten);
    mysqli_stmt_execute($stmt);
}

// ============ FUNGSI AUTO ALPHA ============

/**
 * Buat record 'belum' untuk semua mahasiswa di jadwal yang aktif/akan datang
 * Dipanggil saat QR code di-generate atau jadwal dimulai
 * Ini memastikan semua mahasiswa punya record sebelum jadwal selesai
 * 
 * CATATAN: Untuk jadwal INHALL, hanya mahasiswa yang terdaftar di penggantian_inhall
 * yang akan dibuatkan record presensi
 */
function init_presensi_jadwal($jadwal_id) {
    global $conn;
    
    // Ambil info jadwal - prepared statement
    $stmt_jadwal = mysqli_prepare($conn, "SELECT kode_kelas, kode_mk, tanggal, jam_mulai, jenis, sesi FROM jadwal WHERE id = ?");
    mysqli_stmt_bind_param($stmt_jadwal, "i", $jadwal_id);
    mysqli_stmt_execute($stmt_jadwal);
    $jadwal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jadwal));
    if (!$jadwal) return 0;
    
    $kode_kelas = $jadwal['kode_kelas'];
    $sesi_jadwal = $jadwal['sesi']; // 0 = semua, >0 = sesi tertentu
    $kode_mk = $jadwal['kode_mk'];
    $tanggal_jadwal = $jadwal['tanggal'];
    $jam_mulai = $jadwal['jam_mulai'];
    $jenis = $jadwal['jenis'];
    $total = 0;
    
    // Jika jadwal INHALL, hanya init untuk mahasiswa yang terdaftar inhall untuk MK ini
    if ($jenis == 'inhall') {
        $stmt_mhs_inhall = mysqli_prepare($conn, "SELECT pi.nim 
                                            FROM penggantian_inhall pi
                                            JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                            WHERE pi.status = 'terdaftar'
                                            AND j.kode_mk = ?
                                            AND pi.nim NOT IN (
                                                SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = ?
                                            )");
        mysqli_stmt_bind_param($stmt_mhs_inhall, "si", $kode_mk, $jadwal_id);
        mysqli_stmt_execute($stmt_mhs_inhall);
        $mhs_inhall = mysqli_stmt_get_result($stmt_mhs_inhall);
        
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, verified_by_system) 
                                  VALUES (?, ?, 'belum', '', 0)");
        while ($mhs = mysqli_fetch_assoc($mhs_inhall)) {
            $nim = $mhs['nim'];
            mysqli_stmt_bind_param($stmt_ins, "is", $jadwal_id, $nim);
            mysqli_stmt_execute($stmt_ins);
            $total++;
        }
    } else {
        // Untuk jadwal MATERI dan UJIKOM, init untuk semua mahasiswa sekelas
        // SEMUA mahasiswa di kelas bisa ikut jadwal yang SEDANG AKTIF (belum selesai)
        // Tidak ada filter tanggal_daftar karena ini dipanggil saat QR di-generate (jadwal aktif)
        // UPDATE: Filter berdasarkan sesi jika jadwal memiliki sesi khusus
        if (column_exists('mahasiswa', 'sesi')) {
            $stmt_mhs_belum = mysqli_prepare($conn, "SELECT m.nim 
                                           FROM mahasiswa m 
                                           WHERE m.kode_kelas = ? 
                                           AND (m.sesi = ? OR ? = 0)
                                           AND m.nim NOT IN (
                                               SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = ?
                                           )");
            mysqli_stmt_bind_param($stmt_mhs_belum, "siii", $kode_kelas, $sesi_jadwal, $sesi_jadwal, $jadwal_id);
        } else {
            $stmt_mhs_belum = mysqli_prepare($conn, "SELECT m.nim 
                                           FROM mahasiswa m 
                                           WHERE m.kode_kelas = ? 
                                           AND m.nim NOT IN (
                                               SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = ?
                                           )");
            mysqli_stmt_bind_param($stmt_mhs_belum, "si", $kode_kelas, $jadwal_id);
        }
         mysqli_stmt_execute($stmt_mhs_belum);
         $mhs_belum = mysqli_stmt_get_result($stmt_mhs_belum);
        
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, verified_by_system) 
                                  VALUES (?, ?, 'belum', '', 0)");
        while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
            $nim = $mhs['nim'];
            // Insert sebagai 'belum' - nanti akan diubah ke 'hadir' saat scan QR, atau 'alpha' saat jadwal selesai
            mysqli_stmt_bind_param($stmt_ins, "is", $jadwal_id, $nim);
            mysqli_stmt_execute($stmt_ins);
            $total++;
        }
    }
    
    return $total;
}

/**
 * Set alpha otomatis untuk jadwal yang sudah lewat
 * Mengubah status 'belum' menjadi 'alpha' LANGSUNG setelah jadwal selesai
 * Dipanggil saat halaman monitoring/dashboard dibuka
 * TIDAK termasuk jadwal inhall (inhall bersifat opsional)
 */
function auto_set_alpha_jadwal_lewat() {
    global $conn;
    
    // Update status 'belum' menjadi 'alpha' untuk jadwal yang sudah lewat
    // LANGSUNG setelah jam_selesai (tanpa toleransi)
    // KECUALI jadwal inhall
    $query = "UPDATE presensi_mahasiswa pm
              INNER JOIN jadwal j ON pm.jadwal_id = j.id
              SET pm.status = 'alpha', 
                  pm.waktu_presensi = NOW(),
                  pm.metode = 'auto',
                  pm.verified_by_system = 1
              WHERE pm.status = 'belum'
              AND j.jenis != 'inhall'
              AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))
              AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    mysqli_query($conn, $query);
    $updated = mysqli_affected_rows($conn);
    
    // Juga handle jadwal yang belum ada record sama sekali (fallback)
    // Untuk jadwal yang tidak pernah di-init (misal jadwal lama)
    // TIDAK termasuk inhall
    $jadwal_lewat = mysqli_query($conn, "SELECT j.id, j.kode_kelas, j.tanggal, j.jam_selesai, j.sesi
                                          FROM jadwal j 
                                          WHERE j.jenis != 'inhall'
                                          AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))
                                          AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                          AND j.tanggal <= CURDATE()");
    
    while ($jadwal = mysqli_fetch_assoc($jadwal_lewat)) {
        $jadwal_id = $jadwal['id'];
        $kode_kelas = $jadwal['kode_kelas'];
        // Jika tabel jadwal tidak memiliki kolom 'sesi', anggap sesi = 0 (artinya ambil semua mahasiswa kelas)
        $sesi_jadwal = isset($jadwal['sesi']) ? (int)$jadwal['sesi'] : 0;
        $tanggal_jadwal = $jadwal['tanggal'];
        $jam_selesai = $jadwal['jam_selesai'];
        $tanggal_jam_selesai = $tanggal_jadwal . ' ' . $jam_selesai;
        
        // Insert alpha untuk mahasiswa yang BELUM ada record sama sekali
        // Hanya untuk mahasiswa yang sudah terdaftar SEBELUM jadwal SELESAI
        // Mahasiswa yang didaftarkan SETELAH jadwal selesai tidak kena alpha
        if (column_exists('mahasiswa', 'sesi')) {
            $stmt_mhs_belum = mysqli_prepare($conn, "SELECT m.nim 
                                           FROM mahasiswa m 
                                           WHERE m.kode_kelas = ? 
                                           AND (m.sesi = ? OR ? = 0)
                                           AND m.tanggal_daftar < ?
                                           AND m.nim NOT IN (
                                               SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = ?
                                           )");
            mysqli_stmt_bind_param($stmt_mhs_belum, "siisi", $kode_kelas, $sesi_jadwal, $sesi_jadwal, $tanggal_jam_selesai, $jadwal_id);
        } else {
            $stmt_mhs_belum = mysqli_prepare($conn, "SELECT m.nim 
                                           FROM mahasiswa m 
                                           WHERE m.kode_kelas = ? 
                                           AND m.tanggal_daftar < ?
                                           AND m.nim NOT IN (
                                               SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = ?
                                           )");
            mysqli_stmt_bind_param($stmt_mhs_belum, "ssi", $kode_kelas, $tanggal_jam_selesai, $jadwal_id);
        }
         mysqli_stmt_execute($stmt_mhs_belum);
         $mhs_belum = mysqli_stmt_get_result($stmt_mhs_belum);
         
         $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, waktu_presensi, metode, verified_by_system) 
                                  VALUES (?, ?, 'alpha', NOW(), 'auto', 1)");
         while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
             $nim = $mhs['nim'];
             mysqli_stmt_bind_param($stmt_ins, "is", $jadwal_id, $nim);
             mysqli_stmt_execute($stmt_ins);
             $updated++;
         }
     }
     
     return $updated;
}

/**
 * Set alpha untuk jadwal tertentu (manual trigger)
 * TIDAK untuk jadwal inhall
 */
function set_alpha_jadwal($jadwal_id) {
    global $conn;
    
    // Ambil info jadwal - prepared statement
    $stmt_jadwal = mysqli_prepare($conn, "SELECT kode_kelas, tanggal, jam_mulai, jam_selesai, jenis, sesi FROM jadwal WHERE id = ?");
    mysqli_stmt_bind_param($stmt_jadwal, "i", $jadwal_id);
    mysqli_stmt_execute($stmt_jadwal);
    $jadwal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jadwal));
    if (!$jadwal) return 0;
    
    // Jika jadwal inhall, jangan set alpha otomatis
    if ($jadwal['jenis'] == 'inhall') return 0;
    
    $kode_kelas = $jadwal['kode_kelas'];
    $sesi_jadwal = $jadwal['sesi'];
    $tanggal_jadwal = $jadwal['tanggal'];
    $jam_mulai = $jadwal['jam_mulai'];
    $total = 0;
    
    // Ambil mahasiswa yang belum ada record
    // Hanya yang sudah terdaftar SEBELUM jadwal SELESAI (tanggal + jam_selesai)
    $jam_selesai = $jadwal['jam_selesai'];
    $tanggal_jam_selesai = $tanggal_jadwal . ' ' . $jam_selesai;
    if (column_exists('mahasiswa', 'sesi')) {
        $stmt_mhs_belum = mysqli_prepare($conn, "SELECT m.nim 
                                       FROM mahasiswa m 
                                       WHERE m.kode_kelas = ? 
                                       AND (m.sesi = ? OR ? = 0)
                                       AND m.tanggal_daftar < ?
                                       AND m.nim NOT IN (
                                           SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = ?
                                       )");
        mysqli_stmt_bind_param($stmt_mhs_belum, "siisi", $kode_kelas, $sesi_jadwal, $sesi_jadwal, $tanggal_jam_selesai, $jadwal_id);
    } else {
        $stmt_mhs_belum = mysqli_prepare($conn, "SELECT m.nim 
                                       FROM mahasiswa m 
                                       WHERE m.kode_kelas = ? 
                                       AND m.tanggal_daftar < ?
                                       AND m.nim NOT IN (
                                           SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = ?
                                       )");
        mysqli_stmt_bind_param($stmt_mhs_belum, "ssi", $kode_kelas, $tanggal_jam_selesai, $jadwal_id);
    }
     mysqli_stmt_execute($stmt_mhs_belum);
     $mhs_belum = mysqli_stmt_get_result($stmt_mhs_belum);
     
     $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, verified_by_system) 
                              VALUES (?, ?, 'alpha', 'manual', 1)");
     while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
         $nim = $mhs['nim'];
         mysqli_stmt_bind_param($stmt_ins, "is", $jadwal_id, $nim);
         mysqli_stmt_execute($stmt_ins);
         $total++;
     }
    
    if ($total > 0) {
        log_aktivitas(0, 'AUTO_ALPHA', 'presensi_mahasiswa', $jadwal_id, "$total mahasiswa di-set alpha otomatis");
    }
    
    return $total;
}

// ============ FUNGSI PAGINATION ============

/**
 * Hitung total halaman
 */
function get_total_pages($total_data, $per_page = 10) {
    return ceil($total_data / $per_page);
}

/**
 * Hitung offset untuk query LIMIT
 */
function get_offset($current_page, $per_page = 10) {
    return ($current_page - 1) * $per_page;
}

/**
 * Ambil halaman saat ini dari URL
 */
function get_current_page() {
    $page = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
    return max(1, $page);
}

/**
 * Generate HTML pagination dengan parameter URL yang dipertahankan
 * 
 * @param int $current_page Halaman saat ini
 * @param int $total_pages Total halaman
 * @param string $base_url URL dasar (contoh: index.php?page=admin_mahasiswa)
 * @param array $params Parameter tambahan yang dipertahankan
 * @return string HTML pagination
 */
function render_pagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) return '';
    
    // Build query string dari params yang ada
    $query_string = '';
    foreach ($params as $key => $value) {
        if ($value !== '' && $key !== 'hal') {
            $query_string .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    
    $html = '<nav aria-label="Page navigation" class="mt-3"><ul class="pagination pagination-sm justify-content-center flex-wrap">';
    
    // Tombol Previous
    if ($current_page > 1) {
        $html .= '<li class="page-item">
                    <a class="page-link" href="' . $base_url . $query_string . '&hal=' . ($current_page - 1) . '">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i></span></li>';
    }
    
    // Logika menampilkan nomor halaman
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    // Tampilkan halaman 1 jika tidak termasuk dalam range
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . $query_string . '&hal=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Nomor halaman
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . $query_string . '&hal=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Tampilkan halaman terakhir jika tidak termasuk dalam range
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . $query_string . '&hal=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    
    // Tombol Next
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item">
                    <a class="page-link" href="' . $base_url . $query_string . '&hal=' . ($current_page + 1) . '">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                  </li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-right"></i></span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Generate info "Menampilkan X - Y dari Z data"
 */
function render_pagination_info($current_page, $per_page, $total_data) {
    if ($total_data == 0) return '';
    
    $start = (($current_page - 1) * $per_page) + 1;
    $end = min($current_page * $per_page, $total_data);
    
    return '<small class="text-muted">Menampilkan ' . $start . ' - ' . $end . ' dari ' . $total_data . ' data</small>';
}

/**
 * Otomatis set status Alpha untuk mahasiswa yang tidak hadir setelah jadwal selesai
 * Mengubah status 'belum' menjadi 'alpha' LANGSUNG setelah jam_selesai (tanpa toleransi)
 * Dipanggil setiap kali halaman dimuat
 */
function auto_set_alpha() {
    global $conn;
    
    // Update status 'belum' menjadi 'alpha' untuk jadwal yang sudah selesai
    // LANGSUNG setelah jam_selesai (tanpa toleransi)
    // Termasuk jadwal hari ini DAN jadwal yang sudah lewat (30 hari terakhir)
    // KECUALI jadwal inhall (inhall bersifat opsional, tidak wajib)
    $query = "UPDATE presensi_mahasiswa pm
              INNER JOIN jadwal j ON pm.jadwal_id = j.id
              SET pm.status = 'alpha', 
                  pm.waktu_presensi = NOW(),
                  pm.metode = 'auto',
                  pm.verified_by_system = 1
              WHERE pm.status = 'belum'
              AND j.jenis != 'inhall'
              AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() AND j.jam_selesai < CURTIME()))";
    
    mysqli_query($conn, $query);
    $updated = mysqli_affected_rows($conn);
    
    // Fallback: Insert alpha untuk mahasiswa yang tidak punya record sama sekali
    // Hanya untuk jadwal MATERI dan UJIKOM, BUKAN INHALL
    $query_jadwal = "SELECT j.id as jadwal_id, j.kode_kelas, j.tanggal, j.jam_selesai
                     FROM jadwal j
                     WHERE j.jenis != 'inhall'
                     AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                     AND j.tanggal <= CURDATE()
                     AND (j.tanggal < CURDATE() OR (j.tanggal = CURDATE() && j.jam_selesai < CURTIME()))";
    
    $jadwal_selesai = mysqli_query($conn, $query_jadwal);
    
    while ($jadwal = mysqli_fetch_assoc($jadwal_selesai)) {
        $jadwal_id = $jadwal['jadwal_id'];
        $kode_kelas = $jadwal['kode_kelas'];
        $sesi_jadwal = isset($jadwal['sesi']) ? (int)$jadwal['sesi'] : 0;
        $tanggal_jadwal = $jadwal['tanggal'];
        $jam_selesai = $jadwal['jam_selesai'];
        $tanggal_jam_selesai = $tanggal_jadwal . ' ' . $jam_selesai;

        // Insert untuk mahasiswa yang belum ada record sama sekali
        // Hanya untuk mahasiswa yang sudah terdaftar SEBELUM jadwal SELESAI
        // Cek keberadaan kolom 'sesi' sekali dan cache hasilnya
        static $mahasiswa_has_sesi = null;
        if ($mahasiswa_has_sesi === null) {
            $col_check = mysqli_query($conn, "SHOW COLUMNS FROM mahasiswa LIKE 'sesi'");
            $mahasiswa_has_sesi = ($col_check && mysqli_num_rows($col_check) > 0);
        }

        if ($mahasiswa_has_sesi) {
            $stmt_mhs = mysqli_prepare($conn, "SELECT m.nim 
                      FROM mahasiswa m 
                      WHERE m.kode_kelas = ?
                      AND (m.sesi = ? OR ? = 0)
                      AND m.tanggal_daftar < ?
                      AND m.nim NOT IN (
                          SELECT p.nim FROM presensi_mahasiswa p WHERE p.jadwal_id = ?
                      )");
            mysqli_stmt_bind_param($stmt_mhs, "siisi", $kode_kelas, $sesi_jadwal, $sesi_jadwal, $tanggal_jam_selesai, $jadwal_id);
        } else {
            // Fallback jika kolom sesi tidak ada di schema
            $stmt_mhs = mysqli_prepare($conn, "SELECT m.nim 
                      FROM mahasiswa m 
                      WHERE m.kode_kelas = ?
                      AND m.tanggal_daftar < ?
                      AND m.nim NOT IN (
                          SELECT p.nim FROM presensi_mahasiswa p WHERE p.jadwal_id = ?
                      )");
            mysqli_stmt_bind_param($stmt_mhs, "ssi", $kode_kelas, $tanggal_jam_selesai, $jadwal_id);
        }

        mysqli_stmt_execute($stmt_mhs);
        $mhs_belum = mysqli_stmt_get_result($stmt_mhs);
        
        $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, waktu_presensi, metode, verified_by_system) 
                                 VALUES (?, ?, 'alpha', NOW(), 'auto', 1)");
        while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
            $nim = $mhs['nim'];
            mysqli_stmt_bind_param($stmt_ins, "is", $jadwal_id, $nim);
            mysqli_stmt_execute($stmt_ins);
            $updated++;
        }
    }
    
    return $updated;
}

function optimize_and_save_image($source_path, $destination_path, $max_width, $max_height, $quality = 75) {
    // Cek apakah ekstensi GD aktif
    if (!extension_loaded('gd')) {
        // Fallback jika GD tidak aktif: simpan file asli tanpa resize
        if (is_uploaded_file($source_path)) {
            return move_uploaded_file($source_path, $destination_path);
        }
        return copy($source_path, $destination_path);
    }

    list($width, $height, $type) = getimagesize($source_path);

    // Tentukan tipe gambar dan buat resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($source_path);
            break;
        default:
            return false; // Tipe tidak didukung
    }

    // Hitung rasio
    $ratio = $width / $height;
    if ($max_width / $max_height > $ratio) {
        $new_width = $max_height * $ratio;
        $new_height = $max_height;
    } else {
        $new_height = $max_width / $ratio;
        $new_width = $max_width;
    }

    // Buat gambar baru dengan ukuran yang sudah di-resize
    $dst = imagecreatetruecolor($new_width, $new_height);

    // Menjaga transparansi untuk PNG
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Simpan file baru
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($dst, $destination_path, $quality);
            break;
        case IMAGETYPE_PNG:
            // Kualitas PNG (0-9), 9 = kompresi terbaik
            $result = imagepng($dst, $destination_path, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($dst, $destination_path);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($dst, $destination_path, $quality);
            break;
    }

    // Hapus resource dari memori
    imagedestroy($src);
    imagedestroy($dst);

    return $result;
}

// ============ FITUR TAMBAHAN (GAMIFIKASI & NOTIFIKASI) ============

/**
 * Hitung Badges untuk Mahasiswa
 */
function get_mahasiswa_badges($nim) {
    global $conn;
    $badges = [];
    
    // 1. Badge "Rajin Presensi" (Kehadiran > 90%)
    $q_stat = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir
        FROM presensi_mahasiswa WHERE nim = '$nim'");
    $stat = mysqli_fetch_assoc($q_stat);
    
    if ($stat['total'] > 0 && ($stat['hadir'] / $stat['total']) >= 0.9) {
        $badges[] = ['icon' => 'medal', 'color' => 'warning', 'title' => 'Sobat Rajin', 'desc' => 'Kehadiran di atas 90%'];
    }

    // 2. Badge "Early Bird" (Selalu datang sebelum jam mulai)
    // Cek 5 presensi terakhir, apakah waktu_presensi <= jam_mulai jadwal
    $q_early = mysqli_query($conn, "SELECT p.waktu_presensi, j.jam_mulai 
                                    FROM presensi_mahasiswa p
                                    JOIN jadwal j ON p.jadwal_id = j.id
                                    WHERE p.nim = '$nim' AND p.status = 'hadir'
                                    ORDER BY p.waktu_presensi DESC LIMIT 5");
    
    $is_early = true;
    $count = 0;
    while($row = mysqli_fetch_assoc($q_early)) {
        $count++;
        // Bandingkan waktu (H:i:s)
        if (date('H:i:s', strtotime($row['waktu_presensi'])) > $row['jam_mulai']) {
            $is_early = false;
            break;
        }
    }
    
    if ($count >= 3 && $is_early) {
        $badges[] = ['icon' => 'bolt', 'color' => 'info', 'title' => 'Early Bird', 'desc' => 'Selalu datang tepat waktu'];
    }
    
    return $badges;
}

/**
 * Kirim Notifikasi (Stub/Placeholder)
 * Di sistem real, ini bisa dihubungkan ke API WhatsApp (Fonnte/Twilio) atau PHPMailer
 */
function kirim_notifikasi($target, $pesan, $tipe = 'wa') {
    // Konfigurasi API WhatsApp (Contoh menggunakan Fonnte)
    // Silakan daftar di https://fonnte.com untuk dapat token gratis
    $token = "r3xT7ppTj28hxKamgjJE"; 

    if ($tipe == 'wa') {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.fonnte.com/send',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array(
            'target' => $target,
            'message' => $pesan,
          ),
          CURLOPT_HTTPHEADER => array(
            "Authorization: $token"
          ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    return false;
}

// ============ GAMIFIKASI MAHASISWA ============

function get_mahasiswa_points($nim) {
    global $conn;
    // Hadir = 10 poin, Izin/Sakit = 5 poin
    $q = mysqli_query($conn, "SELECT 
        SUM(CASE 
            WHEN status = 'hadir' THEN 10 
            WHEN status = 'izin' THEN 5 
            WHEN status = 'sakit' THEN 5 
            ELSE 0 
        END) as points 
        FROM presensi_mahasiswa WHERE nim = '$nim'");
    $r = mysqli_fetch_assoc($q);
    return (int)($r['points'] ?? 0);
}

function get_mahasiswa_level($points) {
    if ($points < 50) return ['name' => 'Novice', 'icon' => 'seedling', 'color' => 'secondary', 'min' => 0, 'max' => 50];
    if ($points < 150) return ['name' => 'Apprentice', 'icon' => 'book-reader', 'color' => 'info', 'min' => 50, 'max' => 150];
    if ($points < 300) return ['name' => 'Practitioner', 'icon' => 'user-graduate', 'color' => 'primary', 'min' => 150, 'max' => 300];
    if ($points < 500) return ['name' => 'Expert', 'icon' => 'star', 'color' => 'warning', 'min' => 300, 'max' => 500];
    return ['name' => 'Master', 'icon' => 'crown', 'color' => 'danger', 'min' => 500, 'max' => 1000];
}
?>
