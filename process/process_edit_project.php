<?php
include '../config.php';

// Tangkap Data dari Form
$id   = $_POST['project_id'];
$name = mysqli_real_escape_string($conn, $_POST['project_name']);
$desc = mysqli_real_escape_string($conn, $_POST['description']);
$date = $_POST['due_date'];
// $cat  = $_POST['category']; // (Tidak dipakai di form baru)
$status = $_POST['status'];    // Tangkap Status Baru
// --- LOGIC BARU UNTUK MULTI-SELECT TEAM ---
if (isset($_POST['team'])) {
    if (is_array($_POST['team'])) {
        $team_string = implode(", ", $_POST['team']);
    } else {
        $team_string = $_POST['team'];
    }
} else {
    $team_string = "";
}
$team = mysqli_real_escape_string($conn, $team_string);
// -------------------------------------------
$act  = mysqli_real_escape_string($conn, $_POST['activity']);
$plant= mysqli_real_escape_string($conn, $_POST['plant']);

// Query Update
$query = "UPDATE tb_projects SET 
            project_name = '$name',
            description = '$desc',
            due_date = '$date',
            status = '$status',
            team_members = '$team',
            activity = '$act',
            plant = '$plant'
          WHERE project_id = '$id'";

if (mysqli_query($conn, $query)) {
    // SUKSES: Kirim sinyal 'updated' ke project.php
    header("Location: ../project.php?status=updated");
} else {
    // GAGAL: Kirim pesan error
    $error = urlencode(mysqli_error($conn));
    header("Location: ../project.php?status=error&msg=$error");
}
?>