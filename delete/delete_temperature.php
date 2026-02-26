<?php
include '../layouts/auth_and_config.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // Query Hapus
    $sql = "DELETE FROM tb_temperature WHERE id = '$id'";

    if (mysqli_query($conn, $sql)) {
        mysqli_close($conn);
        // Kembali ke halaman utama dengan status sukses
        header("Location: ../temperature.php?msg=deleted", true, 303);
        exit();
    } else {
        mysqli_close($conn);
        header("Location: ../temperature.php?msg=error", true, 303);
        exit();
    }
}