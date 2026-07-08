<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$dept_id = $_SESSION['departmentid'] ?? 0;

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    
    // ආරක්ෂාව සඳහා තමන්ගේ දෙපාර්තමේන්තුවේ කෙනෙක් නම් පමණක් ඩිලීට් කිරීමට ඉඩ දෙයි
    $conn->query("DELETE FROM students WHERE id='$id' AND departmentid='$dept_id'");
    header("Location: view_students.php?success=deleted");
    exit();
} else {
    header("Location: view_students.php");
    exit();
}
?>