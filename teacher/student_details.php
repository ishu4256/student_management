<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);
$student_id = (int)($_GET['id'] ?? 0);
$department_id = (int)($_GET['department_id'] ?? 0);
$class_id = (int)($_GET['class_id'] ?? 0);

$student_result = $conn->query("SELECT s.*, d.name AS department_name, c.class_name
    FROM students s
    LEFT JOIN departments d ON d.id = s.departmentid
    LEFT JOIN classes c ON c.id = s.class_id
    INNER JOIN teacher_class_assignments a ON a.class_id = s.class_id AND a.department_id = s.departmentid
    WHERE s.id = $student_id AND a.teacher_user_id = $teacher_user_id AND s.departmentid = $department_id AND s.class_id = $class_id
    LIMIT 1");
$student = $student_result ? $student_result->fetch_assoc() : null;
if (!$student) {
    header("Location: view_students.php?department_id=$department_id&class_id=$class_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <link rel="stylesheet" href="../css/style.css?v=3.0">
</head>
<body>
<div class="dashboard student-details-page">
    <div class="dashboard-header">
        <div><span class="eyebrow">STUDENT PROFILE</span><h2><?php echo htmlspecialchars($student['name']); ?></h2><p class="subtitle">Complete student information and academic assignment.</p></div>
        <a href="view_students.php?department_id=<?php echo $department_id; ?>&class_id=<?php echo $class_id; ?>" class="btn-back">Back to class</a>
    </div>
    <div class="student-profile-layout">
        <section class="student-profile-card">
            <div class="profile-avatar"><?php echo strtoupper(substr($student['name'], 0, 1)); ?></div>
            <h3><?php echo htmlspecialchars($student['name']); ?></h3>
            <p><?php echo htmlspecialchars($student['email'] ?? '-'); ?></p>
            <span class="status-chip status-approved">Active student</span>
        </section>
        <section class="student-detail-grid">
            <div><span>Student ID</span><strong>#<?php echo (int)$student['id']; ?></strong></div>
            <div><span>Department</span><strong><?php echo htmlspecialchars($student['department_name'] ?? '-'); ?></strong></div>
            <div><span>Class</span><strong><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></strong></div>
            <div><span>Phone</span><strong><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></strong></div>
            <div><span>Sex</span><strong><?php echo htmlspecialchars($student['sex'] ?? '-'); ?></strong></div>
            <div><span>Date of birth</span><strong><?php echo htmlspecialchars($student['date_of_birth'] ?? '-'); ?></strong></div>
            <div><span>Parent name</span><strong><?php echo htmlspecialchars($student['parents_name'] ?? '-'); ?></strong></div>
            <div><span>Address</span><strong><?php echo htmlspecialchars($student['address'] ?? '-'); ?></strong></div>
        </section>
    </div>
</div>
</body>
</html>
