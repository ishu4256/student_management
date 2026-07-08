<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$dept_id = $_SESSION['departmentid'] ?? 0;

// 💡 පියවර 1: timetable table එකේ තියෙන columns මොනවාදැයි ස්වයංක්‍රීයව පිරික්සීම
$columns_query = $conn->query("SHOW COLUMNS FROM timetable");
$columns = [];
while($col = $columns_query->fetch_assoc()) {
    $columns[] = $col['Field'];
}

// 💡 පියවර 2: විෂය සම්බන්ධ කිරීමට ඇති column එක හඳුනා ගැනීම
$join_on = "";
if (in_array('subject_id', $columns)) {
    $join_on = "t.subject_id = s.id";
} elseif (in_array('subject_code', $columns)) {
    $join_on = "t.subject_code = s.subject_code";
} elseif (in_array('subject', $columns)) {
    $join_on = "t.subject = s.name";
} else {
    $join_on = "t.id = s.id"; 
}

// 💡 පියවර 3: නිවැරදි SQL Query එක ධාවනය කිරීම
$query = "SELECT t.*, s.name AS subject_name 
          FROM timetable t
          LEFT JOIN subjects s ON $join_on
          WHERE s.departmentid = '$dept_id' 
          ORDER BY FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), t.start_time ASC";

$timetable = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable</title>
    <link rel="stylesheet" href="../css/style.css?v=2.3">
    <style>
        /* Modern Table & UI Enhancements */
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5, #3b82f6);
            --bg-soft: #f8fafc;
            --text-dark: #1e293b;
            --border-soft: #e2e8f0;
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-dark);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .timetable-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            border: 1px solid var(--border-soft);
        }

        .dashboard-header {
            border-bottom: 2px solid var(--bg-soft);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .main-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        .btn-back-modern {
            display: inline-flex;
            align-items: center;
            background: #ffffff;
            color: #475569;
            border: 1px solid var(--border-soft);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-back-modern:hover {
            background: #f8fafc;
            color: #0f172a;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        /* Table Styling */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        .modern-table th {
            background: var(--bg-soft);
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            border-bottom: 2px solid var(--border-soft);
            text-align: left;
        }

        .modern-table td {
            padding: 18px 16px;
            border-bottom: 1px solid var(--border-soft);
            font-size: 15px;
            color: #334155;
            vertical-align: middle;
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        .modern-table tr:hover td {
            background-color: #f8fafc;
        }

        /* Component Badges */
        .day-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            display: inline-block;
        }

        .time-box {
            font-weight: 600;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .subject-text {
            font-weight: 600;
            color: #1e1b4b;
        }

        .room-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border-soft);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .no-data {
            text-align: center;
            padding: 40px !important;
            color: #94a3b8;
            font-size: 15px;
        }
    </style>
</head>
<body>

<div class="dashboard container" style="max-width: 1000px; margin: 50px auto; padding: 0 15px;">
    
    <div class="timetable-card">
        <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 class="main-title">📅 Weekly Class Timetable</h2>
                <p class="subtitle">View your department's active lecture schedules and locations</p>
            </div>
            <a href="dashboard.php" class="btn-back-modern">⬅ Back to Dashboard</a>
        </div>

        <div class="table-container" style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Day</th>
                        <th style="width: 25%;">Time Slot</th>
                        <th style="width: 40%;">Subject</th>
                        <th style="width: 20%;">Classroom / Hall</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($timetable && $timetable->num_rows > 0): ?>
                        <?php while($row = $timetable->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="day-badge"><?php echo $row['day']; ?></span>
                                </td>
                                <td>
                                    <div class="time-box">
                                        🕒 <?php echo date("h:i A", strtotime($row['start_time'])) . " - " . date("h:i A", strtotime($row['end_time'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="subject-text"><?php echo $row['subject_name'] ?? 'Unknown Subject'; ?></span>
                                </td>
                                <td>
                                    <span class="room-badge">
                                        🚪 <?php echo $row['classroom'] ?? $row['hall_no'] ?? $row['room'] ?? 'N/A'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data">
                                📭 No timetable schedules found for your department.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>