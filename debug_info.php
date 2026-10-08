<?php
// Set headers to force text output to avoiding HTML parsing issues in terminal
header('Content-Type: text/plain');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "complaint_system";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$logFile = 'debug_log.txt';
$handle = fopen($logFile, 'w');

function logTo($msg) {
    global $handle;
    fwrite($handle, $msg . "\n");
    echo $msg . "\n";
}

logTo("=== DEBUGGING CHAT SYSTEM ===");
logTo("Time: " . date("Y-m-d H:i:s"));

// Check Schema
logTo("\n--- TABLE STRUCTURE: complaint_chat ---");
$result = mysqli_query($conn, "DESCRIBE complaint_chat");
while ($row = mysqli_fetch_assoc($result)) {
    logTo(implode(" | ", $row));
}

// Check Users
logTo("\n--- USERS (sender_id verification) ---");
$result = mysqli_query($conn, "SELECT id, role, email FROM users LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    logTo("ID: {$row['id']} | Role: {$row['role']} | Email: {$row['email']}");
}

// Check Chat Content
logTo("\n--- CHAT MESSAGES (Last 20) ---");
$result = mysqli_query($conn, "SELECT * FROM complaint_chat ORDER BY created_at DESC LIMIT 20");
while ($row = mysqli_fetch_assoc($result)) {
    logTo("ID: {$row['id']} | ComplaintID: {$row['complaint_id']} | SenderID: {$row['sender_id']} | Role: {$row['sender_role']} | Msg: {$row['message']}");
}

fclose($handle);
logTo("\nLog saved to $logFile");
?>
