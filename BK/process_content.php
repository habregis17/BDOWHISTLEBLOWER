<?php
ob_start(); // prevent headers already sent

if (isset($_GET['consent'])) {
    $token = $_GET['token'] ?? '';

    if ($_GET['consent'] === 'yes') {
        if (!$token) {
            die("Token is missing.");
        }

        require 'config/db.php';

        $stmt = $pdo->prepare("SELECT * FROM clients WHERE token = ?");
        $stmt->execute([$token]);
        $client = $stmt->fetch();

        if (!$client) {
            die("Invalid client token.");
        }

        // ✅ Redirect to the form with token
        header('Location: Submit/user_submit_form.php?token=' . urlencode($token));
        exit;

    } elseif ($_GET['consent'] === 'no') {
        header('Location: Thankyou/Thankyou.php');
        exit;
    }
}

ob_end_flush(); // send output
?>
