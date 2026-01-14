<?php
// process/update_progress.php
include '../config.php';

header('Content-Type: application/json'); // Wajib JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['project_id']) && isset($_POST['progress'])) {

        $id = mysqli_real_escape_string($conn, $_POST['project_id']);
        $pct = (int)$_POST['progress']; // Pastikan angka integer

        // Validasi 0-100
        if ($pct < 0) $pct = 0;
        if ($pct > 100) $pct = 100;

        // Update Database
        $query = "UPDATE tb_projects SET progress_percent = '$pct' WHERE project_id = '$id'";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['status' => 'success', 'new_val' => $pct]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    }
}
