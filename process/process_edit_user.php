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
    $id         = mysqli_real_escape_string($conn, $_POST['user_id']);
    $full_name  = mysqli_real_escape_string($conn, $_POST['full_name']);
    $short_name = mysqli_real_escape_string($conn, $_POST['short_name']);
    $role       = mysqli_real_escape_string($conn, $_POST['role']);

    // 3. Eksekusi Update (Username tidak diubah karena biasanya NIK bersifat tetap)
    $sql = "UPDATE tb_users SET 
            full_name = '$full_name', 
            short_name = '$short_name', 
            role = '$role' 
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