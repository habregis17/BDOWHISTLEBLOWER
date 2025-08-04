<?php
require '../../../config/db.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);  // ✅ Required for remote images
$dompdf = new Dompdf($options);  // ✅ Use the options here

$casenumber = $_GET['casenumber'] ?? '';
if (!$casenumber) die("No case number.");

$stmt = $pdo->prepare("SELECT cases.*, clients.name AS client_name 
                       FROM cases 
                       JOIN clients ON cases.client_token = clients.token 
                       WHERE cases.casenumber = ?");
$stmt->execute([$casenumber]);
$case = $stmt->fetch();

if (!$case) die("Case not found.");

$identity = strtolower($case['identity_choice']);
$showIdentity = in_array($identity, ['identifiable', 'identifiable_to_bdo_only', 'identified']);

// ✅ Replace SVG logo with working PNG
$systemLogo = 'https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg';
$clientLogo = 'https://images.africanfinancials.com/rw-bok-logo-min.png';

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
  &copy; ' . date('Y') . ' Whistleblower Solution - BDO East Africa(Rwanda) Ltd. All rights reserved.
</footer>

</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('case_' . $case['casenumber'] . '.pdf', ["Attachment" => false]);
exit;
