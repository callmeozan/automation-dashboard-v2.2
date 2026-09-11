<?php
// 1. Auth & Global Database Connection
include 'layouts/auth_and_config.php';

// Target directory for uploaded PDF files
$targetDir = 'uploads/manuals/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// 2. Action Handlers (Create, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- A. CREATE MANUAL ---
    if ($action === 'create') {
        $plant = mysqli_real_escape_string($conn, $_POST['plant']);
        $area = mysqli_real_escape_string($conn, $_POST['area']);
        $machine_name = mysqli_real_escape_string($conn, $_POST['machine_name']);
        $manual_title = mysqli_real_escape_string($conn, $_POST['manual_title']);

        if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file_pdf']['tmp_name'];
            $fileName = $_FILES['file_pdf']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileExtension === 'pdf') {
                // Bersihkan karakter aneh pada judul, spasi diubah jadi underscore (_)
                $cleanTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $manual_title);
                // Hilangkan dobel underscore jika ada
                $cleanTitle = preg_replace('/_+/', '_', trim($cleanTitle, '_'));

                $newFileName = 'MANUAL_' . $cleanTitle . '_' . time() . '.pdf';
                $destPath = $targetDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $qInsert = "INSERT INTO tb_manuals (plant, area, machine_name, manual_title, file_pdf) 
                                VALUES ('$plant', '$area', '$machine_name', '$manual_title', '$newFileName')";
                    mysqli_query($conn, $qInsert);
                    header("Location: user_manual.php?status=created");
                    exit;
                }
            }
        }
    }

    // --- B. EDIT MANUAL ---
    if ($action === 'edit') {
        $id = (int)$_POST['manual_id'];
        $plant = mysqli_real_escape_string($conn, $_POST['plant']);
        $area = mysqli_real_escape_string($conn, $_POST['area']);
        $machine_name = mysqli_real_escape_string($conn, $_POST['machine_name']);
        $manual_title = mysqli_real_escape_string($conn, $_POST['manual_title']);

        if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file_pdf']['tmp_name'];
            $fileName = $_FILES['file_pdf']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileExtension === 'pdf') {
                $cleanTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $manual_title);
                $cleanTitle = preg_replace('/_+/', '_', trim($cleanTitle, '_'));
                $newFileName = 'MANUAL_' . $cleanTitle . '_' . time() . '.pdf';
                $destPath = $targetDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $resOld = mysqli_query($conn, "SELECT file_pdf FROM tb_manuals WHERE manual_id = $id");
                    if ($rOld = mysqli_fetch_assoc($resOld)) {
                        @unlink($targetDir . $rOld['file_pdf']);
                    }
                    mysqli_query($conn, "UPDATE tb_manuals SET plant='$plant', area='$area', machine_name='$machine_name', manual_title='$manual_title', file_pdf='$newFileName' WHERE manual_id=$id");
                }
            }
        } else {
            mysqli_query($conn, "UPDATE tb_manuals SET plant='$plant', area='$area', machine_name='$machine_name', manual_title='$manual_title' WHERE manual_id=$id");
        }
        header("Location: user_manual.php?status=updated");
        exit;
    }

    // --- C. DELETE MANUAL ---
    if ($action === 'delete') {
        $id = (int)$_POST['manual_id'];
        $resOld = mysqli_query($conn, "SELECT file_pdf FROM tb_manuals WHERE manual_id = $id");
        if ($rOld = mysqli_fetch_assoc($resOld)) {
            @unlink($targetDir . $rOld['file_pdf']);
        }
        mysqli_query($conn, "DELETE FROM tb_manuals WHERE manual_id = $id");
        header("Location: user_manual.php?status=deleted");
        exit;
    }
}

// 3. Fetch & Group: PLANT -> Area -> Manual Items
$query = mysqli_query($conn, "SELECT * FROM tb_manuals ORDER BY plant ASC, area ASC, machine_name ASC, manual_title ASC");
$tree = [];
$totalManuals = 0;

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $plant   = $row['plant'];
        $area    = $row['area'];
        $machine = $row['machine_name'];
        $tree[$plant][$area][$machine][] = $row;
        $totalManuals++;
    }
}

$pageTitle = "User Manual Books";

$extraHead = '
    <style>
        #modalManual:not(.hidden)>div:last-child>div,
        #modalEditManual:not(.hidden)>div:last-child>div {
            animation: popUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes popUp {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
';
?>
<head>
    <meta name="turbo-cache-control" content="no-preview">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<!DOCTYPE html>
<html lang="en">
<?php include 'layouts/head.php'; ?>

<body class="bg-slate-900 text-slate-200 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <?php include 'layouts/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-y-auto relative pb-24" id="main-content">
            <?php include 'layouts/header.php'; ?>

            <div class="p-4 md:p-8 space-y-4 md:space-y-6 pb-28 fade-in">
                <div class="flex border-b border-slate-700 mb-6">
                    <a href="database.php" class="px-6 py-3 text-sm font-medium text-slate-400 hover:text-white hover:border-slate-500 border-b-2 border-transparent transition">
                        <i class="fas fa-microchip mr-2"></i> Machine & Assets
                    </a>
                    <a href="master_items.php" class="px-6 py-3 text-sm font-medium text-slate-400 hover:text-white hover:border-slate-500 border-b-2 border-transparent transition">
                        <i class="fas fa-box mr-2"></i> Master Items
                    </a>
                    <a href="user_manual.php" class="px-6 py-3 text-sm font-bold text-cyan-400 border-b-2 border-cyan-400">
                        <i class="fas fa-book mr-2"></i> User Manual
                    </a>
                </div>

            <div class="p-8 space-y-6">
                <!-- Top Actions Bar -->
                <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                    <div class="relative flex-1 w-full">
                        <i class="fas fa-search absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                        <input type="text" id="manualSearchInput" placeholder="Search machine, title, area..." class="w-full bg-slate-800 border border-slate-700 text-white pl-9 pr-3 py-2.5 rounded-lg focus:border-cyan-500 focus:outline-none transition text-xs md:text-sm">
                    </div>

                    <div class="flex items-center gap-2 justify-between sm:justify-end">
                        <div class="text-[11px] text-slate-400 border-r border-slate-700 pr-3">
                            Total: <span class="text-cyan-400 font-bold"><?php echo $totalManuals; ?></span>
                        </div>
                        <button onclick="openModal('modalManual')" class="bg-cyan-600 hover:bg-cyan-500 text-white px-3 py-2 rounded-lg text-xs font-medium transition shadow-md shadow-cyan-600/20 flex items-center gap-1.5 shrink-0">
                            <i class="fas fa-plus text-[10px]"></i> <span>Add Manual</span>
                        </button>
                    </div>
                </div>

                <!-- Hierarchical Card Container -->
                <div id="manualContainer" class="space-y-8">
                    <?php if (empty($tree)): ?>
                        <div class="bg-slate-800/50 border border-slate-700 border-dashed rounded-xl p-12 text-center">
                            <i class="fas fa-book-open text-4xl text-slate-600 mb-3"></i>
                            <p class="text-slate-400 font-medium">No machine user manuals available.</p>
                            <p class="text-xs text-slate-500 mt-1">Click the "+ Add Manual" button above to upload a new PDF document.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $plantIndex = 0;
                        foreach ($tree as $plantName => $areas): 
                            $plantIndex++;
                            
                            // Inisialisasi awal variabel agar tidak pernah bernilai undefined/null
                            $plantManualCount = 0;
                            $machinePreviews  = [];

                            // Kumpulkan data mesin & hitung total manual
                            if (!empty($areas) && is_array($areas)) {
                                foreach ($areas as $areaKey => $areaVal) {
                                    if (is_array($areaVal)) {
                                        foreach ($areaVal as $machineKey => $docVal) {
                                            if (is_array($docVal)) {
                                                // Jika struktur 3 level: [Plant][Area][Machine][]
                                                $machineName = is_string($machineKey) ? $machineKey : ($docVal['machine_name'] ?? '');
                                                if (!empty($machineName) && !in_array($machineName, $machinePreviews)) {
                                                    $machinePreviews[] = $machineName;
                                                }
                                                $plantManualCount += count($docVal);
                                            } else {
                                                // Antisipasi jika struktur masih 2 level: [Plant][Area][]
                                                $mName = $areaVal['machine_name'] ?? '';
                                                if (!empty($mName) && !in_array($mName, $machinePreviews)) {
                                                    $machinePreviews[] = $mName;
                                                }
                                                $plantManualCount++;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            $plantCollapseId = "plant-content-" . $plantIndex;
                            $plantArrowId    = "plant-arrow-" . $plantIndex;
                        ?>
                            <div class="plant-group bg-slate-800/40 hover:bg-slate-800/60 border border-slate-700/70 hover:border-cyan-500/40 rounded-xl md:rounded-2xl p-4 md:p-5 shadow-lg hover:shadow-cyan-500/5 transition-all duration-200 space-y-4">
                                
                                <!-- Header Plant yang Kaya Informasi -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer select-none group" 
                                    onclick="togglePlantCollapse('<?php echo $plantCollapseId; ?>', '<?php echo $plantArrowId; ?>')">
                                    
                                    <!-- Kiri: Panah, Icon Pabrik, Nama Plant, & Preview Tags Mesin -->
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <!-- Panah Accordion -->
                                        <div class="w-6 h-6 rounded-md bg-slate-800 border border-slate-700 group-hover:border-cyan-500/50 flex items-center justify-center text-slate-400 group-hover:text-cyan-400 shrink-0 transition-colors">
                                            <i class="fas fa-chevron-right text-[11px] transition-transform duration-300 transform rotate-90" id="<?php echo $plantArrowId; ?>"></i>
                                        </div>

                                        <!-- Icon Pabrik -->
                                        <div class="w-8 h-8 rounded-lg bg-cyan-950/80 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-xs shrink-0 shadow-sm">
                                            <i class="fas fa-industry"></i>
                                        </div>
                                        
                                        <!-- Nama Plant & Tags Mesin Mini -->
                                        <div class="flex items-center gap-2.5 flex-wrap min-w-0">
                                            <h2 class="text-sm md:text-base font-bold text-white tracking-wide group-hover:text-cyan-300 transition-colors">
                                                <?php echo htmlspecialchars($plantName); ?>
                                            </h2>

                                            <!-- Preview Badge Mesin (Hanya muncul jika ada mesin) -->
                                            <div class="hidden lg:flex items-center gap-1.5 ml-2">
                                                <?php 
                                                $previewLimit = 3;
                                                $safePreviews = is_array($machinePreviews) ? $machinePreviews : [];
                                                $displayMachines = array_slice($safePreviews, 0, $previewLimit);
                                                foreach ($displayMachines as $mName): 
                                                ?>
                                                    <span class="text-[10px] font-mono text-slate-400 bg-slate-900/90 border border-slate-700/80 px-2 py-0.5 rounded">
                                                        <?php echo htmlspecialchars($mName); ?>
                                                    </span>
                                                <?php endforeach; ?>

                                                <?php if (count($machinePreviews) > $previewLimit): ?>
                                                    <span class="text-[10px] text-slate-500 bg-slate-900/50 px-1.5 py-0.5 rounded border border-slate-800">
                                                        +<?php echo count($machinePreviews) - $previewLimit; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kanan: Badge Total & Tombol Aksi -->
                                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                                        <!-- Badge Total Manual -->
                                        <div class="flex items-center gap-1.5 text-xs bg-slate-900/90 border border-slate-700/80 px-2.5 py-1 rounded-lg">
                                            <i class="fas fa-file-pdf text-rose-400 text-[11px]"></i>
                                            <span class="text-slate-300 font-medium">
                                                <strong class="text-cyan-400"><?php echo $plantManualCount; ?></strong> Docs
                                            </span>
                                        </div>

                                        <!-- Tombol Add -->
                                        <button onclick="event.stopPropagation(); openModalWithPreset('<?php echo htmlspecialchars(addslashes($plantName)); ?>', '')" 
                                                class="text-xs bg-slate-800 hover:bg-cyan-600 text-slate-300 hover:text-white px-3 py-1 rounded-lg border border-slate-700 hover:border-cyan-500 transition flex items-center gap-1.5 shadow-sm">
                                            <i class="fas fa-plus text-[10px]"></i> <span>Add</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Kontainer Isi (Area & Grid Mesin) -->
                                <div id="<?php echo $plantCollapseId; ?>" class="transition-all duration-300 space-y-6 pt-2">
                                    <div class="grid grid-cols-1 gap-6">
                                        <?php foreach ($areas as $areaName => $manualList): ?>
                                            <div class="area-group bg-slate-900/60 border border-slate-800 rounded-lg md:rounded-xl p-3 md:p-4 space-y-3">
                                                <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                                                    <div class="flex items-center gap-1.5 text-[11px] font-semibold text-emerald-400 uppercase tracking-wider truncate">
                                                        <i class="fas fa-map-marker-alt text-[10px]"></i>
                                                        <span class="truncate"><?php echo htmlspecialchars($areaName); ?></span>
                                                    </div>

                                                    <button onclick="openModalWithPreset('<?php echo htmlspecialchars(addslashes($plantName)); ?>', '<?php echo htmlspecialchars(addslashes($areaName)); ?>')" class="text-[10px] text-slate-400 hover:text-cyan-400 bg-slate-950 border border-slate-800 px-2 py-0.5 rounded transition flex items-center gap-1 shrink-0">
                                                        <i class="fas fa-plus text-[8px]"></i> <span>Add Machine</span>
                                                    </button>
                                                </div>

                                                <!-- Machines & Manuals Grid -->
                                                <!-- Machines Grid (Tampilan Estetik Blueprint + Grouping Mesin) -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mt-4">
                                                    <?php foreach ($manualList as $machineName => $docs): ?>
                                                        <div class="manual-item relative bg-slate-900/95 hover:bg-slate-800/90 border border-slate-800 hover:border-cyan-500/50 rounded-2xl p-5 flex flex-col justify-between transition-all duration-300 shadow-xl hover:shadow-cyan-500/10 hover:-translate-y-1 group overflow-hidden">
                                                            
                                                            <!-- 1. Garis Gradien Glowing di Atas Kartu -->
                                                            <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-cyan-500 via-emerald-500 to-indigo-500 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                                                            <!-- 2. Aksen Lingkaran Blueprint / Radar -->
                                                            <div class="absolute -top-12 -right-12 w-44 h-44 rounded-full border border-slate-700/30 border-dashed pointer-events-none group-hover:border-cyan-500/20 transition-colors duration-500"></div>
                                                            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full border border-slate-700/40 pointer-events-none group-hover:border-cyan-500/30 transition-colors duration-500"></div>

                                                            <!-- Konten Kartu -->
                                                            <div class="relative z-0">
                                                                <!-- Header Mesin & Badge Dokumen -->
                                                                <div class="flex justify-between items-center mb-3">
                                                                    <span class="font-mono text-xs font-bold text-cyan-400 bg-cyan-950/80 border border-cyan-500/30 px-2.5 py-1 rounded-md tracking-wide">
                                                                        <?php echo htmlspecialchars($machineName); ?>
                                                                    </span>

                                                                    <div class="flex items-center gap-2">
                                                                        <span class="text-[11px] font-medium bg-slate-800 text-slate-400 px-2 py-0.5 rounded border border-slate-700/80">
                                                                            <strong class="text-emerald-400"><?php echo count($docs); ?></strong> Docs
                                                                        </span>
                                                                        
                                                                        <!-- Tambah manual baru khusus mesin ini -->
                                                                        <button onclick="openModalWithPreset('<?php echo htmlspecialchars(addslashes($plantName)); ?>', '<?php echo htmlspecialchars(addslashes($areaName)); ?>', '<?php echo htmlspecialchars(addslashes($machineName)); ?>')" 
                                                                                class="w-6 h-6 flex items-center justify-center rounded bg-slate-800 hover:bg-cyan-600 text-slate-400 hover:text-white transition text-[10px]" 
                                                                                title="Tambah Dokumen Baru">
                                                                            <i class="fas fa-plus"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <!-- Daftar Judul Dokumen (Clean & Readable) -->
                                                                <div class="space-y-2 my-4">
                                                                    <?php foreach ($docs as $item): 
                                                                        $safe_title   = addslashes(str_replace(array("\r\n", "\r", "\n"), ' ', $item['manual_title']));
                                                                        $safe_machine = addslashes(str_replace(array("\r\n", "\r", "\n"), ' ', $item['machine_name']));
                                                                        $safe_plant   = addslashes($item['plant']);
                                                                        $safe_area    = addslashes($item['area']);
                                                                    ?>
                                                                        <!-- <div class="p-2.5 bg-slate-950/70 rounded-xl border border-slate-800 hover:border-cyan-500/40 flex items-center justify-between gap-3 group/doc transition">
                                                                            <div class="min-w-0 flex-1">
                                                                                <h4 class="text-xs font-medium text-slate-200 group-hover/doc:text-cyan-300 truncate transition-colors" title="<?php echo htmlspecialchars($item['manual_title']); ?>">
                                                                                    <i class="fas fa-file-pdf text-rose-400 mr-1.5 text-[11px]"></i>
                                                                                    <?php echo htmlspecialchars($item['manual_title']); ?>
                                                                                </h4>
                                                                            </div> -->
                                                                            <div class="p-2.5 bg-slate-950/70 rounded-xl border border-slate-800 hover:border-cyan-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-2 group/doc transition">
                                                                            <div class="min-w-0 flex-1">
                                                                                <h4 class="text-xs font-medium text-slate-200 group-hover/doc:text-cyan-300 line-clamp-2 leading-relaxed" title="<?php echo htmlspecialchars($item['manual_title']); ?>">
                                                                                    <i class="fas fa-file-pdf text-rose-400 mr-1.5 text-[11px]"></i>
                                                                                    <?php echo htmlspecialchars($item['manual_title']); ?>
                                                                                </h4>
                                                                            </div>

                                                                            <!-- Tombol Baca & Aksi -->
                                                                            <!-- <div class="flex items-center gap-1 shrink-0">
                                                                                <button onclick="previewPdfModal('uploads/manuals/<?php echo urlencode($item['file_pdf']); ?>', '<?php echo htmlspecialchars(addslashes($item['manual_title'])); ?>')" 
                                                                                        class="px-2.5 py-1 bg-cyan-600/10 hover:bg-cyan-600 border border-cyan-500/30 rounded-lg text-cyan-400 hover:text-white text-[10px] font-semibold transition" 
                                                                                        title="Buka PDF">
                                                                                    <i class="fas fa-book-open mr-1"></i> View
                                                                                </button>

                                                                                <button onclick="openEditManual('<?php echo $item['manual_id']; ?>', '<?php echo htmlspecialchars($safe_plant, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_area, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_machine, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_title, ENT_QUOTES); ?>')" 
                                                                                        class="w-6 h-6 flex items-center justify-center rounded text-slate-400 hover:text-cyan-300 hover:bg-slate-800 transition text-[10px]" 
                                                                                        title="Edit Judul/File">
                                                                                    <i class="fas fa-pen"></i>
                                                                                </button>

                                                                                <button onclick="confirmDeleteManual('<?php echo $item['manual_id']; ?>')" 
                                                                                        class="w-6 h-6 flex items-center justify-center rounded text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition text-[10px]" 
                                                                                        title="Hapus Dokumen">
                                                                                    <i class="fas fa-trash-alt"></i>
                                                                                </button>
                                                                            </div> -->
                                                                            <div class="flex items-center justify-end gap-1.5 shrink-0 pt-1 sm:pt-0 border-t sm:border-t-0 border-slate-800/60">
                                                                                <button onclick="previewPdfModal('uploads/manuals/<?php echo urlencode($item['file_pdf']); ?>', '<?php echo htmlspecialchars(addslashes($item['manual_title'])); ?>')" 
                                                                                        class="px-2.5 py-1 bg-cyan-600/10 hover:bg-cyan-600 border border-cyan-500/30 rounded-lg text-cyan-400 hover:text-white text-[10px] font-semibold transition" 
                                                                                        title="Buka PDF">
                                                                                    <i class="fas fa-book-open mr-1"></i> View
                                                                                </button>
                                                                                <button onclick="openEditManual('<?php echo $item['manual_id']; ?>', '<?php echo htmlspecialchars($safe_plant, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_area, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_machine, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_title, ENT_QUOTES); ?>')" 
                                                                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-cyan-300 hover:bg-slate-800 transition text-[11px]" 
                                                                                        title="Edit">
                                                                                    <i class="fas fa-pen"></i>
                                                                                </button>
                                                                                <button onclick="confirmDeleteManual('<?php echo $item['manual_id']; ?>')" 
                                                                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition text-[11px]" 
                                                                                        title="Hapus">
                                                                                    <i class="fas fa-trash-alt"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Footer: Indikator Format -->
                                                            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500 relative z-0">
                                                                <span>PDF Documents</span>
                                                                <span class="text-cyan-400 font-mono text-[10px]">Active</span>
                                                            </div>

                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div> <!-- Akhir Plant Collapse -->
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Create Manual -->
    <div id="modalManual" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalManual')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-xl shadow-2xl p-6 relative">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-file-upload text-cyan-400"></i> Add New User Manual
                    </h3>
                    <button onclick="closeModal('modalManual')" class="text-slate-400 hover:text-red-400 transition"><i class="fas fa-times text-xl"></i></button>
                </div>

                <form id="formAddManual" action="user_manual.php" method="POST" enctype="multipart/form-data" class="space-y-4" data-turbo="false">
                    <input type="hidden" name="action" value="create">

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">1. Plant</label>
                        <select name="plant" id="create_plant" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                            <option value="">-- Select Plant --</option>
                            <option value="PLANT TBR">PLANT TBR</option>
                            <option value="PLANT A">PLANT A</option>
                            <option value="PLANT BCHIT">PLANT BCHIT</option>
                            <option value="PLANT D/K">PLANT D/K</option>
                            <option value="PLANT E">PLANT E</option>
                            <option value="PLANT MIXING">PLANT MIXING</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">2. Area</label>
                            <input type="text" name="area" id="create_area" placeholder="e.g. Material, Building" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">3. Machine Name / Code</label>
                            <input type="text" name="machine_name" placeholder="e.g. RTE-EX1" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">4. Manual Book Title</label>
                        <input type="text" name="manual_title" placeholder="e.g. Instruction Manual Extruder" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-2 font-medium">5. PDF File</label>
                        <label for="pdf_manual_create" class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-cyan-500 transition group">
                            <div class="flex flex-col items-center justify-center py-4">
                                <i class="fas fa-file-pdf text-2xl text-slate-500 mb-1 group-hover:text-red-400 transition"></i>
                                <p class="text-xs text-slate-400"><span class="font-semibold text-cyan-400">Click to upload</p>
                                <p id="pdf-create-preview" class="text-xs text-emerald-400 mt-1 font-medium hidden"></p>
                            </div>
                            <input id="pdf_manual_create" type="file" name="file_pdf" accept="application/pdf" required class="hidden" onchange="previewManualName(this, 'pdf-create-preview')" />
                        </label>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Maximum file size: <strong class="text-cyan-400">40 MB</strong> (.pdf). If larger, compress the document first.
                        </p>
                    </div>

                    <!-- Container Progress Bar (Default Sembunyi / Hidden) -->
                    <div id="uploadProgressContainer" class="hidden mt-4 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-cyan-400 font-medium flex items-center gap-1.5">
                                <i class="fas fa-spinner fa-spin text-[11px]"></i>
                                <span id="uploadStatusText">Mengunggah Dokumen...</span>
                            </span>
                            <span id="uploadPercentText" class="font-mono text-slate-300 font-bold">0%</span>
                        </div>
                        <!-- Track Bar -->
                        <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-700/60 p-0.5">
                            <div id="uploadProgressBar" 
                                class="bg-gradient-to-r from-cyan-500 to-emerald-400 h-full rounded-full transition-all duration-150 w-0">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modalManual')" class="flex-1 py-2.5 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-400 border border-transparent hover:border-red-500/30 rounded-lg text-sm transition">Cancel</button>
                        <button type="submit" class="flex-1 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg text-sm font-medium transition shadow-lg shadow-cyan-600/20">Save Manual</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Manual -->
    <div id="modalEditManual" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" onclick="closeModal('modalEditManual')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-lg rounded-xl shadow-2xl p-6 relative">
                <div class="flex justify-between items-center mb-6 border-b border-slate-800 pb-4">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-edit text-cyan-400"></i> Edit Machine Manual
                    </h3>
                    <button onclick="closeModal('modalEditManual')" class="text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form id="formEditManual" action="process/process_edit_manual.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="manual_id" id="edit_manual_id">

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">1. Plant</label>
                        <select name="plant" id="edit_plant" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                            <option value="PLANT TBR">PLANT TBR</option>
                            <option value="PLANT A">PLANT A</option>
                            <option value="PLANT BCHIT">PLANT BCHIT</option>
                            <option value="PLANT D/K">PLANT D/K</option>
                            <option value="PLANT E">PLANT E</option>
                            <option value="PLANT MIXING">PLANT MIXING</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">2. Area</label>
                            <input type="text" name="area" id="edit_area" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-medium">3. Machine Name / Code</label>
                            <input type="text" name="machine_name" id="edit_machine" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1 font-medium">4. Manual Book Title</label>
                        <input type="text" name="manual_title" id="edit_manual_title" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-2 font-medium">5. Replace PDF File (Optional)</label>
                        <label for="pdf_manual_edit" class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-cyan-500 transition group">
                            <div class="flex flex-col items-center justify-center py-4">
                                <i class="fas fa-file-pdf text-2xl text-slate-500 mb-1 group-hover:text-red-400 transition"></i>
                                <p class="text-xs text-slate-400"><span class="font-semibold text-cyan-400">Click to replace file</span> (PDF only)</p>
                                <p id="pdf-edit-preview" class="text-xs text-emerald-400 mt-1 font-medium hidden"></p>
                            </div>
                            <input id="pdf_manual_edit" type="file" name="file_pdf" accept="application/pdf" class="hidden" onchange="previewManualName(this, 'pdf-edit-preview')" />
                        </label>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Maximum file size: <strong class="text-cyan-400">40 MB</strong> (.pdf). If larger, compress the document first.
                        </p>
                    </div>

                    <!-- Container Progress Bar Khusus Modal Edit -->
                    <div id="uploadProgressContainerEdit" class="hidden mt-4 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-cyan-400 font-medium flex items-center gap-1.5">
                                <i class="fas fa-spinner fa-spin text-[11px]"></i>
                                <span id="uploadStatusTextEdit">Memperbarui Dokumen...</span>
                            </span>
                            <span id="uploadPercentTextEdit" class="font-mono text-slate-300 font-bold">0%</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-700/60 p-0.5">
                            <div id="uploadProgressBarEdit" 
                                class="bg-gradient-to-r from-cyan-500 to-emerald-400 h-full rounded-full transition-all duration-150 w-0">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3 border-t border-slate-800">
                        <button type="button" onclick="closeModal('modalEditManual')" class="flex-1 py-2.5 bg-slate-800 hover:bg-red-500/20 text-slate-300 hover:text-red-400 border border-transparent hover:border-red-500/30 rounded-lg text-sm transition">Cancel</button>
                        <button type="submit" class="flex-1 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-lg text-sm font-medium transition shadow-lg shadow-cyan-600/20">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- IN-APP PDF VIEWER MODAL -->
    <div id="modalPdfViewer" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="closeModal('modalPdfViewer')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-2 md:p-6">
            <div class="bg-slate-900 border border-slate-700 w-full max-w-5xl h-[92vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden relative">
                
                <!-- Viewer Topbar -->
                <div class="px-4 py-3 bg-slate-950 border-b border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-red-500/20 border border-red-500/40 flex items-center justify-center text-red-400 text-xs shrink-0">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <h3 id="pdfViewerTitle" class="text-xs md:text-sm font-bold text-white truncate">Manual Book Document</h3>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a id="pdfDownloadLink" href="#" target="_blank" download class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg text-xs transition border border-slate-700 flex items-center gap-1.5" title="Download / Open in New Tab">
                            <i class="fas fa-external-link-alt text-[10px]"></i>
                            <span class="hidden sm:inline">New Tab</span>
                        </a>
                        <button onclick="closeModal('modalPdfViewer')" class="w-8 h-8 flex items-center justify-center bg-slate-800 hover:bg-red-500/20 text-slate-400 hover:text-red-400 rounded-lg text-sm transition">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- PDF Embed Frame -->
                <div class="flex-1 bg-slate-950/60 relative overflow-hidden">
                    <iframe id="pdfViewerFrame" src="" class="w-full h-full border-0" allow="fullscreen"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Deletion -->
    <form id="formDeleteManual" action="user_manual.php" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="manual_id" id="delete_manual_id">
    </form>

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script>
        // 1. Modal Controller
        function openModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.remove('hidden');
        }

        function closeModal(id) {
            const m = document.getElementById(id);
            if (m) m.classList.add('hidden');
        }

        // 2. Edit Modal Population
        function openEditManual(id, plant, area, machine, title) {
            document.getElementById('edit_manual_id').value = id;
            document.getElementById('edit_plant').value = plant;
            document.getElementById('edit_area').value = area;
            document.getElementById('edit_machine').value = machine;
            document.getElementById('edit_manual_title').value = title;
            
            openModal('modalEditManual');
        }

        // 3. Delete Confirmation (Dengan Notifikasi Sukses)
        function confirmDeleteManual(id) {
            Swal.fire({
                title: 'Delete Manual Document?',
                text: 'The PDF file will be permanently removed from the server!',
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b',
                color: '#fff',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, Delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Security Verification',
                        text: 'Enter authorization password to delete:',
                        input: 'password',
                        inputPlaceholder: 'Enter password...',
                        showCancelButton: true,
                        background: '#1e293b',
                        color: '#fff',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#334155',
                        confirmButtonText: 'Confirm & Delete',
                        cancelButtonText: 'Cancel',
                        inputAttributes: {
                            autocapitalize: 'off',
                            autocorrect: 'off',
                            style: 'background-color: #0f172a; color: #fff; border: 1px solid #475569;'
                        },
                        preConfirm: (password) => {
                            if (!password) {
                                Swal.showValidationMessage('Password is required!');
                                return false;
                            }
                            const masterPassword = 'tanyapakteguh'; // Sesuaikan password Anda
                            if (password !== masterPassword) {
                                Swal.showValidationMessage('Invalid password! Access denied.');
                                return false;
                            }
                            return true;
                        }
                    }).then((passResult) => {
                        if (passResult.isConfirmed) {
                            // Tampilkan indikator proses menghapus sebentar
                            Swal.fire({
                                title: 'Deleting...',
                                text: 'Removing document from server...',
                                background: '#1e293b',
                                color: '#fff',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Eksekusi penghapusan di background
                            fetch(`process/process_delete_manual.php?id=${id}`)
                                .then(() => {
                                    // Munculkan notifikasi sukses SweetAlert
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: 'Manual document has been removed.',
                                        background: '#1e293b',
                                        color: '#fff',
                                        confirmButtonColor: '#0891b2',
                                        timer: 1500,
                                        showConfirmButton: false
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                })
                                .catch(() => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Failed!',
                                        text: 'Failed to delete manual document.',
                                        background: '#1e293b',
                                        color: '#fff',
                                        confirmButtonColor: '#ef4444'
                                    });
                                });
                        }
                    });
                }
            });
        }

        // 4. Preview Nama File di Dropzone Box
        function previewManualName(input, displayId) {
            const display = document.getElementById(displayId);
            if (display && input.files && input.files[0]) {
                const file = input.files[0];
                const maxBytes = 40 * 1024 * 1024; // Ambang batas 40 MB

                // Jika ukuran file melebihi 40 MB
                if (file.size > maxBytes) {
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(1);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'File Size Too Large!',
                        html: `The selected file is <b>${fileSizeMB} MB</b>.<br>The maximum server limit is <b>40 MB</b>.<br><br><span class="text-xs text-slate-400">Please compress your PDF document before uploading.</span>`,
                        background: '#1e293b',
                        color: '#fff',
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Understood'
                    });

                    // Kosongkan input agar file gagal tidak terkirim
                    input.value = '';
                    display.classList.add('hidden');
                    return;
                }

                display.textContent = "📄 " + file.name;
                display.classList.remove('hidden');
            } else if (display) {
                display.classList.add('hidden');
            }
        }

        function openModalWithPreset(plant, area) {
            // Reset preview file jika ada
            const prev = document.getElementById('pdf-create-preview');
            if (prev) prev.classList.add('hidden');

            // Set nilai awal jika diklik dari kontainer tertentu
            if (document.getElementById('create_plant')) {
                document.getElementById('create_plant').value = plant || '';
            }
            if (document.getElementById('create_area')) {
                document.getElementById('create_area').value = area || '';
            }

            openModal('modalManual');
        }

        function previewPdfModal(fileUrl, title) {
            const frame = document.getElementById('pdfViewerFrame');
            const titleEl = document.getElementById('pdfViewerTitle');
            const downloadLink = document.getElementById('pdfDownloadLink');

            if (frame && titleEl) {
                titleEl.textContent = title;
                // Buka file langsung via browser embedder
                frame.src = fileUrl + "#toolbar=1&navpanes=0";
                if (downloadLink) downloadLink.href = fileUrl;
                openModal('modalPdfViewer');
            }
        }

        function togglePlantCollapse(contentId, arrowId) {
            const content = document.getElementById(contentId);
            const arrow = document.getElementById(arrowId);
            
            if (content) {
                content.classList.toggle('hidden');
            }
            if (arrow) {
                // Rotasi ikon panah 90 derajat saat dibuka/ditutup
                arrow.classList.toggle('rotate-90');
            }
        }

        function initManualUploadBar() {
            const form = document.getElementById('formAddManual');
            if (!form || form.dataset.boundProgress) return;
            form.dataset.boundProgress = "true";

            form.addEventListener('submit', function (e) {
                const fileInput = document.getElementById('pdf_manual_create');

                // Pastikan berkas PDF telah dipilih
                if (!fileInput || fileInput.files.length === 0) {
                    Swal.fire('Please select a PDF file first!');
                    e.preventDefault();
                    return;
                }

                // Tahan submit default browser
                e.preventDefault();

                const formData = new FormData(form);
                const progressBox = document.getElementById('uploadProgressContainer');
                const progressBar = document.getElementById('uploadProgressBar');
                const percentText = document.getElementById('uploadPercentText');
                const statusText  = document.getElementById('uploadStatusText');
                const submitBtn   = form.querySelector('button[type="submit"]');

                // Tampilkan wadah progress bar seketika
                if (progressBox) progressBox.classList.remove('hidden');
                if (progressBar) progressBar.style.width = '10%';
                if (percentText) percentText.innerText = '10%';
                if (statusText)  statusText.innerText = 'Uploading Document...';

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.getAttribute('action') || 'user_manual.php', true);

                // Pantau progres data yang terkirim
                xhr.upload.onprogress = function (event) {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        const visualPercent = Math.max(percent, 15); // Nilai minimal agar bar langsung bergerak
                        if (progressBar) progressBar.style.width = visualPercent + '%';
                        if (percentText) percentText.innerText = visualPercent + '%';

                        if (percent >= 100 && statusText) {
                            statusText.innerText = 'Saving file to server...';
                        }
                    }
                };

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        if (progressBar) progressBar.style.width = '100%';
                        if (percentText) percentText.innerText = '100%';
                        if (statusText)  statusText.innerText = 'Upload Complete!';

                        // 1. Tutup modal form tambah manual
                        closeModal('modalManual');

                        // 2. Panggil SweetAlert Notification
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Document uploaded successfully.',
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#0891b2',
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            // Fallback jika CDN SweetAlert belum termuat
                            setTimeout(() => {
                                window.location.reload();
                            }, 600);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed!',
                                text: 'Failed to upload document. Please check the file size.',
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#ef4444'
                            });
                        } else {
                            alert('Failed to upload document.');
                        }
                        resetUploadState();
                    }
                };

                xhr.onerror = function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error!',
                        text: 'Failed to upload document. Please check your internet connection.',
                        background: '#0f172a',
                        color: '#f8fafc',
                        confirmButtonColor: '#ef4444'
                    });
                    resetUploadState();
                };

                function resetUploadState() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    if (progressBox) progressBox.classList.add('hidden');
                    if (progressBar) progressBar.style.width = '0%';
                }

                xhr.send(formData);
            });
        }

        function initManualEditBar() {
            const formEdit = document.getElementById('formEditManual');
            if (!formEdit || formEdit.dataset.boundEditProgress) return;
            formEdit.dataset.boundEditProgress = "true";

            formEdit.addEventListener('submit', function (e) {
                e.preventDefault();

                const fileInput = document.getElementById('pdf_manual_edit');
                const hasNewFile = fileInput && fileInput.files.length > 0;

                const formData = new FormData(formEdit);
                const progressBox = document.getElementById('uploadProgressContainerEdit');
                const progressBar = document.getElementById('uploadProgressBarEdit');
                const percentText = document.getElementById('uploadPercentTextEdit');
                const statusText  = document.getElementById('uploadStatusTextEdit');
                const submitBtn   = document.getElementById('btnSubmitEditManual') || formEdit.querySelector('button[type="submit"]');

                // Kunci tombol submit
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }

                // Tampilkan loading bar jika mengunggah file baru
                if (hasNewFile && progressBox) {
                    progressBox.classList.remove('hidden');
                    if (progressBar) progressBar.style.width = '10%';
                    if (percentText) percentText.innerText = '10%';
                    if (statusText)  statusText.innerText = 'Uploading file...';
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', formEdit.getAttribute('action') || 'process/process_edit_manual.php', true);

                if (hasNewFile) {
                    xhr.upload.onprogress = function (event) {
                        if (event.lengthComputable) {
                            const percent = Math.round((event.loaded / event.total) * 100);
                            const visualPercent = Math.max(percent, 15);
                            if (progressBar) progressBar.style.width = visualPercent + '%';
                            if (percentText) percentText.innerText = visualPercent + '%';

                            if (percent >= 100 && statusText) {
                                statusText.innerText = 'Saving changes to server...';
                            }
                        }
                    };
                }

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        if (hasNewFile) {
                            if (progressBar) progressBar.style.width = '100%';
                            if (percentText) percentText.innerText = '100%';
                        }

                        closeModal('modalEditManual');

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Manual book updated successfully.',
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#0891b2',
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed!',
                                text: 'Failed to update manual document data.',
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#ef4444'
                            });
                        } else {
                            alert('Failed to update manual document data.');
                        }
                        resetEditUi();
                    }
                };

                xhr.onerror = function () {
                    alert('Connection lost while updating file.');
                    resetEditUi();
                };

                function resetEditUi() {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    if (progressBox) progressBox.classList.add('hidden');
                    if (progressBar) progressBar.style.width = '0%';
                }

                xhr.send(formData);
            });
        }

        // Pasang pemanggilan fungsi di event listener
        document.addEventListener('DOMContentLoaded', function() {
            initManualUploadBar();
            initManualEditBar();
        });
        document.addEventListener('turbo:load', function() {
            initManualUploadBar();
            initManualEditBar();
        });

        // 5. Live Search Filter
        document.getElementById('manualSearchInput')?.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const manualItems = document.querySelectorAll('.manual-item');

            manualItems.forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });

            document.querySelectorAll('.area-group').forEach(area => {
                const visibleInArea = area.querySelectorAll('.manual-item:not([style*="display: none"])');
                area.style.display = visibleInArea.length > 0 ? '' : 'none';
            });

            document.querySelectorAll('.plant-group').forEach(plant => {
                const visibleInPlant = plant.querySelectorAll('.manual-item:not([style*="display: none"])');
                plant.style.display = visibleInPlant.length > 0 ? '' : 'none';
            });
        });
    </script>
</body>
</html>