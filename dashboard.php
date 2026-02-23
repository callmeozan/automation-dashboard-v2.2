<?php
// 1. PANGGIL GLOBAL LOGIC (Satpam, Koneksi, Team List)
include 'layouts/auth_and_config.php';

// 2. QUERY KHUSUS DASHBOARD (Hanya dashboard yang butuh ini)
// A. Hitung KPI Cards
$totalToDo     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE status = 'To Do'"))['total'];
$totalProgress = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE status = 'In Progress'"))['total'];
$totalDone     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_daily_reports WHERE status = 'Solved'"))['total'];
$totalAssets   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_assets"))['total'];

// B. Ambil Data My Daily Report (Tab 2)
$dUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT short_name FROM tb_users WHERE user_id='$id_user'"));
$myName = $dUser['short_name'] ?? 'User';
$queryMyReport = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE pic LIKE '%$myName%' ORDER BY date_log DESC, time_start DESC LIMIT 5");


// 3. SETTING TAMPILAN LAYOUT (HEADER & HEAD)
$pageTitle = "Departement Performance";

// Slot Header: Tombol Presentation Mode
$extraMenu = ''; 
if ($role_user == 'admin' || $role_user == 'section') {
    $extraMenu = '
    <button id="presentationModeBtn" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium bg-indigo-600 hover:bg-indigo-500 text-white rounded-full transition shadow-lg shadow-indigo-500/30">
        <i class="fas fa-tv"></i> <span>Presentation Mode</span>
    </button>';
}

// Slot Head: Library Tambahan
$extraHead = '
    <link href="assets/vendor/tom-select.css" rel="stylesheet">
    <script src="assets/vendor/tom-select.complete.min.js"></script>
    <script defer src="assets/vendor/alpine.js"></script>
    <style>
        /* 1. Hilangkan border bawaan wrapper agar tidak ada kotak double */
        .ts-wrapper { border: none !important; padding: 0 !important; box-shadow: none !important; }
        
        /* 2. Styling kotak utama (Control) */
        .ts-control { 
            background-color: #020617 !important; /* bg-slate-950 */
            border: 1px solid #334155 !important; /* border-slate-700 */
            color: #fff !important; 
            border-radius: 0.5rem !important; 
            padding: 8px 12px !important;
            box-shadow: none !important;
        }

        /* 3. Efek saat diklik (Focus) */
        .ts-wrapper.focus .ts-control {
            border-color: #6366f1 !important; /* indigo-500 */
            outline: none !important;
        }

        /* 4. Styling "Pills" / Item yang sudah dipilih */
        .ts-wrapper.multi .ts-control > div { 
            background-color: #4f46e5 !important; /* Indigo */
            color: white !important; 
            border-radius: 6px !important; 
            padding: 2px 10px !important;
            margin: 2px 4px !important;
        }

        /* Hilangkan garis aneh di tombol hapus */
        .ts-wrapper.multi .ts-control > div .remove { border: none !important; margin-left: 5px !important; }
        
        /* Dropdown menu (Pilihan yang muncul ke bawah) */
        .ts-dropdown { background-color: #1e293b !important; border: 1px solid #334155 !important; color: #fff !important; margin-top: 5px !important; }
        .ts-dropdown .active { background-color: #334155 !important; color: #fff !important; }
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

            <div class="p-8 space-y-8 fade-in">
                <!-- PRESENTATION MODE ADA DISNI -->
                <div id="presentationBanner" class="hidden bg-indigo-900/50 border border-indigo-500/50 text-indigo-200 px-4 py-2 rounded-lg text-center text-sm mb-4">
                    <i class="fas fa-info-circle"></i> Presentation Mode Active.
                </div>

                <!-- KPI CONTAINER ADA DISINI -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6" id="kpi-container">
                    <div class="kpi-card hover:border-red-500/50 group transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">To Do Project</p>
                                <h3 class="text-2xl font-bold text-white group-hover:text-red-400 transition">
                                    <?php echo $totalToDo; ?>
                                </h3>
                            </div>
                            <div class="icon-box text-red-400 bg-red-500/10 group-hover:bg-red-500/20 transition">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Project need to be done</p>
                    </div>

                    <div class="kpi-card hover:border-orange-500/50 group transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">In Progress Project</p>
                                <h3 class="text-2xl font-bold text-white group-hover:text-orange-400 transition">
                                    <?php echo $totalProgress; ?>
                                </h3>
                            </div>
                            <div class="icon-box text-orange-400 bg-orange-500/10 group-hover:bg-orange-500/20 transition">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Project in progress</p>
                    </div>

                    <div class="kpi-card hover:border-emerald-500/50 group transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Done Project</p>
                                <h3 class="text-2xl font-bold text-white group-hover:text-emerald-400 transition">
                                    <?php echo $totalDone; ?>
                                </h3>
                            </div>
                            <div class="icon-box text-emerald-400 bg-emerald-500/10 group-hover:bg-emerald-500/20 transition">
                                <i class="fas fa-check-double"></i>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Project successfully completed</p>
                    </div>

                    <div class="kpi-card hover:border-blue-500/50 group transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-2xl font-bold text-white group-hover:text-blue-400 transition">
                                    <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Assets</p>
                                    <?php echo $totalAssets; ?>
                                </h3>
                            </div>
                            <div class="icon-box text-blue-400 bg-blue-500/10 group-hover:bg-blue-500/20 transition">
                                <i class="fas fa-server"></i>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Machines & Devices recorded</p>
                    </div>
                </div>

                <!-- SEARCH BAR DAN INPUT BAR ADA DISINI -->
                <div id="operational-panel" class="grid grid-cols-1 lg:grid-cols-3 gap-8 transition-all duration-500">
                    <div class="lg:col-span-2 bg-slate-800 rounded-xl border border-slate-700 p-6 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl"></div>
                        <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                            <i class="fas fa-search text-emerald-400"></i> Search Project On Going
                        </h3>

                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-4 top-3.5 text-slate-500"></i>
                                <input type="text"
                                    id="searchInput"
                                    placeholder="Type to filter projects..."
                                    class="w-full bg-slate-900 border border-slate-600 text-white pl-10 pr-4 py-3 rounded-lg focus:border-emerald-500 focus:outline-none transition"
                                    autocomplete="off">
                            </div>
                            <button class="bg-slate-700 text-white px-6 py-3 rounded-lg border border-slate-600 hover:bg-slate-600 transition">
                                <i class="fas fa-qrcode"></i>
                            </button>
                        </div>
                    </div>

                    <div class="bg-slate-800 rounded-xl border border-slate-700 p-6 flex flex-col justify-center space-y-3">
                        <button class="btn-primary btn-input-laporan">
                            <i class="fas fa-plus-circle"></i> Input Daily Activity
                        </button>
                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                            <button onclick="openModal('modalProject')" class="btn-secondary">
                                <i class="fas fa-box-open"></i> Input Project On Going
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- TABEL DATA SEMUA ADA DISINI -->
                <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg" x-data="{ activeTab: 'project' }">

                    <!-- ACTIVE TAB ADA DISINI -->
                    <div class="flex border-b border-slate-700">
                        <button @click="activeTab = 'project'"
                            :class="activeTab === 'project' ? 'text-white border-b-2 border-emerald-500 bg-slate-700/50' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'"
                            class="px-6 py-4 text-sm font-semibold transition-all flex-1 md:flex-none text-left focus:outline-none">
                            <i class="fas fa-rocket mr-2"></i> On Going Projects
                        </button>

                        <button @click="activeTab = 'report'"
                            :class="activeTab === 'report' ? 'text-white border-b-2 border-emerald-500 bg-slate-700/50' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'"
                            class="px-6 py-4 text-sm font-semibold transition-all flex-1 md:flex-none text-left focus:outline-none">
                            <i class="fas fa-user-clock mr-2"></i> My Daily Report
                        </button>
                    </div>

                    <!-- SEMUA DATA TABEL ADA DISINI -->
                    <div class="relative">
                        <div x-show="activeTab === 'project'" x-transition.opacity class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-400">
                                <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                    <tr>
                                        <th class="px-6 py-4 w-20">Plant</th>
                                        <th class="px-6 py-4">Project Name</th>
                                        <th class="px-6 py-4 w-1/3">Project Detail</th>
                                        <th class="px-6 py-4">Teams</th>
                                        <th class="px-6 py-4">Activity</th>
                                        <th class="px-6 py-4">Status</th>
                                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                            <th class="px-6 py-4 text-center">Action</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50" id="projectTableBody">
                                    <?php
                                    // Ambil logika filter Search dari atas (jika ada) atau default
                                    $filter = "";
                                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                                        $k = mysqli_real_escape_string($conn, $_GET['search']);
                                        $filter = " AND (project_name LIKE '%$k%' OR description LIKE '%$k%' OR plant LIKE '%$k%')";
                                    }

                                    // Query Project In Progress
                                    $queryProj = mysqli_query($conn, "SELECT * FROM tb_projects WHERE status='In Progress' $filter ORDER BY due_date ASC");
                                    if (mysqli_num_rows($queryProj) > 0) {
                                        while ($row = mysqli_fetch_assoc($queryProj)) {
                                            // Logic Warna Activity
                                            $act = strtolower($row['activity']);
                                            $actColor = 'text-slate-300';
                                            if (strpos($act, 'not start') !== false) $actColor = 'text-red-400 font-bold';
                                            if (strpos($act, 'meeting') !== false) $actColor = 'text-emerald-400 font-bold';
                                            if (strpos($act, 'discussion') !== false) $actColor = 'text-emerald-400 font-bold';
                                            if (strpos($act, 'prepare bq') !== false) $actColor = 'text-purple-400 font-bold';
                                            if (strpos($act, 'fabrication') !== false) $actColor = 'text-yellow-400 font-bold';
                                            if (strpos($act, 'installation') !== false) $actColor = 'text-blue-400 font-bold';
                                            if (strpos($act, 'commissioning') !== false) $actColor = 'text-blue-400 font-bold';
                                            if (strpos($act, 'done') !== false) $actColor = 'text-emerald-400 font-bold';
                                    ?>
                                            <tr class="project-row hover:bg-slate-700/20 transition text-sm">
                                                <td class="px-6 py-4 text-white font-medium"><?php echo $row['plant']; ?></td>
                                                <td class="px-6 py-4 text-white font-medium"><?php echo $row['project_name']; ?></td>
                                                <td class="px-6 py-4">
                                                    <div class="line-clamp-2" title="<?php echo $row['description']; ?>"><?php echo $row['description']; ?></div>
                                                </td>
                                                <td class="px-6 py-4 text-indigo-300"><?php echo $row['team_members']; ?></td>
                                                <td class="px-6 py-4 <?php echo $actColor; ?>"><?php echo strtoupper($row['activity']); ?></td>
                                                <td class="px-6 py-4 text-slate-300 whitespace-nowrap"><?php echo $row['status']; ?></td>

                                                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                    <td class="px-6 py-4 text-center">
                                                        <div class="flex items-center justify-center gap-3 transition">
                                                            <button onclick="editProject('<?php echo $row['project_id']; ?>','<?php echo htmlspecialchars($row['project_name'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>','<?php echo $row['due_date']; ?>','<?php echo htmlspecialchars($row['category_badge'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($row['team_members'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($row['activity'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>','<?php echo $row['status']; ?>')" class="text-slate-400 hover:text-blue-400 transition"><i class="fas fa-pen"></i></button>
                                                            <button onclick="confirmDelete(<?php echo $row['project_id']; ?>)" class="text-slate-400 hover:text-red-400 transition"><i class="fas fa-trash"></i></button>
                                                        </div>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php }
                                    } else { ?>
                                        <tr id="noDataRow">
                                            <td colspan="7" class="px-6 py-8 text-center text-slate-500 italic">No ongoing projects.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <div class="flex justify-between items-center mt-4 mb-8 px-4" id="paginationContainer">
                                <div class="text-xs text-slate-500" id="pageInfo">Loading data...</div>
                                <div id="paginationControls" class="flex gap-1"></div>
                            </div>
                        </div>

                        <div x-show="activeTab === 'report'" x-transition.opacity class="overflow-x-auto" style="display: none;">
                            <table class="w-full text-left text-sm text-slate-400">
                                <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                    <tr>
                                        <th class="px-6 py-4">Date</th>
                                        <th class="px-6 py-4">Machine / Area</th>
                                        <th class="px-6 py-4 w-1/3">Problem</th>
                                        <th class="px-6 py-4">Status</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-700/50">
                                    <?php
                                    if (mysqli_num_rows($queryMyReport) > 0) {
                                        while ($rowR = mysqli_fetch_assoc($queryMyReport)) {
                                            // Logic Badge Status Report
                                            $st = $rowR['status'];
                                            $badge = 'bg-slate-700 text-slate-300';
                                            if ($st == 'Open') $badge = 'bg-red-500/10 text-red-400 border border-red-500/20';
                                            if ($st == 'Solved') $badge = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                                            if ($st == 'Monitor') $badge = 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20'; ?>

                                            <tr class="hover:bg-slate-700/20 transition cursor-pointer" onclick="window.location.href='laporan.php'">
                                                <td class="px-6 py-4 text-white font-medium"><?php echo date('d M y', strtotime($rowR['date_log'])); ?></td>
                                                <td class="px-6 py-4 text-indigo-300">
                                                    <?php echo $rowR['machine_name']; ?>
                                                    <div class="text-[10px] text-slate-500"><?php echo $rowR['plant']; ?></div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="line-clamp-1"><?php echo $rowR['problem']; ?></div>
                                                </td>
                                                <td class="px-6 py-4"><span class="<?php echo $badge; ?> px-2 py-0.5 rounded text-[10px] font-bold uppercase"><?php echo $st; ?></span></td>
                                            </tr>
                                        <?php }
                                    } else { ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-slate-500 italic">No report history available.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <div class="p-3 bg-slate-900/30 border-t border-slate-700 text-center">
                                <a href="laporan.php" class="text-xs text-indigo-400 hover:text-white transition">View All Reports &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER ADA DISINI -->
     <?php include 'layouts/footer.php'; ?>
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

    <!-- MODAL UNTUK INPUT EDIT PROJECT ON GOING -->
    <div id="modalEditProject" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEditProject')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-xl shadow-2xl p-6 relative">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-edit text-blue-400"></i> Edit Project</h3>
                    <button onclick="closeModal('modalEditProject')" class="text-slate-400 hover:text-red-400"><i class="fas fa-times text-xl"></i></button>
                </div>

                <form action="process/process_edit_project.php" method="POST" class="space-y-4">
                    <input type="hidden" name="project_id" id="edit_id">

                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs text-slate-400 mb-1">Plant</label>
                            <select name="plant" id="edit_plant" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="PLANT A">PLANT A</option>
                                <option value="PLANT BCHIT">PLANT BCHIT</option>
                                <option value="PLANT D/K">PLANT D/K</option>
                                <option value="PLANT E">PLANT E</option>
                                <option value="PLANT TBR">PLANT TBR</option>
                                <option value="PLANT MIXING">PLANT MIXING</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Project Status</label>
                            <select name="status" id="edit_status" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="To Do">To Do</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                    </div>

                    <div><label class="block text-xs text-slate-400 mb-1">Project Name</label><input type="text" name="project_name" id="edit_name" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs text-slate-400 mb-1">Description</label><textarea name="description" id="edit_desc" rows="2" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm"></textarea></div>

                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs text-slate-400 mb-1">Activity (Status)</label>
                            <select name="activity" id="edit_act" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="Not Start">Not Start</option>
                                <option value="Meeting & Discussion">Meeting & Discussion</option>
                                <option value="Prepare BQ">Prepare BQ</option>
                                <option value="Fabrication">Fabrication</option>
                                <option value="Installation">Installation</option>
                                <option value="Commissioning">Commissioning</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                        <div><label class="block text-xs text-slate-400 mb-1">Deadline</label><input type="date" name="due_date" id="edit_date" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm"></div>
                    </div>

                    <div><label class="block text-xs text-slate-400 mb-1">Team Members</label>
                        <select name="team[]" id="edit_team" multiple placeholder="Pilih Tim..." autocomplete="off" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                            <option value="">-- Pilih Lead Engineer --</option>
                            <?php
                            if (isset($teamList)) {
                                foreach ($teamList as $member): ?>
                                    <option value="<?php echo $member; ?>"><?php echo $member; ?></option>
                            <?php endforeach;
                            } ?>
                        </select>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modalEditProject')" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium shadow-lg">Update Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL UNTUK CREATE PROJECT ON GOING -->
    <div id="modalProject" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalProject')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-xl shadow-2xl p-6 relative">

                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-rocket text-indigo-400"></i> New Project On Going
                    </h3>
                    <button onclick="closeModal('modalProject')" class="text-slate-400 hover:text-red-400"><i class="fas fa-times text-xl"></i></button>
                </div>

                <form action="process/process_add_project.php" method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Plant</label>
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

                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Activity</label>
                            <select name="activity" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                <option value="">-- Pilih --</option>
                                <option value="Not Start">Not Start</option>
                                <option value="Meeting & Discussion">Meeting & Discussion</option>
                                <option value="Prepare BQ">Prepare BQ</option>
                                <option value="Fabrication">Fabrication</option>
                                <option value="Installation">Installation</option>
                                <option value="Commissioning">Commissioning</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Project Name</label>
                        <input type="text" name="project_name" placeholder="Nama Project..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Detail..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Deadline</label>
                            <input type="date" name="due_date" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Project Status</label>
                            <select name="status" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none" required>
                                <option value="">-- Pilih --</option>
                                <option value="In Progress">In Progress</option>
                                <option value="To Do">To Do</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Lead Engineer (Team)</label>
                        <select name="team[]" id="create_team" multiple placeholder="Pilih Tim..." autocomplete="off" class="w-full">
                            <option value="">-- Pilih --</option>
                            <?php
                            // Pastikan $teamList sudah didefinisikan di bagian atas file PHP
                            if (isset($teamList)) {
                                foreach ($teamList as $member): ?>
                                    <option value="<?php echo $member; ?>"><?php echo $member; ?></option>
                            <?php endforeach;
                            } ?>
                        </select>
                    </div>

                    <input type="hidden" name="category" value="Upgrade">
                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-2">
                        <button type="button" onclick="closeModal('modalProject')" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition text-sm font-medium shadow-lg shadow-indigo-600/20">
                            Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL UNTUK ADD USER -->
    <div id="modalAddUser" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalAddUser')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-xl shadow-2xl p-6 relative">

                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user-plus text-emerald-400"></i> Registrasi User Baru
                    </h3>
                    <button onclick="closeModal('modalAddUser')" class="close-modal-user text-slate-400 hover:text-red-400 transition"><i class="fas fa-times text-xl"></i></button>
                </div>

                <form action="process/process_add_user.php" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Username (NIK)</label>
                        <input type="text" name="username" placeholder="Contoh: 12-3456" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Password</label>
                        <input type="password" name="password" placeholder="********" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Full Name</label>
                        <input type="text" name="full_name" placeholder="Contoh: Faozan Nur Amanulloh" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Short Name (Panggilan)</label>
                        <input type="text" name="short_name" placeholder="Contoh: Faozan" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                        <p class="text-[10px] text-slate-500 mt-1">*Digunakan untuk dropdown list team.</p>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">Role / Jabatan</label>
                        <select name="role" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="-">--Pilih--</option>
                            <option value="worker">Worker (Lapangan)</option>
                            <option value="officer">Officer (Staff)</option>
                            <option value="section">Section Head</option>
                        </select>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-2">
                        <button type="button" onclick="closeModal('modalAddUser')" class="close-modal-user flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition text-sm font-medium shadow-lg shadow-emerald-600/20">
                            <i class="fas fa-save mr-2"></i> Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MOBILE NAV ADA DISINI -->
    <?php include 'layouts/mobile_nav.php'; ?>

    <!-- SCRIPTS GLOBAL ADA DISINI -->
     <?php include 'layouts/scripts.php'; ?>

     <!-- SCRIPT DASHBOARD ADA DISINI -->
     <script>
window.rowsPerPage = window.rowsPerPage || 10;
window.currentPage = window.currentPage || 1;
window.currentSearchKeyword = window.currentSearchKeyword || "";
window.allRows = window.allRows || [];

        // document.addEventListener('DOMContentLoaded', function() {
        // document.addEventListener('turbo:load', function() {
        (function() {
            if (document.documentElement.hasAttribute("data-turbo-preview")) return;
            if (!window.location.pathname.includes('dashboard.php')) return;
            console.log("Dashboard Logic Started!");

            const urlParams = new URLSearchParams(window.location.search);

            const tableBody = document.querySelector('#projectTableBody'); 
            const searchInput = document.getElementById('searchInput');

            // 1. Tangkap parameter dari URL
            // const urlParams = new URLSearchParams(window.location.search);
            const modalToOpen = urlParams.get('open_modal');

            // Fungsi pembantu agar tidak menulis ulang kode yang sama
                const setupTomSelect = (id, isMulti = false, placeholder = "Pilih...") => {
                    const el = document.getElementById(id);
                    if (!el || el.tomselect) return;
                        new TomSelect(`#${id}`, {
                            plugins: isMulti ? ['remove_button'] : [],
                            create: false,
                            placeholder: placeholder,
                            maxItems: isMulti ? 10 : 1 // Batasi 10 untuk tim, 1 untuk lead
                        });
                };

                // 1. Inisialisasi PIC (Modal Report) - Multiple
                setupTomSelect('create_pic_dashboard', true, "Pilih Personil...");

                // 2. Inisialisasi Lead Engineer (Modal Project) - Single
                setupTomSelect('create_lead_engineer_dashboard', false, "Pilih Lead Engineer...");

                // 3. Inisialisasi Team (Modal Project) - Multiple
                setupTomSelect('create_team', true, "Pilih Anggota Tim...");

            // 2. Jika parameternya adalah 'adduser', maka buka modalnya
            if (modalToOpen === 'adduser') {
                // Pastikan fungsi openModal sudah ada (dari layouts/scripts.php)
                if (typeof openModal === "function") {
                    openModal('modalAddUser');
                } else {
                    // Jika script global belum termuat, kita buka manual lewat class
                    const modal = document.getElementById('modalAddUser');
                    if (modal) modal.classList.remove('hidden');
                }
                
                // 3. (Opsional) Bersihkan URL agar saat di-refresh modal tidak muncul terus
                window.history.replaceState(null, null, window.location.pathname);
            }

            if (tableBody) {
                // Simpan semua baris asli saat pertama kali load
                allRows = Array.from(tableBody.querySelectorAll('tr.project-row'));
                
                // Jalankan render pertama kali
                renderTable();
            }

            // --- FUNGSI SEARCH (LIVESEARCH) ---
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    currentSearchKeyword = this.value.toLowerCase();
                    currentPage = 1; // Reset ke halaman 1 setiap kali mengetik
                    renderTable();
                });
            }
        })();

        // FUNGSI UTAMA: RENDER ULANG TABEL
        function renderTable() {
            const tableBody = document.querySelector('#projectTableBody');
            const pageInfo = document.getElementById('pageInfo');
            const paginationControls = document.getElementById('paginationControls');

            if (!tableBody) return;

            // 1. Filter Data berdasarkan keyword search
            const filteredRows = allRows.filter(row => {
                return row.textContent.toLowerCase().includes(currentSearchKeyword);
            });

            // 2. Hitung Pagination
            const totalItems = filteredRows.length;
            const totalPages = Math.ceil(totalItems / rowsPerPage);

            if (currentPage > totalPages) currentPage = totalPages || 1;

            // 3. Tentukan Baris mana yang tampil (Slice)
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            // 4. Sembunyikan semua, lalu munculkan yang lolos filter & masuk halaman
            allRows.forEach(row => row.style.display = 'none');
            
            filteredRows.slice(start, end).forEach(row => {
                row.style.display = '';
            });

            // 5. Update Info "Showing X of Y entries"
            if (pageInfo) {
                const startInfo = totalItems === 0 ? 0 : start + 1;
                const endInfo = Math.min(end, totalItems);
                pageInfo.innerText = `Showing ${startInfo} - ${endInfo} of ${totalItems} entries`;
            }

            // 6. Gambar ulang tombol angka (1, 2, 3...)
            renderPaginationButtons(totalPages);
        }

        function renderPaginationButtons(totalPages) {
            const container = document.getElementById('paginationControls');
            if (!container) return;
            container.innerHTML = "";

            if (totalPages <= 1) return;

            // Helper Button
            const createBtn = (text, page, isAction = false) => {
                const btn = document.createElement('button');
                btn.innerText = text;
                const isActive = (page === currentPage && !isAction);
                btn.className = `px-3 py-1 rounded transition text-xs ${isActive ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'}`;
                
                btn.onclick = () => {
                    currentPage = page;
                    renderTable();
                };
                return btn;
            };

            // Prev
            if (currentPage > 1) container.appendChild(createBtn("Prev", currentPage - 1, true));
            
            // Angka
            for (let i = 1; i <= totalPages; i++) {
                container.appendChild(createBtn(i, i));
            }

            // Next
            if (currentPage < totalPages) container.appendChild(createBtn("Next", currentPage + 1, true));
        }

        // --- 2. FUNGSI EDIT & DELETE (TETAP DISINI) ---
        function editProject(id, name, desc, date, cat, team, act, plant, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_desc').value = desc;
            document.getElementById('edit_date').value = date;
            if (document.getElementById('edit_status')) document.getElementById('edit_status').value = status;
            
            if (typeof tomSelectEdit !== 'undefined' && tomSelectEdit) {
                tomSelectEdit.clear();
                if (team) {
                    team.split(',').map(t => t.trim()).forEach(m => tomSelectEdit.addItem(m));
                }
            }
            openModal('modalEditProject');
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Project?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'delete/delete_project.php?id=' + id + '&redirect=dashboard.php';
            });
        }
    </script>

    </body>
</html>