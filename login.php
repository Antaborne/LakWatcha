<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $conn->real_escape_string($_POST['email']);
  $password = $_POST['password'];

  $query = "SELECT * FROM users WHERE email='$email'";
  $result = $conn->query($query);

  if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = strtolower($user['role']);
      header("Location: dashboard.php");
      exit();
    } else {
      $error = "Invalid password.";
    }
  } else {
    $error = "User not found.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - LakWatcha</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="container">
  <h2>LakWatcha</h2>
  <div class="tabs">
    <a href="login.php" class="active">Login</a>
    <a href="register.php">Signup</a>
  </div>

  <form method="POST" action="login.php">
  <div class="form-group">
    <label>Email</label>
    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
  </div>

  <div class="form-group">
    <label>Password</label>
    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
  </div>

  <button type="submit" class="btn btn-success">Login</button>

  <div class="forgot">
    <a href="#">Forgot Password?</a>
  </div>
</form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>