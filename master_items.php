<?php
// 1. Panggil Satpam & Koneksi Global
include 'layouts/auth_and_config.php';

// --- LOGIKA FILTER & PAGINATION PHP (Tetap Pertahankan) ---
$searchQuery = "";
$urlParams = ""; 
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $keyword = mysqli_real_escape_string($conn, $_GET['search']);
    $searchQuery = " WHERE item_code LIKE '%$keyword%' OR item_name LIKE '%$keyword%' OR brand LIKE '%$keyword%' OR location LIKE '%$keyword%' ";
    $urlParams .= "&search=" . urlencode($_GET['search']);
}

$limit = 50; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$sqlCount = "SELECT COUNT(*) as total FROM tb_master_items $searchQuery";
$resCount = mysqli_query($conn, $sqlCount);
$totalData = mysqli_fetch_assoc($resCount)['total'];
$totalPages = ceil($totalData / $limit);

$query = mysqli_query($conn, "SELECT * FROM tb_master_items $searchQuery ORDER BY item_id ASC LIMIT $limit OFFSET $offset");

// --- SETTING LAYOUT ---
$pageTitle = "Master Items Database";

// [SLOT HEADER] Total Data & Tombol Add
$extraMenu = '
    <div class="flex items-center gap-4">
        <div class="text-xs text-slate-400 hidden sm:block border-r border-slate-700 pr-4">
            Total Items: <span class="text-emerald-400 font-bold">' . number_format($totalData) . '</span>
        </div>';

// [SLOT HEAD] Style Print & Animasi Modal + Library TomSelect
$extraHead = '
    <link href="assets/vendor/tom-select.css" rel="stylesheet">
    <script src="assets/vendor/tom-select.complete.min.js"></script>
    
    <style>
        /* 1. Animasi Modal Item */
        #modalItem:not(.hidden)>div:last-child>div,
        #modalEditItem:not(.hidden)>div:last-child>div {
            animation: popUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes popUp {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* 2. Style Print (Sama seperti database.php) */
        @media print {
            #sidebar, header, #paginationContainer, .btn-action, #searchInput, button, .mb-6 {
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
            .text-white, .text-slate-400, .text-blue-400, .text-yellow-400 { color: black !important; }
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

    <!-- HEAD DISINI -->
    <?php include 'layouts/head.php'; ?>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR ADA DISINI -->
     <?php include 'layouts/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">
            
            <!-- HEADER DISINI -->
            <?php include 'layouts/header.php'; ?>

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

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script>
    // --- A. FUNGSI EDIT ITEM (Isi Modal Edit) ---
        function editItem(id, code, name, brand, spec, loc, stock, unit, cat, img) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_code').value = code;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_brand').value = brand;
            document.getElementById('edit_spec').value = spec;
            document.getElementById('edit_loc').value = loc;
            document.getElementById('edit_stock').value = stock;
            
            // Handle Dropdown (TomSelect/Select Biasa)
            if(document.getElementById('edit_unit')) document.getElementById('edit_unit').value = unit;
            if(document.getElementById('edit_category')) document.getElementById('edit_category').value = cat;

            // Preview Gambar Lama
            const imgPrev = document.getElementById('preview_img_edit');
            if (imgPrev) {
                if (img && img !== '') {
                    imgPrev.src = 'uploads/' + img;
                    imgPrev.classList.remove('hidden');
                } else {
                    imgPrev.classList.add('hidden');
                }
            }
            
            openModal('modalEditItem');
        }

        // --- B. FUNGSI HAPUS SATU ITEM ---
        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Item?',
                text: "Data stock akan hilang!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete/delete_item.php?id=' + id;
                }
            });
        }

        // --- C. FUNGSI RESET DATABASE (HAPUS SEMUA - FITUR BERBAHAYA) ---
        function confirmResetDatabase() {
            Swal.fire({
                title: 'ANDA YAKIN?',
                text: "SEMUA DATA ITEM AKAN DIHAPUS TOTAL! Tidak bisa dikembalikan.",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#f1f5f9',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Minta Password Admin
                    Swal.fire({
                        title: 'Verifikasi Keamanan',
                        input: 'password',
                        inputLabel: 'Masukkan Password Admin',
                        inputPlaceholder: 'Password...',
                        showCancelButton: true,
                        background: '#1e293b', color: '#f1f5f9',
                        confirmButtonColor: '#ef4444',
                        preConfirm: (value) => {
                            if (!value) Swal.showValidationMessage('Password wajib diisi!')
                        }
                    }).then((pass) => {
                        if (pass.isConfirmed && pass.value) {
                            // Redirect ke proses reset dengan password
                            window.location.href = 'process/reset_master_item.php?pass=' + encodeURIComponent(pass.value);
                        }
                    });
                }
            })
        }

        // --- D. NOTIFIKASI KHUSUS (Reset & Wrong Pass) ---
        // (Karena layouts/scripts.php cuma handle status success/error biasa,
        //  kita handle status 'deleted_all' dan 'wrong_pass' disini)
        
        var urlParamsLocal = new URLSearchParams(window.location.search);
        var statusLocal = urlParamsLocal.get('status');

        if (statusLocal === 'deleted_all') {
            Swal.fire({
                icon: 'success',
                title: 'DATABASE BERSIH!',
                text: 'Semua data master item telah dikosongkan.',
                background: '#1e293b', color: '#fff'
            }).then(() => window.history.replaceState(null, null, window.location.pathname));
        }

        if (statusLocal === 'wrong_pass') {
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Password Admin yang Anda masukkan SALAH.',
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444'
            }).then(() => window.history.replaceState(null, null, window.location.pathname));
        }
    </script>

    </body>
</html>