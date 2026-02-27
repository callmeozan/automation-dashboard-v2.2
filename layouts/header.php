<?php
// layouts/header.php

// --- 1. SETUP DEFAULT VARIABLES (Biar gak error kalau lupa diset) ---
if (!isset($pageTitle))  $pageTitle = 'Automation Portal';
if (!isset($showNotif))  $showNotif = true; // Default: TAMPILKAN Notif
if (!isset($showTheme))  $showTheme = true; // Default: TAMPILKAN Tombol Tema
if (!isset($extraMenu))  $extraMenu = '';   // Default: KOSONG (Slot untuk tombol/statistik tambahan)

// --- 2. LOGIC NOTIFIKASI TERPUSAT (Hanya jalan kalau $showNotif = true) ---
// Jadi Bapak GAK PERLU copas query ini di setiap halaman lagi. Hemat baris!
$totalNotif = 0; // Default 0
$cBD = 0; 
$cOD = 0;

if ($showNotif && isset($conn)) { // Pastikan koneksi $conn ada
    // A. Query Notif Breakdown (Mesin Rusak)
    $qBD = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE category='Breakdown' AND status='Open' ORDER BY date_log DESC LIMIT 5");
    $cBD = mysqli_num_rows($qBD);

    // B. Query Notif Project Overdue (Telat Deadline)
    $today = date('Y-m-d');
    $qOD = mysqli_query($conn, "SELECT * FROM tb_projects WHERE due_date < '$today' AND status != 'Done' ORDER BY due_date ASC LIMIT 5");
    $cOD = mysqli_num_rows($qOD);

    // C. Total
    $totalNotif = $cBD + $cOD;
}
?>

<header class="h-16 shrink-0 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-10 px-8 flex items-center justify-between">
    
    <div class="flex items-center gap-4">
        <button id="sidebarToggle" class="text-slate-400 hover:text-white mr-4 transition-transform active:scale-95">
            </button>
        <h1 class="text-lg font-medium text-white"><?php echo $pageTitle; ?></h1>
    </div>

    <div class="flex items-center gap-4">

        <?php echo $extraMenu; ?>

        <?php if ($showNotif): ?>
            <div class="relative">
                <button onclick="toggleNotif()" class="p-2 text-slate-400 hover:text-white relative transition focus:outline-none">
                    <i class="fas fa-bell"></i>
                    <?php if ($totalNotif > 0): ?>
                        <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-slate-900 animate-pulse"></span>
                    <?php endif; ?>
                </button>

                <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-72 bg-slate-800 border border-slate-700 rounded-lg shadow-xl z-50 overflow-hidden origin-top-right transform transition-all">
                    <div class="px-4 py-3 border-b border-slate-700 bg-slate-900/50 flex justify-between items-center">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Notifications</h3>
                        <span class="text-[10px] bg-slate-700 text-slate-300 px-1.5 py-0.5 rounded"><?php echo $totalNotif; ?> New</span>
                    </div>

                    <div class="max-h-80 overflow-y-auto custom-scroll">
                        <?php if ($totalNotif == 0): ?>
                            <div class="px-4 py-6 text-center text-slate-500">
                                <i class="fas fa-check-circle text-2xl mb-2 text-cyan-500/50"></i>
                                <p class="text-xs">Semua sistem aman.</p>
                            </div>
                        <?php else: ?>
                            
                            <?php if ($cBD > 0): ?>
                                <div class="px-4 py-2 bg-red-500/10 text-red-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-700">
                                    Mesin Breakdown (<?php echo $cBD; ?>)
                                </div>
                                <?php while ($r = mysqli_fetch_assoc($qBD)): ?>
                                    <a href="laporan.php" class="block px-4 py-3 hover:bg-slate-700 transition border-b border-slate-700/30 group">
                                        <div class="flex gap-3">
                                            <div class="bg-red-500/20 p-1.5 rounded text-red-400 mt-0.5"><i class="fas fa-car-crash text-xs"></i></div>
                                            <div>
                                                <p class="text-xs font-bold text-white"><?php echo $r['machine_name']; ?></p>
                                                <p class="text-[10px] text-slate-400 truncate w-40"><?php echo $r['problem']; ?></p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            <?php endif; ?>

                            <?php if ($cOD > 0): ?>
                                <div class="px-4 py-2 bg-orange-500/10 text-orange-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-700">
                                    Project Overdue (<?php echo $cOD; ?>)
                                </div>
                                <?php while ($r = mysqli_fetch_assoc($qOD)): ?>
                                    <a href="project.php" class="block px-4 py-3 hover:bg-slate-700 transition border-b border-slate-700/30 group">
                                        <div class="flex gap-3">
                                            <div class="bg-orange-500/20 p-1.5 rounded text-orange-400 mt-0.5"><i class="far fa-clock text-xs"></i></div>
                                            <div>
                                                <p class="text-xs font-bold text-white"><?php echo $r['project_name']; ?></p>
                                                <?php 
                                                    $days = floor((time() - strtotime($r['due_date'])) / (60 * 60 * 24)); 
                                                ?>
                                                <p class="text-[10px] text-orange-400 font-bold">Telat <?php echo $days; ?> hari</p>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($showTheme): ?>
            <button onclick="toggleTheme()" class="p-2 text-slate-400 hover:text-white transition focus:outline-none mr-2" title="Ganti Tema">
                <i id="themeIcon" class="fas fa-sun"></i>
            </button>
        <?php endif; ?>

    </div>
</header>