<?php
include "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$teacher_count = $conn->query("SELECT COUNT(*) AS total FROM teachers")->fetch_assoc()['total'] ?? 0;
$student_count = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;
$department_count = $conn->query("SELECT COUNT(*) AS total FROM departments")->fetch_assoc()['total'] ?? 0;
$today_attendance_count = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE attendance_date = CURDATE()")->fetch_assoc()['total'] ?? 0;
$today_present_count = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE attendance_date = CURDATE() AND LOWER(status) IN ('present', 'p')")->fetch_assoc()['total'] ?? 0;
$today_absent_count = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE attendance_date = CURDATE() AND LOWER(status) IN ('absent', 'a')")->fetch_assoc()['total'] ?? 0;
$attendance_rate = $today_attendance_count > 0 ? round(($today_present_count / $today_attendance_count) * 100) : 0;
$recent_attendance = $conn->query("SELECT a.attendance_date, a.status, s.name AS student_name FROM attendance a LEFT JOIN students s ON s.id = a.student_id ORDER BY a.attendance_date DESC, a.id DESC LIMIT 5");
$today_label = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css?v=1.5">
</head>
<body>

<div class="dashboard admin-container">
    <div class="dashboard-header">
        <div>
            <span class="eyebrow">ADMINISTRATION • <?php echo htmlspecialchars($today_label); ?></span>
            <h2>Good morning, <?php echo htmlspecialchars($admin_name); ?></h2>
            <p class="subtitle">Your central workspace for keeping the academic community organised.</p>
        </div>
        <div class="header-actions">
            <a href="../logout.php" class="btn-outline">Log out</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Teachers</span>
            <strong><?php echo number_format($teacher_count); ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Students</span>
            <strong><?php echo number_format($student_count); ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Departments</span>
            <strong><?php echo number_format($department_count); ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Today Attendance</span>
            <strong><?php echo number_format($today_attendance_count); ?></strong>
            <span class="stat-meta"><?php echo number_format($attendance_rate); ?>% present</span>
        </div>
    </div>

    <div class="dashboard-section-heading">
        <div>
            <h3>Workspace shortcuts</h3>
            <p>Jump straight into the tasks you use most.</p>
        </div>
    </div>

    <div class="nav-grid">
        <a href="teachers.php" class="nav-card nav-card-primary">
            <span class="icon">👨‍🏫</span>
            <span class="title">Manage Teachers</span>
            <span class="card-description">Profiles, departments and records</span>
        </a>
        
        <a href="department.php" class="nav-card nav-card-teal">
            <span class="icon">🏢</span>
            <span class="title">Departments</span>
            <span class="card-description">Organise academic divisions</span>
        </a>

        <a href="../student.php" class="nav-card nav-card-blue">
            <span class="icon">👨‍🎓</span>
            <span class="title">Manage Students</span>
            <span class="card-description">View and update student profiles</span>
        </a>

        <a href="attendance_summary.php" class="nav-card nav-card-amber">
            <span class="icon">🗂️</span>
            <span class="title">Attendance Summary</span>
            <span class="card-description">Review daily attendance trends</span>
        </a>

        <a href="view_assessments.php" class="nav-card nav-card-violet">
            <span class="icon">🧪</span>
            <span class="title">Assessment Overview</span>
            <span class="card-description">Monitor quizzes and assignments</span>
        </a>

        <a href="teacher_requests.php" class="nav-card nav-card-amber">
            <span class="icon">✅</span>
            <span class="title">Teacher Approvals</span>
            <span class="card-description">Review enrollment requests</span>
        </a>

        <a href="subjects.php" class="nav-card nav-card-blue">
            <span class="icon">📚</span>
            <span class="title">Class Subjects</span>
            <span class="card-description">Manage subjects by class</span>
        </a>
    </div>

    <div class="dashboard-lower-grid">
        <section class="insight-panel">
            <div class="panel-heading">
                <div>
                    <h3>Today's attendance</h3>
                    <p>Live summary from all recorded attendance.</p>
                </div>
                <a href="attendance_summary.php" class="text-link">View report →</a>
            </div>
            <div class="attendance-breakdown">
                <div><strong class="present-value"><?php echo number_format($today_present_count); ?></strong><span>Present</span></div>
                <div><strong class="absent-value"><?php echo number_format($today_absent_count); ?></strong><span>Absent</span></div>
                <div><strong><?php echo number_format($attendance_rate); ?>%</strong><span>Attendance rate</span></div>
            </div>
            <div class="progress-track"><span style="width: <?php echo $attendance_rate; ?>%"></span></div>
        </section>

        <section class="insight-panel recent-panel">
            <div class="panel-heading">
                <div>
                    <h3>Recent attendance</h3>
                    <p>Latest records entered by staff.</p>
                </div>
            </div>
            <div class="recent-list">
                <?php if ($recent_attendance && $recent_attendance->num_rows > 0): ?>
                    <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                        <?php $is_present = in_array(strtolower(trim($record['status'])), ['present', 'p'], true); ?>
                        <div class="recent-item">
                            <span class="recent-avatar"><?php echo strtoupper(substr($record['student_name'] ?? 'S', 0, 1)); ?></span>
                            <div><strong><?php echo htmlspecialchars($record['student_name'] ?? 'Unknown student'); ?></strong><small><?php echo htmlspecialchars($record['attendance_date']); ?></small></div>
                            <span class="status-chip <?php echo $is_present ? 'status-present' : 'status-absent'; ?>"><?php echo $is_present ? 'Present' : 'Absent'; ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty-state">No attendance records have been added yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

</body>
</html>