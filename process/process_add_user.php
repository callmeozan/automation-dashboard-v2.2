<?php
session_start();
include '../config.php';

// Proteksi: Hanya Admin yang boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php?status=error&msg=Akses Ditolak");
    exit();
}

// Redirect target (fallback ke manage_users.php atau dashboard.php)
$redirectTo = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : '../manage_users.php';

// 1. Tangkap Data
$username   = mysqli_real_escape_string($conn, trim($_POST['username']));
$password   = mysqli_real_escape_string($conn, $_POST['password']);
$full_name  = mysqli_real_escape_string($conn, trim($_POST['full_name']));
$short_name = mysqli_real_escape_string($conn, trim($_POST['short_name']));
$role       = mysqli_real_escape_string($conn, $_POST['role']);
$avatar     = 'default.png'; // Default foto

// Tangkap status Field Member (1 jika dicentang, 0 jika tidak)
$is_field_member = isset($_POST['is_field_member']) ? 1 : 0;

// 2. Hash Password (MD5)
$password_hash = md5($password);

// 3. Cek Username Kembar
$check = mysqli_query($conn, "SELECT username FROM tb_users WHERE username = '$username'");
if (mysqli_num_rows($check) > 0) {
    header("Location: {$redirectTo}?status=error&msg=Username/NIK sudah terdaftar!");
    exit();
}

// 4. Simpan ke Database (sertakan is_field_member)
$query = "INSERT INTO tb_users (username, password, full_name, short_name, role, avatar, is_field_member) 
          VALUES ('$username', '$password_hash', '$full_name', '$short_name', '$role', '$avatar', $is_field_member)";

if (mysqli_query($conn, $query)) {
    header("Location: {$redirectTo}?status=success&msg=User berhasil ditambahkan");
} else {
    $error = urlencode(mysqli_error($conn));
    header("Location: {$redirectTo}?status=error&msg=$error");
}
exit();
?>