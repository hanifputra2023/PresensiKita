<?php
// Disable error display for JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json');

// Include config
require_once '../config/koneksi.php';
require_once '../includes/fungsi.php';

// Get search term
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// If empty search, return empty array
if (empty($search)) {
    echo json_encode([
        'success' => true,
        'data' => [],
        'count' => 0
    ]);
    exit;
}

// Build query with search term
$search_param = '%' . $search . '%';

// Query untuk mencari berdasarkan username
$sql = "SELECT l.*, u.username, COALESCE(m.foto, a.foto) as foto 
        FROM log_presensi l 
        LEFT JOIN users u ON l.user_id = u.id 
        LEFT JOIN mahasiswa m ON u.id = m.user_id
        LEFT JOIN asisten a ON u.id = a.user_id
        WHERE u.username LIKE ?
        ORDER BY l.created_at DESC 
        LIMIT 50";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $search_param);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$logs = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Determine badge class and action label
    $aksi = strtoupper($row['aksi']);
    $badgeClass = 'bg-secondary';
    $icon = 'fa-info';
    $aksiLabel = $row['aksi'];
    $rowClass = '';
    
    if (strpos($aksi, 'LOGIN') !== false) {
        $badgeClass = 'bg-info text-dark';
        $icon = 'fa-sign-in-alt';
        $aksiLabel = 'Login Sistem';
        $rowClass = 'row-login';
    } elseif (strpos($aksi, 'PRESENSI_QR') !== false) {
        $badgeClass = 'bg-primary';
        $icon = 'fa-qrcode';
        $aksiLabel = 'Scan QR';
        $rowClass = 'row-update';
    } elseif (strpos($aksi, 'BROADCAST') !== false) {
        $badgeClass = 'bg-success';
        $icon = 'fa-bullhorn';
        $aksiLabel = 'Broadcast WA';
        $rowClass = 'row-create';
    } elseif (strpos($aksi, 'INSERT') !== false || strpos($aksi, 'ADD') !== false || strpos($aksi, 'CREATE') !== false || strpos($aksi, 'GENERATE') !== false) {
        $badgeClass = 'bg-success';
        $icon = 'fa-plus';
        $aksiLabel = 'Tambah Data';
        $rowClass = 'row-create';
    } elseif (strpos($aksi, 'UPDATE') !== false || strpos($aksi, 'EDIT') !== false || strpos($aksi, 'APPROVE') !== false) {
        $badgeClass = 'bg-warning text-dark';
        $icon = 'fa-pen';
        $aksiLabel = 'Ubah Data';
        $rowClass = 'row-update';
    } elseif (strpos($aksi, 'DELETE') !== false || strpos($aksi, 'REMOVE') !== false || strpos($aksi, 'REJECT') !== false) {
        $badgeClass = 'bg-danger';
        $icon = 'fa-trash';
        $aksiLabel = 'Hapus Data';
        $rowClass = 'row-delete';
    } else {
        $aksiLabel = ucfirst(strtolower(str_replace('_', ' ', $aksi)));
    }
    
    // Generate avatar
    $username = $row['username'] ?? 'System';
    $initial = strtoupper(substr($username, 0, 1));
    $avatarBg = '#212529';
    
    if (strtolower($username) !== 'admin') {
        $bgColors = ['#0066cc', '#66cc00', '#ff9900', '#ff3333', '#00ccff', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6c757d'];
        $bgIndex = ord($initial) % count($bgColors);
        $avatarBg = $bgColors[$bgIndex];
    }
    
    $hasPhoto = !empty($row['foto']) && file_exists('../' . $row['foto']);
    
    $logs[] = [
        'id' => $row['id'],
        'created_at' => $row['created_at'],
        'username' => $username,
        'detail' => $row['detail'],
        'tabel' => $row['tabel'] ?? '-',
        'id_record' => $row['id_record'] ?? '-',
        'aksi' => $row['aksi'],
        'aksiLabel' => $aksiLabel,
        'badgeClass' => $badgeClass,
        'icon' => $icon,
        'rowClass' => $rowClass,
        'foto' => $hasPhoto ? $row['foto'] : null,
        'initial' => $initial,
        'avatarBg' => $avatarBg
    ];
}

echo json_encode([
    'success' => true,
    'data' => $logs,
    'count' => count($logs)
]);
