<?php
// Panggil file config kamu yang ada variabel $maintenance_mode
include 'config.php';

// Kirim jawaban ke browser dalam format JSON
header('Content-Type: application/json');
echo json_encode(['maintenance' => $maintenance_mode]);
