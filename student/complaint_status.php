<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || 
    !in_array($_SESSION['role'], ['student', 'faculty'])) {
    header("Location: ../auth/login.php");
    exit;
}

/* FETCH USER COMPLAINTS */
$sql = "SELECT id, category, subject, description, status, created_at, proof, user_unread
        FROM complaints
        WHERE user_id = ?
        ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

/* =========================
   GET UNREAD COUNT (For Sidebar)
========================= */
$unread_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaint_chat 
    JOIN complaints ON complaint_chat.complaint_id = complaints.id 
    WHERE complaints.user_id = {$_SESSION['user_id']} 
    AND sender_role = 'counselor' 
    AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = $unread_row['cnt'];

/* =========================
   GET STATUS UPDATE COUNT (For Sidebar)
========================= */
$status_update_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaints 
    WHERE user_id = {$_SESSION['user_id']} 
    AND user_unread = 1");
$status_update_row = mysqli_fetch_assoc($status_update_res);
$status_update_count = $status_update_row['cnt'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complaint Status</title>
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

<body class="main-page <?= $theme ?>">
<div class="home-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>User Dashboard</h2>

        <button class="side-btn" onclick="location.href='home.php'">Home</button>
   

        <button class="side-btn active">Complaint Status <?= ($status_update_count > 0) ? "<span class='badge'>$status_update_count</span>" : "" ?></button>
        <button class="side-btn" onclick="location.href='chat.php'">
            Chat with Counselor <?= ($unread_count > 0) ? "<span class='badge'>$unread_count</span>" : "" ?>
        </button>
        <button class="side-btn" onclick="location.href='feedback.php'">Feedback</button>

        <div class="dark-mode-toggle">
            <span style="font-size: 14px;">Dark Mode</span>
            <label class="switch">
                <input type="checkbox" id="darkModeToggle" onclick="toggleDarkMode()">
                <span class="slider"></span>
            </label>
        </div>

  <div class="user-box">
            <div class="user-name">
                <?= htmlspecialchars($_SESSION['name']) ?>
            </div>
            <div class="user-id">
                <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
            </div>
            <div class="user-id">
                <?= htmlspecialchars($_SESSION['admission_no'] ?? '') ?>
            </div>
            <button class="logout-btn" onclick="location.href='../auth/logout.php'">
                Logout
            </button>
        </div>
            

        
    </div>

    <!-- MAIN CONTENT -->
    <div class="content home-layout">

        <!-- LEFT SIDE -->
        <div class="home-main">
            <h1>Complaint Status</h1>

            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="complaint-card">

                        <p><b>Category:</b> 
                                <?= htmlspecialchars($row['category']) ?>
                            </p>
                            <p><b>Complaint ID:</b> #<?= $row['id'] ?></p>
                        
                        <p><b>Status:</b> 
                            <?php
                            $statusColor = 'black';
                            switch($row['status']) {
                                case 'Submitted': $statusColor = 'text-submitted'; break;
                                case 'Under Review': $statusColor = 'text-under-review'; break;
                                case 'Resolved': $statusColor = 'text-resolved'; break;
                                case 'Rejected': $statusColor = 'text-rejected'; break;
                                default: $statusColor = '';
                            }
                            ?>
                            <span class="<?= $statusColor ?>"><?= htmlspecialchars($row['status']) ?></span>
                        </p>
                        <p><b>Submitted On:</b> <?= format_date($row['created_at']) ?></p>

                        <!-- View Button -->
                        <button class="next-btn"
                                onclick="toggleDetails('details<?= $row['id'] ?>', <?= $row['id'] ?>)">
                            View Details 
                            <?php if ($row['user_unread'] == 1): ?>
                                <span id="badge_<?= $row['id'] ?>" class="badge" style="background-color: #ef4444; margin-left: 5px;">Updated</span>
                            <?php endif; ?>
                        </button>

                        <!-- Hidden Details -->
                        <div id="details<?= $row['id'] ?>" 
                             style="display:none; margin-top:15px;">

                            <p><b>Subject:</b> <?= htmlspecialchars($row['subject']) ?></p>

                            <p><b>Description:</b><br>
                                <?= nl2br(htmlspecialchars($row['description'])) ?>
                            </p>

                            <!-- PROOF SECTION -->
                            <?php if (!empty($row['proof'])) { ?>
                                <div style="margin-top:10px;">
                                    <p><b>Submitted Proof:</b></p>

                                    <?php
                                    $filePath = "../uploads/" . htmlspecialchars($row['proof']);
                                    $fileExt = strtolower(pathinfo($row['proof'], PATHINFO_EXTENSION));
                                    ?>

                                    <?php if (in_array($fileExt, ['jpg','jpeg','png','gif','webp'])) { ?>
                                        <!-- Image Preview -->
                                        <img src="<?= $filePath ?>" 
                                             alt="Proof Image"
                                             style="max-width:250px; border-radius:8px; margin-top:5px;">
                                    <?php } else { ?>
                                        <!-- Other File -->
                                        <a href="<?= $filePath ?>" target="_blank">
                                            View Uploaded File
                                        </a>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <p><b>Submitted Proof:</b> No file uploaded</p>
                            <?php } ?>

                        </div>

                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="empty-state">
                    <p>No complaints submitted yet.</p>
                </div>
            <?php } ?>

        </div>

        <!-- RIGHT SIDE -->
        <div class="home-info container">
            <h2>Status Information</h2>
            <p>
                Click <b>"View Details"</b> to see category, description,
                and uploaded proof.
            </p>
        </div>

    </div>

</div>

<script>
function toggleDetails(elementId, complaintId) {
    var element = document.getElementById(elementId);
    if (element.style.display === "none") {
        element.style.display = "block";
        
        // Mark as read AJAX
        if (complaintId) {
            var badge = document.getElementById('badge_' + complaintId);
            if (badge) {
                fetch('mark_complaint_read.php?id=' + complaintId)
                    .then(() => badge.style.display = 'none');
            }
        }

    } else {
        element.style.display = "none";
    }
}

// Auto-expand if 'expand' param is present
window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const expandId = urlParams.get('expand');
    if (expandId) {
        const detailsDiv = document.getElementById('details' + expandId);
        if (detailsDiv) {
            detailsDiv.style.display = 'block';
            detailsDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
};
</script>

</body>
</html>
