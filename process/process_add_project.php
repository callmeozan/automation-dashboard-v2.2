<?php
include '../config.php';

// 1. Tangkap Data
$name  = mysqli_real_escape_string($conn, $_POST['project_name']);
$desc  = mysqli_real_escape_string($conn, $_POST['description']);
$date  = $_POST['due_date'];
$cat   = $_POST['category'];
$plant = mysqli_real_escape_string($conn, $_POST['plant']);
$act   = mysqli_real_escape_string($conn, $_POST['activity']);
$status = $_POST['status']; // Ambil Status dari dropdown

// 2. Tangkap Team (Array to String untuk Multi Select)
if (isset($_POST['team'])) {
    if (is_array($_POST['team'])) {
        $team = implode(", ", $_POST['team']);
    } else {
        $team = $_POST['team'];
    }
} else {
    $team = "";
}
$team = mysqli_real_escape_string($conn, $team);

// 3. Redirect Logic (Balik ke halaman asal)
$redirect_page = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'project.php';

// 4. Query Insert
$query = "INSERT INTO tb_projects (
    project_name, description, due_date, category_badge, team_members, 
    plant, activity, status, progress_percent
) VALUES (
    '$name', '$desc', '$date', '$cat', '$team', 
    '$plant', '$act', '$status', 0
)";

if (mysqli_query($conn, $query)) {
    header("Location: ../" . $redirect_page . "?status=success");
} else {
    $error = urlencode(mysqli_error($conn));
    header("Location: ../" . $redirect_page . "?status=error&msg=$error");
}
