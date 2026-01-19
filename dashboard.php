<?php
session_start();
// --- CEK LOGIN (SATPAM) ---
// Jika session 'user_id' tidak ada, artinya dia belum login.
if (!isset($_SESSION['user_id'])) {
    // Tendang balik ke halaman login
    header("Location: index.php");
    exit();
}

include 'config.php';
// Tambahkan: AND role != 'admin'
$queryUsers = mysqli_query($conn, "SELECT short_name FROM tb_users WHERE short_name IS NOT NULL AND short_name != '' AND role != 'admin'");
$teamList = [];
while ($u = mysqli_fetch_assoc($queryUsers)) {
    $teamList[] = $u['short_name'];
}

// 5. LOGIC NOTIFIKASI DETAILED
// A. Ambil Data Breakdown (Max 5 terbaru)
$queryBreakdownList = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE category='Breakdown' AND status='Open' ORDER BY date_log DESC LIMIT 5");
$countBreakdown = mysqli_num_rows($queryBreakdownList); // Hitung jumlahnya

// B. Ambil Data Project Overdue (Max 5 terparah)
$today = date('Y-m-d');
$queryOverdueList = mysqli_query($conn, "SELECT * FROM tb_projects WHERE due_date < '$today' AND status != 'Done' ORDER BY due_date ASC LIMIT 5");
$countOverdue = mysqli_num_rows($queryOverdueList); // Hitung jumlahnya

// Total Angka Notif (Untuk Badge Merah)
$totalNotif = $countBreakdown + $countOverdue;

// --- LOGIC KPI CARDS (UPDATED) ---
// 1. Hitung Project TO DO
$queryToDo = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE status = 'To Do'");
$totalToDo = mysqli_fetch_assoc($queryToDo)['total'];

// 2. Hitung Project IN PROGRESS
$queryProg = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE status = 'In Progress'");
$totalProgress = mysqli_fetch_assoc($queryProg)['total'];

// 3. Hitung Project DONE
// (Saya ambil dari tb_projects sesuai konteks status Done, karena tb_daily_project tidak ada di skema kita)
$queryDone = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_daily_reports WHERE status = 'Solved'");
$totalDone = mysqli_fetch_assoc($queryDone)['total'];

// 4. Hitung TOTAL ASSETS (Tetap)
$queryAsset = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_assets");
$totalAssets = mysqli_fetch_assoc($queryAsset)['total'];

// --- QUERY UNTUK TAB 2: MY DAILY REPORT ---
// 1. Ambil ID User dari Session (Pasti ada karena sudah lolos cek login)
$id_user = $_SESSION['user_id'];

// 2. Cari nama panggilan (short_name) user tersebut di database tb_users
$qUser = mysqli_query($conn, "SELECT short_name FROM tb_users WHERE user_id='$id_user'");
$dUser = mysqli_fetch_assoc($qUser);

// 3. Simpan ke variabel $myName
// (Pakai operator ?? 'User' untuk jaga-jaga kalau datanya kosong/error)
$myName = $dUser['short_name'] ?? 'User';

// 4. Jalankan Query Laporan dengan Filter Nama User tersebut
$queryMyReport = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE pic LIKE '%$myName%' ORDER BY date_log DESC, time_start DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#03142c">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Command Center</title>

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
    <script src="assets/vendor/tom-select.complete.min.js"></script>
    <script defer src="assets/vendor/alpine.js"></script>
    

    <style>
        /* Tambahkan #modalEditProject di sini */
        #modalProject:not(.hidden)>div:last-child>div,
        #modalEditProject:not(.hidden)>div:last-child>div {
            animation: popUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes popUp {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>

    <style>
        /* Custom Style untuk Tom Select di Dark Mode */
        .ts-control {
            background-color: #0f172a !important;
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
            background-color: #4f46e5 !important;
            color: #fff !important;
            border-radius: 4px;
        }

        .ts-wrapper.multi .ts-control>div {
            background-color: #4f46e5 !important;
            color: white !important;
        }
    </style>

    <style>
        /* 1. Supaya kursor jadi telunjuk saat hover di icon Kalender & Jam */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        /* 2. MODE GELAP*/
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
</head>
<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col transition-all duration-300 hidden md:flex">
            <div class="h-16 flex items-center justify-center border-b border-slate-800">
                <h1 class="text-xl font-bold text-white tracking-wide">JIS <span class="text-emerald-400">PORTAL.</span></h1>
            </div>

            <!-- SIDEBAR ADA DISINI -->
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="#" class="nav-item active">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <div class="relative">
                    <button onclick="toggleDbMenu()" class="nav-item w-full flex justify-between items-center focus:outline-none group">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-database w-6 group-hover:text-emerald-400 transition"></i>
                            <span class="group-hover:text-white transition">Database</span>
                        </div>
                        <i id="arrowDb" class="fas fa-chevron-down text-xs text-slate-500 transition-transform duration-200"></i>
                    </button>

                    <div id="dbSubmenu" class="hidden pl-10 space-y-1 mt-1 bg-slate-900/50 py-2 border-l border-slate-800 ml-3">
                        <a href="database.php" class="block text-sm text-slate-400 hover:text-emerald-400 transition py-1">
                            • Machine / Assets
                        </a>
                        <a href="master_items.php" class="block text-sm text-slate-400 hover:text-emerald-400 transition py-1">
                            • Master Items
                        </a>
                    </div>
                </div>

                <a href="laporan.php" class="nav-item">
                    <i class="fas fa-clipboard-list w-6"></i>
                    <span>Daily Report</span>
                </a>

                <a href="project.php" class="nav-item">
                    <i class="fas fa-project-diagram w-6"></i>
                    <span>Projects</span>
                </a>

                <a href="overtime.php" class="nav-item">           
                    <i class="fas fa-clock w-6"></i>
                    <span>Overtime</span>
                </a>

                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                    <a href="javascript:void(0)" onclick="openModal('modalAddUser')" class="nav-item hover:text-emerald-400 transition">
                        <i class="fa-solid fa-user-plus w-6"></i>
                        <span>Add User</span>
                    </a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <div class="px-4 py-2 text-xs font-bold text-slate-500 uppercase tracking-wider mt-4">Admin Menu</div>
                    <a href="manage_users.php" class="nav-item">
                        <i class="fas fa-users-cog w-6"></i> <span class="font-medium">User Management</span>
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="nav-item">
                    <i class="fas fa-solid fa-right-from-bracket w-6"></i>
                    <span>Logout</span>
                </a>
            </nav>

            <!-- USER PROFILE ADA DISINI -->
            <div class="p-4 border-t border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-700 border border-slate-500 overflow-hidden flex items-center justify-center">
                        <img src="image/default_profile.png"
                            alt="User Profile"
                            class="w-full h-full object-cover scale-125 transition-transform hover:scale-150">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">
                            <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest'; ?>
                        </p>
                        <p class="text-xs text-emerald-500">Online</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">

            <!-- HEADER ADA DISINI -->
            <header class="h-16 shrink-0 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-10 px-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="text-slate-400 hover:text-white mr-4 transition-transform active:scale-95">
                    </button>
                    <h1 class="text-lg font-medium text-white">Department Performance</h1>
                </div>

                <div class="flex items-center gap-4">
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                        <button id="presentationModeBtn" class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium bg-indigo-600 hover:bg-indigo-500 text-white rounded-full transition shadow-lg shadow-indigo-500/30">
                            <i class="fas fa-tv"></i> <span>Presentation Mode</span>
                        </button>
                    <?php endif; ?>

                    <div class="relative">
                        <button onclick="toggleNotif()" class="p-2 text-slate-400 hover:text-white relative transition focus:outline-none">
                            <i class="fas fa-bell"></i>
                            <?php if ($totalNotif > 0): ?>
                                <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-slate-900 animate-pulse"></span>
                            <?php endif; ?>
                        </button>

                        <button onclick="toggleTheme()" class="p-2 text-slate-400 hover:text-white transition focus:outline-none mr-2" title="Ganti Tema">
                            <i id="themeIcon" class="fas fa-sun"></i>
                        </button>

                        <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-72 bg-slate-800 border border-slate-700 rounded-lg shadow-xl z-50 overflow-hidden origin-top-right transform transition-all">
                            <div class="px-4 py-3 border-b border-slate-700 bg-slate-900/50 flex justify-between items-center">
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Notifications</h3>
                                <span class="text-[10px] bg-slate-700 text-slate-300 px-1.5 py-0.5 rounded"><?php echo $totalNotif; ?> New</span>
                            </div>

                            <div class="max-h-80 overflow-y-auto custom-scroll">
                                <?php if ($totalNotif == 0): ?>
                                    <div class="px-4 py-6 text-center text-slate-500">
                                        <i class="fas fa-check-circle text-2xl mb-2 text-emerald-500/50"></i>
                                        <p class="text-xs">Semua sistem aman.</p>
                                    </div>
                                <?php else: ?>

                                    <?php if ($countBreakdown > 0): ?>
                                        <div class="px-4 py-2 bg-red-500/10 text-red-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-700">
                                            Mesin Breakdown (<?php echo $countBreakdown; ?>)
                                        </div>
                                        <?php while ($rowBD = mysqli_fetch_assoc($queryBreakdownList)): ?>
                                            <a href="laporan.php" class="block px-4 py-3 hover:bg-slate-700 transition border-b border-slate-700/30 group">
                                                <div class="flex items-start gap-3">
                                                    <div class="bg-red-500/20 p-1.5 rounded text-red-400 mt-0.5 group-hover:bg-red-500 group-hover:text-white transition"><i class="fas fa-car-crash text-xs"></i></div>
                                                    <div>
                                                        <p class="text-xs font-bold text-white"><?php echo $rowBD['machine_name']; ?></p>
                                                        <p class="text-[10px] text-slate-400 line-clamp-1"><?php echo $rowBD['problem']; ?></p>
                                                        <p class="text-[10px] text-red-400 mt-1">Sejak: <?php echo date('d M, H:i', strtotime($rowBD['date_log'] . ' ' . $rowBD['time_start'])); ?></p>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endwhile; ?>
                                    <?php endif; ?>

                                    <?php if ($countOverdue > 0): ?>
                                        <div class="px-4 py-2 bg-orange-500/10 text-orange-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-700">
                                            Project Overdue (<?php echo $countOverdue; ?>)
                                        </div>
                                        <?php while ($rowOD = mysqli_fetch_assoc($queryOverdueList)): ?>
                                            <a href="#table-project" class="block px-4 py-3 hover:bg-slate-700 transition border-b border-slate-700/30 group">
                                                <div class="flex items-start gap-3">
                                                    <div class="bg-orange-500/20 p-1.5 rounded text-orange-400 mt-0.5 group-hover:bg-orange-500 group-hover:text-white transition"><i class="far fa-clock text-xs"></i></div>
                                                    <div>
                                                        <p class="text-xs font-bold text-white"><?php echo $rowOD['project_name']; ?></p>
                                                        <p class="text-[10px] text-slate-400">Lead: <?php echo explode(',', $rowOD['team_members'])[0]; ?></p>
                                                        <?php
                                                        $due = strtotime($rowOD['due_date']);
                                                        $now = time();
                                                        $daysLate = floor(($now - $due) / (60 * 60 * 24));
                                                        ?>
                                                        <p class="text-[10px] text-orange-400 mt-1 font-bold">Telat <?php echo $daysLate; ?> hari (<?php echo date('d M', $due); ?>)</p>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-8 space-y-8 fade-in">
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
    <div class="mt-auto py-6 px-8 border-t border-slate-800/50 flex flex-col md:flex-row justify-between items-center gap-2">
        <p class="text-[10px] text-slate-600 font-medium tracking-wide">
            &copy; <?php echo date('Y'); ?> JIS Automation Dept. <span class="hidden md:inline">- Internal Use Only.</span>
        </p>
        <p class="text-[10px] text-slate-600 font-medium tracking-wide flex items-center gap-1">
            Maintained by <span class="text-slate-500 hover:text-emerald-500 transition cursor-default">zaan</span>
            <i class="fas fa-code text-[8px] opacity-50"></i>
        </p>
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
                        <select name="team[]" id="create_team" multiple placeholder="Pilih Tim..." autocomplete="off" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
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

    <!-- SELURUH SCRIPT YANG DIBUTUHKAN DI HALAMAN DASHBOARD -->
    <script>
        // --- LOGIC MEMBUKA MODAL DARI URL (Untuk Add User) ---
        const urlParamsModal = new URLSearchParams(window.location.search);
        if (urlParamsModal.get('open_modal') === 'adduser') {
            // Tunggu sebentar biar halaman loading sempurna
            setTimeout(() => {
                if (typeof openModal === 'function') {
                    openModal('modalAddUser');
                }
                // Bersihkan URL biar pas refresh gak muncul lagi
                const newUrl = window.location.pathname;
                window.history.replaceState(null, null, newUrl);
            }, 100);
        }
        // 1. FUNGSI ISI DATA KE MODAL EDIT
        function editProject(id, name, desc, date, cat, team, act, plant, status) {

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_desc').value = desc;
            document.getElementById('edit_date').value = date;

            // Isi Status (To Do / In Progress / Done)
            if (document.getElementById('edit_status')) {
                document.getElementById('edit_status').value = status;
            }

            document.getElementById('edit_team').value = team;
            document.getElementById('edit_act').value = act;
            document.getElementById('edit_plant').value = plant;

            openModal('modalEditProject');
        }

        // 2. FUNGSI KONFIRMASI HAPUS
        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Project?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete/delete_project.php?id=' + id + '&redirect=project.php';
                }
            })
        }

        // 3. MASTER NOTIFIKASI (Create, Update, Delete)
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        if (status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: msg || 'Data berhasil disimpan.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669',
                iconColor: '#34d399'
            }).then(() => cleanUrl());
        } else if (status === 'updated') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Diupdate!',
                text: msg || 'Data telah diperbarui.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669',
                iconColor: '#34d399'
            }).then(() => cleanUrl());
        } else if (status === 'deleted') {
            Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: msg || 'Data telah dihapus.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669',
                iconColor: '#34d399'
            }).then(() => cleanUrl());
        } else if (status === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: msg || 'Terjadi kesalahan sistem.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ef4444'
            }).then(() => cleanUrl());
        }

        // Fungsi Bersihkan URL (Agar pas refresh notifikasi gak muncul lagi)
        function cleanUrl() {
            window.history.replaceState(null, null, window.location.pathname);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // KONFIGURASI
            const rowsPerPage = 10; // Mau tampil berapa baris?

            // SELEKTOR
            const tableBody = document.querySelector('tbody'); // Pastikan ini mengarah ke tbody tabel project
            const allRows = Array.from(tableBody.querySelectorAll('tr')); // Ambil semua data asli
            const searchInput = document.getElementById('searchInput');
            const pageInfo = document.getElementById('pageInfo');
            const paginationControls = document.getElementById('paginationControls');

            let currentPage = 1;
            let currentSearchKeyword = "";

            // FUNGSI UTAMA: RENDER ULANG TABEL
            function renderTable() {
                // 1. Filter Data dulu (Sesuai Search Bapak)
                const filteredRows = allRows.filter(row => {
                    const text = row.textContent.toLowerCase();
                    return text.includes(currentSearchKeyword);
                });

                // 2. Hitung Pagination
                const totalItems = filteredRows.length;
                const totalPages = Math.ceil(totalItems / rowsPerPage);

                // Pastikan halaman tidak nyasar (misal lagi di page 5, terus search hasilnya cuma 2 data, harus balik ke page 1)
                if (currentPage > totalPages) currentPage = 1;
                if (currentPage < 1) currentPage = 1;

                // 3. Hitung Index Potong (Slice)
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                // 4. Manipulasi Tampilan (Hide All -> Show Slice)
                allRows.forEach(row => row.style.display = 'none'); // Sembunyikan SEMUA data asli

                // Tampilkan hanya yang lolos filter DAN masuk range halaman
                filteredRows.slice(start, end).forEach(row => {
                    row.style.display = '';
                });

                // 5. Update Info Teks
                const startInfo = totalItems === 0 ? 0 : start + 1;
                const endInfo = Math.min(end, totalItems);
                pageInfo.innerText = `Showing ${startInfo} - ${endInfo} of ${totalItems} entries`;

                // 6. Bikin Tombol
                renderButtons(totalPages);
            }

            // FUNGSI BIKIN TOMBOL (Prev 1 2 3 Next)
            function renderButtons(totalPages) {
                paginationControls.innerHTML = ""; // Reset tombol

                // Jangan tampilkan tombol kalau cuma 1 halaman
                if (totalPages <= 1) return;

                // Helper buat bikin tombol
                const createBtn = (text, page, isActive = false, isDisabled = false) => {
                    const btn = document.createElement('button');
                    btn.innerText = text;
                    btn.className = `px-3 py-1 rounded transition text-xs ${isActive ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'}`;
                    if (isDisabled) {
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                        btn.disabled = true;
                    }
                    btn.addEventListener('click', () => {
                        currentPage = page;
                        renderTable();
                    });
                    return btn;
                };

                // Tombol PREV
                paginationControls.appendChild(createBtn("Prev", currentPage - 1, false, currentPage === 1));

                // Tombol Angka (1, 2, 3...)
                for (let i = 1; i <= totalPages; i++) {
                    paginationControls.appendChild(createBtn(i, i, i === currentPage));
                }

                // Tombol NEXT
                paginationControls.appendChild(createBtn("Next", currentPage + 1, false, currentPage === totalPages));
            }

            // LISTENER: SAAT KETIK SEARCH
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    currentSearchKeyword = this.value.toLowerCase();
                    currentPage = 1; // Reset ke halaman 1 setiap kali mengetik
                    renderTable();
                });
            }

            // Jalankan pertama kali
            renderTable();
        });

        // --- FUNGSI TOGGLE NOTIFIKASI ---
        function toggleNotif() {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
            }
        }

        // Tutup dropdown kalau klik di luar area
        window.addEventListener('click', function(e) {
            const btn = document.querySelector('button[onclick="toggleNotif()"]');
            const dropdown = document.getElementById('notifDropdown');

            // Jika yang diklik BUKAN tombol lonceng DAN BUKAN dropdown itu sendiri
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // --- INISIALISASI TOM SELECT (MULTI SELECT) ---
        let tomSelectCreate;
        let tomSelectEdit;
        let tomSelectPicDashboard; // Variabel baru untuk PIC

        document.addEventListener('DOMContentLoaded', function() {

            // 1. Aktifkan di Modal Create Project
            if (document.getElementById('create_team')) {
                tomSelectCreate = new TomSelect("#create_team", {
                    plugins: ['remove_button'],
                    create: false,
                    maxItems: 5
                });
            }

            // 2. Aktifkan di Modal Edit Project
            if (document.getElementById('edit_team')) {
                tomSelectEdit = new TomSelect("#edit_team", {
                    plugins: ['remove_button'],
                    create: false,
                    maxItems: 5
                });
            }

            // 3. AKTIFKAN DI MODAL LAPORAN DASHBOARD (INI YANG KURANG)
            if (document.getElementById('create_pic_dashboard')) {
                tomSelectPicDashboard = new TomSelect("#create_pic_dashboard", {
                    plugins: ['remove_button'],
                    create: false, // Tidak boleh nambah nama baru manual
                    maxItems: 5,
                    placeholder: "Pilih PIC..."
                });
            }

        });

        // --- UPDATE FUNGSI EDIT PROJECT (PENTING!) ---
        // Kita harus memecah string "Budi, Andi" menjadi pilihan terseleksi
        function editProject(id, name, desc, date, cat, team, act, plant, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_desc').value = desc;
            document.getElementById('edit_date').value = date;

            if (document.getElementById('edit_status')) document.getElementById('edit_status').value = status;
            if (document.getElementById('edit_cat')) document.getElementById('edit_cat').value = cat;
            document.getElementById('edit_act').value = act;
            document.getElementById('edit_plant').value = plant;

            // LOGIC MULTI SELECT (Load Data Lama)
            if (tomSelectEdit) {
                tomSelectEdit.clear(); // Bersihkan dulu
                if (team) {
                    // Pecah string "Budi, Andi" jadi array ["Budi", "Andi"]
                    const members = team.split(',').map(item => item.trim());
                    members.forEach(member => {
                        tomSelectEdit.addItem(member); // Pilih satu per satu
                    });
                }
            }

            openModal('modalEditProject');
        }

        // --- TAMBAHAN LOGIC UPLOAD 5 GAMBAR (Sama seperti di laporan.php) ---
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('file_evidence');
            const fileNameDisplay = document.getElementById('file-name-display');

            if (fileInput && fileNameDisplay) {
                fileInput.addEventListener('change', function(e) {
                    const files = this.files;

                    // 1. Validasi Maksimal 5 File
                    if (files.length > 5) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Terlalu Banyak!',
                            text: 'Maksimal hanya boleh upload 5 gambar sekaligus.',
                            confirmButtonColor: '#f59e0b',
                            background: '#1e293b',
                            color: '#fff'
                        });
                        this.value = ''; // Reset
                        fileNameDisplay.classList.add('hidden');
                        return;
                    }

                    // 2. Tampilkan Info Nama File
                    if (files.length > 0) {
                        fileNameDisplay.classList.remove('hidden');
                        if (files.length === 1) {
                            fileNameDisplay.textContent = `📄 ${files[0].name}`;
                        } else {
                            fileNameDisplay.textContent = `📂 ${files.length} file dipilih`;
                        }
                    } else {
                        fileNameDisplay.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    <script src="assets/js/ui-sidebar.js"></script>
    <script src="assets/js/ui-modal.js"></script>

<button onclick="toggleMobileMenu()" id="mobileMenuBtn" class="fixed bottom-24 right-4 z-[60] md:hidden bg-emerald-600/50 text-white w-12 h-12 rounded-full shadow-lg shadow-emerald-900/50 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 border-1 border-slate-900">
    <i id="iconOpen" class="fas fa-bars text-lg"></i>
    <i id="iconClose" class="fas fa-times text-lg hidden"></i>
</button>

<nav id="mobileNavbar" class="fixed bottom-4 left-4 right-4 bg-slate-900/90 backdrop-blur-md border border-slate-700 rounded-2xl flex justify-around items-center py-3 z-50 md:hidden transition-transform duration-300 ease-in-out translate-y-[150%] shadow-2xl">

    <?php $page = basename($_SERVER['PHP_SELF']); ?>

    <a href="dashboard.php" class="flex flex-col items-center gap-1 w-1/5 transition group <?php echo ($page == 'dashboard.php') ? 'text-emerald-400' : 'text-slate-400 hover:text-emerald-300'; ?>">
        <i class="fa-solid fa-house-chimney text-lg mb-0.5 group-active:scale-90 transition"></i>
        <span class="text-[9px] font-medium uppercase tracking-wide">Home</span>
    </a>

    <a href="database.php" class="flex flex-col items-center gap-1 w-1/5 transition group <?php echo ($page == 'database.php' || $page == 'master_items.php') ? 'text-emerald-400' : 'text-slate-400 hover:text-emerald-300'; ?>">
        <i class="fas fa-database text-lg mb-0.5 group-active:scale-90 transition"></i>
        <span class="text-[9px] font-medium uppercase tracking-wide">Database</span>
    </a>

    <a href="laporan.php" class="flex flex-col items-center gap-1 w-1/5 transition group <?php echo ($page == 'laporan.php' || $page == 'my_laporan.php') ? 'text-emerald-400' : 'text-slate-400 hover:text-emerald-300'; ?>">
        <i class="fas fa-clipboard-list text-lg mb-0.5 group-active:scale-90 transition"></i>
        <span class="text-[9px] font-medium uppercase tracking-wide">Report</span>
    </a>

    <a href="project.php" class="flex flex-col items-center gap-1 w-1/5 transition group <?php echo ($page == 'project.php') ? 'text-emerald-400' : 'text-slate-400 hover:text-emerald-300'; ?>">
        <i class="fas fa-project-diagram text-lg mb-0.5 group-active:scale-90 transition"></i>
        <span class="text-[9px] font-medium uppercase tracking-wide">Projects</span>
    </a>

    <a href="overtime.php" class="flex flex-col items-center gap-1 w-1/5 transition group <?php echo ($page == 'overtime.php') ? 'text-emerald-400' : 'text-slate-400 hover:text-emerald-300'; ?>">
        <i class="fas fa-clock text-lg mb-0.5 group-active:scale-90 transition"></i>
        <span class="text-[9px] font-medium uppercase tracking-wide">Overtime</span>
    </a>

    <a href="logout.php" class="flex flex-col items-center gap-1 w-1/5 text-slate-500 hover:text-red-400 transition group">
        <i class="fas fa-sign-out-alt text-lg mb-0.5 group-active:scale-90 transition"></i>
        <span class="text-[9px] font-medium uppercase tracking-wide">Logout</span>
    </a>

</nav>

<script>
    function toggleMobileMenu() {
        const navbar = document.getElementById('mobileNavbar');
        const iconOpen = document.getElementById('iconOpen');
        const iconClose = document.getElementById('iconClose');
        const btn = document.getElementById('mobileMenuBtn');

        // Toggle Class untuk menampilkan/menyembunyikan Navbar
        // translate-y-[150%] artinya geser ke bawah sejauh 150% dari tingginya (ngumpet)
        // translate-y-0 artinya kembali ke posisi asal (muncul)
        if (navbar.classList.contains('translate-y-[150%]')) {
            // MUNCULKAN MENU
            navbar.classList.remove('translate-y-[150%]');
            navbar.classList.add('translate-y-0');
            
            // Ubah Icon jadi X
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');

            // Ubah warna tombol jadi merah (biar kelihatan tombol close)
            btn.classList.remove('bg-emerald-600');
            btn.classList.add('bg-slate-700');
        } else {
            // SEMBUNYIKAN MENU
            navbar.classList.add('translate-y-[150%]');
            navbar.classList.remove('translate-y-0');
            
            // Ubah Icon jadi Hamburger
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');

            // Balikin warna tombol
            btn.classList.add('bg-emerald-600');
            btn.classList.remove('bg-slate-700');
        }
    }
</script>
</body>

</html>