<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    exit;
}

$complaint_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Verify ownership and update
$sql = "UPDATE complaints SET user_unread = 0 
        WHERE id = ? AND user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $complaint_id, $user_id);
mysqli_stmt_execute($stmt);
?>
