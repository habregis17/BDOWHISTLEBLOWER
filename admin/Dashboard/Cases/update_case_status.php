<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../");
    exit();
}

require '../../../config/db.php';

$case_number = $_POST['case_number'] ?? '';
$status = $_POST['status'] ?? '';
$feedback = $_POST['feedback'] ?? '';
$updated_by = $_SESSION['email'] ?? 'Unknown';

if (!$case_number || !$status) {
    die("Missing required data.");
}

if ($status === 'Closed' && empty(trim($feedback))) {
    die("Feedback is required when status is Closed.");
}

// Update query now includes updated_by
$stmt = $pdo->prepare("UPDATE cases SET status = ?, feedback = ?, updated_by = ? WHERE casenumber = ?");
$stmt->execute([$status, $feedback, $updated_by, $case_number]);

header("Location:../Cases/?casenumber=" . urlencode($case_number));
exit();
