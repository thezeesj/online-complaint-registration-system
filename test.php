<?php
session_start();
include "config/db.php";

// Create a test student account if needed
if (!isset($_SESSION['user_id'])) {
    // Try to auto-login a test account for debugging
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, role FROM users WHERE email = 'student@example.com' LIMIT 1");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complaint System - Test & Verify</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .success { background: #d4edda; border-color: #28a745; }
        .error { background: #f8d7da; border-color: #dc3545; }
        .info { background: #d1ecf1; border-color: #17a2b8; }
        button { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #2980b9; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<h1>Complaint System - Test & Verification</h1>

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="box success">
        <h3>✓ Logged in as: <?= htmlspecialchars($_SESSION['name']) ?></h3>
        <p>User ID: <?= $_SESSION['user_id'] ?></p>
        <p>Role: <?= $_SESSION['role'] ?></p>
    </div>
    
    <div class="box">
        <h3>Test 1: Database Connectivity</h3>
        <?php
            $test = mysqli_query($conn, "SELECT 1");
            if ($test) {
                echo "<p style='color: green;'>✓ Database connection successful</p>";
            } else {
                echo "<p style='color: red;'>✗ Database connection failed</p>";
            }
        ?>
    </div>
    
    <div class="box">
        <h3>Test 2: Check Complaint Status Page</h3>
        <?php
            $sql = "SELECT COUNT(*) as cnt FROM complaints WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            echo "<p>Total complaints: <strong>" . $row['cnt'] . "</strong></p>";
            
            if ($row['cnt'] > 0) {
                // Show first complaint
                $sql2 = "SELECT id, category, subject, status FROM complaints WHERE user_id = ? LIMIT 1";
                $stmt2 = mysqli_prepare($conn, $sql2);
                mysqli_stmt_bind_param($stmt2, "i", $_SESSION['user_id']);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                $complaint = mysqli_fetch_assoc($result2);
                
                echo "<p>Latest complaint:</p>";
                echo "<ul>";
                echo "<li>ID: {$complaint['id']}</li>";
                echo "<li>Category: {$complaint['category']}</li>";
                echo "<li>Subject: {$complaint['subject']}</li>";
                echo "<li>Status: {$complaint['status']}</li>";
                echo "</ul>";
            } else {
                echo "<p style='color: orange;'>⚠ No complaints found</p>";
            }
        ?>
    </div>
    
    <div class="box">
        <h3>Quick Navigation</h3>
        <p>
            <button onclick="location.href='student/home.php'">📝 Go to Home (Submit Complaint)</button>
            <button onclick="location.href='student/complaint_status.php'">📊 Go to Complaint Status</button>
            <button onclick="location.href='debug.php'">🔧 Full Debug Report</button>
        </p>
    </div>
    
    <div class="box">
        <h3>Test 3: Submit Test Complaint</h3>
        <form action="student/process_complaint.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="category" value="Academic">
            <input type="text" name="subject" placeholder="Test Subject" required style="width: 100%; padding: 10px; margin: 5px 0;">
            <textarea name="description" placeholder="Test Description" required style="width: 100%; padding: 10px; margin: 5px 0; height: 100px;"></textarea>
            <p>
                <button type="submit">📤 Submit Test Complaint</button>
            </p>
        </form>
    </div>

<?php else: ?>
    <div class="box error">
        <h3>✗ Not logged in</h3>
        <p>Please login first to access this page.</p>
        <p><button onclick="location.href='auth/login.php'">Go to Login</button></p>
    </div>
<?php endif; ?>

</body>
</html>
