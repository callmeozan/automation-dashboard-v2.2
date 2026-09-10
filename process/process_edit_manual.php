<?php
// process/process_edit_manual.php

// 1. Panggil auth_and_config terlebih dahulu sebelum session_start aktif
include '../layouts/auth_and_config.php';

// Validasi role
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'section')) {
    header("Location: ../user_manual.php?msg=unauthorized", true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = intval($_POST['manual_id'] ?? 0);
    $plant        = trim($_POST['plant'] ?? '');
    $area         = trim($_POST['area'] ?? '');
    $machine_name = trim($_POST['machine_name'] ?? '');
    $manual_title = trim($_POST['manual_title'] ?? '');

    if ($id <= 0 || empty($plant) || empty($machine_name) || empty($manual_title)) {
        header("Location: ../user_manual.php?status=empty_fields", true, 303);
        exit;
    }

    $uploadDir = dirname(__DIR__) . '/uploads/manuals/';

    // Jika ada upload file PDF baru
    if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['file_pdf']['tmp_name'];
        $fileName = $_FILES['file_pdf']['name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExt !== 'pdf') {
            header("Location: ../user_manual.php?status=invalid_format", true, 303);
            exit;
        }

        // Hapus file lama dari tabel tb_manuals
        $qOld = $conn->prepare("SELECT file_pdf FROM tb_manuals WHERE manual_id = ? LIMIT 1");
        $qOld->bind_param("i", $id);
        $qOld->execute();
        $oldRes = $qOld->get_result();
        if ($oldRow = $oldRes->fetch_assoc()) {
            $oldPath = $uploadDir . $oldRow['file_pdf'];
            if (!empty($oldRow['file_pdf']) && file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }
        $qOld->close();

        // Bersihkan nama file baru
        $cleanTitle  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $manual_title);
        $cleanTitle  = preg_replace('/_+/', '_', trim($cleanTitle, '_'));
        $newFileName = 'MANUAL_' . $cleanTitle . '_' . time() . '.pdf';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
            $stmt = $conn->prepare("UPDATE tb_manuals SET plant = ?, area = ?, machine_name = ?, manual_title = ?, file_pdf = ? WHERE manual_id = ?");
            $stmt->bind_param("sssssi", $plant, $area, $machine_name, $manual_title, $newFileName, $id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Update data teks tanpa ganti file
        $stmt = $conn->prepare("UPDATE tb_manuals SET plant = ?, area = ?, machine_name = ?, manual_title = ? WHERE manual_id = ?");
        $stmt->bind_param("ssssi", $plant, $area, $machine_name, $manual_title, $id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect kembali ke halaman utama dengan HTTP 303 (ramah Turbo)
    header("Location: ../user_manual.php?status=updated", true, 303);
    exit;
}

header("Location: ../user_manual.php", true, 303);
exit;