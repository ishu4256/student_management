<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];
$account = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$account_email = $conn->real_escape_string($account['email'] ?? '');
$subject_class_column = $conn->query("SHOW COLUMNS FROM subjects LIKE 'class_id'");
if ($subject_class_column && $subject_class_column->num_rows === 0) { $conn->query("ALTER TABLE subjects ADD COLUMN class_id INT NULL AFTER departmentid"); }
$student = $conn->query("SELECT id, departmentid, class_id FROM students WHERE userid = $user_id OR ('$account_email' <> '' AND email = '$account_email') ORDER BY (userid = $user_id) DESC LIMIT 1")->fetch_assoc();
$student_id = $student['id'] ?? 0;
$dept_id = $student['departmentid'] ?? 0;
$class_id = $student['class_id'] ?? 0;
$has_class_assignment = $student_id > 0 && $dept_id > 0 && $class_id > 0;

// 1. Enroll කිරීමේ තර්කනය (Enroll Button එක Click කළ විට)
$department_name = $conn->query("SELECT name FROM departments WHERE id = '$dept_id'")->fetch_assoc()['name'] ?? 'Not assigned';
$class_name = $conn->query("SELECT class_name FROM classes WHERE id = '$class_id'")->fetch_assoc()['class_name'] ?? 'Not assigned';
$subjects = $class_id > 0 ? $conn->query("SELECT * FROM subjects WHERE departmentid = '$dept_id' AND class_id = '$class_id' ORDER BY subject_code ASC, name ASC") : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Subjects</title>
    <link rel="stylesheet" href="../css/style.css?v=3.8">
    <style>
        .subject-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .subject-info h3 { margin: 0; color: #1e293b; }
        .badge { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="dashboard-container" style="max-width: 800px; margin: 40px auto;">
    <h2>📚 My Class Subjects</h2>
    <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong> · Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
            <a href="dashboard.php" class="btn-secondary">⬅ Back to Dashboard</a>

    <h3>Subjects for my class</h3>
    <?php if ($subjects && $subjects->num_rows > 0): while($row = $subjects->fetch_assoc()): ?>
        <div class="subject-card" style="border-left: 5px solid #16a34a;">
            <div><h3><?php echo htmlspecialchars($row['name']); ?></h3><p>Code: <?php echo htmlspecialchars($row['subject_code'] ?? 'N/A'); ?> · <?php echo htmlspecialchars($row['credits'] ?? '-'); ?> credits</p></div>
            <span class="badge" style="background:#dcfce7; color:#166534;">Enrolled</span>
        </div>
    <?php endwhile; else: ?>
            <div class="subject-card"><p><?php echo $has_class_assignment ? 'No subjects have been added for your class yet.' : 'You are not assigned to a department and class yet. Please complete Class Enrollment first.'; ?></p></div>
    <?php endif; ?>
</div>
</body>
</html>