<?php
include 'layouts/auth_and_config.php';

// ... (Query totalAssets yang tadi) ...

$pageTitle = "Database Inventory";

// Kita bungkus CSS print Bapak ke dalam variable ini
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

<!-- HEAD ADA DISINI -->
 <?php include 'layouts/head.php'; ?>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR ADA DISINI -->
        <?php include 'layouts/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">

        <!-- HEADER ADA DISINI -->
         <?php include 'layouts/header.php'; ?>

         <!-- HALAMAN UTAMA DATABASE -->
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

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <!-- SCRIPT UTAMA DATABASE.PHP -->
    <script>
        window.rowsPerPage = window.rowsPerPage || 10;
        window.currentPage = window.currentPage || 1;
        window.currentSearchKeyword = window.currentSearchKeyword || "";
        window.allRows = window.allRows || [];

        // document.addEventListener('DOMContentLoaded', function() {
        // document.addEventListener('turbo:load', function() {
        (function() {
            if (document.documentElement.hasAttribute("data-turbo-preview")) return;
            if (!window.location.pathname.includes('database.php')) return;
            const tableBody = document.getElementById('tableAssetBody');
            if (!tableBody) return;

            // Ambil hanya baris MASTER (yg punya class asset-row), abaikan detail row
            // Pastikan di HTML <tr> nya sudah ada class="asset-row"
            // Kalau belum ada, kita filter manual berdasarkan ID
            const rawRows = Array.from(tableBody.querySelectorAll('tr'));
            allRows = rawRows.filter(r => !r.id.startsWith('detail-')); 

            // --- D. LOGIC UPLOAD FILE INFO ---
            const fileInputEdit = document.getElementById('file_spec_edit');
            if (fileInputEdit) {
                fileInputEdit.addEventListener('change', function() {
                    const fileNameEdit = document.getElementById('file-name-spec-edit');
                    if (this.files.length > 0) {
                        fileNameEdit.classList.remove('hidden');
                        fileNameEdit.textContent = `📄 ${this.files[0].name}`;
                    }
                });
            }

            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    currentSearchKeyword = this.value.toLowerCase();
                    currentPage = 1;
                    renderTable();
                });
            }

            renderTable();
        })();

        // --- A. EXPAND ROW (TOGGLE DETAIL) ---
        function toggleDetail(rowId) {
            const detailRow = document.getElementById('detail-' + rowId);
            const icon = document.getElementById('icon-' + rowId);

            if (detailRow && icon) {
                const isHidden = detailRow.classList.contains('hidden') || detailRow.style.display === 'none';
                if (isHidden) {
                    detailRow.classList.remove('hidden');
                    detailRow.style.display = 'table-row';
                    icon.classList.replace('fa-plus', 'fa-minus');
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    detailRow.classList.add('hidden');
                    detailRow.style.display = 'none';
                    icon.classList.replace('fa-minus', 'fa-plus');
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        }

        // --- B. EDIT ASSET ---
        function editAsset(id, plant, area, machine, comm, plc_hw, plc_sw, plc_ver, hmi_hw, hmi_sw, hmi_ver, drive, ipc, scan) {
            // Isi form standard
            document.getElementById('edit_id').value = id;
            if (document.getElementById('edit_plant')) document.getElementById('edit_plant').value = plant;
            if (document.getElementById('edit_area')) document.getElementById('edit_area').value = area;
            if (document.getElementById('edit_machine')) document.getElementById('edit_machine').value = machine;
            if (document.getElementById('edit_comm')) document.getElementById('edit_comm').value = comm;

            // Isi form PLC & HMI
            document.getElementById('edit_plc_hw').value = plc_hw;
            document.getElementById('edit_plc_sw').value = plc_sw;
            document.getElementById('edit_plc_ver').value = plc_ver;
            document.getElementById('edit_hmi_hw').value = hmi_hw;
            document.getElementById('edit_hmi_sw').value = hmi_sw;
            document.getElementById('edit_hmi_ver').value = hmi_ver;

            // Helper pecah string "HW - SW - Ver" (Pakai safe check)
            const splitSpec = (str, idx) => (str ? str.split(' - ')[idx] || '' : '');

            // Drive
            document.getElementById('edit_drive_hw').value = splitSpec(drive, 0);
            document.getElementById('edit_drive_sw').value = splitSpec(drive, 1);
            document.getElementById('edit_drive_ver').value = splitSpec(drive, 2);

            // IPC
            document.getElementById('edit_ipc_hw').value = splitSpec(ipc, 0);
            document.getElementById('edit_ipc_os').value = splitSpec(ipc, 1);
            document.getElementById('edit_ipc_apps').value = splitSpec(ipc, 2);

            // Scanner
            document.getElementById('edit_scan_hw').value = splitSpec(scan, 0);
            document.getElementById('edit_scan_sw').value = splitSpec(scan, 1);
            document.getElementById('edit_scan_ver').value = splitSpec(scan, 2);

            openModal('modalEditAsset');
        }

        // --- C. HAPUS ASSET ---
        function confirmDeleteAsset(id) {
            Swal.fire({
                title: 'Hapus Data Mesin?',
                text: "Data dan file lampiran akan hilang permanen!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'delete/delete_asset.php?id=' + id;
            });
        }

        function renderTable() {
            const tableBody = document.getElementById('tableAssetBody');
            const pageInfo = document.getElementById('pageInfo');
            const filteredRows = allRows.filter(row => row.textContent.toLowerCase().includes(currentSearchKeyword));
            
            const totalItems = filteredRows.length;
            const totalPages = Math.ceil(totalItems / rowsPerPage);
            if (currentPage > totalPages) currentPage = totalPages || 1;

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            // 1. Sembunyikan SEMUA baris (Master & Detail)
            const allTrs = tableBody.querySelectorAll('tr');
            allTrs.forEach(tr => tr.style.display = 'none');

            // 2. Tampilkan HANYA Master yang lolos filter
            filteredRows.slice(start, end).forEach(row => {
                row.style.display = '';
                // Jangan lupa sembunyikan detail row pasangannya (takutnya tadi terbuka)
                // const detailRow = document.getElementById('detail-' + row.dataset.id); // Opsional
            });

            // 3. Update Info Text
            if(pageInfo) {
                const startInfo = totalItems === 0 ? 0 : start + 1;
                const endInfo = Math.min(end, totalItems);
                pageInfo.innerText = `Showing ${startInfo} - ${endInfo} of ${totalItems} entries`;
            }

            renderPaginationButtons(totalPages);
        }

        function renderPaginationButtons(totalPages) {
            const container = document.getElementById('paginationControls');
            if (!container) return;
            container.innerHTML = "";
            if (totalPages <= 1) return;

            const createBtn = (text, page) => {
                const btn = document.createElement('button');
                btn.innerText = text;
                const isActive = (page === currentPage);
                btn.className = `px-3 py-1 rounded transition text-xs ${isActive ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'}`;
                btn.onclick = () => { currentPage = page; renderTable(); };
                return btn;
            };

            if (currentPage > 1) container.appendChild(createBtn("Prev", currentPage - 1));
            
            // Logic simple: Tampilkan 5 halaman di sekitar current page
            let startP = Math.max(1, currentPage - 2);
            let endP = Math.min(totalPages, currentPage + 2);
            for (let i = startP; i <= endP; i++) {
                container.appendChild(createBtn(i, i));
            }

            if (currentPage < totalPages) container.appendChild(createBtn("Next", currentPage + 1));
        }

        // --- F. FUNGSI DOWNLOAD EXCEL ---
        function downloadExcel() {
            const searchValue = document.getElementById('searchInput').value;
            window.location.href = 'export/export_excel.php?search=' + encodeURIComponent(searchValue);
        }
    </script>

    </body>
</html>