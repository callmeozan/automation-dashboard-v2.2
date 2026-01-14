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
$input_pass = $_GET['pass'];
$admin_pass = "ozanganteng"; // Ganti dengan password admin yang disepakati

// (Atau lebih aman cek password user yang sedang login dari DB)
// Tapi untuk simpelnya logika ini dulu:

if ($input_pass == $admin_pass) { // Password rahasia untuk reset
    
    // TRUNCATE: Hapus isi dan reset ID ke 1
    $query = "TRUNCATE TABLE tb_master_items";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../master_items.php?status=deleted_all");
    } else {
        header("Location: ../master_items.php?status=error&msg=Gagal reset DB");
    }
} else {
    header("Location: ../master_items.php?status=wrong_pass");
}
?>