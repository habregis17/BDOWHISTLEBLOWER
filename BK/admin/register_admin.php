<?php
session_start();
require '../config/db.php'; // Adjust path if needed

// Redirect if admin already exists
$stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
if ($stmt->fetchColumn() > 0) {
    die('Admin already exists. Delete this file for security.');
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to generate id: ADM + Year + 3-digit zero-padded count
function generateAdminId($pdo) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM admin_users WHERE id LIKE ?");
    $like = "ADM$year%";
    $stmt->execute([$like]);
    $count = $stmt->fetch()['total'] + 1;
    return 'ADM' . $year . str_pad($count, 3, '0', STR_PAD_LEFT);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm = $_POST['confirm'];

        // Basic validation
        if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif (!preg_match('/[A-Z]/', $password) ||
                  !preg_match('/[a-z]/', $password) ||
                  !preg_match('/[0-9]/', $password)) {
            $error = "Password must contain uppercase, lowercase letters, and numbers.";
        } else {
            // Check if email already exists (should be empty since first admin but good practice)
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email is already registered.";
            } else {
                // Insert new admin
                try {
                    $id= generateAdminId($pdo);
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admin_users (id, name, email, password_hash) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $name, $email, $hash]);
                    $success = "Admin registered successfully with ID <strong>" . htmlspecialchars($id) . "</strong>. You may now log in.";
                    // Reset CSRF token on success
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } catch (PDOException $e) {
                    $error = "Database error: " . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register First Admin</title>
<style>
    body {
        background: #f6f6f6;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .register-box {
        background: white;
        padding: 30px 35px;
        border-radius: 12px;
        width: 420px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        box-sizing: border-box;
    }
    h2 {
        margin-bottom: 25px;
        font-weight: 700;
        color: #c62828;
        text-align: center;
    }
    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #333;
        margin-top: 15px;
    }
    input[type="text"],
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 11px 14px;
        border-radius: 6px;
        border: 1.5px solid #bbb;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
        border-color: #c62828;
        outline: none;
    }
    button {
        width: 100%;
        margin-top: 25px;
        padding: 13px 0;
        background: #c62828;
        color: white;
        border: none;
        border-radius: 7px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background: #a52020;
    }
    .message {
        margin-top: 20px;
        color: green;
        font-weight: 600;
        text-align: center;
    }
    .error {
        margin-top: 20px;
        color: #b00020;
        font-weight: 600;
        text-align: center;
    }
</style>
</head>
<body>
<div class="register-box">
    <h2>Register First Admin</h2>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="message"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

        <label for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Your full name" required autocomplete="name" />

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="example@domain.com" required autocomplete="email" />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="At least 8 characters" required autocomplete="new-password" />

        <label for="confirm">Confirm Password</label>
        <input type="password" id="confirm" name="confirm" placeholder="Re-enter password" required autocomplete="new-password" />

        <button type="submit">Register Admin</button>
    </form>
</div>
</body>
</html>
