<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_record = $conn->query("SELECT username, email FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$account_email = $conn->real_escape_string($user_record['email'] ?? '');
$student = $conn->query("SELECT s.*, d.name AS department_name, c.class_name FROM students s LEFT JOIN departments d ON d.id = s.departmentid LEFT JOIN classes c ON c.id = s.class_id WHERE s.userid = $user_id OR ('$account_email' <> '' AND s.email = '$account_email') ORDER BY (s.userid = $user_id) DESC LIMIT 1")->fetch_assoc();
$student_id = (int)($student['id'] ?? 0);
$error = '';

$selected_department = (int)($_POST['department_id'] ?? $_GET['department_id'] ?? ($student['departmentid'] ?? 0));
$selected_class = (int)($_POST['class_id'] ?? $_GET['class_id'] ?? ($student['class_id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_class'])) {
    $valid_class = $conn->query("SELECT id FROM classes WHERE id = $selected_class AND department_id = $selected_department");
    if ($selected_department <= 0 || $selected_class <= 0 || !$valid_class || $valid_class->num_rows === 0) {
        $error = 'Please select a valid department and class.';
    } else {
        if ($student_id <= 0) {
            $student_name = $conn->real_escape_string($user_record['username'] ?? 'Student');
            $student_email = $conn->real_escape_string($user_record['email'] ?? '');
            $created = $conn->query("INSERT INTO students (userid, name, email, departmentid, class_id) VALUES ($user_id, '$student_name', '$student_email', $selected_department, $selected_class)");
            $student_id = $created ? (int)$conn->insert_id : 0;
        } else {
            $created = $conn->query("UPDATE students SET departmentid = $selected_department, class_id = $selected_class WHERE id = $student_id");
        }

        if ($student_id > 0 && $created) {
            header("Location: class_enrollment.php?success=enrolled");
            exit();
        }
        $error = 'Unable to save class enrollment. Please try again.';
    }
}

$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes = $selected_department > 0 ? $conn->query("SELECT id, class_name FROM classes WHERE department_id = $selected_department ORDER BY class_name ASC") : false;
$current_department = $student['department_name'] ?? 'Not assigned';
$current_class = $student['class_name'] ?? 'Not assigned';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Enrollment</title>
    <link rel="stylesheet" href="../css/style.css?v=3.5">
</head>
<body>
<div class="dashboard class-enrollment-page">
    <div class="dashboard-header">
        <div><span class="eyebrow">ACADEMIC PROFILE</span><h2>Class Enrollment</h2><p class="subtitle">Choose the department and class you want to join.</p></div>
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'enrolled'): ?><div class="success">You have been enrolled in the selected class successfully.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="current-class-card"><span>Current enrollment</span><strong><?php echo htmlspecialchars($current_department); ?></strong><small><?php echo htmlspecialchars($current_class); ?></small></div>

    <section class="enrollment-panel">
        <div class="panel-heading"><div><h3>Select a new class</h3><p>Your current class will be replaced when you submit a new enrollment.</p></div></div>
        <form method="post" class="class-enrollment-form">
            <div class="input-group"><label for="department_id">Department</label><select id="department_id" name="department_id" onchange="this.form.submit()" required><option value="">Choose department</option><?php while ($department = $departments->fetch_assoc()): ?><option value="<?php echo (int)$department['id']; ?>" <?php echo $selected_department === (int)$department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option><?php endwhile; ?></select></div>
            <div class="input-group"><label for="class_id">Class</label><select id="class_id" name="class_id" required><option value="">Choose class</option><?php if ($classes): while ($class = $classes->fetch_assoc()): ?><option value="<?php echo (int)$class['id']; ?>" <?php echo $selected_class === (int)$class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option><?php endwhile; endif; ?></select></div>
            <button type="submit" name="enroll_class" value="1" class="btn-primary">Enroll in class</button>
        </form>
    </section>
</div>
</body>
</html>
