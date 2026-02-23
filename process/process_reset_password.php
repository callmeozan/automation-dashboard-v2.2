<?php
session_start();
include '../config.php';

// 1. Proteksi: Hanya Admin yang boleh reset password
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// 2. Ambil ID dari URL
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // 3. Set Password Default dengan enkripsi MD5 (Sesuai database Bapak)
    // "123456" di MD5 akan menjadi "e10adc3949ba59abbe56e057f20f883e"
    $password_default = md5("123456"); 

    // 4. Eksekusi Update
    $sql = "UPDATE tb_users SET password = '$password_default' WHERE user_id = '$id'";
    $query = mysqli_query($conn, $sql);

    if ($query) {
        header("Location: ../manage_users.php?status=success&msg=Password berhasil di-reset ke '123456'.");
    } else {
        header("Location: ../manage_users.php?status=error&msg=Gagal mereset password.");
    }
} else {
    header("Location: ../manage_users.php");
}
exit();
?>