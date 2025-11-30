<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = $conn->real_escape_string($_POST['name']);
  $email = $conn->real_escape_string($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $conn->real_escape_string($_POST['role']);

  $check = $conn->query("SELECT id FROM users WHERE email='$email'");
  if ($check->num_rows > 0) {
    echo "<script>alert('Email already registered!'); window.history.back();</script>";
    exit();
  }

  $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
  if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Registration successful! Please login.'); window.location='login.php';</script>";
    exit();
  } else {
    echo "<script>alert('Error: " . $conn->error . "'); window.history.back();</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Register - LakWatcha</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="container">
  <h2>LakWatcha</h2>
  <div class="tabs">
    <a href="login.php">Login</a>
    <a href="register.php" class="active">Signup</a>
  </div>

  <form method="POST" action="register.php">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="name" placeholder="Enter your full name" required>
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" placeholder="Enter your email" required>
    </div>

    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>
    </div>

    <div class="form-group">
      <label>Role</label>
      <select name="role" required>
        <option value="invitee" selected>Invitee</option>
        <option value="host">Host</option>
      </select>
    </div>

    <button type="submit">Register</button>

    <div class="forgot">
      <a href="login.php">Already have an account?</a>
    </div>
  </form>
</div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>