<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'counselor') {
    header("Location: ../auth/login.php");
    exit;
}

$page = $_GET['page'] ?? 'new';

/* =========================
   GET UNREAD COUNT (For Sidebar)
========================= */
$admin_unread_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaint_chat cc
    JOIN complaints c ON cc.complaint_id = c.id
    WHERE cc.sender_role = 'admin' 
    AND cc.is_read = 0
    AND c.status NOT IN ('Resolved', 'Rejected')");
$admin_unread_row = mysqli_fetch_assoc($admin_unread_res);
$admin_unread_count = $admin_unread_row['cnt'];

$unread_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
     FROM complaint_chat 
     WHERE sender_role IN ('student', 'faculty') 
     AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = $unread_row['cnt'];

$new_res = mysqli_query($conn,
    "SELECT COUNT(*) as cnt FROM complaints WHERE counselor_viewed=0 AND status NOT IN ('Resolved','Rejected')");
$new_row = mysqli_fetch_assoc($new_res);
$new_count = $new_row['cnt'];

function status_class($status) {
    switch($status) {
        case 'Submitted': return 'text-submitted';
        case 'Under Review': return 'text-under-review';
        case 'Resolved': return 'text-resolved';
        case 'Rejected': return 'text-rejected';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Counselor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js" defer></script>
    <style>
        .badge.yellow {
            background: #FFD700;
            color: #000;
        }
    </style>
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

    <button class="side-btn <?= $page=='new'?'active':'' ?>"
        onclick="location.href='dashboard.php?page=new'">
        New <?= ($new_count > 0) ? "<span class='badge small'>$new_count</span>" : "" ?>
    </button>

    <button class="side-btn <?= $page=='pending'?'active':'' ?>"
        onclick="location.href='dashboard.php?page=pending'">
        Pending Decision
    </button>

    <button class="side-btn <?= $page=='reminders'?'active':'' ?>"
        onclick="location.href='dashboard.php?page=reminders'">
        Admin Reminders <?= ($admin_unread_count > 0) ? "<span class='badge small yellow'>$admin_unread_count</span>" : "" ?>
    </button>

    <button class="side-btn <?= $page=='history'?'active':'' ?>"
        onclick="location.href='dashboard.php?page=history'">
        History
    </button>

    <button class="side-btn <?= $page=='chats'?'active':'' ?>"
        onclick="location.href='dashboard.php?page=chats'">
        Chats <?= ($unread_count > 0) ? "<span class='badge small'>$unread_count</span>" : "" ?>
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
        <div class="user-id">
            <?= htmlspecialchars($_SESSION['email'] ?? '') ?>
        </div>
        <button class="logout-btn"
            onclick="location.href='../auth/logout.php'">
            Logout
        </button>
    </div>
</div>

<div class="content">
<h1>Counselor Dashboard</h1>

<?php

/* ================= NEW ================= */
if ($page == 'new') {

    $complaints = mysqli_query($conn,
        "SELECT c.*, u.role as submitter_role
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         ORDER BY c.created_at DESC");

    echo "<h2>Registered Complaints</h2>";

    while($row = mysqli_fetch_assoc($complaints)) {
        $icon = ($row['submitter_role'] === 'faculty') ? '👩‍🏫' : '🎓';
        echo "<div class='complaint-card'>";
        echo "<div class='submitter-icon" . ($row['submitter_role'] === 'faculty' ? ' faculty' : ' student') . "' title='Submitted by: " . htmlspecialchars($row['submitter_role']) . "'>" . $icon . "</div>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Subject:</b> " . htmlspecialchars($row['subject']) . "</p>";
        echo "<p><b>Category:</b> ".htmlspecialchars($row['category'])."</p>";
        echo "<a href='view.php?id={$row['id']}' class='view-btn'>View Details</a>";
        echo "</div>";
    }
}

/* ================= PENDING ================= */
if ($page == 'pending') {

    echo "<h2>Not Viewed</h2>";

    $not_viewed = mysqli_query($conn,
        "SELECT c.*, u.role as submitter_role
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         WHERE c.counselor_viewed=0
         AND c.status NOT IN ('Resolved','Rejected')
         ORDER BY c.created_at DESC");

    while($row = mysqli_fetch_assoc($not_viewed)) {
        $icon = ($row['submitter_role'] === 'faculty') ? '👩‍🏫' : '🎓';
        echo "<div class='complaint-card'>";
        echo "<div class='submitter-icon" . ($row['submitter_role'] === 'faculty' ? ' faculty' : ' student') . "' title='Submitted by: " . htmlspecialchars($row['submitter_role']) . "'>" . $icon . "</div>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Subject:</b> " . htmlspecialchars($row['subject']) . "</p>";
        echo "<p><b>Category:</b> ".htmlspecialchars($row['category'])."</p>";
        echo "<p><b>Status:</b> {$row['status']}</p>";
        echo "<a href='view.php?id={$row['id']}' class='view-btn'>View Details</a>";
        echo "</div>";
    }

    echo "<h2>Viewed</h2>";

    $viewed = mysqli_query($conn,
        "SELECT c.*, u.role as submitter_role
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         WHERE c.counselor_viewed=1
         AND c.status NOT IN ('Resolved','Rejected')
         ORDER BY c.created_at DESC");

    while($row = mysqli_fetch_assoc($viewed)) {
        $icon = ($row['submitter_role'] === 'faculty') ? '👩‍🏫' : '🎓';
        echo "<div class='complaint-card'>";
        echo "<div class='submitter-icon" . ($row['submitter_role'] === 'faculty' ? ' faculty' : ' student') . "' title='Submitted by: " . htmlspecialchars($row['submitter_role']) . "'>" . $icon . "</div>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Subject:</b> " . htmlspecialchars($row['subject']) . "</p>";
        echo "<p><b>Category:</b> ".htmlspecialchars($row['category'])."</p>";
        echo "<p><b>Status:</b> {$row['status']}</p>";
        echo "<a href='view.php?id={$row['id']}' class='view-btn'>View Details</a>";
        echo "</div>";
    }
}

/* ================= REMINDERS (ADMIN) ================= */
if ($page == 'reminders') {
    echo "<h2>Admin Reminders</h2>";

    // Select complaints that have unread messages from 'admin'
    $reminders_q = mysqli_query($conn,
        "SELECT DISTINCT c.*, u.role as submitter_role
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         JOIN complaint_chat cc ON c.id = cc.complaint_id
         WHERE cc.sender_role = 'admin' AND cc.is_read = 0
         AND c.status NOT IN ('Resolved', 'Rejected')
         ORDER BY c.created_at DESC");

    if (mysqli_num_rows($reminders_q) == 0) {
        echo "<div class='empty-state'><p>No new reminders from Admin.</p></div>";
    }

    while($row = mysqli_fetch_assoc($reminders_q)) {
        $icon = ($row['submitter_role'] === 'faculty') ? '👩‍🏫' : '🎓';
        $cls = status_class($row['status']);
        echo "<div class='complaint-card' style='border-left: 5px solid #FFD700;'>";
        echo "<div class='submitter-icon" . ($row['submitter_role'] === 'faculty' ? ' faculty' : ' student') . "' title='Submitted by: " . htmlspecialchars($row['submitter_role']) . "'>" . $icon . "</div>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Subject:</b> " . htmlspecialchars($row['subject']) . "</p>";
        echo "<p><b>Category:</b> " . htmlspecialchars($row['category']) . "</p>";
        echo "<p><b>Status:</b> <b class='" . $cls . "'>" . htmlspecialchars($row['status']) . "</b></p>";
        echo "<p style='color:#d97706; font-weight:bold;'>⚠️ Admin has sent a reminder</p>";
        echo "<a href='view.php?id={$row['id']}' class='view-btn'>View Details</a>";
        echo "</div>";
    }
}

/* ================= HISTORY ================= */
if ($page == 'history') {
    echo "<h2>History (Resolved / Rejected)</h2>";

    $history_q = mysqli_query($conn,
        "SELECT c.*, u.role as submitter_role
         FROM complaints c
         JOIN users u ON c.user_id = u.id
         WHERE c.status IN ('Resolved','Rejected')
         ORDER BY c.created_at DESC");

    while($row = mysqli_fetch_assoc($history_q)) {
        $icon = ($row['submitter_role'] === 'faculty') ? '👩‍🏫' : '🎓';
        $cls = status_class($row['status']);
        echo "<div class='complaint-card'>";
        echo "<div class='submitter-icon" . ($row['submitter_role'] === 'faculty' ? ' faculty' : ' student') . "' title='Submitted by: " . htmlspecialchars($row['submitter_role']) . "'>" . $icon . "</div>";
        echo "<p><b>ID:</b> {$row['id']}</p>";
        echo "<p><b>Date:</b> " . format_date($row['created_at']) . "</p>";
        echo "<p><b>Subject:</b> " . htmlspecialchars($row['subject']) . "</p>";
        echo "<p><b>Category:</b> " . htmlspecialchars($row['category']) . "</p>";
        echo "<p><b>Status:</b> <b class='" . $cls . "'>" . htmlspecialchars($row['status']) . "</b></p>";
        echo "<a href='view.php?id={$row['id']}' class='view-btn'>View Details</a>";
        echo "</div>";
    }

}

/* ================= CHATS ================= */
if ($page == 'chats') {

    echo "<h2>Complaint Chats</h2>";

    $chat_list = mysqli_query($conn,
        "SELECT c.id, c.category, c.subject, c.created_at as complaint_date, c.counselor_viewed,
           u.id as student_id, u.name as student_name, u.role as submitter_role,
           MAX(complaint_chat.created_at) as last_msg_time,
           (SELECT COUNT(*) FROM complaint_chat un 
            WHERE un.complaint_id = c.id 
            AND un.sender_role IN ('student', 'faculty') 
            AND un.is_read = 0) as unread
        FROM complaint_chat
        JOIN complaints c ON complaint_chat.complaint_id = c.id
        JOIN users u ON c.user_id = u.id
        GROUP BY c.id
        ORDER BY last_msg_time DESC");

    while($chat = mysqli_fetch_assoc($chat_list)) {
        $icon = ($chat['submitter_role'] === 'faculty') ? '👩‍🏫' : '🎓';
        echo "<div class='complaint-card' style='display:flex; justify-content:space-between; align-items:center; padding: 15px;'>";
        echo "<div class='submitter-icon" . ($chat['submitter_role'] === 'faculty' ? ' faculty' : ' student') . "' title='Submitted by: " . htmlspecialchars($chat['submitter_role']) . "' style='position:relative; right:0; top:0; margin-right:10px;'>" . $icon . "</div>";
        
        // Left Side: Details
        echo "<div>";
        echo "<div style='font-size: 1.1em; font-weight: bold;'>Complaint #{$chat['id']} - " . htmlspecialchars($chat['category']) . "</div>";
        echo "<div style='color: #555; margin-top: 5px;'>User ID: <b class='user-id-blue'>#{$chat['student_id']}</b></div>";
        echo "<div style='color: #666; font-size: 0.9em; margin-top: 2px;'>Subject: " . htmlspecialchars($chat['subject']) . "</div>";
        echo "<div style='color: #888; font-size: 0.8em; margin-top: 5px;'>Last Activity: " . format_date($chat['last_msg_time']) . "</div>";
        echo "</div>";

        // Right Side: Action
        echo "<div style='text-align: right;'>";
        
        // New Complaint indicator (small badge) and unread messages count (round badge)
        if ($chat['counselor_viewed'] == 0) {
            echo "<span class='badge small' style='margin-right:8px;'>NEW</span>";
        }

        if ($chat['unread'] > 0) {
            echo "<span class='badge' style='margin-bottom: 5px; display: inline-block;'>{$chat['unread']}</span>";
        }
        echo "<a href='chat.php?id={$chat['id']}' class='view-btn' style='display: inline-flex; align-items: center; gap: 5px;'>";
        echo "💬 Open Chat"; 
        echo "</a>";
        echo "</div>";

        echo "</div>";
    }
}

?>

</div>
</div>
</body>
</html>
