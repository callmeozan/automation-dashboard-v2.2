<?php
ob_start();
include '../layouts/auth_and_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil ID dan data baru
    $id      = mysqli_real_escape_string($conn, $_POST['id']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $mesin   = mysqli_real_escape_string($conn, $_POST['mesin']);
    $motor   = mysqli_real_escape_string($conn, $_POST['motor']);
    $de      = mysqli_real_escape_string($conn, $_POST['de']);
    $body    = mysqli_real_escape_string($conn, $_POST['body']);
    $nde     = mysqli_real_escape_string($conn, $_POST['nde']);
    $note    = mysqli_real_escape_string($conn, $_POST['note']);

    // Query Update
    $sql = "UPDATE tb_temperature SET 
            tanggal = '$tanggal', 
            mesin = '$mesin', 
            motor = '$motor', 
            de = '$de', 
            body = '$body', 
            nde = '$nde', 
            note = '$note' 
            WHERE id = '$id'";

    if (mysqli_query($conn, $sql)) {
        mysqli_close($conn);
        ob_end_clean();
        header("Location: ../temperature.php?msg=updated", true, 303);
        exit();
    } else {
        mysqli_close($conn);
        ob_end_clean();
        header("Location: ../temperature.php?msg=error", true, 303);
        exit();
    }
}