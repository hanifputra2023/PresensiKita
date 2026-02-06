<?php
session_start();
require_once '../config/koneksi.php';

header('Content-Type: application/json');

// Cek session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['valid' => false, 'message' => 'Session expired']);
    exit;
}

// Cek request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pass_check = $_POST['password_lama'] ?? '';

if (empty($pass_check)) {
    echo json_encode(['valid' => false, 'message' => 'Password tidak boleh kosong']);
    exit;
}

// Ambil hash password dari database
$stmt_pwd = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_pwd, "i", $user_id);
mysqli_stmt_execute($stmt_pwd);
$pwd_result = mysqli_stmt_get_result($stmt_pwd);
$pwd_data = mysqli_fetch_assoc($pwd_result);

if ($pwd_data && password_verify($pass_check, $pwd_data['password'])) {
    echo json_encode(['valid' => true, 'message' => 'Password benar']);
} else {
    echo json_encode(['valid' => false, 'message' => 'Password lama salah!']);
}
