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
$qData = mysqli_query($conn, "SELECT * FROM tb_manuals ORDER BY plant ASC, area ASC, machine_name ASC");
$tree = [];
$totalManuals = 0;

while ($row = mysqli_fetch_assoc($qData)) {
    $tree[$row['plant']][$row['area']][] = $row;
    $totalManuals++;
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
                        <?php foreach ($tree as $plantName => $areas): ?>
                            <div class="plant-group bg-slate-800/40 border border-slate-700/80 rounded-xl md:rounded-2xl p-3.5 md:p-6 shadow-xl space-y-4 md:space-y-6">
                                <!-- Plant Header -->
                                <div class="flex items-center justify-between border-b border-slate-700/60 pb-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 md:w-8 md:h-8 rounded-lg bg-cyan-600/20 border border-cyan-500 flex items-center justify-center text-cyan-400 text-xs md:text-sm shrink-0">
                                            <i class="fas fa-industry"></i>
                                        </div>
                                        <h2 class="text-sm md:text-base font-bold text-white tracking-wide truncate"><?php echo htmlspecialchars($plantName); ?></h2>
                                    </div>

                                    <button onclick="openModalWithPreset('<?php echo htmlspecialchars(addslashes($plantName)); ?>', '')" class="text-[11px] bg-slate-800 hover:bg-cyan-600 text-slate-300 hover:text-white px-2.5 py-1 rounded-md border border-slate-700 hover:border-cyan-500 transition flex items-center gap-1 shrink-0">
                                        <i class="fas fa-plus text-[9px]"></i> <span>Add</span>
                                    </button>
                                </div>

                                <!-- Sub Group: Areas -->
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
                                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                                <?php foreach ($manualList as $item): 
                                                    $safe_title   = addslashes(str_replace(array("\r\n", "\r", "\n"), ' ', $item['manual_title']));
                                                    $safe_machine = addslashes(str_replace(array("\r\n", "\r", "\n"), ' ', $item['machine_name']));
                                                    $safe_plant   = addslashes($item['plant']);
                                                    $safe_area    = addslashes($item['area']);
                                                ?>
                                                    <div class="manual-item relative bg-slate-900/95 hover:bg-slate-800/90 border border-slate-800 hover:border-cyan-500/50 rounded-2xl p-5 flex flex-col justify-between transition-all duration-300 shadow-xl hover:shadow-cyan-500/10 hover:-translate-y-1 group overflow-hidden">
                                                        <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-cyan-500 via-emerald-500 to-indigo-500 opacity-60 group-hover:opacity-100 transition-opacity"></div>
                                                        <!-- AKSEN GARIS BUSUR MELINGKAR (RADAR / BLUEPRINT RINGS) -->
                                                        <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full border border-slate-700/30 border-dashed pointer-events-none group-hover:border-cyan-500/20 transition-colors duration-500"></div>
                                                        <div class="absolute -top-6 -right-6 w-32 h-32 rounded-full border border-slate-700/40 pointer-events-none group-hover:border-cyan-500/30 transition-colors duration-500"></div>
                                                        <div class="absolute top-0 right-0 w-16 h-16 rounded-full border border-slate-700/20 pointer-events-none group-hover:border-cyan-500/40 transition-colors duration-500"></div>

                                                        <!-- KONTEN UTAMA KARTU -->
                                                        <div class="relative z-10">
                                                            <!-- Header Kartu: Badge Mesin & Tombol Aksi Admin -->
                                                            <div class="flex justify-between items-center mb-3">
                                                                <span class="font-mono text-xs font-bold text-cyan-400 bg-cyan-950/80 border border-cyan-500/30 px-2.5 py-1 rounded-md tracking-wide">
                                                                    <?php echo htmlspecialchars($item['machine_name']); ?>
                                                                </span>

                                                                
                                                                    <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition">
                                                                        <!-- TOMBOL EDIT -->
                                                                        <button onclick="openEditManual('<?php echo $item['manual_id']; ?>', '<?php echo htmlspecialchars($safe_plant, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_area, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_machine, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($safe_title, ENT_QUOTES); ?>')" 
                                                                                class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-cyan-300 hover:bg-slate-800 transition" 
                                                                                title="Edit Manual">
                                                                            <i class="fas fa-pen text-xs"></i>
                                                                        </button> 

                                                                        <!-- TOMBOL DELETE -->
                                                                        <button onclick="confirmDeleteManual('<?php echo $item['manual_id']; ?>')" 
                                                                                class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-400 hover:bg-slate-800 transition" 
                                                                                title="Delete Manual">
                                                                            <i class="fas fa-trash-alt text-xs"></i>
                                                                        </button>
                                                                    </div>
                                                            
                                                            </div>

                                                            <!-- Judul Manual Book -->
                                                            <h4 class="text-sm font-semibold text-white group-hover:text-cyan-300 transition-colors line-clamp-2 leading-snug pr-6" title="<?php echo htmlspecialchars($item['manual_title']); ?>">
                                                                <?php echo htmlspecialchars($item['manual_title']); ?>
                                                            </h4>
                                                        </div>

                                                        <!-- Footer Kartu: Indikator & Tombol Baca -->
                                                        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between mt-5 relative z-10">
                                                            <span class="text-[11px] text-slate-500 font-medium">PDF Document</span>

                                                            <button onclick="previewPdfModal('uploads/manuals/<?php echo urlencode($item['file_pdf']); ?>', '<?php echo htmlspecialchars(addslashes($item['manual_title'])); ?>')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-600/10 hover:bg-red-600 border border-cyan-500/30 rounded-lg text-cyan-400 hover:text-white text-xs font-semibold transition-all active:scale-95 shadow-sm">
                                                                <i class="fas fa-book-open text-[11px]"></i>
                                                                <span>View Manual</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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

                <form action="user_manual.php" method="POST" enctype="multipart/form-data" class="space-y-4" data-turbo="false">
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

                <form action="process/process_edit_manual.php" method="POST" enctype="multipart/form-data" class="space-y-4" data-turbo="false">
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
                            <option value="RUANG AUTOMATION">MARKAS BESAR</option>
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

        // 3. Delete Confirmation
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
                            // Redirect langsung ke file process di folder process/
                            window.location.href = `process/process_delete_manual.php?id=${id}`;
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