<?php
include "config/db.php";
header('Content-Type: text/plain');

echo "DIAGNOSIS START\n";

// 1. Check Table Structure
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'complaint_chat'");
if (mysqli_num_rows($table_exists) == 0) {
    echo "ERROR: Table 'complaint_chat' does not exist.\n";
    // Attempt creation
    $sql = "CREATE TABLE IF NOT EXISTS complaint_chat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        complaint_id INT NOT NULL,
        sender_id INT NOT NULL,
        sender_role VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if (mysqli_query($conn, $sql)) {
        echo "FIX: Table 'complaint_chat' created.\n";
    } else {
        echo "ERROR: Could not create table: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "OK: Table 'complaint_chat' exists.\n";
    $columns = mysqli_query($conn, "DESCRIBE complaint_chat");
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "COLUMN: {$col['Field']} | Type: {$col['Type']}\n";
    }
}

// 2. Test Insertions (Simulating)
echo "\nTESTING INSERTIONS:\n";

// Simulate Student (ID 999)
$test_msg_s = "Test Student " . time();
$sql = "INSERT INTO complaint_chat (complaint_id, sender_id, sender_role, message) VALUES (1, 999, 'student', '$test_msg_s')";
if (mysqli_query($conn, $sql)) {
    echo "INSERT STUDENT: OK\n";
} else {
    echo "INSERT STUDENT: FAIL " . mysqli_error($conn) . "\n";
}

// Simulate Counselor (ID 888)
$test_msg_c = "Test Counselor " . time();
$sql = "INSERT INTO complaint_chat (complaint_id, sender_id, sender_role, message) VALUES (1, 888, 'counselor', '$test_msg_c')";
if (mysqli_query($conn, $sql)) {
    echo "INSERT COUNSELOR: OK\n";
} else {
    echo "INSERT COUNSELOR: FAIL " . mysqli_error($conn) . "\n";
}

// 3. Read Verification
echo "\nREAD VERIFICATION:\n";
$res = mysqli_query($conn, "SELECT * FROM complaint_chat ORDER BY id DESC LIMIT 2");
while ($row = mysqli_fetch_assoc($res)) {
    echo "MSG: {$row['sender_role']} -> {$row['message']}\n";
}

echo "DIAGNOSIS END";
?>
