<?php
include "config/db.php";

$sql = "ALTER TABLE users 
        ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL,
        ADD COLUMN reset_expires DATETIME DEFAULT NULL,
        ADD INDEX (reset_token)";

if (mysqli_query($conn, $sql)) {
    echo "Successfully updated users table schema with reset_token and reset_expires columns.";
} else {
    echo "Error updating schema: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
