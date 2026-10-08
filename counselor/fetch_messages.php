<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['counselor', 'admin'])) {
    exit;
}

$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($complaint_id <= 0) exit;

$stmt = mysqli_prepare($conn,
    "SELECT * FROM complaint_chat
     WHERE complaint_id = ?
     ORDER BY created_at ASC");
mysqli_stmt_bind_param($stmt, "i", $complaint_id);
mysqli_stmt_execute($stmt);
$messages = mysqli_stmt_get_result($stmt);

while ($msg = mysqli_fetch_assoc($messages)) {
    if ($msg['sender_role'] == 'admin') {
        $class = 'admin-message';
        echo "<div class='message $class'>";
        echo "⚠️ ADMIN REMINDER: " . htmlspecialchars($msg['message']);
        echo "<div style='font-size:0.7em; opacity:0.7; margin-top:2px;'>" . format_date($msg['created_at']) . "</div>";
        echo "</div>";
    } else {
        $class = ($msg['sender_role'] == 'counselor')
            ? 'message-sent'
            : 'message-received';
    
        echo "<div class='message $class'>";
        echo htmlspecialchars($msg['message']);
        echo "<div style='font-size:0.7em; text-align:right; opacity:0.7; margin-top:2px;'>" . format_date($msg['created_at']) . "</div>";
        echo "</div>";
    }
}
mysqli_stmt_close($stmt);
?>
