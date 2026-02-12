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
        echo json_encode(['status' => 'success', 'message' => 'Status berhasil diubah']);
    } else {
        // Memberikan pesan error yang lebih spesifik untuk debugging
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>