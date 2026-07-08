<?php
// 1. Database සම්බන්ධතාවය (පියවරක් පිටුපසට ගොස් config ෆෝල්ඩරය වෙත සම්බන්ධ වීම)
include "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ගුරුවරයෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

// ගුරුවරයාගේ දෙපාර්තමේන්තු ID එක සෙෂන් එකෙන් ලබා ගැනීම
$dept_id = $_SESSION['departmentid'] ?? 0;

// තෝරාගත් දිනය ලබා ගැනීම (නැත්නම් අද දිනය ස්වයංක්‍රීයව තේරේ)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 2. ගුරුවරයාගේ දෙපාර්තමේන්තුවේ නම ලබා ගැනීම
$dept_name_query = $conn->query("SELECT name FROM departments WHERE id = '$dept_id'");
$dept_row = $dept_name_query->fetch_assoc();
$my_dept_name = $dept_row['name'] ?? 'Your Department';

// 3. තෝරාගත් දිනයට සහ අදාළ දෙපාර්තමේන්තුවට පමණක් අදාළව දත්ත ලබා ගැනීම
// 💡 a.date වෙනුවට a.attendance_date ලෙස වෙනස් කරන ලදී
// 3. යාවත්කාලීන කළ SQL Query (දෙපාර්තමේන්තුවේ සියලුම සිසුන් සහ පැමිණීමේ විස්තර එකට ලබා ගැනීම)
$query = "SELECT s.id AS student_id, s.name AS student_name, a.status 
          FROM students s 
          LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = '$selected_date'
          WHERE s.departmentid = '$dept_id' 
          ORDER BY s.name ASC";

$attendance_result = $conn->query($query);

// 4. Counts ගණනය කිරීම
$total_students = $attendance_result ? $attendance_result->num_rows : 0;
$present_count = 0;
$absent_count = 0;

if ($total_students > 0) {
    while ($row = $attendance_result->fetch_assoc()) {
        if ($row['status'] == 'Present') $present_count++;
        if ($row['status'] == 'Absent') $absent_count++;
    }
    // Table එකේ පෙන්වීමට pointer එක මුලටම ගැනීම
    $attendance_result->data_seek(0);
}

// පැමිණීමේ ප්‍රතිශතය
$attendance_rate = $total_students > 0 ? round(($present_count / $total_students) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary - Teacher</title>
    <link rel="stylesheet" href="../css/style.css?v=2.1">
    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: #fff;
            padding: 15px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            text-align: center;
            box-shadow: var(--shadow);
        }
        .summary-card h4 { font-size: 13px; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; }
        .summary-card p { font-size: 24px; font-weight: 700; margin: 0; }
        
        .card-total { border-left: 5px solid var(--primary-color); color: var(--primary-color); }
        .card-present { border-left: 5px solid var(--success-color); color: var(--success-color); }
        .card-absent { border-left: 5px solid var(--danger-color); color: var(--danger-color); }
        .card-rate { border-left: 5px solid #eab308; color: #eab308; }

        .status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-present { background: #dcfce7; color: #15803d; }
        .badge-absent { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<div class="dashboard summary-container" style="max-width: 900px; margin: 40px auto;">
    <div class="dashboard-header">
        <div>
            <h2>Attendance Report</h2>
            <p class="subtitle">Department: <strong><?php echo $my_dept_name; ?></strong></p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <div class="dept-form-box" style="background: #f8fafc; padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 25px;">
        <form method="get" style="display: flex; flex-direction: row; gap: 15px; align-items: flex-end;">
            <div class="input-group" style="flex: 1; margin-bottom: 0;">
                <label style="font-weight: 600; font-size: 13px;">Select Report Date</label>
                <input type="date" name="date" value="<?php echo $selected_date; ?>" style="margin-bottom: 0;">
            </div>
            <button type="submit" class="btn-primary" style="width: auto; padding: 12px 25px; margin-top: 0;">View Report</button>
        </form>
    </div>

    <div class="summary-grid">
        <div class="summary-card card-total">
            <h4>Students</h4>
            <p><?php echo $total_students; ?></p>
        </div>
        <div class="summary-card card-present">
            <h4>Present</h4>
            <p><?php echo $present_count; ?></p>
        </div>
        <div class="summary-card card-absent">
            <h4>Absent</h4>
            <p><?php echo $absent_count; ?></p>
        </div>
        <div class="summary-card card-rate">
            <h4>Rate</h4>
            <p><?php echo $attendance_rate; ?>%</p>
        </div>
    </div>

    <div class="table-container">
        <h3 style="margin-bottom: 15px; color: var(--text-main); font-size: 16px;">Student Status List (<?php echo $selected_date; ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 150px;">Student ID</th>
                    <th>Student Name</th>
                    <th style="width: 150px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($attendance_result) && $attendance_result && $attendance_result->num_rows > 0): ?>
                    <?php while($row = $attendance_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['student_id']; ?></strong></td>
                            <td><?php echo $row['student_name']; ?></td>
                          <td>
    <?php if($row['status'] == 'Present'): ?>
        <span class="status-badge badge-present">🟢 Present</span>
    <?php elseif($row['status'] == 'Absent'): ?>
        <span class="status-badge badge-absent">🔴 Absent</span>
    <?php else: ?>
        <span class="status-badge" style="background: #f1f5f9; color: #64748b;">⚪ Not Marked</span>
    <?php endif; ?>
</td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 25px;">No attendance marked for this date.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>