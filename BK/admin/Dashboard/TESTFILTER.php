<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../");
    exit();
}

require '../../config/db.php';

$adminEmail = $_SESSION['email'] ?? 'Unknown';
$adminId = $_SESSION['admin_id'];

$currentUserStmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$currentUserStmt->execute([$adminId]);
$currentUser = $currentUserStmt->fetch();

$isSuperAdmin = $currentUser && $currentUser['user_type'] === 'Super Admin';

if ($isSuperAdmin) {
    $clientsStmt = $pdo->query("SELECT * FROM clients ORDER BY name ASC");
    $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $assignedStmt = $pdo->prepare("SELECT client_token FROM user_client_assignments WHERE user_id = ?");
    $assignedStmt->execute([$adminId]);
    $assignedClients = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($assignedClients)) {
        $placeholders = rtrim(str_repeat('?,', count($assignedClients)), ',');
        $clientsStmt = $pdo->prepare("SELECT * FROM clients WHERE token IN ($placeholders) ORDER BY name ASC");
        $clientsStmt->execute($assignedClients);
        $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $clients = [];
    }
}

$where = [];
$params = [];

if (!empty($_GET['client'])) {
    $where[] = 'clients.name = ?';
    $params[] = $_GET['client'];
}

if (!empty($_GET['anonymity']) && $_GET['anonymity'] !== 'all') {
    $where[] = 'cases.identity_choice = ?';
    $params[] = $_GET['anonymity'];
}

if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
    $where[] = 'cases.status = ?';
    $params[] = $_GET['status'];
}

if (!empty($_GET['startDate'])) {
    $where[] = 'DATE(cases.submitted_at) >= ?';
    $params[] = $_GET['startDate'];
}

if (!empty($_GET['endDate'])) {
    $where[] = 'DATE(cases.submitted_at) <= ?';
    $params[] = $_GET['endDate'];
}

$baseQuery = "
    SELECT cases.*, clients.name AS client_name
    FROM cases
    JOIN clients ON cases.client_token = clients.token
";

if (!$isSuperAdmin && !empty($assignedClients)) {
    $clientTokensPlaceholders = rtrim(str_repeat('?,', count($assignedClients)), ',');
    $where[] = "clients.token IN ($clientTokensPlaceholders)";
    $params = array_merge($params, $assignedClients);
}

if (!empty($where)) {
    $baseQuery .= ' WHERE ' . implode(' AND ', $where);
}

$baseQuery .= ' ORDER BY cases.id DESC';

$casesstmt = $pdo->prepare($baseQuery);
$casesstmt->execute($params);
$allcases = $casesstmt->fetchAll(PDO::FETCH_ASSOC);

$selectedClient = $_GET['client'] ?? '';
$selectedAnonymity = $_GET['anonymity'] ?? 'all';
$selectedStatus = $_GET['status'] ?? 'all';
$selectedStartDate = $_GET['startDate'] ?? '';
$selectedEndDate = $_GET['endDate'] ?? '';
?>

<form method="GET" id="filterForm">
  <div class="filters">
    <!-- Client Filter -->
    <select name="client">
      <option value="">All Clients</option>
      <?php foreach ($clients as $client): ?>
        <option value="<?= htmlspecialchars($client['name']) ?>" <?= $selectedClient === $client['name'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($client['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Anonymity Filter -->
    <select name="anonymity">
      <option value="all" <?= $selectedAnonymity === 'all' ? 'selected' : '' ?>>All</option>
      <option value="Anonymous" <?= $selectedAnonymity === 'Anonymous' ? 'selected' : '' ?>>Anonymous</option>
      <option value="Identified" <?= $selectedAnonymity === 'Identified' ? 'selected' : '' ?>>Identified</option>
    </select>

    <!-- Status Filter -->
    <select name="status">
      <option value="all" <?= $selectedStatus === 'all' ? 'selected' : '' ?>>All</option>
      <option value="Pending" <?= $selectedStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
      <option value="Resolved" <?= $selectedStatus === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
      <option value="In Progress" <?= $selectedStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
    </select>

    <input type="date" name="startDate" value="<?= htmlspecialchars($selectedStartDate) ?>">
    <input type="date" name="endDate" value="<?= htmlspecialchars($selectedEndDate) ?>">

    <button type="submit">Apply Filters</button>
    <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="reset-button">Reset</a>
  </div>
</form>

<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>Case ID</th>
        <th>Client Name</th>
        <th>Case Anonymity</th>
        <th>Submitted Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allcases as $case): ?>
        <tr>
          <td><?= htmlspecialchars($case['casenumber']) ?></td>
          <td><?= htmlspecialchars($case['client_name']) ?></td>
          <td><?= htmlspecialchars($case['identity_choice']) ?></td>
          <td><?= htmlspecialchars($case['submitted_at']) ?></td>
          <td><?= htmlspecialchars($case['status']) ?></td>
          <td><a href="Cases/?casenumber=<?= urlencode($case['casenumber']) ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
