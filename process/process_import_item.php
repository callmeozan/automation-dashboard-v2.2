<?php
include '../config.php';
// TAMBAHKAN INI (Biar PHP gak mati kutu kalau loadingnya lama)
set_time_limit(0); 
ini_set('memory_limit', '512M'); // Jaga-jaga biar memory gak jebol

if (isset($_FILES['file_csv']['name'])) {
    
    $filename = $_FILES['file_csv']['tmp_name'];
    
    // Cek ukuran file
    if ($_FILES['file_csv']['size'] > 0) {
        
        $file = fopen($filename, "r");
        $count = 0;
        $success = 0;

        // Baca baris demi baris
        while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
            $count++;
            
            // Skip Header (Baris 1) atau jika PART_NUM kosong
            if ($count == 1 || empty($data[0])) { 
                continue; 
            }

            // --- MAPPING KOLOM CSV KE DATABASE ---
            // Sesuaikan indeks array dengan urutan kolom di CSV Bapak
            // [0] PART_NUM         -> item_code
            // [1] DESCRIPTION      -> item_name
            // [2] LONG_DESCRIPTION -> model_spec
            // [4] UOM              -> unit
            // [5] MIN              -> min_stock
            // [7] TOTAL            -> stock
            // [9] LOCABW1          -> location
            
            $code  = mysqli_real_escape_string($conn, $data[0]);
            $name  = mysqli_real_escape_string($conn, $data[1]);
            $spec  = mysqli_real_escape_string($conn, $data[2]);
            $unit  = mysqli_real_escape_string($conn, $data[4]);
            
            // Bersihkan angka dari koma (misal: 1,600 jadi 1600)
            $min   = (int)str_replace(',', '', $data[5]);
            $stock = (int)str_replace(',', '', $data[7]);
            
            $loc   = mysqli_real_escape_string($conn, $data[9]);
            
            // Brand (Opsional: Bisa diambil dari deskripsi atau dikosongkan)
            $brand = ""; 

            // Query Insert (ON DUPLICATE KEY UPDATE biar kalau data double, stoknya diupdate)
            $query = "INSERT INTO tb_master_items 
                      (item_code, item_name, brand, model_spec, stock, min_stock, unit, location) 
                      VALUES 
                      ('$code', '$name', '$brand', '$spec', '$stock', '$min', '$unit', '$loc')
                      ON DUPLICATE KEY UPDATE 
                      item_name=VALUES(item_name), stock=VALUES(stock), location=VALUES(location)";
            
            if (mysqli_query($conn, $query)) {
                $success++;
            }
        }
        
        fclose($file);
        header("Location: ../master_items.php?status=success&msg=Berhasil import $success data.");
    } else {
        header("Location: ../master_items.php?status=error&msg=File kosong.");
    }
} else {
    header("Location: ../master_items.php");
}
?>