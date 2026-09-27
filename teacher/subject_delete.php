<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') { header("Location: ../login.php"); exit(); }
$subject_class_column = $conn->query("SHOW COLUMNS FROM subjects LIKE 'class_id'");
if ($subject_class_column && $subject_class_column->num_rows === 0) { $conn->query("ALTER TABLE subjects ADD COLUMN class_id INT NULL AFTER departmentid"); }

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $dept_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
    $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

    $conn->query("DELETE FROM subjects WHERE id='$id' AND departmentid='$dept_id' AND class_id='$class_id'");
}
header("Location: view_subjects.php?department_id=" . (isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0)) . "&class_id=" . (isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0));
exit();
?>