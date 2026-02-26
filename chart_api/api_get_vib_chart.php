<?php
header('Content-Type: application/json');
include '../layouts/auth_and_config.php';

$machine = isset($_GET['machine']) ? mysqli_real_escape_string($conn, $_GET['machine']) : '';
$motor = isset($_GET['motor']) ? mysqli_real_escape_string($conn, $_GET['motor']) : '';

// Ambil 30 data terakhir untuk mesin dan motor yang dipilih
$query = "SELECT * FROM tb_vibration 
          WHERE mesin = '$machine' AND motor = '$motor' 
          ORDER BY tanggal DESC LIMIT 30";

$result = mysqli_query($conn, $query);

$data = [
    'labels' => [],
    'de_a' => [], 'de_h' => [], 'de_v' => [],
    'nde_a' => [], 'nde_h' => [], 'nde_v' => []
];

if ($result && mysqli_num_rows($result) > 0) {
    $temp_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $temp_data[] = $row;
    }
    // Balik urutan agar data terlama di kiri, terbaru di kanan (khas grafik)
    $temp_data = array_reverse($temp_data);

    foreach ($temp_data as $row) {
        $data['labels'][] = date('d M Y', strtotime($row['tanggal']));
        
        // Konversi ke float, jika kosong jadikan 0
        $data['de_a'][] = (float)($row['de_a'] ?? 0);
        $data['de_h'][] = (float)($row['de_h'] ?? 0);
        $data['de_v'][] = (float)($row['de_v'] ?? 0);
        
        $data['nde_a'][] = (float)($row['nde_a'] ?? 0);
        $data['nde_h'][] = (float)($row['nde_h'] ?? 0);
        $data['nde_v'][] = (float)($row['nde_v'] ?? 0);
    }
}

echo json_encode($data);
?>