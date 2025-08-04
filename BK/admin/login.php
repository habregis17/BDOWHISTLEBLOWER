<?php
session_start();
require '../config/db.php'; // Adjust path if needed

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: login.html?error=missing');
        exit();
    }

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Password is correct, create session
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['last_activity'] = time(); // For timeout management

        header('Location: Dashboard/');
        exit();
    } else {
        header('Location: login.html?error=invalid');
        exit();
    }
} else {
    header('Location: login.html');
    exit();
}
