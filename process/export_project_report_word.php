<?php
include '../layouts/auth_and_config.php';

// 1. Ekstrak Data POST & Foto
$postData = [];
if (!empty($_POST['payload_data'])) {
    $postData = json_decode($_POST['payload_data'], true) ?? [];
}

$evidenceList = [];
if (!empty($_POST['evidence_payload'])) {
    $evidenceList = json_decode($_POST['evidence_payload'], true) ?? [];
}

$projectName    = $postData['project_name'] ?? 'Project Report';
$machineName    = $postData['machine_name'] ?? '-';
$plantName      = $postData['plant_name'] ?? '-';
$preparedBy     = !empty($postData['prepared_by']) ? $postData['prepared_by'] : 'Automation Team';
$approvedBy     = !empty($postData['approved_by']) ? $postData['approved_by'] : 'Teguh Imanto';
$backgroundText = $postData['background_text'] ?? '';
$selectedIds    = $postData['selected_ids'] ?? '';

// Ekstrak Aktivitas Persiapan & Eksekusi
$prepActivities = $postData['prep_activity'] ?? [];
$prepDays       = $postData['prep_days'] ?? [];
$prepProgress   = $postData['prep_progress'] ?? [];
$prepStatuses   = $postData['prep_status'] ?? [];
$prepNotes      = $postData['prep_notes'] ?? [];
$prepStartDates = $postData['prep_start_date'] ?? [];
$prepEndDates   = $postData['prep_end_date'] ?? [];

$totalPrepProgress = array_sum(array_map('intval', $prepProgress));

$execActivities = $postData['exec_activity'] ?? [];
$execProgress   = $postData['exec_progress'] ?? [];
$execStatuses   = $postData['exec_status'] ?? [];
$execStartDates = $postData['exec_start_date'] ?? [];
$execEndDates   = $postData['exec_end_date'] ?? [];
$execDays       = $postData['exec_days'] ?? [];
$execNotes      = $postData['exec_notes'] ?? [];

$totalExecProgress = 0;
foreach ($execProgress as $rId => $progArray) {
    foreach ($progArray as $val) {
        $totalExecProgress += (int)$val;
    }
}
$totalProgress = min(100, $totalPrepProgress + $totalExecProgress);

// Ambil tanggal database jika ada
$startDateMin = null;
$endDateMax   = null;

if (!empty($selectedIds)) {
    $idArray = array_filter(array_map('intval', explode(',', $selectedIds)));
    if (!empty($idArray)) {
        $inClause = implode(',', $idArray);
        $res = mysqli_query($conn, "SELECT date_log, end_date FROM tb_daily_reports WHERE report_id IN ($inClause) ORDER BY date_log ASC");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $dStart = $row['date_log'];
                $dEnd   = !empty($row['end_date']) ? $row['end_date'] : $dStart;
                if ($dStart && (!$startDateMin || strtotime($dStart) < strtotime($startDateMin))) $startDateMin = $dStart;
                if ($dEnd && (!$endDateMax || strtotime($dEnd) > strtotime($endDateMax))) $endDateMax = $dEnd;
            }
        }
    }
}

// Siapkan Logo JIS Automation dalam format Base64 untuk Word
$logoPath = dirname(__DIR__) . '/image/jis.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}

// 2. Set Header Download Word
$cleanFileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $projectName);
$fileName = "Laporan_" . $cleanFileName . "_" . date('Ymd') . ".doc";

header("Content-Type: application/vnd.ms-word; charset=UTF-8");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment; filename=\"$fileName\"");
?>
<html xmlns:o='urn:schemas-microsoft-com:office:office'
      xmlns:w='urn:schemas-microsoft-com:office:word'
      xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($projectName); ?></title>
<!--[if gte mso 9]>
<xml>
 <w:WordDocument>
  <w:View>Print</w:View>
  <w:Zoom>100</w:Zoom>
  <w:DoNotOptimizeForBrowser/>
 </w:WordDocument>
</xml>
<![endif]-->
<style>
    @page Section1 {
        size: 21.0cm 29.7cm;
        margin: 2.0cm 2.0cm 2.0cm 2.0cm;
        mso-page-orientation: portrait;
    }
    div.Section1 { page: Section1; }
    body {
        font-family: Calibri, Arial, sans-serif;
        font-size: 10pt;
        color: #1e293b;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10pt;
    }
    th, td {
        border: 1px solid #94a3b8;
        padding: 4.5pt 6.5pt;
        font-size: 9pt;
    }
    th {
        background-color: #f1f5f9;
        font-weight: bold;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold; }
    .page-break {
        page-break-before: always;
        mso-break-type: section-break;
        clear: both;
    }
    h1 { font-size: 22pt; color: #0f172a; margin-bottom: 4pt; }
    h2 { font-size: 13pt; color: #64748b; font-weight: normal; margin: 0 0 20pt 0; }
    h3 { color: #0f172a; margin: 12pt 0 5pt 0; font-size: 10.5pt; }
</style>
</head>
<body>
<div class="Section1">

    <!-- HALAMAN 1: COVER -->
    <br><br>
    <h1><?php echo htmlspecialchars($projectName); ?></h1>
    <h2>Project Report</h2>

    <br><br><br>
    <div style="text-align: center; margin: 30pt 0;">
        <p style="font-size: 14pt; font-weight: bold; color: #0891b2; letter-spacing: 2px;">AUTOMATION SYSTEM REPORT</p>
    </div>
    <br><br><br>

    <!-- ================= HALAMAN 1: COVER ================= -->
    <table style="width: 100%; border: none; height: 100%; margin: 0; padding: 0;">
        <tr style="border: none;">
            <td style="border: none; padding: 0; vertical-align: top;">

                <!-- Title & Subtitle -->
                <div style="margin-top: 10pt; margin-bottom: 30pt;">
                    <h1 style="font-size: 32pt; font-weight: normal; color: #0f172a; margin: 0; line-height: 1.1; font-family: 'Segoe UI', Arial, sans-serif;">
                        <?php echo htmlspecialchars($projectName); ?>
                    </h1>
                    <h2 style="font-size: 16pt; font-weight: normal; color: #64748b; margin-top: 6pt; margin-bottom: 0; font-family: 'Segoe UI', Arial, sans-serif;">
                        <?php echo htmlspecialchars($report_sub_title ?? 'Project Report'); ?>
                    </h2>
                </div>

                <!-- Logo JIS Automation di Tengah -->
                <div style="text-align: center; margin: 40pt 0;">
                    <?php if (!empty($logoBase64)): ?>
                        <img src="<?php echo $logoBase64; ?>" style="width: 220pt; height: auto;" alt="Logo JIS Automation">
                    <?php endif; ?>
                </div>

                <!-- Tabel Approval / Signatures -->
                <div style="margin-top: 50pt;">
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #94a3b8; font-size: 8.5pt;">
                        <thead>
                            <tr style="background-color: #f1f5f9;">
                                <th style="border: 1px solid #94a3b8; padding: 5pt 8pt; text-align: left; width: 33%; font-weight: bold; color: #475569;">PREPARED BY</th>
                                <th style="border: 1px solid #94a3b8; padding: 5pt 8pt; text-align: left; width: 33%; font-weight: bold; color: #475569;">TITLE</th>
                                <th style="border: 1px solid #94a3b8; padding: 5pt 8pt; text-align: left; width: 33%; font-weight: bold; color: #475569;">DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="border: 1px solid #94a3b8; padding: 6pt 8pt; font-weight: bold; color: #0f172a;"><?php echo htmlspecialchars($preparedBy); ?></td>
                                <td style="border: 1px solid #94a3b8; padding: 6pt 8pt; color: #475569;">Automation Engineer</td>
                                <td style="border: 1px solid #94a3b8; padding: 6pt 8pt; color: #0f172a;"><?php echo date('d F Y', strtotime($startDateMin ?: date('Y-m-d'))); ?></td>
                            </tr>
                            <tr style="background-color: #f1f5f9;">
                                <th style="border: 1px solid #94a3b8; padding: 5pt 8pt; text-align: left; font-weight: bold; color: #475569;">APPROVED BY</th>
                                <th style="border: 1px solid #94a3b8; padding: 5pt 8pt; text-align: left; font-weight: bold; color: #475569;">TITLE</th>
                                <th style="border: 1px solid #94a3b8; padding: 5pt 8pt; text-align: left; font-weight: bold; color: #475569;">DATE</th>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #94a3b8; padding: 6pt 8pt; font-weight: bold; color: #0f172a;"><?php echo htmlspecialchars($approvedBy); ?></td>
                                <td style="border: 1px solid #94a3b8; padding: 6pt 8pt; color: #475569;">Department Head</td>
                                <td style="border: 1px solid #94a3b8; padding: 6pt 8pt; color: #0f172a;"><?php echo date('d F Y', strtotime($endDateMax ?: date('Y-m-d'))); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Footer Company Info -->
                    <div style="font-size: 8pt; color: #475569; line-height: 1.4; margin-top: 14pt;">
                        <b style="color: #1e293b; letter-spacing: 0.5px;">JOINT INSTRUMENT AND AUTOMATION (JIS) DEPARTMENT</b><br>
                        <b style="color: #334155; letter-spacing: 0.5px;">PT GAJAH TUNGGAL TBK</b><br>
                        Kompleks Industri Gajah Tunggal<br>
                        Kel. Pasir Jaya, Kec. Jatiuwung, Tangerang 15135<br>
                        <span style="font-weight: bold; margin-top: 2pt; display: inline-block;"><?php echo date('Y'); ?></span>
                    </div>
                </div>

            </td>
        </tr>
    </table>

    <!-- Pemisah Halaman ke Table of Contents -->
    <br clear="all" style="page-break-before: always; mso-break-type: section-break;" />

    <div style="font-size: 8.5pt; color: #475569; margin-top: 20pt;">
        <b>JOINT INSTRUMENT AND AUTOMATION (JIS) DEPARTMENT</b><br>
        <b>PT GAJAH TUNGGAL TBK</b><br>
        Kompleks Industri Gajah Tunggal, Tangerang 15135<br>
        <?php echo date('Y'); ?>
    </div>

    <!-- HALAMAN 2: TABLE OF CONTENTS -->
    <br clear="all" class="page-break" />

    <h3 style="text-decoration: underline;">TABLE OF CONTENTS</h3>
    <br>
    <table style="border: none;">
        <tr style="border: none;">
            <td style="border: none; border-bottom: 1px dotted #94a3b8; font-weight: bold;">A. OVERVIEW PROJECT</td>
            <td style="border: none; border-bottom: 1px dotted #94a3b8; text-align: right; width: 10%;">3</td>
        </tr>
        <tr style="border: none;">
            <td style="border: none; border-bottom: 1px dotted #94a3b8; font-weight: bold; padding-top: 10pt;">B. BACKGROUND (LATAR BELAKANG)</td>
            <td style="border: none; border-bottom: 1px dotted #94a3b8; text-align: right; width: 10%;">3</td>
        </tr>
        <tr style="border: none;">
            <td style="border: none; border-bottom: 1px dotted #94a3b8; font-weight: bold; padding-top: 10pt;">C. JOB ACTIVITY</td>
            <td style="border: none; border-bottom: 1px dotted #94a3b8; text-align: right; width: 10%;">3</td>
        </tr>
        <tr style="border: none;">
            <td style="border: none; border-bottom: 1px dotted #94a3b8; font-weight: bold; padding-top: 10pt;">D. RESULT & EVIDENCE</td>
            <td style="border: none; border-bottom: 1px dotted #94a3b8; text-align: right; width: 10%;">4</td>
        </tr>
    </table>

    <!-- HALAMAN 3: OVERVIEW & JOB ACTIVITY -->
    <br clear="all" class="page-break" />

    <h3>A. OVERVIEW PROJECT</h3>
    <table>
        <tr>
            <td width="28%" class="font-bold" style="background-color: #f8fafc;">Nama Project</td>
            <td><?php echo htmlspecialchars($projectName); ?></td>
        </tr>
        <tr>
            <td class="font-bold" style="background-color: #f8fafc;">Plant / Mesin</td>
            <td><?php echo htmlspecialchars($plantName . ' / ' . $machineName); ?></td>
        </tr>
        <tr>
            <td class="font-bold" style="background-color: #f8fafc;">Start Date</td>
            <td><?php echo $startDateMin ? date('d F Y', strtotime($startDateMin)) : '-'; ?></td>
        </tr>
        <tr>
            <td class="font-bold" style="background-color: #f8fafc;">End Date</td>
            <td><?php echo $endDateMax ? date('d F Y', strtotime($endDateMax)) : '-'; ?></td>
        </tr>
        <tr>
            <td class="font-bold" style="background-color: #f8fafc;">Overall Progress</td>
            <td class="font-bold" style="color: #047857;"><?php echo $totalProgress; ?>%</td>
        </tr>
        <tr>
            <td class="font-bold" style="background-color: #f8fafc;">PIC / Prepared By</td>
            <td><?php echo htmlspecialchars($preparedBy); ?></td>
        </tr>
    </table>

    <h3>B. BACKGROUND (LATAR BELAKANG)</h3>
    <table>
        <tr>
            <td style="padding: 7pt;"><?php echo nl2br(htmlspecialchars($backgroundText ?: '-')); ?></td>
        </tr>
    </table>

    <h3>C. JOB ACTIVITY</h3>
    <table>
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="35%">Activity</th>
                <th width="8%" class="text-center">Days</th>
                <th width="14%" class="text-center">Start Date</th>
                <th width="14%" class="text-center">End Date</th>
                <th width="8%" class="text-center">Prog</th>
                <th width="8%" class="text-center">Status</th>
                <th width="8%">Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($prepActivities)): ?>
                <tr style="background-color: #ecfdf5; font-weight: bold;">
                    <td class="text-center">1</td>
                    <td colspan="7">Project Preparation</td>
                </tr>
                <?php foreach ($prepActivities as $idx => $actName): 
                    $startDate = !empty($prepStartDates[$idx]) ? date('d M Y', strtotime($prepStartDates[$idx])) : '-';
                    $endDate   = !empty($prepEndDates[$idx]) ? date('d M Y', strtotime($prepEndDates[$idx])) : '-';
                ?>
                    <tr>
                        <td class="text-center">1.<?php echo ($idx + 1); ?></td>
                        <td><?php echo htmlspecialchars($actName); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($prepDays[$idx] ?? '1'); ?></td>
                        <td class="text-center"><?php echo $startDate; ?></td>
                        <td class="text-center"><?php echo $endDate; ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($prepProgress[$idx] ?? '0'); ?>%</td>
                        <td class="text-center"><?php echo htmlspecialchars($prepStatuses[$idx] ?? 'OK'); ?></td>
                        <td><?php echo htmlspecialchars($prepNotes[$idx] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td colspan="5" class="text-right">Preparation Progress Sub-total</td>
                    <td class="text-center"><?php echo $totalPrepProgress; ?>%</td>
                    <td class="text-center">OK</td>
                    <td></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($execActivities)): ?>
                <tr style="background-color: #fffbeb; font-weight: bold;">
                    <td class="text-center">2</td>
                    <td colspan="7">Project Execution</td>
                </tr>
                <?php 
                $execNo = 1;
                foreach ($execActivities as $rId => $acts): 
                    foreach ($acts as $idx => $actName):
                        $rawStart  = $execStartDates[$rId][$idx] ?? '';
                        $rawEnd    = $execEndDates[$rId][$idx] ?? '';
                        $startDate = !empty($rawStart) ? date('d M Y', strtotime($rawStart)) : '-';
                        $endDate   = !empty($rawEnd) ? date('d M Y', strtotime($rawEnd)) : '-';
                ?>
                    <tr>
                        <td class="text-center">2.<?php echo $execNo++; ?></td>
                        <td><?php echo htmlspecialchars($actName); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($execDays[$rId][$idx] ?? '1'); ?></td>
                        <td class="text-center"><?php echo $startDate; ?></td>
                        <td class="text-center"><?php echo $endDate; ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($execProgress[$rId][$idx] ?? '0'); ?>%</td>
                        <td class="text-center"><?php echo htmlspecialchars($execStatuses[$rId][$idx] ?? 'OK'); ?></td>
                        <td><?php echo htmlspecialchars($execNotes[$rId][$idx] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; endforeach; ?>
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td colspan="5" class="text-right">Execution Progress Sub-total</td>
                    <td class="text-center"><?php echo $totalExecProgress; ?>%</td>
                    <td class="text-center">OK</td>
                    <td></td>
                </tr>
            <?php endif; ?>

            <tr style="background-color: #eff6ff; font-weight: bold;">
                <td colspan="5" class="text-right">OVERALL PROGRESS</td>
                <td class="text-center"><?php echo $totalProgress; ?>%</td>
                <td class="text-center">OK</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- HALAMAN 4: EVIDENCE -->
    <br clear="all" class="page-break" />

    <h3>D. RESULT & EVIDENCE</h3>
    <table>
        <tr style="background-color: #f1f5f9;">
            <th style="padding: 6pt; text-align: center;">FOTO ACTIVITY & EVIDENCE</th>
        </tr>
        <tr>
            <td style="padding: 10pt;">
                <?php if (empty($evidenceList)): ?>
                    <p style="text-align: center; color: #94a3b8; font-style: italic;">- Tidak ada lampiran foto evidence yang diunggah -</p>
                <?php else: ?>
                    <table style="border: none;">
                        <?php foreach (array_chunk($evidenceList, 2) as $rowPair): ?>
                            <tr style="border: none;">
                                <?php foreach ($rowPair as $ev): ?>
                                    <td width="50%" style="border: 1px solid #e2e8f0; padding: 6pt; text-align: center; vertical-align: top;">
                                        <img src="<?php echo $ev['src']; ?>" width="260" style="max-width: 260pt; max-height: 180pt;"><br>
                                        <span style="font-size: 8pt; color: #475569; display: block; margin-top: 4pt;"><?php echo htmlspecialchars($ev['caption']); ?></span>
                                    </td>
                                <?php endforeach; ?>
                                <?php if (count($rowPair) === 1): ?>
                                    <td width="50%" style="border: none;"></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </td>
        </tr>
    </table>

</div>
</body>
</html>