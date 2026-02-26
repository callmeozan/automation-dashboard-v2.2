<?php
ob_start();
// 1. Hubungkan ke database (Gunakan file auth_and_config Bapak)
include '../layouts/auth_and_config.php';

// 2. Cek apakah form dikirim melalui method POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Ambil data dari inputan Modal dan bersihkan (Security)
    $tanggal    = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $mesin = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['mesin'])));
    $motor = trim(mysqli_real_escape_string($conn, $_POST['motor']));
    $de         = mysqli_real_escape_string($conn, $_POST['de']);
    $body       = mysqli_real_escape_string($conn, $_POST['body']);
    $nde        = mysqli_real_escape_string($conn, $_POST['nde']);
    $temp_limit = mysqli_real_escape_string($conn, $_POST['temp_limit']);
    $note       = mysqli_real_escape_string($conn, $_POST['note']);

    // 4. Query INSERT ke tabel tb_temperature
    $sql = "INSERT INTO tb_temperature (tanggal, mesin, motor, de, body, nde, temp_limit, note) 
            VALUES ('$tanggal', '$mesin', '$motor', '$de', '$body', '$nde', '$temp_limit', '$note')";

    if (mysqli_query($conn, $sql)) {
        mysqli_close($conn); // Tutup koneksi sebelum keluar
        
        // BERSIHKAN BUFFER: Pastikan tidak ada karakter liar yang ikut terkirim
        ob_end_clean(); 
        
        // REDIRECT 303: Wajib bagi Turbo Drive
        header("Location: ../temperature.php?msg=success", true, 303);
        exit();
    } else {
        mysqli_close($conn);
        ob_end_clean();
        header("Location: ../temperature.php?msg=error", true, 303);
        exit();
    }
}