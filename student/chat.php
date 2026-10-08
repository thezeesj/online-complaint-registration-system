<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['student','faculty'])) {
    // If it's an AJAX request (POST), return 403
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        http_response_code(403);
        echo "Session mismatch. Please use a separate browser/incognito window for each user.";
        exit;
    }
    header("Location: ../auth/login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* =========================
   AJAX SEND MESSAGE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // DEBUG LOGGING
    $log = "Timestamp: " . date("Y-m-d H:i:s") . "\n";
    $log .= "User ID: " . $_SESSION['user_id'] . "\n";
    $log .= "Complaint ID: " . $complaint_id . "\n";
    $log .= "Message: " . $_POST['message'] . "\n";

    if ($complaint_id > 0 && isset($_POST['message'])) {
        
        // CHECK IF COMPLAINT BELONGS TO CURRENT USER
        $owner_check = mysqli_prepare($conn, "SELECT id FROM complaints WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($owner_check, "ii", $complaint_id, $user_id);
        mysqli_stmt_execute($owner_check);
        $owner_res = mysqli_stmt_get_result($owner_check);
        if (mysqli_num_rows($owner_res) === 0) {
            http_response_code(403);
            exit("Error: Unauthorized access to complaint.");
        }
        mysqli_stmt_close($owner_check);

        // CHECK IF COUNSELOR HAS INITIATED THE CHAT
        $init_check = mysqli_query($conn, 
            "SELECT id FROM complaint_chat 
             WHERE complaint_id = $complaint_id 
             AND sender_role = 'counselor' 
             LIMIT 1");
        
        if (mysqli_num_rows($init_check) == 0) {
            http_response_code(403);
            exit("Error: You can only send messages after a counselor has initiated the chat.");
        }

        $message = trim($_POST['message']);

        if ($message !== "") {
            $role = $_SESSION['role'];

            $insert_stmt = mysqli_prepare($conn, 
                "INSERT INTO complaint_chat (complaint_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert_stmt, "iiss", $complaint_id, $user_id, $role, $message);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $log .= "Status: SUCCESS\n";
            } else {
                $log .= "Status: FAIL - " . mysqli_error($conn) . "\n";
            }
            mysqli_stmt_close($insert_stmt);
        } else {
             $log .= "Status: FAIL - Empty Message\n";
        }
    } else {
        $log .= "Status: FAIL - Invalid ID or Missing Message\n";
    }
    
    file_put_contents("debug_student.txt", $log . "------------------\n", FILE_APPEND);
    exit;
}

/* =========================
   MARK AS READ (if viewing a complaint)
========================= */
if ($selected_id > 0) {
    mysqli_query($conn, 
        "UPDATE complaint_chat 
        SET is_read=1 
        WHERE complaint_id=$selected_id AND sender_role='counselor'");
}

/* =========================
   GET UNREAD COUNT (For Sidebar)
========================= */
$unread_res = mysqli_query($conn, 
    "SELECT COUNT(*) as cnt 
    FROM complaint_chat 
    JOIN complaints ON complaint_chat.complaint_id = complaints.id 
    WHERE complaints.user_id = $user_id 
    AND sender_role = 'counselor' 
    AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_res);
$unread_count = $unread_row['cnt'];

/* =========================
   GET USER COMPLAINTS
========================= */
$complaints = mysqli_query($conn,
    "SELECT c.id, c.category,
    (SELECT COUNT(*) FROM complaint_chat cc 
     WHERE cc.complaint_id = c.id 
     AND cc.sender_role = 'counselor' 
     AND cc.is_read = 0) as unread
     FROM complaints c
     WHERE c.user_id = $user_id
     ORDER BY c.created_at DESC");

$complaint_details = null;
if ($selected_id > 0) {
    $dq = mysqli_query($conn, "SELECT * FROM complaints WHERE id = $selected_id AND user_id = $user_id");
    $complaint_details = mysqli_fetch_assoc($dq);
    
    // Check if chat is enabled (counselor has sent at least one message)
    $chat_enabled_res = mysqli_query($conn, 
        "SELECT id FROM complaint_chat 
         WHERE complaint_id = $selected_id 
         AND sender_role = 'counselor' 
         LIMIT 1");
    $chat_enabled = (mysqli_num_rows($chat_enabled_res) > 0);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat with Counselor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        <button class="side-btn" onclick="location.href='complaint_status.php'">Complaint Status</button>
        <button class="side-btn active">
            Chat with Counselor 
            <?= ($unread_count > 0) ? "<span class='badge'>$unread_count</span>" : "" ?>
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
            <button class="logout-btn"
                onclick="location.href='../auth/logout.php'">
                Logout
            </button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content home-layout">
        <div class="home-main">
            <h1>Chat with Counselor</h1>

            <!-- Complaint Dropdown -->
            <form method="GET" style="margin-bottom: 10px;">
                <label><b>Select Complaint:</b></label>
                <select name="id"
                        class="dropdown-select"
                        onchange="this.form.submit()"
                        style="padding: 8px; margin-left: 10px;">

                    <option value="">-- Select Complaint --</option>

                    <?php while($row = mysqli_fetch_assoc($complaints)) { 
                        $label = "#" . $row['id'] . " - " . htmlspecialchars($row['category']);
                        if ($row['unread'] > 0 && $row['id'] != $selected_id) {
                            $label .= " (" . $row['unread'] . " New 🔴)";
                        }
                    ?>
                        <option value="<?= $row['id'] ?>"
                            <?= ($selected_id == $row['id']) ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php } ?>

                </select>
            </form>

            <div class="chat-container">

                <?php if ($selected_id > 0): ?>

                    <?php if ($complaint_details): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; margin-bottom:10px;">
                            <h2 style="margin:0;">
                                <?= htmlspecialchars($complaint_details['category']) ?>
                                <span style="font-size: 0.8em; color: #666;">(#<?= $complaint_details['id'] ?>)</span>
                            </h2>
                            <a href="complaint_status.php?expand=<?= $complaint_details['id'] ?>#details<?= $complaint_details['id'] ?>" 
                               class="info-btn" 
                               title="View Complaint Details" 
                               style="font-size:12px; padding: 8px 12px;">
                                View Details
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- CHAT MESSAGES -->
                    <div id="chatBox" class="chat-messages"></div>

                    <!-- CHAT INPUT (Only if initiated by counselor) -->
                    <?php if ($chat_enabled): ?>
                        <form id="chatForm" class="chat-input">
                            <input type="text"
                                   id="messageInput"
                                   placeholder="Type your message..."
                                   autocomplete="off"
                                   required>
                            <button type="submit">Send</button>
                        </form>
                    <?php else: ?>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px dashed #cbd5e1; text-align: center; margin-top: 15px;">
                            <p style="color: #64748b; font-weight: 500; margin: 0;">
                                🔒 Chat is locked. You can send messages once a counselor starts the conversation.
                            </p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p style="margin-top:20px;">
                        Select a complaint to start chatting.
                    </p>
                <?php endif; ?>

            </div>
        </div>

        <div class="home-info container">
            <h2>Chat Information</h2>
            <p>Select a complaint to communicate with the counselor.</p>
        </div>
    </div>
</div>

<?php if ($selected_id > 0): ?>
<script>
const complaintId = <?= $selected_id ?>;
const chatBox = document.getElementById("chatBox");

/* LOAD MESSAGES */
function loadMessages() {
    fetch("fetch_messages.php?id=" + complaintId + "&t=" + new Date().getTime())
        .then(response => response.text())
        .then(data => {
            chatBox.innerHTML = data;
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

/* AUTO REFRESH EVERY 2 SECONDS */
setInterval(loadMessages, 2000);
loadMessages();

/* SEND MESSAGE */
const chatForm = document.getElementById("chatForm");
if (chatForm) {
    chatForm.addEventListener("submit", function(e) {
        e.preventDefault();

        const input = document.getElementById("messageInput");
        const message = input.value.trim();

        if (!message) return;

        fetch("chat.php?id=" + complaintId, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "message=" + encodeURIComponent(message)
        })
        .then(response => {
            if (response.status === 403) {
                alert("Session Error: You are logged in as a different user in another tab!\n\nPlease use an Incognito window or a different browser to test two users simultaneously.");
                return;
            }
            return response.text();
        })
        .then(() => {
            input.value = "";
            loadMessages();
        });
    });
}
</script>
<?php endif; ?>

</body>
</html>
