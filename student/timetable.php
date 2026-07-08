<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$student = $conn->query("SELECT id FROM students WHERE userid = '$user_id'")->fetch_assoc();
$student_id = $student['id'] ?? 0;

// 💡 JOIN මගින් ලියාපදිංචි වූ විෂයයන්ගේ කාලසටහන පමණක් ලබා ගැනීම
// Query එක වෙනස් කිරීම
$query = "SELECT t.*, s.name as subject_name 
          FROM timetable t
          JOIN subjects s ON t.subject_id = s.id
          JOIN enrollments e ON s.id = e.subject_id
          WHERE e.student_id = '$student_id'
          ORDER BY t.day ASC, t.start_time ASC"; // day_of_week යන්න ඔබේ table එකේ ඇති නමට අනුව වෙනස් කරන්න
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Timetable</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
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
                        <td style="padding: 12px; border: 1px solid #e2e8f0;"><?php echo $row['start_time'] . ' - ' . $row['end_time']; ?></td>
                        <td style="padding: 12px; border: 1px solid #e2e8f0;"><?php echo $row['classroom']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:20px;">No timetable data for your enrolled subjects.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>