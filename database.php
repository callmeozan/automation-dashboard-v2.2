<?php
session_start();
// --- CEK LOGIN (SATPAM) ---
// Jika session 'user_id' tidak ada, artinya dia belum login.
if (!isset($_SESSION['user_id'])) {
    // Tendang balik ke halaman login
    header("Location: index.php");
    exit(); // Stop script di sini, jangan lanjut ke bawah!
}
include 'config.php'; // Panggil koneksi database

// Cek Login (Opsional, biar aman)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    // header("Location: index.php"); // Aktifkan nanti kalau mau proteksi halaman
}

// --- COPY LOGIKA NOTIFIKASI DARI DASHBOARD KE SINI ---
// A. Hitung Breakdown
$queryNotif1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_daily_reports WHERE category='Breakdown' AND status='Open'");
$countBreakdown = mysqli_fetch_assoc($queryNotif1)['total'];
$queryBreakdownList = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE category='Breakdown' AND status='Open' ORDER BY date_log DESC LIMIT 5");

// B. Hitung Overdue
$today = date('Y-m-d');
$queryNotif2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE due_date < '$today' AND status != 'Done'");
$countOverdue = mysqli_fetch_assoc($queryNotif2)['total'];
$queryOverdueList = mysqli_query($conn, "SELECT * FROM tb_projects WHERE due_date < '$today' AND status != 'Done' ORDER BY due_date ASC LIMIT 5");

// Total
$totalNotif = $countBreakdown + $countOverdue;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#03142c">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Inventory - Automation Portal</title>

    <link rel="icon" href="image/gajah_tunggal.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">  
    <link rel="stylesheet" href="assets/css/layouts/sidebar.css">
    <link rel="stylesheet" href="assets/css/layouts/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/card.css">
    <link rel="stylesheet" href="assets/css/components/modal.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="assets/vendor/sweetalert2.all.min.js"></script>
    <script src="assets/vendor/tailwind.js"></script>

    <style>
        @media print {

            /* Sembunyikan elemen yang tidak perlu saat print */
            #sidebar,
            header,
            #paginationContainer,
            .btn-action,
            #searchInput,
            button {
                display: none !important;
            }

            /* Atur layout tabel agar lebar penuh & putih */
            body,
            #main-content {
                background-color: white !important;
                color: black !important;
                overflow: visible !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .bg-slate-800 {
                background-color: white !important;
                border: 1px solid #ccc !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                color: black !important;
                font-size: 10pt !important;
            }

            th,
            td {
                border: 1px solid #000 !important;
                padding: 5px !important;
                color: black !important;
            }

            /* Paksa warna teks hitam */
            .text-white,
            .text-slate-400,
            .text-blue-400,
            .text-yellow-400 {
                color: black !important;
            }

            /* Sembunyikan kolom Aksi (Tombol Edit/Hapus) saat print */
            th:last-child,
            td:last-child {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <aside id="sidebar" class="w-64 bg-slate-950 border-r border-slate-800 flex flex-col transition-all duration-300 hidden md:flex">
            <div class="h-16 flex items-center justify-center border-b border-slate-800">
                <h1 class="text-xl font-bold text-white tracking-wide">JIS <span class="text-emerald-400">PORTAL.</span></h1>
            </div>

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
            <header class="h-16 shrink-0 bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-10 px-8 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button id="sidebarToggle" class="text-slate-400 hover:text-white mr-4 transition-transform active:scale-95">
                    </button>
                    <h2 class="text-lg font-medium text-white">Database Inventory</h2>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4 mr-2">
                        Total Items: <span class="text-white font-bold">
                            <?php
                            $countQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_assets");
                            $countData = mysqli_fetch_assoc($countQuery);
                            echo $countData['total'];
                            ?>
                        </span>
                    </div>

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
                <div class="flex border-b border-slate-700 mb-6">
                    <a href="database.php" class="px-6 py-3 text-sm font-bold text-emerald-400 border-b-2 border-emerald-400">
                        <i class="fas fa-microchip mr-2"></i> Machine & Assets
                    </a>
                    <a href="master_items.php" class="px-6 py-3 text-sm font-medium text-slate-400 hover:text-white hover:border-slate-500 border-b-2 border-transparent transition">
                        <i class="fas fa-box mr-2"></i> Master Items
                    </a>
                </div>

                <div class="flex flex-col md:flex-row justify-between gap-4">
                    <div class="flex flex-1 gap-2">
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3 top-3 text-slate-500 text-sm"></i>
                            <input id="searchInput" type="text" placeholder="Search Part Code, Name, or Machine..." class="w-full bg-slate-800 border border-slate-700 text-white pl-9 pr-4 py-2.5 rounded-lg focus:border-emerald-500 focus:outline-none transition text-sm" autocomplete="off">
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button onclick="downloadExcel()" class="bg-slate-800 hover:bg-green-700 text-slate-300 px-4 py-2.5 rounded-lg border border-slate-700 text-sm transition">
                            <i class="fas fa-file-excel mr-1"></i> Export Excel
                        </button>
                        <button id="btnAddItem" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-emerald-600/20 flex items-center gap-2">
                            <i class="fas fa-plus"></i> Add New Asset
                        </button>
                    </div>
                </div>

                <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-400">
                            <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                <tr>
                                    <th class="px-6 py-4 w-10"></th>
                                    <th class="px-6 py-4">Machine & Location</th>
                                    <th class="px-6 py-4">PLC & Communication</th>
                                    <th class="px-6 py-4">HMI System</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50" id="tableAssetBody">
                                <?php
                                // Query Ambil Data
                                $query = mysqli_query($conn, "SELECT * FROM tb_assets ORDER BY machine_name ASC");

                                // Looping Data
                                while ($row = mysqli_fetch_assoc($query)) {
                                    $id = $row['asset_id']; // ID Unik untuk Toggle

                                    // Pecah Data Gabungan (Supaya tampil rapi di Detail)
                                    // Format di DB: "Hardware - Software - Version"
                                    $drive = explode(' - ', $row['drive_info']);
                                    $ipc   = explode(' - ', $row['ipc_info']);
                                    $scan  = explode(' - ', $row['scanner_info']);
                                ?>

                                    <tr class="hover:bg-slate-700/20 transition group border-l-4 border-transparent hover:border-emerald-500">
                                        <td class="px-6 py-4 text-center">
                                            <button onclick="toggleDetail('<?php echo $id; ?>')" class="w-6 h-6 rounded-full bg-slate-700 text-emerald-400 hover:bg-emerald-600 hover:text-white transition flex items-center justify-center focus:outline-none">
                                                <i class="fas fa-plus text-xs transition-transform" id="icon-<?php echo $id; ?>"></i>
                                            </button>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="text-white font-bold text-base"><?php echo $row['machine_name']; ?></div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                <i class="fas fa-map-marker-alt mr-1"></i> <?php echo $row['plant']; ?> - <?php echo $row['area']; ?>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="text-blue-400 font-medium"><?php echo $row['plc_type']; ?></div>
                                            <div class="text-xs text-slate-500 mt-1">
                                                <span class="bg-slate-900 px-1.5 py-0.5 rounded border border-slate-700"><?php echo $row['comm_protocol']; ?></span>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="text-yellow-400 font-medium"><?php echo $row['hmi_type']; ?></div>
                                            <div class="text-xs text-slate-500"><?php echo $row['hmi_software']; ?></div>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section'): ?>
                                                <div class="flex items-center justify-center gap-2">
                                                    <button onclick="editAsset(
                                                        '<?php echo $row['asset_id']; ?>',
                                                        '<?php echo htmlspecialchars($row['plant'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['area'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['machine_name'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['comm_protocol'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['plc_type'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['plc_software'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['plc_version'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['hmi_type'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['hmi_software'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['hmi_version'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['drive_info'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['ipc_info'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['scanner_info'], ENT_QUOTES); ?>')"
                                                        class="bg-slate-700 hover:bg-blue-600 text-white w-8 h-8 rounded flex items-center justify-center transition" title="Edit Data">
                                                        <i class="fas fa-pen text-xs"></i>
                                                    </button>

                                                    <button onclick="confirmDeleteAsset(<?php echo $row['asset_id']; ?>)" class="bg-slate-700 hover:bg-red-600 text-white w-8 h-8 rounded flex items-center justify-center transition" title="Hapus Data">
                                                        <i class="fas fa-trash text-xs"></i>
                                                    </button>
                                                </div>

                                            <?php else: ?>
                                                <span class="text-slate-600 text-xs italic flex justify-center items-center h-8" title="Akses Terbatas (Read Only)">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <tr id="detail-<?php echo $id; ?>" class="hidden bg-slate-800/50 border-b border-slate-700 shadow-inner">
                                        <td colspan="5" class="px-8 py-6">
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-xs">
                                                <div class="space-y-3 border-r border-slate-700 pr-4">
                                                    <h4 class="text-emerald-400 font-bold uppercase tracking-wider mb-2"><i class="fas fa-code mr-1"></i> Software Config</h4>
                                                    <div class="grid grid-cols-2 gap-y-2">
                                                        <span class="text-slate-500">PLC Soft:</span>
                                                        <span class="text-white"><?php echo $row['plc_software'] . " " . $row['plc_version']; ?></span>
                                                        <span class="text-slate-500">HMI Soft:</span>
                                                        <span class="text-white"><?php echo $row['hmi_software'] . " " . $row['hmi_version']; ?></span>
                                                        <span class="text-slate-500">IPC OS:</span>
                                                        <span class="text-white"><?php echo isset($ipc[1]) ? $ipc[1] : '-'; ?></span>
                                                    </div>
                                                </div>

                                                <div class="space-y-3 border-r border-slate-700 pr-4">
                                                    <h4 class="text-purple-400 font-bold uppercase tracking-wider mb-2"><i class="fas fa-bolt mr-1"></i> Peripherals</h4>
                                                    <div>
                                                        <span class="text-slate-500 block mb-0.5">Drive / Servo:</span>
                                                        <span class="text-white font-medium block"><?php echo isset($drive[0]) ? $drive[0] : '-'; ?></span>
                                                        <span class="text-slate-600 italic">Soft: <?php echo isset($drive[1]) ? $drive[1] : ''; ?></span>
                                                    </div>

                                                    <div class="mt-2">
                                                        <span class="text-slate-500 block mb-0.5">Scanner:</span>
                                                        <span class="text-white font-medium"><?php echo isset($scan[0]) ? $scan[0] : '-'; ?></span>
                                                    </div>
                                                </div>

                                                <div class="space-y-3">
                                                    <h4 class="text-blue-400 font-bold uppercase tracking-wider mb-2"><i class="fas fa-folder-open mr-1"></i> Documents</h4>
                                                    <?php if (!empty($row['attachment_file'])): ?>
                                                        <a href="uploads/<?php echo $row['attachment_file']; ?>" target="_blank" class="flex items-center gap-3 p-2 bg-slate-900 rounded border border-slate-700 hover:border-blue-500 transition group">
                                                            <i class="fas fa-file-pdf text-red-400 text-lg"></i>
                                                            <div>
                                                                <div class="text-white font-medium"><?php echo $row['attachment_file']; ?></div>
                                                                <div class="text-[10px] text-slate-500">Click to download</div>
                                                            </div>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-slate-600 italic">- Tidak ada file -</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php }
                                ?>
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

    <!-- MODAL CREATE ASSET -->
    <div id="modalAddPart" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" id="backdropAddPart"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-4xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-server text-emerald-400"></i> Input Data Spesifikasi Automation
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Masukkan detail teknis, versi software, dan file pendukung.</p>
                    </div>
                    <button class="close-modal-add text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_add_asset.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <h4 class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Lokasi & Identitas</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                <label class="block text-xs text-slate-400 mb-1">Area</label>
                                <input type="text" name="area" placeholder="Contoh: FI, Filling" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Machine Name</label>
                                <input type="text" name="machine_name" placeholder="Contoh: MTMS-1" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs text-slate-400 mb-1">Communication Protocols</label>
                            <input type="text" name="comm_protocol" placeholder="Contoh: Profibus, Ethernet TCP/IP" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-blue-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">PLC Details</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label>
                                    <input type="text" name="plc_type" placeholder="Contoh: Logix 5561" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label>
                                        <input type="text" name="plc_software" placeholder="RS Logix 5000" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label>
                                        <input type="text" name="plc_version" placeholder="v16" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-yellow-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">HMI Details</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label>
                                    <input type="text" name="hmi_type" placeholder="Contoh: PanelView 1250" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label>
                                        <input type="text" name="hmi_software" placeholder="FT View ME" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label>
                                        <input type="text" name="hmi_version" placeholder="v8.20" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-purple-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Drive / Servo</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label>
                                    <input type="text" name="drive_hw" placeholder="S300 Kollmorgen" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-purple-500 focus:outline-none">
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label>
                                        <input type="text" name="drive_sw" placeholder="DriveGUI" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-purple-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label>
                                        <input type="text" name="drive_ver" placeholder="v3.4" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-purple-500 focus:outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-indigo-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">IPC / Computer</h4>
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label>
                                        <input type="text" name="ipc_hw" placeholder="Industrial PC" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-500 uppercase mb-1">OS</label>
                                        <input type="text" name="ipc_os" placeholder="Win 10" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 uppercase mb-1">Database / Apps</label>
                                    <input type="text" name="ipc_apps" placeholder="SQL, Wonderware" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <h4 class="text-slate-300 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Scanner / Barcode</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Device Name</label>
                                <input type="text" name="scan_hw" placeholder="Matrix 450N" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label>
                                <input type="text" name="scan_sw" placeholder="DL Code" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label>
                                <input type="text" name="scan_ver" placeholder="1.11.4" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <label class="block text-xs text-slate-400 mb-2 font-medium">Upload Manual Book / Datasheet</label>
                        <div class="w-full">
                            <label for="file_spec" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-emerald-500 transition group">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 mb-2 group-hover:text-emerald-400 transition"></i>
                                    <p class="text-sm text-slate-400 mb-1"><span class="font-semibold text-emerald-400">Klik untuk upload</span></p>
                                    <p id="file-name-spec" class="text-xs text-emerald-400 mt-2 font-medium hidden"></p>
                                </div>
                                <input id="file_spec" type="file" name="attachment" class="hidden" />
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-4">
                        <button type="button" class="close-modal-add flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition text-sm font-medium shadow-lg shadow-emerald-600/20">
                            <i class="fas fa-save mr-2"></i> Simpan Spesifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL UPDATE ASSET -->
    <div id="modalEditAsset" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEditAsset')"></div>

        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-4xl rounded-xl shadow-2xl p-6 relative overflow-y-auto max-h-[90vh] custom-scroll">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-edit text-blue-400"></i> Edit Data Spesifikasi
                    </h3>
                    <button onclick="closeModal('modalEditAsset')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_edit_asset.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="asset_id" id="edit_id">
                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <h4 class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Lokasi & Identitas</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Plant</label>
                                <select name="plant" id="edit_plant" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                    <option value="">-- Pilih --</option>
                                    <option value="PLANT A">PLANT A</option>
                                    <option value="PLANT BCHIT">PLANT BCHIT</option>
                                    <option value="PLANT D/K">PLANT D/K</option>
                                    <option value="PLANT E">PLANT E</option>
                                    <option value="PLANT TBR">PLANT TBR</option>
                                    <option value="PLANT MIXING">PLANT MIXING</option>
                                </select>
                            </div>
                            <div><label class="block text-xs text-slate-400 mb-1">Area</label><input type="text" name="area" id="edit_area" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"></div>
                            <div><label class="block text-xs text-slate-400 mb-1">Machine Name</label><input type="text" name="machine_name" id="edit_machine" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"></div>
                        </div>
                        <div class="mt-3"><label class="block text-xs text-slate-400 mb-1">Comm. Protocols</label><input type="text" name="comm_protocol" id="edit_comm" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-blue-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">PLC Details</h4>
                            <div class="space-y-3">
                                <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label><input type="text" name="plc_type" id="edit_plc_hw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2"><label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label><input type="text" name="plc_software" id="edit_plc_sw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></div>
                                    <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label><input type="text" name="plc_version" id="edit_plc_ver" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"></div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-yellow-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">HMI Details</h4>
                            <div class="space-y-3">
                                <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label><input type="text" name="hmi_type" id="edit_hmi_hw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none"></div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2"><label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label><input type="text" name="hmi_software" id="edit_hmi_sw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none"></div>
                                    <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label><input type="text" name="hmi_version" id="edit_hmi_ver" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-yellow-500 focus:outline-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-purple-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Drive / Servo</h4>
                            <div class="space-y-3">
                                <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label><input type="text" name="drive_hw" id="edit_drive_hw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-purple-500 focus:outline-none"></div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="col-span-2"><label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label><input type="text" name="drive_sw" id="edit_drive_sw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-purple-500 focus:outline-none"></div>
                                    <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label><input type="text" name="drive_ver" id="edit_drive_ver" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-purple-500 focus:outline-none"></div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <h4 class="text-indigo-400 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">IPC / Computer</h4>
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Hardware</label><input type="text" name="ipc_hw" id="edit_ipc_hw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
                                    <div><label class="block text-[10px] text-slate-500 uppercase mb-1">OS</label><input type="text" name="ipc_os" id="edit_ipc_os" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
                                </div>
                                <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Database / Apps</label><input type="text" name="ipc_apps" id="edit_ipc_apps" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <h4 class="text-slate-300 text-xs font-bold uppercase tracking-wider mb-3 border-b border-slate-700 pb-1">Scanner / Barcode</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Device Name</label><input type="text" name="scan_hw" id="edit_scan_hw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></div>
                            <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Software</label><input type="text" name="scan_sw" id="edit_scan_sw" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></div>
                            <div><label class="block text-[10px] text-slate-500 uppercase mb-1">Version</label><input type="text" name="scan_ver" id="edit_scan_ver" class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></div>
                        </div>
                    </div>

                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <label class="block text-xs text-slate-400 mb-2 font-medium">Update Manual Book (Opsional)</label>
                        <div class="w-full">
                            <label for="file_spec_edit" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-blue-500 transition group">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 mb-2 group-hover:text-blue-400 transition"></i>
                                    <p class="text-sm text-slate-400 mb-1"><span class="font-semibold text-blue-400">Klik untuk ganti file</span></p>
                                    <p class="text-[10px] text-slate-500">Biarkan kosong jika tidak ingin mengubah.</p>

                                    <p id="file-name-spec-edit" class="text-xs text-blue-400 mt-2 font-medium hidden"></p>
                                </div>

                                <input id="file_spec_edit" type="file" name="attachment" class="hidden" />
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800 mt-4">
                        <button type="button" onclick="closeModal('modalEditAsset')" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition text-sm font-medium shadow-lg">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/ui-sidebar.js"></script>
    <script src="assets/js/ui-modal.js"></script>

    <script>
        // --- FUNGSI EXPAND ROW (+) (VERSI ANTI-BENTROK) ---
        function toggleDetail(rowId) {
            const detailRow = document.getElementById('detail-' + rowId);
            const icon = document.getElementById('icon-' + rowId);

            if (detailRow && icon) {
                // Cek apakah baris tersembunyi (baik via class ATAU style)
                const isHidden = detailRow.classList.contains('hidden') || detailRow.style.display === 'none';

                if (isHidden) {
                    // BUKA: Paksa tampil via style agar mengalahkan pagination
                    detailRow.classList.remove('hidden');
                    detailRow.style.display = 'table-row';

                    // Putar Ikon
                    icon.classList.remove('fa-plus');
                    icon.classList.add('fa-minus');
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    // TUTUP
                    detailRow.classList.add('hidden');
                    detailRow.style.display = 'none';

                    // Balikin Ikon
                    icon.classList.remove('fa-minus');
                    icon.classList.add('fa-plus');
                    icon.style.transform = 'rotate(0deg)';
                }
            } else {
                console.error("Element detail tidak ditemukan: " + rowId);
            }
        }

        // --- 1. FUNGSI ISI FORM EDIT (Dipanggil tombol Pensil) ---
        function editAsset(id, plant, area, machine, comm,
            plc_type, plc_soft, plc_ver,
            hmi_type, hmi_soft, hmi_ver,
            drive, ipc, scan) {

            // 1. Identitas
            document.getElementById('edit_id').value = id;
            if (document.getElementById('edit_plant')) document.getElementById('edit_plant').value = plant;
            if (document.getElementById('edit_area')) document.getElementById('edit_area').value = area;
            if (document.getElementById('edit_machine')) document.getElementById('edit_machine').value = machine;
            if (document.getElementById('edit_comm')) document.getElementById('edit_comm').value = comm;

            // 2. PLC & HMI (Langsung isi)
            document.getElementById('edit_plc_hw').value = plc_type;
            document.getElementById('edit_plc_sw').value = plc_soft;
            document.getElementById('edit_plc_ver').value = plc_ver;

            document.getElementById('edit_hmi_hw').value = hmi_type;
            document.getElementById('edit_hmi_sw').value = hmi_soft;
            document.getElementById('edit_hmi_ver').value = hmi_ver;

            // 3. DRIVE (Pecah string "HW - SW - Ver")
            // Jika data kosong/tidak ada separator, aman karena pakai array safe check
            let dParts = drive.split(' - ');
            document.getElementById('edit_drive_hw').value = dParts[0] || '';
            document.getElementById('edit_drive_sw').value = dParts[1] || '';
            document.getElementById('edit_drive_ver').value = dParts[2] || '';

            // 4. IPC (Pecah string "HW - OS - Apps")
            let iParts = ipc.split(' - ');
            document.getElementById('edit_ipc_hw').value = iParts[0] || '';
            document.getElementById('edit_ipc_os').value = iParts[1] || '';
            document.getElementById('edit_ipc_apps').value = iParts[2] || '';

            // 5. SCANNER (Pecah string "HW - SW - Ver")
            let sParts = scan.split(' - ');
            document.getElementById('edit_scan_hw').value = sParts[0] || '';
            document.getElementById('edit_scan_sw').value = sParts[1] || '';
            document.getElementById('edit_scan_ver').value = sParts[2] || '';

            openModal('modalEditAsset');
        }

        // --- 2. FUNGSI KONFIRMASI HAPUS ---
        function confirmDeleteAsset(id) {
            Swal.fire({
                title: 'Hapus Data Mesin?',
                text: "Data dan file lampiran akan hilang permanen!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete/delete_asset.php?id=' + id;
                }
            })
        }

        // --- 3. FUNGSI TOGGLE NOTIFIKASI (Lonceng) ---
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

        // --- 4. MASTER NOTIFIKASI SUKSES/GAGAL (Satu Blok Saja) ---
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        function cleanUrl() {
            window.history.replaceState(null, null, window.location.pathname);
        }

        if (status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: 'Data aset baru telah ditambahkan.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669'
            }).then(() => cleanUrl());
        } else if (status === 'updated') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Diupdate!',
                text: 'Data spesifikasi mesin diperbarui.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669'
            }).then(() => cleanUrl());
        } else if (status === 'deleted') {
            Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: 'Data mesin telah dihapus.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#059669'
            }).then(() => cleanUrl());
        } else if (status === 'error') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: msg || 'Terjadi kesalahan.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ef4444'
            }).then(() => cleanUrl());
        }

        // --- LOGIC UPLOAD FILE (MODAL EDIT) ---
        const fileInputEdit = document.getElementById('file_spec_edit');
        const fileNameEdit = document.getElementById('file-name-spec-edit');

        if (fileInputEdit && fileNameEdit) {
            fileInputEdit.addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    fileNameEdit.classList.remove('hidden');
                    fileNameEdit.textContent = `📄 ${this.files[0].name}`;
                }
            });
        }

        // ============================================================
        // LOGIKA SEARCH & PAGINATION (CLIENT SIDE) - DATABASE PART
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {

            // KONFIGURASI
            const rowsPerPage = 10; // Mau tampil berapa baris per halaman?

            // SELEKTOR
            const tableBody = document.getElementById('tableAssetBody');
            // Ambil hanya baris MASTER (yang ada datanya), abaikan baris DETAIL (yang hidden)
            // Kita pakai class 'asset-row' yang tadi ada di tr
            // Kalau belum ada class 'asset-row' di tr, script ini akan ambil semua tr ganjil
            let allRows = Array.from(tableBody.querySelectorAll('tr'));

            // PENTING: Karena tabel kita punya 2 baris per data (1 Master, 1 Detail Hidden),
            // Kita harus memfilter agar yang dihitung cuma baris MASTER.
            // Cara paling aman: Kita filter baris yang TIDAK punya ID 'detail-...'
            allRows = allRows.filter(row => !row.id.includes('detail-'));

            const searchInput = document.getElementById('searchInput');
            const pageInfo = document.getElementById('pageInfo');
            const paginationControls = document.getElementById('paginationControls');

            let currentPage = 1;
            let currentSearchKeyword = "";

            // FUNGSI UTAMA: RENDER TABEL
            function renderTable() {
                // 1. Filter Data dulu
                const filteredRows = allRows.filter(row => {
                    const text = row.textContent.toLowerCase();
                    return text.includes(currentSearchKeyword);
                });

                // 2. Hitung Pagination
                const totalItems = filteredRows.length;
                const totalPages = Math.ceil(totalItems / rowsPerPage);

                if (currentPage > totalPages) currentPage = 1;
                if (currentPage < 1 && totalPages > 0) currentPage = 1;

                // 3. Hitung Slice
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                // 4. Manipulasi Tampilan
                // Sembunyikan SEMUA baris (Master & Detail)
                const allTrs = tableBody.querySelectorAll('tr');
                allTrs.forEach(tr => tr.style.display = 'none');

                // Tampilkan HANYA baris Master yang lolos filter & halaman
                filteredRows.slice(start, end).forEach(row => {
                    row.style.display = '';
                    // Note: Baris detailnya tetap hidden sampai tombol (+) diklik, 
                    // jadi tidak perlu kita apa-apakan.
                });

                // 5. Update Info Teks
                const startInfo = totalItems === 0 ? 0 : start + 1;
                const endInfo = Math.min(end, totalItems);
                pageInfo.innerText = `Showing ${startInfo} - ${endInfo} of ${totalItems} entries`;

                // 6. Bikin Tombol
                renderButtons(totalPages);
            }

            // FUNGSI BIKIN TOMBOL
            function renderButtons(totalPages) {
                paginationControls.innerHTML = "";
                if (totalPages <= 1) return;

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

                // Prev
                paginationControls.appendChild(createBtn("Prev", currentPage - 1, false, currentPage === 1));

                // Angka (Logic simple: tampilin semua angka. Kalau mau canggih kaya Google ada titik2nya beda lagi)
                // Kita batasi max 5 tombol angka biar gak kepanjangan
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, currentPage + 2);

                for (let i = startPage; i <= endPage; i++) {
                    paginationControls.appendChild(createBtn(i, i, i === currentPage));
                }

                // Next
                paginationControls.appendChild(createBtn("Next", currentPage + 1, false, currentPage === totalPages));
            }

            // EVENT LISTENER SEARCH
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    currentSearchKeyword = this.value.toLowerCase();
                    currentPage = 1;
                    renderTable();
                });
            }

            // Jalankan pertama kali
            renderTable();
        });

        // --- FUNGSI DOWNLOAD EXCEL SESUAI SEARCH ---
        function downloadExcel() {
            const searchValue = document.getElementById('searchInput').value;
            // Redirect ke file PHP export dengan membawa parameter search
            window.location.href = 'export/export_excel.php?search=' + encodeURIComponent(searchValue);
        }
    </script>

<button onclick="toggleMobileMenu()" id="mobileMenuBtn" class="fixed bottom-24 right-4 z-[60] md:hidden bg-emerald-600/50 text-white w-12 h-12 rounded-full shadow-lg shadow-emerald-900/50 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 border-1 border-slate-900">
    <i id="iconOpen" class="fas fa-bars text-lg"></i>
    <i id="iconClose" class="fas fa-times text-lg hidden"></i>
</button>

<nav id="mobileNavbar" class="fixed bottom-4 left-4 right-4 bg-slate-900/90 backdrop-blur-md border border-slate-700 rounded-2xl flex justify-around items-center py-3 z-50 md:hidden transition-transform duration-300 ease-in-out translate-y-[150%] shadow-2xl">

    <?php $page = basename($_SERVER['PHP_SELF']); ?>

    <a href="dashboard.php" class="flex flex-col items-center gap-1 w-1/5 transition group <?php echo ($page == 'dashboard.php') ? 'text-emerald-400' : 'text-slate-400 hover:text-emerald-300'; ?>">
        <i class="fas fa-tachometer-alt text-lg mb-0.5 group-active:scale-90 transition"></i>
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