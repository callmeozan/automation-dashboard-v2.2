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
                                            <input id="searchInput" type="text" placeholder="Search Daily Activity..." class="w-full bg-slate-800 border border-slate-700 text-white pl-9 pr-4 py-2.5 rounded-lg focus:border-cyan-500 focus:outline-none transition text-sm" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="flex items-end gap-2 w-full md:w-auto mt-4 xl:mt-0">
                                        
                                        <div class="flex-1 md:flex-none">
                                            <label class="block text-xs text-slate-400 mb-1">From</label>
                                            <input type="date" id="startDate" class="h-[42px] w-full md:w-32px bg-slate-800/30 border border-slate-700 text-slate-300 px-3 rounded-lg focus:border-cyan-500 focus:outline-none transition hover:border-slate-600 text-sm cursor-pointer [color-scheme:dark]">
                                        </div>

                                        <span class="text-slate-600 mb-2.5 font-medium">-</span>

                                        <div class="flex-1 md:flex-none">
                                            <label class="block text-xs text-slate-400 mb-1">To</label>
                                            <input type="date" id="endDate" class="h-[42px] w-full md:w-32px bg-slate-800/30 border border-slate-700 text-slate-300 px-3 rounded-lg focus:border-cyan-500 focus:outline-none transition hover:border-slate-600 text-sm cursor-pointer [color-scheme:dark]">
                                        </div>

                                    </div>

                                </div>
                                
                                <div class="flex flex-col md:flex-row gap-2 w-full xl:w-auto mt-4 xl:mt-0">
                                    <form method="GET" action="" class="w-full md:w-32 m-0 p-0">
                                        <label class="block text-xs text-slate-400 mb-1">Select Year</label>
                                            <select name="tahun" onchange="this.form.submit()" class="bg-slate-800/30 border border-slate-700 text-white text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5">
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
                                <div class="flex flex-wrap items-center gap-2.5 w-full xl:w-auto justify-start xl:justify-end">
                                    <button onclick="downloadExcel()" class="bg-slate-800 hover:bg-green-700 text-slate-300 px-4 py-2.5 rounded-lg border border-slate-700 text-sm transition flex items-center justify-center gap-2 flex-1 xl:flex-none">
                                        <i class="fas fa-file-excel"></i> <span>Export Excel</span>
                                    </button>

                                    <!-- Tombol Generator Report -->
                                    <button type="button" onclick="openProjectReportModal()" class="h-[42px] px-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white rounded-lg text-sm font-semibold shadow-lg shadow-cyan-500/20 transition flex items-center justify-center gap-2">
                                        <i class="fas fa-file-invoice"></i>
                                        <span>Create Report</span>
                                    </button>

                                    <button class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-cyan-600/20 flex items-center justify-center gap-2 flex-1 xl:flex-none btn-input-laporan">
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
                                        <!-- Tambahkan th checkbox di baris terdepan thead -->
                                        <th class="px-3 py-3 w-10 text-center">
                                            <label class="relative inline-flex items-center justify-center cursor-pointer select-none">
                                                <input type="checkbox" id="checkAllReports" class="peer sr-only">
                                                <div class="w-4 h-4 rounded-md border border-slate-700 bg-slate-900/90 transition-all duration-200 ease-out 
                                                            hover:border-cyan-400/60 hover:bg-slate-800
                                                            peer-checked:bg-gradient-to-tr peer-checked:from-cyan-600 peer-checked:to-teal-400 
                                                            peer-checked:border-cyan-400 peer-checked:shadow-[0_0_12px_rgba(16,185,129,0.5)] 
                                                            peer-checked:scale-105 flex items-center justify-center">
                                                    <svg class="w-2.5 h-2.5 text-slate-950 stroke-[3.5] scale-0 peer-checked:scale-100 transition-transform duration-200 ease-out" 
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                </div>
                                            </label>
                                        </th>
                                    </tr>
                                </thead>

                                <!-- PENGAMBILA DATA UNTUK TABEL DARI DATABASE ADA DISINI -->
                                <tbody class="divide-y divide-slate-700/50" id="tableReportBody">
                                    <?php
                                    // 2. Query dimodifikasi dengan filter WHERE YEAR(date_log) = ...
                                    $query = mysqli_query($conn, "SELECT * FROM tb_daily_reports 
                                    WHERE YEAR(date_log) = '$tahun_pilihan' 
                                    ORDER BY date_log DESC, time_start DESC");

                                    while ($row = mysqli_fetch_assoc($query)) {
                                        $id = $row['report_id'];
                                        // 2. Ganti Enter dengan Penanda Unik "_ENTER_"
                                        $clean_problem = addslashes(str_replace(array("\r\n", "\r", "\n"), "_ENTER_", $row['problem']));
                                        $clean_action  = addslashes(str_replace(array("\r\n", "\r", "\n"), "_ENTER_", $row['action']));
                                        $clean_part    = addslashes(str_replace(array("\r\n", "\r", "\n"), "_ENTER_", $row['sparepart_used'] ?? ''));

                                        // Logika Warna Badge Kategori
                                        $catColor = 'text-slate-400 border-slate-500 bg-slate-500/10'; // Default

                                        if ($row['category'] == 'Breakdown') {
                                                $catColor = 'text-red-400 border-red-500 bg-red-500/10';
                                            } elseif ($row['category'] == 'PM') {
                                                $catColor = 'text-blue-400 border-blue-500 bg-blue-500/10';
                                            } elseif (strpos($row['category'], 'Project') !== false) {
                                                $catColor = 'text-purple-400 border-purple-500 bg-purple-500/10';
                                            } elseif ($row['category'] == 'Improvement') {
                                                $catColor = 'text-emerald-400 border-emerald-500 bg-emerald-500/10';
                                            } elseif ($row['category'] == 'Service') {
                                                $catColor = 'text-amber-400 border-amber-500 bg-amber-500/10';
                                            } elseif ($row['category'] == 'Commissioning') {
                                                // Cyan untuk tahap commissioning / integrasi sistem
                                                $catColor = 'text-cyan-400 border-cyan-500 bg-cyan-500/10';
                                            } elseif (in_array($row['category'], ['Meeting', 'Other'])) {
                                                // Slate netral untuk Meeting & Other
                                                $catColor = 'text-slate-300 border-slate-500 bg-slate-500/10';
                                            } else {
                                                // Fallback jika ada kategori lain yang belum terdaftar
                                                $catColor = 'text-slate-400 border-slate-600 bg-slate-600/10';
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
                                            <td class="px-6 py-4 text-center">
                                                <button data-toggle-id="lap<?php echo $id; ?>" class="btn-toggle-row w-6 h-6 rounded-full bg-slate-700 text-cyan-400 hover:bg-cyan-600 hover:text-white transition flex items-center justify-center focus:outline-none">    
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
                                                    <span class="<?php echo $catColor; ?> font-bold text-[10px] px-1.5 py-0.5 rounded border mr-1 uppercase">
                                                        <?php echo htmlspecialchars($row['category']); ?>
                                                    </span>
                                                    <span><?php echo substr($row['problem'], 0, 50) . '...'; ?></span>
                                                </div>
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
                                                                '<?php echo htmlspecialchars($clean_problem, ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($clean_action, ENT_QUOTES); ?>', 
                                                                '<?php echo htmlspecialchars($row['pic'], ENT_QUOTES); ?>',
                                                                '<?php echo htmlspecialchars($clean_part, ENT_QUOTES); ?>', 
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

                                            <!-- Tambahkan td checkbox ini di baris pertama <tr> -->
                                            <td class="px-3 py-3 text-center">
                                                <label class="relative inline-flex items-center justify-center cursor-pointer select-none">
                                                    <input type="checkbox" class="report-item-check peer sr-only"
                                                        value="<?php echo $row['report_id']; ?>"
                                                        data-category="<?php echo htmlspecialchars($row['category'] ?? ''); ?>"
                                                        data-date="<?php echo $row['date_log']; ?>"
                                                        data-end-date="<?php echo $row['end_date']; ?>"
                                                        data-notes="<?php echo htmlspecialchars(($row['sparepart_used'] === '- Tidak ada -' || empty($row['sparepart_used'])) ? '-' : $row['sparepart_used']); ?>"
                                                        data-machine="<?php echo htmlspecialchars($row['machine_name'] ?? ''); ?>"
                                                        data-plant="<?php echo htmlspecialchars($row['plant'] ?? ''); ?>"
                                                        data-pic="<?php echo htmlspecialchars($row['pic'] ?? ''); ?>"
                                                        data-action="<?php echo htmlspecialchars($row['action'] ?? ''); ?>">
                                                    
                                                    <div class="w-4 h-4 rounded-md border border-slate-700 bg-slate-900/90 transition-all duration-200 ease-out 
                                                                hover:border-cyan-400/60 hover:bg-slate-800
                                                                peer-checked:bg-gradient-to-tr peer-checked:from-cyan-600 peer-checked:to-teal-400 
                                                                peer-checked:border-cyan-400 peer-checked:shadow-[0_0_12px_rgba(16,185,129,0.5)] 
                                                                peer-checked:scale-105 flex items-center justify-center">
                                                        <svg class="w-2.5 h-2.5 text-slate-950 stroke-[3.5] scale-0 peer-checked:scale-100 transition-transform duration-200 ease-out" 
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                    </div>
                                                </label>
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
                                                                <?php echo (strpos($row['category'], 'Project') !== false) ? 'PROJECT' : htmlspecialchars($row['category']); ?>
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
                                                            $files = explode(',', $row['evidence_file']);
                                                        ?>
                                                            <div class="grid grid-cols-1 gap-2"> 
                                                                <?php foreach ($files as $file):
                                                                    $file = trim($file);
                                                                    if (empty($file)) continue;
                                                                    $filePath = 'uploads/' . $file;
                                                                ?>
                                                                    <!-- Menggunakan tombol trigger modal pop-up -->
                                                                    <button type="button" onclick="openEvidenceModal('<?php echo $filePath; ?>', '<?php echo htmlspecialchars($file, ENT_QUOTES); ?>')" class="w-full bg-slate-900 p-2 rounded border border-slate-700 flex items-center justify-between group cursor-pointer hover:border-emerald-500 transition text-left">
                                                                        <div class="flex items-center gap-3">
                                                                            <div class="w-8 h-8 bg-slate-800 rounded flex items-center justify-center text-slate-400">
                                                                                <?php if (strpos(strtolower($file), '.pdf') !== false): ?>
                                                                                    <i class="fas fa-file-pdf text-red-400"></i>
                                                                                <?php else: ?>
                                                                                    <i class="fas fa-image text-blue-400"></i>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <div>
                                                                                <div class="text-white font-medium truncate w-32 md:w-48" title="<?php echo $file; ?>">
                                                                                    <?php echo (strlen($file) > 15) ? substr($file, 11) : $file; ?>
                                                                                </div>
                                                                                <div class="text-[10px] text-slate-500">Klik untuk lihat</div>
                                                                            </div>
                                                                        </div>
                                                                        <i class="fas fa-eye text-slate-600 group-hover:text-emerald-400 mr-2 text-xs"></i>
                                                                    </button>
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
                        <p class="text-xs text-slate-500 mt-1">Please fill in the daily activity log in detail.</p>
                    </div>
                    <button class="close-modal text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="formAddReport" action="process/process_add_report.php" method="POST" enctype="multipart/form-data" class="space-y-5">
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
                            <select name="plant" id="create_plant" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">-- Select --</option>
                                <option value="PLANT A">PLANT A</option>
                                <option value="PLANT BCHIT">PLANT BCHIT</option>
                                <option value="PLANT PCR">PLANT PCR</option>
                                <option value="PLANT E">PLANT E</option>
                                <option value="PLANT TBR">PLANT TBR</option>
                                <option value="PLANT MIXING">PLANT MIXING</option>
                                <option value="PLANT RND">PLANT RND</option>
                                <option value="LABORATORIUM">LABORATORIUM</option>
                                <option value="RUANG AUTOMATION">MARKAS BESAR</option>
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
                                <option value="">-- Select --</option>
                                <option value="Service">🛠️ Service</option>
                                <option value="Commissioning">🔀 Commissioning</option>
                                <option value="Improvement">✨ Improvement</option>
                                <option value="Breakdown">🛑 Breakdown / Trouble</option>
                                <option value="Project Preparation">📋 Project Preparation</option>
                                <option value="Project Execution">⚙️ Project Execution</option>
                                <option value="PM">🔧 Preventive Maintenance</option>
                                <option value="Meeting">👥 Meeting</option>
                                <option value="Other">👩🏻‍💻 Other</option>

                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">9. Person / PIC</label>
                        <select name="pic[]" id="create_pic_dashboard" multiple placeholder="Pick Person..." autocomplete="off">
                            <option value="">-- Select --</option>
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
                        <textarea id="action_taken" name="action" rows="4" placeholder="Jelaskan langkah perbaikan..." 
                                class="w-full bg-slate-950 border border-emerald-600/50 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required></textarea>
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
                                    <p class="text-sm text-slate-400 mb-1"><span class="font-semibold text-emerald-400">Click to upload</span></p>
                                    <p id="file-name-create" class="text-xs text-emerald-400 mt-2 font-medium hidden"></p>
                                </div>
                                <input id="file_evidence" type="file" name="evidence[]" multiple accept="image/*,.pdf" class="hidden" />
                            </label>
                        </div>
                    </div>

                    <!-- Container Progress Bar (Default Sembunyi / Hidden) -->
                    <div id="uploadProgressContainer" class="hidden mt-4 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-cyan-400 font-medium flex items-center gap-1.5">
                                <i class="fas fa-spinner fa-spin text-[11px]"></i>
                                <span id="uploadStatusText">Mengunggah Dokumen...</span>
                            </span>
                            <span id="uploadPercentText" class="font-mono text-slate-300 font-bold">0%</span>
                        </div>
                        <!-- Track Bar -->
                        <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-700/60 p-0.5">
                            <div id="uploadProgressBar" 
                                class="bg-gradient-to-r from-cyan-500 to-emerald-400 h-full rounded-full transition-all duration-150 w-0">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800">
                        <button type="button" class="close-modal flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition text-sm font-medium shadow-lg shadow-emerald-600/20">
                            <i class="fas fa-save mr-2"></i> Save Report
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
                        <i class="fas fa-edit text-blue-400"></i> Edit Daily Report
                    </h3>
                    <button onclick="closeModal('modalEditReport')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="formEditReport" action="process/process_edit_report.php" method="POST" enctype="multipart/form-data" class="space-y-5">

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
                                <option value="">-- Select --</option>
                                <option value="PLANT A">PLANT A</option>
                                <option value="PLANT BCHIT">PLANT BCHIT</option>
                                <option value="PLANT PCR">PLANT PCR</option>
                                <option value="PLANT E">PLANT E</option>
                                <option value="PLANT TBR">PLANT TBR</option>
                                <option value="PLANT MIXING">PLANT MIXING</option>
                                <option value="PLANT RND">PLANT RND</option>
                                <option value="LABORATORIUM">LABORATORIUM</option>
                                <option value="RUANG AUTOMATION">MARKAS BESAR</option>
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
                                <option value="">-- Select --</option>
                                <option value="Service">Service</option>
                                <option value="Commissioning">Commissioning</option>
                                <option value="Improvement">Improvement</option>
                                <option value="Breakdown">Breakdown / Trouble</option>
                                <option value="Project Preparation">Project Preparation</option>
                                <option value="Project Execution">Project Execution</option>
                                <option value="PM">Preventive Maintenance</option>
                                <option value="Meeting">Meeting</option>
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

                    <!-- Container Progress Bar Khusus Modal Edit -->
                    <div id="uploadProgressContainerEdit" class="hidden mt-4 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-cyan-400 font-medium flex items-center gap-1.5">
                                <i class="fas fa-spinner fa-spin text-[11px]"></i>
                                <span id="uploadStatusTextEdit">Memperbarui Dokumen...</span>
                            </span>
                            <span id="uploadPercentTextEdit" class="font-mono text-slate-300 font-bold">0%</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-700/60 p-0.5">
                            <div id="uploadProgressBarEdit" 
                                class="bg-gradient-to-r from-cyan-500 to-emerald-400 h-full rounded-full transition-all duration-150 w-0">
                            </div>
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

    <!-- MODAL REVIEW PROJECT REPORT -->
    <div id="modalProjectReport" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-start justify-center p-3 sm:p-4 overflow-y-auto">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-4xl shadow-2xl p-4 sm:p-6 relative my-8">
            
            <!-- Header Modal -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
                <div>
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-file-signature text-cyan-400"></i> Automated Project Report
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Review and generate the automated project report</p>
                </div>
                <button onclick="closeModal('modalProjectReport')" class="text-slate-400 hover:text-white transition text-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Form Target Cetak -->
            <form id="formGenerateReport" action="process/print_project_report.php" method="POST" enctype="multipart/form-data" target="_blank" data-turbo="false">
                
                <!-- Hidden Data ID yang dicentang -->
                <input type="hidden" name="selected_ids" id="report_selected_ids">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Nama Proyek -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Project Name</label>
                        <input type="text" name="project_name" id="modal_project_name" required placeholder="Example: Upgrade Conveyor Line 1" 
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- Nama Mesin & Plant -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Machine</label>
                            <input type="text" name="machine_name" id="modal_machine_name" required
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Plant</label>
                            <input type="text" name="plant_name" id="modal_plant_name" required
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                        </div>
                    </div>

                    <!-- Prepared By -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Prepared By (Lead Engineer / Team)</label>
                        <input type="text" name="prepared_by" id="modal_prepared_by" required
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>

                    <!-- Approved By -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Approved By</label>
                        <input type="text" name="approved_by" value="Teguh Imanto" required
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <!-- Background Laporan -->
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Background (Project Background)</label>
                    <textarea name="background_text" rows="3" placeholder="Write the background of the problem or the reason for creating this project..."
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-cyan-500 leading-relaxed"></textarea>
                </div>

                <!-- 1. FASE 1: PROJECT PREPARATION -->
                <div class="mb-5 border border-slate-800 rounded-xl p-4 bg-slate-950/40">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 mb-3">
                        <div>
                            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wide flex items-center gap-2">
                                <i class="fas fa-clipboard-list"></i> 1. Project Preparation
                            </h4>
                            <p class="text-[11px] text-slate-400">Meetings, architecture, BOM, design, or PR/PO items</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnToggleManage" onclick="toggleGlobalEditMode()"
                                    class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-400 text-[11px] rounded font-medium border border-slate-700 transition flex items-center gap-1.5">
                                <i class="fas fa-magic"></i>
                                <span id="labelToggleManage">Edit Data</span>
                            </button>
                            <button type="button" onclick="addPreparationRow()" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] rounded font-medium transition">
                                <i class="fas fa-plus mr-1"></i> Add Row
                            </button>
                        </div>
                    </div>

                    <!-- Container Baris Dinamis Preparation -->
                    <div id="prepContainer" class="space-y-2 max-h-56 overflow-y-auto">
                        <!-- Baris akan dimasukkan via JavaScript -->
                    </div>
                </div>

                <!-- 2. FASE 2: PROJECT EXECUTION -->
                <div class="mb-5 border border-slate-800 rounded-xl p-3 sm:p-4 bg-slate-950/40">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 mb-3">
                        <div>
                            <h4 class="text-xs font-bold text-cyan-400 uppercase tracking-wide flex items-center gap-1.5 flex-wrap">
                                <i class="fas fa-tasks"></i> 
                                <span>2. Project Execution</span>
                                <span class="text-[10px] bg-cyan-950/80 border border-cyan-800 text-cyan-300 px-1.5 py-0.5 rounded font-normal normal-case">
                                    <span id="totalSelectedCount">0</span> entries selected
                                </span>
                            </h4>
                            <p class="text-[10px] sm:text-[11px] text-slate-400 mt-0.5">Daily activities that are checked from the table</p>
                        </div>
                        <span class="text-[10px] sm:text-[11px] text-cyan-400 font-mono">Adjust weight % & status</span>
                    </div>
                    <div class="max-h-56 overflow-y-auto border border-slate-800/80 rounded-xl bg-slate-950/60 p-2 space-y-2" id="selectedActivitiesList">
                        <!-- Item baris akan dirender via JavaScript -->
                    </div>
                </div>

                <!-- SECTION LAMPIRAN EVIDENCE -->
                <div class="mt-4 p-3 bg-slate-900 border border-slate-800 rounded-lg">
                    <label class="block text-xs font-semibold text-cyan-400 mb-2">
                        <i class="fas fa-camera mr-1"></i> Project Evidence Attachments (Activity / Before / After)
                    </label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <!-- 1. Foto Before -->
                        <div class="bg-slate-950 p-2.5 rounded border border-slate-800">
                            <span class="block text-[11px] text-slate-300 font-medium mb-1">Pict Before (Initial Condition)</span>
                            <input type="file" name="evidence_before[]" multiple accept="image/*" class="w-full text-[11px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700">
                            <input type="text" name="caption_before" placeholder="Before Description..." class="mt-1.5 w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-300 text-[11px] focus:outline-none">
                        </div>

                        <!-- 2. Foto Activity -->
                        <div class="bg-slate-950 p-2.5 rounded border border-slate-800">
                            <span class="block text-[11px] text-slate-300 font-medium mb-1">Pict Activity (Process)</span>
                            <input type="file" name="evidence_activity[]" multiple accept="image/*" class="w-full text-[11px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700">
                            <input type="text" name="caption_activity" placeholder="Activity Description..." class="mt-1.5 w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-300 text-[11px] focus:outline-none">
                        </div>

                        <!-- 3. Foto After -->
                        <div class="bg-slate-950 p-2.5 rounded border border-slate-800">
                            <span class="block text-[11px] text-slate-300 font-medium mb-1">Pict After (Final Result)</span>
                            <input type="file" name="evidence_after[]" multiple accept="image/*" class="w-full text-[11px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[11px] file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700">
                            <input type="text" name="caption_after" placeholder="After Description..." class="mt-1.5 w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-300 text-[11px] focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Bawah -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" onclick="closeModal('modalProjectReport')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-cyan-600/30 transition flex items-center gap-2">
                        <i class="fas fa-print"></i> Open Print Preview Report
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Pop-up Modal View Evidence -->
    <div id="modalViewEvidence" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/80 backdrop-blur-sm p-3 transition-opacity">
        <div class="relative w-full max-w-3xl bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-200">
            
            <!-- Header Pop-up -->
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800 bg-slate-950/60">
                <div class="flex items-center gap-2">
                    <i class="fas fa-paperclip text-emerald-400 text-sm"></i>
                    <span id="evidenceModalTitle" class="text-xs font-semibold text-slate-200 truncate max-w-xs md:max-w-md">File Preview</span>
                </div>
                <button type="button" onclick="closeEvidenceModal()" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Body Konten (Preview Gambar / PDF) -->
            <div id="evidenceModalBody" class="p-3 overflow-auto flex items-center justify-center min-h-[250px] bg-slate-950/40">
                <!-- Konten gambar / dokumen disuntik via JS -->
            </div>
        </div>
    </div>

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script>
        // --- A. INISIALISASI TOM SELECT (PIC) ---
        var tomSelectPicCreate, tomSelectPicEdit;

        document.addEventListener('turbo:load', function() {
            // 1. Logika untuk Modal EDIT
            const editPlant = document.getElementById('edit_plant');
            const editMachine = document.getElementById('edit_machine');
            if (editPlant && editMachine) {
                editPlant.addEventListener('change', function() {
                    if (this.value === 'RUANG AUTOMATION') {
                        editMachine.value = 'Markas Besar JIS Automation';
                    }
                });
            }

            // 2. Logika untuk Modal CREATE
            const createPlant = document.getElementById('create_plant');
            const createMachine = document.getElementById('create_machine') || document.querySelector('input[name="machine_name"]');
            if (createPlant && createMachine) {
                createPlant.addEventListener('change', function() {
                    if (this.value === 'RUANG AUTOMATION') {
                        createMachine.value = 'Markas Besar JIS Automation';
                    }
                });
            }
        });

        (function() {
            // Init TomSelect Modal Create
            const configTom = { plugins: ['remove_button'], create: false, maxItems: 5, placeholder: "Pick Person..." };
            if (document.getElementById('create_pic')) {
                tomSelectPicCreate = new TomSelect("#create_pic", configTom);
            }
            if (document.getElementById('create_pic_dashboard')) {
                new TomSelect("#create_pic_dashboard", configTom);
            }
            
            // Init TomSelect Modal Edit
            if (document.getElementById('edit_pic')) {
                tomSelectPicEdit = new TomSelect("#edit_pic", configTom);
            }

            // Logic Pagination & File Preview
            initTablePagination();
            setupFileUpload('file_evidence', 'file-name-create');
            setupFileUpload('file_evidence_edit', 'file-name-edit');
        })();

        // --- B. TEMPLATE AUTO-NUMBERING PADA INPUT ACTIVITY ---
        function initActionTakenAutoNumber() {
            const actionArea = document.getElementById('action_taken');

            // 1. Tangani tombol pembuka modal Input Daily Activity
            document.querySelectorAll('.btn-input-laporan').forEach(btn => {
                // Hapus listener lama jika ada agar tidak double
                btn.onclick = function () {
                    setTimeout(() => {
                        const targetArea = document.getElementById('action_taken');
                        if (targetArea && !targetArea.value.trim()) {
                            targetArea.value = "1. \n2. \n3. ";
                        }
                    }, 100); // Beri jeda 100ms agar form.reset() selesai dulu
                };
            });

            // 2. Logika Auto-Numbering saat tombol Enter ditekan
            if (actionArea && !actionArea.dataset.enterBound) {
                actionArea.dataset.enterBound = "true"; // Flag pencegah duplikasi listener

                actionArea.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();

                        const cursorPos = this.selectionStart;
                        const textBefore = this.value.substring(0, cursorPos);
                        const textAfter = this.value.substring(cursorPos);

                        const lines = textBefore.split('\n');
                        const lastLine = lines[lines.length - 1];
                        const match = lastLine.match(/^(\d+)[\.\)]\s*/);

                        let nextNumberStr = '\n';
                        if (match) {
                            const nextNum = parseInt(match[1], 10) + 1;
                            nextNumberStr += `${nextNum}. `;
                        }

                        this.value = textBefore + nextNumberStr + textAfter;
                        this.selectionStart = this.selectionEnd = cursorPos + nextNumberStr.length;
                    }
                });
            }
        }

        // Jalankan pada Turbo load dan DOMContentLoaded agar selalu aktif
        document.addEventListener('turbo:load', initActionTakenAutoNumber);
        document.addEventListener('DOMContentLoaded', initActionTakenAutoNumber);

        // --- C. FUNGSI EDIT REPORT ---
        function editReport(id, date, end_date, plant, shift, start, finish, machine, cat, prob, act, pic, part, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_end_date').value = end_date;
            document.getElementById('edit_start').value = start;
            document.getElementById('edit_finish').value = finish;
            document.getElementById('edit_machine').value = machine;
            
            const setVal = (id, val) => { if(document.getElementById(id)) document.getElementById(id).value = val; };
            setVal('edit_plant', plant);
            setVal('edit_shift', shift);
            setVal('edit_cat', cat);
            setVal('edit_status', status);

            document.getElementById('edit_problem').value = prob.split("_ENTER_").join("\n");
            document.getElementById('edit_action').value = act.split("_ENTER_").join("\n");
            document.getElementById('edit_part').value = part.split("_ENTER_").join("\n");

            if (tomSelectPicEdit) {
                tomSelectPicEdit.clear();
                if (pic) {
                    const names = pic.split(',').map(item => item.trim());
                    names.forEach(name => tomSelectPicEdit.addItem(name));
                }
            }
            openModal('modalEditReport');
        }

        // --- D. FUNGSI DELETE REPORT ---
        function confirmDeleteReport(id) {
            Swal.fire({
                title: 'Delete Report?',
                text: "Data cannot be recovered!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, Delete!'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'delete/delete_report.php?id=' + id;
            });
        }

        function downloadExcel() {
            // Ambil nilai dari input filter di dashboard
            const searchVal = document.getElementById('searchInput')?.value || document.querySelector('input[placeholder*="Search"]')?.value || '';
            const startVal  = document.getElementById('startDate')?.value || document.querySelector('input[name="start_date"]')?.value || '';
            const endVal    = document.getElementById('endDate')?.value || document.querySelector('input[name="end_date"]')?.value || '';

            let url = 'export/export_laporan.php?export=true';
            
            if (searchVal) url += '&search=' + encodeURIComponent(searchVal);
            if (startVal)  url += '&start=' + encodeURIComponent(startVal);
            if (endVal)    url += '&end=' + encodeURIComponent(endVal);

            window.location.href = url;
        }

        // --- F. HELPER: FILE UPLOAD PREVIEW ---
        function setupFileUpload(inputId, displayId) {
            const fileInput = document.getElementById(inputId);
            const fileNameDisplay = document.getElementById(displayId);
            if (fileInput && fileNameDisplay) {
                fileInput.addEventListener('change', function() {
                    const files = this.files;
                    if (files.length > 20) {
                        Swal.fire({ icon: 'warning', title: 'Maximum 20 File!', background: '#1e293b', color: '#fff' });
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

        // --- G. HELPER: TABLE PAGINATION & FILTER ---
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

                const filteredRows = allRows.filter(row => {
                    const textMatch = row.textContent.toLowerCase().includes(searchText);
                    let dateMatch = true;
                    const rowDate = row.getAttribute('data-date');
                    if (startVal && rowDate < startVal) dateMatch = false;
                    if (endVal && rowDate > endVal) dateMatch = false;
                    return textMatch && dateMatch;
                });

                const totalItems = filteredRows.length;
                const totalPages = Math.ceil(totalItems / rowsPerPage);
                if (currentPage > totalPages) currentPage = 1;
                
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                tableBody.querySelectorAll('tr').forEach(tr => tr.style.display = 'none');
                filteredRows.slice(start, end).forEach(row => row.style.display = '');

                if(pageInfo) pageInfo.innerText = `Showing ${totalItems === 0 ? 0 : start + 1} - ${Math.min(end, totalItems)} of ${totalItems} reports`;
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

            [searchInput, startDateInput, endDateInput].forEach(el => {
                if(el) {
                    el.addEventListener('input', () => { 
                        currentPage = 1; 
                        renderTable(); 
                    });
                }
            });
            renderTable();
        }

        // Check All Checkbox Listener
        document.getElementById('checkAllReports')?.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.report-item-check').forEach(cb => cb.checked = isChecked);
        });

        var isManageModeActive = false;
        // --- H. FUNGSI GENERATOR LAPORAN (PROJECT PREPARATION & EXECUTION) ---
        function openProjectReportModal() {
            isManageModeActive = false;
            const btnManage = document.getElementById('btnToggleManage');
            if (btnManage) {
                btnManage.className = "px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-400 text-[11px] rounded font-medium border border-slate-700 transition flex items-center gap-1.5";
                btnManage.innerHTML = `<i class="fas fa-magic"></i> <span id="labelToggleManage">Edit Data</span>`;
            }
            
            const checkedItems = document.querySelectorAll('.report-item-check:checked');
            if (checkedItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Reports',
                    text: 'At least one report to generate the project report.',
                    background: '#0f172a',
                    color: '#fff'
                });
                return;
            }

            const prepContainer = document.getElementById('prepContainer');
            const execContainer = document.getElementById('selectedActivitiesList');
            if (prepContainer) prepContainer.innerHTML = '';
            if (execContainer) execContainer.innerHTML = '';

            const selectedIds = [];
            let firstMachine = '';
            let firstPlant = '';
            const picList = [];

            // Struktur penampung sementara untuk pembagian bobot otomatis
            const parsedPrepItems = [];
            const parsedExecItems = [];

            // 1. Ekstrak Data Checkbox
            checkedItems.forEach((cb, index) => {
                const currentId = cb.value;
                if (currentId) selectedIds.push(currentId);

                if (index === 0) {
                    firstMachine = cb.getAttribute('data-machine') || '';
                    firstPlant = cb.getAttribute('data-plant') || '';
                }

                // Kumpulkan PIC Unik untuk info global header
                const rawPic = cb.getAttribute('data-pic') || '';
                if (rawPic) {
                    rawPic.split(',').forEach(name => {
                        const clean = name.trim();
                        if (clean && !picList.includes(clean)) {
                            picList.push(clean);
                        }
                    });
                }

                const startDate = cb.getAttribute('data-date') || '';
                const endDate = cb.getAttribute('data-end-date') || startDate;
                const itemNotes = cb.getAttribute('data-notes') || '-';
                const rawAction = (cb.getAttribute('data-action') || '').trim();
                const rawProblem = (cb.getAttribute('data-problem') || '').trim();
                const category = (cb.getAttribute('data-category') || '').trim();
                const isPrep = (category === 'Project Preparation');

                // Hitung durasi hari: (endDate - startDate) + 1 hari
                let calculatedDays = 1;
                if (startDate && endDate) {
                    const d1 = new Date(startDate);
                    const d2 = new Date(endDate);
                    const diffTime = d2.getTime() - d1.getTime();
                    calculatedDays = Math.max(1, Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1);
                }

                // Parser Pemotong Teks Berdasarkan Nomor / Enter
                let items = [];
                const textToParse = rawAction || rawProblem;

                if (/\d+[\.\)]/.test(textToParse)) {
                    items = textToParse.split(/(?:^|\r?\n|\s+)(?=\d+[\.\)])/)
                        .map(item => item.replace(/^\d+[\.\)]\s*/, '').trim())
                        .filter(item => item.length > 0);
                } else {
                    items = textToParse.split(/\r?\n/)
                        .map(l => l.replace(/^[-*•]\s*/, '').trim())
                        .filter(l => l.length > 0);
                }

                if (items.length === 0) {
                    items = [textToParse || 'Activity Log'];
                }

                items.forEach(actionText => {
                    const itemObj = {
                        reportId: currentId,
                        activity: actionText,
                        startDate: startDate,
                        endDate: endDate,
                        days: calculatedDays,
                        notes: itemNotes
                    };

                    if (isPrep) {
                        parsedPrepItems.push(itemObj);
                    } else {
                        parsedExecItems.push(itemObj);
                    }
                });
            }); // <-- checkedItems selesai di sini

            // --- URUTKAN DARI TANGGAL TERLAMA KE TERBARU (KRONOLOGIS) ---
            const sortByDateAsc = (a, b) => new Date(a.startDate) - new Date(b.startDate);
            parsedPrepItems.sort(sortByDateAsc);
            parsedExecItems.sort(sortByDateAsc);

            // 2. Hitung Bobot Rata Agar Genap 18% & 82% (Total Tepat 100%)
            function distributeWeight(totalWeight, count) {
                if (count === 0) return [];
                const base = Math.floor(totalWeight / count);
                const remainder = totalWeight % count;
                const result = new Array(count).fill(base);
                for (let i = 0; i < remainder; i++) {
                    result[i] += 1;
                }
                return result;
            }

            const prepWeights = distributeWeight(18, parsedPrepItems.length);
            const execWeights = distributeWeight(82, parsedExecItems.length);

            // 3. Render Wadah 1: Project Preparation (Cukup 1 kali)
            parsedPrepItems.forEach((item, idx) => {
                addPreparationRow({
                    activity: item.activity,
                    progress: prepWeights[idx] || 0,
                    start_date: item.startDate,
                    end_date: item.endDate,
                    days: item.days,
                    status: 'OK',
                    notes: item.notes
                });
            });

            // 4. Render Wadah 2: Project Execution
            parsedExecItems.forEach((item, idx) => {
                if (!execContainer) return;
                const rowId = 'exec_' + Date.now() + Math.random().toString(36).substring(2, 6);
                const row = document.createElement('div');
                row.id = `row_${rowId}`;
                row.className = 'p-2.5 bg-slate-900/90 border border-slate-800 rounded-lg text-xs transition';

                const progress = execWeights[idx] || 0;

                row.innerHTML = `
                    <!-- MODE RINGKAS (DEFAULT VIEW) -->
                    <div id="view_${rowId}" class="flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0 flex items-center gap-2">
                            <span class="font-mono text-cyan-400 font-semibold text-[11px] shrink-0">${progress}%</span>
                            <span id="label_act_${rowId}" class="text-slate-200 truncate font-medium" title="${item.activity}">${item.activity}</span>
                        </div>
                        
                        <div class="flex items-center gap-2.5 shrink-0">
                            <span class="text-[11px] font-mono text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                                ${item.startDate} s/d ${item.endDate} (${item.days}d)
                            </span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                OK
                            </span>
                            <span class="text-[11px] text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800 max-w-[120px] truncate" title="${item.notes}">
                                ${item.notes}
                            </span>

                            <!-- Action Button (Hanya class hidden tanpa flex) -->
                            <div class="row-actions hidden items-center gap-1 border-l border-slate-800 pl-2">
                                <button type="button" onclick="toggleRowEdit('${rowId}')" class="p-1 text-cyan-400 hover:text-cyan-300 transition" title="Edit Baris">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button type="button" onclick="document.getElementById('row_${rowId}').remove()" class="p-1 text-rose-400 hover:text-rose-300 transition" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- MODE EDIT INTERAKTIF (AWALNYA TERSEMBUNYI) -->
                    <div id="edit_${rowId}" class="hidden flex flex-col lg:flex-row items-start lg:items-center justify-between gap-2 pt-1">
                        <div class="flex-1 min-w-0 w-full">
                            <input type="text" name="exec_activity[${item.reportId}][]" value="${item.activity.replace(/"/g, '&quot;')}" required
                                oninput="document.getElementById('label_act_${rowId}').innerText = this.value"
                                class="w-full bg-slate-950 border border-slate-700 focus:border-cyan-500 rounded px-2.5 py-1 text-slate-200 text-xs focus:outline-none">
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 w-full lg:w-auto">
                            <div class="flex items-center gap-1 bg-slate-950 border border-slate-700 rounded px-2 py-0.5">
                                <input type="date" id="exec_start_${rowId}" name="exec_start_date[${item.reportId}][]" value="${item.startDate}" required
                                    onchange="updateExecDuration('${rowId}')"
                                    class="w-[115px] bg-transparent text-cyan-400 font-mono text-[11px] focus:outline-none cursor-pointer">
                                <span class="text-slate-500 text-[10px]">-</span>
                                <input type="date" id="exec_end_${rowId}" name="exec_end_date[${item.reportId}][]" value="${item.endDate}" required
                                    onchange="updateExecDuration('${rowId}')"
                                    class="w-[115px] bg-transparent text-cyan-400 font-mono text-[11px] focus:outline-none cursor-pointer">
                            </div>
                            <input type="hidden" id="exec_days_${rowId}" name="exec_days[${item.reportId}][]" value="${item.days}">
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="flex items-center gap-1 bg-slate-950 border border-slate-700 rounded px-1.5 py-0.5">
                                <input type="number" name="exec_progress[${item.reportId}][]" value="${progress}" min="0" max="100" 
                                    class="w-8 bg-transparent text-center text-white text-[11px] focus:outline-none">
                                <span class="text-slate-400 text-[10px]">%</span>
                            </div>
                            <select name="exec_status[${item.reportId}][]" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-slate-300 text-[11px] focus:outline-none">
                                <option value="OK">OK</option>
                                <option value="B.OK">B.OK</option>
                            </select>
                            <input type="text" name="exec_notes[${item.reportId}][]" value="${item.notes.replace(/"/g, '&quot;')}" placeholder="Notes..."
                                class="w-24 bg-slate-950 border border-slate-700 rounded px-2 py-1 text-slate-300 text-xs focus:outline-none">
                            
                            <button type="button" onclick="toggleRowEdit('${rowId}')" class="px-2 py-1 bg-cyan-600 hover:bg-cyan-500 text-white rounded text-[10px] font-semibold" title="Finish Edit">
                                OK
                            </button>
                        </div>
                    </div>
                `;
                execContainer.appendChild(row);
                updateExecDuration(rowId);
            });

            // Set Data ke Input Global Modal
            const hiddenIds = document.getElementById('report_selected_ids');
            if (hiddenIds) hiddenIds.value = selectedIds.join(',');

            const countLabel = document.getElementById('totalSelectedCount');
            if (countLabel) countLabel.innerText = checkedItems.length;

            const machineInput = document.getElementById('modal_machine_name');
            if (machineInput) machineInput.value = firstMachine;

            const plantInput = document.getElementById('modal_plant_name');
            if (plantInput) plantInput.value = firstPlant;

            const prepInput = document.getElementById('modal_prepared_by');
            if (prepInput) {
                prepInput.value = picList.length > 0 ? picList.join(', ') : 'Danang Widya Pratama';
            }

            openModal('modalProjectReport');
        }

        function toggleGlobalEditMode() {
            isManageModeActive = !isManageModeActive;
            
            const btn = document.getElementById('btnToggleManage');
            const label = document.getElementById('labelToggleManage');
            const actionContainers = document.querySelectorAll('.row-actions');

            if (isManageModeActive) {
                // Aktifkan mode kelola: munculkan tombol pensil & trash di setiap baris
                actionContainers.forEach(el => {
                    el.classList.remove('hidden');
                    el.classList.add('flex');
                });

                if (btn) {
                    btn.className = "px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] rounded font-semibold border border-emerald-500 transition flex items-center gap-1.5 shadow-sm";
                    btn.innerHTML = `<i class="fas fa-check"></i> <span id="labelToggleManage">Finish Edit</span>`;
                }
            } else {
                // Matikan mode kelola: sembunyikan tombol pensil & trash
                actionContainers.forEach(el => {
                    el.classList.remove('flex');
                    el.classList.add('hidden');
                });

                // Kembalikan semua input form aktif ke mode ringkas jika ada yang masih terbuka
                document.querySelectorAll('[id^="edit_"]').forEach(el => el.classList.add('hidden'));
                document.querySelectorAll('[id^="view_"]').forEach(el => el.classList.remove('hidden'));

                if (btn) {
                    btn.className = "px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-400 text-[11px] rounded font-medium border border-slate-700 transition flex items-center gap-1.5";
                    btn.innerHTML = `<i class="fas fa-magic"></i> <span id="labelToggleManage">Edit Data</span>`;
                }
            }
        }

        // Helper Fungsi Durasi (Preparation & Execution)
        function updatePrepDuration(rowId) {
            const start = document.getElementById(`prep_start_${rowId}`);
            const end = document.getElementById(`prep_end_${rowId}`);
            const days = document.getElementById(`prep_days_${rowId}`);
            if (start && end && days && start.value && end.value) {
                const diffDays = Math.ceil((new Date(end.value) - new Date(start.value)) / (1000 * 60 * 60 * 24)) + 1;
                days.value = diffDays > 0 ? diffDays : 1;
            }
        }

        function updateExecDuration(rowId) {
            const start = document.getElementById(`exec_start_${rowId}`);
            const end = document.getElementById(`exec_end_${rowId}`);
            const days = document.getElementById(`exec_days_${rowId}`);
            if (start && end && days && start.value && end.value) {
                const diffDays = Math.ceil((new Date(end.value) - new Date(start.value)) / (1000 * 60 * 60 * 24)) + 1;
                days.value = diffDays > 0 ? diffDays : 1;
            }
        }

        function addPreparationRow(data = null) {
            const prepContainer = document.getElementById('prepContainer');
            if (!prepContainer) return;

            const rowId = 'prep_' + Date.now() + Math.random().toString(36).substring(2, 6);
            const row = document.createElement('div');
            row.id = `row_${rowId}`;
            row.className = 'p-2.5 bg-slate-900/90 border border-slate-800 rounded-lg text-xs transition';

            const activity  = data?.activity || '';
            const progress  = data?.progress || 0;
            const status    = data?.status || 'OK';
            const notes     = data?.notes || '-';
            const startDate = data?.start_date || '';
            const endDate   = data?.end_date || startDate;
            const days      = data?.days || 1;

            row.innerHTML = `
                <!-- MODE RINGKAS (DEFAULT VIEW) -->
                <div id="view_${rowId}" class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0 flex items-center gap-2">
                        <span class="font-mono text-emerald-400 font-semibold text-[11px] shrink-0">${progress}%</span>
                        <span id="label_act_${rowId}" class="text-slate-200 truncate font-medium" title="${activity}">${activity}</span>
                    </div>
                    
                    <div class="flex items-center gap-2.5 shrink-0">
                        <span class="text-[11px] font-mono text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800">
                            ${startDate} s/d ${endDate} (${days}d)
                        </span>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded ${status === 'OK' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'}">
                            ${status}
                        </span>
                        <span class="text-[11px] text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-slate-800 max-w-[120px] truncate" title="${notes}">
                            ${notes}
                        </span>

                        <!-- Action Button -->
                        <div class="row-actions hidden items-center gap-1 border-l border-slate-800 pl-2">
                            <button type="button" onclick="toggleRowEdit('${rowId}')" class="p-1 text-cyan-400 hover:text-cyan-300 transition" title="Edit Baris">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <button type="button" onclick="document.getElementById('row_${rowId}').remove()" class="p-1 text-rose-400 hover:text-rose-300 transition" title="Hapus">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- MODE EDIT INTERAKTIF (AWALNYA TERSEMBUNYI) -->
                <div id="edit_${rowId}" class="hidden flex flex-col lg:flex-row items-start lg:items-center justify-between gap-2 pt-1">
                    <div class="flex-1 min-w-0 w-full">
                        <input type="text" name="prep_activity[]" value="${activity.replace(/"/g, '&quot;')}" required
                            oninput="document.getElementById('label_act_${rowId}').innerText = this.value"
                            class="w-full bg-slate-950 border border-slate-700 focus:border-cyan-500 rounded px-2.5 py-1 text-slate-200 text-xs focus:outline-none">
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0 w-full lg:w-auto">
                        <div class="flex items-center gap-1 bg-slate-950 border border-slate-700 rounded px-2 py-0.5">
                            <input type="date" id="prep_start_${rowId}" name="prep_start_date[]" value="${startDate}" required
                                onchange="updatePrepDuration('${rowId}')"
                                class="w-[115px] bg-transparent text-cyan-400 font-mono text-[11px] focus:outline-none cursor-pointer">
                            <span class="text-slate-500 text-[10px]">-</span>
                            <input type="date" id="prep_end_${rowId}" name="prep_end_date[]" value="${endDate}" required
                                onchange="updatePrepDuration('${rowId}')"
                                class="w-[115px] bg-transparent text-cyan-400 font-mono text-[11px] focus:outline-none cursor-pointer">
                        </div>
                        <input type="hidden" id="prep_days_${rowId}" name="prep_days[]" value="${days}">
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="flex items-center gap-1 bg-slate-950 border border-slate-700 rounded px-1.5 py-0.5">
                            <input type="number" name="prep_progress[]" value="${progress}" min="0" max="100" 
                                class="w-8 bg-transparent text-center text-white text-[11px] focus:outline-none">
                            <span class="text-slate-400 text-[10px]">%</span>
                        </div>
                        <select name="prep_status[]" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-slate-300 text-[11px] focus:outline-none">
                            <option value="OK" ${status === 'OK' ? 'selected' : ''}>OK</option>
                            <option value="B.OK" ${status === 'B.OK' ? 'selected' : ''}>B.OK</option>
                        </select>
                        <input type="text" name="prep_notes[]" value="${notes.replace(/"/g, '&quot;')}" placeholder="Notes..."
                            class="w-24 bg-slate-950 border border-slate-700 rounded px-2 py-1 text-slate-300 text-xs focus:outline-none">
                        
                        <button type="button" onclick="toggleRowEdit('${rowId}')" class="px-2 py-1 bg-cyan-600 hover:bg-cyan-500 text-white rounded text-[10px] font-semibold" title="Selesai Edit">
                            OK
                        </button>
                    </div>
                </div>
            `;

            prepContainer.appendChild(row);
        }

        // --- I. MODAL LIGHTBOX EVIDENCE ---
        function openEvidenceModal(fileUrl, fileName) {
            const modal = document.getElementById('modalViewEvidence');
            const title = document.getElementById('evidenceModalTitle');
            const body  = document.getElementById('evidenceModalBody');
            if (!modal || !body) return;

            title.innerText = fileName || 'File Preview';
            const isPdf = fileUrl.toLowerCase().endsWith('.pdf');

            if (isPdf) {
                body.innerHTML = `<iframe src="${fileUrl}" class="w-full h-[70vh] rounded-lg border border-slate-800" frameborder="0"></iframe>`;
            } else {
                body.innerHTML = `<img src="${fileUrl}" alt="Evidence" class="max-h-[75vh] w-auto max-w-full rounded-lg object-contain shadow-md">`;
            }
            modal.classList.remove('hidden');
        }

        function closeEvidenceModal() {
            const modal = document.getElementById('modalViewEvidence');
            const body  = document.getElementById('evidenceModalBody');
            if (modal) {
                modal.classList.add('hidden');
                if (body) body.innerHTML = '';
            }
        }

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('modalViewEvidence');
            if (e.target === modal) {
                closeEvidenceModal();
            }
        });

        function toggleRowEdit(rowId) {
            const viewEl = document.getElementById(`view_${rowId}`);
            const editEl = document.getElementById(`edit_${rowId}`);
            if (!viewEl || !editEl) return;

            if (editEl.classList.contains('hidden')) {
                // Buka mode edit
                editEl.classList.remove('hidden');
                viewEl.classList.add('hidden');
            } else {
                // Simpan / Tutup mode edit (sinkronkan teks tampilan view)
                editEl.classList.add('hidden');
                viewEl.classList.remove('hidden');
            }
        }

        function initReportUploadBar() {
            const form = document.getElementById('formAddReport'); // ID form Daily Report
            if (!form || form.dataset.boundProgress) return;
            form.dataset.boundProgress = "true";

            form.addEventListener('submit', function (e) {
                // Tahan submit default browser agar halaman tidak langsung reload
                e.preventDefault();

                const formData    = new FormData(form);
                const progressBox = document.getElementById('uploadProgressContainer');
                const progressBar = document.getElementById('uploadProgressBar');
                const percentText = document.getElementById('uploadPercentText');
                const statusText  = document.getElementById('uploadStatusText');
                const submitBtn   = form.querySelector('button[type="submit"]');

                // Tampilkan progress bar seketika
                if (progressBox) progressBox.classList.remove('hidden');
                if (progressBar) progressBar.style.width = '10%';
                if (percentText) percentText.innerText = '10%';
                if (statusText)  statusText.innerText = 'Uploading Report & Evidence...';

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.getAttribute('action'), true);

                // Pantau progres data yang terkirim secara real-time
                xhr.upload.onprogress = function (event) {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        const visualPercent = Math.max(percent, 15);
                        if (progressBar) progressBar.style.width = visualPercent + '%';
                        if (percentText) percentText.innerText = visualPercent + '%';

                        if (percent >= 100 && statusText) {
                            statusText.innerText = 'Saving file to server...';
                        }
                    }
                };

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        if (progressBar) progressBar.style.width = '100%';
                        if (percentText) percentText.innerText = '100%';
                        if (statusText)  statusText.innerText = 'Upload Complete!';

                        if (typeof closeModal === 'function') {
                            closeModal('modalAddReport');
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Report saved successfully.',
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#0891b2',
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            setTimeout(() => { window.location.reload(); }, 600);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed!',
                                text: 'Failed to upload report. Please try again.',
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#ef4444'
                            });
                        } else {
                            alert('Failed to upload report.');
                        }
                        resetUploadState();
                    }
                };

                xhr.onerror = function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Error!',
                            text: 'Failed to upload report. Please check your network connection.',
                            background: '#0f172a',
                            color: '#f8fafc',
                            confirmButtonColor: '#ef4444'
                        });
                    } else {
                        alert('Connection Error!');
                    }
                    resetUploadState();
                };

                function resetUploadState() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    if (progressBox) progressBox.classList.add('hidden');
                    if (progressBar) progressBar.style.width = '0%';
                }

                xhr.send(formData);
            });
        }

        function initReportEditBar() {
            const formEdit = document.getElementById('formEditReport');
            if (!formEdit || formEdit.dataset.boundEditProgress) return;
            formEdit.dataset.boundEditProgress = "true";

            formEdit.addEventListener('submit', function (e) {
                e.preventDefault(); // Tahan submit bawaan

                const fileInput  = formEdit.querySelector('input[type="file"]');
                const hasNewFile = fileInput && fileInput.files.length > 0;

                const formData    = new FormData(formEdit);
                const progressBox = document.getElementById('uploadProgressContainerEdit');
                const progressBar = document.getElementById('uploadProgressBarEdit');
                const percentText = document.getElementById('uploadPercentTextEdit');
                const statusText  = document.getElementById('uploadStatusTextEdit');
                const submitBtn   = formEdit.querySelector('button[type="submit"]');

                // Always show progress bar on submit so user gets feedback
                if (progressBox) progressBox.classList.remove('hidden');
                if (progressBar) progressBar.style.width = '25%';
                if (percentText) percentText.innerText = '25%';
                if (statusText)  statusText.innerText = hasNewFile ? 'Uploading file...' : 'Updating report data...';

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', formEdit.getAttribute('action') || 'process/process_edit_report.php', true);

                // Track upload progress if file is attached
                if (hasNewFile) {
                    xhr.upload.onprogress = function (event) {
                        if (event.lengthComputable) {
                            const percent = Math.round((event.loaded / event.total) * 100);
                            const visualPercent = Math.max(percent, 25);
                            if (progressBar) progressBar.style.width = visualPercent + '%';
                            if (percentText) percentText.innerText = visualPercent + '%';

                            if (percent >= 100 && statusText) {
                                statusText.innerText = 'Saving changes to server...';
                            }
                        }
                    };
                } else {
                    // Fake visual step if updating text only
                    setTimeout(() => {
                        if (progressBar) progressBar.style.width = '75%';
                        if (percentText) percentText.innerText = '75%';
                    }, 150);
                }

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        if (progressBar) progressBar.style.width = '100%';
                        if (percentText) percentText.innerText = '100%';
                        if (statusText)  statusText.innerText = 'Updated successfully!';

                        // Give 400ms delay so animation is clearly visible
                        setTimeout(() => {
                            if (typeof closeModal === 'function') {
                                closeModal('modalEditReport');
                            }

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Report updated successfully.',
                                    background: '#0f172a',
                                    color: '#f8fafc',
                                    confirmButtonColor: '#0891b2',
                                    timer: 1600,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                window.location.reload();
                            }
                        }, 400);
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed!',
                                text: 'Failed to update report.',
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#ef4444'
                            });
                        } else {
                            alert('Failed to update report.');
                        }
                        resetEditUi();
                    }
                };

                xhr.onerror = function () {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Error!',
                            text: 'Network error while updating report.',
                            background: '#0f172a',
                            color: '#f8fafc',
                            confirmButtonColor: '#ef4444'
                        });
                    } else {
                        alert('Connection Error!');
                    }
                    resetEditUi();
                };

                function resetEditUi() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    if (progressBox) progressBox.classList.add('hidden');
                    if (progressBar) progressBar.style.width = '0%';
                }

                xhr.send(formData);
            });
        }

        // Pasang pemanggilan fungsi di event listener
        document.addEventListener('DOMContentLoaded', function() {
            initReportUploadBar();
            initReportEditBar();
        });

        document.addEventListener('turbo:load', function() {
            initReportUploadBar();
            initReportEditBar();
        });
    </script>
</body>

</html>