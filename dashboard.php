<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = strtolower(trim($_SESSION['role']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard - LakWatcha</title>
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

    .main-content h2 {
      font-size: 1.5rem;
      margin-top: 30px;
      margin-bottom: 15px;
    }

    .logout-btn {
      float: right;
      background: #e74c3c;
      border: none;
      padding: 8px 14px;
      color: white;
      border-radius: 4px;
      text-decoration: none;
      margin-top: -40px;
    }

    .logout-btn:hover {
      background: #c0392b;
    }

    ul.event-list {
      list-style: none;
      padding-left: 0;
      margin-top: 15px;
    }

    ul.event-list li {
      background-color: #ffffffcc;
      padding: 15px 20px;
      margin-bottom: 12px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
      transition: transform 0.2s;
    }

    ul.event-list li:hover {
      transform: scale(1.02);
    }

    ul.event-list li a {
      color: #2980b9;
      text-decoration: none;
      font-weight: 500;
    }

    ul.event-list li a:hover {
      text-decoration: underline;
    }

    .status {
      font-size: 0.95rem;
      margin-left: 10px;
      padding: 4px 8px;
      border-radius: 4px;
      background-color: #eee;
      display: inline-block;
    }

    .status.pending { background-color: #f1c40f; color: #fff; }
    .status.accepted { background-color: #2ecc71; color: #fff; }
    .status.rejected { background-color: #e74c3c; color: #fff; }

    .respond-link {
      margin-left: 10px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>

<div class="dashboard-wrapper">

  <div class="sidebar">
    <h2>LakWatcha</h2>
    <ul>
      <li><a href="dashboard.php" class="active">Dashboard</a></li>

      <?php if ($role === 'admin'): ?>
        <li><a href="admin_panel.php">Manage Users & Events</a></li>
        <li><a href="admin_panel.php#events">View All Events</a></li>
        <li><a href="admin_panel.php#users">Manage Users</a></li>

      <?php elseif ($role === 'host'): ?>
        <li><a href="create_event.php">Create New Event</a></li>
        <li><a href="invite.php">Invite Users</a></li>

      <?php elseif ($role === 'invitee'): ?>
        <li><a href="event_details.php">View Invitations</a></li>
      <?php endif; ?>

      <li><a href="logout.php" style="color: #e74c3c;">Logout</a></li>
    </ul>
  </div>

  <div class="main-content">
    <h1>Welcome, <?php echo ucfirst(htmlspecialchars($role)); ?></h1>

    <?php if ($role === 'admin'): ?>
      <p>You are logged in as <strong>Admin</strong>. Use the sidebar to manage events and users.</p>

    <?php elseif ($role === 'host'): ?>
      <h2>My Events</h2>
      <?php
      $stmt = $conn->prepare("SELECT id, title, date_time FROM events WHERE host_id = ?");
      $stmt->bind_param('i', $user_id);
      $stmt->execute();
      $events = $stmt->get_result();

      if ($events->num_rows > 0) {
          echo '<ul class="event-list">';
          while ($event = $events->fetch_assoc()) {
              echo '<li><a href="event_details.php?id=' . $event['id'] . '">' .
                  htmlspecialchars($event['title']) . ' (' . htmlspecialchars($event['date_time']) . ')</a></li>';
          }
          echo '</ul>';
      } else {
          echo '<p>You have not created any events yet.</p>';
      }
      ?>

    <?php elseif ($role === 'invitee'): ?>
      <h2>Your Invitations</h2>
      <?php
      $stmt = $conn->prepare("
          SELECT e.id AS event_id, e.title, e.date_time, i.status, i.id AS invitation_id
          FROM invitations i
          JOIN events e ON i.event_id = e.id
          WHERE i.user_id = ?
          ORDER BY e.date_time ASC
      ");
      $stmt->bind_param('i', $user_id);
      $stmt->execute();
      $invitations = $stmt->get_result();

      if ($invitations->num_rows > 0) {
          echo '<ul class="event-list">';
          while ($invite = $invitations->fetch_assoc()) {
              echo '<li>';
              echo '<a href="event_details.php?id=' . $invite['event_id'] . '">' .
                  htmlspecialchars($invite['title']) . ' (' . htmlspecialchars($invite['date_time']) . ')</a>';
              echo '<span class="status ' . htmlspecialchars(strtolower($invite['status'])) . '">' .
                  ucfirst(htmlspecialchars($invite['status'])) . '</span>';
              echo '<a class="respond-link" href="respond_invite.php?invitation_id=' . $invite['invitation_id'] . '">(Respond)</a>';
              echo '</li>';
          }
          echo '</ul>';
      } else {
          echo '<p>No invitations yet.</p>';
      }
      ?>

    <?php else: ?>
      <p>Unknown role.</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
