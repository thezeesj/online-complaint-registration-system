<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'counselor') {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        http_response_code(403);
        echo "Session mismatch.";
        exit;
    }
    header("Location: ../auth/login.php");
    exit;
}

$counselor_id = (int) $_SESSION['user_id'];

/* =========================
   HANDLE AJAX MESSAGE SEND
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_GET['id'])) {
        exit("No complaint ID");
    }

    $complaint_id = (int) $_GET['id'];

    if ($complaint_id <= 0) {
        exit("Invalid complaint ID");
    }

    if (!isset($_POST['message']) || trim($_POST['message']) === "") {
        exit("Empty message");
    }

    $message = trim($_POST['message']);

    $stmt = mysqli_prepare($conn,
        "INSERT INTO complaint_chat
         (complaint_id, sender_id, sender_role, message)
         VALUES (?, ?, 'counselor', ?)");
    mysqli_stmt_bind_param($stmt, "iis", $complaint_id, $counselor_id, $message);
    $insert = mysqli_stmt_execute($stmt);

    if (!$insert) {
        echo "DB Error: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);

    exit;
}

/* =========================
   NORMAL PAGE LOAD
========================= */
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($selected_id <= 0) {
    die("Invalid complaint.");
}

$stmt = mysqli_prepare($conn,
    "SELECT id, category 
     FROM complaints 
     WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $selected_id);
mysqli_stmt_execute($stmt);
$complaint_query = mysqli_stmt_get_result($stmt);
$complaint = mysqli_fetch_assoc($complaint_query);
mysqli_stmt_close($stmt);

if (!$complaint) {
    die("Complaint not found.");
}

/* =========================
   MARK AS READ
========================= */
mysqli_query($conn, 
    "UPDATE complaint_chat 
    SET is_read=1 
    WHERE complaint_id=$selected_id AND sender_role IN ('student', 'faculty')");

/* =========================
   GET UNREAD COUNT (For Sidebar)
========================= */
$unread_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaint_chat 
    WHERE sender_role IN ('student', 'faculty') 
    AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = $unread_row['cnt'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Counselor Chat</title>
    <link rel="stylesheet" href="../assets/css/style.css">
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>Counselor Panel</h2>

        <button class="side-btn"
            onclick="location.href='dashboard.php'">
            Dashboard
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

    <!-- MAIN CONTENT -->
    <div class="content home-layout">

        <div class="home-main">

            <h1>
                Complaint #<?= $complaint['id']; ?> -
                <?= htmlspecialchars($complaint['category']); ?>
            </h1>

            <div class="chat-container">

                <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
                     <a href="view.php?id=<?= $complaint['id'] ?>" 
                       class="info-btn" 
                       title="View Complaint Details"
                       style="font-size:12px; padding: 8px 12px;">
                        View Details
                    </a>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatBox"></div>

                <!-- Input -->
                <form id="chatForm" class="chat-input">
                    <input type="text"
                           id="messageInput"
                           placeholder="Type your message to the STUDENT..."
                           required>
                    <button type="submit">Send</button>
                </form>

            </div>
        </div>

        <div class="home-info container">
            <h2>Complaint Details</h2>
            <p><strong>ID:</strong> <?= $complaint['id']; ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($complaint['category']); ?></p>
        </div>

    </div>
</div>

<script>
const complaintId = <?= $selected_id ?>;
const chatBox = document.getElementById("chatBox");

/* LOAD MESSAGES */
function loadMessages() {
    fetch("fetch_messages.php?id=" + complaintId + "&t=" + new Date().getTime())
        .then(res => res.text())
        .then(data => {
            chatBox.innerHTML = data;
            // Only scroll if we were already at bottom or just loaded? 
            // For now, keep simple behavior but maybe improve later.
            // Actually, let's keep it simple to ensure they see new messages.
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

/* AUTO REFRESH */
setInterval(loadMessages, 2000);
loadMessages();

/* SEND MESSAGE */
document.getElementById("chatForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const messageInput = document.getElementById("messageInput");
    const message = messageInput.value.trim();

    if (message === "") return;

    fetch("chat.php?id=" + complaintId, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "message=" + encodeURIComponent(message)
    })
    .then(response => {
        if (response.status === 403) {
            alert("Session Error: You are logged in as a different user in another tab!\n\nPlease use an Incognito window or a different browser to test two users simultaneously.");
            throw new Error("Session mismatch");
        }
        return response.text();
    })
    .then(data => {
        console.log("Server:", data); // debug
        messageInput.value = "";
        loadMessages();
    })
    .catch(error => console.error(error));
});
</script>

</body>
</html>
