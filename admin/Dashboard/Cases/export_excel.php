<?php
require '../../../config/db.php';
require '../vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Get the case number
$casenumber = $_GET['casenumber'] ?? '';
if (!$casenumber) die("No case number.");

$stmt = $pdo->prepare("SELECT cases.*, clients.name AS client_name 
                       FROM cases 
                       JOIN clients ON cases.client_token = clients.token 
                       WHERE cases.casenumber = ?");
$stmt->execute([$casenumber]);
$case = $stmt->fetch();
if (!$case) die("Case not found.");

$identity = strtolower($case['identity_choice']);
$showIdentity = in_array($identity, ['identifiable', 'identifiable_to_bdo_only', 'identified']);

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Case Details');

// Build headers and values
$headers = [];
$values = [];

// Case metadata
$headers[] = 'Case Number';
$values[] = $case['casenumber'];

$headers[] = 'Status';
$values[] = $case['status'];

$headers[] = 'Submitted At';
$values[] = $case['submitted_at'];

$headers[] = 'Last Updated By';
$values[] = $case['updated_by'] . ' on ' . $case['last_updated'];

// Reporter info
$headers[] = 'Concerned Client';
$values[] = $case['client_name'];

$headers[] = 'Affiliation';
$values[] = $case['affiliation'];

$headers[] = 'Anonymity';
$values[] = $case['identity_choice'];

if ($showIdentity) {
    $headers[] = 'Full Name';
    $values[] = $case['full_name'];

    $headers[] = 'Email';
    $values[] = $case['email'];

    $headers[] = 'Phone';
    $values[] = $case['phone'];
}

// Incident details
$headers[] = 'When (Incident)';
$values[] = $case['incident_when'];

$headers[] = 'Where (Incident)';
$values[] = $case['incident_where'];

$headers[] = 'Department / Division';
$values[] = $case['incident_division'];

$headers[] = 'Description of Incident';
$values[] = $case['incident_description'];

$headers[] = 'Feedback';
$values[] = $case['feedback'];

// Write to spreadsheet
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

$col = 'A';
foreach ($values as $value) {
    $sheet->setCellValue($col . '2', $value);
    $col++;
}

// Autofit columns
foreach (range('A', $sheet->getHighestColumn()) as $colLetter) {
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

// Output Excel file
$filename = 'case_' . $case['casenumber'] . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
