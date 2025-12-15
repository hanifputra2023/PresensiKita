<?php
// Logout - hapus remember token
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET remember_token = NULL, token_expires = NULL WHERE id = '$user_id'");
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
