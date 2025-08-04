<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

require '../../config/db.php';

function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    return substr(str_shuffle($chars), 0, $length);
}

function generateAdminId($pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM admin_users WHERE id LIKE ?");
    $like = "ADM$year%";
    $stmt->execute([$like]);
    $count = $stmt->fetch()['total'] + 1;
    return 'ADM' . $year . str_pad($count, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $userType = $_POST['user_type'] ?? '';

    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Name and email are required']);
        exit;
    }

    $rawPassword = generateRandomPassword();
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
    $adminId = generateAdminId($pdo);

    $stmt = $pdo->prepare("INSERT INTO admin_users (id, name, email, Telephone, user_type, password_hash) VALUES (?, ?, ?, ?, ?, ?)");

    try {
        $stmt->execute([$adminId, $name, $email, $telephone, $userType, $hashedPassword]);
        echo json_encode(['success' => true, 'password' => $rawPassword]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'error' => 'Email already exists.']);
        } else {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
?>
