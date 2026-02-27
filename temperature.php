<?php
include 'layouts/auth_and_config.php';

$pageTitle = "Motor Temperature";

// --- LOGIKA SERVER-SIDE PAGINATION & SEARCH ---
$limit = 25; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : '';
$offset = ($page - 1) * $limit;

$conditions = [];

if (!empty($search)) {
    $conditions[] = "(mesin LIKE '%$search%' OR motor LIKE '%$search%')";
}

if (!empty($start_date) && !empty($end_date)) {
    // Karena format tanggal di database biasanya YYYY-MM-DD
    $conditions[] = "tanggal BETWEEN '$start_date' AND '$end_date'";
}

// Filter Pencarian
$whereClause = "";
if (count($conditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Hitung Total Data (Untuk Pagination)
$countQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_temperature $whereClause");
$countRow = mysqli_fetch_assoc($countQuery);
$totalData = $countRow['total'];
$totalPages = ceil($totalData / $limit);

$urlParams = ($search != '') ? "&search=" . urlencode($search) : "";

// CSS khusus untuk print (diadaptasi dari struktur Bapak)
$extraMenu = '
    <div class="flex items-center gap-4">
        <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4">
            Total Items: <span class="text-cyan-400 font-bold">' . number_format($totalData) . '</span>
        </div>';

$extraHead = '
    <style>
        @media print {
            #sidebar, header, #paginationContainer, .btn-action, #searchInput, button {
                display: none !important;
            }
            body, #main-content {
                background-color: white !important;
                color: black !important;
                overflow: visible !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .bg-slate-800 { background-color: white !important; border: 1px solid #ccc !important; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; }
            th, td { border: 1px solid #000 !important; padding: 5px !important; color: black !important; }
            .text-white, .text-slate-400, .text-emerald-400 { color: black !important; }
            th:last-child, td:last-child { display: none !important; }
        }
    </style>
';
?>

<head>
    <meta name="turbo-cache-control" content="no-preview">
</head>

<!DOCTYPE html>
<html lang="id">

<?php include 'layouts/head.php'; ?>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
<?php include 'layouts/sidebar.php'; ?>

<main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">
    <?php include 'layouts/header.php'; ?>

        <div class="p-8 space-y-6 fade-in">
            <div class="space-y-6">
                <!-- <div class="border-b border-slate-700 pb-4">
                    <h1 class="text-2xl font-bold text-white mb-1"><i class="fas fa-thermometer-half text-rose-500 mr-2"></i>Motor - Temperature Measurement</h1>
                    <p class="text-sm text-slate-400">Monitoring suhu elemen (DE, Body, NDE) pada motor secara berkala.</p>
                </div> -->
                <div class="mb-6">
                    <div class="mb-4">
                        <!-- <h1 class="text-2xl font-bold text-white mb-1"><i class="fas fa-chart-pie text-cyan-500 mr-2"></i> //Motor - Condition Monitoring</h1>
                        <p class="text-sm text-slate-400">Pemantauan berkala untuk parameter Suhu dan Getaran pada motor.</p> -->
                    </div>

                    <div class="border-b border-slate-700">
                        <nav class="-mb-px flex gap-8" aria-label="Tabs">
                            <a href="temperature.php" class="border-cyan-500 text-cyan-400 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-bold flex items-center gap-2">
                                <i class="fas fa-thermometer-half"></i> Temperature
                            </a>

                            <a href="vibration.php" class="border-transparent text-slate-400 hover:border-slate-500 hover:text-slate-300 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium flex items-center gap-2 transition-colors">
                                <i class="fas fa-wave-square"></i> Vibration
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4"> 
    
                    <div class="grid grid-cols-2 md:flex md:flex-wrap items-center gap-2 w-full xl:w-auto">
                        <button onclick="openModal('modalAddTemp')" class="h-[42px] w-full md:w-auto bg-cyan-600 hover:bg-cyan-500 text-white px-4 rounded-lg text-sm font-medium transition shadow-lg shadow-cyan-600/20 flex items-center justify-center gap-2">
                            <i class="fas fa-plus"></i> <span class="whitespace-nowrap">New Record</span>
                        </button>

                        <button onclick="document.getElementById('modalLastRecord').classList.remove('hidden')" class="h-[42px] w-full md:w-auto bg-cyan-600 hover:bg-cyan-500 text-white px-4 rounded-lg text-sm font-medium transition shadow-lg shadow-cyan-600/20 flex items-center justify-center gap-2">
                            <i class="fas fa-clock"></i> <span class="whitespace-nowrap">Last Record</span>
                        </button>

                        <button id="btnBackToTable" onclick="toggleView('table')" class="col-span-2 hidden h-[42px] w-full md:w-auto bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 rounded-lg border border-slate-700 text-sm transition font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-table text-cyan-400"></i> <span class="whitespace-nowrap">Data Record</span>
                        </button>

                        <button  id="btnViewGraph" onclick="toggleView('graph')" class="col-span-2 h-[42px] w-full md:w-auto bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 rounded-lg border border-slate-700 text-sm transition font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-chart-line text-emerald-400"></i> <span class="whitespace-nowrap">View Graph</span>
                        </button>
                    </div>

                    <div class="w-full xl:w-auto">
                        <form action="temperature.php" method="GET" class="flex flex-col md:flex-row items-center gap-2 w-full" data-turbo-frame="_top">
                            
                            <div class="grid grid-cols-2 gap-2 w-full md:w-auto">
                                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="h-[42px] w-full bg-slate-800 border border-slate-700 text-slate-300 px-3 rounded-lg focus:border-cyan-500 focus:outline-none transition text-sm cursor-pointer" title="Start Date">
                                
                                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="h-[42px] w-full bg-slate-800 border border-slate-700 text-slate-300 px-3 rounded-lg focus:border-cyan-500 focus:outline-none transition text-sm cursor-pointer" title="End Date">
                            </div>

                            <div class="flex items-center gap-2 w-full md:w-auto">
                                <div class="relative flex-1 md:w-48 h-[42px]">
                                    <i class="fas fa-search absolute left-3 top-3.5 text-slate-500 text-sm"></i>
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search..." class="w-full h-full bg-slate-800 border border-slate-700 text-white pl-9 pr-4 rounded-lg focus:border-cyan-500 focus:outline-none transition text-sm" autocomplete="off">
                                </div>

                                <button type="submit" class="h-[42px] px-3.5 md:px-4 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg text-sm font-medium transition shadow-lg flex items-center justify-center flex-shrink-0" title="Terapkan Filter">
                                    <i class="fas fa-filter"></i> <span class="hidden md:inline ml-2">Filter</span>
                                </button>

                                <a href="export/export_temp.php?search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="h-[42px] px-3.5 md:px-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium transition shadow-lg flex items-center justify-center flex-shrink-0" title="Export to Excel">
                                    <i class="fas fa-file-excel"></i> <span class="hidden md:inline ml-2">Export</span>
                                </a>

                                <?php if(!empty($search) || !empty($start_date)): ?>
                                    <a href="temperature.php" class="h-[42px] w-[42px] bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white rounded-lg text-sm font-medium border border-rose-500/20 hover:border-transparent transition flex items-center justify-center flex-shrink-0" title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                        </form>
                    </div>
                </div>

            </div>

            <!-- TABEL UTAMA -->
        <div id="tableSection" class="space-y-6">
            <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-400">
                            <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                <tr>
                                    <th class="px-6 py-4 text-center">Tanggal</th>
                                    <th class="px-6 py-4">Machine</th>
                                    <th class="px-6 py-4">Motor</th>
                                    <th class="px-6 py-4 text-center">DE</th>
                                    <th class="px-6 py-4 text-center">Body</th>
                                    <th class="px-6 py-4 text-center">NDE</th>
                                    <th class="px-6 py-4">Note</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50" id="tableTempBody">
                                <?php
                                // Ambil Data dari tabel yang baru saja kita migrasi
                                // $query = mysqli_query($conn, "SELECT * FROM tb_temperature ORDER BY tanggal DESC");
                                $query = mysqli_query($conn, "SELECT * FROM tb_temperature $whereClause ORDER BY tanggal DESC LIMIT $offset, $limit");

                                if (mysqli_num_rows($query) > 0) {
                                while ($row = mysqli_fetch_assoc($query)) {
                                    $id = $row['id'];
                                    // Format Tanggal (Misal: 2021-11-10 jadi 10 Nov 2021)
                                    $tanggal_format = date('d M Y', strtotime($row['tanggal']));
                                ?>

                                    <tr class="hover:bg-slate-700/20 transition group border-l-4 border-transparent hover:border-cyan-500">
                                        <td class="px-6 py-4 text-center font-medium text-slate-300 whitespace-nowrap">
                                            <?php echo $tanggal_format; ?>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-white whitespace-nowrap">
                                            <?php echo $row['mesin']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                            <?php echo $row['motor']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center font-mono text-amber-300">
                                            <?php echo $row['de']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center font-mono text-rose-400 font-bold">
                                            <?php echo $row['body']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center font-mono text-blue-300">
                                            <?php echo $row['nde']; ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs italic text-slate-500 max-w-xs truncate">
                                            <?php echo !empty($row['note']) ? $row['note'] : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                <div class="flex items-center justify-center gap-2">
                                                    <button onclick="confirmDeleteTemp(<?php echo $id; ?>)" class="bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white px-3 py-1 rounded text-xs font-semibold border border-red-500/20 hover:border-transparent transition" title="Hapus Data">
                                                        Hapus
                                                    </button>
                                                    <button onclick="editTemp(
                                                        '<?php echo $id; ?>',
                                                        '<?php echo $row['tanggal']; ?>',
                                                        '<?php echo htmlspecialchars($row['mesin'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['motor'], ENT_QUOTES); ?>',
                                                        '<?php echo $row['de']; ?>',
                                                        '<?php echo $row['body']; ?>',
                                                        '<?php echo $row['nde']; ?>',
                                                        '<?php echo htmlspecialchars($row['note'] ?? '', ENT_QUOTES); ?>')"
                                                        class="bg-emerald-500/10 hover:bg-emerald-500 text-emerald-500 hover:text-white px-3 py-1 rounded text-xs font-semibold border border-emerald-500/20 hover:border-transparent transition" title="Edit Data">
                                                        Edit
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-600 text-xs italic"><i class="fas fa-lock"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } else {
                                    echo '<tr><td colspan="8" class="text-center py-6 text-slate-500">Tidak ada data ditemukan.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalData > 0): ?>
                        <div class="flex justify-between items-center mt-4 mb-8 px-4">
                            <div class="text-xs text-slate-500">
                                Showing <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $totalData); ?> of <?php echo $totalData; ?> entries
                            </div>

                            <div class="flex gap-1">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1 . $urlParams; ?>" class="px-3 py-1 text-xs bg-slate-700 text-slate-300 rounded hover:bg-slate-600 transition">Prev</a>
                                <?php endif; ?>

                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?page=<?php echo $i . $urlParams; ?>" class="px-3 py-1 text-xs rounded transition <?php echo ($i == $page) ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1 . $urlParams; ?>" class="px-3 py-1 text-xs bg-slate-700 text-slate-300 rounded hover:bg-slate-600 transition">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
            </div>
        </div>

            <!-- GRAFIK -->
            <div id="graphSection" class="hidden space-y-6 fade-in">
                    <div class="text-center space-y-2">
                        <h2 id="displayMachine" class="text-4xl font-bold text-white tracking-widest"></h2>
                        <div class="bg-indigo-900/50 border-y border-indigo-500/30 py-2">
                            <span id="displayMotor" class="text-indigo-300 font-semibold uppercase tracking-widest"></span>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 flex flex-wrap items-end gap-6">
                        
                        <!-- LIST AREA MESIN ADA DISINI -->
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-400 uppercase">Machine</label>
                            
                            <?php
                            $query_string = "SELECT DISTINCT mesin FROM tb_temperature WHERE mesin LIKE 'MC%-%' ORDER BY mesin ASC";
                            $q_mesin = mysqli_query($conn, $query_string);
                            $mesin_group = [];

                            if ($q_mesin && mysqli_num_rows($q_mesin) > 0) {
                                while ($row = mysqli_fetch_assoc($q_mesin)) {
                                    $nama_mesin = trim($row['mesin']);
                                    $parts = explode('-', $nama_mesin);
                                    $prefix = $parts[0]; 
                                    $mesin_group[$prefix][] = $nama_mesin;
                                }
                            }
                            ?>

                            <select id="filterMachine" class="bg-slate-900 border border-slate-700 text-white rounded-lg px-4 py-2.5 h-[42px] w-64 focus:border-cyan-500 outline-none custom-scroll shadow-inner">
                                <?php if (!empty($mesin_group)): ?>
                                    <?php foreach ($mesin_group as $grup => $list_mesin): ?>
                                        
                                        <optgroup label="Area <?php echo htmlspecialchars($grup); ?>" class="bg-slate-800 text-cyan-400 font-bold uppercase">
                                            <?php foreach ($list_mesin as $m): ?>
                                                <option value="<?php echo htmlspecialchars($m, ENT_QUOTES); ?>" class="bg-slate-900 text-slate-200 font-normal">
                                                    <?php echo htmlspecialchars($m, ENT_QUOTES); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>

                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Tidak ada data mesin</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="flex-1 space-y-3">
                            <label class="block text-xs font-bold text-slate-400 uppercase">Motor</label>
                            
                            <?php
                            // 1. KITA BUAT "KAMUS" MESIN & MOTOR DARI DATABASE (Tabel Temperature)
                            $q_map = mysqli_query($conn, "SELECT mesin, motor FROM tb_temperature WHERE mesin IS NOT NULL AND motor IS NOT NULL GROUP BY mesin, motor");
                            $motor_map = [];
                            if ($q_map && mysqli_num_rows($q_map) > 0) {
                                while($r = mysqli_fetch_assoc($q_map)){
                                    $mesin_id = trim($r['mesin']);
                                    // Hilangkan kata "Motor " agar nama di tombol tetap singkat
                                    $motor_short = trim(str_replace('Motor ', '', $r['motor']));
                                    $motor_map[$mesin_id][] = $motor_short;
                                }
                            }
                            $motor_map_json = json_encode($motor_map);
                            ?>

                            <div class="flex flex-wrap items-center gap-2.5 text-sm" id="motorRadioGroup">
                                </div>
                        </div>
                    </div>

                    <!-- CONTAINER DATA TEMPERATURE ADA DISINI -->
                    <div class="text-center space-y-4">
                        <div id="graphCards" class="text-center space-y-4 transition-all duration-300">
                        <p class="text-sm text-slate-400 italic">Last Measurement | <span id="lastDate">-</span></p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 text-center">
                                <h4 class="text-slate-400 text-xs font-bold uppercase mb-4">DE</h4>
                                <div id="colorDE" class="text-3xl font-bold text-emerald-400 transition-colors duration-300">
                                    <span id="valDE">0.0</span> <span class="text-sm text-slate-500">°C</span>
                                </div>
                            </div>

                            <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 text-center">
                                <h4 class="text-slate-400 text-xs font-bold uppercase mb-4">NDE</h4>
                                    <div id="colorNDE" class="text-3xl font-bold text-emerald-400 transition-colors duration-300">
                                        <span id="valNDE">0.0</span> <span class="text-sm text-slate-500">°C</span>
                                    </div>
                            </div>

                            <div class="bg-slate-800 p-6 rounded-lg border border-slate-700 text-center">
                                <h4 class="text-slate-400 text-xs font-bold uppercase mb-4">Body</h4>
                                <div id="colorBody" class="text-3xl font-bold text-emerald-400 transition-colors duration-300">
                                    <span id="valBody">0.0</span> <span class="text-sm text-slate-500">°C</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HEADER TEMPERATURE GRAFIK ADA DISNI -->
                    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-300"><i class="fas fa-th-large mr-2"></i> Temperature Record <span id="chartTitle"></span></h3>
                            <div class="flex gap-2">
                                <button onclick="minimizeGraph()" class="w-6 h-6 bg-cyan-600 text-white rounded text-xs"><i class="fas fa-minus"></i></button>
                                <button onclick="toggleView('table')" class="w-6 h-6 bg-rose-600 text-white rounded text-xs"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        
                    <div id="graphBody" class="transition-all duration-300 overflow-hidden">
                        <div class="h-80 w-full">
                            <canvas id="tempTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</main>

    <!-- MODAL ADD TEMPERATURE DISINI -->
    <div id="modalAddTemp" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalAddTemp')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-plus-circle text-cyan-400"></i> Tambah Record Suhu
                        </h3>
                    </div>
                    <button onclick="closeModal('modalAddTemp')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_add_temperature.php" method="POST" data-turbo="false" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Tanggal Pengecekan</label>
                            <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Limit Suhu (°C)</label>
                            <input type="number" step="0.1" name="temp_limit" value="85" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Machine</label>
                            <input list="list_mesin" name="mesin" placeholder="Pilih atau ketik mesin..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" autocomplete="off" required>
                            <datalist id="list_mesin">
                                <?php
                                $q_mesin = mysqli_query($conn, "SELECT DISTINCT mesin FROM tb_temperature WHERE mesin IS NOT NULL AND mesin != '' ORDER BY mesin ASC");
                                while($row_mesin = mysqli_fetch_assoc($q_mesin)) {
                                    echo "<option value='" . htmlspecialchars($row_mesin['mesin']) . "'>";
                                }
                                ?>
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Motor</label>
                            <select name="motor" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" required>
                                <option value="">-- Pilih Motor --</option>
                                <?php
                                // Ambil daftar motor yang tidak duplikat dari database
                                $q_motor = mysqli_query($conn, "SELECT DISTINCT motor FROM tb_temperature WHERE motor IS NOT NULL AND motor != '' ORDER BY motor ASC");
                                while($row_motor = mysqli_fetch_assoc($q_motor)) {
                                    echo "<option value='" . htmlspecialchars($row_motor['motor']) . "'>" . htmlspecialchars($row_motor['motor']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <h4 class="text-rose-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Hasil Pengukuran (°C)</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Drive End (DE)</label>
                                <input type="number" step="0.1" name="de" placeholder="0.0" class="w-full bg-slate-950 border border-slate-700 text-amber-300 font-mono rounded px-3 py-2 text-sm focus:border-amber-500 focus:outline-none" required>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Body</label>
                                <input type="number" step="0.1" name="body" placeholder="0.0" class="w-full bg-slate-950 border border-slate-700 text-rose-400 font-mono rounded px-3 py-2 text-sm focus:border-rose-500 focus:outline-none" required>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Non-Drive End (NDE)</label>
                                <input type="number" step="0.1" name="nde" placeholder="0.0" class="w-full bg-slate-950 border border-slate-700 text-blue-300 font-mono rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Note / Catatan (Opsional)</label>
                        <textarea name="note" rows="2" placeholder="Catatan temuan..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-4">
                        <button type="button" onclick="closeModal('modalAddTemp')" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg transition text-sm font-medium shadow-lg shadow-cyan-600/20">
                            <i class="fas fa-save mr-2"></i> Simpan Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT TEMPERATURE DISINI -->
    <div id="modalEditTemp" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEditTemp')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-edit text-emerald-400"></i> Edit Record Suhu
                    </h3>
                    <button onclick="closeModal('modalEditTemp')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_edit_temperature.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" id="edit_temp_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                        </div>
                        <div>
                            </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Machine</label>
                            <input id="edit_mesin" list="edit_list_mesin" name="mesin" placeholder="Pilih atau ketik mesin..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" autocomplete="off" required>
                            
                            <datalist id="edit_list_mesin">
                                <?php
                                $q_mesin = mysqli_query($conn, "SELECT DISTINCT mesin FROM tb_temperature WHERE mesin IS NOT NULL AND mesin != '' ORDER BY mesin ASC");
                                while($row_mesin = mysqli_fetch_assoc($q_mesin)) {
                                    echo "<option value='" . htmlspecialchars($row_mesin['mesin']) . "'>";
                                }
                                ?>
                            </datalist>
                        </div>
                        
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Motor</label>
                            <select id="edit_motor" name="motor" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" required>
                                <option value="">-- Pilih Motor --</option>
                                <?php
                                $q_motor = mysqli_query($conn, "SELECT DISTINCT motor FROM tb_temperature WHERE motor IS NOT NULL AND motor != '' ORDER BY motor ASC");
                                while($row_motor = mysqli_fetch_assoc($q_motor)) {
                                    echo "<option value='" . htmlspecialchars($row_motor['motor']) . "'>" . htmlspecialchars($row_motor['motor']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <h4 class="text-rose-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Hasil Pengukuran (°C)</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Drive End (DE)</label>
                                <input type="number" step="0.1" name="de" id="edit_de" class="w-full bg-slate-950 border border-slate-700 text-amber-300 font-mono rounded px-3 py-2 text-sm focus:border-amber-500 focus:outline-none" required>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Body</label>
                                <input type="number" step="0.1" name="body" id="edit_body" class="w-full bg-slate-950 border border-slate-700 text-rose-400 font-mono rounded px-3 py-2 text-sm focus:border-rose-500 focus:outline-none" required>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Non-Drive End (NDE)</label>
                                <input type="number" step="0.1" name="nde" id="edit_nde" class="w-full bg-slate-950 border border-slate-700 text-blue-300 font-mono rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Note</label>
                        <textarea name="note" id="edit_note" rows="2" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-4">
                        <button type="button" onclick="closeModal('modalEditTemp')" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition text-sm font-medium shadow-lg">
                            Update Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script>
        window.rowsPerPage = window.rowsPerPage || 10; // Bisa diubah jika ingin nampilin lebih banyak
        window.currentPage = window.currentPage || 1;
        window.currentSearchKeyword = window.currentSearchKeyword || "";
        window.allRows = window.allRows || [];

        var myChart; // Variabel global untuk grafik

        (function() {
            if (document.documentElement.hasAttribute("data-turbo-preview")) return;
            const tableBody = document.getElementById('tableTempBody');
            if (!tableBody) return;

            // Ambil semua baris tabel
            allRows = Array.from(tableBody.querySelectorAll('tr'));

            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    currentSearchKeyword = this.value.toLowerCase();
                    currentPage = 1;
                    renderTable();
                });
            }

            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');

            if (msg) {
                // Tentukan isi notifikasi berdasarkan isi pesan
                let title, text, icon;

                if (msg === 'success') {
                    title = 'Berhasil!';
                    text = 'Data suhu baru telah berhasil disimpan.';
                    icon = 'success';
                } else if (msg === 'updated') {
                    title = 'Diperbarui!';
                    text = 'Data suhu berhasil diperbaiki.';
                    icon = 'success';
                } else if (msg === 'deleted') {
                    title = 'Dihapus!';
                    text = 'Data suhu telah berhasil dihapus dari sistem.';
                    icon = 'success';
                } else if (msg === 'error') {
                    title = 'Gagal!';
                    text = 'Terjadi kesalahan sistem saat memproses data.';
                    icon = 'error';
                }

                // Eksekusi Swal Fire jika ada pesan
                if (title) {
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: icon,
                        background: '#1e293b', // Warna slate-900 biar nyambung sama tema
                        color: '#fff',
                        confirmButtonColor: '#059669', // Emerald-600
                        timer: 3000, // Hilang otomatis dalam 3 detik
                        timerProgressBar: true
                    }).then(() => {
                        // Bersihkan URL dari parameter msg setelah muncul agar tidak muncul lagi saat di-refresh
                        window.history.replaceState({}, document.title, window.location.pathname);
                    });
                }
            }

            // renderTable();
        })();

        // --- FUNGSI OPEN/CLOSE MODAL ---
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // --- FUNGSI EDIT DATA ---
        function editTemp(id, tanggal, mesin, motor, de, body, nde, note) {
            document.getElementById('edit_temp_id').value = id;
            document.getElementById('edit_tanggal').value = tanggal;
            document.getElementById('edit_mesin').value = mesin;
            document.getElementById('edit_motor').value = motor;
            document.getElementById('edit_de').value = de;
            document.getElementById('edit_body').value = body;
            document.getElementById('edit_nde').value = nde;
            document.getElementById('edit_note').value = note;
            openModal('modalEditTemp');
        }

        // --- FUNGSI HAPUS DATA ---
        function confirmDeleteTemp(id) {
            Swal.fire({
                title: 'Hapus Record Suhu?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                // Pastikan file delete_temperature.php nanti dibuat ya Pak
                if (result.isConfirmed) window.location.href = 'delete/delete_temperature.php?id=' + id;
            });
        }

        function renderPaginationButtons(totalPages) {
            const container = document.getElementById('paginationControls');
            if (!container) return;
            container.innerHTML = "";
            if (totalPages <= 1) return;

            const createBtn = (text, page) => {
                const btn = document.createElement('button');
                btn.innerText = text;
                const isActive = (page === currentPage);
                // Style kotak-kotak persis seperti di UI Lama
                btn.className = `px-3 py-1 border border-slate-700 transition text-sm ${isActive ? 'bg-cyan-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}`;
                btn.onclick = () => { currentPage = page; renderTable(); };
                return btn;
            };

            if (currentPage > 1) container.appendChild(createBtn("Previous", currentPage - 1));
            
            let startP = Math.max(1, currentPage - 2);
            let endP = Math.min(totalPages, currentPage + 2);
            for (let i = startP; i <= endP; i++) {
                container.appendChild(createBtn(i, i));
            }

            if (currentPage < totalPages) container.appendChild(createBtn("Next", currentPage + 1));
        }

        function toggleView(mode) {
            // 1. Definisikan variabelnya
            const tableSection = document.getElementById('tableSection');
            const graphSection = document.getElementById('graphSection');

            const btnGraph = document.getElementById('btnViewGraph');
            const btnTable = document.getElementById('btnBackToTable');
            
            if (mode === 'graph') {
                // PERBAIKAN: Gunakan tableSection, bukan tableContainer
                tableSection.classList.add('hidden');
                graphSection.classList.remove('hidden');

                // Sembunyikan tombol Graph, Munculkan tombol Table
                if(btnGraph) btnGraph.classList.add('hidden');
                if(btnTable) btnTable.classList.remove('hidden');
                
                setTimeout(() => { loadChartData(); }, 100); 
            } else {
                // PERBAIKAN: Gunakan tableSection, bukan tableContainer
                tableSection.classList.remove('hidden');
                graphSection.classList.add('hidden');

                // Sembunyikan tombol Table, Munculkan tombol Graph
                if(btnGraph) btnGraph.classList.remove('hidden');
                if(btnTable) btnTable.classList.add('hidden');
            }
        }

        function minimizeGraph() {
            const body = document.getElementById('graphBody');
            const icon = document.querySelector('button[onclick="minimizeGraph()"] i');

            if (body.classList.contains('hidden')) {
                body.classList.remove('hidden');
                icon.classList.replace('fa-plus', 'fa-minus'); // Balikkan ikon ke minus
            } else {
                body.classList.add('hidden');
                icon.classList.replace('fa-minus', 'fa-plus'); // Ubah ikon ke plus saat sembunyi
            }
        }

        // Fungsi untuk memanggil API
        async function loadChartData() {
            // 1. Ambil nilai filter (jika Bapak nanti ganti-ganti dropdown)
            const machine = document.getElementById('filterMachine').value || 'MCG-01';
            const motorEl = document.querySelector('input[name="motor_select"]:checked');
            const motor = motorEl ? "Motor " + motorEl.value : 'Motor Mixer 01';

            // 2. Update teks judul dasbor secara otomatis
            document.getElementById('displayMachine').innerText = machine;
            document.getElementById('displayMotor').innerText = motor;
            document.getElementById('chartTitle').innerText = `${machine} (${motor})`;

            try {
                // 3. Tarik data dari API yang Bapak buat tadi
                const response = await fetch(`chart_api/api_get_chart_data.php?machine=${machine}&motor=${motor}`);
                const data = await response.json();

                // 4. Jika datanya ada, update angka di KARTU SUMMARY (DE, NDE, Body)
                if (data.labels && data.labels.length > 0) {
                    const lastIdx = data.labels.length - 1; // Ambil data paling belakang/terbaru
                    
                    updateCardColor('valDE', 'colorDE', data.de[lastIdx]);
                    updateCardColor('valNDE', 'colorNDE', data.nde[lastIdx]);
                    updateCardColor('valBody', 'colorBody', data.body[lastIdx]);

                    document.getElementById('lastDate').innerText = data.labels[lastIdx]; 
                } else {
                    // Kalau mesinnya belum punya data sama sekali, kembalikan ke 0
                    updateCardColor('valDE', 'colorDE', '0.0');
                    updateCardColor('valNDE', 'colorNDE', '0.0');
                    updateCardColor('valBody', 'colorBody', '0.0');

                    document.getElementById('lastDate').innerText = 'Belum Ada Data'; 
                }

                // 5. Lempar datanya untuk digambar jadi grafik
                renderChart(data);

            } catch (error) {
                console.error("Gagal mengambil data grafik:", error);
            }
        }

        // Fungsi untuk mengecek suhu dan mengubah warna kartu otomatis
        function updateCardColor(valId, colorId, value) {
            document.getElementById(valId).innerText = value;
            const colorEl = document.getElementById(colorId);
            
            const num = parseFloat(value);
            
            // Reset semua class warna bawaan terlebih dahulu
            colorEl.className = "text-3xl font-bold transition-colors duration-300"; 

            // Logika Warna (Bisa disesuaikan batas suhunya)
            if (num >= 85) {
                colorEl.classList.add("text-rose-500", "animate-pulse"); // MERAH & BERKEDIP (Overheat)
            } else if (num >= 80) {
                colorEl.classList.add("text-amber-400"); // KUNING (Warning)
            } else {
                colorEl.classList.add("text-emerald-400"); // HIJAU (Normal)
            }
        }

        // ==========================================
        // FUNGSI FILTER TABEL LAST RECORD DI MODAL
        // ==========================================
        function filterLastRecord() {
            // Ambil nama mesin yang dipilih dari dropdown
            const selectedMachine = document.getElementById('filterModalMachine').value.toUpperCase();
            
            // Ambil semua baris data di dalam tabel Last Record
            const rows = document.querySelectorAll('.row-last-record');

            rows.forEach(row => {
                // Ambil atribut data-mesin dari masing-masing baris
                const rowMachine = row.getAttribute('data-mesin').toUpperCase();
                
                // Logika: Jika pilih "ALL" atau nama mesinnya cocok, maka tampilkan. Kalau tidak, sembunyikan!
                if (selectedMachine === "ALL" || rowMachine === selectedMachine) {
                    row.style.display = ''; 
                } else {
                    row.style.display = 'none'; 
                }
            });
        }

        // FUNGSI MURNI UNTUK MENGGAMBAR GRAFIK (GAYA MINIMALIST VIBRATION)
        function renderChart(data) {
            const ctx = document.getElementById('tempTrendChart').getContext('2d');
            if (myChart) myChart.destroy(); 

            myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels.map(label => label.substring(0, 6)), 
                    datasets: [
                        { 
                            label: 'DE', 
                            data: data.de, 
                            borderColor: '#dfa800', 
                            // backgroundColor: '#dfa800', // Samakan dengan border
                            backgroundColor: 'rgba(223, 168, 0, 0.1)',
                            tension: 0.4, 
                            borderWidth: 2, 
                            pointRadius: 2, // Diperbesar sedikit seperti Vibration
                            pointHoverRadius: 6,
                            fill: true // Hilangkan efek bayangan area
                        },
                        { 
                            label: 'NDE', 
                            data: data.nde, 
                            borderColor: '#3b82f6', 
                            // backgroundColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)', 
                            tension: 0.4, 
                            borderWidth: 2, 
                            pointRadius: 2,
                            pointHoverRadius: 6,
                            fill: false
                        },
                        { 
                            label: 'Body', 
                            data: data.body, 
                            borderColor: '#f43f5e', 
                            // backgroundColor: '#f43f5e',
                            backgroundColor: 'rgba(244, 63, 94, 0.1)', 
                            tension: 0.4, 
                            borderWidth: 2, 
                            pointRadius: 2,
                            pointHoverRadius: 6,
                            fill: true
                        },
                        { 
                            label: 'LIMIT', 
                            data: data.limit, 
                            borderColor: '#d946ef', 
                            // backgroundColor: '#d946ef',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5], 
                            tension: 0, 
                            borderWidth: 1.5, 
                            pointRadius: 0, // Limit tidak perlu ada titik
                            fill: true 
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false, 
                    },
                    plugins: { 
                        legend: { 
                            display: true, 
                            // Tambahkan padding: 20 agar jarak legend lega seperti Vibration
                            labels: { color: '#94a3b8', usePointStyle: true, boxWidth: 8, padding: 20 } 
                        }, 
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.95)', 
                            titleColor: '#94a3b8', 
                            titleFont: { size: 13, weight: 'bold' }, 
                            bodyColor: '#f8fafc', 
                            // bodyFont SAYA HAPUS DI SINI BIAR NGGIKUT VIBRATION
                            borderColor: '#334155', 
                            borderWidth: 1, 
                            padding: 12, 
                            boxPadding: 6, 
                            usePointStyle: true,
                            callbacks: {
                                labelColor: function(context) { 
                                    return { 
                                        borderColor: context.dataset.borderColor, 
                                        backgroundColor: context.dataset.borderColor, 
                                        borderWidth: 2, 
                                        borderRadius: 6 
                                    }; 
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    if (context.parsed.y !== null) label += context.parsed.y + ' °C';
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            grid: { color: '#334155', borderDash: [2, 2] }, 
                            ticks: { color: '#94a3b8' },
                            title: { display: true, text: 'Temperature (°C)', color: '#64748b' } // Tambah judul sumbu Y
                        },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        }

        // ==========================================
        // FITUR AUTO-REFRESH GRAFIK
        // ==========================================
        // FITUR TOMBOL MOTOR DINAMIS
        // ==========================================
        var motorMap = <?php echo $motor_map_json; ?>;

        function updateMotorButtons() {
            const machine = document.getElementById('filterMachine').value;
            const container = document.getElementById('motorRadioGroup');
            
            // Ambil daftar motor sesuai mesin. Kalau kosong, beri default 'Mixer 01'
            const motors = motorMap[machine] || ['Mixer 01']; 
            
            container.innerHTML = ''; // Bersihkan tombol lama
            
            // Cetak tombol baru
            motors.forEach((motor, index) => {
                const isChecked = index === 0 ? 'checked' : ''; 
                const html = `
                    <label class="relative cursor-pointer group fade-in">
                        <input type="radio" name="motor_select" value="${motor}" class="peer sr-only" ${isChecked}>
                        <div class="px-4 py-2 rounded-lg border border-slate-700 bg-slate-900 text-slate-400 font-medium transition-all duration-200 hover:border-cyan-500 hover:text-cyan-400 peer-checked:bg-cyan-600 peer-checked:border-cyan-500 peer-checked:text-white peer-checked:shadow-lg peer-checked:shadow-cyan-600/20 select-none">
                            ${motor}
                        </div>
                    </label>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });

            // Pasang event listener ke tombol yang baru dicetak
            let elMotors = document.querySelectorAll('input[name="motor_select"]');
            elMotors.forEach(radio => radio.addEventListener('change', loadChartData));
        }

        // EVENT LISTENER UNTUK AUTO-REFRESH (Aman dari Turbo.js)
        setTimeout(() => {
            let elMachine = document.getElementById('filterMachine');
            
            // Render pertama kali saat halaman dimuat
            if(elMachine) {
                updateMotorButtons(); 
            }

            // Saat dropdown mesin diganti
            if(elMachine) {
                elMachine.addEventListener('change', () => {
                    updateMotorButtons();
                    loadChartData(); 
                });
            }
        }, 200);
    </script>

    <div id="modalLastRecord" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm hidden fade-in p-4 md:p-10">
        <div class="bg-slate-900 border border-slate-700 rounded-xl shadow-2xl w-full max-w-6xl max-h-full flex flex-col overflow-hidden">
            
            <div class="px-6 py-4 border-b border-slate-700 flex justify-between items-center bg-slate-800">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-cyan-400"></i> Last Record Temperature
                </h3>
                <button onclick="document.getElementById('modalLastRecord').classList.add('hidden')" class="text-slate-400 hover:text-rose-400 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="px-6 py-3 bg-slate-800/50 border-b border-slate-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <label class="text-xs font-bold text-slate-400 uppercase flex items-center gap-2">
                    <i class="fas fa-filter text-cyan-500"></i> Filter Machine:
                </label>
                <select id="filterModalMachine" onchange="filterLastRecord()" class="w-full sm:w-64 bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none cursor-pointer shadow-inner">
                    <option value="ALL">-- Tampilkan Semua Mesin --</option>
                    <?php
                    // Ambil daftar mesin untuk filter modal
                    $q_mesin_modal = mysqli_query($conn, "SELECT DISTINCT mesin FROM tb_temperature WHERE mesin IS NOT NULL AND mesin != '' ORDER BY mesin ASC");
                    while($rm = mysqli_fetch_assoc($q_mesin_modal)) {
                        echo "<option value='" . htmlspecialchars($rm['mesin']) . "'>" . htmlspecialchars($rm['mesin']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="p-4 md:p-0 overflow-y-auto custom-scrollbar">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950 sticky top-0 z-10 shadow-md hidden md:table-header-group">
                        <tr class="text-xs uppercase font-bold text-slate-400 whitespace-nowrap">
                            <th class="px-6 py-4 border-b border-slate-700">Machine</th>
                            <th class="px-6 py-4 border-b border-slate-700">Motor</th>
                            <th class="px-6 py-4 border-b border-slate-700">Last Record</th>
                            <th class="px-6 py-4 border-b border-slate-700 text-center">DE</th>
                            <th class="px-6 py-4 border-b border-slate-700 text-center">Body</th>
                            <th class="px-6 py-4 border-b border-slate-700 text-center">NDE</th>
                            <th class="px-6 py-4 border-b border-slate-700 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group md:divide-y divide-slate-800 bg-transparent md:bg-slate-800/30 p-0">
                        <?php
                        $q_last = "
                        SELECT t1.*
                        FROM tb_temperature t1
                        INNER JOIN (
                            SELECT mesin, motor, MAX(id) as max_id
                            FROM tb_temperature
                            WHERE mesin IS NOT NULL AND motor IS NOT NULL
                            GROUP BY mesin, motor
                        ) t2 ON t1.id = t2.max_id
                        ORDER BY t1.mesin ASC, t1.motor ASC
                        ";
                        $res_last = mysqli_query($conn, $q_last);

                        if($res_last && mysqli_num_rows($res_last) > 0) {
                            while($d = mysqli_fetch_assoc($res_last)) {
                                $limit = isset($d['temp_limit']) ? $d['temp_limit'] : 85; 
                                $de = isset($d['de']) ? $d['de'] : 0;
                                $body = isset($d['body']) ? $d['body'] : 0;
                                $nde = isset($d['nde']) ? $d['nde'] : 0;

                                $score = 0;
                                $c_de = 'text-emerald-400'; $c_body = 'text-emerald-400'; $c_nde = 'text-emerald-400';

                                if($de > $limit) { $score++; $c_de = 'text-rose-500 font-bold'; }
                                if($body > $limit) { $score++; $c_body = 'text-rose-500 font-bold'; }
                                if($nde > $limit) { $score++; $c_nde = 'text-rose-500 font-bold'; }

                                if($score == 3) {
                                    $status = "1. BAD"; $bg_status = "bg-rose-500/20 text-rose-400 border border-rose-500/50";
                                } else if ($score == 0) {
                                    $status = "3. GOOD"; $bg_status = "bg-emerald-500/20 text-emerald-400 border border-emerald-500/50";
                                } else {
                                    $status = "2. WARNING"; $bg_status = "bg-amber-500/20 text-amber-400 border border-amber-500/50";
                                }

                                $tanggal_format = date('d M Y', strtotime($d['tanggal']));
                                
                                // TAMBAHAN PENTING: Class 'row-last-record' dan atribut 'data-mesin' agar bisa disaring JS
                                echo "<tr class='row-last-record block md:table-row bg-slate-800 md:bg-transparent rounded-xl md:rounded-none border border-slate-700 md:border-none mb-4 md:mb-0 hover:bg-slate-700/50 transition overflow-hidden shadow-lg md:shadow-none' data-mesin='" . htmlspecialchars($d['mesin']) . "'>";
                                
                                echo "<td class='flex justify-between md:table-cell px-4 py-2 md:px-6 md:py-4 border-b border-slate-700/50 md:border-none md:font-bold md:text-white items-center'> <span class='md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider'>Machine</span> <span class='font-bold text-white'>{$d['mesin']}</span> </td>";
                                echo "<td class='flex justify-between md:table-cell px-4 py-2 md:px-6 md:py-4 border-b border-slate-700/50 md:border-none items-center'> <span class='md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider'>Motor</span> <span class='text-right md:text-left'>{$d['motor']}</span> </td>";
                                echo "<td class='flex justify-between md:table-cell px-4 py-2 md:px-6 md:py-4 border-b border-slate-700/50 md:border-none md:text-slate-400 items-center'> <span class='md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider'>Date</span> <span>{$tanggal_format}</span> </td>";
                                echo "<td class='flex justify-between md:table-cell px-4 py-2 md:px-6 md:py-4 border-b border-slate-700/50 md:border-none md:text-center items-center'> <span class='md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider'>DE</span> <span class='{$c_de}'>{$de}</span> </td>";
                                echo "<td class='flex justify-between md:table-cell px-4 py-2 md:px-6 md:py-4 border-b border-slate-700/50 md:border-none md:text-center items-center'> <span class='md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider'>Body</span> <span class='{$c_body}'>{$body}</span> </td>";
                                echo "<td class='flex justify-between md:table-cell px-4 py-2 md:px-6 md:py-4 border-b border-slate-700/50 md:border-none md:text-center items-center'> <span class='md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider'>NDE</span> <span class='{$c_nde}'>{$nde}</span> </td>";
                                echo "<td class='flex justify-between md:table-cell px-4 py-3 md:px-6 md:py-4 md:text-center items-center bg-slate-900/50 md:bg-transparent'> <span class='md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider'>Status</span> <span class='px-3 py-1 rounded-full text-xs font-bold {$bg_status} shadow-sm'>{$status}</span> </td>";
                                
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr class='block md:table-row'><td colspan='7' class='block md:table-cell text-center py-8 text-slate-500'>Belum ada record data.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</body>
</html>