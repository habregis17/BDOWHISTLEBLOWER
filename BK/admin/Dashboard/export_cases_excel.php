<?php
require '../../config/db.php';
require 'vendor/autoload.php';
require_once 'dompdf/autoload.inc.php';

 // PhpSpreadsheet and Dompdf

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

$systemLogo = 'https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg';

$startDate = $_GET['startDate'] ?? '';
$endDate = $_GET['endDate'] ?? '';
$exportType = $_GET['export'] ?? '';

$hasDates = !empty($startDate) && !empty($endDate);
$params = [];
$where = '';

if ($hasDates) {
    $where = "WHERE DATE(cases.submitted_at) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];
}

$stmt = $pdo->prepare("
    SELECT cases.*, clients.name AS client_name
    FROM cases
    JOIN clients ON cases.client_token = clients.token
    $where
    ORDER BY cases.submitted_at DESC
");
$stmt->execute($params);
$cases = $stmt->fetchAll();

if (!$cases) {
    echo "<div style='padding: 20px; background: #ffeeba; color: #856404; border: 1px solid #ffeeba; font-family: sans-serif;'>⚠️ No cases found in this date range.</div>";
    exit;
}

// =============== EXCEL EXPORT ===============
if ($exportType === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Cases');

    $headers = [
        'Case Number', 'Status', 'Submitted At', 'Last Updated By', 'Client',
        'Affiliation', 'Anonymity', 'Full Name', 'Email', 'Phone',
        'When', 'Where', 'Division', 'Description', 'Feedback'
    ];
    $sheet->fromArray($headers, null, 'A1');

    $row = 2;
    foreach ($cases as $case) {
        $identity = strtolower($case['identity_choice']);
        $showIdentity = in_array($identity, ['identifiable', 'identifiable_to_bdo_only', 'identified']);

        $sheet->fromArray([
            $case['casenumber'],
            $case['status'],
            $case['submitted_at'],
            $case['updated_by'] . ' on ' . $case['last_updated'],
            $case['client_name'],
            $case['affiliation'],
            $case['identity_choice'],
            $showIdentity ? $case['full_name'] : '',
            $showIdentity ? $case['email'] : '',
            $showIdentity ? $case['phone'] : '',
            $case['incident_when'],
            $case['incident_where'],
            $case['incident_division'],
            $case['incident_description'],
            $case['feedback']

        ], null, "A{$row}");
        $row++;
    }

    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'cases_export_' . ($hasDates ? "{$startDate}_to_{$endDate}" : 'all') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// =============== PDF EXPORT ===============
if ($exportType === 'pdf') {
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .case { margin-bottom: 20px; page-break-inside: avoid; }
            .case h2 { background: #ED1A3B; color: white; padding: 10px; }
            .case table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .case td, .case th { padding: 6px 10px; border: 1px solid #ddd; }
            .logo-row { display: flex; justify-content: space-between; align-items: center; }
  .logo-row img { height: 60px; max-width: 45%; }
  footer { text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ccc; padding-top: 12px; margin-top: 40px; }
        </style>
    </head>
    <body>';
    
    foreach ($cases as $case) {
        $identity = strtolower($case['identity_choice']);
        $showIdentity = in_array($identity, ['identifiable', 'identifiable_to_bdo_only', 'identified']);

        $html .= '
        <div class="case">
              
  </div>
            <h2>Case: ' . htmlspecialchars($case['casenumber']) . '</h2>
            <table>
                <tr><th>Status</th><td>' . htmlspecialchars($case['status']) . '</td></tr>
                <tr><th>Submitted At</th><td>' . htmlspecialchars($case['submitted_at']) . '</td></tr>
                <tr><th>Client</th><td>' . htmlspecialchars($case['client_name']) . '</td></tr>
                <tr><th>Affiliation</th><td>' . htmlspecialchars($case['affiliation']) . '</td></tr>
                <tr><th>Anonymity</th><td>' . htmlspecialchars($case['identity_choice']) . '</td></tr>';

        if ($showIdentity) {
            $html .= '
                <tr><th>Full Name</th><td>' . htmlspecialchars($case['full_name']) . '</td></tr>
                <tr><th>Email</th><td>' . htmlspecialchars($case['email']) . '</td></tr>
                <tr><th>Phone</th><td>' . htmlspecialchars($case['phone']) . '</td></tr>';
        }

        $html .= '
                <tr><th>When</th><td>' . htmlspecialchars($case['incident_when']) . '</td></tr>
                <tr><th>Where</th><td>' . htmlspecialchars($case['incident_where']) . '</td></tr>
                <tr><th>Division</th><td>' . htmlspecialchars($case['incident_division']) . '</td></tr>
                <tr><th>Description</th><td>' . nl2br(htmlspecialchars($case['incident_description'])) . '</td></tr>
                <tr><th>Feedback</th><td>' . nl2br(htmlspecialchars($case['feedback'])) . '</td></tr>
            </table>
            <footer>
  &copy; ' . date('Y') . ' Whistleblower Solution - BDO East Africa(Rwanda) Ltd. All rights reserved.
</footer>
        </div>';
    }

    $html .= '</body></html>';

    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'cases_export_' . ($hasDates ? "{$startDate}_to_{$endDate}" : 'all') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

// =============== DEFAULT ===============
echo "Unknown export type requested.";
exit;
