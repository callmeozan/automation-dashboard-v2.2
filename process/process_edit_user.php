<?php
session_start();
include '../config.php';

// 1. Proteksi: Hanya Admin yang boleh edit
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// 2. Ambil Data dari POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id              = mysqli_real_escape_string($conn, $_POST['user_id']);
    $full_name       = mysqli_real_escape_string($conn, $_POST['full_name']);
    $short_name      = mysqli_real_escape_string($conn, $_POST['short_name']);
    $role            = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Tangkap status Field Member (1 jika dicentang, 0 jika uncheck)
    $is_field_member = isset($_POST['is_field_member']) ? 1 : 0;

    // 3. Eksekusi Update
    $sql = "UPDATE tb_users SET 
            full_name = '$full_name', 
            short_name = '$short_name', 
            role = '$role',
            is_field_member = $is_field_member
            WHERE user_id = '$id'";
    
    $query = mysqli_query($conn, $sql);

    if ($query) {
        header("Location: ../manage_users.php?status=success_update&msg=Data user berhasil diperbarui.");
    } else {
        header("Location: ../manage_users.php?status=error&msg=Gagal memperbarui data.");
    }
} else {
    header("Location: ../manage_users.php");
}
exit();
?>