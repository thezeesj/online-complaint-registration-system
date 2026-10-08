<?php
session_start();

// If no logged-in user, redirect to login
if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
}

// If confirmation posted, destroy session and redirect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
        // clear session array
        $_SESSION = [];
        // delete session cookie if present
        if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                        $params['path'], $params['domain'],
                        $params['secure'], $params['httponly']
                );
        }
        session_destroy();
        header('Location: login.php');
        exit;
}

$user_id = htmlspecialchars((string)($_SESSION['user_id'] ?? ''));
$identifier_label = !empty($_SESSION['email']) ? 'Email' : 'Admission No';
$identifier_val = htmlspecialchars((string)(!empty($_SESSION['email']) ? $_SESSION['email'] : ($_SESSION['admission_no'] ?? '')));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logout</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .logout-info { max-width: 480px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        .logout-info p { margin: 6px 0; }
        .logout-actions { margin-top: 18px; display:flex; gap:12px; }
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
    <div class="container logout-info">
        <h2>Confirm Logout</h2>
        <p><strong>User ID:</strong> <?= $user_id ?></p>
        <p><strong><?= $identifier_label ?>:</strong> <?= $identifier_val ?></p>
        <form method="POST">
            <div class="logout-actions">
                <button type="submit" name="confirm_logout" class="primary-btn">Logout</button>
                <a href="javascript:history.back()" class="action-btn secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
