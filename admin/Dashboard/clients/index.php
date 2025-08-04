<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../");
    exit();
}

require '../../../config/db.php';

$adminEmail = $_SESSION['email'] ?? 'Unknown';
$adminId = $_SESSION['admin_id'];

// Fetch counts for statistics
$clientsCount = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$usersCount = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
$casesCount = $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn();

// Fetch clients list
$clientsStmt = $pdo->query("SELECT * FROM clients ORDER BY name ASC");
$clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);
$token = 

// Fetch users list
$stmt = $pdo->prepare("SELECT * FROM admin_users");
$stmt->execute();
$allUsers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Client</title>
  
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
    }
    button:hover {
      background-color: #ED1A3B;
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
.form-container select {
  width: 100%;
  padding: 10px 12px;
  font-size: 15px;
  border: 1px solid #ccc;
  border-radius: 8px;
  margin-top: 4px;
  box-sizing: border-box;
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
      <?php
      require '../../../config/db.php';

      $client_token = $_GET['token'] ?? null;
      if (!$client_token) {
          die('Client token is missing');
      }

      $stmt = $pdo->prepare("SELECT * FROM clients WHERE token = ?");
      $stmt->execute([$client_token]);
      $client = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$client) {
          die('Client not found');
      }

      $stmt = $pdo->prepare("SELECT admin_users.* FROM admin_users 
          JOIN user_client_assignments ON admin_users.id = user_client_assignments.user_id
          WHERE user_client_assignments.client_token = ?");
      $stmt->execute([$client_token]);
      $assigned_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $stmt = $pdo->query("SELECT * FROM admin_users");
      $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

<h2 class="form-title"><?= htmlspecialchars($client['name']) ?></h2>

<div class="form-container">
  <form method="POST" action="update_client.php">
    <input type="hidden" name="client_token" value="<?= htmlspecialchars($client['token']) ?>">

    <label>Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($client['name']) ?>">

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($client['email']) ?>">

    <label>Phone:</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($client['Telephone']) ?>">

    <label>Assign Users:</label>
    <select id="user-multiselect" name="users_to_assign[]" multiple>
      <?php foreach ($all_users as $user): ?>
        <option value="<?= $user['id'] ?>">
          <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" style="background: #ED1A3B;">Save Changes</button>
  </form>
</div>



<script>
  document.addEventListener('DOMContentLoaded', function () {
    const element = document.getElementById('user-multiselect');
    const choices = new Choices(element, {
      removeItemButton: true,
      searchPlaceholderValue: 'Search users...',
      placeholderValue: 'Select users',
      shouldSort: false
    });
  });
</script>


      <h3>Currently Assigned Users</h3>
      <?php if (count($assigned_users) === 0): ?>
        <p>No users assigned yet.</p>
      <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>User Type</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assigned_users as $user): ?>
                <tr>
                  <td><?= htmlspecialchars($user['name']) ?></td>
                  <td><?= htmlspecialchars($user['email']) ?></td>
                  <td><?= htmlspecialchars($user['user_type']) ?></td>
                  <td>
                    <form method="POST" action="remove_user_assignment.php" onsubmit="return confirm('Are you sure?');">
                      <input type="hidden" name="client_token" value="<?= htmlspecialchars($client['token']) ?>">
                      <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                      <button type="submit">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }
  </script>

</body>
</html>