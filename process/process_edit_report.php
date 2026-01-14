<?php
include '../config.php';

// 1. TANGKAP DATA INPUT
$report_id = $_POST['report_id'];
$date_log = $_POST['date_log'];
$end_date = $_POST['end_date'];
$plant = $_POST['plant'];
$shift = $_POST['shift'];
$time_start = $_POST['time_start'];
$time_finish = $_POST['time_finish'];

// Handle PIC Multi-Select (Array ke String)
if (isset($_POST['pic'])) {
    if (is_array($_POST['pic'])) {
        $pic_string = implode(", ", $_POST['pic']); 
    } else {
        $pic_string = $_POST['pic'];
    }
} else {
    // Kalau tidak diedit/kosong, kita perlu ambil data lama? 
    // Atau biarkan kosong. Biasanya dropdown edit terisi value lama.
    $pic_string = ""; 
}
$pic = mysqli_real_escape_string($conn, $pic_string);

$machine_name = $_POST['machine_name'];
$category = $_POST['category'];
$problem = mysqli_real_escape_string($conn, $_POST['problem']);
$action = mysqli_real_escape_string($conn, $_POST['action']);
$sparepart_used = mysqli_real_escape_string($conn, $_POST['sparepart_used']);
$status = $_POST['status'];

// 2. HITUNG DOWNTIME OTOMATIS
$total_downtime_minutes = 0;
if(!empty($time_start) && !empty($time_finish)){
    $start = strtotime($time_start);
    $end = strtotime($time_finish);
    if($end < $start) $end += 24 * 60 * 60; // Handle lewat tengah malam
    $diff = $end - $start;
    $total_downtime_minutes = floor($diff / 60);
}

// ---------------------------------------------------------
// 3. HANDLE UPLOAD EVIDENCE (MULTIPLE - MAX 5 FILES)
// ---------------------------------------------------------
$evidence_sql_part = ""; // Default: tidak update kolom evidence

// Cek apakah user mengupload file BARU?
// (Cek index ke-0 tidak kosong)
if(isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
    
    $uploaded_files = [];
    $count = count($_FILES['evidence']['name']);
    $target_dir = "../uploads/";
    
    for($i = 0; $i < $count; $i++) {
        $fileName  = $_FILES['evidence']['name'][$i];
        $fileTmp   = $_FILES['evidence']['tmp_name'][$i];
        $fileError = $_FILES['evidence']['error'][$i];
        
        // Validasi
        if($fileError === 0 && !empty($fileName)) {
            // Generate Nama Unik
            $newFileName = "EVID_" . time() . "_" . $i . "_" . basename($fileName);
            $target_file = $target_dir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $target_file)) {
                $uploaded_files[] = $newFileName;
            }
        }
    }
    
    // Jika ada file berhasil diupload, kita TIMPA data lama
    if(!empty($uploaded_files)){
        $evidence_string = implode(",", $uploaded_files);
        $evidence_sql_part = ", evidence_file = '$evidence_string'";
    }
}

// ---------------------------------------------------------
// 4. UPDATE DATABASE
// ---------------------------------------------------------
$query = "UPDATE tb_daily_reports SET 
    date_log = '$date_log',
    end_date = '$end_date',
    plant = '$plant',
    shift = '$shift',
    time_start = '$time_start',
    time_finish = '$time_finish',
    total_downtime_minutes = '$total_downtime_minutes',
    pic = '$pic',
    machine_name = '$machine_name',
    category = '$category',
    problem = '$problem',
    action = '$action',
    sparepart_used = '$sparepart_used',
    status = '$status'
    $evidence_sql_part  -- <-- Bagian ini hanya nempel kalau ada upload baru
WHERE report_id = '$report_id'";

if (mysqli_query($conn, $query)) {
    header("Location: ../laporan.php?status=updated");
} else {
    $error = urlencode(mysqli_error($conn));
    header("Location: ../laporan.php?status=error&msg=$error");
}
?>