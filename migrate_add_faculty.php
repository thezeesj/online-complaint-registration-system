<?php
/**
 * Migration: Add 'faculty' to users.role ENUM
 */
include "config/db.php";

// Check current enum values
$res = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
$row = mysqli_fetch_assoc($res);
$type = $row['Type']; // e.g. enum('student','counselor')

if (strpos($type, "faculty") !== false) {
    echo '<h2 style="color: blue;">Role already includes faculty</h2>';
    echo '<p><a href="auth/login.php">Go to Login</a></p>';
    exit;
}

// Build new enum
$newEnum = "ENUM('student','faculty','counselor') NOT NULL";
$alter = "ALTER TABLE users MODIFY COLUMN role $newEnum";

if (mysqli_query($conn, $alter)) {
    echo '<h2 style="color: green;">✓ Migration Successful</h2>';
    echo '<p>Added \"faculty\" to users.role enum.</p>';
    echo '<p><a href="auth/login.php">Go to Login</a></p>';
} else {
    echo '<h2 style="color: red;">✗ Migration Failed</h2>';
    echo '<p>Error: ' . mysqli_error($conn) . '</p>';
}

mysqli_close($conn);
?>