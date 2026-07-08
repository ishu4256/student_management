<?php
include "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../css/style.css?v=2.0">
</head>
<body>

<div class="dashboard admin-container">
    <div class="dashboard-header">
        <h2>Teacher Dashboard</h2>
        <p class="subtitle">Welcome back! Manage your department students and attendance efficiently.</p>
    </div>

    <div class="nav-grid">
        <a href="view_students.php" class="nav-card">
            <span class="icon">👨‍🎓</span>
            <span class="title">My Department Students</span>
        </a>
        
        <a href="mark_attendance.php" class="nav-card">
            <span class="icon">📝</span>
            <span class="title">Mark Attendance</span>
        </a>

         <a href="attendance_summary.php" class="nav-card">
            <span class="icon">📝</span>
            <span class="title">View Attendance Summary</span>
        </a>

        <a href="view_timetable.php" class="nav-card">
            <span class="icon">📝</span>
            <span class="title">View Timetable</span>
        </a>

        <a href="view_subjects.php" class="nav-card">
            <span class="icon">📝</span>
            <span class="title">View Subjects</span>
        </a>
        <a href="../logout.php" class="nav-card logout-card">
            <span class="icon">🚪</span>
            <span class="title">Logout</span>
        </a>
    </div>
</div>

</body>
</html>