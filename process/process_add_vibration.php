<?php
ob_start();
// 1. Hubungkan ke database (Gunakan file auth_and_config Bapak)
include '../layouts/auth_and_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $mesin = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['mesin'])));
    $motor = trim(mysqli_real_escape_string($conn, $_POST['motor']));
    
    // Logika agar kalau inputan dibiarkan kosong, masuknya sebagai NULL (bukan string kosong yang bikin error)
    $de_a = $_POST['de_a'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['de_a']) . "'" : "NULL";
    $de_h = $_POST['de_h'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['de_h']) . "'" : "NULL";
    $de_v = $_POST['de_v'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['de_v']) . "'" : "NULL";
    
    $nde_a = $_POST['nde_a'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['nde_a']) . "'" : "NULL";
    $nde_h = $_POST['nde_h'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['nde_h']) . "'" : "NULL";
    $nde_v = $_POST['nde_v'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['nde_v']) . "'" : "NULL";
    
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    $query = "INSERT INTO tb_vibration (tanggal, mesin, motor, de_a, de_h, de_v, nde_a, nde_h, nde_v, note) 
              VALUES ('$tanggal', '$mesin', '$motor', $de_a, $de_h, $de_v, $nde_a, $nde_h, $nde_v, '$note')";

    if (mysqli_query($conn, $query)) {
        header("Location: ../vibration.php?msg=success");
    } else {
        // Jika butuh ngecek error database, Bapak bisa hapus komentar di bawah ini:
        // echo mysqli_error($conn); exit;
        header("Location: ../vibration.php?msg=error");
    }
    exit();
}
?>