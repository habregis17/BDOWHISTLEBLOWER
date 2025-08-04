<?php
require '../../config/db.php';

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM clients WHERE token = ?");
$stmt->execute([$token]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

$usersStmt = $pdo->prepare("
  SELECT admin_users.id, admin_users.name, admin_users.email
  FROM client_user_assignments
  JOIN admin_users ON admin_users.id = client_user_assignments.user_id
  WHERE client_user_assignments.client_token = ?
");
$usersStmt->execute([$token]);
$assignedUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  "client" => $client,
  "assignedUsers" => $assignedUsers
]);
?>
