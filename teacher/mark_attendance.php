<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$dept_id = $_SESSION['departmentid'] ?? 0;
// 💡 දැනට ලොග් වී සිටින ගුරුවරයාගේ User ID එක සෙෂන් එකෙන් ලබා ගැනීම
$teacher_user_id = $_SESSION['user_id'] ?? 0; 

// පැමිණීම සුරැකීම
if (isset($_POST['save_attendance'])) {
    $date = $_POST['attendance_date'];
    $status_array = $_POST['status'] ?? [];

    if (!empty($date)) {
        foreach ($status_array as $student_id => $status) {
            $student_id = $conn->real_escape_string($student_id);
            $status = $conn->real_escape_string($status);
            
            // 1. කලින් මේ ශිෂ්‍යයාට එදිනම පැමිණීම සටහන් කර ඇත්දැයි බැලීම
            $check = $conn->query("SELECT id FROM attendance WHERE student_id='$student_id' AND attendance_date='$date'");
            
            if ($check->num_rows > 0) {
                // 2. කලින් සටහන් කර ඇත්නම් එය Update කිරීම (මෙහිදී marked_by එකත් අවශ්‍ය නම් දාන්න පුළුවන්)
                $conn->query("UPDATE attendance SET status='$status', marked_by='$teacher_user_id' WHERE student_id='$student_id' AND attendance_date='$date'");
            } else {
                // 3. 💡 අලුතින්ම ඇතුළත් කරද්දී marked_by එකට ගුරුවරයාගේ ID එක ($teacher_user_id) AUTO ඇතුළත් කිරීම
                $conn->query("INSERT INTO attendance (student_id, attendance_date, status, marked_by) VALUES ('$student_id', '$date', '$status', '$teacher_user_id')");
            }
        }
        $success = "✅ Attendance saved successfully!";
    }
}

// ගුරුවරයාගේ දෙපාර්තමේන්තුවේ සිසුන් පමණක් ලබා ගැනීම
$students = $conn->query("SELECT id, name FROM students WHERE departmentid='$dept_id' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="../css/style.css?v=2.0">
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
            <p class="subtitle">Select date and update daily attendance records</p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if(isset($success)) echo "<div class='success' style='padding:12px; background:#dcfce7; color:#16a34a; border-radius:6px; margin-bottom:15px;'>$success</div>"; ?>

    <form method="post">
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
                            <td colspan="3" style="text-align: center; color: var(--text-muted);">No students found in your department.</td>
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