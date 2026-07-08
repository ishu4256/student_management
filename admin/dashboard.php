<?php
include "../config/db.php";

// සටහන: session_start() එක db.php එක ඇතුලේ නැත්නම් පමණක් මෙතනින් දාන්න
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css?v=1.2">
</head>
<body>

<div class="dashboard admin-container">
    <div class="dashboard-header">
        <h2>Admin Dashboard</h2>
        <p class="subtitle">Control panel for managing school management system</p>
    </div>

    <div class="nav-grid">
        <a href="teachers.php" class="nav-card">
            <span class="icon">👨‍🏫</span>
            <span class="title">Manage Teachers</span>
        </a>
        
        <a href="department.php" class="nav-card">
            <span class="icon">🏢</span>
            <span class="title">Department</span>
        </a>

        <a href="../student.php" class="nav-card">
            <span class="icon">👨‍🎓</span>
            <span class="title">Manage Students</span>
        </a>
         <a href="attendance_summary.php" class="nav-card">
            <span class="icon">👨‍🎓</span>
            <span class="title">Manage Attendance</span>
        </a>

        <a href="../logout.php" class="nav-card logout-card">
            <span class="icon">🚪</span>
            <span class="title">Logout</span>
        </a>
    </div>
</div>

</body>
</html>