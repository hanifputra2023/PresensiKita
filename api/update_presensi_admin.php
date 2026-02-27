<?php
// Pastikan tidak ada output lain yang terkirim sebelum JSON
if (ob_get_length()) ob_clean();
// Header JSON agar response dibaca sebagai JSON oleh JavaScript
header('Content-Type: application/json');

// Pastikan user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST
    $jadwal_id = isset($_POST['jadwal_id']) ? escape($_POST['jadwal_id']) : '';
    $nim = isset($_POST['nim']) ? escape($_POST['nim']) : '';
    $status = isset($_POST['status']) ? escape($_POST['status']) : '';

    // Validasi input
    if (empty($jadwal_id) || empty($nim) || empty($status)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // [PERBAIKAN DUPLIKASI]
    // Karena database mungkin tidak memiliki UNIQUE KEY pada (jadwal_id, nim),
    // kita gunakan metode DELETE lalu INSERT untuk mencegah duplikasi data.
    
    // 1. Hapus data lama (membersihkan duplikat jika ada) - prepared statement
    $stmt_del = mysqli_prepare($conn, "DELETE FROM presensi_mahasiswa WHERE jadwal_id = ? AND nim = ?");
    mysqli_stmt_bind_param($stmt_del, "is", $jadwal_id, $nim);
    mysqli_stmt_execute($stmt_del);

    // 2. Insert data baru - prepared statement
    $stmt_ins = mysqli_prepare($conn, "INSERT INTO presensi_mahasiswa (jadwal_id, nim, status, waktu_presensi) 
              VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt_ins, "iss", $jadwal_id, $nim, $status);

    if (mysqli_stmt_execute($stmt_ins)) {
        // [FIX] Logika Otomatis Inhall
        // Jika status diubah jadi Izin/Sakit, buatkan/update record di penggantian_inhall
        if ($status == 'izin' || $status == 'sakit') {
            // Cek apakah sudah ada record inhall
            $stmt_cek_inhall = mysqli_prepare($conn, "SELECT id FROM penggantian_inhall WHERE jadwal_asli_id = ? AND nim = ?");
            mysqli_stmt_bind_param($stmt_cek_inhall, "is", $jadwal_id, $nim);
            mysqli_stmt_execute($stmt_cek_inhall);
            $res_inhall = mysqli_stmt_get_result($stmt_cek_inhall);
            
            // [FIX] approved_by di database merujuk ke tabel asisten(kode_asisten).
            // Karena Admin bukan asisten, ID Admin tidak bisa masuk karena Foreign Key Constraint.
            // Kita set NULL agar query berhasil (kolom approved_by membolehkan NULL).
            $approved_by = null; 
            
            if (mysqli_num_rows($res_inhall) == 0) {
                // Insert baru (Auto Approved karena Admin yang input)
                $alasan = "Diubah oleh Admin menjadi " . ucfirst($status);
                $stmt_add_inhall = mysqli_prepare($conn, "INSERT INTO penggantian_inhall 
                    (nim, jadwal_asli_id, materi_diulang, status, alasan_izin, status_approval, approved_by, approved_at, bukti_file) 
                    VALUES (?, ?, ?, 'terdaftar', ?, 'approved', ?, NOW(), NULL)");
                mysqli_stmt_bind_param($stmt_add_inhall, "sisss", $nim, $jadwal_id, $status, $alasan, $approved_by);
                if (!mysqli_stmt_execute($stmt_add_inhall)) {
                    // Kembalikan error agar admin tahu jika gagal insert ke tabel inhall
                    echo json_encode(['status' => 'error', 'message' => 'Gagal insert inhall: ' . mysqli_stmt_error($stmt_add_inhall)]);
                    exit;
                }
            } else {
                // Update existing (Pastikan approved)
                $row_inhall = mysqli_fetch_assoc($res_inhall);
                $inhall_id = $row_inhall['id'];
                $stmt_upd_inhall = mysqli_prepare($conn, "UPDATE penggantian_inhall 
                    SET materi_diulang = ?, 
                        status_approval = 'approved', 
                        approved_by = ?, 
                        approved_at = NOW(),
                        status = 'terdaftar',
                        alasan_reject = NULL
                    WHERE id = ?");
                mysqli_stmt_bind_param($stmt_upd_inhall, "ssi", $status, $approved_by, $inhall_id);
                if (!mysqli_stmt_execute($stmt_upd_inhall)) {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal update inhall: ' . mysqli_stmt_error($stmt_upd_inhall)]);
                    exit;
                }
            }
        } elseif ($status == 'hadir' || $status == 'alpha') {
            // Jika diubah jadi Hadir/Alpha, hapus kewajiban inhall jika ada
            $stmt_del_inhall = mysqli_prepare($conn, "DELETE FROM penggantian_inhall WHERE jadwal_asli_id = ? AND nim = ?");
            mysqli_stmt_bind_param($stmt_del_inhall, "is", $jadwal_id, $nim);
            mysqli_stmt_execute($stmt_del_inhall);
        }

        echo json_encode(['status' => 'success', 'message' => 'Status berhasil diubah']);
    } else {
        // Memberikan pesan error yang lebih spesifik untuk debugging
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>