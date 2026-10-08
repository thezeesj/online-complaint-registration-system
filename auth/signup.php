<?php
session_start();
include "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? 'student');
    $auth_type = $_POST['auth_type'] ?? 'email';
    
    $email = null;
    $admission_no = null;
    $identifier = '';

    if ($auth_type === 'email') {
        $email = trim($_POST['email'] ?? '');
        $identifier = $email;
    } else {
        $admission_no = trim($_POST['admission_no'] ?? '');
        $identifier = $admission_no;
    }

    // Basic validation
    if (empty($name) || empty($identifier) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else if ($auth_type === 'email' && empty($email)) {
        $error = "Email is required.";
    } else if ($role === 'counselor') {
        // Check if a counselor already exists
        $counselor_check = mysqli_query($conn, "SELECT id FROM users WHERE role='counselor'");
        if (mysqli_num_rows($counselor_check) > 0) {
            $error = "Counselor account already exists. Only one counselor allowed.";
        }
    } else if ($role === 'admin') {
        // Check if an admin already exists
        $admin_check = mysqli_query($conn, "SELECT id FROM users WHERE role='admin'");
        if (mysqli_num_rows($admin_check) > 0) {
            $error = "Admin account already exists. Only one admin allowed.";
        }
    }

    if (empty($error)) {
        // Check if user exists (by email or admission_no)
        if ($auth_type === 'email') {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
            mysqli_stmt_bind_param($stmt, "s", $email);
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE admission_no=?");
            mysqli_stmt_bind_param($stmt, "s", $admission_no);
        }
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = ($auth_type === 'email' ? "Email" : "Admission No") . " already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            // We need to handle NULLs properly.
            // Prepared statement structure depends on what we are inserting.
            if ($auth_type === 'email') {
                $insert = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($insert, "ssss", $name, $email, $hashed, $role);
            } else {
                $insert = mysqli_prepare($conn, "INSERT INTO users (name, admission_no, password, role) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($insert, "ssss", $name, $admission_no, $hashed, $role);
            }

            if (mysqli_stmt_execute($insert)) {
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email; // Might be null
                $_SESSION['admission_no'] = $admission_no; // Might be null
                $_SESSION['role'] = $role;

                // Redirect based on role
                if ($role === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } elseif ($role === 'counselor') {
                    header("Location: ../counselor/dashboard.php");
                } else {
                    header("Location: ../student/home.php");
                }
                exit;
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($insert);
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Sign Up</title>
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
  <h2>Sign Up</h2>

  <?php if (!empty($error)): ?>
    <p style="color:red;text-align:center;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <form method="POST" id="signupForm">

    <input
      type="text"
      name="name"
      placeholder="Full Name"
      required
    >

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
          placeholder="Email ID"
          required
        >
    </div>

    <input
      type="password"
      name="password"
      placeholder="Create Password"
      required
    >

    <select name="role" required>
      <option value="">I'm a</option>
      <option value="student">Student</option>
      <option value="faculty">Faculty</option>
      <option value="counselor">Counselor</option>
      <option value="admin">Admin</option>
    </select>

    <button type="submit">Register</button>
  </form>

  <div class="link">
    Already have an account?
    <a href="login.php">Login</a>
  </div>
</div>

<script>
function toggleAuthField() {
    const type = document.getElementById('authTypeSelector').value;
    const container = document.getElementById('authFieldContainer');
    
    if (type === 'email') {
        container.innerHTML = '<input type="text" name="email" id="authInput" placeholder="Email ID" required>';
    } else {
        container.innerHTML = '<input type="text" name="admission_no" id="authInput" placeholder="Admission Number" required>';
    }
}
</script>

</body>
</html>
