<?php
include '../config.php';

// 1. Tangkap ID Aset (PENTING)
$id = $_POST['asset_id'];

// 2. Tangkap Data Identitas & Teknis
$plant = $_POST['plant'];
$area = $_POST['area'];
$machine_name = $_POST['machine_name'];
$comm_protocol = $_POST['comm_protocol'];

$plc_type = $_POST['plc_type'];
$plc_software = $_POST['plc_software'];
$plc_version = $_POST['plc_version'];

$hmi_type = $_POST['hmi_type'];
$hmi_software = $_POST['hmi_software'];
$hmi_version = $_POST['hmi_version'];

// Data Textarea (Drive, IPC, Scanner) dibiarkan apa adanya (string panjang)
$drive_info = $_POST['drive_hw'] . ' - ' . $_POST['drive_sw'] . ' - ' . $_POST['drive_ver'];
$ipc_info   = $_POST['ipc_hw']   . ' - ' . $_POST['ipc_os']   . ' - ' . $_POST['ipc_apps'];
$scanner_info = $_POST['scan_hw'] . ' - ' . $_POST['scan_sw'] . ' - ' . $_POST['scan_ver'];

// 3. LOGIKA UPLOAD FILE (Hanya update jika ada file baru)
$file_query = ""; // Default: tidak update kolom file

if(!empty($_FILES['attachment']['name'])){
    $target_dir = "../uploads/";
    $file_name = time() . "_REV_" . basename($_FILES["attachment"]["name"]); // Tambah _REV_ biar tau ini revisi
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        // Jika upload sukses, masukkan ke query update
        $file_query = ", attachment_file = '$file_name'";
        
        // (Opsional) Hapus file lama di sini kalau mau hemat storage
    }
}

// 4. Query Update
$query = "UPDATE tb_assets SET 
            plant = '$plant',
            area = '$area',
            machine_name = '$machine_name',
            comm_protocol = '$comm_protocol',
            plc_type = '$plc_type',
            plc_software = '$plc_software',
            plc_version = '$plc_version',
            hmi_type = '$hmi_type',
            hmi_software = '$hmi_software',
            hmi_version = '$hmi_version',
            drive_info = '$drive_info',
            ipc_info = '$ipc_info',
            scanner_info = '$scanner_info'
            $file_query
          WHERE asset_id = '$id'";

if (mysqli_query($conn, $query)) {
    header("Location: ../database.php?status=updated");
} else {
    $error = urlencode(mysqli_error($conn));
    header("Location: ../database.php?status=error&msg=$error");
}
?>