<?php
include "../config/db.php";
$res = mysqli_query($conn, "ALTER TABLE feedback ADD COLUMN is_read TINYINT DEFAULT 0");
if ($res) {
    echo "Success: is_read column added to feedback table.";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
