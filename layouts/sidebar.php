<?php
// layouts/sidebar.php

// 1. Deteksi nama file halaman yang sedang dibuka
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col transition-all duration-300 hidden md:flex">
    <div class="h-16 flex items-center justify-center border-b border-slate-800">
        <!-- <h1 class="text-xl font-bold text-white tracking-wide">JIS <span class="text-cyan-400">PORTAL.</span></h1> -->
         <h1 class="text-xl font-bold text-white tracking-wide">JIS <span class="text-cyan-400">PORTAL.</span></h1>
    </div>

    <nav class="flex-1 px-4 py-6 space-y-2">
        
        <a href="dashboard.php" class="nav-item hover:text-cyan-400 <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt w-6"></i>
            <span class="font-medium hover:text-white">Dashboard</span>
        </a>

        <div class="relative">
            <?php 
                // Cek apakah sedang di salah satu menu database
                $is_db_active = ($current_page == 'database.php' || $current_page == 'master_items.php');
            ?>
            <button onclick="toggleDbMenu()" class="nav-item hover:text-cyan-400 w-full flex justify-between items-center focus:outline-none group <?php echo $is_db_active ? 'text-white bg-slate-800/50' : ''; ?>">
                <div class="flex items-center gap-3">
                    <i class="fas fa-database w-6 group-hover:text-cyan-400 transition <?php echo $is_db_active ? 'text-cyan-400' : ''; ?>"></i>
                    <span class="group-hover:text-white transition">Database</span>
                </div>
                <i id="arrowDb" class="fas fa-chevron-down text-xs text-slate-500 transition-transform duration-200 <?php echo $is_db_active ? 'rotate-180' : ''; ?>"></i>
            </button>

            <div id="dbSubmenu" class="<?php echo $is_db_active ? '' : 'hidden'; ?> pl-10 space-y-1 mt-1 bg-slate-900/50 py-2 border-l border-slate-800 ml-3">
                <a href="database.php" class="block text-sm py-1 transition <?php echo ($current_page == 'database.php') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-cyan-400'; ?>">
                    • Machine / Assets
                </a>
                <a href="master_items.php" class="block text-sm py-1 transition <?php echo ($current_page == 'master_items.php') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-cyan-400'; ?>">
                    • Master Items
                </a>
            </div>
        </div>

        <a href="laporan.php" class="nav-item hover:text-cyan-400 <?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list w-6"></i>
            <span class="hover:text-white">Daily Report</span>
        </a>

        <a href="project.php" class="nav-item hover:text-cyan-400 <?php echo ($current_page == 'project.php') ? 'active' : ''; ?>">
            <i class="fas fa-project-diagram w-6"></i>
            <span class="hover:text-white">Projects</span>
        </a>

        <a href="overtime.php" class="nav-item hover:text-cyan-400 <?php echo ($current_page == 'overtime.php') ? 'active' : ''; ?>">
            <i class="fas fa-clock w-6"></i>
            <span class="hover:text-white">Overtime</span>
        </a>

        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
            <a href="dashboard.php?open_modal=adduser" class="nav-item hover:text-cyan-400 transition">
                <i class="fa-solid fa-user-plus w-6"></i>
                <span class="hover:text-white">Add User</span>
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'admin'): ?>
        <div class="px-4 py-2 text-xs font-bold text-slate-500 uppercase tracking-wider mt-4">Admin Menu</div>
            <div class="relative">
                <?php 
                    // Ubah nama variabel PHP-nya biar gak bentrok
                    $is_monitoring_active = ($current_page == 'temperature.php' || $current_page == 'vibration.php');
                ?>
                <button onclick="toggleMonitoringMenu()" class="nav-item hover:text-cyan-400 w-full flex justify-between items-center focus:outline-none group <?php echo $is_monitoring_active ? 'text-white bg-slate-800/50' : ''; ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-chart-pie w-6 group-hover:text-cyan-400 transition <?php echo $is_monitoring_active ? 'text-cyan-400' : ''; ?>"></i>
                        <span class="group-hover:text-white transition">Monitoring</span>
                    </div>
                    <i id="arrowMonitoring" class="fas fa-chevron-down text-xs text-slate-500 transition-transform duration-200 <?php echo $is_monitoring_active ? 'rotate-180' : ''; ?>"></i>
                </button>

                <div id="monitoringSubmenu" class="<?php echo $is_monitoring_active ? '' : 'hidden'; ?> pl-10 space-y-1 mt-1 bg-slate-900/50 py-2 border-l border-slate-800 ml-3">
                    <a href="temperature.php" class="block text-sm py-1 transition <?php echo ($current_page == 'temperature.php') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-cyan-400'; ?>">
                        • Motor Temperature
                    </a>
                    <a href="vibration.php" class="block text-sm py-1 transition <?php echo ($current_page == 'vibration.php') ? 'text-cyan-400 font-bold' : 'text-slate-400 hover:text-cyan-400'; ?>">
                        • Motor Vibration
                    </a>
                </div>
            </div>

            <a href="manage_users.php" class="nav-item hover:text-cyan-400 <?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users-cog w-6"></i> 
                <span class="font-medium hover:text-white">User Management</span>
            </a>
        <?php endif; ?>

        <a href="logout.php" class="nav-item text-slate-400 hover:text-red-400 transition">
            <i class="fas fa-solid fa-right-from-bracket w-6"></i>
            <span>Logout</span>
        </a>
    </nav>

    <div class="p-4 border-t border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-slate-700 border border-slate-500 overflow-hidden flex items-center justify-center">
                <img src="image/default_profile.png" alt="User Profile" class="w-full h-full object-cover scale-125 transition-transform hover:scale-150">
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-white truncate">
                    <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest'; ?>
                </p>
                <p class="text-xs text-cyan-500 flex items-center gap-1">
                    <span class="w-2 h-2 bg-cyan-500 rounded-full animate-pulse"></span> Online
                </p>
            </div>
        </div>
    </div>
</aside>