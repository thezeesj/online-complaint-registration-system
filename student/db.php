<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "complaint_system";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die("Database connection failed");
}
?>
