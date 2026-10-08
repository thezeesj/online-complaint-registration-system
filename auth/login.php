<?php
session_start();
include "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth_type = $_POST['auth_type'] ?? 'email';
    $identifier = '';
    $password = $_POST['password'] ?? '';

    if ($auth_type === 'email') {
        $identifier = trim($_POST['email'] ?? '');
    } else {
        $identifier = trim($_POST['admission_no'] ?? '');
    }

    // Validation
    if (empty($identifier) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Query user by email or admission_no
        if ($auth_type === 'email') {
            $stmt = mysqli_prepare($conn, "SELECT id, name, email, admission_no, password, role FROM users WHERE email=?");
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id, name, email, admission_no, password, role FROM users WHERE admission_no=?");
        }
        
        mysqli_stmt_bind_param($stmt, "s", $identifier);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['admission_no'] = $user['admission_no'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'counselor') {
                header("Location: ../counselor/dashboard.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../student/home.php");
            }
            exit;
        } else {
            $error = "Invalid " . ($auth_type === 'email' ? "email" : "admission no") . " or password.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="../assets/js/script.js" defer></script>
  <style>
      .form-group { margin-bottom: 15px; }
      select, input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; }
  </style>
</head>

<?php
$role = $_SESSION['role'] ?? '';
$theme = '';
if ($role === 'counselor') $theme = 'theme-counselor';
elseif ($role === 'faculty') $theme = 'theme-faculty';
elseif ($role === 'student') $theme = 'theme-student';
?>

<body class="auth-page <?= $theme ?>">

<div class="title-wrapper">
  <div class="site-title">Online complaint registration system</div>
</div>

<div class="container">
  <h2>Login</h2>

  <?php if (!empty($error)): ?>
    <p style="color:red;text-align:center;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <p id="successMsg" style="display:none;color:green;text-align:center;">
    Account created successfully. Please login.
  </p>

  <form method="POST">
    
    <div class="form-group">
        <select name="auth_type" id="authTypeSelector" onchange="toggleAuthField()">
            <option value="email">Use Email</option>
            <option value="admission_no">Use Admission No</option>
        </select>
    </div>

    <!-- Container for dynamic input -->
    <div id="authFieldContainer">
        <input
          type="text"
          name="email"
          id="authInput"
          placeholder="Email"
          required
        >
    </div>

    <input type="password" name="password" placeholder="Password" required>

    <div style="text-align: right; margin-top: -10px; margin-bottom: 20px;">
        <a href="forgot_password.php" style="font-size: 0.9em;">Forgot Password?</a>
    </div>

    <button type="submit">Login</button>
  </form>

  <div class="link">
    New user?
    <a href="signup.php">Sign Up</a>
  </div>
</div>

<script>
  const params = new URLSearchParams(window.location.search);
  if (params.get("success") === "1") {
    document.getElementById("successMsg").style.display = "block";
  }

  function toggleAuthField() {
    const type = document.getElementById('authTypeSelector').value;
    const container = document.getElementById('authFieldContainer');
    
    if (type === 'email') {
        container.innerHTML = '<input type="text" name="email" id="authInput" placeholder="Email" required>';
    } else {
        container.innerHTML = '<input type="text" name="admission_no" id="authInput" placeholder="Admission Number" required>';
    }
  }
</script>



</body>
</html>
