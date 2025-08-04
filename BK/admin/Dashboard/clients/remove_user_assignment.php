<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../");
    exit();
}

require '../../../config/db.php';

$userId = $_POST['user_id'] ?? '';
$clientToken = $_POST['client_token'] ?? '';

if (!$userId || !$clientToken) {
    // Redirect with error message (optional)
    header("Location: ../?error=missing_data");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM user_client_assignments WHERE user_id = ? AND client_token = ?");
    $stmt->execute([$userId, $clientToken]);

    // Redirect back with success message
    header("Location: ../?success=user_removed");
    exit();
} catch (PDOException $e) {
    // Redirect with error message (optional)
    header("Location: ../?error=db_error");
    exit();
}
