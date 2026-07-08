<?php
include "config/db.php";

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    
    // දත්ත ගොනුවෙන් ශිෂ්‍යයාව ඉවත් කිරීම
    $conn->query("DELETE FROM students WHERE id='$id'");
    
    header("Location: student.php?success=deleted");
    exit();
} else {
    header("Location: student.php");
    exit();
}
?>