<?php
session_start();
// --- CEK LOGIN (SATPAM) ---
// Jika session 'user_id' tidak ada, artinya dia belum login.
if (!isset($_SESSION['user_id'])) {
    // Tendang balik ke halaman login
    header("Location: index.php");
    exit(); // Stop script di sini, jangan lanjut ke bawah!
}
include 'config.php';
$queryUsers = mysqli_query($conn, "SELECT short_name FROM tb_users WHERE short_name IS NOT NULL AND short_name != '' AND role != 'admin'");
$teamList = [];
while ($u = mysqli_fetch_assoc($queryUsers)) {
    $teamList[] = $u['short_name'];
}
// --- LOGIC DATA GANTT CHART ---
// Ambil semua project (Urutkan berdasarkan tanggal mulai/deadline)
$qChart = mysqli_query($conn, "SELECT * FROM tb_projects ORDER BY due_date ASC");
$ganttData = [];

while ($r = mysqli_fetch_assoc($qChart)) {
    // 1. Tentukan Start Date
    $start = !empty($r['created_at']) ? strtotime($r['created_at']) : strtotime($r['due_date'] . ' -7 days');
    $end   = strtotime($r['due_date']);

    // Validasi: Start gak boleh lebih besar dari End
    if ($start > $end) $start = $end - (86400 * 3);

    // 2. Tentukan Warna Bar berdasarkan Status
    $color = '#94a3b8'; // Default Abu (To Do)
    if ($r['status'] == 'In Progress') $color = '#3b82f6'; // Biru
    if ($r['status'] == 'Done') $color = '#10b981';        // Hijau
    // Jika Overdue dan belum Done -> Merah
    if ($r['status'] != 'Done' && time() > $end) $color = '#ef4444';

    // 3. Susun Array
    $ganttData[] = [
        'x' => $r['project_name'],
        'y' => [$start * 1000, $end * 1000], // Convert ke milidetik
        'fillColor' => $color,
        'meta' => $r['status'] . ' (' . $r['activity'] . ')' // Info tambahan
    ];
}
$jsonGantt = json_encode($ganttData);

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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#03142c">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management - Automation Portal</title>

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
    <script src="assets/vendor/tom-select.complete.min.js"></script>
    <script defer src="assets/vendor/alpine.js"></script>
    <script src="assets/vendor/apexcharts.js"></script>
    <script src="assets/vendor/sweetalert2.all.min.js"></script>

    <style>
        /* Kita paksa animasi jalan untuk Modal Project */
        #modalProject:not(.hidden)>div:last-child>div {
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

        /* Styling agar cocok dengan Dark Mode */
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
            background-color: #4f46e5 !important;
            color: #fff !important;
            border-radius: 4px;
        }

        .ts-wrapper.multi .ts-control>div {
            background-color: #4f46e5 !important;
            color: white !important;
        }

        /* Animasi Modal (Biar tetap membal) */
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
        /* Animasi Dropdown Project Menu */
        .project-menu {
            transform-origin: top right;
            transition: all 0.1s ease-out;
        }

        .project-menu.hidden {
            opacity: 0;
            transform: scale(0.95);
            pointer-events: none;
            /* Biar gak bisa diklik pas sembunyi */
        }

        .project-menu:not(.hidden) {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
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
</head>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">

    <div class="flex h-screen overflow-hidden">

        <aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col transition-all duration-300 hidden md:flex">
            <div class="h-16 flex items-center justify-center border-b border-slate-800">
                <h1 class="text-xl font-bold text-white tracking-wide">JIS <span class="text-emerald-400">PORTAL.</span></h1>
            </div>

            <!-- SIDEBAR ADA DISINI -->
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="dashboard.php" class="nav-item">
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

                <a href="project.php" class="nav-item active">
                    <i class="fas fa-project-diagram w-6"></i>
                    <span>Projects</span>
                </a>

                <a href="overtime.php" class="nav-item">
                    <i class="fas fa-clock w-6"></i>
                    <span>Overtime</span>
                </a>

                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                    <a href="dashboard.php?open_modal=adduser" class="nav-item hover:text-emerald-400 transition">
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
            <header class="h-16 shrink-0 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50 px-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="text-slate-400 hover:text-white mr-4 transition-transform active:scale-95">
                        <!-- <i class="fas fa-bars text-xl"></i> -->
                    </button>
                    <h2 class="text-lg font-medium text-white">Project Timeline</h2>
                </div>

                <!-- TOPBAR ADA DISINI -->
                <div class="flex items-center gap-4">
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                        <button id="btnNewProject" onclick="openModal('modalProject')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-1.5 rounded-full text-sm font-medium transition shadow-lg shadow-indigo-600/20 flex items-center gap-2">
                            <i class="fas fa-plus"></i> New Project
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
                                    <div class="p-4 space-y-4 overflow-y-auto custom-scroll h-full">
                                        <?php
                                        // Query Done
                                        $query = mysqli_query($conn, "SELECT * FROM tb_projects WHERE status='Done' ORDER BY due_date DESC LIMIT 10");
                                        while ($row = mysqli_fetch_assoc($query)) {
                                        ?>

                                            <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 shadow opacity-75 hover:opacity-100 transition cursor-pointer group relative">

                                                <div class="flex justify-between items-start mb-2">
                                                    <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                                                        <?php echo $row['category_badge']; ?>
                                                    </span>

                                                    <div class="relative">
                                                        <button onclick="event.stopPropagation(); toggleProjectMenu('menu-<?php echo $row['project_id']; ?>')" class="text-emerald-600 hover:text-emerald-400 p-1 rounded transition hover:bg-slate-700/50">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>

                                                        <div id="menu-<?php echo $row['project_id']; ?>" class="project-menu hidden absolute right-0 top-6 w-32 bg-slate-900 border border-slate-700 rounded-lg shadow-xl z-20 overflow-hidden">

                                                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                                <button onclick="event.stopPropagation(); editProject(
                                                '<?php echo $row['project_id']; ?>',
                                                '<?php echo htmlspecialchars($row['project_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>',
                                                '<?php echo $row['due_date']; ?>',
                                                '<?php echo htmlspecialchars($row['category_badge'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['team_members'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['activity'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>',
                                                '<?php echo $row['status']; ?>'
                                            )" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-blue-600 hover:text-white transition flex items-center gap-2">
                                                                    <i class="fas fa-pen"></i> Edit
                                                                </button>
                                                                <button onclick="event.stopPropagation(); confirmDeleteProject(<?php echo $row['project_id']; ?>)" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-red-600 hover:text-white transition flex items-center gap-2 border-t border-slate-700/50">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h4 class="text-slate-400 font-medium mb-1 line-through group-hover:no-underline group-hover:text-emerald-400 transition text-sm">
                                                    <?php echo $row['project_name']; ?>
                                                </h4>

                                                <p class="text-xs text-slate-500 mb-3 line-clamp-1"><?php echo $row['description']; ?></p>

                                                <div class="flex justify-between items-center border-t border-slate-700 pt-2">
                                                    <div class="p-4 space-y-4 overflow-y-auto custom-scroll h-full">
                                                        <?php
                                                        // Query Done
                                                        $query = mysqli_query($conn, "SELECT * FROM tb_projects WHERE status='Done' ORDER BY due_date DESC LIMIT 10");
                                                        while ($row = mysqli_fetch_assoc($query)) {
                                                        ?>

                                                            <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 shadow opacity-75 hover:opacity-100 transition cursor-pointer group relative">

                                                                <div class="flex justify-between items-start mb-2">
                                                                    <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                                                                        <?php echo $row['category_badge']; ?>
                                                                    </span>

                                                                    <div class="relative">
                                                                        <button onclick="event.stopPropagation(); toggleProjectMenu('menu-<?php echo $row['project_id']; ?>')" class="text-emerald-600 hover:text-emerald-400 p-1 rounded transition hover:bg-slate-700/50">
                                                                            <i class="fas fa-ellipsis-h"></i>
                                                                        </button>

                                                                        <div id="menu-<?php echo $row['project_id']; ?>" class="project-menu hidden absolute right-0 top-6 w-32 bg-slate-900 border border-slate-700 rounded-lg shadow-xl z-20 overflow-hidden">

                                                                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                                                <button onclick="event.stopPropagation(); editProject(
                                                '<?php echo $row['project_id']; ?>',
                                                '<?php echo htmlspecialchars($row['project_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>',
                                                '<?php echo $row['due_date']; ?>',
                                                '<?php echo htmlspecialchars($row['category_badge'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['team_members'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['activity'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>',
                                                '<?php echo $row['status']; ?>'
                                            )" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-blue-600 hover:text-white transition flex items-center gap-2">
                                                                                    <i class="fas fa-pen"></i> Edit
                                                                                </button>
                                                                                <button onclick="event.stopPropagation(); confirmDeleteProject(<?php echo $row['project_id']; ?>)" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-red-600 hover:text-white transition flex items-center gap-2 border-t border-slate-700/50">
                                                                                    <i class="fas fa-trash"></i> Delete
                                                                                </button>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <h4 class="text-slate-400 font-medium mb-1 line-through group-hover:no-underline group-hover:text-emerald-400 transition text-sm">
                                                                    <?php echo $row['project_name']; ?>
                                                                </h4>

                                                                <p class="text-xs text-slate-500 mb-3 line-clamp-1"><?php echo $row['description']; ?></p>

                                                                <div class="flex justify-between items-center border-t border-slate-700 pt-2">
                                                                    <span class="text-[10px] text-emerald-500 font-bold flex items-center gap-1">
                                                                        <!-- <i class="fas fa-check-circle"></i> Selesai -->
                                                                    </span>
                                                                    <span class="text-[10px] text-slate-500">
                                                                        <?php echo date('d M Y', strtotime($row['due_date'])); ?>
                                                                    </span>
                                                                </div>

                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                    <span class="text-[10px] text-slate-500">
                                                        <?php echo date('d M Y', strtotime($row['due_date'])); ?>
                                                    </span>
                                                </div>

                                            </div>
                                        <?php } ?>
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
            </header>

            <div class="p-8 fade-in h-full overflow-x-auto">

                <div class="flex flex-col md:flex-row gap-6 h-full">

                    <?php
                    $sqlCount1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE status='To Do'");
                    $count1 = mysqli_fetch_assoc($sqlCount1)['total'];
                    ?>
                    <div class="flex-1 min-w-[300px] flex flex-col bg-slate-800/50 rounded-xl border border-slate-700/50">
                        <div class="p-4 border-b border-slate-700/50 flex justify-between items-center sticky top-0 bg-slate-800/90 backdrop-blur rounded-t-xl z-10">
                            <h3 class="font-bold text-slate-300 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-500"></span> To Do
                            </h3>
                            <span class="bg-slate-700 text-slate-400 text-xs px-2 py-1 rounded-full"><?php echo $count1; ?></span>
                        </div>

                        <div class="p-4 space-y-4 overflow-y-auto custom-scroll">
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM tb_projects WHERE status='To Do' ORDER BY due_date ASC");
                            while ($row = mysqli_fetch_assoc($query)) {

                                // --- AREA PEMBERSIH DATA (SISIPKAN DISINI) ---
                                // 1. Ambil Data Mentah
                                $raw_name = $row['project_name'];
                                $raw_desc = $row['description'];
                                $raw_act  = $row['activity'];

                                // 2. Ganti Enter jadi "_ENTER_" & Escape Kutip
                                $clean_name = str_replace(array("\r", "\n"), "_ENTER_", $raw_name);
                                $clean_name = str_replace("'", "\'", $clean_name);

                                $clean_desc = str_replace(array("\r", "\n"), "_ENTER_", $raw_desc);
                                $clean_desc = str_replace("'", "\'", $clean_desc);

                                $clean_act  = str_replace(array("\r", "\n"), "_ENTER_", $raw_act);
                                $clean_act  = str_replace("'", "\'", $clean_act);

                                // 3. Bungkus htmlspecialchars
                                $clean_name = htmlspecialchars($clean_name, ENT_QUOTES);
                                $clean_desc = htmlspecialchars($clean_desc, ENT_QUOTES);
                                $clean_act  = htmlspecialchars($clean_act, ENT_QUOTES);
                            ?>
                                <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 shadow hover:border-indigo-500 transition cursor-pointer group">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-bold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded"><?php echo $row['category_badge']; ?></span>
                                        <!-- <button class="text-slate-500 hover:text-white"><i class="fas fa-ellipsis-h"></i></button> -->
                                        <div class="relative">
                                            <button onclick="event.stopPropagation(); toggleProjectMenu('menu-<?php echo $row['project_id']; ?>')" class="text-slate-500 hover:text-white p-1 rounded transition hover:bg-slate-700/50">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>

                                            <div id="menu-<?php echo $row['project_id']; ?>" class="project-menu hidden absolute right-0 top-6 w-32 bg-slate-900 border border-slate-700 rounded-lg shadow-xl z-20 overflow-hidden">

                                                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                    <button onclick="event.stopPropagation(); editProject(
                                                '<?php echo $row['project_id']; ?>',
                                                '<?php echo $clean_name; ?>',
                                                '<?php echo $clean_desc; ?>',

                                                '<?php echo $row['due_date']; ?>',
                                                '<?php echo htmlspecialchars($row['category_badge'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['team_members'], ENT_QUOTES); ?>',
                                                '<?php echo $clean_act; ?>',

                                                '<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>',
                                                '<?php echo $row['status']; ?>'
                                            )" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-blue-600 hover:text-white transition flex items-center gap-2">
                                                        <i class="fas fa-pen"></i> Edit
                                                    </button>

                                                    <button onclick="event.stopPropagation(); confirmDeleteProject(<?php echo $row['project_id']; ?>)" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-red-600 hover:text-white transition flex items-center gap-2 border-t border-slate-700/50">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <h4 class="text-white font-medium mb-1 group-hover:text-indigo-400 transition"><?php echo $row['project_name']; ?></h4>
                                    <p class="text-xs text-slate-400 mb-4 line-clamp-2"><?php echo $row['description']; ?></p>

                                    <div class="flex justify-between items-center">
                                        <div class="flex -space-x-2">
                                            <?php
                                            $members = explode(',', $row['team_members']);
                                            $colors = ['bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-yellow-500'];
                                            foreach ($members as $index => $member) {
                                                if ($index > 2) break;
                                                $initial = substr(trim($member), 0, 1);
                                                $bg = $colors[$index % 4];
                                                echo "<div class='w-6 h-6 rounded-full $bg flex items-center justify-center text-[10px] text-white ring-2 ring-slate-800' title='$member'>$initial</div>";
                                            }
                                            ?>
                                        </div>
                                        <span class="text-xs text-slate-500">
                                            <i class="far fa-clock mr-1"></i> <?php echo date('d M', strtotime($row['due_date'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <?php
                    $sqlCount2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE status='In Progress'");
                    $count2 = mysqli_fetch_assoc($sqlCount2)['total'];
                    ?>
                    <div class="flex-1 min-w-[300px] flex flex-col bg-slate-800/50 rounded-xl border border-slate-700/50">
                        <div class="p-4 border-b border-slate-700/50 flex justify-between items-center sticky top-0 bg-slate-800/90 backdrop-blur rounded-t-xl z-10">
                            <h3 class="font-bold text-blue-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span> In Progress
                            </h3>
                            <span class="bg-slate-700 text-slate-400 text-xs px-2 py-1 rounded-full"><?php echo $count2; ?></span>
                        </div>

                        <div class="p-4 space-y-4 overflow-y-auto custom-scroll">
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM tb_projects WHERE status='In Progress' ORDER BY due_date ASC");
                            while ($row = mysqli_fetch_assoc($query)) {
                                $pct = $row['progress_percent'];
                                $barColor = 'bg-blue-500';
                                if ($pct > 75) $barColor = 'bg-emerald-500';
                                if ($pct < 25) $barColor = 'bg-red-500';
                            ?>
                                <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 shadow hover:border-blue-500 transition cursor-pointer group">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-bold text-blue-400 bg-blue-500/10 px-2 py-0.5 rounded"><?php echo $row['category_badge']; ?></span>
                                        <!-- <button class="text-slate-500 hover:text-white"><i class="fas fa-ellipsis-h"></i></button> -->
                                        <div class="relative">
                                            <button onclick="event.stopPropagation(); toggleProjectMenu('menu-<?php echo $row['project_id']; ?>')" class="text-slate-500 hover:text-white p-1 rounded transition hover:bg-slate-700/50">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>

                                            <div id="menu-<?php echo $row['project_id']; ?>" class="project-menu hidden absolute right-0 top-6 w-32 bg-slate-900 border border-slate-700 rounded-lg shadow-xl z-20 overflow-hidden">

                                                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                    <button onclick="event.stopPropagation(); editProject(
                                                '<?php echo $row['project_id']; ?>',
                                                '<?php echo htmlspecialchars($row['project_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>',
                                                '<?php echo $row['due_date']; ?>',
                                                '<?php echo htmlspecialchars($row['category_badge'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['team_members'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['activity'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>',
                                                '<?php echo $row['status']; ?>'
                                            )" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-blue-600 hover:text-white transition flex items-center gap-2">
                                                        <i class="fas fa-pen"></i> Edit
                                                    </button>

                                                    <button onclick="event.stopPropagation(); confirmDeleteProject(<?php echo $row['project_id']; ?>)" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-red-600 hover:text-white transition flex items-center gap-2 border-t border-slate-700/50">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <h4 class="text-white font-medium mb-1 group-hover:text-blue-400 transition"><?php echo $row['project_name']; ?></h4>
                                    <p class="text-xs text-slate-400 mb-3 line-clamp-2"><?php echo $row['description']; ?></p>
                                    <div class="mb-3">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-[10px] text-slate-500">Progress</span>
                                            <span id="prog-val-<?php echo $row['project_id']; ?>" class="text-[10px] font-bold text-white">
                                                <?php echo $pct; ?>%
                                            </span>
                                        </div>

                                        <?php
                                        // Tentukan Warna Awal (PHP)
                                        $fillColor = '#3b82f6'; // Biru (Default)
                                        $accentClass = 'accent-blue-500';

                                        if ($pct < 25) {
                                            $fillColor = '#ef4444'; // Merah
                                            $accentClass = 'accent-red-500';
                                        } elseif ($pct > 75) {
                                            $fillColor = '#10b981'; // Hijau
                                            $accentClass = 'accent-emerald-500';
                                        }
                                        ?>

                                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                            <input type="range"
                                                min="0" max="100"
                                                value="<?php echo $pct; ?>"
                                                class="w-full h-1.5 rounded-lg appearance-none cursor-pointer transition-all <?php echo $accentClass; ?>"
                                                style="background: linear-gradient(to right, <?php echo $fillColor; ?> 0%, <?php echo $fillColor; ?> <?php echo $pct; ?>%, #334155 <?php echo $pct; ?>%, #334155 100%);"
                                                id="slider-<?php echo $row['project_id']; ?>"
                                                oninput="updateSliderUI(this, '<?php echo $row['project_id']; ?>')"
                                                onchange="saveProgress(this.value, '<?php echo $row['project_id']; ?>')">
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="flex -space-x-2">
                                            <?php
                                            $members = explode(',', $row['team_members']);
                                            $colors = ['bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-yellow-500'];
                                            foreach ($members as $index => $member) {
                                                if ($index > 2) break;
                                                $initial = substr(trim($member), 0, 1);
                                                $bg = $colors[$index % 4];
                                                echo "<div class='w-6 h-6 rounded-full $bg flex items-center justify-center text-[10px] text-white ring-2 ring-slate-800' title='$member'>$initial</div>";
                                            }
                                            ?>
                                        </div>
                                        <span class="text-xs text-slate-500"><i class="far fa-clock mr-1"></i> <?php echo date('d M', strtotime($row['due_date'])); ?></span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <?php
                    $sqlCount3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE status='Done'");
                    $count3 = mysqli_fetch_assoc($sqlCount3)['total'];
                    ?>
                    <div class="flex-1 min-w-[300px] flex flex-col bg-slate-800/50 rounded-xl border border-slate-700/50">
                        <div class="p-4 border-b border-slate-700/50 flex justify-between items-center sticky top-0 bg-slate-800/90 backdrop-blur rounded-t-xl z-10">
                            <h3 class="font-bold text-emerald-400 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Done
                            </h3>
                            <span class="bg-slate-700 text-slate-400 text-xs px-2 py-1 rounded-full"><?php echo $count3; ?></span>
                        </div>

                        <div class="p-4 space-y-4 overflow-y-auto custom-scroll">
                            <?php
                            $query = mysqli_query($conn, "SELECT * FROM tb_projects WHERE status='Done' ORDER BY due_date DESC LIMIT 10");
                            while ($row = mysqli_fetch_assoc($query)) {
                            ?>
                                <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 shadow opacity-75 hover:opacity-100 transition cursor-pointer group">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded"><?php echo $row['category_badge']; ?></span>
                                        <!-- <div class="text-emerald-500"><i class="fas fa-check-circle"></i></div> -->
                                        <div class="relative">
                                            <button onclick="event.stopPropagation(); toggleProjectMenu('menu-<?php echo $row['project_id']; ?>')" class="text-emerald-500 hover:text-emerald-400 p-1 rounded transition hover:bg-slate-700/50">
                                                <i class="fas fa-check-circle"></i>
                                            </button>

                                            <div id="menu-<?php echo $row['project_id']; ?>" class="project-menu hidden absolute right-0 top-6 w-32 bg-slate-900 border border-slate-700 rounded-lg shadow-xl z-20 overflow-hidden">

                                                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                    <button onclick="event.stopPropagation(); editProject(
                                                '<?php echo $row['project_id']; ?>',
                                                '<?php echo htmlspecialchars($row['project_name'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>',
                                                '<?php echo $row['due_date']; ?>',
                                                '<?php echo htmlspecialchars($row['category_badge'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['team_members'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['activity'], ENT_QUOTES); ?>',
                                                '<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>',
                                                '<?php echo $row['status']; ?>'
                                            )" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-blue-600 hover:text-white transition flex items-center gap-2">
                                                        <i class="fas fa-pen"></i> Edit
                                                    </button>

                                                    <button onclick="event.stopPropagation(); confirmDeleteProject(<?php echo $row['project_id']; ?>)" class="w-full text-left px-4 py-2 text-xs text-slate-300 hover:bg-red-600 hover:text-white transition flex items-center gap-2 border-t border-slate-700/50">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <h4 class="text-slate-300 font-medium mb-1 line-through group-hover:no-underline transition"><?php echo $row['project_name']; ?></h4>
                                    <p class="text-xs text-slate-500 mb-3 line-clamp-1"><?php echo $row['description']; ?></p>
                                    <div class="flex justify-between items-center border-t border-slate-700 pt-2">
                                        <span class="text-xs text-slate-500">Selesai: <?php echo date('d M Y', strtotime($row['due_date'])); ?></span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL CREATE PROJECT -->
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
                                <!-- <option value="PLANT DUMMY">PLANT DUMMY</option> -->
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
                                <!-- <option value="In Progress" selected>In Progress</option> -->
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
                            <?php if (isset($teamList)) {
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
                    <input type="hidden" name="redirect_to" value="project.php">
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
                                <!-- <option value="PLANT DUMMY">PLANT DUMMY</option> -->
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

    <script src="assets/js/ui-sidebar.js"></script>
    <script src="assets/js/ui-modal.js"></script>

    <script>
        // --- DEFINISI ULANG FUNGSI MODAL SECARA GLOBAL ---
        // Ini dipasang disini agar tombol onclick="openModal()" PASTI menemukannya
        // tanpa harus menunggu file eksternal termuat atau cache lama.

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                // Efek animasi kecil
                const content = modal.querySelector('div[class*="transform"]');
                // (Opsional: jika di html ada class transform)
            } else {
                console.error("Modal ID tidak ditemukan: " + modalId);
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // --- LOGIC NOTIFIKASI SUKSES (SWEETALERT) ---
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Project Created!',
                text: 'Proyek baru berhasil ditambahkan ke papan To Do.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669',
                iconColor: '#34d399'
            }).then(() => {
                window.history.replaceState(null, null, window.location.pathname);
            });
        } else if (status === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Terjadi kesalahan saat menyimpan data.',
                background: '#1e293b',
                color: '#fff'
            });
        }

        // --- FUNGSI TOGGLE NOTIFIKASI ---
        function toggleNotif() {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
            } else {
                dropdown.classList.add('hidden');
            }
        }

        window.addEventListener('click', function(e) {
            const btn = document.querySelector('button[onclick="toggleNotif()"]');
            const dropdown = document.getElementById('notifDropdown');
            if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Variabel Global untuk instance Tom Select
        let tomSelectCreate;
        let tomSelectEdit;

        document.addEventListener('DOMContentLoaded', function() {

            // 1. Aktifkan di Modal Create
            if (document.getElementById('create_team')) {
                tomSelectCreate = new TomSelect("#create_team", {
                    plugins: ['remove_button'],
                    create: false,
                    maxItems: 5
                });
            }

            // 2. Aktifkan di Modal Edit
            if (document.getElementById('edit_team')) {
                tomSelectEdit = new TomSelect("#edit_team", {
                    plugins: ['remove_button'],
                    create: false,
                    maxItems: 5
                });
            }
        });

        // --- LOGIKA MENU PROJECT (TITIK TIGA) ---
        function toggleProjectMenu(menuId) {
            // 1. Tutup semua menu lain dulu (biar gak numpuk)
            document.querySelectorAll('.project-menu').forEach(el => {
                if (el.id !== menuId) el.classList.add('hidden');
            });

            // 2. Toggle menu yang diklik
            const menu = document.getElementById(menuId);
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }

        // Tutup menu saat klik di mana saja (Outside Click)
        document.addEventListener('click', function(e) {
            // Jika yang diklik BUKAN tombol ellipsis
            if (!e.target.closest('.fa-ellipsis-h') && !e.target.closest('button[onclick*="toggleProjectMenu"]')) {
                document.querySelectorAll('.project-menu').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        // --- FUNGSI HAPUS PROJECT (PENTING) ---
        function confirmDeleteProject(id) {
            // Tutup menu dulu biar rapi
            document.querySelectorAll('.project-menu').forEach(el => el.classList.add('hidden'));

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
                    window.location.href = 'delete/delete_project.php?id=' + id + '&redirect=project.php'; // Pastikan file ini ada
                }
            })
        }

        // --- UPDATE FUNGSI EDIT PROJECT (PENTING!) ---
        // Kita harus memecah string "Budi, Andi" menjadi pilihan terseleksi
        function editProject(id, name, desc, date, cat, team, act, plant, status) {
            document.getElementById('edit_id').value = id;

            // document.getElementById('edit_name').value = name;
            // document.getElementById('edit_desc').value = desc;
            document.getElementById('edit_name').value = name.split("_ENTER_").join("\n");
            document.getElementById('edit_desc').value = desc.split("_ENTER_").join("\n");

            document.getElementById('edit_date').value = date;

            if (document.getElementById('edit_status')) document.getElementById('edit_status').value = status;
            if (document.getElementById('edit_cat')) document.getElementById('edit_cat').value = cat;

            // document.getElementById('edit_act').value = act;
            document.getElementById('edit_act').value = act.split("_ENTER_").join(" ");

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

        // --- FUNGSI UPDATE UI SLIDER (WARNA FILL & ANGKA) ---
        function updateSliderUI(slider, id) {
            const val = parseInt(slider.value);
            const label = document.getElementById('prog-val-' + id);

            // 1. Update Angka Persen
            if (label) label.innerText = val + '%';

            // 2. Tentukan Warna Berdasarkan Nilai
            let color = '#3b82f6'; // Biru (Default)
            let accentClass = 'accent-blue-500';

            if (val < 25) {
                color = '#ef4444'; // Merah
                accentClass = 'accent-red-500';
            } else if (val > 75) {
                color = '#10b981'; // Hijau
                accentClass = 'accent-emerald-500';
            }

            // 3. Update Background Gradient (Efek Batang Terisi)
            // Ini triknya: Warna A (0% sampai Val%) + Warna B (Val% sampai 100%)
            slider.style.background = `linear-gradient(to right, ${color} 0%, ${color} ${val}%, #334155 ${val}%, #334155 100%)`;

            // 4. Update Warna Tombol Bulat (Thumb)
            // Kita reset dulu class accent lama, lalu tambah yang baru
            slider.className = `w-full h-1.5 rounded-lg appearance-none cursor-pointer transition-all ${accentClass}`;
        }

        // --- FUNGSI SIMPAN KE DATABASE (AJAX) ---
        function saveProgress(val, id) {
            // Tampilkan Toast Loading kecil (Opsional, biar user tau lagi nyimpen)
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            // Kirim Data ke PHP
            const formData = new FormData();
            formData.append('project_id', id);
            formData.append('progress', val);

            fetch('process/update_progress.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Toast.fire({
                            icon: 'success',
                            title: 'Progress Disimpan'
                        });

                        // Jika 100%, tawarkan ubah status jadi Done? (Fitur tambahan keren)
                        if (val == 100) {
                            Swal.fire({
                                title: 'Project Selesai?',
                                text: "Progress sudah 100%. Mau ubah status jadi DONE sekalian?",
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Selesaikan!',
                                cancelButtonText: 'Nanti saja',
                                background: '#1e293b',
                                color: '#fff'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Panggil fungsi edit status (kalau mau canggih)
                                    // Atau biarkan user edit manual
                                }
                            });
                        }
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: 'Gagal menyimpan'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Toast.fire({
                        icon: 'error',
                        title: 'Koneksi Error'
                    });
                });
        }

        // --- INIT WARNA SLIDER SAAT LOAD ---
        document.addEventListener("DOMContentLoaded", function() {
            const sliders = document.querySelectorAll('input[type="range"]');
            sliders.forEach(s => {
                // Trigger logic warna sekali saat halaman dibuka
                const val = parseInt(s.value);
                s.classList.remove('accent-red-500', 'accent-blue-500', 'accent-emerald-500');
                if (val < 25) s.classList.add('accent-red-500');
                else if (val > 75) s.classList.add('accent-emerald-500');
                else s.classList.add('accent-blue-500');
            });
        });
    </script>

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