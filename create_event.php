<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$role = strtolower(trim($_SESSION['role']));
if ($role !== 'host' && $role !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $desc  = $conn->real_escape_string($_POST['description']);
    $date  = $conn->real_escape_string($_POST['date']);
    $host  = $_SESSION['user_id'];

    $sql = "INSERT INTO events (title, description, date_time, host_id)
            VALUES ('$title', '$desc', '$date', '$host')";
    if ($conn->query($sql)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Create Event - LakWatcha</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #e0eafc, #cfdef3);
      color: #333;
    }

    .dashboard-wrapper {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 250px;
      background: rgba(44, 62, 80, 0.85);
      backdrop-filter: blur(10px);
      padding: 30px 20px;
      color: #ecf0f1;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
    }

    .sidebar h2 {
      font-size: 1.8rem;
      margin-bottom: 30px;
      text-align: center;
      color: #fff;
    }

    .sidebar ul {
      list-style: none;
      padding-left: 0;
    }

    .sidebar ul li {
      margin-bottom: 18px;
    }

    .sidebar ul li a {
      text-decoration: none;
      color: #ecf0f1;
      padding: 12px 18px;
      display: block;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background-color: #3498db;
      transform: translateX(4px);
    }

    .main-content {
      margin-left: 250px;
      padding: 40px 50px;
      flex: 1;
    }

    .main-content h1 {
      font-size: 2.2rem;
      margin-bottom: 20px;
    }

    .card {
      background: #fff;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: auto;
    }

    .card h2 {
      margin-bottom: 25px;
      font-weight: 600;
      color: #2c3e50;
      text-align: center;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #34495e;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 12px 15px;
      font-size: 1rem;
      border: 1.8px solid #bdc3c7;
      border-radius: 8px;
      transition: border-color 0.3s ease;
      font-family: 'Segoe UI', sans-serif;
      resize: vertical;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      border-color: #3498db;
      outline: none;
      box-shadow: 0 0 8px rgba(52, 152, 219, 0.4);
    }

    .btn-primary {
      background-color: #3498db;
      border: none;
      padding: 12px 30px;
      border-radius: 8px;
      color: white;
      font-weight: 600;
      font-size: 1.1rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: inline-block;
    }

    .btn-primary:hover {
      background-color: #2980b9;
    }

    .btn-secondary {
      padding: 12px 30px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1.1rem;
      color: #34495e;
      background: #ecf0f1;
      border: none;
      cursor: pointer;
      margin-left: 15px;
      text-decoration: none;
      display: inline-block;
      vertical-align: middle;
      transition: background-color 0.3s ease;
    }

    .btn-secondary:hover {
      background-color: #d0d7de;
      color: #2c3e50;
      text-decoration: none;
    }

    .error-message {
      color: #e74c3c;
      margin-bottom: 20px;
      font-weight: 600;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="dashboard-wrapper">
    <nav class="sidebar">
      <h2>LakWatcha</h2>
      <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <?php if ($role === 'admin'): ?>
          <li><a href="admin_panel.php">Manage Users & Events</a></li>
        <?php endif; ?>
        <?php if ($role === 'host' || $role === 'admin'): ?>
          <li><a href="create_event.php" class="active">Create New Event</a></li>
          <li><a href="invite.php">Invite Users</a></li>
        <?php endif; ?>
        <li><a href="logout.php" style="color: #e74c3c;">Logout</a></li>
      </ul>
    </nav>

    <main class="main-content">
      <div class="card">
        <h2>Create New Event</h2>

        <?php if (!empty($error)): ?>
          <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="create_event.php">
          <div class="form-group">
            <label for="title">Event Title</label>
            <input type="text" name="title" id="title" required />
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4" required></textarea>
          </div>

          <div class="form-group">
            <label for="date">Date & Time</label>
            <input type="datetime-local" name="date" id="date" required />
          </div>

          <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn-primary">Create Event</button>
            <a href="dashboard.php" class="btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
