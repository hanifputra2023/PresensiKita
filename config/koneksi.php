<?php
// Konfigurasi Database - Sederhana
// $host = "localhost";
// $user = "root";
// $pass = "";
// $db   = "presensi";

// Koneksi database
$conn = mysqli_connect('mysql', 'root', 'root', 'presensi');

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Set timezone MySQL ke Asia/Jakarta (penting untuk hosting!)
mysqli_query($conn, "SET time_zone = '+07:00'");
?>
