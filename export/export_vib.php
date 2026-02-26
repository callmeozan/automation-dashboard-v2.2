<?php
// Jangan ada spasi atau enter sebelum tag <?php ini ya Pak, biar file excel tidak corrupt
session_start();
require_once '../config.php'; // Sesuaikan lokasi file koneksi

// 1. SET HEADER AGAR BROWSER MEN-DOWNLOAD FILE SEBAGAI EXCEL
$filename = "Report_Vibration_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// 2. TANGKAP FILTER PENCARIAN & TANGGAL (Persis seperti di halaman utama)
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

// 3. TARIK DATA DARI DATABASE (Tanpa LIMIT agar semua data terekspor)
$query = mysqli_query($conn, "SELECT * FROM tb_vibration $whereClause ORDER BY tanggal DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Export Data Vibration</title>
    <style>
        /* CSS dasar agar tabel di Excel ada garisnya */
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid black; }
        th { background-color: #4ade80; color: black; font-weight: bold; padding: 5px; text-align: center; }
        td { padding: 5px; text-align: center; }
        .text-left { text-align: left; }
    </style>
</head>
<body>
    <center>
        <h2>Laporan Data Condition Monitoring - Vibration</h2>
        <?php if(!empty($start_date) && !empty($end_date)): ?>
            <p><strong>Periode:</strong> <?php echo date('d M Y', strtotime($start_date)); ?> s/d <?php echo date('d M Y', strtotime($end_date)); ?></p>
        <?php endif; ?>
        <?php if(!empty($search)): ?>
            <p><strong>Keyword Pencarian:</strong> <?php echo htmlspecialchars($search); ?></p>
        <?php endif; ?>
    </center>
    <br>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Machine</th>
                <th>Motor</th>
                <th style="background-color: #22d3ee;">DE-Axial</th>
                <th style="background-color: #22d3ee;">DE-Horizontal</th>
                <th style="background-color: #22d3ee;">DE-Vertical</th>
                <th style="background-color: #34d399;">NDE-Axial</th>
                <th style="background-color: #34d399;">NDE-Horizontal</th>
                <th style="background-color: #34d399;">NDE-Vertical</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (mysqli_num_rows($query) > 0) {
                $no = 1;
                while ($row = mysqli_fetch_assoc($query)) {
                    // Cek jika null/kosong jadikan strip (-)
                    $de_a = isset($row['de_a']) ? $row['de_a'] : '-';
                    $de_h = isset($row['de_h']) ? $row['de_h'] : '-';
                    $de_v = isset($row['de_v']) ? $row['de_v'] : '-';
                    $nde_a = isset($row['nde_a']) ? $row['nde_a'] : '-';
                    $nde_h = isset($row['nde_h']) ? $row['nde_h'] : '-';
                    $nde_v = isset($row['nde_v']) ? $row['nde_v'] : '-';
                    $note = !empty($row['note']) ? $row['note'] : '-';

                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . date('d M Y', strtotime($row['tanggal'])) . "</td>";
                    echo "<td>" . $row['mesin'] . "</td>";
                    echo "<td>" . $row['motor'] . "</td>";
                    
                    // Supaya Excel tidak salah baca angka desimal (misal 0.50 jadi 0,50 atau teks)
                    echo "<td style=\"mso-number-format:'0\.00';\">" . $de_a . "</td>";
                    echo "<td style=\"mso-number-format:'0\.00';\">" . $de_h . "</td>";
                    echo "<td style=\"mso-number-format:'0\.00';\">" . $de_v . "</td>";
                    echo "<td style=\"mso-number-format:'0\.00';\">" . $nde_a . "</td>";
                    echo "<td style=\"mso-number-format:'0\.00';\">" . $nde_h . "</td>";
                    echo "<td style=\"mso-number-format:'0\.00';\">" . $nde_v . "</td>";
                    
                    echo "<td class='text-left'>" . $note . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='11'>Tidak ada data ditemukan pada periode/pencarian ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>