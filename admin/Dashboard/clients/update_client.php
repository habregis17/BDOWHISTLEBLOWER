<?php
require '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method');
}

$client_token = $_POST['client_token'] ?? null;
if (!$client_token) {
    die('Client token is missing');
}

// Get posted client fields
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$users_to_assign = $_POST['users_to_assign'] ?? []; // array of user IDs

// Basic validation
if (!$name || !$email) {
    die('Name and Email are required');
}

// 1. Update client details using token
$updateStmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, Telephone = ? WHERE token = ?");
$updateStmt->execute([$name, $email, $phone, $client_token]);

// 2. Insert new user assignments, avoiding duplicates
if (!empty($users_to_assign) && is_array($users_to_assign)) {
    // Fetch existing assigned user_ids for this client_token
    $existingStmt = $pdo->prepare("SELECT user_id FROM user_client_assignments WHERE client_token = ?");
    $existingStmt->execute([$client_token]);
    $existingUserIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);

    // Prepare insert statement
    $insertStmt = $pdo->prepare("INSERT INTO user_client_assignments (client_token, user_id) VALUES (?, ?)");

    foreach ($users_to_assign as $user_id) {
        if (!in_array($user_id, $existingUserIds)) {
            $insertStmt->execute([$client_token, $user_id]);
        }
    }
}

// Redirect back to edit page with token parameter
header("Location: ../?token=" . urlencode($client_token));
exit;
