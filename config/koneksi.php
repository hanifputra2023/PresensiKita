<?php

// 1. Definisikan Default (Settingan Teman Anda / XAMPP)
$host = "127.0.0.1";
$user = "admin";
$pass = "123"; // XAMPP default kosong
$db   = "presensi";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
mysqli_query($conn, "SET time_zone = '+07:00'");

?>