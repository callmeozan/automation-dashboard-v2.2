<?php
// process/process_delete_manual.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Naik satu folder untuk mengambil auth & config
include '../layouts/auth_and_config.php';

// Validasi role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'section')) {
    header("Location: ../user_manual.php?msg=unauthorized");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 1. Cari nama file PDF fisik
    $stmt = $conn->prepare("SELECT file_pdf FROM tb_manuals WHERE manual_id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $filePath = dirname(__DIR__) . '/uploads/manuals/' . $row['file_pdf'];

        // Hapus berkas fisik jika ada
        if (!empty($row['file_pdf']) && file_exists($filePath)) {
            @unlink($filePath);
        }

        // 2. Hapus data dari tabel
        $delStmt = $conn->prepare("DELETE FROM tb_manuals WHERE manual_id = ?");
        $delStmt->bind_param("i", $id);
        $delStmt->execute();
        $delStmt->close();
    }
    $stmt->close();

    header("Location: ../user_manual.php?msg=deleted");
    exit;
}

header("Location: ../user_manual.php?msg=invalid_id");
exit;