<?php
require '../config/db.php';
require '../utils/casenumber.php';

$token = $_POST['token'] ?? '';
if (!$token) die("Token missing.");

// Use client token from form
$client_token = $token;

// Get form inputs
$affiliation         = $_POST['affiliation'] ?? '';
$identity_choice     = $_POST['identity_choice'] ?? '';
$full_name           = $_POST['fullname'] ?? null;
$email               = $_POST['contact_email'] ?? null;
$phone               = $_POST['phone'] ?? null;
$department          = $_POST['department'] ?? null;
$incident_description = $_POST['incident_description'] ?? '';
$incident_when       = $_POST['incident_when'] ?? null;
$incident_where      = $_POST['incident_where'] ?? null;
$incident_division   = $_POST['incident_division'] ?? null;



// ✅ Generate proper case number using client name via token
$case_number = generateCaseNumber($pdo, $client_token);

// Handle incident_evidence uploads
$uploaded_files = [];
$target_dir = "Uploads/";
if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

if (isset($_FILES['incident_evidence'])) {
    $files = $_FILES['incident_evidence'];

    if (is_array($files['name'])) {
        foreach ($files['name'] as $index => $filename) {
            $tmp_name = $files['tmp_name'][$index];
            $error = $files['error'][$index];

            if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmp_name)) {
                $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($filename));
                $filepath = $target_dir . $uniqueName;
                if (move_uploaded_file($tmp_name, $filepath)) {
                    $uploaded_files[] = $filepath;
                }
            }
        }
    } else {
        $filename = $files['name'];
        $tmp_name = $files['tmp_name'];
        $error = $files['error'];

        if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmp_name)) {
            $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($filename));
            $filepath = $target_dir . $uniqueName;
            if (move_uploaded_file($tmp_name, $filepath)) {
                $uploaded_files[] = $filepath;
            }
        }
    }
}

$files_json = json_encode($uploaded_files);


// ✅ Generate proper case number using client name via token
$case_number = generateCaseNumber($pdo, $client_token);

// Insert into DB
$stmt = $pdo->prepare("INSERT INTO cases (
    casenumber,
    client_token,
    affiliation,
    identity_choice,
    full_name,
    email,
    phone,
    department,
    incident_date,
    incident_description,
    files,
    incident_when,
    incident_where,
    incident_division
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    $case_number,
    $client_token,
    $affiliation,
    $identity_choice,
    $full_name,
    $email,
    $phone,
    $department,
    null, // incident_date not collected in your current form, set as NULL
    $incident_description,
    $files_json,
    $incident_when,
    $incident_where,
    $incident_division
]);

header("Location: ../Thankyou/?casenumber=" . urlencode($case_number));
exit();
?> 