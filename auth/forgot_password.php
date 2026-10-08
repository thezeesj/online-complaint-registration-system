<?php
session_start();
include "../config/db.php";
require_once '../PHPMailer-PHPMailer-3cd2a2a/src/Exception.php';
require_once '../PHPMailer-PHPMailer-3cd2a2a/src/PHPMailer.php';
require_once '../PHPMailer-PHPMailer-3cd2a2a/src/SMTP.php';
require_once '../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Email is required.";
    } else {
        // Check if email exists
        $stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));

            $update_stmt = mysqli_prepare($conn, "UPDATE users SET reset_token=?, reset_expires=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id=?");
            mysqli_stmt_bind_param($update_stmt, "si", $token, $user['id']);
            mysqli_stmt_execute($update_stmt);

            // Send email
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = (SMTP_SECURE === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;

                //Recipients
                $mail->setFrom(FROM_EMAIL, FROM_NAME);
                $mail->addAddress($email, $user['name']);

                //Content
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . str_replace("forgot_password.php", "reset_password.php", $_SERVER['PHP_SELF']) . "?token=" . $token;
                
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body    = "Hello " . htmlspecialchars($user['name']) . ",<br><br>
                                  You requested a password reset. Click the link below to reset your password:<br>
                                  <a href='$reset_link'>$reset_link</a><br><br>
                                  This link will expire in 1 hour.<br><br>
                                  If you did not request this, please ignore this email.";
                $mail->AltBody = "Hello " . $user['name'] . ",\n\nYou requested a password reset. Click the link below to reset your password:\n" . $reset_link . "\n\nThis link will expire in 1 hour.";

                $mail->send();
                $success = "A reset link has been sent to your email.";
            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            // Don't reveal if email exists or not for security, but user specifically asked for "login with the help of email id"
            // so we'll just say email not found if it's not there.
            $error = "No account found with that email.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
      .form-group { margin-bottom: 15px; }
      input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; }
  </style>
</head>
<body class="auth-page">

<div class="container">
  <h2>Forgot Password</h2>

  <?php if (!empty($error)): ?>
    <p style="color:red;text-align:center;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <p style="color:green;text-align:center;"><?php echo htmlspecialchars($success); ?></p>
  <?php else: ?>
    <form method="POST">
      <div class="form-group">
          <input type="email" name="email" placeholder="Enter your registered email" required>
      </div>
      <button type="submit">Send Reset Link</button>
    </form>
  <?php endif; ?>

  <div class="link">
    Remember your password?
    <a href="login.php">Login</a>
  </div>
</div>

</body>
</html>
