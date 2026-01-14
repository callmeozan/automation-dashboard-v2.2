<?php
session_start();
include '../config.php';

// 1. Cek Hak Akses (Hanya Admin/Section yang boleh approve)
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'section')) {
    header("Location: ../overtime.php?status=error&msg=Akses Ditolak");
    exit();
}

// 2. Ambil ID dan Status dari URL
$ot_id = $_GET['id'];
$status = $_GET['status']; // 'Approved' atau 'Rejected'
$approver = $_SESSION['full_name']; // Nama Bos yang klik

// 3. Update Database
$query = "UPDATE tb_overtime SET status='$status', approved_by='$approver' WHERE ot_id='$ot_id'";

if (mysqli_query($conn, $query)) {
    header("Location: ../overtime.php?status=updated");
} else {
    header("Location: ../overtime.php?status=error&msg=" . urlencode(mysqli_error($conn)));
}
?>