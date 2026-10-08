<?php
/**
 * Database Migration - Add proof column to complaints
 */

include "config/db.php";

// Check if proof column exists
$check = mysqli_query($conn, "SHOW COLUMNS FROM complaints LIKE 'proof'");

if (mysqli_num_rows($check) == 0) {
    // Add the proof column
    $alter = "ALTER TABLE complaints ADD COLUMN proof VARCHAR(255) NULL AFTER description";
    
    if (mysqli_query($conn, $alter)) {
        echo '<h2 style="color: green;">✓ Migration Successful!</h2>';
        echo '<p>Added <code>proof</code> column to complaints table.</p>';
        echo '<p><a href="student/home.php">Go to Home</a></p>';
    } else {
        echo '<h2 style="color: red;">✗ Migration Failed</h2>';
        echo '<p>Error: ' . mysqli_error($conn) . '</p>';
    }
} else {
    echo '<h2 style="color: blue;">✓ Column Already Exists</h2>';
    echo '<p>The <code>proof</code> column is already in the complaints table.</p>';
    echo '<p><a href="student/home.php">Go to Home</a></p>';
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
