<?php
session_start();
include '../config.php';

// 1. Cek Login Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../master_items.php?status=error&msg=Akses Ditolak!");
    exit();
}

// 2. Cek Password (Hardcode atau cek DB user admin)
// Disini contoh simpel cek password inputan JS tadi
$input_pass = isset($_GET['pass']) ? $_GET['pass'] : '';
$admin_pass = "ozanganteng"; // Ganti dengan password admin yang disepakati

// (Atau lebih aman cek password user yang sedang login dari DB)
// Tapi untuk simpelnya logika ini dulu:

if ($input_pass === $admin_pass) { // Password rahasia untuk reset

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    // TRUNCATE: Hapus isi dan reset ID ke 1
    // $query = "TRUNCATE TABLE tb_master_items";
    $reset_items  = mysqli_query($conn, "TRUNCATE TABLE tb_master_items");
    $reset_stocks = mysqli_query($conn, "TRUNCATE TABLE tb_stocks");
    
    if ($reset_items && $reset_stocks) {
        header("Location: ../master_items.php?status=deleted_all");
    } else {
        header("Location: ../master_items.php?status=error&msg=" . urlencode(mysqli_error($conn)));
    }
} else {
    header("Location: ../master_items.php?status=wrong_pass");
}
?>