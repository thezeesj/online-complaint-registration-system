<?php
session_start();
include "config/db.php";

echo "<!DOCTYPE html><html><head><title>Complaint System Debug</title><style>";
echo "body { font-family: Arial; padding: 20px; background: #f5f5f5; }";
echo ".box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }";
echo ".success { color: green; } .error { color: red; } .info { color: blue; }";
echo "</style></head><body><h1>Complaint System Debug Report</h1>";

// Check database connection
echo "<div class='box'>";
if ($conn) {
    echo "<p class='success'>✓ Database Connected</p>";
    
    // Check tables exist
    $tables = ['users', 'complaints', 'complaint_chat', 'feedback'];
    foreach ($tables as $table) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($check) > 0) {
            echo "<p class='success'>✓ Table '$table' exists</p>";
        } else {
            echo "<p class='error'>✗ Table '$table' NOT FOUND</p>";
        }
    }
    
    // Check columns in complaints table
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM complaints");
    echo "<p><b>Complaints table columns:</b></p>";
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
    
    // Check columns in complaint_chat table
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM complaint_chat");
    echo "<p><b>Complaint_chat table columns:</b></p>";
    while ($col = mysqli_fetch_assoc($columns)) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
    }
    
} else {
    echo "<p class='error'>✗ Database Connection Failed</p>";
}
echo "</div>";

// Check file permissions
echo "<div class='box'>";
echo "<p><b>File Permissions:</b></p>";
$uploadDir = "uploads";
if (is_dir($uploadDir)) {
    echo "<p class='success'>✓ Uploads directory exists</p>";
    if (is_writable($uploadDir)) {
        echo "<p class='success'>✓ Uploads directory is writable</p>";
    } else {
        echo "<p class='error'>✗ Uploads directory is NOT writable</p>";
    }
} else {
    echo "<p class='error'>✗ Uploads directory does NOT exist</p>";
}
echo "</div>";

// Check session
echo "<div class='box'>";
echo "<p><b>Session Data:</b></p>";
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✓ User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p class='success'>✓ User Name: " . ($_SESSION['name'] ?? 'N/A') . "</p>";
    echo "<p class='success'>✓ User Role: " . ($_SESSION['role'] ?? 'N/A') . "</p>";
} else {
    echo "<p class='info'>No active session - Login first to test</p>";
}
echo "</div>";

// Test query
if (isset($_SESSION['user_id'])) {
    echo "<div class='box'>";
    echo "<p><b>Test Query Results:</b></p>";
    
    $sql = "SELECT id, category, subject, status FROM complaints WHERE user_id = ? LIMIT 5";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p class='success'>✓ Found " . mysqli_num_rows($result) . " complaints</p>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "- ID: {$row['id']}, Category: {$row['category']}, Status: {$row['status']}<br>";
        }
    } else {
        echo "<p class='info'>No complaints found (this is normal if none submitted yet)</p>";
    }
    
    echo "</div>";
}

echo "<p style='text-align:center; margin-top: 30px;'>";
echo "<a href='student/complaint_status.php' style='padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Go to Complaint Status</a> ";
echo "<a href='student/home.php' style='padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;'>Go to Home</a>";
echo "</p>";

echo "</body></html>";
?>
