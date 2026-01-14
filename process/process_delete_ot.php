<?php
// 1. Cek Session (Biar aman dari error "Ignoring session_start")
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// 2. AMBIL DATA DULU (WAJIB DI ATAS)
// Supaya variabel $id dan $user_id bisa dipakai di logika bawahnya
$id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$my_role = $_SESSION['role'];

// 3. TENTUKAN LOGIKA QUERY (ADMIN vs USER)
$isAdmin = ($my_role == 'admin' || $my_role == 'section');

if ($isAdmin) {
    // ADMIN: Hapus data apa saja berdasarkan ID (Bebas status)
    $query = "DELETE FROM tb_overtime WHERE ot_id='$id'";
} else {
    // USER BIASA: Hapus hanya jika Punya Sendiri DAN Status Pending
    $query = "DELETE FROM tb_overtime WHERE ot_id='$id' AND user_id='$user_id' AND status='Pending'";
}

// 4. EKSEKUSI QUERY
if (mysqli_query($conn, $query)) {
    // Cek apakah ada data yang terhapus?
    if (mysqli_affected_rows($conn) > 0) {
        header("Location: ../overtime.php?status=deleted");
    } else {
        // Query jalan tapi tidak ada yg terhapus (Misal user biasa coba hapus data Approved)
        header("Location: ../overtime.php?status=error&msg=Gagal hapus. Data terkunci atau bukan milik Anda.");
    }
} else {
    header("Location: ../overtime.php?status=error&msg=" . urlencode(mysqli_error($conn)));
}
?>