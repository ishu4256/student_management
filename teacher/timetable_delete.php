<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$slot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($slot_id > 0) {
    $conn->query("DELETE FROM timetable WHERE id = '$slot_id'");
}

header("Location: view_timetable.php?department_id=$selected_department&class_id=$selected_class");
exit();
