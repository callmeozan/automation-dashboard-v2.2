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

    if ($duration > 4) {
        $duration = $duration - 1;
    }
    
    // Format jadi 1 angka di belakang koma (misal 2.5)
    $duration = number_format($duration, 1);

    // --- 3. PROSES UPLOAD EVIDENCE (BARU) ---
    $evidence_string = ""; // Default kosong
    $uploaded_files = [];

    // Cek apakah user mengupload file
    if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
        
        // Buat folder jika belum ada
        $target_dir = "../uploads/evidence/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $total_files = count($_FILES['evidence']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            $filename = $_FILES['evidence']['name'][$i];
            $tmp_name = $_FILES['evidence']['tmp_name'][$i];
            $error    = $_FILES['evidence']['error'][$i];

            // Cek error upload
            if ($error === 0) {
                // Ganti nama file biar unik (Format: time_random_namaasli)
                // Ini penting supaya kalau ada file nama sama tidak tertimpa
                $new_name = time() . '_' . rand(100, 999) . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $filename);
                
                // Pindahkan file ke folder tujuan
                if (move_uploaded_file($tmp_name, $target_dir . $new_name)) {
                    $uploaded_files[] = $new_name;
                }
            }
        }

        // Gabungkan nama file dengan koma (file1.jpg,file2.png)
        if (!empty($uploaded_files)) {
            $evidence_string = implode(',', $uploaded_files);
        }
    }

    // 3. Simpan ke Database
    // $query = "INSERT INTO tb_overtime (user_id, date_ot, time_start, time_end, duration, spk_number, activity, status) 
    //           VALUES ('$user_id', '$date_ot', '$time_start', '$time_end', '$duration', '$spk_number', '$activity', 'Pending')";

    $query = "INSERT INTO tb_overtime (user_id, date_ot, time_start, time_end, duration, spk_number, activity, evidence, status, created_at) 
              VALUES ('$user_id', '$date_ot', '$time_start', '$time_end', '$duration', '$spk_number', '$activity', '$evidence_string', 'Pending', NOW())";

    if (mysqli_query($conn, $query)) {

    // =======================================================
    // 🔥 SAKLAR NOTIFIKASI WA (MULAI DARI SINI)
    // =======================================================
    
    // Format Tanggal biar enak dibaca (contoh: 15 Jan 2026)
    // $tgl_indo = date('d M Y', strtotime($date_ot));
    
    // Rakit Pesan
    // $pesan  = "📢 *INFO LEMBUR BARU*\n\n";
    // $pesan .= "👤 Nama: *$full_name*\n";
    // $pesan .= "📅 Tgl: $tgl_indo\n";
    // $pesan .= "⏰ Jam: $time_start s.d $time_end ($hours Jam)\n";
    // $pesan .= "📋 SPK: " . ($spk_number ? $spk_number : '-') . "\n";
    // $pesan .= "🔧 Ket: $activity\n\n";
    // $pesan .= "Mohon dicek ya Bos! 🙏\n";
    // $pesan .= "Link: " . $base_url . "overtime.php"; // Opsional kalau base_url sudah diset

    // EKSEKUSI KIRIM (Memanggil fungsi dari config.php)
    // send_wa_group($pesan);
    
    // =======================================================
    // 🔥 SELESAI
    // =======================================================

        header("Location: ../overtime.php?status=success");
    } else {
        header("Location: ../overtime.php?status=error&msg=" . urlencode(mysqli_error($conn)));
    }
}
?>