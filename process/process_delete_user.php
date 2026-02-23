<?php
session_start();
include '../config.php';

// 1. Proteksi: Hanya Admin yang boleh hapus
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// 2. Ambil ID dari URL
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // 3. Eksekusi Hapus
    $query = mysqli_query($conn, "DELETE FROM tb_users WHERE user_id = '$id'");

    if ($query) {
        header("Location: ../manage_users.php?status=deleted&msg=User berhasil dihapus.");
    } else {
        header("Location: ../manage_users.php?status=error&msg=Gagal menghapus user.");
    }
} else {
    header("Location: ../manage_users.php");
}
exit();
?>