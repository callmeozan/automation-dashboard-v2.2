<?php
include '../config.php';

// 1. Header agar file didownload sebagai Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Data_Asset_Automation_" . date('Y-m-d') . ".xls");

// 2. Logika Filter (Sama seperti di halaman utama)
$searchQuery = "";
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['search']);
    $searchQuery = " WHERE machine_name LIKE '%$keyword%' 
                     OR plant LIKE '%$keyword%' 
                     OR area LIKE '%$keyword%' 
                     OR plc_type LIKE '%$keyword%' ";
}

// 3. Ambil Data (Tanpa Limit)
$query = mysqli_query($conn, "SELECT * FROM tb_assets $searchQuery ORDER BY plant ASC, machine_name ASC");
?>

<table border="1">
    <thead>
        <tr style="background-color: #4CAF50; color: white;">
            <th>No</th>
            <th>Plant</th>
            <th>Area</th>
            <th>Machine Name</th>
            <th>Communication Protocol</th>
            <th>PLC Hardware</th>
            <th>PLC Software</th>
            <th>PLC Version</th>
            <th>HMI Hardware</th>
            <th>HMI Software</th>
            <th>HMI Version</th>
            <th>Drive / Servo</th>
            <th>IPC / Computer</th>
            <th>Scanner / Barcode</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        while($row = mysqli_fetch_assoc($query)): 
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['plant']; ?></td>
            <td><?php echo $row['area']; ?></td>
            <td><?php echo $row['machine_name']; ?></td>
            <td><?php echo $row['comm_protocol']; ?></td>
            
            <td><?php echo $row['plc_type']; ?></td>
            <td><?php echo $row['plc_software']; ?></td>
            <td><?php echo $row['plc_version']; ?></td>

            <td><?php echo $row['hmi_type']; ?></td>
            <td><?php echo $row['hmi_software']; ?></td>
            <td><?php echo $row['hmi_version']; ?></td>

            <td><?php echo $row['drive_info']; ?></td>
            <td><?php echo $row['ipc_info']; ?></td>
            <td><?php echo $row['scanner_info']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>