<?php
require_once dirname(__DIR__) . '/includes/fungsi.php';

// Create API to lookup user (AJAX)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = isset($_GET['q']) ? $_GET['q'] : '';
$q = "%" . $q . "%";

// Find mahasiswa by name or NIM, exclude the logged-in user
$mahasiswa = get_mahasiswa_login();
$my_nim = $mahasiswa['nim'];

$query = "SELECT nim, nama, kode_kelas, sesi 
          FROM mahasiswa 
          WHERE (nama LIKE ? OR nim LIKE ?) 
          AND nim != ? AND status = 'aktif'
          LIMIT 10";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sss", $q, $q, $my_nim);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['nim'],
        'text' => $row['nama'] . ' (NIM: ' . $row['nim'] . ' - Kelas ' . $row['kode_kelas'] . ' Sesi ' . $row['sesi'] . ')'
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>