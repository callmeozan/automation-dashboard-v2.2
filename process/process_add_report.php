<?php
include '../config.php';

// 1. Tangkap Data Form
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
    $pic_string = "";
}
$pic = mysqli_real_escape_string($conn, $pic_string);

$machine_name = $_POST['machine_name'];
$category = $_POST['category'];
$problem = mysqli_real_escape_string($conn, $_POST['problem']);
$action = mysqli_real_escape_string($conn, $_POST['action']);
$sparepart_used = mysqli_real_escape_string($conn, $_POST['sparepart_used']);
$status = 'Solved'; 

// 2. Hitung Total Downtime (Menit) Otomatis
$total_downtime_minutes = 0;
if(!empty($time_start) && !empty($time_finish)){
    $start = strtotime($time_start);
    $end = strtotime($time_finish);
    
    if($end < $start) {
        $end += 24 * 60 * 60; // Lewat tengah malam
    }
    
    $diff = $end - $start;
    $total_downtime_minutes = floor($diff / 60);
}

// ---------------------------------------------------------
// 3. HANDLE UPLOAD EVIDENCE (MULTIPLE - MAX 5 FILES)
// ---------------------------------------------------------
$evidence_file = ""; // Default kosong jika gak ada upload
$uploaded_files = []; // Array penampung nama file sukses

// Cek apakah ada file yg dikirim & bentuknya array (karena multiple)
if(isset($_FILES['evidence']) && is_array($_FILES['evidence']['name'])) {
    
    $count = count($_FILES['evidence']['name']);
    $target_dir = "../uploads/";
    
    // Loop maksimal 5 kali atau sejumlah file yang ada
    for($i = 0; $i < $count; $i++) {
        
        $fileName  = $_FILES['evidence']['name'][$i];
        $fileTmp   = $_FILES['evidence']['tmp_name'][$i];
        $fileError = $_FILES['evidence']['error'][$i];
        
        // Pastikan tidak error dan nama file tidak kosong
        if($fileError === 0 && !empty($fileName)) {
            
            // Generate Nama Unik: EVID_WAKTU_INDEX_NAMAASLI
            // Index ($i) penting biar kalau upload file nama sama gak bentrok
            $newFileName = "EVID_" . time() . "_" . $i . "_" . basename($fileName);
            $target_file = $target_dir . $newFileName;
            
            // Pindahkan file ke folder uploads
            if (move_uploaded_file($fileTmp, $target_file)) {
                $uploaded_files[] = $newFileName; // Masukkan ke array sukses
            }
        }
    }
    
    // Gabungkan array jadi string dipisah koma
    // Contoh: "EVID_123_0_a.jpg,EVID_123_1_b.png"
    if(!empty($uploaded_files)){
        $evidence_file = implode(",", $uploaded_files);
    }
}

// ---------------------------------------------------------
// 4. SIMPAN KE DATABASE
// ---------------------------------------------------------
$query = "INSERT INTO tb_daily_reports (
    date_log, end_date, plant, shift, time_start, time_finish, total_downtime_minutes,
    pic, machine_name, category, problem, action, sparepart_used, status, evidence_file
) VALUES (
    '$date_log', '$end_date', '$plant', '$shift', '$time_start', '$time_finish', '$total_downtime_minutes',
    '$pic', '$machine_name', '$category', '$problem', '$action', '$sparepart_used', '$status', '$evidence_file'
)";

if (mysqli_query($conn, $query)) {
    header("Location: ../laporan.php?status=success");
} else {
    $error = urlencode(mysqli_error($conn));
    header("Location: ../laporan.php?status=error&msg=$error");
}
?>