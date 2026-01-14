<?php
include '../config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 1. Ambil Nama File Lama Dulu (Sebelum dihapus datanya)
    $queryGetFile = mysqli_query($conn, "SELECT attachment_file FROM tb_assets WHERE asset_id = '$id'");
    $dataFile = mysqli_fetch_assoc($queryGetFile);
    $fileToDelete = $dataFile['attachment_file'];

    // 2. Hapus Data dari Database
    $queryDelete = "DELETE FROM tb_assets WHERE asset_id = '$id'";

    if (mysqli_query($conn, $queryDelete)) {
        
        // 3. Hapus File Fisik (Jika Ada)
        if (!empty($fileToDelete)) {
            $filePath = "../uploads/" . $fileToDelete;
            if (file_exists($filePath)) {
                unlink($filePath); // Perintah hapus file
            }
        }
        
        header("Location: ../database.php?status=deleted");
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}
?>