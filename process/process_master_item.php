<?php
include '../config.php';

$action = $_POST['action'];
$id = $_POST['item_id'];
$code = mysqli_real_escape_string($conn, $_POST['item_code']);
$name = mysqli_real_escape_string($conn, $_POST['item_name']);
$brand = mysqli_real_escape_string($conn, $_POST['brand']);
$spec = mysqli_real_escape_string($conn, $_POST['model_spec']);
$loc = mysqli_real_escape_string($conn, $_POST['location']);
$unit = mysqli_real_escape_string($conn, $_POST['unit']);
$stock = (int)$_POST['stock'];
$min = (int)$_POST['min_stock'];

if ($action == 'create') {
    $query = "INSERT INTO tb_master_items (item_code, item_name, brand, model_spec, stock, min_stock, unit, location)
              VALUES ('$code', '$name', '$brand', '$spec', '$stock', '$min', '$unit', '$loc')";
} else {
    $query = "UPDATE tb_master_items SET 
              item_code='$code', item_name='$name', brand='$brand', model_spec='$spec',
              stock='$stock', min_stock='$min', unit='$unit', location='$loc'
              WHERE item_id='$id'";
}

if (mysqli_query($conn, $query)) {
    header("Location: ../master_items.php?status=success");
} else {
    header("Location: ../master_items.php?status=error");
}
?>