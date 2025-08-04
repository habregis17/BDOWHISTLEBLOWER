<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../");
    exit();
}

require '../../config/db.php';

$adminEmail = $_SESSION['email'] ?? 'Unknown';
$adminId = $_SESSION['admin_id'];


// Filtering logic
// 🔍 Get current user's details (to check if Super Admin)
$currentUserStmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$currentUserStmt->execute([$adminId]);
$currentUser = $currentUserStmt->fetch();

$isSuperAdmin = $currentUser && $currentUser['user_type'] === 'Super Admin';

if ($isSuperAdmin) {
    // 🧑‍💼 Super Admin: Load everything
    $clientsCount = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $usersCount = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    $casesCount = $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();

    $clientsStmt = $pdo->query("SELECT * FROM clients ORDER BY name ASC");
    $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM admin_users");
    $allUsers = $stmt->fetchAll();

    $casesstmt = $pdo->query("
        SELECT cases.*, clients.name AS client_name
        FROM cases
        JOIN clients ON cases.client_token = clients.token
        ORDER BY cases.id DESC
    ");
    $allcases = $casesstmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // 👤 Regular Admin: Only show assigned clients & cases
    $assignedStmt = $pdo->prepare("SELECT client_token FROM user_client_assignments WHERE user_id = ?");
    $assignedStmt->execute([$adminId]);
    $assignedClients = $assignedStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($assignedClients)) {
        $clients = [];
        $allcases = [];
        $clientsCount = 0;
        $casesCount = 0;
    } else {
        $placeholders = rtrim(str_repeat('?,', count($assignedClients)), ',');

        $clientsStmt = $pdo->prepare("SELECT * FROM clients WHERE token IN ($placeholders) ORDER BY name ASC");
        $clientsStmt->execute($assignedClients);
        $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

        $casesstmt = $pdo->prepare("
            SELECT cases.*, clients.name AS client_name
            FROM cases
            JOIN clients ON cases.client_token = clients.token
            WHERE clients.token IN ($placeholders)
            ORDER BY cases.id DESC
        ");
        $casesstmt->execute($assignedClients);
        $allcases = $casesstmt->fetchAll(PDO::FETCH_ASSOC);

        $clientsCount = count($clients);
        $casesCount = count($allcases);
    }

    // Hide admin users list for non-super admins
    $usersCount = 0;
    $allUsers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
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
      background-color: #c62828;
      border: none;
      color: white;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    button:hover {
      background-color: #a71d1d;
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
  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <div class="logo">
      <img
        src="https://cdn.worldvectorlogo.com/logos/bdo.svg"
        alt="BDO Logo"
      />
    </div>
    <ul>
      <li class="active" data-section="home">
        <a href="#"><i class="fas fa-home"></i> <span>Home</span></a>
      </li>
      <li data-section="reports">
        <a href="#"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
      </li>
      <li data-section="clients">
        <a href="#"><i class="fas fa-briefcase"></i> <span>Clients</span></a>
      </li>
      <?php if ($isSuperAdmin): ?>
      <li data-section="users">
        <a href="#"><i class="fas fa-users"></i> <span>Users</span></a>
      </li>
      <?php endif; ?>
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

    <div class="content" id="content-area">
      <!-- Home Section -->
      <section id="home-section" class="section active-section">
        <h2>Welcome to your dashboard!</h2>
        <div class="stats-cards">
          <div class="card">
            <h3>Clients</h3>
            <p><?= $clientsCount ?></p>
          </div>
          <div class="card">
            <h3>Users</h3>
            <p><?= $usersCount ?></p>
          </div>
           <div class="card">
            <h3>Cases</h3>
            <p><?= $casesCount ?></p>
          </div>
        </div>
      </section>

      <!-- Reports Section -->
      <section id="reports-section" class="section">
  <h2>Reports</h2>
<!-- Filter forms -->
<form method="GET" id="filterForm" action="export_cases_excel.php">
  <div class="filters">
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
      <!-- Start Date -->
      <div>
        <label for="startDate">Start Date:</label><br>
        <input type="date" id="startDate" name="startDate">
      </div>

      <!-- End Date -->
      <div>
        <label for="endDate">End Date:</label><br>
        <input type="date" id="endDate" name="endDate">
      </div>
    </div>

    <div style="margin-top: 20px;">
      <button type="submit" name="export" value="excel" id="exportExcel">Export to Excel</button>
      <button type="submit" name="export" value="pdf" id="exportPDF">Export to PDF</button>
    </div>
  </div>
</form>



  <!-- Case Table -->
  <div class="table-wrapper">
    <table id="casesTable">
      <thead>
        <tr>
          <th><input type="checkbox" id="selectAll"></th>
          <th>Case ID</th>
          <th>Client Name</th>
          <th>Case Anonymity</th>
          <th>Submitted Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
       <?php foreach ($allcases as $cases): ?>
            <tr>
              <td><input type="checkbox" id="selectAll"></td>
              <td><?= htmlspecialchars($cases['casenumber']) ?></td>
              <td><?= htmlspecialchars($cases['client_name']) ?></td>
              <td><?= htmlspecialchars($cases['identity_choice']) ?></td>
              <td><?= htmlspecialchars($cases['submitted_at']) ?></td>
              <td><?= htmlspecialchars($cases['status']) ?></td>
              <td>
                        <a href="Cases/?casenumber=<?= urlencode($cases['casenumber']) ?>">
  <button>View</button>
</a>
              </td>
            </tr>
       <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <div id="pagination">
    <!-- Pagination buttons will be inserted dynamically -->
  </div>
</section>

<script>
  document.getElementById('selectAll').addEventListener('change', function () {
    const checkboxes = document.querySelectorAll('#casesTable tbody input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
  });

  // Dynamically populate client dropdown from database
  document.addEventListener('DOMContentLoaded', () => {
    fetch('api/get_clients.php')
      .then(response => response.json())
      .then(data => {
        const clientFilter = document.getElementById('clientFilter');
        data.forEach(client => {
          const option = document.createElement('option');
          option.value = client.id; // assuming client has an id
          option.textContent = client.name;
          clientFilter.appendChild(option);
        });
      })
      .catch(error => console.error('Error fetching clients:', error));
  });
</script>


      <!-- Clients Section -->
            <section id="clients-section" class="section">
        <h2>Clients List</h2>
        <?php if ($isSuperAdmin): ?>
          <button onclick="openModal('addClientModal')">+ Add Client</button>
<?php endif; ?>
        <input
          type="text"
          id="client-search"
          placeholder="Search clients..."
          onkeyup="filterTable('client-table', this.value)"
          style="margin: 10px 0 10px 0; padding: 8px; width: 100%; max-width: 400px"
        />
        <table id="client-table" cellpadding="8" cellspacing="0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Point of Contact</th>
              <th>Telephone</th>
              <th>Description</th>
              <th>BDO User Contact</th>
              <th>Client Contact</th>
              <th>Unique ID (Token)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $client): ?>
            <tr>
              <td><?= htmlspecialchars($client['name']) ?></td>
              <td><?= htmlspecialchars($client['email'] ?? '') ?></td>
              <td><?= htmlspecialchars($client['Telephone'] ?? '') ?></td>
              <td><?= htmlspecialchars($client['Description'] ?? '') ?></td>
              <td><?= htmlspecialchars($client['bdo_user_contact_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($client['client_contact_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($client['token']) ?></td>
              <td>
                  <a href="<?= 'localhost:8080/Whistleblower' . '?token=' . urlencode($client['token']) ?>" target="_blank">Open Custom Form</a>
              </td>

              <td>
                  <a href="<?= 'clients/' . '?token=' . urlencode($client['token']) ?>"><button>View</button></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <!-- Users Section -->
           <section id="users-section" class="section">
        <h2>Users List</h2>
        <button onclick="openModal('addUserModal')">+ Add User</button>
        <input
          type="text"
          id="user-search"
          placeholder="Search users..."
          onkeyup="filterTable('user-table', this.value)"
          style="margin: 10px 0 10px 0; padding: 8px; width: 100%; max-width: 400px"
        />
        <table id="user-table" cellpadding="8" cellspacing="0">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Telephone</th>
              <th>User Type</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allUsers as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= htmlspecialchars($user['telephone'] ?? '') ?></td>
              <td><?= htmlspecialchars($user['user_type'] ?? '') ?></td>
              <td>
                <button onclick="deleteUser(<?= $user['id'] ?>)">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  </div>
  <!-- Add Client Modal -->
  <div class="modal-bg" id="addClientModal">
    <div class="modal">
      <span class="close-btn" onclick="closeModal('addClientModal')">&times;</span>
      <h3>Add Client</h3>
      <form id="addClientForm">
        <label>Name of Client</label>
        <input type="text" name="name" required />
        
        <label>Point of Contact</label>
        <input type="text" name="point_of_contact" required />
        
        <label>Telephone</label>
        <input type="tel" name="telephone" required />
        
        <label>Description</label>
        <textarea name="description"></textarea>
        
        <label>BDO User Contact</label>
        <select name="bdo_user_contact">
          <option value="">Select BDO User</option>
          <?php foreach ($allUsers as $user): ?>
          <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
          <?php endforeach; ?>
        </select>
        
        <label>Client Contact</label>
        <select name="client_contact" >
          <option value="">Select Client Contact</option>
          <?php foreach ($allUsers as $user): ?>
          <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
          <?php endforeach; ?>
        </select>
        
        <button type="submit">Add Client</button>
      </form>
    </div>
  </div>

  <!-- Add User Modal -->
  <div class="modal-bg" id="addUserModal">
    <div class="modal">
      <span class="close-btn" onclick="closeModal('addUserModal')">&times;</span>
      <h3>Add User</h3>
      <form id="addUserForm">
        <label>Name</label>
        <input type="text" name="name" required />
        
        <label>Email</label>
        <input type="email" name="email" required />
        
        <label>Telephone</label>
        <input type="tel" name="telephone" />
        
        <label>User Type</label>
        <select name="user_type" required>
          <option value="">Select user type</option>
          <option value="Super Admin">Super Admin</option>
          <option value="BDO User">BDO User</option>
        </select>
        <button type="submit">Add User</button>
      </form>
    </div>
  </div>

<!-- ------------------------Start of the client View modal------------------------ -->

<!-- -------------------------End of client displaying -->





  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('collapsed');
    }

    // Navigation click handler to show/hide sections
    document.querySelectorAll('.sidebar ul li').forEach((item) => {
      item.addEventListener('click', () => {
        document.querySelectorAll('.sidebar ul li').forEach((li) =>
          li.classList.remove('active')
        );
        item.classList.add('active');

        // Hide all sections
        document.querySelectorAll('.section').forEach((sec) => (sec.style.display = 'none'));

        // Show the selected section
        const sectionId = item.getAttribute('data-section');
        document.getElementById(sectionId + '-section').style.display = 'block';
      });
    });

    // Table filter helper
    function filterTable(tableId, searchTerm) {
      searchTerm = searchTerm.toLowerCase();
      const rows = document
        .getElementById(tableId)
        .getElementsByTagName('tbody')[0]
        .getElementsByTagName('tr');

      for (let row of rows) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      }
    }

    // Delete client by ID
    function deleteClient(clientId) {
      if (!confirm('Are you sure you want to delete this client?')) return;

      fetch('delete_client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: clientId }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            alert('Client deleted.');
            location.reload();
          } else {
            alert('Failed to delete client.');
          }
        })
        .catch(() => alert('Error deleting client.'));
    }

    // Delete user by ID
    function deleteUser(userId) {
      if (!confirm('Are you sure you want to delete this user?')) return;

      fetch('delete_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: userId }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            alert('User deleted.');
            location.reload();
          } else {
            alert('Failed to delete user.');
          }
        })
        .catch(() => alert('Error deleting user.'));
    }
  </script>
    <script>
    // Show/hide modals
    function openModal(id) {
      document.getElementById(id).classList.add('active');
    }
    function closeModal(id) {
      document.getElementById(id).classList.remove('active');
    }

    // Navigation click handler to show/hide sections (existing)
    document.querySelectorAll('.sidebar ul li').forEach((item) => {
      item.addEventListener('click', () => {
        document.querySelectorAll('.sidebar ul li').forEach((li) =>
          li.classList.remove('active')
        );
        item.classList.add('active');

        document.querySelectorAll('.section').forEach((sec) => (sec.style.display = 'none'));

        const sectionId = item.getAttribute('data-section');
        document.getElementById(sectionId + '-section').style.display = 'block';
      });
    });

    // Filter helper (existing)
    function filterTable(tableId, searchTerm) {
      searchTerm = searchTerm.toLowerCase();
      const rows = document
        .getElementById(tableId)
        .getElementsByTagName('tbody')[0]
        .getElementsByTagName('tr');

      for (let row of rows) {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      }
    }

    // Delete handlers (existing)
    function deleteClient(clientId) {
      if (!confirm('Are you sure you want to delete this client?')) return;

      fetch('delete_client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: clientId }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            alert('Client deleted.');
            location.reload();
          } else {
            alert('Failed to delete client.');
          }
        })
        .catch(() => alert('Error deleting client.'));
    }
    function deleteUser(userId) {
      if (!confirm('Are you sure you want to delete this user?')) return;

      fetch('delete_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: userId }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            alert('User deleted.');
            location.reload();
          } else {
            alert('Failed to delete user.');
          }
        })
        .catch(() => alert('Error deleting user.'));
    }

    // Add Client form submission
    document.getElementById('addClientForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);

      fetch('add_client.php', {
        method: 'POST',
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            alert('Client added successfully!');
            closeModal('addClientModal');
            location.reload();
          } else {
            alert('Failed to add client: ' + (data.error || 'Unknown error'));
          }
        })
        .catch(() => alert('Error adding client.'));
    });
//add user modal
    document.getElementById('addUserForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('add_user.php', {
    method: 'POST',
    body: formData,
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('User added successfully!\nGenerated Password: ' + data.password);
        closeModal('addUserModal');
        window.location.href = '../'; // go up one level
      } else {
        alert('Failed to add user: ' + (data.error || 'Unknown error'));
      }
    })
    .catch((err) => {
      console.error(err);
      alert('Error adding user.');
    });
});

  </script>

  <!-- Script to view client details and edit -->

  
</html>
