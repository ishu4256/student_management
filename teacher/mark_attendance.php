<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_class_assignments (id INT AUTO_INCREMENT PRIMARY KEY, teacher_user_id INT NOT NULL, department_id INT NOT NULL, class_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY teacher_class (teacher_user_id, class_id), INDEX (department_id))");
$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);
$dept_id = (int)($_GET['department_id'] ?? $_POST['department_id'] ?? 0);
$selected_class = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
$class_access = $conn->query("SELECT id FROM teacher_class_assignments WHERE teacher_user_id = $teacher_user_id AND department_id = $dept_id AND class_id = $selected_class");
$access_granted = $class_access && $class_access->num_rows > 0;
$success = '';
$error = '';

if (isset($_POST['save_attendance'])) {
    $date = $_POST['attendance_date'];
    $status_array = $_POST['status'] ?? [];

    if (!$access_granted) {
        $error = 'You can only mark attendance for an approved department and class.';
    } elseif (empty($date)) {
        $error = 'Please select an attendance date.';
    } else {
        foreach ($status_array as $student_id => $status) {
            $student_id = $conn->real_escape_string($student_id);
            $status = $conn->real_escape_string($status);

            $student_access = $conn->query("SELECT id FROM students WHERE id='$student_id' AND departmentid='$dept_id' AND class_id='$selected_class'");
            if (!$student_access || $student_access->num_rows === 0) {
                continue;
            }

            $check = $conn->query("SELECT id FROM attendance WHERE student_id='$student_id' AND attendance_date='$date'");

            if ($check->num_rows > 0) {
                $conn->query("UPDATE attendance SET status='$status', marked_by='$teacher_user_id' WHERE student_id='$student_id' AND attendance_date='$date'");
            } else {
                $conn->query("INSERT INTO attendance (student_id, attendance_date, status, marked_by) VALUES ('$student_id', '$date', '$status', '$teacher_user_id')");
            }
        }
        $success = "✅ Attendance saved successfully for the selected class!";
    }
}

    $students = $access_granted ? $conn->query("SELECT id, name FROM students WHERE departmentid='$dept_id' AND class_id='$selected_class' ORDER BY name ASC") : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="../css/style.css?v=3.0">
    <style>
        .radio-group { display: flex; gap: 15px; }
        .radio-label { display: flex; align-items: center; gap: 5px; cursor: pointer; font-weight: 600; }
        .p-color { color: #22c55e; } .a-color { color: #ef4444; }
    </style>
</head>
<body>

<div class="dashboard teachers-container" style="max-width: 800px; margin: 40px auto;">
    <div class="dashboard-header">
        <div>
            <h2>Mark Student Attendance</h2>
            <p class="subtitle">Select date and update daily attendance records for the chosen class.</p>
        </div>
        <a href="dashboard.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if($success !== '') echo "<div class='success' style='padding:12px; background:#dcfce7; color:#16a34a; border-radius:6px; margin-bottom:15px;'>$success</div>"; ?>
    <?php if($error !== '') echo "<div class='error'>$error</div>"; ?>

    <form method="post">
        <input type="hidden" name="department_id" value="<?php echo $dept_id; ?>">
        <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
        <div class="dept-form-box" style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 25px;">
            <div class="input-group" style="max-width: 250px; margin-bottom: 0;">
                <label style="font-weight: 600; font-size: 13px;">Attendance Date</label>
                <input type="date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students && $students->num_rows > 0): ?>
                        <?php while($row = $students->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                <td><?php echo $row['name']; ?></td>
                                <td>
                                    <div class="radio-group">
                                        <label class="radio-label p-color"><input type="radio" name="status[<?php echo $row['id']; ?>]" value="Present" checked> Present</label>
                                        <label class="radio-label a-color"><input type="radio" name="status[<?php echo $row['id']; ?>]" value="Absent"> Absent</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted);">No students found in the selected department/class.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($students && $students->num_rows > 0): ?>
            <button type="submit" name="save_attendance" class="btn-primary" style="margin-top: 20px; float: right; width: auto; padding: 12px 30px;">💾 Save Attendance</button>
        <?php endif; ?>
    </form>
</div>

</body>
</html>