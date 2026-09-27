<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];
$account = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$account_email = $conn->real_escape_string($account['email'] ?? '');
$student = $conn->query("SELECT id, departmentid, class_id FROM students WHERE userid = $user_id OR ('$account_email' <> '' AND email = '$account_email') ORDER BY (userid = $user_id) DESC LIMIT 1")->fetch_assoc();
$student_id = $student['id'] ?? 0;
$department_id = (int)($student['departmentid'] ?? 0);
$class_id = (int)($student['class_id'] ?? 0);

$teacher_column = $conn->query("SHOW COLUMNS FROM timetable LIKE 'teacher_user_id'");
if ($teacher_column && $teacher_column->num_rows === 0) {
    $conn->query("ALTER TABLE timetable ADD COLUMN teacher_user_id INT NULL AFTER id");
}
$timetable_columns_result = $conn->query("SHOW COLUMNS FROM timetable");
$timetable_columns = [];
if ($timetable_columns_result) { while ($column = $timetable_columns_result->fetch_assoc()) { $timetable_columns[] = $column['Field']; } }
$class_condition = in_array('class_id', $timetable_columns, true) ? "AND (t.class_id = '$class_id' OR t.class_id IS NULL)" : '';

// 💡 JOIN මගින් ලියාපදිංචි වූ විෂයයන්ගේ කාලසටහන පමණක් ලබා ගැනීම
// Query එක වෙනස් කිරීම
$query = "SELECT t.*, s.name AS subject_name, tr.name AS teacher_name
          FROM timetable t
          INNER JOIN subjects s ON t.subject_id = s.id
          LEFT JOIN teachers tr ON tr.userid = t.teacher_user_id
          WHERE s.departmentid = '$department_id' AND s.class_id = '$class_id'
          $class_condition
          ORDER BY FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), t.start_time ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Timetable</title>
    <link rel="stylesheet" href="../css/style.css?v=3.9">
</head>
<body>
    <div class="dashboard-container" style="max-width: 900px; margin: 40px auto;">
        <h2>📅 My Class Timetable</h2>
        <a href="dashboard.php" class="btn-secondary">⬅ Back to Dashboard</a>

        <table style="width: 100%; margin-top: 25px; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 12px; border: 1px solid #e2e8f0;">Day</th>
                    <th style="padding: 12px; border: 1px solid #e2e8f0;">Subject</th>
                    <th style="padding: 12px; border: 1px solid #e2e8f0;">Teacher</th>
                    <th style="padding: 12px; border: 1px solid #e2e8f0;">Time</th>
                    <th style="padding: 12px; border: 1px solid #e2e8f0;">Location</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #e2e8f0;"><?php echo $row['day']; ?></td>
                        <td style="padding: 12px; border: 1px solid #e2e8f0;"><strong><?php echo $row['subject_name']; ?></strong></td>
                        <td style="padding: 12px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($row['teacher_name'] ?? 'Teacher not assigned'); ?></td>
                        <td style="padding: 12px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($row['start_time'] . ' - ' . $row['end_time']); ?></td>
                        <td style="padding: 12px; border: 1px solid #e2e8f0;"><?php echo $row['classroom']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:20px;">No timetable data for your class subjects.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>