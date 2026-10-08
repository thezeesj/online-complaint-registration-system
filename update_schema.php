<?php
include "config/db.php";
header('Content-Type: text/plain');

echo "SCHEMA UPDATE START\n";

// 1. Check if is_read column exists
$columns = mysqli_query($conn, "SHOW COLUMNS FROM complaint_chat LIKE 'is_read'");
if (mysqli_num_rows($columns) == 0) {
    echo "adding is_read column...\n";
    $sql = "ALTER TABLE complaint_chat ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message";
    if (mysqli_query($conn, $sql)) {
        echo "SUCCESS: is_read column added.\n";
    } else {
        echo "ERROR: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "OK: is_read column already exists.\n";
}

// 2. Check if sender_role includes faculty
$role_col = mysqli_query($conn, "SHOW COLUMNS FROM complaint_chat LIKE 'sender_role'");
if ($role_col && $role_row = mysqli_fetch_assoc($role_col)) {
    if (strpos($role_row['Type'], 'faculty') === false) {
        echo "Updating sender_role ENUM to include faculty...\n";
        $alter_role = "ALTER TABLE complaint_chat MODIFY COLUMN sender_role ENUM('student','counselor','faculty','admin') NOT NULL";
        if (mysqli_query($conn, $alter_role)) {
            echo "SUCCESS: sender_role ENUM updated to include faculty.\n";
        } else {
            echo "ERROR: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "OK: sender_role already includes faculty.\n";
    }
}

echo "SCHEMA UPDATE END\n";
?>
