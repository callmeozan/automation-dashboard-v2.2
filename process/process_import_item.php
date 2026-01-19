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

                // =========================================================
                // [MULAI] LOGIKA UNTUK ISI TABEL STOCKS (ABW1 - ABW7)
                // =========================================================
                
                // 1. Hapus stok lama barang ini di tb_stocks (biar bersih)
                mysqli_query($conn, "DELETE FROM tb_stocks WHERE part_number = '$code'");

                // 2. Daftar Posisi Kolom CSV (Nama Gudang, Index Qty, Index Lokasi)
                // ABW1 ada di data[8] dan data[9], ABW2 di data[10] dan data[11], dst.
                $list_gudang = [
                    ['ABW1', 8, 9],
                    ['ABW2', 10, 11],
                    ['ABW3', 12, 13],
                    ['ABW4', 14, 15],
                    ['ABW5', 16, 17],
                    ['ABW6', 18, 19],
                    ['ABW7', 20, 21]
                ];

                // 3. Loop untuk Insert ke tb_stocks
                foreach ($list_gudang as $gdg) {
                    $nama_gudang = $gdg[0];
                    $idx_qty     = $gdg[1];
                    $idx_loc     = $gdg[2];

                    // Ambil data (Cek apakah kolomnya ada isinya)
                    $qty_raw = isset($data[$idx_qty]) ? $data[$idx_qty] : 0;
                    $loc_raw = isset($data[$idx_loc]) ? $data[$idx_loc] : '';
                    
                    // Bersihkan angka (hapus koma)
                    $qty_clean = (int)str_replace(',', '', $qty_raw);
                    $loc_clean = mysqli_real_escape_string($conn, $loc_raw);

                    // Hanya simpan jika Stok > 0 atau Lokasi ada isinya
                    if ($qty_clean > 0 || ($loc_clean != '' && $loc_clean != '0')) {
                        $sql_stock = "INSERT INTO tb_stocks (part_number, warehouse_name, quantity, bin_location) 
                                      VALUES ('$code', '$nama_gudang', '$qty_clean', '$loc_clean')";
                        mysqli_query($conn, $sql_stock);
                    }
                }
                // =========================================================
                // [SELESAI]
                // =========================================================
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