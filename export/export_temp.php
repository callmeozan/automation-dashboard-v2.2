<?php
session_start();
// Sesuaikan dengan letak file koneksi database Bapak
require_once '../config.php'; // Ganti 'koneksi.php' kalau nama file koneksi Bapak beda

// 1. Tangkap kata kunci pencarian (kalau ada)
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : '';
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(mesin LIKE '%$search%' OR motor LIKE '%$search%')";
}

if (!empty($start_date) && !empty($end_date)) {
    $conditions[] = "tanggal BETWEEN '$start_date' AND '$end_date'";
}

$whereClause = "";
if (count($conditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// 2. Setting Header agar browser otomatis mendownload file Excel (.xls)
$nama_file = "Data_Temperature_Motor_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$nama_file\"");
header("Pragma: no-cache");
header("Expires: 0");

// 3. Tarik data dari database
$query = mysqli_query($conn, "SELECT * FROM tb_temperature $whereClause ORDER BY tanggal DESC");
?>

<table border="1">
    <thead>
        <tr>
            <th style="background-color: #0f172a; color: #ffffff;">No</th>
            <th style="background-color: #0f172a; color: #ffffff;">Tanggal</th>
            <th style="background-color: #0f172a; color: #ffffff;">Machine</th>
            <th style="background-color: #0f172a; color: #ffffff;">Motor</th>
            <th style="background-color: #0f172a; color: #ffffff;">DE (°C)</th>
            <th style="background-color: #0f172a; color: #ffffff;">Body (°C)</th>
            <th style="background-color: #0f172a; color: #ffffff;">NDE (°C)</th>
            <th style="background-color: #0f172a; color: #ffffff;">Note</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (mysqli_num_rows($query) > 0) {
            $no = 1;
            while ($row = mysqli_fetch_assoc($query)) {
                $tanggal_format = date('d M Y', strtotime($row['tanggal']));
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . $tanggal_format . "</td>";
                echo "<td>" . $row['mesin'] . "</td>";
                echo "<td>" . $row['motor'] . "</td>";
                echo "<td>" . $row['de'] . "</td>";
                echo "<td>" . $row['body'] . "</td>";
                echo "<td>" . $row['nde'] . "</td>";
                echo "<td>" . (!empty($row['note']) ? $row['note'] : '-') . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='8'>Tidak ada data</td></tr>";
        }
        ?>
    </tbody>
</table>