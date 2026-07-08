<?php
include "config/db.php";

$message = "";

// 1. පැමිණීම සටහන් කිරීමේ ක්‍රියාවලිය (Insert / Mark Attendance)
if (isset($_POST['mark_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $students_status = $_POST['status'] ?? []; // Array එකක් ලෙස ලැබෙයි [student_id => status]

    if (!empty($attendance_date) && !empty($students_status)) {
        foreach ($students_status as $student_id => $status) {
            $student_id = $conn->real_escape_string($student_id);
            $status = $conn->real_escape_string($status);
            $attendance_date = $conn->real_escape_string($attendance_date);

            // එකම දිනක එකම ශිෂ්‍යයාට දැනටමත් පැමිණීම සටහන් කර ඇත්දැයි බැලීම
            $check = $conn->query("SELECT id FROM attendance WHERE student_id = '$student_id' AND attendance_date = '$attendance_date'");
            
            if ($check->num_rows > 0) {
                // දැනටමත් ඇත්නම් එය Update කරයි
                $conn->query("UPDATE attendance SET status = '$status' WHERE student_id = '$student_id' AND attendance_date = '$attendance_date'");
            } else {
                // අලුතින් ඇතුළත් කරයි
                $conn->query("INSERT INTO attendance (student_id, attendance_date, status) VALUES ('$student_id', '$attendance_date', '$status')");
            }
        }
        header("Location: attendance.php?success=saved");
        exit();
    }
}

// 2. සියලුම සිසුන්ගේ ලැයිස්තුව ලබා ගැනීම
$students_result = $conn->query("SELECT id, name FROM students ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance</title>
    <link rel="stylesheet" href="css/style.css?v=1.8">
    <style>
        .radio-group { display: flex; gap: 15px; }
        .radio-label { display: flex; align-items: center; gap: 5px; cursor: pointer; font-size: 14px; }
        .radio-label input { margin: 0; cursor: pointer; }
        .status-present { color: #22c55e; font-weight: bold; }
        .status-absent { color: #ef4444; font-weight: bold; }
    </style>
</head>
<body>

<div class="dashboard attendance-container">
    <div class="dashboard-header">
        <div>
            <h2>Student Attendance</h2>
            <p class="subtitle">Mark daily student attendance records</p>
        </div>
        <a href="admin/dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="success" style="background: #f0fdf4; color: #15803d; padding: 15px; border-radius: var(--radius-md); margin-bottom: 20px; border: 1px solid #bbf7d0;">
            ✅ Attendance records saved successfully!
        </div>
    <?php endif; ?>

    <form method="post">
        <!-- දිනය තෝරන කොටස -->
        <div class="dept-form-box" style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 25px;">
            <div class="input-group" style="max-width: 300px; margin-bottom: 0;">
                <label for="attendance_date" style="font-weight: 600; font-size: 13px;">Select Attendance Date</label>
                <input type="date" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <!-- ශිෂ්‍ය ලැයිස්තුව සහ Attendance රේඩියෝ බොත්තම් -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 150px;">Student ID</th>
                        <th>Student Name</th>
                        <th style="width: 250px;">Attendance Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students_result && $students_result->num_rows > 0): ?>
                        <?php while($row = $students_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                <td><?php echo $row['name']; ?></td>
                                <td>
                                    <div class="radio-group">
                                        <label class="radio-label status-present">
                                            <input type="radio" name="status[<?php echo $row['id']; ?>]" value="Present" checked> Present
                                        </label>
                                        <label class="radio-label status-absent">
                                            <input type="radio" name="status[<?php echo $row['id']; ?>]" value="Absent"> Absent
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 20px;">No students found in the database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Save Button -->
        <?php if($students_result && $students_result->num_rows > 0): ?>
            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" name="mark_attendance" class="btn-primary" style="width: auto; padding: 14px 35px; font-weight: 600;">💾 Submit Attendance</button>
            </div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>