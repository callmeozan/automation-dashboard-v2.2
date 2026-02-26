<?php
ob_start();
include '../layouts/auth_and_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $mesin = mysqli_real_escape_string($conn, $_POST['mesin']);
    $motor = mysqli_real_escape_string($conn, $_POST['motor']);
    
    $de_a = $_POST['de_a'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['de_a']) . "'" : "NULL";
    $de_h = $_POST['de_h'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['de_h']) . "'" : "NULL";
    $de_v = $_POST['de_v'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['de_v']) . "'" : "NULL";
    
    $nde_a = $_POST['nde_a'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['nde_a']) . "'" : "NULL";
    $nde_h = $_POST['nde_h'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['nde_h']) . "'" : "NULL";
    $nde_v = $_POST['nde_v'] !== '' ? "'" . mysqli_real_escape_string($conn, $_POST['nde_v']) . "'" : "NULL";
    
    $note = mysqli_real_escape_string($conn, $_POST['note']);

    $query = "UPDATE tb_vibration SET 
                tanggal = '$tanggal', 
                mesin = '$mesin', 
                motor = '$motor', 
                de_a = $de_a, 
                de_h = $de_h, 
                de_v = $de_v, 
                nde_a = $nde_a, 
                nde_h = $nde_h, 
                nde_v = $nde_v, 
                note = '$note' 
              WHERE id = '$id'";

    if (mysqli_query($conn, $query)) {
        header("Location: ../vibration.php?msg=updated");
    } else {
        header("Location: ../vibration.php?msg=error");
    }
    exit();
}
?>