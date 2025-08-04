<?php
require '../config/db.php';
require '../utils/casenumber.php';
require_once '../admin/Dashboard/dompdf/autoload.inc.php';
require '../utils/vendor/autoload.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for dev (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Optional: error log file path
$logFile = __DIR__ . '/../logs/error_log.txt';
function logError($message) {
    global $logFile;
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, $logFile);
}

$token = $_POST['token'] ?? '';
if (!$token) {
    die("Token missing.");
}

// Form inputs
$client_token         = $token;
$affiliation          = $_POST['affiliation'] ?? '';
$identity_choice      = $_POST['identity_choice'] ?? '';
$full_name            = $_POST['fullname'] ?? null;
$email                = $_POST['contact_email'] ?? null;
$phone                = $_POST['phone'] ?? null;
$department           = $_POST['department'] ?? null;
$incident_description = $_POST['incident_description'] ?? '';
$incident_when        = $_POST['incident_when'] ?? null;
$incident_where       = $_POST['incident_where'] ?? null;
$incident_division    = $_POST['incident_division'] ?? null;

// Generate case number
try {
    $case_number = generateCaseNumber($pdo, $client_token);
    if (!$case_number) throw new Exception("Failed to generate case number.");
} catch (Exception $e) {
    logError("Case Number Error: " . $e->getMessage());
    die("Error generating case number.");
}

// Handle file uploads
$uploaded_files = [];
$target_dir = __DIR__ . "/Uploads/";
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        logError("Failed to create uploads directory.");
        die("Error setting up file uploads.");
    }
}

try {
    if (isset($_FILES['incident_evidence'])) {
        $files = $_FILES['incident_evidence'];
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmp_names = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];

        foreach ($names as $index => $filename) {
            $tmp_name = $tmp_names[$index];
            $error = $errors[$index];

            if ($error === UPLOAD_ERR_OK && is_uploaded_file($tmp_name)) {
                $mime = mime_content_type($tmp_name);
                if (!in_array($mime, $allowedTypes)) {
                    logError("File rejected due to invalid mime type: $mime");
                    continue; // skip disallowed types
                }

                $uniqueName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($filename));
                $filepath = $target_dir . $uniqueName;
                if (move_uploaded_file($tmp_name, $filepath)) {
                    chmod($filepath, 0644);
                    $uploaded_files[] = $filepath;
                } else {
                    logError("Failed to move uploaded file: $filename");
                }
            }
        }
    }
} catch (Exception $e) {
    logError("Upload Error: " . $e->getMessage());
    // Don't block the process, continue without files
}

$files_json = json_encode($uploaded_files);

// Save to DB
try {
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
        null, // incident_date
        $incident_description,
        $files_json,
        $incident_when,
        $incident_where,
        $incident_division
    ]);
} catch (PDOException $e) {
    logError("DB Insert Error: " . $e->getMessage());
    die("Error saving report. Please try again later.");
}

$casenumber = $case_number;
if (!$casenumber) {
    die("No case number.");
}

// Fetch full case data for email/pdf
try {
    $stmt = $pdo->prepare("SELECT cases.*, clients.name AS client_name 
                           FROM cases 
                           JOIN clients ON cases.client_token = clients.token 
                           WHERE cases.casenumber = ?");
    $stmt->execute([$casenumber]);
    $case = $stmt->fetch();
    if (!$case) throw new Exception("Case not found.");
} catch (Exception $e) {
    logError("Fetch Case Error: " . $e->getMessage());
    die("Error fetching case details.");
}

// Send email if identity choice requires and email valid
if (in_array($identity_choice, ['Identifiable', 'Identifiable to BDO only']) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $identity = strtolower($case['identity_choice']);
        $showIdentity = in_array($identity, ['identifiable', 'identifiable to bdo only', 'identified']);
        $systemLogo = 'https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg';

        $html = '
        <!DOCTYPE html>
        <html><head><meta charset="UTF-8" /><title>Case Report ' . htmlspecialchars($case['casenumber']) . '</title><style>
          body { font-family: "Trebuchet MS", sans-serif; color: #333; margin: 30px; }
          header { text-align: center; margin-bottom: 30px; }
          .logo-row { display: flex; justify-content: space-between; align-items: center; }
          .logo-row img { height: 60px; max-width: 45%; }
          h1 { color: #ED1A3B; margin: 15px 0 0; }
          h2 { margin: 0; font-weight: normal; color: #555; font-size: 16px; }
          section { margin-bottom: 25px; }
          label { display: block; font-weight: bold; margin: 10px 0 5px; font-size: 13px; }
          .field { background: #f7f7f7; border: 1px solid #ccc; border-radius: 4px; padding: 8px; font-size: 12px; }
          textarea.field { white-space: pre-wrap; }
          .status-box { background: #00A86B; color: white; font-weight: bold; padding: 8px; border-radius: 5px; text-align: center; }
          footer { text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ccc; padding-top: 12px; margin-top: 40px; }
        </style></head><body>
        <header>
          <div class="logo-row"><img src="' . $systemLogo . '" alt="System Logo" /></div>
          <h1>Whistleblower Case Management System</h1>
          <h2>Case #: ' . htmlspecialchars($case['casenumber']) . '</h2>
        </header>
        <section>
          <label>Submitted At</label>
          <div class="field">' . htmlspecialchars($case['submitted_at']) . '</div>
        </section>
        <section>
          <h3>Reporter Information</h3>
          <label>Concerned Client</label>
          <div class="field">' . htmlspecialchars($case['client_name']) . '</div>
          <label>Affiliation</label>
          <div class="field">' . htmlspecialchars($case['affiliation']) . '</div>
          <label>Anonymity</label>
          <div class="field">' . htmlspecialchars($case['identity_choice']) . '</div>';

        if ($showIdentity) {
            $html .= '
            <label>Full Name</label>
            <div class="field">' . htmlspecialchars($case['full_name']) . '</div>
            <label>Email</label>
            <div class="field">' . htmlspecialchars($case['email']) . '</div>
            <label>Telephone</label>
            <div class="field">' . htmlspecialchars($case['phone']) . '</div>';
        }

        $html .= '</section><section>
          <h3>Incident Details</h3>
          <label>When did the incident(s) take place?</label>
          <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_when'])) . '</div>
          <label>Where did the incident(s) take place?</label>
          <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_where'])) . '</div>
          <label>Which department/site does it concern?</label>
          <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_division'])) . '</div>
          <label>Description of the incident(s)</label>
          <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_description'])) . '</div>
          
        </section>
        <footer>&copy; ' . date('Y') . ' Whistleblower Solution - BDO East Africa (Rwanda) Ltd. All rights reserved.</footer>
        </body></html>';

        // Generate PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save PDF temporarily
        $tempPdfPath = sys_get_temp_dir() . "/case_$casenumber.pdf";
        file_put_contents($tempPdfPath, $dompdf->output());

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.office365.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'alert.rw@bdo-ea.com';
        $mail->Password   = 'Bdo@2023!';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('alert.rw@bdo-ea.com', 'BDO Whistleblowing Platform');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Your Case Submission Receipt - Ref #$casenumber";
        $mail->Body = 
           '
<div style="font-family: Trebuchet MS, sans-serif; padding: 20px; color: #333; max-width: 600px; margin: auto;">
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="' . $systemLogo . '" alt="BDO Logo" style="max-width: 200px; height: auto;">
    </div>
    
    <h2 style="color: #ED1A3B;">Thank you for your report</h2>
    <p>Dear ' . htmlspecialchars($case['full_name']) . ',</p>

    <p>We have successfully received your report. Your case reference number is:</p>
    <h3 style="color: #ED1A3B;">' . htmlspecialchars($casenumber) . '</h3>

    <p>We have attached a copy of your case receipt for your reference.</p>
    <p>Should you have any questions or need further assistance, feel free to contact us.</p>

    <br/>
    <p>Best regards,<br/><strong>BDO Whistleblowing Support Team</strong></p>

    <hr style="margin-top: 30px;">
    <small style="color: #999;">This is an automated message. Please do not reply.</small>
</div>';
        $mail->AltBody = "Thank you for your report. Case Ref: $casenumber. See attached PDF.";
        $mail->addAttachment($tempPdfPath, "case_$casenumber.pdf");

        $mail->send();

        // Delete temp PDF
        unlink($tempPdfPath);

    } catch (Exception $e) {
        logError("Email/PDF Error: " . $e->getMessage());
        // Don't block the user, just log the issue
    }
}

// Redirect to thank you page
header("Location: ../Thankyou/?casenumber=" . urlencode($casenumber));
exit();
