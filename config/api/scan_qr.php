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

// VALIDASI 1: Cek QR Code valid dan belum expired
$qr_session = mysqli_fetch_assoc(mysqli_query($conn, "SELECT qs.*, j.id as jadwal_id, j.kode_kelas, j.kode_mk, j.tanggal, j.jam_mulai, j.jam_selesai, 
                                                       j.materi, j.jenis, j.sesi, mk.nama_mk, l.nama_lab
                                                       FROM qr_code_session qs
                                                       JOIN jadwal j ON qs.jadwal_id = j.id
                                                       LEFT JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                       LEFT JOIN lab l ON j.kode_lab = l.kode_lab
                                                       WHERE qs.qr_code = '$qr_code'"));

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
$mahasiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mahasiswa WHERE nim = '$nim'"));

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
    $cek_perlu_inhall = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pi.*, j.pertemuan_ke, j.materi, mk.nama_mk
                                                                 FROM penggantian_inhall pi
                                                                 JOIN jadwal j ON pi.jadwal_asli_id = j.id
                                                                 JOIN mata_kuliah mk ON j.kode_mk = mk.kode_mk
                                                                 WHERE pi.nim = '$nim' 
                                                                 AND pi.status = 'terdaftar'
                                                                 AND j.kode_mk = '$kode_mk'
                                                                 ORDER BY j.tanggal ASC
                                                                 LIMIT 1"));
    
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

    // VALIDASI SESI
    if ($qr_session['sesi'] != 0 && $qr_session['sesi'] != $mahasiswa['sesi']) {
        echo json_encode(['success' => false, 'message' => 'Jadwal ini khusus untuk Sesi ' . $qr_session['sesi'] . '. Anda terdaftar di Sesi ' . $mahasiswa['sesi']]);
        exit;
    }
}

// VALIDASI 3: Cek waktu presensi (toleransi 15 menit sebelum dan sesudah)
$now = time();
$jadwal_mulai = strtotime($qr_session['tanggal'] . ' ' . $qr_session['jam_mulai']);
$jadwal_selesai = strtotime($qr_session['tanggal'] . ' ' . $qr_session['jam_selesai']);
$toleransi_sebelum = $jadwal_mulai - (TOLERANSI_SEBELUM * 60);
$toleransi_sesudah = $jadwal_selesai + (TOLERANSI_SESUDAH * 60);

if ($now < $toleransi_sebelum) {
    $menit_tersisa = ceil(($toleransi_sebelum - $now) / 60);
    echo json_encode(['success' => false, 'message' => "Presensi belum dibuka. Tunggu $menit_tersisa menit lagi."]);
    exit;
}

if ($now > $toleransi_sesudah) {
    echo json_encode(['success' => false, 'message' => 'Waktu presensi sudah berakhir untuk sesi ini.']);
    exit;
}

// VALIDASI 4: Cek sudah pernah presensi untuk jadwal ini
$cek_presensi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM presensi_mahasiswa WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'"));

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

// SEMUA VALIDASI LOLOS - Catat presensi
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$device = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100);
$location = $qr_session['nama_lab'];

// Jika sudah ada record, UPDATE. Jika belum ada record, INSERT
if ($cek_presensi) {
    // Update status ke hadir (dari 'belum' atau untuk inhall dari status apapun)
    $query = mysqli_query($conn, "UPDATE presensi_mahasiswa 
                                   SET status = 'hadir', waktu_presensi = NOW(), metode = 'qr', 
                                       location_lab = '$location', ip_address = '$ip', device_id = '$device', verified_by_system = 1
                                   WHERE jadwal_id = '$jadwal_id' AND nim = '$nim'");
} else {
    $query = mysqli_query($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, metode, location_lab, ip_address, device_id, verified_by_system) 
                                   VALUES ('$jadwal_id', '$nim', 'hadir', 'qr', '$location', '$ip', '$device', 1)");
}

if ($query) {
    $message = "Presensi berhasil dicatat untuk {$qr_session['nama_mk']} - {$qr_session['materi']} di {$qr_session['nama_lab']}";
    
    // Jika ini adalah jadwal INHALL, update penggantian_inhall
    if ($is_inhall && isset($inhall_id)) {
        mysqli_query($conn, "UPDATE penggantian_inhall SET status = 'hadir', jadwal_inhall_id = '$jadwal_id' 
                              WHERE id = '$inhall_id'");
        
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
