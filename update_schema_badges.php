<?php
include "config/db.php";

// Add counselor_viewed column
$check = mysqli_query($conn, "SHOW COLUMNS FROM complaints LIKE 'counselor_viewed'");
if (mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE complaints ADD COLUMN counselor_viewed TINYINT(1) DEFAULT 0");
    echo "Added counselor_viewed column.<br>";
} else {
    echo "counselor_viewed already exists.<br>";
}

// Add user_unread column
$check2 = mysqli_query($conn, "SHOW COLUMNS FROM complaints LIKE 'user_unread'");
if (mysqli_num_rows($check2) == 0) {
    mysqli_query($conn, "ALTER TABLE complaints ADD COLUMN user_unread TINYINT(1) DEFAULT 0");
    echo "Added user_unread column.<br>";
} else {
    echo "user_unread already exists.<br>";
}

echo "Schema update complete.";
?>
