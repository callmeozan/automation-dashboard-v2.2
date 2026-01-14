<?php
include '../config.php';
$id = $_GET['id'];
if (mysqli_query($conn, "DELETE FROM tb_master_items WHERE item_id='$id'")) {
    header("Location: ../master_items.php?status=deleted");
} else {
    echo "Error";
}
?>