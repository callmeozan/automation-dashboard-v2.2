<?php
// 1. Panggil Satpam & Koneksi Global (Sudah handle session, teamList, & notifikasi)
include 'layouts/auth_and_config.php';

// 2. LOGIC DATA GANTT CHART (Pertahankan karena spesifik halaman ini)
$qChart = mysqli_query($conn, "SELECT * FROM tb_projects ORDER BY due_date ASC");
$ganttData = [];
while ($r = mysqli_fetch_assoc($qChart)) {
    $start = !empty($r['created_at']) ? strtotime($r['created_at']) : strtotime($r['due_date'] . ' -7 days');
    $end   = strtotime($r['due_date']);
    if ($start > $end) $start = $end - (86400 * 3);
    $color = ($r['status'] == 'Done') ? '#10b981' : (($r['status'] == 'In Progress') ? '#3b82f6' : '#94a3b8');
    if ($r['status'] != 'Done' && time() > $end) $color = '#ef4444';

    $ganttData[] = [
        'x' => $r['project_name'],
        'y' => [$start * 1000, $end * 1000],
        'fillColor' => $color,
        'meta' => $r['status'] . ' (' . $r['activity'] . ')'
    ];
}
$jsonGantt = json_encode($ganttData);

// 3. KONFIGURASI LAYOUT
$pageTitle = "Project Timeline";

// [SLOT HEADER] Tombol New Project (Hanya Admin/Section)
$extraMenu = '';
if ($role_user == 'admin' || $role_user == 'section') {
    $extraMenu = '
        <button onclick="openModal(\'modalProject\')" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-1.5 rounded-full text-sm font-medium transition shadow-lg shadow-indigo-600/20 flex items-center gap-2">
            <i class="fas fa-plus"></i> <span class="hidden sm:inline">New Project</span>
        </button>';
}

// [SLOT HEAD] CSS Spesifik Project (TomSelect & Animasi)
$extraHead = '
    <script src="assets/vendor/apexcharts.js"></script>
    <link href="assets/vendor/tom-select.css" rel="stylesheet">
    <script src="assets/vendor/tom-select.complete.min.js"></script>
    <style>
        .ts-control { background-color: #0f172a !important; border: 1px solid #334155 !important; color: #fff !important; border-radius: 0.5rem; }
        .ts-dropdown { background-color: #1e293b !important; border: 1px solid #334155 !important; color: #fff !important; }
        .project-menu { transform-origin: top right; transition: all 0.1s ease-out; }
        input[type="date"] { color-scheme: dark; }
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

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script src="assets/js/ui-sidebar.js"></script>
    <script src="assets/js/ui-modal.js"></script>

    <script>
        // 1. VARIABEL GLOBAL TOMSELECT
        var tomSelectCreate, tomSelectEdit;

        // document.addEventListener('DOMContentLoaded', function() {
        // document.addEventListener('turbo:load', function() {
        (function() {
        if (document.documentElement.hasAttribute("data-turbo-preview")) return;

        // 2. PROTEKSI HALAMAN
        if (!window.location.pathname.includes('project.php')) return;

            // --- A. INISIALISASI TOM SELECT ---
            if (document.getElementById('create_team')) {
                tomSelectCreate = new TomSelect("#create_team", { plugins: ['remove_button'], create: false, maxItems: 5 });
            }
            if (document.getElementById('edit_team')) {
                tomSelectEdit = new TomSelect("#edit_team", { plugins: ['remove_button'], create: false, maxItems: 5 });
            }

            // --- B. INIT WARNA SLIDER SAAT LOAD ---
            const sliders = document.querySelectorAll('input[type="range"]');
            sliders.forEach(s => {
                const val = parseInt(s.value);
                s.classList.remove('accent-red-500', 'accent-blue-500', 'accent-emerald-500');
                if (val < 25) s.classList.add('accent-red-500');
                else if (val > 75) s.classList.add('accent-emerald-500');
                else s.classList.add('accent-blue-500');
            });
        })();

           // Tutup menu saat klik di luar
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.fa-ellipsis-h') && !e.target.closest('button[onclick*="toggleProjectMenu"]')) {
                document.querySelectorAll('.project-menu').forEach(el => el.classList.add('hidden'));
            }
        });

        // --- 2. LOGIKA MENU KANBAN (TITIK TIGA) ---
        function toggleProjectMenu(menuId) {
            document.querySelectorAll('.project-menu').forEach(el => {
                if (el.id !== menuId) el.classList.add('hidden');
            });
            const menu = document.getElementById(menuId);
            if (menu) menu.classList.toggle('hidden');
        }

        // --- 3. FUNGSI EDIT PROJECT ---
        function editProject(id, name, desc, date, cat, team, act, plant, status) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name.split("_ENTER_").join("\n");
            document.getElementById('edit_desc').value = desc.split("_ENTER_").join("\n");
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_act').value = act.split("_ENTER_").join(" ");
            document.getElementById('edit_plant').value = plant;

            if (document.getElementById('edit_status')) document.getElementById('edit_status').value = status;

            // Load data Team ke TomSelect
            if (tomSelectEdit) {
                tomSelectEdit.clear();
                if (team) {
                    const members = team.split(',').map(item => item.trim());
                    members.forEach(m => tomSelectEdit.addItem(m));
                }
            }
            openModal('modalEditProject');
        }

        // --- 4. FUNGSI HAPUS PROJECT ---
        function confirmDeleteProject(id) {
            document.querySelectorAll('.project-menu').forEach(el => el.classList.add('hidden'));
            Swal.fire({
                title: 'Hapus Project?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                background: '#1e293b', color: '#fff',
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'delete/delete_project.php?id=' + id + '&redirect=project.php';
                }
            });
        }

        // --- 5. LOGIKA SLIDER PROGRESS (AJAX) ---
        function updateSliderUI(slider, id) {
            const val = parseInt(slider.value);
            const label = document.getElementById('prog-val-' + id);
            if (label) label.innerText = val + '%';

            let color = '#3b82f6'; 
            let accentClass = 'accent-blue-500';

            if (val < 25) { color = '#ef4444'; accentClass = 'accent-red-500'; }
            else if (val > 75) { color = '#10b981'; accentClass = 'accent-emerald-500'; }

            slider.style.background = `linear-gradient(to right, ${color} 0%, ${color} ${val}%, #334155 ${val}%, #334155 100%)`;
            slider.className = `w-full h-1.5 rounded-lg appearance-none cursor-pointer transition-all ${accentClass}`;
        }

        function saveProgress(val, id) {
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false,
                timer: 1000, timerProgressBar: true
            });

            const formData = new FormData();
            formData.append('project_id', id);
            formData.append('progress', val);

            fetch('process/update_progress.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Toast.fire({ icon: 'success', title: 'Progress Disimpan' });
                    if (val == 100) {
                        Swal.fire({
                            title: 'Project Selesai?', text: "Progress sudah 100%. Ubah status ke DONE?",
                            icon: 'question', showCancelButton: true,
                            confirmButtonText: 'Ya!', background: '#1e293b', color: '#fff'
                        });
                    }
                }
            });
        }
    </script>
</body>

</html>