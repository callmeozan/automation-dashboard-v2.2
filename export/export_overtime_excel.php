<?php
include '../config.php';

// Header supaya browser download file Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Lembur_" . date('Y-m-d_H-i') . ".xls");

session_start();
$my_role = $_SESSION['role'];
$my_id   = $_SESSION['user_id'];

// --- 1. MEMBANGUN QUERY DENGAN FILTER ---

// Array untuk menampung syarat WHERE
$conditions = [];

// A. Filter Security (User Biasa cuma bisa lihat punya sendiri)
if ($my_role != 'admin' && $my_role != 'section') {
    $conditions[] = "a.user_id='$my_id'";
}

// B. Filter Search (Tangkap dari URL ?search=...)
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['search']);
    // Cari di Nama, SPK, atau Aktivitas
    $conditions[] = "(b.full_name LIKE '%$keyword%' OR a.spk_number LIKE '%$keyword%' OR a.activity LIKE '%$keyword%')";
}

// Rakit Query Utama
$sql = "SELECT a.*, b.full_name 
        FROM tb_overtime a 
        JOIN tb_users b ON a.user_id = b.user_id";

// Jika ada syarat, tambahkan WHERE
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Urutkan
$sql .= " ORDER BY a.date_ot DESC";

$query = mysqli_query($conn, $sql);
?>

<h3 style="text-align: center;">REKAPITULASI OVERTIME (LEMBUR)</h3>

<?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
    <p style="text-align: center;">Filter Pencarian: <strong>"<?php echo htmlspecialchars($_GET['search']); ?>"</strong></p>
<?php endif; ?>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color: #059669; color: white;">
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
        
        if (mysqli_num_rows($query) > 0) {
            while ($row = mysqli_fetch_assoc($query)) {
                // Hitung total hanya jika Approved
                if($row['status'] == 'Approved'){
                    $total_jam += $row['duration'];
                }
                
                // Warna Status untuk Excel
                $bg_color = "#ffffff";
                if($row['status'] == 'Pending') $bg_color = "#fff3cd"; // Kuning muda
                if($row['status'] == 'Rejected') $bg_color = "#f8d7da"; // Merah muda
                if($row['status'] == 'Approved') $bg_color = "#d1e7dd"; // Hijau muda
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $row['full_name']; ?></td>
                <td><?php echo $row['date_ot']; ?></td>
                <td style="mso-number-format:'@';"><?php echo $row['spk_number']; ?></td> <td><?php echo date('H:i', strtotime($row['time_start'])); ?></td>
                <td><?php echo date('H:i', strtotime($row['time_end'])); ?></td>
                <td style="text-align: center; font-weight: bold;"><?php echo $row['duration']; ?></td>
                <td><?php echo $row['activity']; ?></td>
                <td style="background-color: <?php echo $bg_color; ?>;"><?php echo $row['status']; ?></td>
                <td><?php echo $row['approved_by']; ?></td>
            </tr>
            <?php } 
        } else {
            echo "<tr><td colspan='10' style='text-align:center; padding: 20px;'>Tidak ada data ditemukan.</td></tr>";
        }
        ?>
        
        <tr>
            <td colspan="6" style="text-align: right; font-weight: bold; background-color: #f0f0f0;">TOTAL JAM (APPROVED):</td>
            <td style="text-align: center; font-weight: bold; font-size: 14px; background-color: #f0f0f0;"><?php echo $total_jam; ?></td>
            <td colspan="3" style="background-color: #f0f0f0;"></td>
        </tr>
    </tbody>
</table>