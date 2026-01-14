<?php
session_start();
include 'config.php';

// Cek apakah form dikirim
if (isset($_POST['username']) && isset($_POST['password'])) {
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Enkripsi password inputan jadi MD5 (biar cocok sama database)
    $password_md5 = md5($password);

    // Cek User di Database
    $query = mysqli_query($conn, "SELECT * FROM tb_users WHERE username='$username' AND password='$password_md5'");
    
    if (mysqli_num_rows($query) > 0) {
        // --- LOGIN SUKSES ---
        $data = mysqli_fetch_assoc($query);
        
        $_SESSION['user_id']   = $data['user_id'];
        $_SESSION['username']  = $data['username'];
        $_SESSION['full_name'] = $data['full_name'];
        $_SESSION['role']      = $data['role'];
        $_SESSION['avatar']    = $data['avatar'];
        $user_id = $data['user_id'];
        mysqli_query($conn, "UPDATE tb_users SET last_login = NOW() WHERE user_id = '$user_id'");

        // Lempar ke Dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        // --- LOGIN GAGAL (Password Salah / User Gak Ada) ---
        header("Location: index.php?error=1");
        exit();
    }
} else {
    // Kalau akses langsung tanpa form
    header("Location: index.php");
    exit();
}
?>