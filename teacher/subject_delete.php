<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $dept_id = $_SESSION['departmentid'] ?? 0;
    
    // ආරක්ෂාව සඳහා තමන්ගේ දෙපාර්තමේන්තුවේ විෂයක් නම් පමණක් ඩිලීට් කිරීමට ඉඩ දෙයි
    $conn->query("DELETE FROM subjects WHERE id='$id' AND departmentid='$dept_id'");
}
header("Location: view_subjects.php");
exit();
?>