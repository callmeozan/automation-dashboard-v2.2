<?php
include '../layouts/auth_and_config.php';

// 1. Fungsi konversi gambar ke Base64 (Aman untuk preview & cetak PDF)
function renderPdfImage($filePath, $mimeType = '') {
    if (!empty($filePath) && file_exists($filePath)) {
        $data = file_get_contents($filePath);
        if (empty($mimeType)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
        }
        return 'data:' . $mimeType . ';base64,' . base64_encode($data);
    }
    return '';
}

// 2. Olah upload Before, Activity, dan After (Mendukung Multi-Upload)
$evidenceList = [];

$categories = [
    'evidence_before'   => $_POST['caption_before'] ?? 'Kondisi Awal (Before)',
    'evidence_activity' => $_POST['caption_activity'] ?? 'Aktivitas Pekerjaan',
    'evidence_after'    => $_POST['caption_after'] ?? 'Hasil Akhir (After)'
];

foreach ($categories as $inputName => $defaultCaption) {
    if (!empty($_FILES[$inputName]['name'][0])) {
        $totalFiles = count($_FILES[$inputName]['name']);
        for ($i = 0; $i < $totalFiles; $i++) {
            if (isset($_FILES[$inputName]['error'][$i]) && $_FILES[$inputName]['error'][$i] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES[$inputName]['tmp_name'][$i];
                $mime    = $_FILES[$inputName]['type'][$i];
                $base64  = renderPdfImage($tmpPath, $mime);
                
                if (!empty($base64)) {
                    $captionText = $defaultCaption;
                    if ($totalFiles > 1) {
                        $captionText .= ' (' . ($i + 1) . ')';
                    }
                    $evidenceList[] = [
                        'src'     => $base64,
                        'caption' => $captionText
                    ];
                }
            }
        }
    }
}

// 1. Data Identitas Proyek & Latar Belakang
$selected_ids    = $_POST['selected_ids'] ?? '';
$project_name    = $_POST['project_name'] ?? 'Project Report';
$machine_name    = $_POST['machine_name'] ?? '-';
$plant_name      = $_POST['plant_name'] ?? '-';
$prepared_by     = $_POST['prepared_by'] ?? '-';
$approved_by     = $_POST['approved_by'] ?? 'Teguh Imanto';
$background_text = $_POST['background_text'] ?? '';

// 2. Tangkap Input Fase 1: Project Preparation
$prep_activities = $_POST['prep_activity'] ?? [];
$prep_days       = $_POST['prep_days'] ?? [];
$prep_progress   = $_POST['prep_progress'] ?? [];
$prep_statuses   = $_POST['prep_status'] ?? [];
$prep_notes      = $_POST['prep_notes'] ?? [];
$prep_start_dates = $_POST['prep_start_date'] ?? [];
$prep_end_dates   = $_POST['prep_end_date'] ?? [];

// Hitung Subtotal Progress Persiapan
$total_prep_progress = 0;
foreach ($prep_progress as $p) {
    $total_prep_progress += (int)$p;
}

// 3. Tangkap Input Fase 2: Project Execution (Hasil pecahan/editan di modal)
$exec_activities = $_POST['exec_activity'] ?? [];
$exec_progress   = $_POST['exec_progress'] ?? [];
$exec_statuses   = $_POST['exec_status'] ?? [];
$exec_start_dates = $_POST['exec_start_date'] ?? [];
$exec_end_dates   = $_POST['exec_end_date'] ?? [];
$exec_days        = $_POST['exec_days'] ?? [];

// Hitung Subtotal Progress Eksekusi
$total_exec_progress = 0;
foreach ($exec_progress as $report_id => $prog_array) {
    foreach ($prog_array as $val) {
        $total_exec_progress += (int)$val;
    }
}

// 4. Hitung Total Overall Progress (%)
$total_progress = $total_prep_progress + $total_exec_progress;
if ($total_progress > 100) $total_progress = 100;

// 5. Query Database untuk Evidence & Rentang Tanggal Asli
$activities     = [];
$evidence_list  = [];
$start_date_min = null;
$end_date_max   = null;

if (!empty($selected_ids)) {
    $id_array = array_filter(array_map('intval', explode(',', $selected_ids)));

    if (!empty($id_array)) {
        $in_clause = implode(',', $id_array);
        $query = "SELECT * FROM tb_daily_reports 
                  WHERE report_id IN ($in_clause) 
                  ORDER BY date_log ASC";
        
        $res = mysqli_query($conn, $query);

        if ($res && mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $activities[] = $row;

                // Ambil file evidence gambar
                $raw_evidence = $row['evidence_file'] ?? '';
                if (!empty($raw_evidence)) {
                    $files = explode(',', $raw_evidence);
                    foreach ($files as $f) {
                        $f = trim($f);
                        if (!empty($f)) {
                            $evidence_list[] = [
                                'file'    => $f,
                                'caption' => $row['problem'] ?? $row['action'] ?? 'Evidence Activity'
                            ];
                        }
                    }
                }

                // Kalkulasi tanggal awal dan akhir proyek
                $d_start = $row['date_log'];
                $d_end   = !empty($row['end_date']) ? $row['end_date'] : $d_start;

                if ($d_start && (!$start_date_min || strtotime($d_start) < strtotime($start_date_min))) {
                    $start_date_min = $d_start;
                }
                if ($d_end && (!$end_date_max || strtotime($d_end) > strtotime($end_date_max))) {
                    $end_date_max = $d_end;
                }
            }
        }
    }
}

// Fallback jika tanggal tidak ditemukan di database
$start_date = $start_date_min ?: date('Y-m-d');
$end_date   = $end_date_max ?: date('Y-m-d');

// Kategori & Label Sub-title
$raw_category = trim($activities[0]['category'] ?? 'Project');
$report_sub_title = ($raw_category === 'PM') 
    ? 'Preventive Maintenance Report' 
    : ((stripos($raw_category, 'Project') !== false) ? 'Project Report' : $raw_category . ' Report');

// Prepared By (Gunakan isian form modal yang sudah menampung PIC gabungan)
$display_prepared_by = !empty($prepared_by) ? $prepared_by : 'Automation Team';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($project_name); ?> - Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        /* 1. Tipe FIXED: Cover, TOC, dan Evidence (Tepat 1 Halaman A4) */
        .page-fixed {
            width: 210mm;
            height: 297mm;
            padding: 18mm 20mm 15mm 20mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        /* 2. Tipe FLUID: Tabel Job Activity (Padding dirampingkan agar muat 1 lembar utuh) */
        .page-fluid {
            width: 210mm;
            min-height: 297mm;
            padding: 14mm 18mm 14mm 18mm; /* Padding atas-bawah diperkecil dari 20mm ke 14mm */
            margin: 0 auto 20px auto;
            background: #ffffff;
            box-sizing: border-box;
            position: relative;
        }

        /* 3. Penomoran Halaman di Pojok Bawah */
        .page-number {
            position: absolute;
            bottom: 10mm;
            right: 18mm;
            font-size: 10px;
            color: #94a3b8;
        }

        @media print {
            body {
                background: transparent !important;
            }

            .no-print {
                display: none !important;
            }

            /* HANYA gunakan page-break-after, jangan campur dengan break-before */
            .page-fixed {
                margin: 0 !important;
                box-shadow: none !important;
                page-break-after: always !important;
                break-after: page !important;
                height: 297mm !important;
            }

            .page-fluid {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                box-shadow: none !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .page-fixed:last-child,
            .page-fluid:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            thead {
                display: table-header-group !important;
            }

            /* Cegah baris tabel terpotong terpisah */
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Rampingkan padding sel tabel khusus saat dicetak */
            table td, table th {
                padding-top: 3px !important;
                padding-bottom: 3px !important;
            }
        }
    </style>
</head>
<body class="bg-slate-200 font-sans antialiased text-slate-800">

    <!-- Tombol Bar Aksi Atas (Tidak ikut tercetak) -->
    <div class="no-print fixed top-4 right-4 z-50 flex items-center gap-3 bg-slate-900/90 backdrop-blur p-3 rounded-xl shadow-2xl border border-slate-700">
        <button onclick="window.print()" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-2">
            <i class="fas fa-print"></i> Print / Save PDF
        </button>
        <!-- <form action="../process/export_project_report_word.php" method="POST" target="_blank" class="m-0 p-0">
            <input type="hidden" name="payload_data" value="<?php echo htmlspecialchars(json_encode($_POST), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="evidence_payload" value="<?php echo htmlspecialchars(json_encode($evidenceList ?? []), ENT_QUOTES, 'UTF-8'); ?>">
            
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-2">
                <i class="fas fa-file-word"></i> Export Word
            </button>
        </form> -->
        <button onclick="window.close()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-lg transition">
            Close
        </button>
    </div>

    <!-- ======================================================== -->
    <!-- HALAMAN 1: COVER REPORT                                  -->
    <!-- ======================================================== -->
    <div class="page-fixed flex flex-col justify-between">
        <div class="pt-4">
            <h1 class="text-5xl font-normal tracking-tight text-slate-900 leading-tight">
                <?php echo htmlspecialchars($project_name); ?>
            </h1>
            <h2 class="text-2xl font-normal text-slate-500 mt-2 tracking-wide">
                <?php echo htmlspecialchars($report_sub_title); ?>
            </h2>
        </div>

        <div class="flex justify-center my-auto py-6">
            <img src="../image/jis.png" alt="Logo JIS Automation" class="h-64 w-auto object-contain">
        </div>

        <div class="pb-2">
            <table class="w-full border-collapse border border-slate-400 text-xs mb-6">
                <thead>
                    <tr class="bg-slate-100 text-left uppercase text-[10px] text-slate-600">
                        <th class="border border-slate-400 px-3 py-1.5 w-1/3">Prepared By</th>
                        <th class="border border-slate-400 px-3 py-1.5 w-1/3">Title</th>
                        <th class="border border-slate-400 px-3 py-1.5 w-1/3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-slate-400 px-3 py-2 font-medium"><?php echo htmlspecialchars($display_prepared_by); ?></td>
                        <td class="border border-slate-400 px-3 py-2 text-slate-600">Automation Engineer</td>
                        <td class="border border-slate-400 px-3 py-2"><?php echo date('d F Y', strtotime($start_date_min ?: date('Y-m-d'))); ?></td>
                    </tr>
                    <tr class="bg-slate-100 text-left uppercase text-[10px] text-slate-600">
                        <th class="border border-slate-400 px-3 py-1.5">Approved By</th>
                        <th class="border border-slate-400 px-3 py-1.5">Title</th>
                        <th class="border border-slate-400 px-3 py-1.5">Date</th>
                    </tr>
                    <tr>
                        <td class="border border-slate-400 px-3 py-2 font-medium"><?php echo htmlspecialchars($approved_by); ?></td>
                        <td class="border border-slate-400 px-3 py-2 text-slate-600">Department Head</td>
                        <td class="border border-slate-400 px-3 py-2"><?php echo date('d F Y', strtotime($end_date_max ?: date('Y-m-d'))); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="text-[11px] text-slate-600 leading-relaxed">
                <p class="font-bold text-slate-800 uppercase tracking-wide">Joint Instrument and Automation (JIS) Department</p>
                <p class="font-semibold text-slate-700 uppercase tracking-wide">PT Gajah Tunggal Tbk</p>
                <p class="text-slate-500 mt-0.5">Kompleks Industri Gajah Tunggal</p>
                <p class="text-slate-500">Kel. Pasir Jaya, Kec. Jatiuwung, Tangerang 15135</p>
                <p class="text-slate-500 font-semibold mt-1.5"><?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- HALAMAN 2: TABLE OF CONTENTS                             -->
    <!-- ======================================================== -->
    <div class="page-fixed flex flex-col justify-between pt-12">
        <div>
            <h2 class="text-base font-bold tracking-wider underline uppercase text-slate-900 mb-8">
                Table of Contents
            </h2>
            <div class="space-y-4 text-xs">
                <div class="flex items-baseline justify-between border-b border-dotted border-slate-400 pb-1">
                    <span class="font-semibold">A. OVERVIEW PROJECT</span>
                    <span class="font-mono">3</span>
                </div>
                <div class="flex items-baseline justify-between border-b border-dotted border-slate-400 pb-1">
                    <span class="font-semibold">B. BACKGROUND (LATAR BELAKANG)</span>
                    <span class="font-mono">3</span>
                </div>
                <div class="flex items-baseline justify-between border-b border-dotted border-slate-400 pb-1">
                    <span class="font-semibold">C. JOB ACTIVITY</span>
                    <span class="font-mono">3</span>
                </div>
                <div class="flex items-baseline justify-between border-b border-dotted border-slate-400 pb-1">
                    <span class="font-semibold">D. RESULT</span>
                    <span class="font-mono">4</span>
                </div>
            </div>
        </div>
        
        <!-- Nomor halaman terkunci di dasar lembar -->
        <div class="page-number">Page 2 of 4</div>
    </div>

    <!-- ======================================================== -->
    <!-- HALAMAN 3: DETAIL PROYEK & JOB ACTIVITY                  -->
    <!-- ======================================================== -->
    <div class="page-content flex flex-col justify-between !py-4 !px-6">
        <div class="page-fluid force-page-break pb-16">
            <!-- A. OVERVIEW PROJECT -->
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-2 pt-2">A. OVERVIEW PROJECT</h3>
            <table class="w-full border-collapse border border-slate-400 text-[11px] mb-4">
                <tbody>
                    <tr>
                        <td class="border border-slate-400 px-2.5 py-1 font-semibold bg-slate-50 w-1/3">Nama Project</td>
                        <td class="border border-slate-400 px-2.5 py-1"><?php echo htmlspecialchars($project_name); ?></td>
                    </tr>
                    <tr>
                        <td class="border border-slate-400 px-2.5 py-1 font-semibold bg-slate-50">Machine</td>
                        <td class="border border-slate-400 px-2.5 py-1"><?php echo htmlspecialchars($machine_name); ?></td>
                    </tr>
                    <tr>
                        <td class="border border-slate-400 px-2.5 py-1 font-semibold bg-slate-50">Plant</td>
                        <td class="border border-slate-400 px-2.5 py-1"><?php echo htmlspecialchars($plant_name); ?></td>
                    </tr>
                    <tr>
                        <td class="border border-slate-400 px-2.5 py-1 font-semibold bg-slate-50">Start Date</td>
                        <td class="border border-slate-400 px-2.5 py-1"><?php echo $start_date_min ? date('d F Y', strtotime($start_date_min)) : '-'; ?></td>
                    </tr>
                    <tr>
                        <td class="border border-slate-400 px-2.5 py-1 font-semibold bg-slate-50">End Date</td>
                        <td class="border border-slate-400 px-2.5 py-1"><?php echo $end_date_max ? date('d F Y', strtotime($end_date_max)) : '-'; ?></td>
                    </tr>
                    <tr>
                        <td class="border border-slate-400 px-2.5 py-1 font-semibold bg-slate-50">Overall Progress (%)</td>
                        <td class="border border-slate-400 px-2.5 py-1 font-bold text-emerald-700"><?php echo $total_progress; ?>%</td>
                    </tr>
                    <tr>
                        <td class="border border-slate-400 px-2.5 py-1 font-semibold bg-slate-50">PIC</td>
                        <td class="border border-slate-400 px-2.5 py-1"><?php echo htmlspecialchars($display_prepared_by); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- B. BACKGROUND -->
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-1.5">B. BACKGROUND (LATAR BELAKANG)</h3>
            <div class="border border-slate-400 p-2 text-[11px] leading-relaxed text-slate-700 mb-4 min-h-[45px] whitespace-pre-line text-justify">
                <?php echo nl2br(htmlspecialchars($background_text ?? '')); ?>
            </div>

            <!-- C. JOB ACTIVITY -->
            <div class="mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-1.5">C. JOB ACTIVITY</h3>
                <table class="w-full border-collapse border border-slate-400 text-[10px]">
                    <thead>
                        <tr class="bg-slate-200 text-slate-800 text-center font-bold">
                            <th class="border border-slate-400 px-1 py-1 w-7">No</th>
                            <th class="border border-slate-400 px-2 py-1 text-left">Activity</th>
                            <th class="border border-slate-400 px-1 py-1 w-14">Duration<br>(Days)</th>
                            <th class="border border-slate-400 px-1 py-1 w-16">Start Date</th>
                            <th class="border border-slate-400 px-1 py-1 w-16">End Date</th>
                            <th class="border border-slate-400 px-1 py-1 w-14">Progress<br>(%)</th>
                            <th class="border border-slate-400 px-1 py-1 w-14">Status<br>(OK/B.OK)</th>
                            <th class="border border-slate-400 px-2 py-1 text-left w-28">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prep_activities)): ?>
                            <!-- HEADER FASE 1: PROJECT PREPARATION -->
                            <tr class="bg-emerald-100/70 font-bold text-slate-900">
                                <td class="border border-slate-400 px-1 py-1 text-center">1</td>
                                <td colspan="7" class="border border-slate-400 px-2 py-1">Project Preparation</td>
                            </tr>

                            <?php 
                            foreach ($prep_activities as $idx => $act_name): 
                                $sub_no    = '1.' . ($idx + 1);
                                $days      = htmlspecialchars($prep_days[$idx] ?? '1');
                                $prog      = htmlspecialchars($prep_progress[$idx] ?? '0');
                                $stat      = htmlspecialchars($prep_statuses[$idx] ?? 'OK');
                                $notes     = htmlspecialchars($prep_notes[$idx] ?? '-');
                                $startDate = !empty($prep_start_dates[$idx]) ? date('d M Y', strtotime($prep_start_dates[$idx])) : '-';
                                $endDate   = !empty($prep_end_dates[$idx]) ? date('d M Y', strtotime($prep_end_dates[$idx])) : '-';
                            ?>
                                <tr>
                                    <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $sub_no; ?></td>
                                    <td class="border border-slate-400 px-2 py-1"><?php echo htmlspecialchars($act_name); ?></td>
                                    <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $days; ?></td>
                                    <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $startDate; ?></td>
                                    <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $endDate; ?></td>
                                    <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $prog; ?>%</td>
                                    <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $stat; ?></td>
                                    <td class="border border-slate-400 px-2 py-1"><?php echo $notes; ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- SUB-TOTAL PREPARATION -->
                            <tr class="font-bold text-slate-900 bg-slate-50">
                                <td colspan="5" class="border border-slate-400 px-2 py-1 text-right">Project Preparation Progress</td>
                                <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $total_prep_progress; ?>%</td>
                                <td class="border border-slate-400 px-1 py-1 text-center">OK</td>
                                <td class="border border-slate-400 px-2 py-1"></td>
                            </tr>
                        <?php endif; ?>

                        <!-- HEADER FASE 2: PROJECT EXECUTION -->
                        <tr class="bg-amber-100/70 font-bold text-slate-900">
                            <td class="border border-slate-400 px-1 py-1 text-center">2</td>
                            <td colspan="7" class="border border-slate-400 px-2 py-1">Project Execution</td>
                        </tr>

                        <?php 
                        $exec_counter = 1;
                        if (isset($_POST['exec_activity'])) {
                            foreach ($_POST['exec_activity'] as $reportId => $activities) {
                                foreach ($activities as $idx => $act_name) {
                                    $sub_no    = '2.' . $exec_counter++;
                                    $days      = htmlspecialchars($_POST['exec_days'][$reportId][$idx] ?? '1');
                                    $prog      = htmlspecialchars($_POST['exec_progress'][$reportId][$idx] ?? '0');
                                    $stat      = htmlspecialchars($_POST['exec_status'][$reportId][$idx] ?? 'OK');
                                    $notes     = htmlspecialchars($_POST['exec_notes'][$reportId][$idx] ?? '-');
                                    
                                    $rawStart  = $_POST['exec_start_date'][$reportId][$idx] ?? '';
                                    $rawEnd    = $_POST['exec_end_date'][$reportId][$idx] ?? '';
                                    $startDate = !empty($rawStart) ? date('d M Y', strtotime($rawStart)) : '-';
                                    $endDate   = !empty($rawEnd) ? date('d M Y', strtotime($rawEnd)) : '-';
                                    ?>
                                    <tr>
                                        <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $sub_no; ?></td>
                                        <td class="border border-slate-400 px-2 py-1"><?php echo htmlspecialchars($act_name); ?></td>
                                        <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $days; ?></td>
                                        <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $startDate; ?></td>
                                        <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $endDate; ?></td>
                                        <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $prog; ?>%</td>
                                        <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $stat; ?></td>
                                        <td class="border border-slate-400 px-2 py-1"><?php echo $notes; ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                        }
                        ?>

                        <!-- SUB-TOTAL EXECUTION -->
                        <tr class="font-bold text-slate-900 bg-slate-50">
                            <td colspan="5" class="border border-slate-400 px-2 py-1 text-right">Project Execution Progress</td>
                            <td class="border border-slate-400 px-1 py-1 text-center"><?php echo $total_exec_progress; ?>%</td>
                            <td class="border border-slate-400 px-1 py-1 text-center">OK</td>
                            <td class="border border-slate-400 px-2 py-1"></td>
                        </tr>

                        <!-- OVERALL PROGRESS AKHIR -->
                        <tr class="bg-blue-100/70 font-bold text-slate-900 text-xs">
                            <td colspan="5" class="border border-slate-400 px-2 py-1 text-right uppercase tracking-wider">Overall Progress</td>
                            <td class="border border-slate-400 px-1 py-1 text-center text-blue-900 font-extrabold"><?php echo $total_progress; ?>%</td>
                            <td class="border border-slate-400 px-1 py-1 text-center">OK</td>
                            <td class="border border-slate-400 px-2 py-1"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="page-number">Page 3 of 4</div>
    </div>

    <!-- ======================================================== -->
    <!-- HALAMAN 4: HASIL DOKUMENTASI (EVIDENCE PHOTOS)           -->
    <!-- ======================================================== -->
    <div class="page-fixed flex flex-col">
        
        <!-- 1. Header Bagian D -->
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3 pt-2">
            D. RESULT
        </h3>

        <!-- 2. Kotak Frame Evidence (Tanpa min-height besar, padding proporsional) -->
        <div class="border border-slate-400 p-4 rounded bg-white">
            <h4 class="text-center font-bold text-xs uppercase tracking-wider text-slate-700 underline mb-4">
                FOTO ACTIVITY & EVIDENCE
            </h4>

            <?php if (empty($evidenceList)): ?>
                <div class="py-16 text-center text-slate-400 italic text-xs">
                    - Tidak ada lampiran foto evidence yang diunggah -
                </div>
            <?php else: ?>
                <!-- Grid 2 Kolom Rapi -->
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($evidenceList as $ev): ?>
                        <div class="border border-slate-300 rounded p-2 bg-slate-50 flex flex-col items-center justify-between">
                            <!-- Frame Preview Gambar -->
                            <div class="w-full h-52 flex items-center justify-center overflow-hidden bg-white rounded border border-slate-200">
                                <img src="<?php echo $ev['src']; ?>" 
                                    alt="Evidence" 
                                    class="max-h-full max-w-full object-contain">
                            </div>
                            
                            <!-- Caption Foto -->
                            <p class="text-[10px] text-slate-700 text-center mt-2 font-medium line-clamp-2">
                                <?php echo htmlspecialchars($ev['caption']); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Nomor Halaman Terkunci di Bawah Lembar A4 -->
        <div class="page-number">Page 4 of 4</div>
    </div>

</body>
</html>