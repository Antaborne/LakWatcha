<?php
session_start();
$config = require 'config.php';
include 'db.php';

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'host') {
    header('Location: login.php');
    exit();
}

$message = '';

$base_url = "http://localhost/LakWatcha";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');

    if ($event_id <= 0 || empty($email) || empty($name)) {
        $message = "Please fill in all fields correctly.";
    } else {
        $token = bin2hex(random_bytes(16));

        $stmt = $conn->prepare("INSERT INTO invitations (event_id, email, name, token, status) VALUES (?, ?, ?, ?, 'pending')");
        if (!$stmt) {
            $message = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("isss", $event_id, $email, $name, $token);

            if ($stmt->execute()) {
                $invitation_link = $base_url . "/respond_invite.php?token=" . urlencode($token);

                $message = "Invitation created and email sent. Invitation link:<br><a href='$invitation_link' target='_blank'>$invitation_link</a>";

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $config['mail_username'];
                    $mail->Password = $config['mail_password'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom($config['mail_username'], 'LakWatcha');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = "You're invited to an event!";
                    $mail->Body = "
                        Hello <strong>" . htmlspecialchars($name) . "</strong>,<br><br>
                        You have been invited to an event.<br>
                        Please click the link below to respond:<br><br>
                        <a href='$invitation_link'>$invitation_link</a><br><br>
                        Thank you!<br>
                        LakWatcha Team
                    ";

                    $mail->send();
                } catch (Exception $e) {
                    $message .= "<br><small>Mailer error: " . htmlspecialchars($mail->ErrorInfo) . "</small>";
                }
            } else {
                $message = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$events = [];
$stmt2 = $conn->prepare("SELECT id, title FROM events WHERE host_id = ?");
$stmt2->bind_param("i", $_SESSION['user_id']);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $events[] = $row;
}
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Invite Users - LakWatcha</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
.form-group select {
  width: 100%;
  padding: 12px 15px;
  font-size: 1rem;
  border: 1.8px solid #bdc3c7;
  border-radius: 8px;
  transition: border-color 0.3s ease;
  font-family: 'Segoe UI', sans-serif;
}

.form-group input:focus,
.form-group select:focus {
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

.alert-info {
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
        <li><a href="create_event.php">Create Event</a></li>
        <li><a href="invite.php" class="active">Invite Users</a></li>
        <li><a href="logout.php" style="color: #e74c3c;">Logout</a></li>
      </ul>
    </nav>
    <main class="main-content">
      <div class="card">
        <h2>Send Invitation</h2>
        <?php if ($message): ?>
          <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST" action="invite.php">
          <div class="form-group">
            <label for="event_id">Select Event</label>
            <select name="event_id" id="event_id" required>
              <option value="" disabled selected>-- Choose an event --</option>
              <?php foreach ($events as $ev): ?>
                <option value="<?php echo $ev['id']; ?>"><?php echo htmlspecialchars($ev['title']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="name">Invitee Name</label>
            <input type="text" name="name" id="name" required />
          </div>
          <div class="form-group">
            <label for="email">Invitee Email</label>
            <input type="email" name="email" id="email" required />
          </div>
          <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">Send Invitation</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
