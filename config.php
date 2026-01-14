<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Setting Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');
$host = "localhost";
$user = "root";
$pass = "";
$db   = "automation_dashboard"; // Pastikan nama database di phpMyAdmin sama persis

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}

mysqli_query($conn, "SET time_zone = '+07:00'");

// ==========================================
// FITUR MAINTENANCE MODE (SAKLAR)
// ==========================================
$maintenance_mode = false; // Ganti jadi TRUE untuk mengaktifkan maintenance

if ($maintenance_mode) {
    // Cek halaman apa yang sedang dibuka sekarang
    $current_page = basename($_SERVER['PHP_SELF']);
    include 'services.html';
    exit();
}
