<?php
require '../config/db.php';
require_once '../admin/Dashboard//dompdf/autoload.inc.php';
require '../utils/vendor/autoload.php'; // Adjust this if using Composer elsewhere

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get case number
$casenumber = $_POST['casenumber'] ?? '';
if (!$casenumber) die("No case number.");

// Fetch case
$stmt = $pdo->prepare("SELECT cases.*, clients.name AS client_name 
                       FROM cases 
                       JOIN clients ON cases.client_token = clients.token 
                       WHERE cases.casenumber = ?");
$stmt->execute([$casenumber]);
$case = $stmt->fetch();
if (!$case) die("Case not found.");

// Prepare variables
$identity = strtolower($case['identity_choice']);
$showIdentity = in_array($identity, ['identifiable', 'identifiable_to_bdo_only', 'identified']);
$systemLogo = 'https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg';
$clientLogo = 'https://images.africanfinancials.com/rw-bok-logo-min.png';

// Build HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>Case Report ' . htmlspecialchars($case['casenumber']) . '</title>
<style>
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
  .files div { margin: 5px 0; font-size: 13px; }
  .status-box { background: #00A86B; color: white; font-weight: bold; padding: 8px; border-radius: 5px; text-align: center; }
  footer { text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ccc; padding-top: 12px; margin-top: 40px; }
</style>
</head>
<body>

<header>
  <div class="logo-row">
    <img src="' . $systemLogo . '" alt="System Logo" />
  </div>
  <h1>Whistleblower Case Management System</h1>
  <h2>Case #: ' . htmlspecialchars($case['casenumber']) . '</h2>
</header>

<section>
  <label>Status</label>
  <div class="status-box">' . htmlspecialchars($case['status']) . '</div>

  <label>Submitted At</label>
  <div class="field">' . htmlspecialchars($case['submitted_at']) . '</div>

  <label>Last Updated By</label>
  <div class="field">' . htmlspecialchars($case['updated_by']) . ' on ' . htmlspecialchars($case['last_updated']) . '</div>
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

$html .= '
</section>

<section>
  <h3>Incident Details</h3>

  <label>When did the incident(s) take place?</label>
  <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_when'])) . '</div>

  <label>Where did the incident(s) take place?</label>
  <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_where'])) . '</div>

  <label>Which department/site does it concern?</label>
  <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_division'])) . '</div>

  <label>Description of the incident(s)</label>
  <div class="field textarea">' . nl2br(htmlspecialchars($case['incident_description'])) . '</div>

  <label>Feedback (Applicable if Closed)</label>
  <div class="field textarea">' . nl2br(htmlspecialchars($case['feedback'])) . '</div>
</section>

<footer>
  &copy; ' . date('Y') . ' Whistleblower Solution - BDO East Africa (Rwanda) Ltd. All rights reserved.
</footer>

</body>
</html>
';

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

// Email sending
$user_email =$_POST['receipt_email'] ?? '';
$case_token = $case['casenumber'];

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com';        // Outlook SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'alert.rw@bdo-ea.com'; // Your Outlook email
    $mail->Password   = 'Bdo@2023!';         // App password or real password (if allowed)
    $mail->SMTPSecure = 'tls';                       // Encryption
    $mail->Port       = 587;                         // TLS port

    $mail->setFrom('alert.rw@bdo-ea.com', 'Whistleblowing Platform');
    $mail->addAddress($user_email);

    $mail->isHTML(true);
    $mail->Subject = "📄 Your Case Submission Receipt - Ref #$case_token";
    $mail->Body = "
    <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
        <h2 style='color: #0073e6;'>Thank you for your report</h2>
        <p>Dear User,</p>
        <p>We have successfully received your report. Your case reference number is:</p>
        <h3 style='color: #28a745;'>$case_token</h3>
        <p>We’ve attached a copy of your case receipt for your reference.</p>
        <p>Should you have any questions or need further assistance, feel free to contact us.</p>
        <br/>
        <p>Best regards,<br/><strong>Whistleblowing Support Team</strong></p>
        <hr/>
        <small style='color: #999;'>This is an automated message. Please do not reply.</small>
    </div>";
    $mail->AltBody = "Thank you for your report. Case Ref: $case_token. See attached PDF.";

    $mail->addAttachment($tempPdfPath, "case_$case_token.pdf");

    $mail->send();
    echo "✅ Receipt sent to $user_email.";

    // Delete temp file
    unlink($tempPdfPath);
} catch (Exception $e) {
    echo "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
