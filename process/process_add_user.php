<?php
session_start();
include '../config.php';

// Proteksi: Hanya Admin yang boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php?status=error&msg=Akses Ditolak");
    exit();
}

// 1. Tangkap Data
$username   = mysqli_real_escape_string($conn, $_POST['username']);
$password   = mysqli_real_escape_string($conn, $_POST['password']);
$full_name  = mysqli_real_escape_string($conn, $_POST['full_name']);
$short_name = mysqli_real_escape_string($conn, $_POST['short_name']);
$role       = mysqli_real_escape_string($conn, $_POST['role']);
$avatar     = 'default.png'; // Default foto

// 2. Hash Password (MD5)
// PENTING: Harus MD5 karena auth.php kita pakai md5()
$password_hash = md5($password);

// 3. Cek Username Kembar
$check = mysqli_query($conn, "SELECT username FROM tb_users WHERE username = '$username'");
if(mysqli_num_rows($check) > 0){
    header("Location: ../dashboard.php?status=error&msg=Username/NIK sudah terdaftar!");
    exit();
}

// 4. Simpan ke Database
$query = "INSERT INTO tb_users (username, password, full_name, short_name, role, avatar) 
          VALUES ('$username', '$password_hash', '$full_name', '$short_name', '$role', '$avatar')";

if (mysqli_query($conn, $query)) {
    header("Location: ../dashboard.php?status=success&msg=User berhasil ditambahkan");
} else {
    $error = urlencode(mysqli_error($conn));
    header("Location: ../dashboard.php?status=error&msg=$error");
}
?>