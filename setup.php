<?php
/**
 * Database Setup Script
 * Run this once to initialize the complaint system database
 */

$host = "localhost";
$user = "root";
$pass = "";

// Connect without selecting database
$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = [];

// SQL Setup
$sqls = [
    // Drop existing if needed
    "DROP TABLE IF EXISTS feedback",
    "DROP TABLE IF EXISTS complaint_chat",
    "DROP TABLE IF EXISTS complaints",
    "DROP TABLE IF EXISTS users",
    
    // Create users table
    "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('student','counselor') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Create complaints table
    "CREATE TABLE complaints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        category VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'Submitted',
        proof VARCHAR(255),
        counselor_viewed TINYINT(1) DEFAULT 0,
        user_unread TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (user_id),
        INDEX (status)
    )",
    
    // Create complaint_chat table
    "CREATE TABLE complaint_chat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        complaint_id INT NOT NULL,
        sender_id INT NOT NULL,
        sender_role ENUM('student','counselor') NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (complaint_id),
        INDEX (sender_id)
    )",
    
    // Create feedback table
    "CREATE TABLE feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        complaint_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
        INDEX (user_id),
        INDEX (complaint_id)
    )"
];

// Create database first
$create_db = "CREATE DATABASE IF NOT EXISTS complaint_system";
if (mysqli_query($conn, $create_db)) {
    $success[] = "✓ Database created/exists";
} else {
    $errors[] = "✗ Failed to create database: " . mysqli_error($conn);
    die("<h2>Setup Failed</h2><p>" . implode("<br>", $errors) . "</p>");
}

// Select database
if (!mysqli_select_db($conn, "complaint_system")) {
    die("✗ Failed to select database: " . mysqli_error($conn));
}

// Execute all SQL statements
foreach ($sqls as $sql) {
    if (mysqli_query($conn, $sql)) {
        $success[] = "✓ " . substr($sql, 0, 40) . "...";
    } else {
        $errors[] = "✗ " . mysqli_error($conn);
    }
}

// Insert test accounts
$test_accounts = [
    ['Demo Student', 'student@example.com', 'password', 'student'],
    ['Demo Counselor', 'counselor@example.com', 'password', 'counselor']
];

foreach ($test_accounts as $account) {
    $name = $account[0];
    $email = $account[1];
    $password = password_hash($account[2], PASSWORD_DEFAULT);
    $role = $account[3];
    
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        $success[] = "✓ Created test account: $email";
    } else {
        $errors[] = "✗ Failed to create test account: $email";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complaint System - Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .success {
            color: #27ae60;
            margin: 10px 0;
        }
        .error {
            color: #e74c3c;
            margin: 10px 0;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #3498db;
        }
        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✓ Setup Complete!</h1>
        
        <div class="section">
            <h3>Database Setup Results:</h3>
            <?php foreach ($success as $msg): ?>
                <div class="success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $msg): ?>
                <div class="error"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h3>Test Credentials:</h3>
            <p><strong>Student:</strong><br>
            Email: student@example.com<br>
            Password: password</p>
            
            <p><strong>Counselor:</strong><br>
            Email: counselor@example.com<br>
            Password: password</p>
        </div>
        
        <div style="text-align: center;">
            <a href="auth/login.php" class="button">Go to Login</a>
        </div>
    </div>
</body>
</html>
