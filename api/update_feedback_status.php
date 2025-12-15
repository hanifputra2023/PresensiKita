<?php
// API untuk update status feedback
require_once '../config/koneksi.php';

if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    $feedback_id = intval($data['feedback_id'] ?? 0);
    $new_status = $data['status'] ?? null;
    $admin_response = $data['admin_response'] ?? null;
    $admin_id = $_SESSION['user_id'];
    
    if (!$feedback_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        exit;
    }
    
    // Validate status
    $valid_statuses = ['open', 'in_progress', 'resolved', 'closed', 'rejected'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }
    
    // Get current status using prepared statement
    $query = "SELECT status FROM feedback WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param('i', $feedback_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Feedback tidak ditemukan']);
        $stmt->close();
        exit;
    }
    
    $feedback = $result->fetch_assoc();
    $old_status = $feedback['status'];
    $stmt->close();
    
    // Prepare update query
    $resolved_at = null;
    if ($new_status === 'resolved' || $new_status === 'closed') {
        $resolved_at = date('Y-m-d H:i:s');
    }
    
    // Update feedback with prepared statement
    $update_query = "UPDATE feedback SET status = ?";
    $types = 's';
    $params = [$new_status];
    
    if ($resolved_at) {
        $update_query .= ", resolved_at = ?";
        $types .= 's';
        $params[] = $resolved_at;
    }
    
    if ($admin_response) {
        $update_query .= ", admin_response = ?";
        $types .= 's';
        $params[] = $admin_response;
    }
    
    $update_query .= " WHERE id = ?";
    $types .= 'i';
    $params[] = $feedback_id;
    
    $update_stmt = $koneksi->prepare($update_query);
    
    if (!$update_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    // Bind parameters dynamically
    $bind_params = array_merge([$types], $params);
    call_user_func_array([$update_stmt, 'bind_param'], $bind_params);
    
    if ($update_stmt->execute()) {
        // Log the change
        $log_query = "INSERT INTO feedback_log (feedback_id, old_status, new_status, changed_by, note) VALUES (?, ?, ?, ?, ?)";
        $log_stmt = $koneksi->prepare($log_query);
        
        if ($log_stmt) {
            $note = $admin_response ? substr($admin_response, 0, 100) : '';
            $log_stmt->bind_param('issss', $feedback_id, $old_status, $new_status, $admin_id, $note);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
    }
    
    $update_stmt->close();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>

