<?php
// API untuk generate QR code
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/fungsi.php';

// Authorization check - hanya asisten yang bisa generate QR
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'asisten') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Hanya asisten yang bisa generate QR']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$jadwal_id = isset($input['jadwal_id']) ? (int)$input['jadwal_id'] : 0;

if (!$jadwal_id) {
    echo json_encode(['success' => false, 'message' => 'Jadwal ID diperlukan']);
    exit;
}

// Ambil info jadwal untuk expired time
$stmt_jadwal = mysqli_prepare($conn, "SELECT tanggal, jam_selesai FROM jadwal WHERE id = ?");
mysqli_stmt_bind_param($stmt_jadwal, "i", $jadwal_id);
mysqli_stmt_execute($stmt_jadwal);
$jadwal_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jadwal));

if (!$jadwal_info) {
    echo json_encode(['success' => false, 'message' => 'Jadwal tidak ditemukan']);
    exit;
}

// Generate QR code baru
$qr_code = generate_qr_code();
$expired = $jadwal_info['tanggal'] . ' ' . $jadwal_info['jam_selesai']; // Expired saat jadwal selesai

$stmt_ins = mysqli_prepare($conn, "INSERT INTO qr_code_session (jadwal_id, qr_code, expired_at) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt_ins, "iss", $jadwal_id, $qr_code, $expired);
mysqli_stmt_execute($stmt_ins);

echo json_encode([
    'success' => true,
    'qr_code' => $qr_code,
    'expired_at' => $expired
]);
?>
