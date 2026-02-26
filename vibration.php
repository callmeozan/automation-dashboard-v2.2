<?php
include 'layouts/auth_and_config.php';

$pageTitle = "Motor Vibration";

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
    $conditions[] = "tanggal BETWEEN '$start_date' AND '$end_date'";
}

// Filter Pencarian
$whereClause = "";
if (count($conditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Hitung Total Data (Untuk Pagination)
$countQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_vibration $whereClause");
$countRow = mysqli_fetch_assoc($countQuery);
$totalData = $countRow['total'];
$totalPages = ceil($totalData / $limit);

$urlParams = ($search != '') ? "&search=" . urlencode($search) : "";
if ($start_date != '') $urlParams .= "&start_date=" . urlencode($start_date);
if ($end_date != '') $urlParams .= "&end_date=" . urlencode($end_date);

// CSS khusus untuk print (sama dengan temperature)
$extraMenu = '
    <div class="flex items-center gap-4">
        <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4">
            Total Items: <span class="text-emerald-400 font-bold">' . number_format($totalData) . '</span>
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
            .text-white, .text-slate-400, .text-emerald-400, .text-cyan-400 { color: black !important; }
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
                <div class="mb-6">
                    <div class="border-b border-slate-700">
                        <nav class="-mb-px flex gap-8" aria-label="Tabs">
                            <a href="temperature.php" class="border-transparent text-slate-400 hover:border-slate-500 hover:text-slate-300 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium flex items-center gap-2 transition-colors">
                                <i class="fas fa-thermometer-half"></i> Temperature
                            </a>

                            <a href="vibration.php" class="border-emerald-500 text-emerald-400 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-bold flex items-center gap-2">
                                <i class="fas fa-wave-square"></i> Vibration
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4"> 
                    <div class="grid grid-cols-2 md:flex md:flex-wrap items-center gap-2 w-full xl:w-auto">
                        <button onclick="openModal('modalAddVib')" class="h-[42px] w-full md:w-auto bg-cyan-600 hover:bg-cyan-500 text-white px-4 rounded-lg text-sm font-medium transition shadow-lg shadow-cyan-600/20 flex items-center justify-center gap-2">
                            <i class="fas fa-plus"></i> <span class="whitespace-nowrap">New Record</span>
                        </button>

                        <button id="btnBackToTable" onclick="toggleView('table')" class="hidden h-[42px] w-full md:w-auto bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 rounded-lg border border-slate-700 text-sm transition font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-table text-cyan-400"></i> <span class="whitespace-nowrap">Data Record</span>
                        </button>

                        <button id="btnViewGraph" onclick="toggleView('graph')" class="h-[42px] w-full md:w-auto bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 rounded-lg border border-slate-700 text-sm transition font-medium flex items-center justify-center gap-2">
                            <i class="fas fa-chart-line text-emerald-400"></i> <span class="whitespace-nowrap">View Graph</span>
                        </button>
                    </div>

                    <div class="w-full xl:w-auto">
                        <form action="vibration.php" method="GET" class="flex flex-col md:flex-row items-center gap-2 w-full" data-turbo-frame="_top">
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

                                <a href="export/export_vib.php?search=<?php echo urlencode($search); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="h-[42px] px-3.5 md:px-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium transition shadow-lg flex items-center justify-center flex-shrink-0" title="Export to Excel">
                                    <i class="fas fa-file-excel"></i> <span class="hidden md:inline ml-2">Export</span>
                                </a>

                                <?php if(!empty($search) || !empty($start_date)): ?>
                                    <a href="vibration.php" class="h-[42px] w-[42px] bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white rounded-lg text-sm font-medium border border-rose-500/20 hover:border-transparent transition flex items-center justify-center flex-shrink-0" title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="tableSection" class="space-y-6">
                <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-400 whitespace-nowrap">
                            <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                <tr>
                                    <th class="px-4 py-4 text-center">Tanggal</th>
                                    <th class="px-4 py-4">Machine</th>
                                    <th class="px-4 py-4">Motor</th>
                                    <th class="px-4 py-4 text-center text-cyan-400" title="Drive End - Axial">DE-A</th>
                                    <th class="px-4 py-4 text-center text-cyan-400" title="Drive End - Horizontal">DE-H</th>
                                    <th class="px-4 py-4 text-center text-cyan-400" title="Drive End - Vertical">DE-V</th>
                                    <th class="px-4 py-4 text-center text-emerald-400" title="Non-Drive End - Axial">NDE-A</th>
                                    <th class="px-4 py-4 text-center text-emerald-400" title="Non-Drive End - Horizontal">NDE-H</th>
                                    <th class="px-4 py-4 text-center text-emerald-400" title="Non-Drive End - Vertical">NDE-V</th>
                                    <th class="px-4 py-4">Note</th>
                                    <th class="px-4 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50" id="tableVibBody">
                                <?php
                                $query = mysqli_query($conn, "SELECT * FROM tb_vibration $whereClause ORDER BY tanggal DESC LIMIT $offset, $limit");

                                if (mysqli_num_rows($query) > 0) {
                                    while ($row = mysqli_fetch_assoc($query)) {
                                        $id = $row['id'];
                                        $tanggal_format = date('d M Y', strtotime($row['tanggal']));
                                ?>
                                    <tr class="hover:bg-slate-700/20 transition group border-l-4 border-transparent hover:border-cyan-500">
                                        <td class="px-4 py-4 text-center font-medium text-slate-300">
                                            <?php echo $tanggal_format; ?>
                                        </td>
                                        <td class="px-4 py-4 font-bold text-white">
                                            <?php echo $row['mesin']; ?>
                                        </td>
                                        <td class="px-4 py-4 text-slate-300">
                                            <?php echo $row['motor']; ?>
                                        </td>
                                        <td class="px-4 py-4 text-center font-mono font-bold text-white">
                                            <?php echo $row['de_a'] ?? '-'; ?>
                                        </td>
                                        <td class="px-4 py-4 text-center font-mono font-bold text-white">
                                            <?php echo $row['de_h'] ?? '-'; ?>
                                        </td>
                                        <td class="px-4 py-4 text-center font-mono font-bold text-white">
                                            <?php echo $row['de_v'] ?? '-'; ?>
                                        </td>
                                        <td class="px-4 py-4 text-center font-mono font-bold text-white">
                                            <?php echo $row['nde_a'] ?? '-'; ?>
                                        </td>
                                        <td class="px-4 py-4 text-center font-mono font-bold text-white">
                                            <?php echo $row['nde_h'] ?? '-'; ?>
                                        </td>
                                        <td class="px-4 py-4 text-center font-mono font-bold text-white">
                                            <?php echo $row['nde_v'] ?? '-'; ?>
                                        </td>

                                        <td class="px-4 py-4 text-xs italic text-slate-500 max-w-[200px] truncate" title="<?php echo htmlspecialchars($row['note'] ?? ''); ?>">
                                            <?php echo !empty($row['note']) ? $row['note'] : '-'; ?>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                <div class="flex items-center justify-center gap-2">
                                                    <button onclick="confirmDeleteVib(<?php echo $id; ?>)" class="bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white px-3 py-1 rounded text-xs font-semibold border border-red-500/20 hover:border-transparent transition" title="Hapus Data">
                                                        Hapus
                                                    </button>
                                                    <button onclick="editVib(
                                                        '<?php echo $id; ?>',
                                                        '<?php echo $row['tanggal']; ?>',
                                                        '<?php echo htmlspecialchars($row['mesin'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['motor'], ENT_QUOTES); ?>',
                                                        '<?php echo $row['de_a']; ?>',
                                                        '<?php echo $row['de_h']; ?>',
                                                        '<?php echo $row['de_v']; ?>',
                                                        '<?php echo $row['nde_a']; ?>',
                                                        '<?php echo $row['nde_h']; ?>',
                                                        '<?php echo $row['nde_v']; ?>',
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
                                    echo '<tr><td colspan="11" class="text-center py-8 text-slate-500"><i class="fas fa-inbox text-3xl mb-3 block"></i>Belum ada data rekaman getaran ditemukan.</td></tr>';
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

            <div id="graphSection" class="hidden space-y-6 fade-in">
                
                <div class="text-center space-y-2">
                    <h2 id="displayMachine" class="text-4xl font-bold text-white tracking-widest"></h2>
                    <div class="bg-indigo-900/50 border-y border-indigo-500/30 py-2">
                        <span id="displayMotor" class="text-indigo-300 font-semibold uppercase tracking-widest"></span>
                    </div>
                </div>

                <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700 flex flex-wrap items-end gap-6">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase">Machine</label>
                        <?php
                        $q_mesin = mysqli_query($conn, "SELECT DISTINCT mesin FROM tb_vibration WHERE mesin LIKE 'MC%-%' ORDER BY mesin ASC");
                        $mesin_group = [];
                        if ($q_mesin && mysqli_num_rows($q_mesin) > 0) {
                            while ($row = mysqli_fetch_assoc($q_mesin)) {
                                $nama_mesin = trim($row['mesin']);
                                $parts = explode('-', $nama_mesin);
                                $mesin_group[$parts[0]][] = $nama_mesin;
                            }
                        }
                        ?>
                        <select id="filterMachine" class="bg-slate-900 border border-slate-700 text-white rounded-lg px-4 py-2.5 h-[42px] w-64 focus:border-cyan-500 outline-none shadow-inner">
                            <?php if (!empty($mesin_group)): ?>
                                <?php foreach ($mesin_group as $grup => $list_mesin): ?>
                                    <optgroup label="Area <?php echo htmlspecialchars($grup); ?>" class="bg-slate-800 text-cyan-400 font-bold uppercase">
                                        <?php foreach ($list_mesin as $m): ?>
                                            <option value="<?php echo htmlspecialchars($m, ENT_QUOTES); ?>" class="bg-slate-900 text-slate-200 font-normal"><?php echo htmlspecialchars($m, ENT_QUOTES); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="MCG-01">MCG-01</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="flex-1 space-y-3">
                        <label class="block text-xs font-bold text-slate-400 uppercase">Motor</label>
                        
                        <?php
                        // 1. KITA BUAT "KAMUS" MESIN & MOTOR DARI DATABASE
                        $q_map = mysqli_query($conn, "SELECT mesin, motor FROM tb_vibration WHERE mesin IS NOT NULL AND motor IS NOT NULL GROUP BY mesin, motor");
                        $motor_map = [];
                        if ($q_map && mysqli_num_rows($q_map) > 0) {
                            while($r = mysqli_fetch_assoc($q_map)){
                                $mesin_id = trim($r['mesin']);
                                // Hilangkan kata "Motor " agar nama di tombol tetap singkat (Misal: "Mixer 01")
                                $motor_short = trim(str_replace('Motor ', '', $r['motor']));
                                $motor_map[$mesin_id][] = $motor_short;
                            }
                        }
                        // Ubah array PHP jadi format JSON agar bisa dibaca oleh JavaScript
                        $motor_map_json = json_encode($motor_map);
                        ?>

                        <div class="flex flex-wrap items-center gap-2.5 text-sm" id="motorRadioGroup">
                            </div>
                    </div>
                </div>

                <div class="text-center space-y-4">
                    <p class="text-sm text-slate-400 italic">Last Measurement | <span id="lastDate">-</span></p>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 text-center shadow-lg">
                            <h4 class="text-cyan-400 text-sm font-bold uppercase mb-4 tracking-widest"><i class="fas fa-circle-notch mr-2"></i>Drive End (DE)</h4>
                            <div class="grid grid-cols-3 gap-4 divide-x divide-slate-700/50">
                                <div>
                                    <span class="text-[10px] text-slate-500 uppercase block mb-1 font-bold">Axial (A)</span>
                                    <span id="valDEA" class="text-2xl font-bold text-white transition-colors duration-300">0.0</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 uppercase block mb-1 font-bold">Horiz (H)</span>
                                    <span id="valDEH" class="text-2xl font-bold text-white transition-colors duration-300">0.0</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 uppercase block mb-1 font-bold">Vert (V)</span>
                                    <span id="valDEV" class="text-2xl font-bold text-white transition-colors duration-300">0.0</span>
                                </div>
                            </div>
                        </div>

                <div class="bg-slate-800 p-5 rounded-lg border border-slate-700 text-center shadow-lg">
                    <h4 class="text-emerald-400 text-sm font-bold uppercase mb-4 tracking-widest"><i class="fas fa-circle-notch mr-2"></i>Non-Drive End (NDE)</h4>
                        <div class="grid grid-cols-3 gap-4 divide-x divide-slate-700/50">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase block mb-1 font-bold">Axial (A)</span>
                                <span id="valNDEA" class="text-2xl font-bold text-white transition-colors duration-300">0.0</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase block mb-1 font-bold">Horiz (H)</span>
                                <span id="valNDEH" class="text-2xl font-bold text-white transition-colors duration-300">0.0</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase block mb-1 font-bold">Vert (V)</span>
                                <span id="valNDEV" class="text-2xl font-bold text-white transition-colors duration-300">0.0</span>
                            </div>
                        </div>
                </div>

                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-300"><i class="fas fa-chart-line mr-2 text-cyan-400"></i> Drive End (DE) Trend</h3>
                            <div class="flex gap-2">
                                <button onclick="minimizeGraph('graphDEBody', 'iconDE')" class="w-6 h-6 bg-cyan-600 hover:bg-cyan-500 text-white rounded text-xs transition" title="Minimize/Maximize"><i id="iconDE" class="fas fa-minus"></i></button>
                                <button onclick="toggleView('table')" class="w-6 h-6 bg-rose-600 hover:bg-rose-500 text-white rounded text-xs transition" title="Tutup Grafik"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <div id="graphDEBody" class="transition-all duration-300 overflow-hidden">
                            <div class="h-72 w-full">
                                <canvas id="vibTrendChartDE"></canvas>
                            </div>
                        </div>
                    </div>

                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-inner">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-300"><i class="fas fa-chart-line mr-2 text-emerald-400"></i> Non-Drive End (NDE) Trend</h3>
                            <div class="flex gap-2">
                                <button onclick="minimizeGraph('graphNDEBody', 'iconNDE')" class="w-6 h-6 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs transition" title="Minimize/Maximize"><i id="iconNDE" class="fas fa-minus"></i></button>
                                <button onclick="toggleView('table')" class="w-6 h-6 bg-rose-600 hover:bg-rose-500 text-white rounded text-xs transition" title="Tutup Grafik"><i class="fas fa-times"></i></button>
                            </div>
                    </div>
                    
                    <div id="graphNDEBody" class="transition-all duration-300 overflow-hidden">
                        <div class="h-72 w-full">
                            <canvas id="vibTrendChartNDE"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

    <div id="modalAddVib" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalAddVib')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-plus-circle text-cyan-400"></i> Tambah Record Getaran
                    </h3>
                    <button onclick="closeModal('modalAddVib')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_add_vibration.php" method="POST" data-turbo="false" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" required>
                        </div>
                        <div>
                            </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Machine</label>
                            <input type="text" name="mesin" placeholder="Contoh: MCG-01" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Motor</label>
                            <input type="text" name="motor" placeholder="Contoh: Motor Mixer 01" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-cyan-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Drive End (DE) - mm/s</h4>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Axial (A)</label>
                                    <input type="number" step="0.01" name="de_a" placeholder="0.00" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-cyan-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Horiz (H)</label>
                                    <input type="number" step="0.01" name="de_h" placeholder="0.00" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-cyan-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Vert (V)</label>
                                    <input type="number" step="0.01" name="de_v" placeholder="0.00" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-cyan-500 focus:outline-none transition">
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Non-Drive End (NDE) - mm/s</h4>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Axial (A)</label>
                                    <input type="number" step="0.01" name="nde_a" placeholder="0.00" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-emerald-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Horiz (H)</label>
                                    <input type="number" step="0.01" name="nde_h" placeholder="0.00" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-emerald-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Vert (V)</label>
                                    <input type="number" step="0.01" name="nde_v" placeholder="0.00" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-emerald-500 focus:outline-none transition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Note / Catatan (Opsional)</label>
                        <textarea name="note" rows="2" placeholder="Catatan temuan..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-4">
                        <button type="button" onclick="closeModal('modalAddVib')" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg transition text-sm font-medium shadow-lg shadow-cyan-600/20">
                            <i class="fas fa-save mr-2"></i> Simpan Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEditVib" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEditVib')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-2xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-edit text-emerald-400"></i> Edit Record Getaran
                    </h3>
                    <button onclick="closeModal('modalEditVib')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_edit_vibration.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" id="edit_vib_id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" id="edit_vib_tanggal" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                        </div>
                        <div></div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Machine</label>
                            <input type="text" name="mesin" id="edit_vib_mesin" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Motor</label>
                            <input type="text" name="motor" id="edit_vib_motor" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-cyan-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Drive End (DE) - mm/s</h4>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Axial (A)</label>
                                    <input type="number" step="0.01" name="de_a" id="edit_de_a" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-cyan-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Horiz (H)</label>
                                    <input type="number" step="0.01" name="de_h" id="edit_de_h" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-cyan-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Vert (V)</label>
                                    <input type="number" step="0.01" name="de_v" id="edit_de_v" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-cyan-500 focus:outline-none transition">
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Non-Drive End (NDE) - mm/s</h4>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Axial (A)</label>
                                    <input type="number" step="0.01" name="nde_a" id="edit_nde_a" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-emerald-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Horiz (H)</label>
                                    <input type="number" step="0.01" name="nde_h" id="edit_nde_h" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-emerald-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 text-center mb-1 uppercase">Vert (V)</label>
                                    <input type="number" step="0.01" name="nde_v" id="edit_nde_v" class="w-full bg-slate-950 border border-slate-700 text-white px-2 py-2 rounded text-center text-sm focus:border-emerald-500 focus:outline-none transition">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Note</label>
                        <textarea name="note" id="edit_vib_note" rows="2" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-4">
                        <button type="button" onclick="closeModal('modalEditVib')" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
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
        var vibChartDE;  // Variabel grafik khusus DE
        var vibChartNDE; // Variabel grafik khusus NDE
        // SCRIPT NOTIFIKASI
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');

            if (msg) {
                let title, text, icon;

                if (msg === 'success') {
                    title = 'Berhasil!'; text = 'Data getaran baru telah berhasil disimpan.'; icon = 'success';
                } else if (msg === 'updated') {
                    title = 'Diperbarui!'; text = 'Data getaran berhasil diperbaiki.'; icon = 'success';
                } else if (msg === 'deleted') {
                    title = 'Dihapus!'; text = 'Data getaran telah berhasil dihapus dari sistem.'; icon = 'success';
                } else if (msg === 'error') {
                    title = 'Gagal!'; text = 'Terjadi kesalahan sistem saat memproses data.'; icon = 'error';
                }

                if (title) {
                    Swal.fire({
                        title: title, text: text, icon: icon,
                        background: '#1e293b', color: '#fff',
                        confirmButtonColor: '#059669', timer: 3000, timerProgressBar: true
                    }).then(() => {
                        window.history.replaceState({}, document.title, window.location.pathname);
                    });
                }
            }
        })();

        // FUNGSI BUKA TUTUP MODAL
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('hidden');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('hidden');
        }

        // FUNGSI EDIT DATA VIBRATION
        function editVib(id, tanggal, mesin, motor, de_a, de_h, de_v, nde_a, nde_h, nde_v, note) {
            document.getElementById('edit_vib_id').value = id;
            document.getElementById('edit_vib_tanggal').value = tanggal;
            document.getElementById('edit_vib_mesin').value = mesin;
            document.getElementById('edit_vib_motor').value = motor;
            
            document.getElementById('edit_de_a').value = de_a;
            document.getElementById('edit_de_h').value = de_h;
            document.getElementById('edit_de_v').value = de_v;
            
            document.getElementById('edit_nde_a').value = nde_a;
            document.getElementById('edit_nde_h').value = nde_h;
            document.getElementById('edit_nde_v').value = nde_v;
            
            document.getElementById('edit_vib_note').value = note;
            
            openModal('modalEditVib');
        }

        // FUNGSI HAPUS DATA VIBRATION
        function confirmDeleteVib(id) {
            Swal.fire({
                title: 'Hapus Record Getaran?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                // Pastikan file delete/delete_vibration.php nanti dibuat 
                if (result.isConfirmed) window.location.href = 'delete/delete_vibration.php?id=' + id;
            });
        }

        // FUNGSI TOGGLE TABLE/GRAPH VIEW
        function toggleView(mode) {
            const tableSection = document.getElementById('tableSection');
            const graphSection = document.getElementById('graphSection');
            const btnGraph = document.getElementById('btnViewGraph');
            const btnTable = document.getElementById('btnBackToTable');
            
            if (mode === 'graph') {
                tableSection.classList.add('hidden');
                graphSection.classList.remove('hidden');
                if(btnGraph) btnGraph.classList.add('hidden');
                if(btnTable) btnTable.classList.remove('hidden');
            } else {
                tableSection.classList.remove('hidden');
                graphSection.classList.add('hidden');
                if(btnGraph) btnGraph.classList.remove('hidden');
                if(btnTable) btnTable.classList.add('hidden');
            }
        }

        // PERBARUI FUNGSI INI: Tambahkan pemicu loadChartData() di dalamnya
        function toggleView(mode) {
            const tableSection = document.getElementById('tableSection');
            const graphSection = document.getElementById('graphSection');
            const btnGraph = document.getElementById('btnViewGraph');
            const btnTable = document.getElementById('btnBackToTable');
            
            if (mode === 'graph') {
                tableSection.classList.add('hidden');
                graphSection.classList.remove('hidden');
                if(btnGraph) btnGraph.classList.add('hidden');
                if(btnTable) btnTable.classList.remove('hidden');
                
                // Memicu grafik untuk dimuat saat tab graph ditekan!
                setTimeout(() => { loadChartData(); }, 100); 
            } else {
                tableSection.classList.remove('hidden');
                graphSection.classList.add('hidden');
                if(btnGraph) btnGraph.classList.remove('hidden');
                if(btnTable) btnTable.classList.add('hidden');
            }
        }

        // FUNGSI ANIMASI MINIMIZE
        function minimizeGraph(bodyId, iconId) {
            const body = document.getElementById(bodyId);
            const icon = document.getElementById(iconId);
            if (body.classList.contains('hidden')) {
                body.classList.remove('hidden');
                icon.classList.replace('fa-plus', 'fa-minus');
            } else {
                body.classList.add('hidden');
                icon.classList.replace('fa-minus', 'fa-plus');
            }
        }

        // FUNGSI TARIK DATA API GETARAN
        async function loadChartData() {
            const machine = document.getElementById('filterMachine').value || 'MCG-01';
            const motorEl = document.querySelector('input[name="motor_select"]:checked');
            const motor = motorEl ? "Motor " + motorEl.value : 'Motor Mixer 01';

            document.getElementById('displayMachine').innerText = machine;
            document.getElementById('displayMotor').innerText = motor;
            // document.getElementById('chartTitle').innerText = `${machine} (${motor})`;

            try {
                const response = await fetch(`chart_api/api_get_vib_chart.php?machine=${machine}&motor=${motor}`);
                const data = await response.json();

                if (data.labels && data.labels.length > 0) {
                    const lastIdx = data.labels.length - 1;
                    updateVibColor('valDEA', data.de_a[lastIdx]);
                    updateVibColor('valDEH', data.de_h[lastIdx]);
                    updateVibColor('valDEV', data.de_v[lastIdx]);
                    updateVibColor('valNDEA', data.nde_a[lastIdx]);
                    updateVibColor('valNDEH', data.nde_h[lastIdx]);
                    updateVibColor('valNDEV', data.nde_v[lastIdx]);
                    document.getElementById('lastDate').innerText = data.labels[lastIdx]; 
                } else {
                    ['valDEA', 'valDEH', 'valDEV', 'valNDEA', 'valNDEH', 'valNDEV'].forEach(id => {
                        updateVibColor(id, '0.0');
                    });
                    document.getElementById('lastDate').innerText = 'Belum Ada Data'; 
                }

                renderCharts(data); // Panggil fungsi cetak 2 grafik
            } catch (error) {
                console.error("Gagal mengambil data grafik getaran:", error);
            }
        }

        // LOGIKA WARNA LIMIT GETARAN
        function updateVibColor(elementId, value) {
            const el = document.getElementById(elementId);
            el.innerText = value;
            const num = parseFloat(value);
            el.className = "text-2xl font-bold transition-colors duration-300"; 
            
            if (num >= 7.1) {
                el.classList.add("text-rose-500", "animate-pulse"); // Bahaya
            } else if (num >= 4.5) {
                el.classList.add("text-amber-400"); // Peringatan
            } else {
                el.classList.add("text-white"); // Aman
            }
        }

        // FUNGSI RENDER CHART.JS
        function renderCharts(data) {
            // Kita buat template dasar supaya tidak ngetik ulang settingan yang sama
            const chartOptions = {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { display: true, labels: { color: '#94a3b8', usePointStyle: true, boxWidth: 8, padding: 20 } }, 
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)', titleColor: '#94a3b8', titleFont: { size: 13, weight: 'bold' }, bodyColor: '#f8fafc',
                        borderColor: '#334155', borderWidth: 1, padding: 12, boxPadding: 6, usePointStyle: true,
                        callbacks: {
                            labelColor: function(context) { return { borderColor: context.dataset.borderColor, backgroundColor: context.dataset.borderColor, borderWidth: 2, borderRadius: 6 }; },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) label += context.parsed.y + ' mm/s';
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: { grid: { color: '#334155', borderDash: [2, 2] }, ticks: { color: '#94a3b8' }, title: { display: true, text: 'Velocity (mm/s)', color: '#64748b' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            };

            // 1. RAKIT GRAFIK DRIVE END (DE)
            const ctxDE = document.getElementById('vibTrendChartDE').getContext('2d');
            if (vibChartDE) vibChartDE.destroy(); 
            vibChartDE = new Chart(ctxDE, {
                type: 'line',
                data: {
                    // labels: data.labels,
                    labels: data.labels.map(label => label.substring(0, 6)), 
                    datasets: [
                        { label: 'Axial (A)', data: data.de_a, borderColor: '#22d3ee', backgroundColor: '#22d3ee', borderWidth: 2, tension: 0.3, pointRadius: 2, pointHoverRadius: 6 },
                        { label: 'Horizontal (H)', data: data.de_h, borderColor: '#0891b2', backgroundColor: '#0891b2', borderWidth: 2, borderDash: [4, 4], tension: 0.3, pointRadius: 2, pointHoverRadius: 6 },
                        { label: 'Vertical (V)', data: data.de_v, borderColor: '#0284c7', backgroundColor: '#0284c7', borderWidth: 2, borderDash: [2, 2], tension: 0.3, pointRadius: 2, pointHoverRadius: 6 }
                    ]
                },
                options: chartOptions
            });

            // 2. RAKIT GRAFIK NON-DRIVE END (NDE)
            const ctxNDE = document.getElementById('vibTrendChartNDE').getContext('2d');
            if (vibChartNDE) vibChartNDE.destroy(); 
            vibChartNDE = new Chart(ctxNDE, {
                type: 'line',
                data: {
                    labels: data.labels, 
                    datasets: [
                        { label: 'Axial (A)', data: data.nde_a, borderColor: '#34d399', backgroundColor: '#34d399', borderWidth: 2, tension: 0.3, pointRadius: 2, pointHoverRadius: 6 },
                        { label: 'Horizontal (H)', data: data.nde_h, borderColor: '#059669', backgroundColor: '#059669', borderWidth: 2, borderDash: [4, 4], tension: 0.3, pointRadius: 2, pointHoverRadius: 6 },
                        { label: 'Vertical (V)', data: data.nde_v, borderColor: '#166534', backgroundColor: '#166534', borderWidth: 2, borderDash: [2, 2], tension: 0.3, pointRadius: 2, pointHoverRadius: 6 }
                    ]
                },
                options: chartOptions
            });
        }

        // ==========================================
        // FITUR TOMBOL MOTOR DINAMIS (Sesuai ide Bapak)
        // ==========================================
        var motorMap = <?php echo $motor_map_json; ?>;

        function updateMotorButtons() {
            const machine = document.getElementById('filterMachine').value;
            const container = document.getElementById('motorRadioGroup');
            
            // Ambil daftar motor sesuai mesin. Kalau kosong, beri default 'Mixer 01'
            const motors = motorMap[machine] || ['Mixer 01']; 
            
            container.innerHTML = ''; // Sapu bersih tombol motor yang lama
            
            // Suntikkan tombol motor yang baru
            motors.forEach((motor, index) => {
                const isChecked = index === 0 ? 'checked' : ''; // Pilih motor pertama secara otomatis
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

            // Pasang ulang radar (event listener) ke tombol-tombol yang baru lahir ini
            let elMotors = document.querySelectorAll('input[name="motor_select"]');
            elMotors.forEach(radio => radio.addEventListener('change', loadChartData));
        }

        // EVENT LISTENER UNTUK AUTO-REFRESH (Aman dari Turbo.js)
        setTimeout(() => {
            let elMachine = document.getElementById('filterMachine');
            
            // Saat pertama kali load, langsung sesuaikan tombol motornya
            if(elMachine) {
                updateMotorButtons(); 
            }

            // Kalau dropdown mesin diganti, ubah tombol motornya, lalu panggil grafik baru
            if(elMachine) {
                elMachine.addEventListener('change', () => {
                    updateMotorButtons();
                    loadChartData(); 
                });
            }
        }, 200);
    </script>
</body>
</html>