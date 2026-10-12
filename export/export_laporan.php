<?php
include '../config.php';

// 1. Header untuk CSV Murni
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Laporan_Harian_Automation_' . date('Y-m-d_H-i') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

// 2. Tambahkan UTF-8 BOM agar Excel membaca karakter khusus & format dengan benar
echo "\xEF\xBB\xBF";

// 3. Buka output stream PHP
$output = fopen('php://output', 'w');

// --- A. BARIS JUDUL LAPORAN (Membuat bagian atas lebih profesional) ---
fputcsv($output, ['DAILY REPORTS AUTOMATION TEAM']);
fputcsv($output, ['Export Date:', date('d-m-Y H:i') . ' WIB']);
fputcsv($output, []); // Baris kosong pembatas

// --- B. HEADER TABEL (Menggunakan UPPERCASE) ---
fputcsv($output, [
    'NO',
    'START DATE',
    'END DATE',
    'SHIFT',
    'START',
    'FINISH',
    'TOTAL DOWNTIME',
    'PLANT',
    'MACHINE / AREA',
    'CATEGORY',
    'PROBLEM DESCRIPTION',
    'ACTION TAKEN',
    'SPAREPART / NOTE',
    'PIC',
    'STATUS',
    'EVIDENCE'
]);

// 4. LOGIKA FILTER
$whereClause = "WHERE 1=1";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = mysqli_real_escape_string($conn, trim($_GET['search']));
    $whereClause .= " AND (machine_name LIKE '%$keyword%' 
                        OR problem LIKE '%$keyword%' 
                        OR action LIKE '%$keyword%' 
                        OR pic LIKE '%$keyword%'
                        OR category LIKE '%$keyword%')";
}

if (isset($_GET['start']) && !empty($_GET['start'])) {
    $start_formatted = date('Y-m-d', strtotime($_GET['start']));
    $start = mysqli_real_escape_string($conn, $start_formatted);
    $whereClause .= " AND date_log >= '$start'";
}

if (isset($_GET['end']) && !empty($_GET['end'])) {
    $end_formatted = date('Y-m-d', strtotime($_GET['end']));
    $end = mysqli_real_escape_string($conn, $end_formatted);
    $whereClause .= " AND date_log <= '$end'";
}

// 5. Ambil Data dan Tulis Baris per Baris
$query = mysqli_query($conn, "SELECT * FROM tb_daily_reports $whereClause ORDER BY date_log DESC, time_start DESC");

$no = 1;
while ($row = mysqli_fetch_assoc($query)) {
    $h = floor($row['total_downtime_minutes'] / 60);
    $m = $row['total_downtime_minutes'] % 60;
    $downtime = $h . " Jam " . $m . " Menit";

    fputcsv($output, [
        $no++,
        $row['date_log'],
        $row['end_date'],
        $row['shift'],
        $row['time_start'],
        $row['time_finish'],
        $downtime,
        $row['plant'],
        $row['machine_name'],
        $row['category'],
        $row['problem'],
        $row['action'],
        $row['sparepart_used'],
        $row['pic'],
        $row['status'],
        $row['evidence_file']
    ]);
}

fclose($output);
exit();
?>