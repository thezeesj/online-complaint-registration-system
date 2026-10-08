<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id'])) exit;

$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($complaint_id <= 0) exit;

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// IDOR Protection: Verify that current user owns the complaint or has counselor/admin role
if (!in_array($user_role, ['counselor', 'admin'])) {
    $auth_stmt = mysqli_prepare($conn, "SELECT id FROM complaints WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($auth_stmt, "ii", $complaint_id, $user_id);
    mysqli_stmt_execute($auth_stmt);
    $auth_res = mysqli_stmt_get_result($auth_stmt);
    if (mysqli_num_rows($auth_res) === 0) {
        http_response_code(403);
        exit("Unauthorized");
    }
    mysqli_stmt_close($auth_stmt);
}

$stmt = mysqli_prepare($conn,
    "SELECT * FROM complaint_chat
     WHERE complaint_id = ?
     ORDER BY created_at ASC");
mysqli_stmt_bind_param($stmt, "i", $complaint_id);
mysqli_stmt_execute($stmt);
$messages = mysqli_stmt_get_result($stmt);

while ($msg = mysqli_fetch_assoc($messages)) {
    // Mark as sent by current user if sender_id matches session user
    $class = ($msg['sender_id'] == $user_id) ? 'message-sent' : 'message-received';

    echo "<div class='message $class'>";
    echo htmlspecialchars($msg['message']);
    echo "<div style='font-size:0.7em; text-align:right; opacity:0.7; margin-top:2px;'>" . format_date($msg['created_at']) . "</div>";
    echo "</div>";
}
mysqli_stmt_close($stmt);
?>
