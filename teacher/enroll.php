<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_enrollment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT NOT NULL,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (teacher_user_id),
    INDEX (department_id),
    INDEX (class_id),
    INDEX (status)
)");

$conn->query("CREATE TABLE IF NOT EXISTS teacher_class_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT NOT NULL,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY teacher_class (teacher_user_id, class_id),
    INDEX (department_id)
)");

$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $department_id = (int)($_POST['department_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);

    $valid_class = $conn->query("SELECT id FROM classes WHERE id = $class_id AND department_id = $department_id");
    $existing = $conn->query("SELECT id FROM teacher_enrollment_requests WHERE teacher_user_id = $teacher_user_id AND class_id = $class_id AND status = 'pending'");
    $assigned = $conn->query("SELECT id FROM teacher_class_assignments WHERE teacher_user_id = $teacher_user_id AND class_id = $class_id");

    if ($department_id <= 0 || $class_id <= 0 || !$valid_class || $valid_class->num_rows === 0) {
        $error = 'Please select a valid department and class.';
    } elseif ($assigned && $assigned->num_rows > 0) {
        $error = 'You are already enrolled in this class.';
    } elseif ($existing && $existing->num_rows > 0) {
        $error = 'Your request for this class is already pending.';
    } else {
        $conn->query("INSERT INTO teacher_enrollment_requests (teacher_user_id, department_id, class_id) VALUES ($teacher_user_id, $department_id, $class_id)");
        header("Location: enroll.php?success=submitted");
        exit();
    }
}

$selected_department = (int)($_GET['department_id'] ?? 0);
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes = $selected_department > 0 ? $conn->query("SELECT id, class_name FROM classes WHERE department_id = $selected_department ORDER BY class_name ASC") : false;
$requests = $conn->query("SELECT r.*, d.name AS department_name, c.class_name FROM teacher_enrollment_requests r LEFT JOIN departments d ON d.id = r.department_id LEFT JOIN classes c ON c.id = r.class_id WHERE r.teacher_user_id = $teacher_user_id ORDER BY r.created_at DESC");
$assignments = $conn->query("SELECT a.*, d.name AS department_name, c.class_name FROM teacher_class_assignments a LEFT JOIN departments d ON d.id = a.department_id LEFT JOIN classes c ON c.id = a.class_id WHERE a.teacher_user_id = $teacher_user_id ORDER BY d.name, c.class_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department & Class Enrollment</title>
    <link rel="stylesheet" href="../css/style.css?v=2.9">
</head>
<body>
<div class="dashboard enrollment-page">
    <div class="dashboard-header">
        <div>
            <span class="eyebrow">TEACHER REQUESTS</span>
            <h2>Department & Class Enrollment</h2>
            <p class="subtitle">Request access to a department and class. An administrator must approve every request.</p>
        </div>
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'submitted'): ?>
        <div class="success">Enrollment request submitted. It will be active after admin approval.</div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="enrollment-grid">
        <section class="enrollment-panel">
            <div class="panel-heading">
                <div><h3>New enrollment request</h3><p>Select the department first to load its classes.</p></div>
            </div>
            <form method="get" class="enrollment-form department-picker">
                <div class="input-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id" onchange="this.form.submit()" required>
                        <option value="0">Choose department</option>
                        <?php while ($department = $departments->fetch_assoc()): ?>
                            <option value="<?php echo (int)$department['id']; ?>" <?php echo $selected_department === (int)$department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
            <?php if ($selected_department > 0): ?>
                <form method="post" class="enrollment-form">
                    <input type="hidden" name="department_id" value="<?php echo $selected_department; ?>">
                    <div class="input-group">
                        <label for="class_id">Class</label>
                        <select id="class_id" name="class_id" required>
                            <option value="">Choose class</option>
                            <?php if ($classes): while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?php echo (int)$class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <button type="submit" name="submit_request" class="btn-primary">Submit for approval</button>
                </form>
            <?php endif; ?>
        </section>

        <section class="enrollment-panel approved-panel">
            <div class="panel-heading"><div><h3>Approved assignments</h3><p>Only approved assignments are active.</p></div></div>
            <div class="enrollment-list">
                <?php if ($assignments && $assignments->num_rows > 0): while ($assignment = $assignments->fetch_assoc()): ?>
                    <div class="enrollment-item"><div><strong><?php echo htmlspecialchars($assignment['department_name'] ?? '-'); ?></strong><small><?php echo htmlspecialchars($assignment['class_name'] ?? '-'); ?></small></div><span class="status-chip status-present">Approved</span></div>
                <?php endwhile; else: ?><p class="empty-state">No approved department or class assignments yet.</p><?php endif; ?>
            </div>
        </section>
    </div>

    <section class="enrollment-panel request-history">
        <div class="panel-heading"><div><h3>Request history</h3><p>Track pending and reviewed enrollment requests.</p></div></div>
        <div class="enrollment-list">
            <?php if ($requests && $requests->num_rows > 0): while ($request = $requests->fetch_assoc()): ?>
                <div class="enrollment-item"><div><strong><?php echo htmlspecialchars($request['department_name'] ?? '-'); ?> / <?php echo htmlspecialchars($request['class_name'] ?? '-'); ?></strong><small>Requested <?php echo htmlspecialchars($request['created_at']); ?></small></div><span class="status-chip status-<?php echo htmlspecialchars($request['status']); ?>"><?php echo ucfirst(htmlspecialchars($request['status'])); ?></span></div>
            <?php endwhile; else: ?><p class="empty-state">No enrollment requests submitted yet.</p><?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
