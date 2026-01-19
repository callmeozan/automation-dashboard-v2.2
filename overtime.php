<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include 'config.php';

// --- LOGIKA STATISTIK MINI (CUTOFF 15-15) ---
$my_id = $_SESSION['user_id'];
$tgl_hari_ini = date('d');
$bln_hari_ini = date('m');
$thn_hari_ini = date('Y');

// 1. Tentukan Range Tanggal Periode Ini (Cutoff tgl 15)
// Jika hari ini tanggal > 15 (misal tgl 16 Jan), berarti masuk periode: 16 Jan - 15 Feb
// Jika hari ini tanggal <= 15 (misal tgl 10 Feb), berarti masuk periode: 16 Jan - 15 Feb
if ($tgl_hari_ini > 15) {
    $periode_start = date('Y-m-16'); // 16 Bulan Ini
    $periode_end   = date('Y-m-15', strtotime('+1 month')); // 15 Bulan Depan
} else {
    $periode_start = date('Y-m-16', strtotime('-1 month')); // 16 Bulan Lalu
    $periode_end   = date('Y-m-15'); // 15 Bulan Ini
}

// Format Tampilan Periode (Contoh: "16 Jan - 15 Feb")
$label_periode = date('d M', strtotime($periode_start)) . " - " . date('d M', strtotime($periode_end));

// 2. Hitung Total Jam (Approved) DALAM PERIODE CUTOFF INI
$qJam = mysqli_query($conn, "SELECT SUM(duration) as total_jam 
                             FROM tb_overtime 
                             WHERE user_id='$my_id' 
                             AND status='Approved' 
                             AND date_ot BETWEEN '$periode_start' AND '$periode_end'");
$dJam = mysqli_fetch_assoc($qJam);
$total_jam_saya = $dJam['total_jam'] ? floatval($dJam['total_jam']) : 0;

// 3. Logika Kuota & Progress Bar (Max 15 Jam)
$max_quota = 15;
$persen_pakai = ($total_jam_saya / $max_quota) * 100;
if ($persen_pakai > 100) $persen_pakai = 100; // Mentok di 100% biar bar ngga bablas

// Tentukan Warna Bar berdasarkan tingkat bahaya
$bar_color = "bg-emerald-500"; // Aman
if ($total_jam_saya >= 10) $bar_color = "bg-yellow-500"; // Warning (Hampir habis)
if ($total_jam_saya >= 15) $bar_color = "bg-red-500";    // Bahaya (Habis/Over)

// 4. Hitung Frekuensi & Pending (Sama kayak sebelumnya)
$qFreq = mysqli_query($conn, "SELECT COUNT(*) as total_freq 
                              FROM tb_overtime 
                              WHERE user_id='$my_id' AND status='Approved' 
                              AND date_ot BETWEEN '$periode_start' AND '$periode_end'");
$dFreq = mysqli_fetch_assoc($qFreq);
$total_freq_saya = $dFreq['total_freq'];

$qPend = mysqli_query($conn, "SELECT COUNT(*) as total_pending FROM tb_overtime WHERE user_id='$my_id' AND status='Pending'");
$dPend = mysqli_fetch_assoc($qPend);
$total_pending_saya = $dPend['total_pending'];

// Ambil Data User Login
$id_login = $_SESSION['user_id'];
$my_role  = $_SESSION['role']; // admin, section, atau user biasa

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
    <title>Overtime Request - Automation Portal</title>

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
        /* Custom Scrollbar */
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #1e293b; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
    </style>
</head>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">

        <aside class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col hidden md:flex">
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

                <a href="project.php" class="nav-item">
                    <i class="fas fa-project-diagram w-6"></i>
                    <span>Projects</span>
                </a>

                <a href="#" class="nav-item active">
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

        <main class="flex-1 flex flex-col overflow-y-auto relative">

        <!-- HEADER ADA DISINI -->
            <header class="h-16 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-10 px-8 flex items-center justify-between">

                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="text-slate-400 hover:text-white mr-4 transition-transform active:scale-95">
                    </button>
                    <h2 class="text-lg font-medium text-white">Overtime Request</h2>
                </div>

                <div class="flex items-center gap-4">
                    
                    <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4 mr-2">
                        <!-- Total Report: <span class="text-white font-bold">
                            <?php
                            $countQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_daily_reports");
                                if ($countQuery) {
                                    $countData = mysqli_fetch_assoc($countQuery);
                                    echo $countData['total'];
                                } else {
                                     echo "Query gagal";
                                }
                            ?>
                        </span> -->
                    </div>

                    <!-- <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4 mr-2">
                        Total Report: <span class="text-white font-bold">
                            <?php
                            $countQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tb_daily_reports");
                                if ($countQuery) {
                                    $countData = mysqli_fetch_assoc($countQuery);
                                    echo $countData['total'];
                                } else {
                                     echo "Query gagal";
                                }
                            ?>
                        </span>
                    </div> -->
                    
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

            <div class="p-8 fade-in">
                <div class="flex flex-col md:flex-row justify-between items-end mb-6 gap-4">
                    <div>
                        <h3 class="text-2xl font-bold text-white">Overtime Log</h3>
                        <p class="text-sm text-slate-400 mt-1">Recapitulation of overtime hours based on SPK.</p>
                    </div>

                    <div class="flex gap-2">
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 group-focus-within:text-emerald-500 transition">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search Name, SPK, Activity..." 
                                    class="pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-700 text-white rounded-lg text-sm focus:border-emerald-500 outline-none w-full md:w-64 transition shadow-sm">
                            </div>

                        <button onclick="downloadExcelOvertime()" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-lg border border-slate-700 text-sm transition flex items-center gap-2">
                            <i class="fas fa-file-excel text-green-500"></i> Export Excel
                        </button>

                        <button onclick="document.getElementById('modalOvertime').classList.remove('hidden')" 
                                class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-emerald-600/20 flex items-center gap-2">
                            <i class="fas fa-plus"></i> Request Overtime
                        </button>
                    </div>
                </div>

                <?php if ($my_role == 'admin' || $my_role == 'section'): ?>
                    <div class="bg-indigo-900/20 border border-indigo-500/30 rounded-xl p-6 mb-8">
                        <h3 class="text-xl font-bold text-indigo-300 mb-4 flex items-center gap-2">
                            <i class="fas fa-user-check"></i> Waiting for Approval
                        </h3>
                        
                        <div class="overflow-x-auto bg-slate-900 rounded-lg border border-slate-700">
                            <table class="w-full text-left text-sm text-slate-400">
                                <thead class="bg-indigo-900/50 text-indigo-200 uppercase text-xs font-bold">
                                    <tr>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Duration</th>
                                        <th class="px-4 py-3">Activity</th>
                                        <th class="px-4 py-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700">
                                    <?php
                                    // JOIN ke tb_users untuk ambil nama karyawan
                                    $qApprove = mysqli_query($conn, "SELECT a.*, b.full_name 
                                                                     FROM tb_overtime a 
                                                                     JOIN tb_users b ON a.user_id = b.user_id 
                                                                     WHERE a.status='Pending' 
                                                                     ORDER BY a.date_ot DESC");

                                    if (mysqli_num_rows($qApprove) > 0) {
                                        while ($rowA = mysqli_fetch_assoc($qApprove)) {
                                    ?>
                                    <tr class="hover:bg-slate-800 transition">
                                        <td class="px-4 py-3 font-bold text-white"><?php echo $rowA['full_name']; ?></td>
                                        <td class="px-4 py-3"><?php echo date('d M', strtotime($rowA['date_ot'])); ?></td>
                                        <td class="px-4 py-3">
                                            <span class="text-xs bg-slate-800 px-2 py-1 rounded text-white">
                                                <?php echo $rowA['duration']; ?> Jam
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs"><?php echo $rowA['activity']; ?></td>
                                        <td class="px-4 py-3 flex gap-2 justify-center">
                                            
                                            <!-- <a href="process/process_update_ot.php?id=<?php echo $rowA['ot_id']; ?>&status=Approved" 
                                               class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded text-xs font-bold transition flex items-center gap-1"
                                               onclick="return confirm('Setujui lembur ini?')">
                                                <i class="fas fa-check"></i> ACC
                                            </a>

                                            <a href="process/process_update_ot.php?id=<?php echo $rowA['ot_id']; ?>&status=Rejected" 
                                               class="bg-red-600 hover:bg-red-500 text-white px-3 py-1.5 rounded text-xs font-bold transition flex items-center gap-1"
                                               onclick="return confirm('Tolak lembur ini?')">
                                                <i class="fas fa-times"></i> Tolak
                                            </a> -->

                                        <div class="flex items-center gap-2 justify-center">
    
                                            <button onclick="updateStatus('<?php echo $rowA['ot_id']; ?>', 'Approved')" 
                                            class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded text-xs font-bold transition flex items-center gap-1"
                                            title="Setujui">
                                                <i class="fas fa-check"></i> ACC
                                            </button>

                                            <button onclick="updateStatus('<?php echo $rowA['ot_id']; ?>', 'Rejected')" 
                                            class="bg-red-600 hover:bg-red-500 text-white px-3 py-1.5 rounded text-xs font-bold transition flex items-center gap-1"
                                            title="Tolak">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>

                                        </div>

                                        </td>
                                    </tr>
                                    <?php 
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="px-4 py-4 text-center text-slate-500 italic">There are no new overtime requests.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 fade-in">
            <div class="bg-slate-800 rounded-xl p-5 border border-slate-700 shadow-lg flex items-center justify-between relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-emerald-500 to-transparent"></div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Total Hours (This Month)</p>
                    <p class="text-[10px] text-slate-500">Period: <span class="text-indigo-400 font-bold"><?php echo $label_periode; ?></span></p>
                    
                    <h3 class="text-3xl font-bold text-white mt-1">
                        <?php echo $total_jam_saya; ?> <span class="text-sm font-normal text-slate-500">Hours</span>
                    </h3>
                    <p class="text-[10px] text-emerald-400 mt-2 flex items-center gap-1">
                        <i class="fas fa-chart-line"></i> Accumulation Verified
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-xl group-hover:scale-110 transition">
                    <i class="fas fa-clock"></i>
                </div>
            </div>

            <div class="bg-slate-800 rounded-xl p-5 border border-slate-700 shadow-lg flex items-center justify-between relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-blue-500 to-transparent"></div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Frequency of Overtime</p>
                    <h3 class="text-3xl font-bold text-white mt-1">
                        <?php echo $total_freq_saya; ?> <span class="text-sm font-normal text-slate-500">Times</span>
                    </h3>
                    <p class="text-[10px] text-blue-400 mt-2 flex items-center gap-1">
                        <i class="fas fa-calendar-check"></i> Within active period
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-500 text-xl group-hover:scale-110 transition">
                    <i class="fas fa-user-clock"></i>
                </div>
            </div>

            <div class="bg-slate-800 rounded-xl p-5 border border-slate-700 shadow-lg flex items-center justify-between relative overflow-hidden group">
                <div class="absolute right-0 top-0 h-full w-1 bg-gradient-to-b from-yellow-500 to-transparent"></div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Waiting Approval</p>
                    <h3 class="text-3xl font-bold text-white mt-1">
                        <?php echo $total_pending_saya; ?> <span class="text-sm font-normal text-slate-500">Request</span>
                    </h3>
                    <p class="text-[10px] text-yellow-400 mt-2 flex items-center gap-1">
                        <i class="fas fa-hourglass-half"></i> Waiting Approval
                    </p>
                </div>
                <div class="w-12 h-12 rounded-full bg-yellow-500/10 flex items-center justify-center text-yellow-500 text-xl group-hover:scale-110 transition">
                    <i class="fas fa-file-signature"></i>
                </div>
            </div>
        </div>

                <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-400">
                            <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                <tr>
                                    <th class="px-6 py-4">Name</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">SPK Number</th>
                                    <th class="px-6 py-4">Activity / Job Desc</th>
                                    <th class="px-6 py-4">Time</th>
                                    <th class="px-6 py-4">Total</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50" id="tableOvertimeBody">
                                <?php
                                // QUERY UTAMA (Tanpa LIMIT, biarkan ambil semua)
                                $qHist = mysqli_query($conn, "SELECT a.*, b.full_name 
                                                            FROM tb_overtime a 
                                                            JOIN tb_users b ON a.user_id = b.user_id 
                                                            ORDER BY a.date_ot DESC");

                                // MULAI LOOPING DATA (INI BAGIAN YANG TADI HILANG)
                                if (mysqli_num_rows($qHist) > 0) {
                                    while ($row = mysqli_fetch_assoc($qHist)) {
                                        
                                        // Setup Variabel Tampilan (Warna Status & Row Milik Sendiri)
                                        $statusClass = "bg-yellow-500/10 text-yellow-400 border-yellow-500/20";
                                        if($row['status'] == 'Approved') $statusClass = "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
                                        if($row['status'] == 'Rejected') $statusClass = "bg-red-500/10 text-red-400 border-red-500/20";

                                        $isMe = ($row['user_id'] == $_SESSION['user_id']);
                                        $rowClass = $isMe ? "bg-slate-800/50" : "";
                                ?>
                                        <tr class="hover:bg-slate-700/20 transition <?php echo $rowClass; ?>">
                                            
                                            <td class="px-6 py-4 font-bold text-white whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <span><?php echo $row['full_name']; ?></span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-slate-400 whitespace-nowrap">
                                                <?php echo date('d M Y', strtotime($row['date_ot'])); ?>
                                            </td>

                                            <td class="px-6 py-4 font-mono text-xs text-indigo-400">
                                                <?php echo $row['spk_number'] ? $row['spk_number'] : '-'; ?>
                                            </td>

                                            <td class="px-6 py-4 text-slate-400">
                                                <?php echo $row['activity']; ?>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-slate-400">
                                                <div class="flex items-center gap-2">
                                                    <span class="bg-slate-900 px-2 py-1 rounded text-xs"><?php echo date('H:i', strtotime($row['time_start'])); ?></span>
                                                    <span class="text-slate-600">-</span>
                                                    <span class="bg-slate-900 px-2 py-1 rounded text-xs"><?php echo date('H:i', strtotime($row['time_end'])); ?></span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span class="font-bold text-white"><?php echo $row['duration']; ?></span> Jam
                                            </td>

                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-1 rounded border text-[10px] font-bold uppercase tracking-wider <?php echo $statusClass; ?>">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 text-center">
                                                <?php 
                                                // Logika Permission: Admin Bebas, User Cuma Pending & Punya Sendiri
                                                $isPending = ($row['status'] == 'Pending');
                                                $isAdmin   = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section');
                                                
                                                $allowAccess = ($isMe && $isPending) || $isAdmin;

                                                if ($allowAccess) { 
                                                ?>
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button onclick="openEditModal('<?php echo $row['ot_id']; ?>','<?php echo $row['date_ot']; ?>','<?php echo $row['time_start']; ?>','<?php echo $row['time_end']; ?>','<?php echo $row['spk_number']; ?>','<?php echo htmlspecialchars($row['activity'], ENT_QUOTES); ?>')" class="text-blue-400 hover:text-blue-300 transition" title="Edit Data">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button onclick="confirmDeleteOt('<?php echo $row['ot_id']; ?>')" class="text-red-400 hover:text-red-300 transition" title="Hapus Request">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="flex items-center justify-center gap-1 text-xs italic text-slate-600">
                                                        <?php if (!$isPending): ?>
                                                            <i class="fas fa-check-double text-emerald-800"></i> <span>Final</span>
                                                        <?php else: ?>
                                                            <i class="fas fa-user-lock"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                <?php 
                                    } // Tutup While
                                } else { // Jika Data Kosong
                                ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-slate-500 italic">Belum ada history lembur siapapun.</td>
                                    </tr>
                                <?php } // Tutup Else ?>
                            </tbody>
                        </table>

                        <div class="flex justify-between items-center mt-4 mb-8 px-4" id="paginationContainer">
                            <div class="text-xs text-slate-500" id="pageInfo">Loading data...</div>
                            <div id="paginationControls" class="flex gap-1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL ADD OVERTIME -->
    <div id="modalOvertime" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('modalOvertime').classList.add('hidden')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-xl shadow-2xl p-6 relative">

                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-edit text-emerald-400"></i> Input Request Overtime
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Please fill in the work activity log in detail.</p>
                    </div>
                    <button onclick="document.getElementById('modalOvertime').classList.add('hidden')" class="close-modal text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_add_overtime.php" method="POST" class="space-y-4">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Tanggal</label>
                            <input type="date" name="date_ot" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Nomor SPK (Optional)</label>
                            <input type="text" name="spk_number" placeholder="Contoh: SPK-001" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Jam Mulai</label>
                            <input type="time" name="time_start" id="t_start" onchange="calculateDuration()" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Jam Selesai</label>
                            <input type="time" name="time_end" id="t_end" onchange="calculateDuration()" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                    </div>

                    <div class="bg-slate-800 p-3 rounded border border-slate-700 flex justify-between items-center">
                        <span class="text-xs text-slate-400">Estimasi Durasi:</span>
                        <span id="duration_preview" class="text-emerald-400 font-bold text-lg">0.0 Jam</span>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Aktivitas / Pekerjaan</label>
                        <textarea name="activity" rows="3" required placeholder="Jelaskan pekerjaan yang dilakukan..." class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="document.getElementById('modalOvertime').classList.add('hidden')" class="flex-1 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-500 text-sm font-bold shadow-lg shadow-emerald-600/20">Simpan Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT OVERTIME -->
    <div id="modalEditOvertime" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-xl shadow-2xl p-6 relative">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-edit text-emerald-400"></i> Edit Request Overtime
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Please fill in the work activity log in detail.</p>
                    </div>
                    <button onclick="document.getElementById('modalEditOvertime').classList.add('hidden')" class="close-modal text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_edit_ot.php" method="POST" class="space-y-4">
                    <input type="hidden" name="ot_id" id="edit_ot_id">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Tanggal</label>
                            <input type="date" name="date_ot" id="edit_date" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Nomor SPK</label>
                            <input type="text" name="spk_number" id="edit_spk" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Jam Mulai</label>
                            <input type="time" name="time_start" id="edit_start" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Jam Selesai</label>
                            <input type="time" name="time_end" id="edit_end" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Aktivitas</label>
                        <textarea name="activity" id="edit_activity" rows="3" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="closeEditModal()" class="flex-1 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 text-sm font-bold shadow-lg">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    // ============================================================
    //  1. FUNGSI HITUNG DURASI & MODAL
    // ============================================================

    // Script Hitung Durasi Otomatis
    function calculateDuration() {
        const startVal = document.getElementById('t_start').value;
        const endVal = document.getElementById('t_end').value;
        const display = document.getElementById('duration_preview');

        if (startVal && endVal) {
            let start = new Date("2000-01-01 " + startVal);
            let end = new Date("2000-01-01 " + endVal);

            // Jika jam selesai lebih kecil (lembur lewat tengah malam), tambah 1 hari
            if (end < start) {
                end.setDate(end.getDate() + 1);
            }

            let diffMs = end - start;
            let diffHrs = diffMs / (1000 * 60 * 60); // Konversi ms ke jam

            display.innerText = diffHrs.toFixed(1) + " Jam";
        } else {
            display.innerText = "0.0 Jam";
        }
    }

    // Fungsi Buka Modal Edit
    function openEditModal(id, date, start, end, spk, activity) {
        document.getElementById('edit_ot_id').value = id;
        document.getElementById('edit_date').value = date;
        document.getElementById('edit_start').value = start;
        document.getElementById('edit_end').value = end;
        document.getElementById('edit_spk').value = spk;
        document.getElementById('edit_activity').value = activity;

        document.getElementById('modalEditOvertime').classList.remove('hidden');
    }

    // Fungsi Tutup Modal Edit
    function closeEditModal() {
        document.getElementById('modalEditOvertime').classList.add('hidden');
    }

    // ============================================================
    //  2. LOGIKA NOTIFIKASI SWEETALERT (SWAL)
    // ============================================================
    
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const msg = urlParams.get('msg');

    // Config dasar Swal agar tidak mengetik ulang warna berulang kali
    const swalConfig = { background: '#1e293b', color: '#fff' };

    if (status === 'success') {
        Swal.fire({ ...swalConfig, icon: 'success', title: 'Berhasil!', text: 'Request lembur berhasil dikirim.', confirmButtonColor: '#059669' });
    } 
    else if (status === 'success_update') {
        Swal.fire({ ...swalConfig, icon: 'success', title: 'Data Diperbarui!', text: 'Perubahan data lembur berhasil disimpan.', confirmButtonColor: '#3b82f6' });
    } 
    else if (status === 'deleted') {
        Swal.fire({ ...swalConfig, icon: 'success', title: 'Dihapus!', text: 'Data lembur berhasil dibatalkan.', confirmButtonColor: '#ef4444' });
    } 
    else if (status === 'updated') {
        Swal.fire({ ...swalConfig, icon: 'success', title: 'Status Diperbarui!', text: 'Status approval berhasil diubah.', confirmButtonColor: '#059669' });
    } 
    else if (status === 'error') {
        Swal.fire({ ...swalConfig, icon: 'error', title: 'Gagal', text: msg || 'Terjadi kesalahan sistem.', confirmButtonColor: '#ef4444' });
    }

    // Bersihkan URL (Hapus ?status=...)
    if (status) {
        window.history.replaceState(null, null, window.location.pathname);
    }

    // ============================================================
    //  3. FUNGSI KONFIRMASI DELETE
    // ============================================================
    
    function confirmDeleteOt(id) {
        Swal.fire({
            title: 'Batalkan Lembur?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            background: '#1e293b',
            color: '#fff',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'process/process_delete_ot.php?id=' + id;
            }
        });
    }

    // ============================================================
    //  4. FUNGSI UI LAINNYA (DROPDOWN NOTIF)
    // ============================================================

    function toggleNotif() {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    // Tutup dropdown kalau klik di luar area (Dengan Safety Check)
    window.addEventListener('click', function(e) {
        const btn = document.querySelector('button[onclick="toggleNotif()"]');
        const dropdown = document.getElementById('notifDropdown');

        // Pastikan elemennya ADA dulu, baru dicek contains-nya
        if (btn && dropdown) {
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        }
    });

    // ============================================================
    //  5. FUNGSI LIVE SEARCH (CARI CEPAT)
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
    // --- LOGIC PAGINATION & SEARCH (GAYA LAPORAN.PHP) ---
    const tableBody = document.getElementById('tableOvertimeBody');
    const searchInput = document.getElementById('searchInput'); // Pastikan ID input search Bapak 'searchInput'
    const pageInfo = document.getElementById('pageInfo');
    const paginationControls = document.getElementById('paginationControls');

    if (tableBody) {
        // Konfigurasi: Tampilkan 50 data per halaman
        const rowsPerPage = 30; 
        
        // Ambil semua baris data dari tabel
        let allRows = Array.from(tableBody.querySelectorAll('tr'));
        let currentPage = 1;

        function renderTable() {
            // 1. Ambil kata kunci pencarian
            const searchText = searchInput ? searchInput.value.toLowerCase() : '';

            // 2. Filter Baris (Cari text di dalam baris)
            const filteredRows = allRows.filter(row => {
                const textMatch = row.textContent.toLowerCase().includes(searchText);
                return textMatch;
            });

            // 3. Hitung Pagination
            const totalItems = filteredRows.length;
            const totalPages = Math.ceil(totalItems / rowsPerPage);

            // Reset halaman jika melampaui batas
            if (currentPage > totalPages) currentPage = 1;
            if (currentPage < 1 && totalPages > 0) currentPage = 1;

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            // 4. Render (Sembunyikan semua, lalu munculkan yang sesuai halaman)
            allRows.forEach(row => row.style.display = 'none'); // Sembunyikan TOTAL semua
            
            // Munculkan yang lolos filter & sesuai halaman
            filteredRows.slice(start, end).forEach(row => {
                row.style.display = ''; 
            });

            // 5. Update Info Text
            if (pageInfo) {
                if (totalItems === 0) {
                    pageInfo.innerText = "Tidak ada data yang cocok.";
                } else {
                    pageInfo.innerText = `Menampilkan ${start + 1} - ${Math.min(end, totalItems)} dari ${totalItems} data`;
                }
            }

            // 6. Render Tombol Angka
            renderButtons(totalPages);
        }

        function renderButtons(totalPages) {
            if (!paginationControls) return;
            paginationControls.innerHTML = ""; // Bersihkan tombol lama
            
            if (totalPages <= 1) return; // Kalau cuma 1 halaman, gak usah muncul tombol

            const createBtn = (text, page, isActive = false, isDisabled = false) => {
                const btn = document.createElement('button');
                btn.innerHTML = text; // Pakai innerHTML biar bisa icon
                // Style tombol persis laporan.php
                btn.className = `px-3 py-1 rounded transition text-xs ${isActive ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'}`;
                
                if (isDisabled) {
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                    btn.disabled = true;
                } else {
                    btn.addEventListener('click', () => {
                        currentPage = page;
                        renderTable(); // Render ulang saat klik
                    });
                }
                return btn;
            };

            // Tombol Prev
            paginationControls.appendChild(createBtn('<i class="fas fa-chevron-left"></i>', currentPage - 1, false, currentPage === 1));

            // Logic Tombol Angka (Maksimal 5 tombol biar rapi)
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            for (let i = startPage; i <= endPage; i++) {
                paginationControls.appendChild(createBtn(i, i, i === currentPage));
            }

            // Tombol Next
            paginationControls.appendChild(createBtn('<i class="fas fa-chevron-right"></i>', currentPage + 1, false, currentPage === totalPages));
        }

        // Event Listener untuk Search (Live Typing)
        if (searchInput) {
            // Hapus onkeyup lama di HTML biar gak bentrok
            searchInput.removeAttribute('onkeyup'); 
            searchInput.addEventListener('keyup', () => {
                currentPage = 1; // Reset ke halaman 1 kalau user ngetik
                renderTable();
            });
        }

        // Jalankan Pertama Kali
        renderTable();
    }
});

    // --- FUNGSI UPDATE STATUS (ACC / TOLAK) ---
    function updateStatus(id, status) {
        // Tentukan Warna & Kata-kata berdasarkan status
        let titleText = status === 'Approved' ? 'Setujui Lembur?' : 'Tolak Lembur?';
        let confirmText = status === 'Approved' ? 'Ya, Setujui' : 'Ya, Tolak';
        let btnColor = status === 'Approved' ? '#059669' : '#ef4444'; // Hijau atau Merah
        let iconType = status === 'Approved' ? 'question' : 'warning';

        Swal.fire({
            title: titleText,
            text: "Status akan diperbarui di sistem.",
            icon: iconType,
            background: '#1e293b',
            color: '#fff',
            showCancelButton: true,
            confirmButtonColor: btnColor,
            cancelButtonColor: '#64748b',
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Arahkan ke file proses update
                window.location.href = 'process/process_update_ot.php?id=' + id + '&status=' + status;
            }
        });
    }

    // FUNGSI EXPORT DENGAN FILTER (MIRIP LAPORAN.PHP)
    function downloadExcelOvertime() {
        // 1. Ambil nilai dari kotak pencarian
        const searchInput = document.getElementById('searchInput');
        const searchValue = searchInput ? searchInput.value : '';

        // 2. Buat URL ke file export
        let url = 'export/export_overtime_excel.php?export=true';
        
        // 3. Jika ada ketikan pencarian, tempelkan ke URL
        if (searchValue) {
            url += '&search=' + encodeURIComponent(searchValue);
        }

        // 4. Buka tab baru untuk download
        window.open(url, '_blank');
    }
</script>

<script src="assets/js/ui-sidebar.js"></script>
<script src="assets/js/ui-modal.js"></script>
</body>
</html>