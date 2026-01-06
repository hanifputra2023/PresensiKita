<?php
// API untuk generate QR code
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/fungsi.php';

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

// Generate QR code baru
$qr_code = generate_qr_code();
$expired = date('Y-m-d H:i:s', strtotime('+' . QR_DURASI . ' hours'));

$stmt_ins = mysqli_prepare($conn, "INSERT INTO qr_code_session (jadwal_id, qr_code, expired_at) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt_ins, "iss", $jadwal_id, $qr_code, $expired);
mysqli_stmt_execute($stmt_ins);

echo json_encode([
    'success' => true,
    'qr_code' => $qr_code,
    'expired_at' => $expired
]);
?>
