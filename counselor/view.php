<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    header("Location: ../auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$view_stmt = mysqli_prepare($conn, "UPDATE complaints SET counselor_viewed=1 WHERE id=?");
mysqli_stmt_bind_param($view_stmt, "i", $id);
mysqli_stmt_execute($view_stmt);
mysqli_stmt_close($view_stmt);

$c_stmt = mysqli_prepare($conn,
    "SELECT c.*, u.role as submitter_role
     FROM complaints c
     JOIN users u ON c.user_id = u.id
     WHERE c.id=?");
mysqli_stmt_bind_param($c_stmt, "i", $id);
mysqli_stmt_execute($c_stmt);
$c_res = mysqli_stmt_get_result($c_stmt);
$complaint = mysqli_fetch_assoc($c_res);
mysqli_stmt_close($c_stmt);

if (!$complaint) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_POST['update_status'])) {

    $status = trim($_POST['status'] ?? '');
    $allowed_statuses = ['Submitted', 'Under Review', 'Resolved', 'Rejected'];

    if (in_array($status, $allowed_statuses)) {
        $up_stmt = mysqli_prepare($conn,
            "UPDATE complaints 
             SET status=?,
                 user_unread=1
             WHERE id=?");
        mysqli_stmt_bind_param($up_stmt, "si", $status, $id);
        mysqli_stmt_execute($up_stmt);
        mysqli_stmt_close($up_stmt);

        // Fetch user email to send notification
        $user_stmt = mysqli_prepare($conn, 
            "SELECT u.email, u.name, c.subject 
             FROM complaints c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.id=?");
        mysqli_stmt_bind_param($user_stmt, "i", $id);
        mysqli_stmt_execute($user_stmt);
        $user_res = mysqli_stmt_get_result($user_stmt);
             
        if ($udrea = mysqli_fetch_assoc($user_res)) {
            if (!empty($udrea['email'])) {
                // Include the email helper
                require_once '../common/email_helper.php';

                $to = $udrea['email'];
                $subject = "Complaint Status Updated - ID #$id";
                
                // Use HTML for the body
                $msg = "Dear " . htmlspecialchars($udrea['name']) . ",<br><br>";
                $msg .= "The status of your complaint regarding '<b>" . htmlspecialchars($udrea['subject']) . "</b>' has been updated to: <b style='color:blue;'>" . htmlspecialchars($status) . "</b>.<br><br>";
                $msg .= "Please login to your dashboard to view the details.<br><br>";
                $msg .= "Regards,<br>Complaint System Team";
                
                // Send email using PHPMailer helper
                sendNotificationEmail($to, $udrea['name'], $subject, $msg);
            }
        }
        mysqli_stmt_close($user_stmt);
    }

    header("Location: view.php?id=".$id);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complaint View</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js" defer></script>
</head>

<?php
$role = $_SESSION['role'] ?? '';
$theme = '';
if ($role === 'counselor') $theme = 'theme-counselor';
elseif ($role === 'faculty') $theme = 'theme-faculty';
elseif ($role === 'student') $theme = 'theme-student';
?>

<body class="main-page counselor-page <?= $theme ?>">
<div class="home-container">

<div class="sidebar">
    <h2>Counselor Panel</h2>
    
    <button class="side-btn" onclick="location.href='dashboard.php'">
        Back
    </button>

    <div class="dark-mode-toggle">
        <span style="font-size: 14px;">Dark Mode</span>
        <label class="switch">
            <input type="checkbox" id="darkModeToggle" onclick="toggleDarkMode()">
            <span class="slider"></span>
        </label>
    </div>

    <div class="user-box">
        <div class="user-name">Counselor</div>
        <button class="logout-btn"
            onclick="location.href='../auth/logout.php'">
            Logout
        </button>
    </div>
</div>

<div class="content home-layout">
    <div class="home-main">
        <h1>Complaint #<?= $complaint['id'] ?></h1>

        <?php $icon = ($complaint['submitter_role'] === 'faculty') ? '👩‍🏫' : '🎓'; ?>
        <div class="complaint-card">
            <div class="submitter-icon<?= $complaint['submitter_role'] === 'faculty' ? ' faculty' : ' student' ?>" title="Submitted by: <?= htmlspecialchars($complaint['submitter_role']) ?>"><?= $icon ?></div>
            
            <p><b>Subject:</b> <?= htmlspecialchars($complaint['subject']) ?></p>
            <p><b>Category:</b> <?= htmlspecialchars($complaint['category']) ?></p>
            <p><b>Date:</b> <?= format_date($complaint['created_at']) ?></p>
            <p><b>Status:</b> 
                <?php
                $statusColor = 'black';
                switch($complaint['status']) {
                    case 'Submitted': $statusColor = 'text-submitted'; break;
                    case 'Under Review': $statusColor = 'text-under-review'; break;
                    case 'Resolved': $statusColor = 'text-resolved'; break;
                    case 'Rejected': $statusColor = 'text-rejected'; break;
                    default: $statusColor = '';
                }
                ?>
                <b class="<?= $statusColor ?>"><?= htmlspecialchars($complaint['status']) ?></b>
            </p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
            <p><b>Description:</b><br><br>
                <?= nl2br(htmlspecialchars($complaint['description'])) ?>
            </p>

            <?php if (!empty($complaint['proof'])): ?>
                <div style="margin-top:15px;">
                    <p><b>Submitted Proof:</b></p>
                    <?php
                    $filePath = "../uploads/" . htmlspecialchars($complaint['proof']);
                    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                        echo "<img src='$filePath' style='max-width:300px; border-radius:8px; margin-top:5px;'>";
                    } else {
                        echo "<a href='$filePath' target='_blank'>View File</a>";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <h3>Take Action</h3>
                <form method="POST" style="margin-top: 10px;">
                    <label>Update Status:</label>
                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                        <select name="status" class="dropdown-select" style="padding: 8px; flex: 1; max-width: 200px;">
                            <option <?= $complaint['status'] == 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                            <option <?= $complaint['status'] == 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                             <option <?= $complaint['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option <?= $complaint['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <button name="update_status" class="next-btn" style="padding: 8px 20px; margin:0;">Update</button>
                    </div>
                </form>
            </div>

            <div style="margin-top: 20px; text-align: right;">
                 <a href="chat.php?id=<?= $complaint['id'] ?>" class="view-btn" style="display: inline-flex; align-items: center; gap: 8px; font-size: 1.1em; padding: 10px 20px;">
                    💬 Open Chat
                </a>
            </div>

        </div>
    </div>
</div>
</div>
</body>
</html>
