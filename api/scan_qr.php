<?php
// API untuk scan QR presensi
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/fungsi.php';

// Terima data JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['qr_code']) || !isset($input['nim'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$qr_code = escape($input['qr_code']);
$nim = escape($input['nim']);
$lat_user = isset($input['latitude']) ? (float)$input['latitude'] : null;
$long_user = isset($input['longitude']) ? (float)$input['longitude'] : null;

// VALIDASI 1: Cek QR Code valid dan belum expired
$stmt_qr = mysqli_prepare($conn, "SELECT qs.*, j.id as jadwal_id, j.kode_kelas, j.kode_mk, j.tanggal, j.jam_mulai, j.jam_selesai,
                                                       j.materi, j.jenis, mk.nama_mk, l.nama_lab, l.latitude as lab_lat, l.longitude as lab_long
                                                       FROM qr_code_session qs
                                                       JOIN jadwal j ON qs.jadwal_id = j.id
                                                       LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                                       WHERE qs.qr_code = ?");
mysqli_stmt_bind_param($stmt_qr, "s", $qr_code);
mysqli_stmt_execute($stmt_qr);
$result_qr = mysqli_stmt_get_result($stmt_qr);
$qr_session = mysqli_fetch_assoc($result_qr);
mysqli_stmt_close($stmt_qr);

if (!$qr_session) {
    echo json_encode(['success' => false, 'message' => 'QR Code tidak valid atau tidak ditemukan']);
    exit;
}

// Cek expired
if (strtotime($qr_session['expired_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'QR Code sudah expired. Minta asisten untuk generate ulang.']);
    exit;
}

// VALIDASI 2: Cek mahasiswa ada
$stmt_mhs = mysqli_prepare($conn, "SELECT * FROM mahasiswa WHERE nim = ?");
mysqli_stmt_bind_param($stmt_mhs, "s", $nim);
mysqli_stmt_execute($stmt_mhs);
$result_mhs = mysqli_stmt_get_result($stmt_mhs);
$mahasiswa = mysqli_fetch_assoc($result_mhs);
mysqli_stmt_close($stmt_mhs);

if (!$mahasiswa) {
    echo json_encode(['success' => false, 'message' => 'NIM tidak ditemukan dalam database']);
    exit;
}

$jadwal_id = $qr_session['jadwal_id'];
$is_inhall = ($qr_session['jenis'] == 'inhall');

// VALIDASI KELAS - Berbeda untuk jadwal biasa vs inhall
if ($is_inhall) {
    // Untuk INHALL: Boleh beda kelas, tapi harus punya izin di MK yang sama
    $kode_mk = $qr_session['kode_mk'];
    
    // Cek apakah mahasiswa punya izin/sakit yang belum diganti untuk MK ini
    $stmt_inhall = mysqli_prepare($conn, "SELECT pi.*, j.pertemuan_ke, j.materi, mk.nama_mk
                                                                 FROM penggantian_inhall pi
                                                                 JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                                                 JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                                 WHERE pi.nim = ?
                                                                 AND pi.status = 'terdaftar'
                                                                 AND j.kode_mk = ?
                                                                 ORDER BY j.tanggal ASC
                                                                 LIMIT 1");
    mysqli_stmt_bind_param($stmt_inhall, "ss", $nim, $kode_mk);
    mysqli_stmt_execute($stmt_inhall);
    $cek_perlu_inhall = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_inhall));
    mysqli_stmt_close($stmt_inhall);
    
    if (!$cek_perlu_inhall) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin/sakit yang perlu diganti untuk mata kuliah ' . $qr_session['nama_mk']]);
        exit;
    }
    
    // Simpan info inhall untuk diupdate nanti
    $inhall_id = $cek_perlu_inhall['id'];
    $pertemuan_diganti = $cek_perlu_inhall['pertemuan_ke'];
    
} else {
    // Untuk jadwal BIASA: Kelas harus sama
    if ($mahasiswa['kode_kelas'] != $qr_session['kode_kelas']) {
        echo json_encode(['success' => false, 'message' => 'Anda bukan dari kelas yang dijadwalkan di lab ini. Kelas Anda: ' . $mahasiswa['kode_kelas'] . ', Jadwal untuk: ' . $qr_session['kode_kelas']]);
        exit;
    }
}

// VALIDASI 3: Cek waktu presensi (harus tepat waktu mulai, tidak ada toleransi sebelum)
$now = time();
$jadwal_mulai = strtotime($qr_session['tanggal'] . ' ' . $qr_session['jam_mulai']);
$jadwal_selesai = strtotime($qr_session['tanggal'] . ' ' . $qr_session['jam_selesai']);
$toleransi_sebelum = $jadwal_mulai - (TOLERANSI_SEBELUM * 60); // TOLERANSI_SEBELUM = 0, jadi harus tepat waktu
$toleransi_sesudah = $jadwal_selesai + (TOLERANSI_SESUDAH * 60);

if ($now < $toleransi_sebelum) {
    $menit_tersisa = ceil(($toleransi_sebelum - $now) / 60);
    $jam_buka = date('H:i', $jadwal_mulai);
    echo json_encode(['success' => false, 'message' => "Presensi belum dibuka. Presensi akan dibuka tepat pukul $jam_buka (tersisa $menit_tersisa menit lagi)."]);
    exit;
}

if ($now > $toleransi_sesudah) {
    echo json_encode(['success' => false, 'message' => 'Waktu presensi sudah berakhir untuk sesi ini.']);
    exit;
}

// VALIDASI 4: Cek sudah pernah presensi untuk jadwal ini
$stmt_cek = mysqli_prepare($conn, "SELECT * FROM presensi_mahasiswa WHERE jadwal_id = ? AND nim = ?");
mysqli_stmt_bind_param($stmt_cek, "is", $jadwal_id, $nim);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);
$cek_presensi = mysqli_fetch_assoc($result_cek);
mysqli_stmt_close($stmt_cek);

if ($cek_presensi) {
    // Jika status 'belum', update ke 'hadir' (bukan insert baru)
    if ($cek_presensi['status'] == 'belum') {
        // Lanjut ke proses update di bawah
    } elseif ($cek_presensi['status'] == 'hadir') {
        echo json_encode(['success' => false, 'message' => 'Anda sudah melakukan presensi untuk sesi ini.']);
        exit;
    } else {
        // Untuk INHALL: Jika ada status izin/sakit/alpha dari record lama yang salah, 
        // tetap boleh presensi karena yang penting sudah lolos validasi penggantian_inhall
        if (!$is_inhall) {
            echo json_encode(['success' => false, 'message' => 'Status presensi Anda untuk sesi ini: ' . ucfirst($cek_presensi['status'])]);
            exit;
        }
        // Untuk inhall, lanjut update status ke 'hadir'
    }
}

// VALIDASI 6: Cek Lokasi (Geofencing) - Mencegah scan dari rumah
if ($qr_session['lab_lat'] && $qr_session['lab_long']) {
    if ($lat_user === null || $long_user === null) {
        echo json_encode(['success' => false, 'message' => 'Lokasi tidak terdeteksi. Mohon izinkan akses lokasi di browser Anda.']);
        exit;
    }

    // Rumus Haversine untuk hitung jarak (meter)
    $earthRadius = 6371000;
    $dLat = deg2rad($qr_session['lab_lat'] - $lat_user);
    $dLon = deg2rad($qr_session['lab_long'] - $long_user);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat_user)) * cos(deg2rad($qr_session['lab_lat'])) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;

    // Toleransi jarak (misal 100 meter dari titik pusat lab)
    if ($distance > 100) {
        echo json_encode(['success' => false, 'message' => 'Anda berada di luar jangkauan Lab (' . round($distance) . 'm). Silakan scan di dalam ruangan lab.']);
        exit;
    }
}

// VALIDASI 5: Cek Penggunaan Perangkat (Mencegah 1 HP untuk banyak NIM)
// Gunakan escape() untuk keamanan dan perpanjang durasi blokir jadi 5 menit
$device_id = escape(substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100));
$stmt_device = mysqli_prepare($conn, "SELECT * FROM presensi_mahasiswa
                                         WHERE jadwal_id = ?
                                         AND device_id = ?
                                         AND nim != ?
                                         AND waktu_presensi > NOW() - INTERVAL 5 MINUTE");
mysqli_stmt_bind_param($stmt_device, "iss", $jadwal_id, $device_id, $nim);
mysqli_stmt_execute($stmt_device);
$cek_device_query = mysqli_stmt_get_result($stmt_device);

if (mysqli_num_rows($cek_device_query) > 0) {
    mysqli_stmt_close($stmt_device);
    echo json_encode([
        'success' => false, 
        'message' => 'Perangkat ini baru saja digunakan untuk presensi NIM lain. Silakan gunakan perangkat masing-masing atau tunggu 5 menit.'
    ]);
    exit;
}

mysqli_stmt_close($stmt_device);

// SEMUA VALIDASI LOLOS - Catat presensi
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$location = $qr_session['nama_lab'];

// Jika sudah ada record, UPDATE. Jika belum ada record, INSERT
if ($cek_presensi) {
    // Update status ke hadir (dari 'belum' atau untuk inhall dari status apapun)
    $stmt_update = mysqli_prepare($conn, "UPDATE presensi_mahasiswa
                                   SET status = 'hadir', waktu_presensi = NOW(), metode = 'qr',
                                       location_lab = ?, ip_address = ?, device_id = ?, verified_by_system = 1
                                   WHERE jadwal_id = ? AND nim = ?");
    mysqli_stmt_bind_param($stmt_update, "sssis", $location, $ip, $device_id, $jadwal_id, $nim);
    $query = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
} else {
    $stmt_insert = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, location_lab, ip_address, device_id, verified_by_system)
                                   VALUES (?, ?, 'hadir', 'qr', ?, ?, ?, 1)");
    mysqli_stmt_bind_param($stmt_insert, "issss", $jadwal_id, $nim, $location, $ip, $device_id);
    $query = mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);
}

if ($query) {
    $message = "Presensi berhasil dicatat untuk {$qr_session['nama_mk']} - {$qr_session['materi']} di {$qr_session['nama_lab']}";
    
    // Jika ini adalah jadwal INHALL, update penggantian_inhall
    if ($is_inhall && isset($inhall_id)) {
        $stmt_inhall_upd = mysqli_prepare($conn, "UPDATE penggantian_inhall SET status = 'hadir', jadwal_inhall_id = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_inhall_upd, "ii", $jadwal_id, $inhall_id);
        mysqli_stmt_execute($stmt_inhall_upd);
        
        $message = "Presensi INHALL berhasil! Pertemuan ke-$pertemuan_diganti ({$qr_session['nama_mk']}) telah diganti.";
    }
    
    log_aktivitas(null, 'PRESENSI_QR', 'presensi_mahasiswa', $jadwal_id, "Mahasiswa $nim presensi via QR di $location" . ($is_inhall ? " (INHALL)" : ""));
    
    echo json_encode([
        'success' => true, 
        'message' => $message
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan presensi. Silakan coba lagi.']);
}
?>
