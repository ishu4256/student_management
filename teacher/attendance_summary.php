<?php
include "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_class_assignments (id INT AUTO_INCREMENT PRIMARY KEY, teacher_user_id INT NOT NULL, department_id INT NOT NULL, class_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY teacher_class (teacher_user_id, class_id), INDEX (department_id))");
$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);
$allowed_departments = $conn->query("SELECT DISTINCT department_id FROM teacher_class_assignments WHERE teacher_user_id = $teacher_user_id");
$department_ids = [];
if ($allowed_departments) { while ($allowed = $allowed_departments->fetch_assoc()) { $department_ids[] = (int)$allowed['department_id']; } }
$requested_department = (int)($_GET['department_id'] ?? 0);
$dept_id = in_array($requested_department, $department_ids, true) ? $requested_department : ($department_ids[0] ?? 0);
$requested_class = (int)($_GET['class_id'] ?? 0);
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$departments = $conn->query("SELECT id, name FROM departments WHERE id IN (" . ($department_ids ? implode(',', $department_ids) : '0') . ") ORDER BY name ASC");
$classes = $conn->query("SELECT c.id, c.class_name FROM classes c INNER JOIN teacher_class_assignments a ON a.class_id = c.id WHERE a.teacher_user_id = $teacher_user_id AND a.department_id = $dept_id ORDER BY c.class_name ASC");
$class_options = [];
$class_ids = [];
if ($classes) { while ($class = $classes->fetch_assoc()) { $class_options[] = $class; $class_ids[] = (int)$class['id']; } }
$selected_class = in_array($requested_class, $class_ids, true) ? $requested_class : 0;

$dept_name_query = $conn->query("SELECT name FROM departments WHERE id = '$dept_id'");
$dept_row = $dept_name_query->fetch_assoc();
$my_dept_name = $dept_row['name'] ?? 'Your Department';

$class_name = $selected_class > 0 ? ($conn->query("SELECT class_name FROM classes WHERE id = '$selected_class'")->fetch_assoc()['class_name'] ?? 'Selected Class') : 'Select a class';

$query = $selected_class > 0 ? "SELECT s.id AS student_id, s.name AS student_name, a.status
          FROM students s 
          LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = '$selected_date'
          WHERE s.departmentid = '$dept_id' AND s.class_id = '$selected_class' ORDER BY s.name ASC" : '';

$attendance_result = $query !== '' ? $conn->query($query) : false;

$total_students = $attendance_result ? $attendance_result->num_rows : 0;
$present_count = 0;
$absent_count = 0;

if ($total_students > 0) {
    while ($row = $attendance_result->fetch_assoc()) {
        if ($row['status'] == 'Present') $present_count++;
        if ($row['status'] == 'Absent') $absent_count++;
    }
    $attendance_result->data_seek(0);
}

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

<div class="dashboard summary-container" style="max-width: 960px; margin: 40px auto;">
    <div class="dashboard-header">
        <div>
            <h2>Attendance Report</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($my_dept_name); ?></strong> | Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
        </div>
        <a href="dashboard.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <div class="dept-form-box" style="background: #f8fafc; padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 25px;">
        <form method="get" style="display: flex; flex-direction: row; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div class="input-group" style="flex: 1; min-width: 220px; margin-bottom: 0;">
                <label style="font-weight: 600; font-size: 13px;">Select Department</label>
                <select name="department_id" onchange="this.form.submit()" style="margin-bottom: 0;">
                    <option value="">-- Select Department --</option>
                    <?php while ($dept = $departments->fetch_assoc()): ?>
                        <option value="<?php echo (int)$dept['id']; ?>" <?php echo ($dept_id == (int)$dept['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input-group" style="flex: 1; min-width: 220px; margin-bottom: 0;">
                <label style="font-weight: 600; font-size: 13px;">Select Class</label>
                <select name="class_id" onchange="this.form.submit()" style="margin-bottom: 0;">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($class_options as $class): ?>
                        <option value="<?php echo (int)$class['id']; ?>" <?php echo ($selected_class == (int)$class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group" style="flex: 1; min-width: 220px; margin-bottom: 0;">
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

    <div class="table-container" style="margin-bottom: 20px;">
        <h3 style="margin-bottom: 15px; color: var(--text-main); font-size: 16px;">Filter Summary</h3>
        <p style="margin: 0; color: var(--text-muted);">Showing attendance for <strong><?php echo htmlspecialchars($my_dept_name); ?></strong> / <strong><?php echo htmlspecialchars($class_name); ?></strong> on <strong><?php echo htmlspecialchars($selected_date); ?></strong>.</p>
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