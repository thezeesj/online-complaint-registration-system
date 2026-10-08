<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || 
    !in_array($_SESSION['role'], ['student', 'faculty'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $_SESSION['user_id'];
    $category = trim($_POST['category']);
    $subject = trim($_POST['subject']);
    
    // Safety check for subject
    if (empty($subject) && $category !== 'Others') {
        $subject = $category;
    }
    
    $description = trim($_POST['description']);

    $proofPath = NULL;

    /* FILE UPLOAD HANDLING */
    if (!empty($_FILES['proof']['name'])) {

        $targetDir = "../uploads/";
        $fileName = time() . "_" . basename($_FILES["proof"]["name"]);
        $targetFile = $targetDir . $fileName;

        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowed = ['jpg','jpeg','png','pdf'];

        if (in_array($fileType, $allowed)) {
            move_uploaded_file($_FILES["proof"]["tmp_name"], $targetFile);
            $proofPath = $fileName;
        }
    }

    $status = 'Submitted';
    
    $sql = "INSERT INTO complaints 
            (user_id, category, subject, description, proof, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssss",
        $user_id,
        $category,
        $subject,
        $description,
        $proofPath,
        $status
    );

    if (mysqli_stmt_execute($stmt)) {
        // Send notification email if user has an email set
        if (!empty($_SESSION['email'])) {
            include_once "../common/email_helper.php";
            $to = $_SESSION['email'];
            $name = $_SESSION['name'];
            $emailSubject = "Complaint Submitted: " . $subject;
            $emailBody = "Hello $name,\n\nYour complaint has been successfully submitted.\n\nCategory: $category\nSubject: $subject\nStatus: Submitted\n\nYou will receive further updates as we review your complaint.\n\nRegards,\nComplaint System";
            
            sendNotificationEmail($to, $name, $emailSubject, $emailBody);
        }

        header("Location: complaint_status.php?success=1");
        exit;
    } else {
        die("Database Error: " . mysqli_error($conn));
    }
}
?>

