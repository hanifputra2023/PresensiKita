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
    $query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
    return mysqli_fetch_assoc($query);
}

// Fungsi untuk mendapatkan data mahasiswa yang login
function get_mahasiswa_login() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $query = mysqli_query($conn, "SELECT m.*, k.nama_kelas FROM mahasiswa m 
                                  JOIN kelas k ON m.kode_kelas = k.kode_kelas 
                                  WHERE m.user_id = '$user_id'");
    return mysqli_fetch_assoc($query);
}

// Fungsi untuk mendapatkan data asisten yang login
function get_asisten_login() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $query = mysqli_query($conn, "SELECT a.*, mk.nama_mk FROM asisten a 
                                  LEFT JOIN mata_kuliah mk ON a.kode_mk = mk.kode_mk 
                                  WHERE a.user_id = '$user_id'");
    return mysqli_fetch_assoc($query);
}

// Fungsi escape input
function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
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

// Fungsi untuk validasi waktu presensi
function validasi_waktu($jam_mulai, $jam_selesai) {
    $sekarang = time();
    $mulai = strtotime($jam_mulai) - (TOLERANSI_SEBELUM * 60);
    $akhir = strtotime($jam_selesai) + (TOLERANSI_SESUDAH * 60);
    
    return ($sekarang >= $mulai && $sekarang <= $akhir);
}

// Fungsi untuk log aktivitas
function log_aktivitas($user_id, $aksi, $tabel, $id_record, $detail) {
    global $conn;
    $aksi = escape($aksi);
    $tabel = escape($tabel);
    $detail = escape($detail);
    mysqli_query($conn, "INSERT INTO log_presensi (user_id, aksi, tabel, id_record, detail) 
                         VALUES ('$user_id', '$aksi', '$tabel', '$id_record', '$detail')");
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
    
    // Cek apakah ini memang jadwal milik asisten ini
    $jadwal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM jadwal 
                                                       WHERE id = '$jadwal_id' 
                                                       AND (kode_asisten_1 = '$kode_asisten' OR kode_asisten_2 = '$kode_asisten')"));
    
    if (!$jadwal) {
        // Bukan jadwal milik asisten ini, jangan catat hadir
        return false;
    }
    
    // Cek apakah sudah ada record untuk jadwal ini
    $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, status FROM absen_asisten 
                                                         WHERE jadwal_id = '$jadwal_id' AND kode_asisten = '$kode_asisten'"));
    
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
        // Insert baru sebagai hadir
        mysqli_query($conn, "INSERT INTO absen_asisten (jadwal_id, kode_asisten, status, jam_masuk) 
                              VALUES ('$jadwal_id', '$kode_asisten', 'hadir', '$jam_masuk')");
        return true;
    }
}

/**
 * Update jam keluar asisten
 */
function update_jam_keluar_asisten($kode_asisten, $jadwal_id) {
    global $conn;
    $jam_keluar = date('H:i:s');
    mysqli_query($conn, "UPDATE absen_asisten SET jam_keluar = '$jam_keluar' 
                          WHERE jadwal_id = '$jadwal_id' AND kode_asisten = '$kode_asisten' AND status = 'hadir'");
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
    
    // Ambil info jadwal
    $jadwal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kode_kelas, kode_mk, tanggal, jam_mulai, jenis FROM jadwal WHERE id = '$jadwal_id'"));
    if (!$jadwal) return 0;
    
    $kode_kelas = $jadwal['kode_kelas'];
    $kode_mk = $jadwal['kode_mk'];
    $tanggal_jadwal = $jadwal['tanggal'];
    $jam_mulai = $jadwal['jam_mulai'];
    $jenis = $jadwal['jenis'];
    $total = 0;
    
    // Jika jadwal INHALL, hanya init untuk mahasiswa yang terdaftar inhall untuk MK ini
    if ($jenis == 'inhall') {
        $mhs_inhall = mysqli_query($conn, "SELECT pi.nim 
                                            FROM penggantian_inhall pi
                                            JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                            WHERE pi.status = 'terdaftar'
                                            AND j.kode_mk = '$kode_mk'
                                            AND pi.nim NOT IN (
                                                SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id'
                                            )");
        
        while ($mhs = mysqli_fetch_assoc($mhs_inhall)) {
            $nim = $mhs['nim'];
            mysqli_query($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, verified_by_system) 
                                  VALUES ('$jadwal_id', '$nim', 'belum', '', 0)");
            $total++;
        }
    } else {
        // Untuk jadwal MATERI dan UJIKOM, init untuk semua mahasiswa sekelas
        // SEMUA mahasiswa di kelas bisa ikut jadwal yang SEDANG AKTIF (belum selesai)
        // Tidak ada filter tanggal_daftar karena ini dipanggil saat QR di-generate (jadwal aktif)
        $mhs_belum = mysqli_query($conn, "SELECT m.nim 
                                           FROM mahasiswa m 
                                           WHERE m.kode_kelas = '$kode_kelas' 
                                           AND m.nim NOT IN (
                                               SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id'
                                           )");
        
        while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
            $nim = $mhs['nim'];
            // Insert sebagai 'belum' - nanti akan diubah ke 'hadir' saat scan QR, atau 'alpha' saat jadwal selesai
            mysqli_query($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, verified_by_system) 
                                  VALUES ('$jadwal_id', '$nim', 'belum', '', 0)");
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
              AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()
              AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    
    mysqli_query($conn, $query);
    $updated = mysqli_affected_rows($conn);
    
    // Juga handle jadwal yang belum ada record sama sekali (fallback)
    // Untuk jadwal yang tidak pernah di-init (misal jadwal lama)
    // TIDAK termasuk inhall
    $jadwal_lewat = mysqli_query($conn, "SELECT j.id, j.kode_kelas, j.tanggal, j.jam_selesai
                                          FROM jadwal j 
                                          WHERE j.jenis != 'inhall'
                                          AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()
                                          AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                          AND j.tanggal <= CURDATE()");
    
    while ($jadwal = mysqli_fetch_assoc($jadwal_lewat)) {
        $jadwal_id = $jadwal['id'];
        $kode_kelas = $jadwal['kode_kelas'];
        $tanggal_jadwal = $jadwal['tanggal'];
        $jam_selesai = $jadwal['jam_selesai'];
        
        // Insert alpha untuk mahasiswa yang BELUM ada record sama sekali
        // Hanya untuk mahasiswa yang sudah terdaftar SEBELUM jadwal SELESAI
        // Mahasiswa yang didaftarkan SETELAH jadwal selesai tidak kena alpha
        $mhs_belum = mysqli_query($conn, "SELECT m.nim 
                                           FROM mahasiswa m 
                                           WHERE m.kode_kelas = '$kode_kelas' 
                                           AND m.tanggal_daftar < '$tanggal_jadwal $jam_selesai'
                                           AND m.nim NOT IN (
                                               SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id'
                                           )");
        
        while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
            $nim = $mhs['nim'];
            mysqli_query($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, waktu_presensi, metode, verified_by_system) 
                                  VALUES ('$jadwal_id', '$nim', 'alpha', NOW(), 'auto', 1)");
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
    
    // Ambil info jadwal
    $jadwal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kode_kelas, tanggal, jam_mulai, jam_selesai, jenis FROM jadwal WHERE id = '$jadwal_id'"));
    if (!$jadwal) return 0;
    
    // Jika jadwal inhall, jangan set alpha otomatis
    if ($jadwal['jenis'] == 'inhall') return 0;
    
    $kode_kelas = $jadwal['kode_kelas'];
    $tanggal_jadwal = $jadwal['tanggal'];
    $jam_mulai = $jadwal['jam_mulai'];
    $total = 0;
    
    // Ambil mahasiswa yang belum ada record
    // Hanya yang sudah terdaftar SEBELUM jadwal SELESAI (tanggal + jam_selesai)
    $jam_selesai = $jadwal['jam_selesai'];
    $mhs_belum = mysqli_query($conn, "SELECT m.nim 
                                       FROM mahasiswa m 
                                       WHERE m.kode_kelas = '$kode_kelas' 
                                       AND m.tanggal_daftar < '$tanggal_jadwal $jam_selesai'
                                       AND m.nim NOT IN (
                                           SELECT nim FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id'
                                       )");
    
    while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
        $nim = $mhs['nim'];
        mysqli_query($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, verified_by_system) 
                              VALUES ('$jadwal_id', '$nim', 'alpha', 'manual', 1)");
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
              AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()";
    
    mysqli_query($conn, $query);
    $updated = mysqli_affected_rows($conn);
    
    // Fallback: Insert alpha untuk mahasiswa yang tidak punya record sama sekali
    // Hanya untuk jadwal MATERI dan UJIKOM, BUKAN INHALL
    $query_jadwal = "SELECT j.id as jadwal_id, j.kode_kelas, j.tanggal, j.jam_selesai
                     FROM jadwal j
                     WHERE j.jenis != 'inhall'
                     AND j.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                     AND j.tanggal <= CURDATE()
                     AND CONCAT(j.tanggal, ' ', j.jam_selesai) < NOW()";
    
    $jadwal_selesai = mysqli_query($conn, $query_jadwal);
    
    while ($jadwal = mysqli_fetch_assoc($jadwal_selesai)) {
        $jadwal_id = $jadwal['jadwal_id'];
        $kode_kelas = $jadwal['kode_kelas'];
        $tanggal_jadwal = $jadwal['tanggal'];
        $jam_selesai = $jadwal['jam_selesai'];
        
        // Insert untuk mahasiswa yang belum ada record sama sekali
        // Hanya untuk mahasiswa yang sudah terdaftar SEBELUM jadwal SELESAI
        $query_mhs = "SELECT m.nim 
                      FROM mahasiswa m 
                      WHERE m.kode_kelas = '$kode_kelas'
                      AND m.tanggal_daftar < '$tanggal_jadwal $jam_selesai'
                      AND m.nim NOT IN (
                          SELECT p.nim FROM presensi_mahasiswa p WHERE p.jadwal_id = '$jadwal_id'
                      )";
        
        $mhs_belum = mysqli_query($conn, $query_mhs);
        
        while ($mhs = mysqli_fetch_assoc($mhs_belum)) {
            $nim = $mhs['nim'];
            mysqli_query($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, waktu_presensi, metode, verified_by_system) 
                                 VALUES ('$jadwal_id', '$nim', 'alpha', NOW(), 'auto', 1)");
            $updated++;
        }
    }
    
    return $updated;
}
?>
