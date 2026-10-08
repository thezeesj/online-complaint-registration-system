<?php
include "config/db.php";

echo "<h1>Debug Chat Table</h1>";

$result = mysqli_query($conn, "SELECT * FROM complaint_chat ORDER BY id DESC LIMIT 20");

if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}

echo "<table border='1'><tr><th>ID</th><th>Complaint ID</th><th>Sender ID</th><th>Sender Role</th><th>Message</th><th>Created At</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['complaint_id']}</td>";
    echo "<td>{$row['sender_id']}</td>";
    echo "<td>{$row['sender_role']}</td>";
    echo "<td>" . htmlspecialchars($row['message']) . "</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}

echo "</table>";
?>
