<?php
// 1. Panggil Satpam & Koneksi Global
include 'layouts/auth_and_config.php';

// --- PERBAIKAN ERROR ---
// Kita ambil lagi Short Name user yang login untuk validasi tombol Edit/Hapus di bawah
$id_login = $_SESSION['user_id'];
$qUserLogin = mysqli_query($conn, "SELECT short_name FROM tb_users WHERE user_id='$id_login'");
$dUserLogin = mysqli_fetch_assoc($qUserLogin);
// Jika tidak ketemu (misal admin), set string kosong biar gak error
$my_name = isset($dUserLogin['short_name']) ? $dUserLogin['short_name'] : ''; 
// -----------------------

// 2. LOGIKA FILTER TAHUN
if (isset($_GET['tahun'])) {
    $tahun_pilihan = $_GET['tahun'];
} else {
    $tahun_pilihan = date('Y');
}

// 3. HITUNG TOTAL REPORT (Untuk Header)
$qCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_daily_reports");
$totalReports = mysqli_fetch_assoc($qCount)['total'];

// 4. KONFIGURASI LAYOUT
$pageTitle = "Daily Activity Report";

// [SLOT HEADER] Teks Total Report
$extraMenu = '
    <div class="flex items-center gap-4">
        <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4">
            Total Report: <span class="text-white font-bold">' . number_format($totalReports) . '</span>
        </div>
    </div>';

// [SLOT HEAD] CSS/JS Tambahan
$extraHead = '
    <link href="assets/vendor/tom-select.css" rel="stylesheet">
    <script src="assets/vendor/tom-select.complete.min.js"></script>
    <script src="assets/vendor/apexcharts.js"></script>
    <style>
        .ts-control { background-color: #0f172a !important; border: 1px solid #334155 !important; color: #fff !important; border-radius: 0.5rem; }
        .ts-dropdown { background-color: #1e293b !important; border: 1px solid #334155 !important; color: #fff !important; }
        .ts-dropdown .active { background-color: #334155 !important; color: #fff !important; }
        .ts-control .item { background-color: #059669 !important; color: #fff !important; border-radius: 4px; }
        .ts-wrapper.multi .ts-control>div { background-color: #059669 !important; color: white !important; }
        
        input[type="date"], input[type="time"] { color-scheme: dark; cursor: pointer; }
        body.light-mode input[type="date"], body.light-mode input[type="time"] { color-scheme: light; }
        
        @media print {
            #sidebar, header, .btn-action, button, form, #paginationContainer, .no-print { display: none !important; }
            body, #main-content { background: white !important; color: black !important; height: auto !important; overflow: visible !important; }
            th, td { border: 1px solid black !important; color: black !important; }
        }
    </style>
';
?>

<head>
    <meta name="turbo-cache-control" content="no-preview">
</head>

<!DOCTYPE html>
<html lang="id">

<!-- HEAD ADA DISINI -->
<?php include 'layouts/head.php'; ?>

<!-- <head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#03142c">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - Automation Portal</title>

    <link rel="icon" href="image/gajah_tunggal.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/layouts/sidebar.css">
    <link rel="stylesheet" href="assets/css/layouts/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/card.css">
    <link rel="stylesheet" href="assets/css/components/modal.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="assets/vendor/tom-select.css" rel="stylesheet">
    <script src="assets/vendor/tailwind.js"></script>
    <script src="assets/vendor/sweetalert2.all.min.js"></script>
    <script src="assets/vendor/apexcharts.js"></script>
    <script src="assets/vendor/tom-select.complete.min.js"></script>

    <style>
        /* Custom Style Dark Mode untuk Tom Select */
        .ts-control {
            background-color: #0f172a !important;
            border: 1px solid #334155 !important;
            color: #fff !important;
            border-radius: 0.5rem;
        }

        .ts-dropdown {
            background-color: #1e293b !important;
            border: 1px solid #334155 !important;
            color: #fff !important;
        }

        .ts-dropdown .active {
            background-color: #334155 !important;
            color: #fff !important;
        }

        .ts-control .item {
            background-color: #059669 !important;
            color: #fff !important;
            border-radius: 4px;
        }

        .ts-wrapper.multi .ts-control>div {
            background-color: #059669 !important;
            color: white !important;
        }
    </style>

    <style>
        /* 1. Supaya kursor jadi telunjuk saat hover di icon Kalender & Jam */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        /* 2. MODE GELAP */
        input[type="date"],
        input[type="time"] {
            color-scheme: dark;
        }

        /* 3. MODE TERANG (Saat ada class 'light-mode') */
        body.light-mode input[type="date"],
        body.light-mode input[type="time"] {
            color-scheme: light;
        }
    </style>
</head> -->

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR ADA DISINI -->
        <?php include 'layouts/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">
            
            <!-- HEADER ADA DISINI -->
            <?php include 'layouts/header.php'; ?>

            <div class="p-8 space-y-6 fade-in">
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
                    <div class="flex flex-col md:flex-row gap-4 justify-between items-end">
                        <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-4 w-full mb-6">

                            <!-- FILTER BY DATE ADA DISINI -->
                                <div class="flex flex-col md:flex-row gap-4 items-end w-full xl:w-auto">
                                    <div class="w-full md:w-64">
                                        <label class="block text-xs text-slate-400 mb-1">Search Daily Report</label>
                                        <div class="relative w-full">
                                            <i class="fas fa-search absolute left-3 top-3 text-slate-500 text-sm"></i>
                                            <input id="searchInput" type="text" placeholder="Search Daily Activity..." class="w-full bg-slate-800 border border-slate-700 text-white pl-9 pr-4 py-2.5 rounded-lg focus:border-emerald-500 focus:outline-none transition text-sm" autocomplete="off">
                                        </div>
                                    </div>

                                    <!-- <div class="flex items-end gap-2 w-full md:w-auto">
                                        <div class="flex-1 md:flex-none">
                                            <label class="block text-xs text-slate-400 mb-1">From</label>
                                            <div class="flex items-center bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 w-full">
                                                <i class="far fa-calendar-alt text-slate-400 mr-2 hidden md:block"></i>
                                                <input type="date" id="startDate" class="bg-transparent text-white text-sm focus:outline-none placeholder-slate-500 w-full md:w-32 appearance-none">
                                            </div>
                                        </div>

                                        <span class="text-slate-500 mb-3">-</span>

                                        <div class="flex-1 md:flex-none">
                                            <label class="block text-xs text-slate-400 mb-1">To</label>
                                            <div class="flex items-center bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 w-full">
                                                <i class="far fa-calendar-alt text-slate-400 mr-2 hidden md:block"></i>
                                                <input type="date" id="endDate" class="bg-transparent text-white text-sm focus:outline-none placeholder-slate-500 w-full md:w-32 appearance-none">
                                            </div>
                                        </div>
                                    </div> -->
                                    <div class="flex items-end gap-2 w-full md:w-auto mt-4 xl:mt-0">
    
                                        <div class="flex-1 md:flex-none">
                                            <label class="block text-xs text-slate-400 mb-1">From</label>
                                            <div class="flex items-center bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 w-full">
                                                <input type="date" id="startDate" class="bg-transparent text-white text-sm focus:outline-none placeholder-slate-500 w-full md:w-32 cursor-pointer [&::-webkit-calendar-picker-indicator]:invert">
                                            </div>
                                        </div>

                                        <span class="text-slate-500 mb-3 self-center">-</span>

                                        <div class="flex-1 md:flex-none">
                                            <label class="block text-xs text-slate-400 mb-1">To</label>
                                            <div class="flex items-center bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 w-full">
                                                <input type="date" id="endDate" class="bg-transparent text-white text-sm focus:outline-none placeholder-slate-500 w-full md:w-32 cursor-pointer [&::-webkit-calendar-picker-indicator]:invert">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                
                                <div class="flex flex-col md:flex-row gap-2 w-full xl:w-auto mt-4 xl:mt-0">
                                        <form method="GET" action="" class="w-full md:w-32">
                                        <label class="block text-xs text-slate-400 mb-1">Select Year</label>
                                            <select name="tahun" onchange="this.form.submit()" class="bg-slate-900 border border-slate-600 text-white text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5">
                                            <?php
                                            $thn_skrg = date('Y');
                                            // Loop 5 tahun ke belakang
                                            for ($x = $thn_skrg; $x >= $thn_skrg - 5; $x--) {
                                            $selected = ($x == $tahun_pilihan) ? 'selected' : '';
                                            echo "<option value='$x' $selected>$x</option>";
                                            }
                                            ?>
                                            </select>
                                        </form>
                                </div>

                                <!-- INPUT DAN EXPORT EXCEL ADA DISINI -->
                                <div class="flex gap-2 w-full xl:w-auto justify-start xl:justify-end mt-2 xl:mt-0">
                                    <button onclick="downloadExcel()" class="bg-slate-800 hover:bg-green-700 text-slate-300 px-4 py-2.5 rounded-lg border border-slate-700 text-sm transition flex items-center justify-center gap-2 flex-1 xl:flex-none">
                                        <i class="fas fa-file-excel"></i> <span>Export Excel</span>
                                    </button>

                                    <button class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 flex-1 xl:flex-none btn-input-laporan">
                                        <i class="fas fa-plus"></i> Input Daily Activity
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- SEMUA DATA DI TABEL ADA DISINI -->
                    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-400">
                                <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                    <tr>
                                        <th class="px-6 py-4 w-10"></th>
                                        <th class="px-6 py-4">Date & Shift</th>
                                        <th class="px-6 py-4">Machine / Area</th>
                                        <th class="px-6 py-4 w-1/3">Issue & Solution</th>
                                        <th class="px-6 py-4">PIC</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-center">Action</th>
                                    </tr>
                                </thead>

                                <!-- PENGAMBILA DATA UNTUK TABEL DARI DATABASE ADA DISINI -->
                                <tbody class="divide-y divide-slate-700/50" id="tableReportBody">
                                    <?php
                                    // Query: Ambil data laporan, urutkan dari yang paling baru (DESC)
                                    // $query = mysqli_query($conn, "SELECT * FROM tb_daily_reports ORDER BY date_log DESC, time_start DESC");
                                    // $tahun_ini = date('Y');
                                    // $query = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE YEAR(date_log) = '$tahun_ini' ORDER BY date_log DESC, time_start DESC");
                                    
                                    // 2. Query dimodifikasi dengan filter WHERE YEAR(date_log) = ...
                                    $query = mysqli_query($conn, "SELECT * FROM tb_daily_reports 
                                    WHERE YEAR(date_log) = '$tahun_pilihan' 
                                    ORDER BY date_log DESC, time_start DESC");

                                    while ($row = mysqli_fetch_assoc($query)) {
                                        $id = $row['report_id'];
                                        // 2. Ganti Enter dengan Penanda Unik "_ENTER_"
                                        $clean_problem = str_replace(array("\r\n", "\r", "\n"), "_ENTER_", $row['problem']);
                                        $clean_action  = str_replace(array("\r\n", "\r", "\n"), "_ENTER_", $row['action']);
                                        $clean_part  = str_replace(array("\r\n", "\r", "\n"), "_ENTER_", $row['sparepart_used']);

                                        // Logika Warna Badge Kategori
                                        $catColor = 'text-slate-400 border-slate-500'; // Default
                                        if ($row['category'] == 'Breakdown') {
                                            $catColor = 'text-red-400 border-red-500 bg-red-500/10';
                                        }
                                        if ($row['category'] == 'PM') {
                                            $catColor = 'text-blue-400 border-blue-500 bg-blue-500/10';
                                        }
                                        if ($row['category'] == 'Project') {
                                            $catColor = 'text-purple-400 border-purple-500 bg-purple-500/10';
                                        }

                                        // Logika Warna Status
                                        $statusBadge = 'bg-slate-700 text-slate-300';
                                        if ($row['status'] == 'Solved') {
                                            $statusBadge = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                                        }
                                        if ($row['status'] == 'Monitor') {
                                            $statusBadge = 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20';
                                        }
                                        if ($row['status'] == 'Open') {
                                            $statusBadge = 'bg-red-500/10 text-red-400 border border-red-500/20';
                                        }
                                    ?>

                                            <tr class="hover:bg-slate-800/50 transition border-b border-slate-800" data-date="<?php echo $row['date_log']; ?>">
                                        <!-- <tr class="hover:bg-slate-700/20 transition group border-l-4 border-transparent hover:border-emerald-500" data-date="<?php echo $row['date_log']; ?>"> -->
                                            <td class="px-6 py-4 text-center">
                                                <button onclick="toggleDetail('lap<?php echo $id; ?>')" class="w-6 h-6 rounded-full bg-slate-700 text-emerald-400 hover:bg-emerald-600 hover:text-white transition flex items-center justify-center focus:outline-none">
                                                    <i class="fas fa-plus text-xs transition-transform" id="icon-lap<?php echo $id; ?>"></i>
                                                </button>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-white font-medium"><?php echo date('d M Y', strtotime($row['date_log'])); ?></div>
                                                <div class="text-xs text-slate-500">Shift <?php echo $row['shift']; ?></div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span class="text-indigo-400 font-medium"><?php echo $row['machine_name']; ?></span>
                                                <div class="text-xs text-slate-500"><?php echo $row['plant']; ?> - <?php echo $row['area']; ?></div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="line-clamp-1 text-white">
                                                    <span class="<?php echo $catColor; ?> font-bold text-[10px] px-1 rounded border mr-1 uppercase"><?php echo $row['category']; ?></span>
                                                    <?php echo substr($row['problem'], 0, 50) . '...'; ?>
                                                </div>
                                                <div class="text-xs text-slate-500 mt-1 line-clamp-1"><?php echo substr($row['action'], 0, 50) . '...'; ?></div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-full bg-slate-600 flex items-center justify-center text-[10px] text-white font-bold">
                                                        <?php echo substr($row['pic'], 0, 1); ?>
                                                    </div>
                                                    <span class="text-xs"><?php echo $row['pic']; ?></span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span class="<?php echo $statusBadge; ?> px-2 py-0.5 rounded text-xs">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 text-center">
                                                <?php
                                                // --- LOGIKA HAK AKSES ---
                                                // 1. Cek apakah nama user login ada di kolom PIC laporan ini?
                                                $isMyReport = (stripos($row['pic'], $my_name) !== false);

                                                // 2. Cek apakah dia Admin/Section (Bebas Edit semua)
                                                $isAdmin = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section');

                                                // JIKA INI LAPORAN SAYA ATAU SAYA ADMIN -> TAMPILKAN TOMBOL
                                                if ($isMyReport || $isAdmin) {
                                                ?>
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button onclick="editReport(
                                                    '<?php echo $row['report_id']; ?>',
                                                    '<?php echo $row['date_log']; ?>',
                                                    '<?php echo $row['end_date']; ?>',
                                                    '<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>',
                                                    '<?php echo $row['shift']; ?>',
                                                    '<?php echo $row['time_start']; ?>',
                                                    '<?php echo $row['time_finish']; ?>',
                                                    '<?php echo htmlspecialchars($row['machine_name'], ENT_QUOTES); ?>',
                                                    '<?php echo htmlspecialchars($row['category'], ENT_QUOTES); ?>',
                                                    '<?php echo $clean_problem; ?>',
                                                    '<?php echo $clean_action; ?>', 
                                                    '<?php echo htmlspecialchars($row['pic'], ENT_QUOTES); ?>',
                                                    '<?php echo $clean_part; ?>',                                                  
                                                    '<?php echo $row['status']; ?>'
                                                )" class="bg-slate-700 hover:bg-blue-600 text-white w-8 h-8 rounded flex items-center justify-center transition" title="Edit">
                                                            <i class="fas fa-pen text-xs"></i>
                                                        </button>

                                                        <button onclick="confirmDeleteReport(<?php echo $row['report_id']; ?>)" class="bg-slate-700 hover:bg-red-600 text-white w-8 h-8 rounded flex items-center justify-center transition" title="Hapus Laporan">
                                                            <i class="fas fa-trash text-xs"></i>
                                                        </button>
                                                    </div>
                                                <?php
                                                } else {
                                                    // JIKA BUKAN LAPORAN SAYA -> TAMPILKAN ICON GEMBOK / KOSONG
                                                ?>
                                                    <span class="text-slate-600 text-xs italic" title="Anda tidak memiliki akses edit">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                <?php } ?>
                                            </td>
                                        </tr>

                                        <tr id="detail-lap<?php echo $id; ?>" class="hidden bg-slate-800/50 border-b border-slate-700 shadow-inner">
                                            <td colspan="6" class="px-8 py-6">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-xs">

                                                    <div class="space-y-2 border-r border-slate-700 pr-4">
                                                        <h4 class="text-emerald-400 font-bold uppercase tracking-wider mb-2">⏳ Durasi Pengerjaan</h4>
                                                        <div class="flex justify-between">
                                                            <span class="text-slate-500">End Date:</span>
                                                            <span class="text-white font-mono"><?php echo $row['end_date']; ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-slate-500">Time Start:</span>
                                                            <span class="text-white font-mono"><?php echo $row['time_start']; ?></span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-slate-500">Time Finish:</span>
                                                            <span class="text-white font-mono"><?php echo $row['time_finish']; ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2 border-r border-slate-700 pr-4">
                                                        <h4 class="text-emerald-400 font-bold uppercase tracking-wider mb-2">🛠️ Teknis & Sparepart</h4>
                                                        <div>
                                                            <span class="text-slate-500 block mb-1">Kategori:</span>
                                                            <span class="<?php echo $catColor; ?> px-2 py-1 rounded border text-xs font-bold">
                                                                <?php echo $row['category']; ?>
                                                            </span>
                                                        </div>
                                                        <div class="mt-3">
                                                            <span class="text-slate-500 block mb-1">Sparepart Used / Note:</span>
                                                            <span class="text-slate-300 italic bg-slate-900 px-2 py-1 rounded block border border-slate-700">
                                                                <?php echo !empty($row['sparepart_used']) ? $row['sparepart_used'] : '- Tidak ada -'; ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <h4 class="text-emerald-400 font-bold uppercase tracking-wider mb-2">📷 Lampiran / Evidence</h4>

                                                        <?php if (!empty($row['evidence_file'])):
                                                            // 1. Pecah string menjadi array berdasarkan koma
                                                            $files = explode(',', $row['evidence_file']);
                                                        ?>
                                                            <div class="grid grid-cols-1 gap-2"> <?php foreach ($files as $file):
                                                                                                        $file = trim($file); // Hapus spasi jika ada
                                                                                                        if (empty($file)) continue;
                                                                                                    ?>
                                                                    <a href="uploads/<?php echo $file; ?>" target="_blank" class="bg-slate-900 p-2 rounded border border-slate-700 flex items-center justify-between group cursor-pointer hover:border-emerald-500 transition">
                                                                        <div class="flex items-center gap-3">
                                                                            <div class="w-8 h-8 bg-slate-800 rounded flex items-center justify-center text-slate-400">
                                                                                <?php if (strpos($file, '.pdf') !== false): ?>
                                                                                    <i class="fas fa-file-pdf text-red-400"></i>
                                                                                <?php else: ?>
                                                                                    <i class="fas fa-image text-blue-400"></i>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <div>
                                                                                <div class="text-white font-medium truncate w-32" title="<?php echo $file; ?>">
                                                                                    <?php echo (strlen($file) > 15) ? substr($file, 11) : $file; // Potong timestamp depannya biar rapi (opsional) 
                                                                                    ?>
                                                                                </div>
                                                                                <div class="text-[10px] text-slate-500">Klik untuk lihat</div>
                                                                            </div>
                                                                        </div>
                                                                        <i class="fas fa-external-link-alt text-slate-600 group-hover:text-emerald-400 mr-2 text-xs"></i>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-slate-500 italic">- Tidak ada lampiran -</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="mt-4 pt-4 border-t border-slate-700">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            <span class="text-[10px] text-slate-500 font-bold uppercase block mb-1">Problem Description:</span>
                                                            <p class="text-slate-300 bg-slate-900/50 p-2 rounded border border-slate-700/50 leading-relaxed">
                                                                <?php echo nl2br($row['problem']); ?>
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <span class="text-[10px] text-emerald-500 font-bold uppercase block mb-1">Action Taken:</span>
                                                            <p class="text-slate-300 bg-slate-900/50 p-2 rounded border border-emerald-900/30 leading-relaxed">
                                                                <?php echo nl2br($row['action']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-between items-center mt-4 mb-8 px-4" id="paginationContainer">
                            <div class="text-xs text-slate-500" id="pageInfo">Loading data...</div>
                            <div id="paginationControls" class="flex gap-1"></div>
                        </div>
                    </div>
                </div>
        </main>
    </div>

    <!-- Modal CREATE laporan harian -->
    <div id="modalLaporan" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" id="backdropLaporan"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-3xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">

                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-edit text-emerald-400"></i> Input Daily Activity
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Silakan isi log aktivitas pekerjaan secara detail.</p>
                    </div>
                    <button class="close-modal text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_add_report.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">1. Start Date</label>
                            <input type="date" name="date_log" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">2. End Date</label>
                            <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">3. Plant / Area</label>
                            <select name="plant" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">-- Pilih --</option>
                                <option value="PLANT A">PLANT A</option>
                                <option value="PLANT BCHIT">PLANT BCHIT</option>
                                <option value="PLANT D/K">PLANT D/K</option>
                                <option value="PLANT E">PLANT E</option>
                                <option value="PLANT TBR">PLANT TBR</option>
                                <option value="PLANT MIXING">PLANT MIXING</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">4. Time Start</label>
                            <input type="time" name="time_start" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">5. Time Finish</label>
                            <input type="time" name="time_finish" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">6. Shift</label>
                            <select name="shift" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                                <option value="Non-Shift">Non-Shift (Normal)</option>
                                <option value="1">Shift 1 (Pagi)</option>
                                <option value="2">Shift 2 (Sore)</option>
                                <option value="3">Shift 3 (Malam)</option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-slate-800 my-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">7. Machine Name / Tag</label>
                            <input type="text" name="machine_name" placeholder="Contoh: Conveyor Line 1" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">8. Category</label>
                            <select name="category" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                                <option value="Breakdown">🛑 Breakdown / Trouble</option>
                                <option value="PM">🔧 Preventive Maintenance</option>
                                <option value="Project">🚀 Project Implementation</option>
                                <option value="Other">📋 Other / Meeting</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">9. Person / PIC</label>
                        <select name="pic[]" id="create_pic_dashboard" multiple placeholder="Pilih Teknisi..." autocomplete="off">
                            <option value="">Pilih Personil...</option>
                            <?php
                            // Pastikan $teamList sudah ada di bagian paling atas file PHP
                            if (isset($teamList)) {
                                foreach ($teamList as $m): ?>
                                    <option value="<?php echo $m; ?>"><?php echo $m; ?></option>
                            <?php endforeach;
                            } ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">10. Problem / Issue Description</label>
                        <textarea name="problem" rows="2" placeholder="Jelaskan gejala kerusakan..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required></textarea>
                    </div>

                    <div>
                        <label class="block text-xs text-emerald-400 mb-1 font-medium">11. Action Taken (Solusi)</label>
                        <textarea name="action" rows="2" placeholder="Jelaskan langkah perbaikan..." class="w-full bg-slate-950 border border-emerald-600/50 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required></textarea>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">12. Note / Sparepart Used</label>
                        <textarea name="sparepart_used" rows="2" placeholder="Catatan tambahan..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-2 font-medium">13. Evidence / Attachment</label>
                        <div class="w-full">
                            <label for="file_evidence" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-emerald-500 transition group">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 mb-2 group-hover:text-emerald-400 transition"></i>
                                    <p class="text-sm text-slate-400 mb-1"><span class="font-semibold text-emerald-400">Klik untuk upload</span></p>
                                    <p id="file-name-display" class="text-xs text-emerald-400 mt-2 font-medium hidden"></p>
                                </div>
                                <input id="file_evidence" type="file" name="evidence[]" multiple accept="image/*,.pdf" class="hidden" />
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800">
                        <button type="button" class="close-modal flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition text-sm font-medium shadow-lg shadow-emerald-600/20">
                            <i class="fas fa-save mr-2"></i> Simpan Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Laporan Harian -->
    <div id="modalEditReport" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEditReport')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-3xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">

                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-edit text-blue-400"></i> Edit Laporan Harian
                    </h3>
                    <button onclick="closeModal('modalEditReport')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_edit_report.php" method="POST" enctype="multipart/form-data" class="space-y-5">

                    <input type="hidden" name="report_id" id="edit_id">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Start Date</label>
                            <input type="date" name="date_log" id="edit_date" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">End Date</label>
                            <input type="date" name="end_date" id="edit_end_date" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Plant</label>
                            <select name="plant" id="edit_plant" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">-- Pilih --</option>
                                <option value="PLANT A">PLANT A</option>
                                <option value="PLANT BCHIT">PLANT BCHIT</option>
                                <option value="PLANT D/K">PLANT D/K</option>
                                <option value="PLANT E">PLANT E</option>
                                <option value="PLANT TBR">PLANT TBR</option>
                                <option value="PLANT MIXING">PLANT MIXING</option>
                                <!-- <option value="PLANT DUMMY">PLANT DUMMY</option> -->
                            </select>
                        </div>

                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Start Time</label>
                            <input type="time" name="time_start" id="edit_start" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Finish Time</label>
                            <input type="time" name="time_finish" id="edit_finish" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Shift</label>
                            <select name="shift" id="edit_shift" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                                <option value="3">Shift 3</option>
                                <option value="Non-Shift">Non-Shift</option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-slate-800 my-2">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Machine Name</label>
                            <input type="text" name="machine_name" id="edit_machine" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Category</label>
                            <select name="category" id="edit_cat" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                <option value="Breakdown">Breakdown</option>
                                <option value="PM">Preventive Maint.</option>
                                <option value="Project">Project</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">Status</label>
                            <select name="status" id="edit_status" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                <option value="Open">Open (Belum Selesai)</option>
                                <option value="Monitor">Monitor (Pantau)</option>
                                <option value="Solved">Solved (Selesai)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">PIC</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-2.5 text-slate-500 text-xs"></i>
                            <select name="pic[]" id="edit_pic" multiple placeholder="Pilih Teknisi..." autocomplete="off">
                                <option value="">Pilih Personil...</option>
                                <?php if (isset($teamList)) {
                                    foreach ($teamList as $m): ?>
                                        <option value="<?php echo $m; ?>"><?php echo $m; ?></option>
                                <?php endforeach;
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Problem</label>
                        <textarea name="problem" id="edit_problem" rows="2" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs text-emerald-400 mb-1 font-medium">Action Taken</label>
                        <textarea name="action" id="edit_action" rows="2" class="w-full bg-slate-950 border border-emerald-600/50 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Sparepart Used / Note</label>
                        <textarea name="sparepart_used" id="edit_part" rows="2" placeholder="Catatan tambahan..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></textarea>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <label class="block text-xs text-slate-400 mb-2 font-medium">Update Evidence (Opsional)</label>
                        <div class="w-full">
                            <label for="file_evidence_edit" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-emerald-500 transition group">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 mb-2 group-hover:text-emerald-400 transition"></i>
                                    <p class="text-sm text-slate-400 mb-1"><span class="font-semibold text-emerald-400">Klik untuk ganti file</span></p>
                                    <p class="text-[10px] text-slate-500">Biarkan kosong jika tidak ingin mengubah.</p>
                                    <p id="file-name-edit" class="text-xs text-emerald-400 mt-2 font-medium hidden"></p>
                                </div>
                                <input id="file_evidence_edit" type="file" name="evidence[]" multiple accept="image/*,.pdf" class="hidden" />
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-2">
                        <button type="button" onclick="closeModal('modalEditReport')" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition text-sm font-medium shadow-lg">Update Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script>
        // --- A. INISIALISASI TOM SELECT (PIC) ---
        // Kita simpan instance-nya di variabel global biar bisa di-clear saat edit
        var tomSelectPicCreate, tomSelectPicEdit;

        // document.addEventListener('DOMContentLoaded', function() {
        // document.addEventListener('turbo:load', function() {
        (function() {
            // Init TomSelect untuk Modal Create (Dashboard & Laporan)
            const configTom = { plugins: ['remove_button'], create: false, maxItems: 5, placeholder: "Pilih PIC..." };
            
            if (document.getElementById('create_pic')) {
                tomSelectPicCreate = new TomSelect("#create_pic", configTom);
            }
            // Jika ada modal create dashboard (opsional)
            if (document.getElementById('create_pic_dashboard')) {
                new TomSelect("#create_pic_dashboard", configTom);
            }
            
            // Init TomSelect untuk Modal Edit
            if (document.getElementById('edit_pic')) {
                tomSelectPicEdit = new TomSelect("#edit_pic", configTom);
            }

            // --- B. LOGIC PAGINATION & FILTER TABLE (CLIENT SIDE) ---
            initTablePagination();

            // --- C. LOGIC UPLOAD FILE PREVIEW ---
            setupFileUpload('file_evidence', 'file-name-display');
            setupFileUpload('file_evidence_edit', 'file-name-edit');
        })();

        // --- FUNGSI EDIT REPORT ---
        function editReport(id, date, end_date, plant, shift, start, finish, machine, cat, prob, act, pic, part, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_end_date').value = end_date;
            document.getElementById('edit_start').value = start;
            document.getElementById('edit_finish').value = finish;
            document.getElementById('edit_machine').value = machine;
            
            // Handle Dropdown Biasa (Safe Check)
            const setVal = (id, val) => { if(document.getElementById(id)) document.getElementById(id).value = val; };
            setVal('edit_plant', plant);
            setVal('edit_shift', shift);
            setVal('edit_cat', cat);
            setVal('edit_status', status);

            // Handle Textarea (Convert _ENTER_ back to Newline)
            document.getElementById('edit_problem').value = prob.split("_ENTER_").join("\n");
            document.getElementById('edit_action').value = act.split("_ENTER_").join("\n");
            document.getElementById('edit_part').value = part.split("_ENTER_").join("\n");

            // Handle TomSelect PIC
            if (tomSelectPicEdit) {
                tomSelectPicEdit.clear();
                if (pic) {
                    const names = pic.split(',').map(item => item.trim());
                    names.forEach(name => tomSelectPicEdit.addItem(name));
                }
            }
            openModal('modalEditReport');
        }

        // --- FUNGSI DELETE REPORT ---
        function confirmDeleteReport(id) {
            Swal.fire({
                title: 'Hapus Laporan?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'delete/delete_report.php?id=' + id;
            });
        }

        // --- FUNGSI TOGGLE DETAIL ROW ---
        function toggleDetail(rowId) {
            const detailRow = document.getElementById('detail-' + rowId);
            const icon = document.getElementById('icon-' + rowId);
            if (detailRow && icon) {
                const isHidden = detailRow.classList.contains('hidden') || detailRow.style.display === 'none';
                if (isHidden) {
                    detailRow.classList.remove('hidden');
                    detailRow.style.display = 'table-row';
                    icon.classList.replace('fa-plus', 'fa-minus');
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    detailRow.classList.add('hidden');
                    detailRow.style.display = 'none';
                    icon.classList.replace('fa-minus', 'fa-plus');
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        }

        // --- FUNGSI DOWNLOAD EXCEL ---
        function downloadExcel() {
            const searchVal = document.getElementById('searchInput')?.value || '';
            const startVal = document.getElementById('startDate')?.value || '';
            const endVal = document.getElementById('endDate')?.value || '';
            let url = 'export/export_laporan.php?export=true';
            if (searchVal) url += '&search=' + encodeURIComponent(searchVal);
            if (startVal) url += '&start=' + encodeURIComponent(startVal);
            if (endVal) url += '&end=' + encodeURIComponent(endVal);
            window.location.href = url;
        }

        // --- HELPER: FILE UPLOAD PREVIEW ---
        function setupFileUpload(inputId, displayId) {
            const fileInput = document.getElementById(inputId);
            const fileNameDisplay = document.getElementById(displayId);
            if (fileInput && fileNameDisplay) {
                fileInput.addEventListener('change', function() {
                    const files = this.files;
                    if (files.length > 5) {
                        Swal.fire({ icon: 'warning', title: 'Maksimal 5 File!', background: '#1e293b', color: '#fff' });
                        this.value = ''; fileNameDisplay.classList.add('hidden'); return;
                    }
                    if (files.length > 0) {
                        fileNameDisplay.classList.remove('hidden');
                        fileNameDisplay.textContent = files.length === 1 ? `📄 ${files[0].name}` : `📂 ${files.length} file dipilih`;
                    } else {
                        fileNameDisplay.classList.add('hidden');
                    }
                });
            }
        }

        // --- HELPER: TABLE PAGINATION & FILTER ---
        function initTablePagination() {
            const tableBody = document.getElementById('tableReportBody');
            if (!tableBody) return;

            const rowsPerPage = 10;
            let allRows = Array.from(tableBody.querySelectorAll('tr')).filter(row => !row.id.includes('detail-'));
            let currentPage = 1;

            const searchInput = document.getElementById('searchInput');
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            const pageInfo = document.getElementById('pageInfo');
            const paginationControls = document.getElementById('paginationControls');

            function renderTable() {
                const searchText = searchInput ? searchInput.value.toLowerCase() : '';
                const startVal = startDateInput ? startDateInput.value : '';
                const endVal = endDateInput ? endDateInput.value : '';

                // Filter Logic
                const filteredRows = allRows.filter(row => {
                    const textMatch = row.textContent.toLowerCase().includes(searchText);
                    let dateMatch = true;
                    const rowDate = row.getAttribute('data-date');
                    if (startVal && rowDate < startVal) dateMatch = false;
                    if (endVal && rowDate > endVal) dateMatch = false;
                    return textMatch && dateMatch;
                });

                // Pagination Logic
                const totalItems = filteredRows.length;
                const totalPages = Math.ceil(totalItems / rowsPerPage);
                if (currentPage > totalPages) currentPage = 1;
                
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                // Hide All
                tableBody.querySelectorAll('tr').forEach(tr => tr.style.display = 'none');

                // Show Filtered Master Rows
                filteredRows.slice(start, end).forEach(row => row.style.display = '');

                // Update Info Text
                if(pageInfo) pageInfo.innerText = `Showing ${totalItems === 0 ? 0 : start + 1} - ${Math.min(end, totalItems)} of ${totalItems} reports`;
                
                // Render Buttons
                renderButtons(totalPages);
            }

            function renderButtons(totalPages) {
                if (!paginationControls) return;
                paginationControls.innerHTML = "";
                if (totalPages <= 1) return;

                const createBtn = (text, page) => {
                    const btn = document.createElement('button');
                    btn.innerText = text;
                    const isActive = (page === currentPage);
                    btn.className = `px-3 py-1 rounded transition text-xs ${isActive ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'}`;
                    btn.onclick = () => { currentPage = page; renderTable(); };
                    return btn;
                };

                if (currentPage > 1) paginationControls.appendChild(createBtn("Prev", currentPage - 1));
                
                let startP = Math.max(1, currentPage - 2);
                let endP = Math.min(totalPages, currentPage + 2);
                for (let i = startP; i <= endP; i++) paginationControls.appendChild(createBtn(i, i));
                
                if (currentPage < totalPages) paginationControls.appendChild(createBtn("Next", currentPage + 1));
            }

            // Event Listeners
            [searchInput, startDateInput, endDateInput].forEach(el => {
                if(el) {
                    // Gunakan event 'input', ini paling sakti untuk teks DAN date picker
                    el.addEventListener('input', () => { 
                        currentPage = 1; 
                        renderTable(); 
                    });
                }
            });
            renderTable();
            }
    </script>
</body>

</html>