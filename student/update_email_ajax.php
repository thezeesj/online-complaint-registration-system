<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $user_id = $_SESSION['user_id'];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    // Update in database
    $stmt = mysqli_prepare($conn, "UPDATE users SET email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['email'] = $email; // Update session
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
