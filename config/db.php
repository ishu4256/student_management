<?php
$conn = new mysqli("localhost","root","","student_mgmt");
session_start();

if ($conn->connect_error) {
    die("Database connection failed");
}
?>
