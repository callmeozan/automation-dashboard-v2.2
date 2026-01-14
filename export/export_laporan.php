<?php
include '../config.php';

// 1. Header agar browser download sebagai Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Harian_Automation_" . date('Y-m-d_H-i') . ".xls");

// 2. LOGIKA FILTER (Sama persis dengan yang di tampilan, tapi versi PHP)
$whereClause = "WHERE 1=1"; // Default (Ambil semua)

// A. Filter Search Teks
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['search']);
    $whereClause .= " AND (machine_name LIKE '%$keyword%' 
                        OR problem LIKE '%$keyword%' 
                        OR action LIKE '%$keyword%' 
                        OR pic LIKE '%$keyword%')";
}

// B. Filter Tanggal Start
if(isset($_GET['start']) && !empty($_GET['start'])) {
    $start = mysqli_real_escape_string($conn, $_GET['start']);
    $whereClause .= " AND date_log >= '$start'";
}

// C. Filter Tanggal End
if(isset($_GET['end']) && !empty($_GET['end'])) {
    $end = mysqli_real_escape_string($conn, $_GET['end']);
    $whereClause .= " AND date_log <= '$end'";
}

// 3. Ambil Data (Tanpa Limit, karena mau download semua)
$query = mysqli_query($conn, "SELECT * FROM tb_daily_reports $whereClause ORDER BY date_log DESC, time_start DESC");
?>

<table border="1">
    <thead>
        <tr style="background-color: #059669; color: white;">
            <th>No</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Shift</th>
            <th>Start</th>
            <th>Finish</th>
            <th>Total Downtime</th>
            <th>Plant</th>
            <th>Machine / Area</th>
            <th>Category</th>
            <th>Problem Description</th>
            <th>Action Taken</th>
            <th>Sparepart / Note</th>
            <th>PIC</th>
            <th>Status</th>
            <th>Evidence</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        while($row = mysqli_fetch_assoc($query)): 
            // Hitung Jam Menit
            $h = floor($row['total_downtime_minutes']/60); 
            $m = $row['total_downtime_minutes']%60;
            $downtime = $h." Jam ".$m." Menit";
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['date_log']; ?></td>
            <td><?php echo $row['end_date']; ?></td>
            <td><?php echo $row['shift']; ?></td>
            <td><?php echo $row['time_start']; ?></td>
            <td><?php echo $row['time_finish']; ?></td>
            <td><?php echo $downtime; ?></td>
            <td><?php echo $row['plant']; ?></td>
            <td><?php echo $row['machine_name']; ?></td>
            <td><?php echo $row['category']; ?></td>
            <td><?php echo $row['problem']; ?></td>
            <td><?php echo $row['action']; ?></td>
            <td><?php echo $row['sparepart_used']; ?></td>
            <td><?php echo $row['pic']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td><?php echo $row['evidence_file']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>