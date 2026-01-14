<?php
include '../config.php';

// Header supaya browser download file Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Lembur_" . date('Y-m-d') . ".xls");

session_start();
$my_role = $_SESSION['role'];
$my_id   = $_SESSION['user_id'];

// LOGIKA QUERY:
// 1. Jika Admin/Section: Download SEMUA data (Bisa difilter per bulan kalau mau dikembangkan nanti)
// 2. Jika User Biasa: Download data miliknya sendiri saja
if ($my_role == 'admin' || $my_role == 'section') {
    $query = mysqli_query($conn, "SELECT a.*, b.full_name 
                                  FROM tb_overtime a 
                                  JOIN tb_users b ON a.user_id = b.user_id 
                                  ORDER BY a.date_ot DESC");
} else {
    $query = mysqli_query($conn, "SELECT a.*, b.full_name 
                                  FROM tb_overtime a 
                                  JOIN tb_users b ON a.user_id = b.user_id 
                                  WHERE a.user_id='$my_id' 
                                  ORDER BY a.date_ot DESC");
}
?>

<h3 style="text-align: center;">REKAPITULASI OVERTIME (LEMBUR)</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color: #f0f0f0;">
            <th>No</th>
            <th>Nama Karyawan</th>
            <th>Tanggal</th>
            <th>No SPK</th>
            <th>Jam Mulai</th>
            <th>Jam Selesai</th>
            <th>Durasi (Jam)</th>
            <th>Aktivitas</th>
            <th>Status</th>
            <th>Approved By</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        $total_jam = 0;
        while ($row = mysqli_fetch_assoc($query)) {
            // Hanya hitung total jika status APPROVED
            if($row['status'] == 'Approved'){
                $total_jam += $row['duration'];
            }
            
            // Warna Status
            $bg_color = "#ffffff";
            if($row['status'] == 'Pending') $bg_color = "#fff3cd"; // Kuning
            if($row['status'] == 'Rejected') $bg_color = "#f8d7da"; // Merah
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['full_name']; ?></td>
            <td><?php echo $row['date_ot']; ?></td>
            <td><?php echo $row['spk_number']; ?></td>
            <td><?php echo date('H:i', strtotime($row['time_start'])); ?></td>
            <td><?php echo date('H:i', strtotime($row['time_end'])); ?></td>
            <td style="text-align: center; font-weight: bold;"><?php echo $row['duration']; ?></td>
            <td><?php echo $row['activity']; ?></td>
            <td style="background-color: <?php echo $bg_color; ?>;"><?php echo $row['status']; ?></td>
            <td><?php echo $row['approved_by']; ?></td>
        </tr>
        <?php } ?>
        
        <tr style="background-color: #d1e7dd;">
            <td colspan="6" style="text-align: right; font-weight: bold;">TOTAL JAM (APPROVED ONLY):</td>
            <td style="text-align: center; font-weight: bold; font-size: 14px;"><?php echo $total_jam; ?></td>
            <td colspan="3"></td>
        </tr>
    </tbody>
</table>