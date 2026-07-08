<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$student = $conn->query("SELECT id FROM students WHERE userid = '$user_id'")->fetch_assoc();
$student_id = $student['id'] ?? 0;

// Filter තර්කනය (Filter logic)
$filter = $_GET['filter'] ?? 'all';
$where_sql = "WHERE student_id = '$student_id'";

if ($filter == 'present') {
    $where_sql .= " AND status IN ('present', 'p')";
} elseif ($filter == 'absent') {
    $where_sql .= " AND status IN ('absent', 'a')";
}

// සාරාංශ දත්ත (මුළු දින ගණන සැමවිටම පෙන්වීමට පෙරහන් නොමැතිව)
$summary = $conn->query("SELECT 
    COUNT(*) as total_days,
    SUM(CASE WHEN status IN ('present', 'p') THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN status IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_days
    FROM attendance WHERE student_id = '$student_id'")->fetch_assoc();

// වාර්තා ලබා ගැනීම
$attendance_query = $conn->query("SELECT * FROM attendance $where_sql ORDER BY attendance_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; }
        .filter-nav { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn-filter { padding: 8px 16px; border-radius: 6px; text-decoration: none; border: 1px solid #ddd; color: #333; font-size: 14px; }
        .btn-filter.active { background: #4f46e5; color: white; border-color: #4f46e5; }
        .status-pill { padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="dashboard-container" style="max-width: 800px; margin: 40px auto;">
        <h2>📊 My Attendance Summary</h2>
        <a href="dashboard.php" class="btn-secondary">⬅ Back to Dashboard</a>

        <div class="stats-grid" style="margin-top:20px;">
            <div class="stat-card"><h4>Total Days</h4><p><?php echo $summary['total_days'] ?? 0; ?></p></div>
            <div class="stat-card"><h4>Present</h4><p style="color:#16a34a;"><?php echo $summary['present_days'] ?? 0; ?></p></div>
            <div class="stat-card"><h4>Absent</h4><p style="color:#dc2626;"><?php echo $summary['absent_days'] ?? 0; ?></p></div>
        </div>

        <div class="filter-nav">
            <a href="?filter=all" class="btn-filter <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=present" class="btn-filter <?php echo $filter == 'present' ? 'active' : ''; ?>">Present Only</a>
            <a href="?filter=absent" class="btn-filter <?php echo $filter == 'absent' ? 'active' : ''; ?>">Absent Only</a>
        </div>
        
        <table>
            <thead>
                <tr><th>Date</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while($row = $attendance_query->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['attendance_date']; ?></td>
                    <td>
                        <?php 
                        $status = strtolower(trim($row['status']));
                        if($status == 'present' || $status == 'p'): ?>
                            <span class="status-pill" style="background:#dcfce7; color:#166534;">🟢 PRESENT</span>
                        <?php else: ?>
                            <span class="status-pill" style="background:#fee2e2; color:#991b1b;">🔴 ABSENT</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>