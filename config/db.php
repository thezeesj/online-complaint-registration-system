<?php
$conn = mysqli_connect("localhost", "root", "", "complaint_system");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");


function format_date($date) {
    if (!$date || $date == '0000-00-00 00:00:00') return '';
    return date('d/m/Y h:i A', strtotime($date));
}

