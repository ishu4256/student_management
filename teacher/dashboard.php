<?php
include "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_class_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT NOT NULL,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY teacher_class (teacher_user_id, class_id),
    INDEX (department_id)
)");

$logged_in_user_id = (int)($_SESSION['user_id'] ?? 0);
$teacher_name = $_SESSION['username'] ?? 'Teacher';
$teacher_profile = $conn->query("SELECT name FROM teachers WHERE userid = $logged_in_user_id LIMIT 1");
if ($teacher_profile && $teacher_profile->num_rows > 0) {
    $teacher_name = $teacher_profile->fetch_assoc()['name'];
}
$approved_departments = $conn->query("SELECT DISTINCT d.id, d.name
    FROM teacher_class_assignments a
    INNER JOIN departments d ON d.id = a.department_id
    WHERE a.teacher_user_id = $logged_in_user_id
    ORDER BY d.name ASC");
$approved_department_ids = [];
if ($approved_departments) {
    while ($approved_department = $approved_departments->fetch_assoc()) {
        $approved_department_ids[] = (int)$approved_department['id'];
    }
}

$requested_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$selected_department = in_array($requested_department, $approved_department_ids, true)
    ? $requested_department
    : ($approved_department_ids[0] ?? 0);
$approved_classes = $conn->query("SELECT c.id, c.class_name
    FROM teacher_class_assignments a
    INNER JOIN classes c ON c.id = a.class_id
    WHERE a.teacher_user_id = $logged_in_user_id AND a.department_id = $selected_department
    ORDER BY c.class_name ASC");
$approved_class_ids = [];
$assigned_classes = [];
if ($approved_classes) {
    while ($approved_class = $approved_classes->fetch_assoc()) {
        $approved_class_ids[] = (int)$approved_class['id'];
        $assigned_classes[] = $approved_class;
    }
}
$requested_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$selected_class = in_array($requested_class, $approved_class_ids, true) ? $requested_class : 0;

$departments = $conn->query("SELECT id, name FROM departments WHERE id IN (" . ($approved_department_ids ? implode(',', $approved_department_ids) : '0') . ") ORDER BY name ASC");

$current_department_name = $selected_department > 0
    ? ($conn->query("SELECT name FROM departments WHERE id = '$selected_department'")->fetch_assoc()['name'] ?? 'Selected Department')
    : 'No approved department';
$student_count = $conn->query("SELECT COUNT(*) AS total FROM students WHERE departmentid = '$selected_department'")->fetch_assoc()['total'] ?? 0;
$class_count = $conn->query("SELECT COUNT(*) AS total FROM classes WHERE department_id = '$selected_department'")->fetch_assoc()['total'] ?? 0;
$subject_count = $conn->query("SELECT COUNT(*) AS total FROM subjects WHERE departmentid = '$selected_department'")->fetch_assoc()['total'] ?? 0;
$attendance_count = $conn->query("SELECT COUNT(*) AS total FROM attendance a JOIN students s ON s.id = a.student_id WHERE s.departmentid = '$selected_department' AND a.attendance_date = CURDATE()")->fetch_assoc()['total'] ?? 0;
$present_count = $conn->query("SELECT COUNT(*) AS total FROM attendance a JOIN students s ON s.id = a.student_id WHERE s.departmentid = '$selected_department' AND a.attendance_date = CURDATE() AND LOWER(a.status) IN ('present', 'p')")->fetch_assoc()['total'] ?? 0;
$absent_count = $conn->query("SELECT COUNT(*) AS total FROM attendance a JOIN students s ON s.id = a.student_id WHERE s.departmentid = '$selected_department' AND a.attendance_date = CURDATE() AND LOWER(a.status) IN ('absent', 'a')")->fetch_assoc()['total'] ?? 0;
$attendance_rate = $attendance_count > 0 ? round(($present_count / $attendance_count) * 100) : 0;
$today_label = date('l, F j, Y');
$teacher_assignments = $conn->query("SELECT a.created_at, d.name AS department_name, c.class_name
    FROM teacher_class_assignments a
    LEFT JOIN departments d ON d.id = a.department_id
    LEFT JOIN classes c ON c.id = a.class_id
    WHERE a.teacher_user_id = $logged_in_user_id
    ORDER BY d.name ASC, c.class_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>

<div class="dashboard teacher-dashboard">
    <div class="dashboard-header">
        <div>
            <span class="eyebrow">TEACHER WORKSPACE • <?php echo htmlspecialchars($today_label); ?></span>
            <h2>Welcome back, <?php echo htmlspecialchars($teacher_name); ?></h2>
            <p class="subtitle">Manage your department, classes, attendance and learning resources from one place.</p>
        </div>
        <a href="../logout.php" class="btn-outline">Log out</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Department</span>
            <strong><?php echo htmlspecialchars($current_department_name); ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Students</span>
            <strong><?php echo number_format($student_count); ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Classes</span>
            <strong><?php echo number_format($class_count); ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Subjects</span>
            <strong><?php echo number_format($subject_count); ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Today Attendance</span>
            <strong><?php echo number_format($attendance_count); ?></strong>
        </div>
        <div class="stat-card teacher-rate-stat">
            <span class="stat-label">Attendance Rate</span>
            <strong><?php echo number_format($attendance_rate); ?>%</strong>
        </div>
    </div>

    <div class="teacher-filter-card">
        <div class="teacher-filter-heading">
            <div>
                <span class="eyebrow">CURRENT VIEW</span>
                <h3>Choose your working area</h3>
                <p>All student and attendance actions below use this selection.</p>
            </div>
            <span class="selection-badge"><?php echo htmlspecialchars($current_department_name); ?></span>
        </div>
        <form method="get" class="teacher-filter-form">
            <div class="input-group">
                <label for="department_id">Select Department</label>
                <select id="department_id" name="department_id" onchange="this.form.submit()">
                    <option value="">-- Select Department --</option>
                    <?php while ($dept = $departments->fetch_assoc()): ?>
                        <option value="<?php echo (int)$dept['id']; ?>" <?php echo ($selected_department == (int)$dept['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="input-group">
                <label for="class_id">Select Class</label>
                <select id="class_id" name="class_id" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($assigned_classes as $class): ?>
                        <option value="<?php echo (int)$class['id']; ?>" <?php echo ($selected_class == (int)$class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="action-grid teacher-action-grid">
        <a href="view_students.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-card card-add">
            <span class="action-icon">👨‍🎓</span>
            <span class="action-title">View Student Details</span>
            <span class="card-description">Review profiles and class lists</span>
        </a>

        <a href="mark_attendance.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-card card-edit">
            <span class="action-icon">📝</span>
            <span class="action-title">Mark Attendance</span>
            <span class="card-description">Record today’s student attendance</span>
        </a>

        <a href="attendance_summary.php?department_id=<?php echo $selected_department; ?>" class="action-card card-search">
            <span class="action-icon">📊</span>
            <span class="action-title">Attendance Summary</span>
            <span class="card-description">Analyse attendance by date</span>
        </a>

        <a href="view_subjects.php?department_id=<?php echo $selected_department; ?>" class="action-card card-search">
            <span class="action-icon">📚</span>
            <span class="action-title">View Subjects</span>
            <span class="card-description">Manage learning subjects</span>
        </a>

        <a href="view_timetable.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-card card-add">
            <span class="action-icon">🗓️</span>
            <span class="action-title">View Timetable</span>
            <span class="card-description">Check the class schedule</span>
        </a>

        <a href="view_assessments.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-card card-search">
            <span class="action-icon">🧪</span>
            <span class="action-title">Manage Assessments</span>
            <span class="card-description">Create and review assessments</span>
        </a>

        <a href="enroll.php" class="action-card card-violet">
            <span class="action-icon">🔐</span>
            <span class="action-title">Request Enrollment</span>
            <span class="card-description">Request department and class access</span>
        </a>

        
    </div>

    <div class="teacher-insight-panel">
        <div class="panel-heading">
            <div>
                <h3>Today's attendance snapshot</h3>
                <p><?php echo htmlspecialchars($current_department_name); ?> department overview.</p>
            </div>
            <a href="attendance_summary.php?department_id=<?php echo $selected_department; ?>" class="text-link">Open report →</a>
        </div>
        <div class="teacher-attendance-breakdown">
            <div><strong class="present-value"><?php echo number_format($present_count); ?></strong><span>Present</span></div>
            <div><strong class="absent-value"><?php echo number_format($absent_count); ?></strong><span>Absent</span></div>
            <div><strong><?php echo number_format($attendance_count); ?></strong><span>Records today</span></div>
        </div>
        <div class="progress-track"><span style="width: <?php echo $attendance_rate; ?>%"></span></div>
    </div>

    <section class="teacher-assignment-panel">
        <div class="panel-heading">
            <div>
                <h3>My department & class assignments</h3>
                <p>Only administrator-approved assignments are shown here.</p>
            </div>
            <a href="enroll.php" class="text-link">Request another →</a>
        </div>
        <div class="teacher-assignment-table-wrap">
            <table class="teacher-assignment-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Assigned on</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($teacher_assignments && $teacher_assignments->num_rows > 0): ?>
                        <?php while ($assignment = $teacher_assignments->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($assignment['department_name'] ?? '-'); ?></strong></td>
                                <td><?php echo htmlspecialchars($assignment['class_name'] ?? '-'); ?></td>
                                <td><span class="status-chip status-approved">Approved</span></td>
                                <td><?php echo htmlspecialchars($assignment['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-table">No approved department/class assignments yet. Submit a request to get started.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

</body>
</html>