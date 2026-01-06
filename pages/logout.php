<?php
// Logout - hapus remember token dengan prepared statement
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "UPDATE users SET remember_token = NULL, token_expires = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
}

// Hapus cookies
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('remember_user', '', time() - 3600, '/');
}

session_destroy();
header("Location: index.php?page=login");
exit;
?>
