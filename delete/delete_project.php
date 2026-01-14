<?php
include '../config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '../dashboard.php';
    
    // Query Hapus
    $query = "DELETE FROM tb_projects WHERE project_id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../dashboard.php?status=deleted");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>