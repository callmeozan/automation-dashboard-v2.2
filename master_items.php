<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// --- 1. LOGIKA FILTER PENCARIAN (PHP) ---
$searchQuery = "";
$urlParams = ""; // Untuk link pagination
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['search']);
    // Cari di kode, nama, merk, atau lokasi
    $searchQuery = " WHERE item_code LIKE '%$keyword%' 
                     OR item_name LIKE '%$keyword%' 
                     OR brand LIKE '%$keyword%' 
                     OR location LIKE '%$keyword%' ";
    $urlParams .= "&search=" . urlencode($_GET['search']);
}

// --- 2. LOGIKA PAGINATION (PHP) ---
$limit = 10; // Tampil 10 data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Hitung Total Data (Untuk tombol pagination)
$sqlCount = "SELECT COUNT(*) as total FROM tb_master_items $searchQuery";
$resCount = mysqli_query($conn, $sqlCount);
$totalData = mysqli_fetch_assoc($resCount)['total'];
$totalPages = ceil($totalData / $limit);

// --- 3. QUERY DATA UTAMA (Pakai LIMIT) ---
// Ini rahasia cepatnya: Cuma ambil 10 baris dari ribuan data
$query = mysqli_query($conn, "SELECT * FROM tb_master_items $searchQuery ORDER BY item_name ASC LIMIT $limit OFFSET $offset");

// Logic Notifikasi (Tetap Ada)
$queryNotif1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_daily_reports WHERE category='Breakdown' AND status='Open'");
$countBreakdown = mysqli_fetch_assoc($queryNotif1)['total'];
$queryBreakdownList = mysqli_query($conn, "SELECT * FROM tb_daily_reports WHERE category='Breakdown' AND status='Open' ORDER BY date_log DESC LIMIT 5");
$today = date('Y-m-d');
$queryNotif2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_projects WHERE due_date < '$today' AND status != 'Done'");
$countOverdue = mysqli_fetch_assoc($queryNotif2)['total'];
$queryOverdueList = mysqli_query($conn, "SELECT * FROM tb_projects WHERE due_date < '$today' AND status != 'Done' ORDER BY due_date ASC LIMIT 5");
$totalNotif = $countBreakdown + $countOverdue;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#03142c">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Items - Automation Portal</title>

    <link rel="icon" href="image/gajah_tunggal.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/layouts/sidebar.css">
    <link rel="stylesheet" href="assets/css/layouts/header.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/card.css">
    <link rel="stylesheet" href="assets/css/components/modal.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="assets/vendor/tailwind.js"></script>
    <script src="assets/vendor/sweetalert2.all.min.js"></script>

    <style>
        #modalItem:not(.hidden)>div:last-child>div {
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
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt w-6"></i><span class="font-medium">Dashboard</span></a>

                <div class="relative group">
                    <button onclick="toggleDbMenu()" class="nav-item w-full flex justify-between items-center focus:outline-none active">
                        <div class="flex items-center"><i class="fas fa-database w-6"></i><span>Database</span></div>
                        <i class="fas fa-chevron-down text-xs transition-transform" id="arrow-db"></i>
                    </button>
                    <div id="dbSubmenu" class="pl-8 space-y-1 mt-1 bg-slate-900/50 rounded-lg py-2 border border-slate-800">
                        <a href="database.php" class="block px-3 py-2 text-sm text-slate-400 hover:text-white hover:bg-slate-800 rounded transition">Machine / Assets</a>
                        <a href="master_items.php" class="block px-3 py-2 text-sm text-emerald-400 bg-slate-800 rounded transition font-bold">Master Items</a>
                    </div>
                </div>

                <a href="laporan.php" class="nav-item"><i class="fas fa-clipboard-list w-6"></i><span>Daily Report</span></a>
                <a href="project.php" class="nav-item"><i class="fas fa-project-diagram w-6"></i><span>Projects</span></a>

                <a href="overtime.php" class="nav-item">
                    <i class="fas fa-clock w-6"></i>
                    <span>Overtime</span>
                </a>

                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section')): ?>
                    <a href="dashboard.php?open_modal=adduser" class="nav-item hover:text-emerald-400 transition"><i class="fa-solid fa-user-plus w-6"></i><span>Add User</span></a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <div class="px-4 py-2 text-xs font-bold text-slate-500 uppercase tracking-wider mt-4">Admin Menu</div>
                    <a href="manage_users.php" class="nav-item">
                        <i class="fas fa-users-cog w-6"></i> <span class="font-medium">User Management</span>
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt w-6"></i><span>Logout</span></a>
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
                    <button id="sidebarToggle" class="text-slate-400 hover:text-white mr-4 transition-transform active:scale-95 hidden md:block"></button>
                    <h2 class="text-lg font-medium text-white">Database Inventory</h2>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4 mr-2">
                        Total Items: <span class="text-white font-bold">
                            <?php
                            $countQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_master_items");
                            $countData = mysqli_fetch_assoc($countQuery);
                            echo $countData['total'];
                            ?>
                        </span>
                    </div>

                    <div class="relative">
                        <button onclick="toggleNotif()" class="p-2 text-slate-400 hover:text-white relative transition focus:outline-none">
                            <i class="fas fa-bell"></i>
                            <?php if ($totalNotif > 0): ?><span class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-slate-900 animate-pulse"></span><?php endif; ?>
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
                                        <div class="px-4 py-2 bg-red-500/10 text-red-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-700">Mesin Breakdown (<?php echo $countBreakdown; ?>)</div>
                                        <?php while ($rowBD = mysqli_fetch_assoc($queryBreakdownList)): ?>
                                            <a href="laporan.php" class="block px-4 py-3 hover:bg-slate-700 transition border-b border-slate-700/30 group">
                                                <div class="flex items-start gap-3">
                                                    <div class="bg-red-500/20 p-1.5 rounded text-red-400 mt-0.5 group-hover:bg-red-500 group-hover:text-white transition"><i class="fas fa-car-crash text-xs"></i></div>
                                                    <div>
                                                        <p class="text-xs font-bold text-white"><?php echo $rowBD['machine_name']; ?></p>
                                                        <p class="text-[10px] text-slate-400 line-clamp-1"><?php echo $rowBD['problem']; ?></p>
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endwhile; ?>
                                    <?php endif; ?>

                                    <?php if ($countOverdue > 0): ?>
                                        <div class="px-4 py-2 bg-orange-500/10 text-orange-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-700">Project Overdue (<?php echo $countOverdue; ?>)</div>
                                        <?php while ($rowOD = mysqli_fetch_assoc($queryOverdueList)): ?>
                                            <a href="project.php" class="block px-4 py-3 hover:bg-slate-700 transition border-b border-slate-700/30 group">
                                                <div class="flex items-start gap-3">
                                                    <div class="bg-orange-500/20 p-1.5 rounded text-orange-400 mt-0.5 group-hover:bg-orange-500 group-hover:text-white transition"><i class="far fa-clock text-xs"></i></div>
                                                    <div>
                                                        <p class="text-xs font-bold text-white"><?php echo $rowOD['project_name']; ?></p>
                                                        <?php
                                                        $daysLate = floor((time() - strtotime($rowOD['due_date'])) / (60 * 60 * 24));
                                                        ?>
                                                        <p class="text-[10px] text-orange-400 mt-1 font-bold">Telat <?php echo $daysLate; ?> hari</p>
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

            <div class="p-8 space-y-6 fade-in">
                <div class="flex border-b border-slate-700 mb-6">
                    <a href="database.php" class="px-6 py-3 text-sm font-medium text-slate-400 hover:text-white hover:border-slate-500 border-b-2 border-transparent transition">
                        <i class="fas fa-microchip mr-2"></i> Machine & Assets
                    </a>
                    <a href="master_items.php" class="px-6 py-3 text-sm font-bold text-emerald-400 border-b-2 border-emerald-400">
                        <i class="fas fa-box mr-2"></i> Master Items
                    </a>
                </div>

                <div class="flex flex-col md:flex-row justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
                        <form method="GET" action="master_items.php">
                            <i class="fas fa-search absolute left-4 top-3.5 text-slate-500"></i>
                            <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Search Item Code, Name, Brand... (Press Enter)" class="w-full bg-slate-800 border border-slate-700 text-white pl-10 pr-4 py-3 rounded-lg focus:border-emerald-500 focus:outline-none transition">
                        </form>
                    </div>

                    <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section' || $_SESSION['role'] == 'officer'): ?>
                            <button onclick="openModal('modalImport')" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-blue-600/20 flex items-center gap-2">
                                <i class="fas fa-file-csv"></i> Import CSV
                            </button>
                        <?php endif; ?>

                        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section' || $_SESSION['role'] == 'officer'): ?>
                            <button onclick="confirmResetDatabase()" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition shadow-lg shadow-red-600/20 flex items-center gap-2">
                                <i class="fas fa-trash-alt"></i> Clear All Data
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-400">
                            <thead class="bg-slate-900/50 text-xs uppercase font-semibold text-slate-300 border-b border-slate-700">
                                <tr>
                                    <th class="px-6 py-4 w-48">Item Code</th>
                                    <th class="px-6 py-4">Part Name</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50">
                                <?php
                                if (mysqli_num_rows($query) > 0) {
                                    while ($row = mysqli_fetch_assoc($query)) {
                                        // Hidden Data untuk info saja (karena read only)
                                ?>
                                        <tr class="item-row hover:bg-slate-700/20 transition group">
                                            <td class="px-6 py-4">
                                                <span class="font-mono text-emerald-400 font-bold bg-emerald-500/10 px-2 py-1 rounded border border-emerald-500/20">
                                                    <?php echo $row['item_code']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-white font-medium">
                                                <?php echo $row['item_name']; ?>
                                                <div class="text-xs text-slate-500 mt-1 flex gap-3">
                                                    <!-- <span><i class="fas fa-tag mr-1"></i> <?php echo $row['brand']; ?></span> -->

                                                    <!-- <span><i class="fas fa-map-marker-alt mr-1"></i> <?php echo $row['location']; ?></span> -->
                                                    <span><i class="fas fa-map-marker-alt mr-1"></i> 
                                                        <?php 
                                                        // 1. Ambil kode barang dari baris ini
                                                        $kode = $row['item_code']; 

                                                        // 2. Cari di tabel tb_stocks, gudang mana saja yang punya stok > 0
                                                        $q_loc = mysqli_query($conn, "SELECT warehouse_name FROM tb_stocks WHERE part_number = '$kode' AND quantity > 0");
                                                        
                                                        // 3. Tampung hasilnya ke array
                                                        $lokasi_array = [];
                                                        while($l = mysqli_fetch_assoc($q_loc)){
                                                            $lokasi_array[] = $l['warehouse_name'];
                                                        }

                                                        // 4. Tampilkan hasilnya (digabung koma)
                                                        if(!empty($lokasi_array)){
                                                            echo implode(", ", $lokasi_array); // Contoh Output: ABW1, ABW3
                                                        } else {
                                                            echo "-"; // Kalau tidak ada stok di manapun
                                                        }
                                                        ?>
                                                    </span>

                                                    <span><i class="fas fa-box mr-1"></i> Stok: <?php echo $row['stock']; ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="2" class="px-6 py-8 text-center text-slate-500 italic">Tidak ada data item.</td>
                                    </tr>
                                <?php } ?>
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
        </main>
    </div>

    <!-- Modal Import CSV -->
    <div id="modalImport" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalImport')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-md rounded-xl shadow-2xl p-6 relative">

                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-file-csv text-blue-400"></i> Import Data Part
                    </h3>
                    <button onclick="closeModal('modalImport')" class="text-slate-400 hover:text-red-400 transition"><i class="fas fa-times text-xl"></i></button>
                </div>

                <form action="process/process_import_item.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                        <label class="block text-xs text-slate-400 mb-2 font-medium">Pilih File CSV</label>
                        <input type="file" name="file_csv" accept=".csv" required class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-blue-400 hover:file:bg-slate-700" />
                        <p class="text-[10px] text-slate-500 mt-2">Pastikan format CSV sesuai template (PART_NUM, DESCRIPTION, dll).</p>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modalImport')" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium shadow-lg">Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/ui-sidebar.js"></script>
    <script>
        // --- LOGIKA DARK/LIGHT MODE ---
        // 1. Cek memori saat loading
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
            // Update icon setelah loading selesai
            setTimeout(() => updateIcon(true), 100);
        }

        // 2. Fungsi Tombol Switch
        function toggleTheme() {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            updateIcon(isLight);
        }

        // 3. Fungsi Ubah Ikon
        function updateIcon(isLight) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                if (isLight) {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    icon.classList.add('text-slate-600');
                } else {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                    icon.classList.remove('text-slate-600');
                }
            }
        }

        function toggleNotif() {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown.classList.contains('hidden')) dropdown.classList.remove('hidden');
            else dropdown.classList.add('hidden');
        }

        // --- FUNGSI BANTUAN MODAL (PASTIKAN ADA) ---
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('hidden');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('hidden');
        }

        function confirmResetDatabase() {
            Swal.fire({
                title: 'ANDA YAKIN?',
                text: "Semua data Master Item akan DIHAPUS TOTAL! Data tidak bisa kembali.",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b',
                color: '#f1f5f9',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Minta password lagi biar aman (Optional, tapi disarankan)
                    Swal.fire({
                        title: 'Verifikasi Keamanan',
                        input: 'password',
                        inputLabel: 'Masukkan Password Admin',
                        inputPlaceholder: 'Password...',
                        showCancelButton: true,
                        background: '#1e293b',
                        color: '#f1f5f9',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        preConfirm: (value) => {
                            if (!value) {
                                Swal.showValidationMessage('Password tidak boleh kosong!')
                            }
                        }
                    }).then((pass) => {
                        if (pass.value) {
                            // Kirim ke PHP penghapus
                            window.location.href = 'process/reset_master_item.php?pass=' + encodeURIComponent(pass.value);
                        }
                    });
                }
            })
        }

        // ... (Script Notifikasi yang sudah ada) ...
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        function cleanUrl() {
            window.history.replaceState(null, null, window.location.pathname);
        }

        // Success Reset
        if (status === 'deleted_all') {
            Swal.fire({
                icon: 'success',
                title: 'DATABASE BERSIH!',
                text: 'Semua data master item telah dihapus.',
                background: '#1e293b',
                color: '#fff'
            }).then(() => cleanUrl());
        }

        // --- TAMBAHKAN INI UNTUK PASSWORD SALAH ---
        if (status === 'wrong_pass') {
            Swal.fire({
                icon: 'error',
                title: 'Proses Hapus Dibatalkan',
                text: 'Password Admin yang Anda masukkan SALAH.',
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ef4444'
            }).then(() => cleanUrl());
        }
    </script>

    <nav class="fixed bottom-0 left-0 w-full bg-slate-950 border-t border-slate-800 flex justify-around items-center py-3 z-50 md:hidden safe-area-pb">

        <?php $page = basename($_SERVER['PHP_SELF']); ?>

        <a href="dashboard.php" class="flex flex-col items-center gap-1 w-1/5 transition <?php echo ($page == 'dashboard.php') ? 'text-emerald-400' : 'text-slate-500 hover:text-slate-300'; ?>">
            <i class="fas fa-tachometer-alt text-xl mb-0.5"></i>
            <span class="text-[9px] font-medium uppercase tracking-wide">Home</span>
        </a>

        <a href="database.php" class="flex flex-col items-center gap-1 w-1/5 transition <?php echo ($page == 'database.php' || $page == 'master_items.php') ? 'text-emerald-400' : 'text-slate-500 hover:text-slate-300'; ?>">
            <i class="fas fa-database text-xl mb-0.5"></i>
            <span class="text-[9px] font-medium uppercase tracking-wide">Database</span>
        </a>

        <a href="laporan.php" class="flex flex-col items-center gap-1 w-1/5 transition <?php echo ($page == 'laporan.php' || $page == 'my_laporan.php') ? 'text-emerald-400' : 'text-slate-500 hover:text-slate-300'; ?>">
            <i class="fas fa-clipboard-list text-xl mb-0.5"></i>
            <span class="text-[9px] font-medium uppercase tracking-wide">Report</span>
        </a>

        <a href="project.php" class="flex flex-col items-center gap-1 w-1/5 transition <?php echo ($page == 'project.php') ? 'text-emerald-400' : 'text-slate-500 hover:text-slate-300'; ?>">
            <i class="fas fa-project-diagram text-xl mb-0.5"></i>
            <span class="text-[9px] font-medium uppercase tracking-wide">Projects</span>
        </a>

        <a href="logout.php" class="flex flex-col items-center gap-1 w-1/5 text-slate-500 hover:text-red-400 transition">
            <i class="fas fa-sign-out-alt text-xl mb-0.5"></i>
            <span class="text-[9px] font-medium uppercase tracking-wide">Logout</span>
        </a>

    </nav>
</body>

</html>