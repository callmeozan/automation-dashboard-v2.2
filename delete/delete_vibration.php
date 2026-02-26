<?php
include '../layouts/auth_and_config.php';

// Pastikan hanya user yang punya akses yang bisa hapus
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'section')) {
    header("Location: ../vibration.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $query = "DELETE FROM tb_vibration WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../vibration.php?msg=deleted");
    } else {
        header("Location: ../vibration.php?msg=error");
    }
} else {
    header("Location: ../vibration.php");
}
exit();
?>