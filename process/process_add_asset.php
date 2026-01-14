<?php
include '../config.php';

// 1. Tangkap Data dari Form
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

// 2. Gabungkan Data (Concat) untuk kolom yang kita ringkas
// Drive: Hardware - Software - Version
$drive_info = $_POST['drive_hw'] . ' - ' . $_POST['drive_sw'] . ' - ' . $_POST['drive_ver'];

// IPC: HW - OS - Apps
$ipc_info = $_POST['ipc_hw'] . ' - ' . $_POST['ipc_os'] . ' - ' . $_POST['ipc_apps'];

// Scanner: HW - SW - Ver
$scanner_info = $_POST['scan_hw'] . ' - ' . $_POST['scan_sw'] . ' - ' . $_POST['scan_ver'];


// 3. Handle Upload File
$attachment_file = "";
if(!empty($_FILES['attachment']['name'])){
    $target_dir = "../uploads/";
    $file_name = time() . "_" . basename($_FILES["attachment"]["name"]); // Tambah waktu biar unik
    $target_file = $target_dir . $file_name;
    $uploadOk = 1;
    
    // Coba Upload
    if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
        $attachment_file = $file_name;
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

// 4. Masukkan ke Database (Query Insert)
$query = "INSERT INTO tb_assets (
    plant, area, machine_name, comm_protocol,
    plc_type, plc_software, plc_version,
    hmi_type, hmi_software, hmi_version,
    drive_info, ipc_info, scanner_info, attachment_file
) VALUES (
    '$plant', '$area', '$machine_name', '$comm_protocol',
    '$plc_type', '$plc_software', '$plc_version',
    '$hmi_type', '$hmi_software', '$hmi_version',
    '$drive_info', '$ipc_info', '$scanner_info', '$attachment_file'
)";

if (mysqli_query($conn, $query)) {
    // SUKSES: Redirect dengan parameter status=success
    header("Location: ../database.php?status=success");
} else {
    // GAGAL: Redirect dengan pesan error
    $error = urlencode(mysqli_error($conn));
    header("Location: ../database.php?status=error&msg=$error");
}
?>