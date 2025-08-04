<?php
require 'config/db.php';
require 'utils/mailer.php';

function generateClientToken($length = 8) {
    return 'CL-' . strtoupper(bin2hex(random_bytes($length / 2)));
}

$name = $_POST['name'];
$address = $_POST['address'];
$emails = $_POST['emails'];
$primary_email = $_POST['primary_email'];
$logo = null;

if (!empty($_FILES['logo']['name'])) {
    $target = "logos/" . basename($_FILES["logo"]["name"]);
    move_uploaded_file($_FILES["logo"]["tmp_name"], $target);
    $logo = $target;
}

$token = generateClientToken();

$stmt = $pdo->prepare("INSERT INTO clients (name, address, notification_emails, logo, token, primary_email) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$name, $address, $emails, $logo, $token, $primary_email]);

$pageContent = "<?php header('Location: ../user_submit_form.php?token=$token'); ?>";
file_put_contents("clients/client_$token.php", $pageContent);

$link = "http://localhost/whistleblower-solution/user_submit_form.php?token=$token";
$subject = "Your Whistleblower Submission Link";
$message = "Dear $name,\n\nYour anonymous whistleblower link is ready:\n$link\n\nRegards,\nRegis Tech Team";

sendMail($primary_email, $subject, $message);

echo "Client added and email sent to $primary_email.";
?>