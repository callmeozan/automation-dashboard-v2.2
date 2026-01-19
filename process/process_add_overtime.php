<?php
session_start();
include '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil Data dari Form
    $user_id    = $_SESSION['user_id']; // Ambil ID user yang sedang login
    $date_ot    = $_POST['date_ot'];
    $spk_number = $_POST['spk_number'];
    $time_start = $_POST['time_start'];
    $time_end   = $_POST['time_end'];
    $activity   = mysqli_real_escape_string($conn, $_POST['activity']);

    // 2. Hitung Durasi Otomatis (Dalam Satuan Jam)
    $start_ts = strtotime($time_start);
    $end_ts   = strtotime($time_end);

    // Jika jam selesai lebih kecil dari jam mulai (misal lembur sampai pagi besok), tambah 24 jam
    if ($end_ts < $start_ts) {
        $end_ts += 24 * 60 * 60;
    }

    // Hitung selisih detik dibagi 3600 untuk dapat jam (desimal)
    $duration = ($end_ts - $start_ts) / 3600;
    
    // Format jadi 1 angka di belakang koma (misal 2.5)
    $duration = number_format($duration, 1);

    // 3. Simpan ke Database
    $query = "INSERT INTO tb_overtime (user_id, date_ot, time_start, time_end, duration, spk_number, activity, status) 
              VALUES ('$user_id', '$date_ot', '$time_start', '$time_end', '$duration', '$spk_number', '$activity', 'Pending')";

    if (mysqli_query($conn, $query)) {

    // =======================================================
    // 🔥 SAKLAR NOTIFIKASI WA (MULAI DARI SINI)
    // =======================================================
    
    // Format Tanggal biar enak dibaca (contoh: 15 Jan 2026)
    $tgl_indo = date('d M Y', strtotime($date_ot));
    
    // Rakit Pesan
    $pesan  = "📢 *INFO LEMBUR BARU*\n\n";
    $pesan .= "👤 Nama: *$full_name*\n";
    $pesan .= "📅 Tgl: $tgl_indo\n";
    $pesan .= "⏰ Jam: $time_start s.d $time_end ($hours Jam)\n";
    $pesan .= "📋 SPK: " . ($spk_number ? $spk_number : '-') . "\n";
    $pesan .= "🔧 Ket: $activity\n\n";
    $pesan .= "Mohon dicek ya Bos! 🙏\n";
    // $pesan .= "Link: " . $base_url . "overtime.php"; // Opsional kalau base_url sudah diset

    // EKSEKUSI KIRIM (Memanggil fungsi dari config.php)
    send_wa_group($pesan);
    
    // =======================================================
    // 🔥 SELESAI
    // =======================================================

        header("Location: ../overtime.php?status=success");
    } else {
        header("Location: ../overtime.php?status=error&msg=" . urlencode(mysqli_error($conn)));
    }
}
?>