<?php
/**
 * Database Migration - Add is_read column to complaint_chat
 */

include "config/db.php";

// Check if is_read column exists
$check = mysqli_query($conn, "SHOW COLUMNS FROM complaint_chat LIKE 'is_read'");

if (mysqli_num_rows($check) == 0) {
    // Add the is_read column
    $alter = "ALTER TABLE complaint_chat ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER message";
    
    if (mysqli_query($conn, $alter)) {
        echo '<h2 style="color: green;">✓ Migration Successful!</h2>';
        echo '<p>Added <code>is_read</code> column to complaint_chat table.</p>';
        echo '<p><a href="auth/login.php">Go to Login</a></p>';
    } else {
        echo '<h2 style="color: red;">✗ Migration Failed</h2>';
        echo '<p>Error: ' . mysqli_error($conn) . '</p>';
    }
} else {
    echo '<h2 style="color: blue;">✓ Column Already Exists</h2>';
    echo '<p>The <code>is_read</code> column is already in the complaint_chat table.</p>';
    echo '<p><a href="auth/login.php">Go to Login</a></p>';
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        div {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
</body>
</html>
