<?php
// 1. Panggil Satpam & Koneksi Global
include 'layouts/auth_and_config.php';

// --- PERBAIKAN ERROR $my_role ---
// Kita ambil role dari session yang sudah divalidasi di layouts/auth_and_config.php
$my_role = $_SESSION['role'] ?? 'user'; 
$my_id   = $_SESSION['user_id'];
// --------------------------------

// --- 2. LOGIKA KHUSUS OVERTIME (CUTOFF 15-15) ---
$tgl_hari_ini = date('d');
if ($tgl_hari_ini > 15) {
    $periode_start = date('Y-m-16');
    $periode_end   = date('Y-m-15', strtotime('+1 month'));
} else {
    $periode_start = date('Y-m-16', strtotime('-1 month'));
    $periode_end   = date('Y-m-15');
}
$label_periode = date('d M', strtotime($periode_start)) . " - " . date('d M', strtotime($periode_end));

// Hitung Jam Approved periode ini
$qJam = mysqli_query($conn, "SELECT SUM(duration) as total_jam FROM tb_overtime WHERE user_id='$my_id' AND status='Approved' AND date_ot BETWEEN '$periode_start' AND '$periode_end'");
$dJam = mysqli_fetch_assoc($qJam);
$total_jam_saya = $dJam['total_jam'] ? floatval($dJam['total_jam']) : 0;

// Hitung Frekuensi & Pending (Tetap Pertahankan)
$qFreq = mysqli_query($conn, "SELECT COUNT(*) as total_freq FROM tb_overtime WHERE user_id='$my_id' AND status='Approved' AND date_ot BETWEEN '$periode_start' AND '$periode_end'");
$total_freq_saya = mysqli_fetch_assoc($qFreq)['total_freq'] ?? 0;

$qPend = mysqli_query($conn, "SELECT COUNT(*) as total_pending FROM tb_overtime WHERE user_id='$my_id' AND status='Pending'");
$total_pending_saya = mysqli_fetch_assoc($qPend)['total_pending'] ?? 0;

// --- 3. KONFIGURASI LAYOUT ---
$pageTitle = "Overtime Request";

// [SLOT HEADER] Tombol Request Overtime
$extraMenu = '
    <div class="flex items-center gap-3">
        <button onclick="openModal(\'modalOvertime\')" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-1.5 rounded-full text-sm font-medium transition shadow-lg shadow-cyan-600/20 flex items-center gap-2">
            <i class="fas fa-plus"></i> <span class="hidden sm:inline">Request Overtime</span>
        </button>
    </div>';

$extraHead = '
    <style>
        input[type="date"], input[type="time"] { color-scheme: dark; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
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

        <!-- SIDEBAR ADA DISINI -->
         <?php include 'layouts/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-y-auto relative">

        <!-- HEADER ADA DISINI -->
        <?php include 'layouts/header.php'; ?>

            <div class="p-8 fade-in">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-end mb-8 gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Overtime Log</h3>
                    <p class="text-sm text-slate-400 mt-1">Recapitulation of overtime hours based on SPK.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    
                    <div class="relative group w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 group-focus-within:text-cyan-500 transition">
                            <i class="fas fa-search text-sm"></i>
                        </span>
                        <input type="text" id="searchInput" placeholder="Search Name, SPK..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-700 text-white rounded-xl text-sm focus:border-cyan-500 outline-none transition shadow-sm">
                    </div>

                    <div class="flex gap-2 w-full sm:w-auto">
                        <button onclick="downloadExcelOvertime()" 
                            class="flex-1 sm:flex-none bg-slate-800 hover:bg-slate-700 text-slate-300 px-4 py-2.5 rounded-xl border border-slate-700 text-sm transition flex items-center justify-center gap-2">
                            <i class="fas fa-file-excel text-green-500"></i>
                            <span>Export</span>
                        </button>

                        <button onclick="openModal('modalOvertime')" 
                            class="flex-[1.5] sm:flex-none bg-cyan-600 hover:bg-cyan-500 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition shadow-lg shadow-cyan-600/20 flex items-center justify-center gap-2 text-nowrap">
                            <i class="fas fa-plus"></i>
                            <span>Request <span class="hidden xs:inline">Overtime</span></span>
                        </button>
                    </div>
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
                                        <th class="px-4 py-3">Evidence</th>
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
                                        
                                        <td class="px-4 py-3 align-middle">
                                            <?php if (!empty($rowA['evidence'])): ?>
                                                <div class="flex flex-col gap-1.5">
                                                    <?php 
                                                    $files = explode(',', $rowA['evidence']);
                                                    foreach ($files as $file): 
                                                        $file = trim($file);
                                                        if(empty($file)) continue;

                                                        $isPdf = (strpos(strtolower($file), '.pdf') !== false);
                                                        $iconClass = $isPdf ? 'fa-file-pdf text-red-400' : 'fa-image text-blue-400';
                                                        // Potong nama file biar gak kepanjangan
                                                        $displayName = (strlen($file) > 18) ? substr($file, 11, 15) . '...' : substr($file, 11);
                                                    ?>
                                                        <a href="uploads/evidence/<?php echo $file; ?>" target="_blank" 
                                                        class="flex items-center gap-2 p-1.5 bg-slate-900 border border-slate-700 rounded hover:border-emerald-500 hover:bg-slate-800 transition group w-full max-w-[180px] shadow-sm relative overflow-hidden">
                                                            
                                                            <div class="w-6 h-6 rounded bg-slate-800 flex items-center justify-center shrink-0 border border-slate-700 group-hover:border-slate-600">
                                                                <i class="fas <?php echo $iconClass; ?> text-[10px]"></i>
                                                            </div>

                                                            <div class="flex-1 min-w-0 leading-none">
                                                                <p class="text-[10px] text-slate-300 font-medium truncate group-hover:text-white transition mb-0.5" title="<?php echo $file; ?>">
                                                                    <?php echo $displayName; ?>
                                                                </p>
                                                                <p class="text-[9px] text-slate-500 group-hover:text-emerald-400 transition flex items-center gap-1">
                                                                    <i class="fas fa-search text-[8px]"></i> Klik untuk lihat
                                                                </p>
                                                            </div>

                                                            <div class="absolute right-0 top-0 h-full w-0.5 bg-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-600 text-[10px] italic opacity-50">- Tidak ada lampiran -</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-4 py-3 flex gap-2 justify-center">
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
                                    <th class="px-6 py-4 w-10"></th> <th class="px-6 py-4">Name / SPK</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Time</th>
                                    <th class="px-6 py-4">Total</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-700/50" id="tableOvertimeBody">
                                <?php
                                $qHist = mysqli_query($conn, "SELECT a.*, b.full_name 
                                                            FROM tb_overtime a 
                                                            JOIN tb_users b ON a.user_id = b.user_id 
                                                            ORDER BY a.date_ot DESC");

                                if (mysqli_num_rows($qHist) > 0) {
                                    while ($row = mysqli_fetch_assoc($qHist)) {
                                        $id = $row['ot_id'];
                                        
                                        // Warna Status
                                        $statusClass = "bg-yellow-500/10 text-yellow-400 border-yellow-500/20";
                                        if($row['status'] == 'Approved') $statusClass = "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";
                                        if($row['status'] == 'Rejected') $statusClass = "bg-red-500/10 text-red-400 border-red-500/20";

                                        $isMe = ($row['user_id'] == $_SESSION['user_id']);
                                        $rowClass = $isMe ? "bg-slate-800/50" : "";
                                ?>
                                        <tr class="hover:bg-slate-700/20 transition group border-l-4 border-transparent hover:border-cyan-500 <?php echo $rowClass; ?>">
                                            
                                            <td class="px-6 py-4 text-center">
                                                <!-- <button onclick="toggleDetail('ot<?php echo $id; ?>')" class="w-6 h-6 rounded-full bg-slate-700 text-emerald-400 hover:bg-emerald-600 hover:text-white transition flex items-center justify-center focus:outline-none"> -->
                                                <button data-toggle-id="ot<?php echo $id; ?>" class="btn-toggle-row w-6 h-6 rounded-full bg-slate-700 text-cyan-400 hover:bg-cyan-600 hover:text-white transition flex items-center justify-center focus:outline-none">    
                                                    <i class="fas fa-plus text-xs transition-transform" id="icon-ot<?php echo $id; ?>"></i>
                                                </button>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-bold text-white"><?php echo $row['full_name']; ?></div>
                                                <div class="text-xs text-indigo-400 font-mono mt-0.5">
                                                    <?php echo $row['spk_number'] ? $row['spk_number'] : '- No SPK -'; ?>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 text-slate-400 whitespace-nowrap">
                                                <?php echo date('d M Y', strtotime($row['date_ot'])); ?>
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
                                                $isPending = ($row['status'] == 'Pending');
                                                $isAdmin   = ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'section');
                                                $allowAccess = ($isMe && $isPending) || $isAdmin;

                                                if ($allowAccess) { 
                                                ?>
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button onclick="openEditModal('<?php echo $row['ot_id']; ?>','<?php echo $row['date_ot']; ?>','<?php echo $row['time_start']; ?>','<?php echo $row['time_end']; ?>','<?php echo $row['duration']; ?>', '<?php echo $row['spk_number']; ?>','<?php echo htmlspecialchars($row['activity'], ENT_QUOTES); ?>')" class="bg-slate-700 hover:bg-blue-600 text-white w-8 h-8 rounded flex items-center justify-center transition" title="Edit Data">
                                                            <i class="fas fa-edit text-xs"></i>
                                                        </button>
                                                        <button onclick="confirmDeleteOt('<?php echo $row['ot_id']; ?>')" class="bg-slate-700 hover:bg-red-600 text-white w-8 h-8 rounded flex items-center justify-center transition" title="Hapus Request">
                                                            <i class="fas fa-trash text-xs"></i>
                                                        </button>
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="flex items-center justify-center gap-1 text-xs italic text-slate-600">
                                                        <?php if (!$isPending): ?>
                                                            <i class="fas fa-check-double text-emerald-800"></i> <span>Final</span>
                                                        <?php else: ?>
                                                            <i class="fas fa-lock"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php } ?>
                                            </td>
                                        </tr>

                                        <tr id="detail-ot<?php echo $id; ?>" class="hidden bg-slate-800/50 border-b border-slate-700 shadow-inner">
                                            <td colspan="7" class="px-8 py-6">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs">
                                                    
                                                    <div class="space-y-2 border-r border-slate-700 pr-4">
                                                        <h4 class="text-emerald-400 font-bold uppercase tracking-wider mb-2">📋 Aktivitas / Pekerjaan</h4>
                                                        <p class="text-slate-300 bg-slate-900/50 p-3 rounded border border-slate-700/50 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($row['activity']); ?></p>
                                                    </div>

                                                    <div class="space-y-2">
                                                        <h4 class="text-emerald-400 font-bold uppercase tracking-wider mb-2">📷 Lampiran / Evidence</h4>

                                                        <?php if (!empty($row['evidence'])): 
                                                            $files = explode(',', $row['evidence']);
                                                        ?>
                                                            <div class="grid grid-cols-1 gap-2"> 
                                                                <?php foreach ($files as $file):
                                                                    $file = trim($file);
                                                                    if (empty($file)) continue;
                                                                ?>
                                                                    <a href="uploads/evidence/<?php echo $file; ?>" target="_blank" class="bg-slate-900 p-2 rounded border border-slate-700 flex items-center justify-between group cursor-pointer hover:border-emerald-500 transition">
                                                                        <div class="flex items-center gap-3">
                                                                            <div class="w-8 h-8 bg-slate-800 rounded flex items-center justify-center text-slate-400">
                                                                                <?php if (strpos($file, '.pdf') !== false): ?>
                                                                                    <i class="fas fa-file-pdf text-red-400"></i>
                                                                                <?php else: ?>
                                                                                    <i class="fas fa-image text-blue-400"></i>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <div>
                                                                                <div class="text-white font-medium truncate w-48" title="<?php echo $file; ?>">
                                                                                    <?php echo (strlen($file) > 20) ? substr($file, 15) : $file; ?>
                                                                                </div>
                                                                                <div class="text-[10px] text-slate-500">Klik untuk lihat</div>
                                                                            </div>
                                                                        </div>
                                                                        <i class="fas fa-external-link-alt text-slate-600 group-hover:text-emerald-400 mr-2 text-xs"></i>
                                                                    </a>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="p-3 bg-slate-900/30 rounded border border-slate-700 border-dashed text-center text-slate-500 italic">
                                                                - Tidak ada lampiran -
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                <?php 
                                    } 
                                } else { 
                                ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-slate-500 italic">Belum ada history lembur siapapun.</td>
                                    </tr>
                                <?php } ?>
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

                <form action="process/process_add_overtime.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    
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

                    <div>
                        <label class="block text-xs text-slate-400 mb-2 font-medium">13. Evidence / Attachment</label>
                        <div class="w-full">
                            <label for="file_evidence" id="drop-zone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-emerald-500 transition group relative overflow-hidden">
                                
                                <div class="flex flex-col items-center justify-center pt-5 pb-6" id="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 mb-2 group-hover:text-emerald-400 transition"></i>
                                    <p class="text-sm text-slate-400 mb-1"><span class="font-semibold text-emerald-400">Klik untuk upload</span></p>
                                    <p class="text-[10px] text-slate-500">JPG, PNG, PDF (Max 2MB)</p>
                                </div>

                                <div id="file-preview" class="hidden flex-col items-center justify-center w-full h-full bg-slate-800/80 absolute inset-0">
                                    <i class="fas fa-check-circle text-3xl text-emerald-400 mb-2"></i>
                                    <p id="file-name-display" class="text-xs text-white font-medium text-center px-2 break-all">Filename.jpg</p>
                                    <p class="text-[10px] text-slate-400 mt-1">(Klik lagi untuk ganti)</p>
                                </div>

                                <input id="file_evidence" type="file" name="evidence[]" multiple accept="image/*,.pdf" class="hidden" onchange="previewFile()" />
                            </label>
                        </div>
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
                        <p class="text-xs text-slate-500 mt-1">Silakan perbarui data lembur jika diperlukan.</p>
                    </div>
                    <button onclick="closeEditModal()" class="close-modal text-slate-400 hover:text-red-400 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="process/process_edit_ot.php" method="POST" enctype="multipart/form-data" class="space-y-4">
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
                            <input type="time" name="time_start" id="edit_start" onchange="calculateEditDuration()" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Jam Selesai</label>
                            <input type="time" name="time_end" id="edit_end" onchange="calculateEditDuration()" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none">
                        </div>
                    </div>

                    <div class="bg-slate-800 p-3 rounded border border-slate-700 flex justify-between items-center">
                        <span class="text-xs text-slate-400">Estimasi Durasi:</span>
                        <span id="edit_duration" class="text-emerald-400 font-bold text-lg">0.0 Jam</span>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Aktivitas</label>
                        <textarea name="activity" id="edit_activity" rows="3" required class="w-full bg-slate-950 border border-slate-700 text-white rounded px-3 py-2 text-sm focus:border-emerald-500 outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-400 mb-2 font-medium">Update Evidence (Opsional)</label>
                        <div class="w-full">
                            <label for="edit_file_evidence" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-700 border-dashed rounded-lg cursor-pointer bg-slate-950 hover:bg-slate-800 hover:border-emerald-500 transition group relative overflow-hidden">
                                
                                <div class="flex flex-col items-center justify-center pt-5 pb-6" id="edit-upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 mb-2 group-hover:text-emerald-400 transition"></i>
                                    <p class="text-sm text-slate-400 mb-1"><span class="font-semibold text-emerald-400">Ganti File</span></p>
                                    <p class="text-[10px] text-slate-500">Biarkan kosong jika tidak diubah</p>
                                </div>

                                <div id="edit-file-preview" class="hidden flex-col items-center justify-center w-full h-full bg-slate-800/80 absolute inset-0">
                                    <i class="fas fa-check-circle text-3xl text-emerald-400 mb-2"></i>
                                    <p id="edit-file-name-display" class="text-xs text-white font-medium text-center px-2 break-all">Filename.jpg</p>
                                    <p class="text-[10px] text-slate-400 mt-1">(Klik lagi untuk ganti)</p>
                                </div>

                                <input id="edit_file_evidence" type="file" name="evidence[]" multiple accept="image/*,.pdf" class="hidden" onchange="previewEditFile()" />
                            </label>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="closeEditModal()" class="flex-1 py-2 bg-slate-800 text-slate-300 rounded hover:bg-slate-700 text-sm">Batal</button>
                        <button type="submit" class="flex-1 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 text-sm font-bold shadow-lg">Update Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'layouts/mobile_nav.php'; ?>
    <?php include 'layouts/scripts.php'; ?>

    <script>
                // ============================================================
        //  5. LOGIKA PAGINATION & LIVE SEARCH (FULL FIX)
        // ============================================================
        // document.addEventListener('DOMContentLoaded', function() {
        // document.addEventListener('turbo:load', function() {
        (function() {
            if (document.documentElement.hasAttribute("data-turbo-preview")) return;

            // 2. PROTEKSI HALAMAN
            if (!window.location.pathname.includes('overtime.php')) return;

            const tableBody = document.getElementById('tableOvertimeBody');
            const searchInput = document.getElementById('searchInput');
            const pageInfo = document.getElementById('pageInfo');
            const paginationControls = document.getElementById('paginationControls');

            if (tableBody) {
                const rowsPerPage = 20; 
                // Ambil baris MASTER saja (abaikan baris detail-otxxx)
                let allRows = Array.from(tableBody.querySelectorAll('tr')).filter(row => !row.id.includes('detail-'));
                let currentPage = 1;

                // --- FUNGSI UTAMA RENDER TABEL ---
                function renderTable() {
                    const searchText = searchInput ? searchInput.value.toLowerCase() : '';

                    // 1. Filter Data
                    const filteredRows = allRows.filter(row => {
                        return row.textContent.toLowerCase().includes(searchText);
                    });

                    // 2. Hitung Halaman
                    const totalItems = filteredRows.length;
                    const totalPages = Math.ceil(totalItems / rowsPerPage);
                    if (currentPage > totalPages) currentPage = 1;

                    const start = (currentPage - 1) * rowsPerPage;
                    const end = start + rowsPerPage;

                    // 3. Sembunyikan Semua & Munculkan yang terpilih
                    tableBody.querySelectorAll('tr').forEach(tr => tr.style.display = 'none');
                    
                    filteredRows.slice(start, end).forEach(row => {
                        row.style.display = ''; 
                    });

                    // 4. Update Info Text
                    if (pageInfo) {
                        pageInfo.innerText = totalItems === 0 ? "Tidak ada data yang cocok." : `Menampilkan ${start + 1} - ${Math.min(end, totalItems)} dari ${totalItems} data`;
                    }

                    // 5. Panggil Fungsi Render Tombol (PASTIKAN ADA)
                    renderButtons(totalPages);
                }

                // --- FUNGSI RENDER TOMBOL PAGINATION ---
                function renderButtons(totalPages) {
                    if (!paginationControls) return;
                    paginationControls.innerHTML = "";
                    if (totalPages <= 1) return;

                    const createBtn = (text, page, isActive = false, isDisabled = false) => {
                        const btn = document.createElement('button');
                        btn.innerHTML = text;
                        btn.className = `px-3 py-1 rounded transition text-xs ${isActive ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'}`;
                        if (isDisabled) {
                            btn.classList.add('opacity-50', 'cursor-not-allowed');
                            btn.disabled = true;
                        } else {
                            btn.onclick = () => { currentPage = page; renderTable(); };
                        }
                        return btn;
                    };

                    paginationControls.appendChild(createBtn('<i class="fas fa-chevron-left"></i>', currentPage - 1, false, currentPage === 1));
                    
                    let startP = Math.max(1, currentPage - 2);
                    let endP = Math.min(totalPages, currentPage + 2);
                    for (let i = startP; i <= endP; i++) {
                        paginationControls.appendChild(createBtn(i, i, i === currentPage));
                    }

                    paginationControls.appendChild(createBtn('<i class="fas fa-chevron-right"></i>', currentPage + 1, false, currentPage === totalPages));
                }

                // --- EVENT LISTENER SEARCH ---
                if (searchInput) {
                        searchInput.oninput = () => {
                        currentPage = 1;
                        renderTable();
                    };
                }

                // Jalankan Pertama Kali
                renderTable();
            }
        })();

        // 1. HITUNG DURASI OTOMATIS (Modal Create & Edit)
        function calculateDuration(type = 'add') {
            const prefix = type === 'edit' ? 'edit_' : 't_';
            const startVal = document.getElementById(type === 'edit' ? 'edit_start' : 't_start').value;
            const endVal = document.getElementById(type === 'edit' ? 'edit_end' : 't_end').value;
            const display = document.getElementById(type === 'edit' ? 'edit_duration' : 'duration_preview');

            if (startVal && endVal) {
                let start = new Date("2000-01-01 " + startVal);
                let end = new Date("2000-01-01 " + endVal);
                if (end < start) end.setDate(end.getDate() + 1); // Lewat tengah malam

                let diffHrs = (end - start) / (1000 * 60 * 60);
                if (diffHrs > 4) diffHrs -= 1; // Potong istirahat 1 jam jika > 4 jam
                display.innerText = diffHrs.toFixed(1) + " Jam";
            }
        }

        // --- FUNGSI HITUNG DURASI KHUSUS MODAL EDIT ---
        function calculateEditDuration() {
            const startVal = document.getElementById('edit_start').value;
            const endVal = document.getElementById('edit_end').value;
            const display = document.getElementById('edit_duration');

            if (startVal && endVal) {
                let start = new Date("2000-01-01 " + startVal);
                let end = new Date("2000-01-01 " + endVal);

                // Handle jika lembur melewati tengah malam (misal: 22:00 - 02:00)
                if (end < start) {
                    end.setDate(end.getDate() + 1);
                }

                let diffMs = end - start;
                let diffHrs = diffMs / (1000 * 60 * 60); // Konversi ke jam

                // --- LOGIKA POTONG ISTIRAHAT 1 JAM ---
                // Jika durasi kerja lebih dari 4 jam, otomatis potong 1 jam istirahat
                if (diffHrs > 4) {
                    diffHrs = diffHrs - 1;
                }

                display.innerText = diffHrs.toFixed(1) + " Jam";
            } else {
                display.innerText = "0.0 Jam";
            }
        }

        // 2. MODAL EDIT BINDER
        function openEditModal(id, date, start, end, duration, spk, activity) {
            document.getElementById('edit_ot_id').value = id;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_start').value = start;
            document.getElementById('edit_end').value = end;
            document.getElementById('edit_duration').innerText = duration + " Jam";
            document.getElementById('edit_spk').value = spk;
            document.getElementById('edit_activity').value = activity;
            openModal('modalEditOvertime');
        }

        // 3. UPDATE STATUS (ACC/TOLAK) - AJAX Style
        function updateStatus(id, status) {
            Swal.fire({
                title: status === 'Approved' ? 'Setujui Lembur?' : 'Tolak Lembur?',
                icon: status === 'Approved' ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: status === 'Approved' ? '#059669' : '#ef4444',
                background: '#1e293b', color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'process/process_update_ot.php?id=' + id + '&status=' + status;
                }
            });
        }

        // --- FUNGSI EXPORT EXCEL (DENGAN FILTER PENCARIAN) ---
        function downloadExcelOvertime() {
            // 1. Ambil nilai dari kotak pencarian (agar yang di-export sesuai yang dicari)
            const searchInput = document.getElementById('searchInput');
            const searchValue = searchInput ? searchInput.value : '';

            // 2. Tentukan URL file pemroses export bapak
            let url = 'export/export_overtime_excel.php?export=true';
            
            // 3. Jika user sedang mencari sesuatu, tempelkan kata kuncinya ke URL
            if (searchValue) {
                url += '&search=' + encodeURIComponent(searchValue);
            }

            // 4. Buka di tab baru untuk memulai proses download
            window.open(url, '_blank');
        }

        // --- FUNGSI PREVIEW FILE (DIPERLUKAN UNTUK MODAL) ---
        function previewFile() {
            const input = document.getElementById('file_evidence');
            const previewDiv = document.getElementById('file-preview');
            const nameDisplay = document.getElementById('file-name-display');
            
            if (input.files && input.files.length > 0) {
                nameDisplay.innerText = input.files.length === 1 ? input.files[0].name : input.files.length + " File Dipilih";
                previewDiv.classList.replace('hidden', 'flex');
            } else {
                previewDiv.classList.replace('flex', 'hidden');
            }
        }

        function previewEditFile() {
            const input = document.getElementById('edit_file_evidence');
            const previewDiv = document.getElementById('edit-file-preview');
            const nameDisplay = document.getElementById('edit-file-name-display');
            
            if (input.files && input.files.length > 0) {
                nameDisplay.innerText = input.files.length === 1 ? input.files[0].name : input.files.length + " File Baru Dipilih";
                previewDiv.classList.replace('hidden', 'flex');
            } else {
                previewDiv.classList.replace('flex', 'hidden');
            }
        }

        // 4. DELETE & TOGGLE DETAIL
        function confirmDeleteOt(id) {
            Swal.fire({
                title: 'Hapus Data?', text: "Data tidak bisa kembali!", icon: 'warning',
                showCancelButton: true, background: '#1e293b', color: '#fff', confirmButtonColor: '#ef4444'
            }).then((r) => { if(r.isConfirmed) window.location.href = 'process/process_delete_ot.php?id='+id; });
        }

        // --- FUNGSI TUTUP MODAL EDIT ---
        function closeEditModal() {
            const modal = document.getElementById('modalEditOvertime');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
    </script>
</body>
</html>