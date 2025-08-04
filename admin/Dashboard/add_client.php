<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

require '../../config/db.php';

// Get POST data from form submission
$name = trim($_POST['name'] ?? '');
$point_of_contact = trim($_POST['point_of_contact'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$description = trim($_POST['description'] ?? '');
$bdo_user_contact = intval($_POST['bdo_user_contact'] ?? 0);
$client_contact = intval($_POST['client_contact'] ?? 0);

// if (!$name || !$point_of_contact || !$telephone || !$bdo_user_contact || !$client_contact) {
//   echo json_encode(['success' => false, 'error' => 'Required fields missing']);
//   exit;
// }

// Generate unique identifier - random 32 chars alphanumeric
function generateUniqueId($pdo) {
  $tries = 0;
  do {
    // Generate a 32-character token (16 bytes = 128-bit)
    $rawBytes = random_bytes(16);
    $id = bin2hex($rawBytes); // 32 hex chars

    // Optionally: Add a prefix like "cli_" or a timestamp
    // $id = 'cli_' . bin2hex($rawBytes);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE token = ?");
    $stmt->execute([$id]);
    $exists = $stmt->fetchColumn() > 0;
    $tries++;
    if ($tries > 10) break;
  } while ($exists);
  
  return $id;
}

$unique_id = generateUniqueId($pdo);

// Insert client
$stmt = $pdo->prepare("INSERT INTO clients (name, email, Telephone, Description, BDO_contact, Client_Contact, token) VALUES (?, ?, ?, ?, ?, ?, ?)");
$success = $stmt->execute([
  $name, $point_of_contact, $telephone, $description,
  $bdo_user_contact, $client_contact, $unique_id
]);

echo json_encode(['success' => $success]);
