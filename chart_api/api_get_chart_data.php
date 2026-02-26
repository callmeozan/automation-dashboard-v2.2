<?php
// 1. Hubungkan ke database
include '../layouts/auth_and_config.php';

// 2. Ambil parameter dari URL (dikirim oleh JavaScript nanti)
$mesin = isset($_GET['machine']) ? mysqli_real_escape_string($conn, $_GET['machine']) : 'MCG-01';
$motor = isset($_GET['motor']) ? mysqli_real_escape_string($conn, $_GET['motor']) : 'Mixer 01';

// 3. Ambil 30 data TERAKHIR, lalu urutkan ASC (lama ke baru) agar grafik berjalan ke kanan
$sql = "SELECT tanggal, de, body, nde, temp_limit 
        FROM (
            SELECT * FROM tb_temperature 
            WHERE mesin = '$mesin' AND motor = '$motor' 
            ORDER BY tanggal DESC LIMIT 30
        ) as sub 
        ORDER BY tanggal ASC";

$result = mysqli_query($conn, $sql);

// 4. Siapkan wadah kosong (Array)
$data = [
    'labels' => [],
    'de' => [],
    'nde' => [],
    'body' => [],
    'limit' => [] // Garis Limit yang warna pink/ungu tadi
];

// 5. Masukkan data dari database ke dalam wadah
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Ubah format tanggal (Contoh: '2025-06-25' menjadi '25 Jun')
        $data['labels'][] = date('d M Y', strtotime($row['tanggal']));
        $data['de'][] = (float)$row['de'];
        $data['nde'][] = (float)$row['nde'];
        $data['body'][] = (float)$row['body'];
        $data['limit'][] = isset($row['temp_limit']) ? (float)$row['temp_limit'] : 85; 
    }
}

mysqli_close($conn);

// 6. Hidangkan sebagai JSON
header('Content-Type: application/json');
echo json_encode($data);
?>