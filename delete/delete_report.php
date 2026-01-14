<?php
include '../config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Ambil Nama File Lama Dulu (Untuk dihapus)
    $queryGetFile = mysqli_query($conn, "SELECT evidence_file FROM tb_daily_reports WHERE report_id = '$id'");
    $dataFile = mysqli_fetch_assoc($queryGetFile);
    $fileToDelete = $dataFile['evidence_file'];

    // 2. Hapus Data dari Database
    $queryDelete = "DELETE FROM tb_daily_reports WHERE report_id = '$id'";

    if (mysqli_query($conn, $queryDelete)) {
        
        // 3. Hapus File Fisik (Jika Ada)
        if (!empty($fileToDelete)) {
            $filePath = "../uploads/" . $fileToDelete;
            if (file_exists($filePath)) {
                unlink($filePath); // Hapus file dari folder
            }
        }
        
        header("Location: ../laporan.php?status=deleted");
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}
?>