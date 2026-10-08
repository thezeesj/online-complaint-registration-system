<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "Unauthorized";
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    // Check if complaint exists
    $check = mysqli_query($conn, "SELECT id FROM complaints WHERE id=$id");
    if (mysqli_num_rows($check) > 0) {
        // Find counselor ID - assuming single counselor or broadcasting
        // For this system, we post a message to the chat as 'admin'
        // or effectively a system message.
        // NOTE: The user asked to "send a remind message to counselor".
        // Often this means email, but since we don't have email config,
        // we will log it in the chat as a system/admin message.
        
        // We need an admin user ID. We use the current session user_id.
        $admin_id = $_SESSION['user_id'];
        $message = "[SYSTEM REMINDER] This complaint has been pending for a while. Please take action.";
        
        // Insert into chat. Convert 'admin' role to something compatible or update enum
        // We updated the enum in database_setup.sql.
        
        $sql = "INSERT INTO complaint_chat (complaint_id, sender_id, sender_role, message, is_read) 
                VALUES ($id, $admin_id, 'admin', ?, 0)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $message);
        
        if (mysqli_stmt_execute($stmt)) {
            // Success: Chat message sent.
            // Now send EMAIL to all counselors.
            require_once '../common/email_helper.php';
            
            // Fetch all counselors
            $counselor_q = mysqli_query($conn, "SELECT email, name FROM users WHERE role='counselor'");
            
            $email_count = 0;
            while ($c = mysqli_fetch_assoc($counselor_q)) {
                if (!empty($c['email'])) {
                    $subject = "Admin Reminder - Complaint ID #$id";
                    $body = "Dear " . htmlspecialchars($c['name']) . ",<br><br>";
                    $body .= "The Administrator has sent a reminder regarding Complaint ID #$id.<br>";
                    $body .= "Please log in to the dashboard and take necessary action.<br><br>";
                    $body .= "Message: <i>" . htmlspecialchars($message) . "</i><br><br>";
                    $body .= "Regards,<br>Complaint System Admin";
                    
                    sendNotificationEmail($c['email'], $c['name'], $subject, $body);
                    $email_count++;
                }
            }

            echo "Reminder sent to chat and emailed to $email_count counselor(s).";
        } else {
            echo "Error sending reminder: " . mysqli_error($conn);
        }
    } else {
        echo "Complaint not found.";
    }
} else {
    echo "Invalid ID";
}
?>
