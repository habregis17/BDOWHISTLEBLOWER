<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../");
    exit();
}
require '../../../config/db.php';

$adminEmail = $_SESSION['email'] ?? 'Unknown';
$adminId = $_SESSION['admin_id'];

$rawFiles = $case['files'] ?? '';
$files = [];

if ($rawFiles) {
    $decoded = json_decode($rawFiles, true);
    if (is_array($decoded)) {
        $files = $decoded;
    } else {
        $files = array_filter(explode(',', $rawFiles)); // fallback if not JSON
    }
}


$caseNumber = $_GET['casenumber'] ?? '';
if (!$caseNumber) {
  die("Invalid case number.");
}

$stmt = $pdo->prepare("SELECT cases.*, clients.name AS client_name 
                       FROM cases 
                       JOIN clients ON cases.client_token = clients.token 
                       WHERE cases.casenumber = ?");
$stmt->execute([$caseNumber]);
$case = $stmt->fetch();

if (!$case) {
  die("Case not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Case View - <?= htmlspecialchars($case['casenumber']) ?></title>
  
  <!-- Include Choices.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
      * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      display: flex;
      min-height: 100vh;
      font-family: 'Trebuchet MS', sans-serif;
      background: #f9f9f9;
    }
    H2{
      font-size: 25px;
      font-weight: bold;
      color: #ED1A3B;
    }

    .sidebar {
      width: 250px;
      background-color: #1d1f2f;
      color: white;
      transition: width 0.3s;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .sidebar .logo {
      text-align: center;
      padding: 1rem;
      border-bottom: 1px solid #333;
    }

    .sidebar .logo img {
      width: 100px;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
      flex-grow: 1;
    }

    .sidebar ul li {
      padding: 15px 20px;
      border-left: 5px solid transparent;
      cursor: pointer;
      transition: background-color 0.3s, border-left-color 0.3s;
    }

    .sidebar ul li a {
      text-decoration: none;
      color: white;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
    }

    .sidebar ul li:hover,
    .sidebar ul li.active {
      background-color: rgba(255, 255, 255, 0.1);
      border-left: 5px solid #222;
    }

    .main-content {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      background: white;
      box-shadow: 0 0 15px rgb(0 0 0 / 0.1);
      min-height: 100vh;
    }

    .topbar {
      background-color: #eee;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .topbar .app-name {
      font-size: 1.5rem;
      font-weight: bold;
      color: #333;
    }

    .topbar .user-info {
      font-size: 0.9rem;
      color: #444;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .topbar .user-info span {
      font-weight: 600;
    }

    .topbar .user-info a {
      text-decoration: none;
      color: crimson;
      font-weight: bold;
      padding: 6px 12px;
      border-radius: 4px;
      transition: background-color 0.3s;
      background-color: transparent;
      border: 1px solid crimson;
    }

    .topbar .user-info a:hover {
      background-color: crimson;
      color: white;
    }

    .toggle-btn {
      background: crimson;
      color: white;
      border: none;
      padding: 6px 12px;
      cursor: pointer;
      font-size: 0.9rem;
      margin-left: 20px;
      border-radius: 4px;
      display: none;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 60px;
      }
      .sidebar .logo img {
        width: 40px;
      }
      .sidebar ul li a span {
        display: none;
      }
      .toggle-btn {
        display: inline-block;
      }
    }

    .content {
      padding: 20px;
      flex-grow: 1;
      overflow-y: auto;
    }

    /* Cards on Home */
    .stats-cards {
      display: flex;
      gap: 20px;
      margin-top: 20px;
      flex-wrap: wrap;
    }
    .card {
      background: #f2f2f2;
      border-radius: 8px;
      padding: 20px;
      flex: 1 1 200px;
      text-align: center;
      box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
      cursor: default;
      transition: box-shadow 0.3s ease;
    }
    .card:hover {
      box-shadow: 0 5px 15px rgb(0 0 0 / 0.2);
    }
    .card h3 {
      margin-bottom: 10px;
      color: #c62828;
    }
    .card p {
      font-size: 2rem;
      font-weight: bold;
      color: #333;
    }

    /* Table styling */
    table {
      width: 100%;
      max-width: 900px;
      border-collapse: collapse;
      margin-top: 10px;
    }
    table th,
    table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: left;
    }
    table th {
      background-color: #f0f0f0;
    }
    button {
      background-color: #ED1A3B;
      border: none;
      color: white;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
      font-family: 'Trebuchet Ms';
    }
    button:hover {
      background-color: #333333;
    }

    /* Section toggle helper */
    .section {
      display: none;
    }
    .active-section {
      display: block;
    }
     /* Add form modal styles */
    .modal-bg {
      position: fixed;
      top:0; left:0; right:0; bottom:0;
      background: rgba(0,0,0,0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }
    .modal-bg.active {
      display: flex;
    }
    .modal {
      background: white;
      border-radius: 8px;
      padding: 20px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    .modal h3 {
      margin-bottom: 15px;
    }
    .modal label {
      display: block;
      margin-top: 10px;
      font-weight: 600;
    }
    .modal input[type=text],
    .modal input[type=email],
    .modal input[type=tel],
    .modal select,
    .modal textarea {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
      resize: vertical;
    }
    .modal textarea {
      min-height: 60px;
    }
    .modal button {
      margin-top: 15px;
      background-color: #c62828;
      border: none;
      color: white;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s;
    }
    .modal button:hover {
      background-color: #a71d1d;
    }
    .modal .close-btn {
      background: #555;
      float: right;
      padding: 5px 10px;
      margin-top: -10px;
      margin-right: -10px;
      border-radius: 50%;
      font-weight: normal;
      cursor: pointer;
    }
      .filters {
    margin-bottom: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
  }

  .filters label {
    font-weight: bold;
  }

  .filters select,
  .filters input[type="date"] {
    padding: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
  }

  .table-wrapper {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
  }

  th {
    background-color: #f4f4f4;
  }
  .form-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 20px;
  color: #333;
}

.form-container {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
  padding: 30px;
  max-width: 600px;
  margin-top: 20px;
  font-family: 'Trebuchet Ms';
}

.form-container label {
  display: block;
  font-weight: 500;
  margin-bottom: 6px;
  color: #444;
  margin-top: 20px;
}

.form-container input[type="text"],
.form-container input[type="email"],
.form-container select,
.form-container textarea {
  width: 100%;
  padding: 10px 12px;
  font-size: 15px;
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-top: 4px;
  box-sizing: border-box;
  font-family: 'Trebuchet Ms';
}

.form-container select[multiple] {
  height: auto;
  min-height: 160px;
  font-size: 14px;
  padding: 10px;
}

.form-container button {
  margin-top: 30px;
  padding: 12px 20px;
  font-size: 16px;
  font-weight: 600;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.form-container button:hover {
  background: #0056b3;
}
/* Shrink and style selected user tags */
.choices__inner {
  min-height: 44px;
  padding: 6px 8px;
  border-radius: 8px;
  font-size: 10px;
}


/* Style the selected user tags */
.choices__list--multiple .choices__item {
  background-color: #ED1A3B;
  color: #fff;
  border-radius: 20px;
  padding: 4px 10px 4px 10px;
  font-size: 13px;
  font-weight: 500;
  position: relative;
  border-color: none;
}

/* Hide original weird character in close button */
.choices__list--multiple .choices__item .choices__button {
  font-size: 0;         /* hide weird character */
  border: none;
  background: none;
  width: 16px;
  height: 16px;
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
}

/* Replace with custom X using CSS */
.choices__list--multiple .choices__item .choices__button::before {
  content: "×"; /* clean X */
  font-size: 14px;
  color: #fff;
  display: inline-block;
  line-height: 1;
}

.file-list a {
  font-family: 'TREBUCHET MS', sans-serif;
  color: #333333;
  font-weight: bold;
  text-decoration: underline;
}
.file-list a:hover {
  text-decoration: underline;
}

.case-header {
  margin-bottom: 20px;
}

.case-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.case-info,
.case-description {
  flex: 1 1 45%;
  min-width: 300px;
}

@media (max-width: 768px) {
  .case-info,
  .case-description {
    flex: 1 1 100%;
  }
}

  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <div class="logo">
      <img src="https://cdn.worldvectorlogo.com/logos/bdo.svg" alt="BDO Logo" />
    </div>
    <ul>
      <li class="active" data-section="home">
        <a href="../"><i class="fas fa-home"></i> <span>Home</span></a>
      </li>
<!--       <li data-section="reports">
        <a href="#"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
      </li>
      <li data-section="clients">
        <a href="#"><i class="fas fa-briefcase"></i> <span>Clients</span></a>
      </li>
      <li data-section="users">
        <a href="#"><i class="fas fa-users"></i> <span>Users</span></a>
      </li> -->
    </ul>
  </div>

  <div class="main-content">
    <div class="topbar">
      <div class="app-name">Whistleblower Admin Dashboard</div>
      <div class="user-info">
        <span><?= htmlspecialchars($adminEmail) ?> (ID: <?= $adminId ?>)</span>
        <a href="profile.php">View Profile</a>
        <a href="logout.php">Logout</a>
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
      </div>
    </div>
<div class="content">
  <div class="case-header">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
  <h2 style="margin: 0;">Case #: <?= htmlspecialchars($case['casenumber']) ?></h2>
  <a href="export_pdf.php?casenumber=<?= urlencode($case['casenumber']) ?>" target="_blank" style="border-color:#ED1A3B ;background-color: #ED1A3B; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center;font-family: 'Trebuchet Ms'; font-size: 12px; font-weight: bold;">
    <i class="fas fa-file-pdf" style="margin-right: 6px;"></i> Export to PDF
  </a>
<!-- Export Excel -->
    <a href="export_excel.php?casenumber=<?= urlencode($case['casenumber']) ?>" target="_blank" style="border-color:#00a86b ;background-color:#00a86b; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center;font-family: 'Trebuchet Ms'; font-size: 12px; font-weight: bold;">
    <i class="fas fa-file-excel" style="margin-right: 6px;"></i> Export to Excel
  </a>
</div>
    <p><strong>Status:</strong> <?= htmlspecialchars($case['status']) ?> | 
       <strong>Submitted:</strong> <?= htmlspecialchars($case['submitted_at']) ?> | 
       <strong>Last Updated by:</strong> <?= htmlspecialchars($case['updated_by']) ?> on <?= htmlspecialchars($case['last_updated']) ?>
    </p>
  </div>

  <div class="case-grid">
    <div class="case-info">
      <div class="form-container">
        <label>Concerned Client</label>
        <input type="text" value="<?= htmlspecialchars($case['client_name']) ?>" disabled>

        <label>Affiliation</label>
        <input type="text" value="<?= htmlspecialchars($case['affiliation']) ?>" disabled>

        <label>Anonymity</label>
        <input type="text" value="<?= htmlspecialchars($case['identity_choice']) ?>" disabled>

        <?php
$identity = strtolower($case['identity_choice']);
if ($identity === 'identifiable' || $identity === 'identifiable to bdo only' || $identity === 'identified'):
?>
<label>Full Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($case['full_name']) ?>" disabled>

<label>Department</label>
    <input type="text" name="name" value="<?= htmlspecialchars($case['email']) ?>" disabled>

<label>Email</label>
    <input type="text" name="name" value="<?= htmlspecialchars($case['email']) ?>" disabled>

<label>Telephone</label>
    <input type="text" name="name" value="<?= htmlspecialchars($case['phone']) ?>" disabled>

<?php endif; ?>
      </div>
    </div>

    <div class="case-description">
      <div class="form-container">
        <label>When did the incident(s) take place?</label>
        <textarea rows="5" disabled><?= htmlspecialchars($case['incident_when']) ?></textarea>

        <label>Where did the incident(s) take place?</label>
        <textarea rows="5" disabled><?= htmlspecialchars($case['incident_where']) ?></textarea>

        <label>Which department/site does it concern?</label>
        <textarea rows="5" disabled><?= htmlspecialchars($case['incident_division']) ?></textarea>

        <label>Description of the incident(s)</label>
        <textarea rows="5" disabled><?= htmlspecialchars($case['incident_description']) ?></textarea>
        
        <label><strong>Attached File(s)</strong></label>
      <?php
$files = json_decode($case['files'], true); // Decode JSON string to PHP array

if (is_array($files) && count($files) > 0): ?>
  <label><strong>Attached File(s)</strong></label>
  <div class="file-list">
    <?php foreach ($files as $file): 
      $path = "../../../Submit/" . htmlspecialchars($file);
      $filename = basename($file);
      $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      // Choose icon based on file type
      switch ($ext) {
        case 'pdf': $icon = '📄'; break;
        case 'doc':
        case 'docx': $icon = '📝'; break;
        case 'xls':
        case 'xlsx': $icon = '📊'; break;
        case 'jpg':
        case 'jpeg':
        case 'png': $icon = '🖼️'; break;
        case 'zip':
        case 'rar': $icon = '🗜️'; break;
        case 'whiteboard': $icon = '🧭'; break;
        default: $icon = '📁'; break;
      }
    ?>
      <div style="margin: 5px 0;">
        <a href="<?= $path ?>" target="_blank" style="text-decoration: none;">
          <?= $icon ?> <?= $filename ?>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p><em>No files attached.</em></p>
<?php endif; ?>
      </div>
    </div>
  </div>

  <div class="form-container" style="margin-top: 30px;">
    <form method="POST" action="update_case_status.php" onsubmit="return validateStatusForm();">
      <input type="hidden" name="case_number" value="<?= htmlspecialchars($case['casenumber']) ?>">
      <label for="status">Status</label>
      <select name="status" id="status" onchange="toggleFeedbackField()">
        <option value="Pending" <?= $case['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Closed" <?= $case['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
      </select>

      <div id="feedbackContainer" style="display: <?= $case['status'] === 'Closed' ? 'block' : 'none' ?>; margin-top:10px;">
        <label for="feedback">Feedback (Required if Closed)</label>
        <textarea name="feedback" id="feedback" rows="4"><?= htmlspecialchars($case['feedback']) ?></textarea>
      </div>

      <button type="submit" style="background: #ED1A3B;">Update Status</button>
    </form>
  </div>
</div>

  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }
  </script>

  <script>
function toggleFeedbackField() {
  const status = document.getElementById("status").value;
  const feedbackContainer = document.getElementById("feedbackContainer");

  if (status === "Closed") {
    feedbackContainer.style.display = "block";
  } else {
    feedbackContainer.style.display = "none";
  }
}

function validateStatusForm() {
  const status = document.getElementById("status").value;
  const feedback = document.getElementById("feedback");

  if (status === "Closed" && (!feedback.value.trim())) {
    alert("Feedback is required when closing the case.");
    return false;
  }

  return true;
}
</script>

</body>
</html>