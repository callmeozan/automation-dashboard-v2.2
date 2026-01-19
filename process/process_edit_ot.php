<?php
// 1. Cek Session dulu biar tidak error "Notice"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// 2. Cek apakah Form dikirim via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- A. AMBIL DATA DARI FORM DULU (PENTING! INI HARUS DI ATAS) ---
    $ot_id      = $_POST['ot_id']; 
    $date_ot    = $_POST['date_ot'];
    $time_start = $_POST['time_start'];
    $time_end   = $_POST['time_end'];
    $duration   = $_POST['duration'];
    $spk_number = $_POST['spk_number'];
    $activity   = mysqli_real_escape_string($conn, $_POST['activity']);
    
    // Ambil data User yang login
    $user_id    = $_SESSION['user_id'];
    $my_role    = $_SESSION['role'];

    // --- B. LOGIKA HAK AKSES (ADMIN vs USER) ---
    // Apakah yang login Admin/Section?
    $isAdmin = ($my_role == 'admin' || $my_role == 'section');

    // Tentukan Syarat WHERE berdasarkan Role
    if ($isAdmin) {
        // ADMIN: Boleh edit data apapun berdasarkan ID, abaikan status
        $sql_where = "WHERE ot_id='$ot_id'";
    } else {
        // USER BIASA: Wajib punya sendiri DAN Status masih Pending
        $sql_where = "WHERE ot_id='$ot_id' AND user_id='$user_id' AND status='Pending'";
    }

    // --- C. HITUNG ULANG DURASI ---
    $start_ts = strtotime($time_start);
    $end_ts   = strtotime($time_end);
    
    // Handle lembur lintas hari (misal jam 23:00 s/d 01:00)
    if ($end_ts < $start_ts) {
        $end_ts += 24 * 60 * 60; 
    }

    $raw_duration = ($end_ts - $start_ts) / 3600;
    if ($raw_duration > 4) {
        $raw_duration = $raw_duration - 1;
    }

    $duration = number_format($raw_duration, 1);

    // --- D. EKSEKUSI QUERY UPDATE ---
    $query = "UPDATE tb_overtime SET 
              date_ot='$date_ot', 
              time_start='$time_start', 
              time_end='$time_end', 
              duration='$duration', 
              spk_number='$spk_number', 
              activity='$activity' 
              $sql_where"; // <-- Variable WHERE ditempel disini

    if (mysqli_query($conn, $query)) {
        // Cek apakah ada baris yang berubah?
        // Jika Admin edit data user lain, atau user edit data sendiri
        if (mysqli_affected_rows($conn) > 0) {
            header("Location: ../overtime.php?status=success_update");
        } else {
            // Query jalan tapi tidak ada yg berubah (mungkin datanya sama persis)
            // Tetap anggap sukses
            header("Location: ../overtime.php?status=success_update");
        }
    } else {
        // Gagal SQL
        header("Location: ../overtime.php?status=error&msg=" . urlencode(mysqli_error($conn)));
    }

} else {
    // Jika ditembak langsung lewat URL (bukan POST)
    header("Location: ../overtime.php");
}
?>