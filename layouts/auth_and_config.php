<?php
// layouts/auth_and_config.php
session_start();

// 1. CEK LOGIN (SATPAM)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. KONEKSI DATABASE
// include 'config.php';
include dirname(__DIR__) . '/config.php';

// 3. AMBIL DATA USER LOGIN
$id_user = $_SESSION['user_id'];
$role_user = $_SESSION['role'];

// --- LOGIKA NOTIFIKASI GLOBAL (Pindahkan ke sini) ---
$today = date('Y-m-d');
// Hitung Breakdown
$qBD = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE category='Breakdown' AND status='Open'");
$cBD = mysqli_num_rows($qBD);
// Hitung Overdue
$qOD = mysqli_query($conn, "SELECT * FROM tb_projects WHERE due_date < '$today' AND status != 'Done'");
$cOD = mysqli_num_rows($qOD);

$totalNotif = $cBD + $cOD; // Sekarang $totalNotif sudah "Lahir" di awal!

// 4. DATA TEAM LIST (Sering dipakai di dropdown modal berbagai halaman)
$queryUsers = mysqli_query($conn, "SELECT short_name FROM tb_users WHERE short_name IS NOT NULL AND short_name != '' AND role != 'admin'");
$teamList = [];
while ($u = mysqli_fetch_assoc($queryUsers)) {
    $teamList[] = $u['short_name'];
}
?>