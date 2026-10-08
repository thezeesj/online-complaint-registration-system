<?php
session_start();
include "../config/db.php";

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    die("Invalid request.");
}

// Verify token
$stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE reset_token=? AND reset_expires > NOW()");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("Invalid or expired token.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $update_stmt = mysqli_prepare($conn, "UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user['id']);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success = "Password has been reset successfully. You can now login.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        mysqli_stmt_close($update_stmt);
    }
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
      .form-group { margin-bottom: 15px; }
      input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; }
  </style>
</head>
<body class="auth-page">

<div class="container">
  <h2>Reset Password</h2>

  <?php if (!empty($error)): ?>
    <p style="color:red;text-align:center;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:green;text-align:center;"><?php echo htmlspecialchars($success); ?></p>
    <div class="link">
      <a href="login.php" class="btn">Go to Login</a>
    </div>
  <?php else: ?>
    <form method="POST">
      <div class="form-group">
          <input type="password" name="password" placeholder="New Password" required minlength="6">
      </div>
      <div class="form-group">
          <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
      </div>
      <button type="submit">Reset Password</button>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
