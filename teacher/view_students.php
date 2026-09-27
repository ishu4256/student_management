<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_class_assignments (id INT AUTO_INCREMENT PRIMARY KEY, teacher_user_id INT NOT NULL, department_id INT NOT NULL, class_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY teacher_class (teacher_user_id, class_id), INDEX (department_id))");
$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);
$requested_department = (int)($_GET['department_id'] ?? 0);
$requested_class = (int)($_GET['class_id'] ?? 0);
$allowed_departments = $conn->query("SELECT DISTINCT department_id FROM teacher_class_assignments WHERE teacher_user_id = $teacher_user_id");
$department_ids = [];
if ($allowed_departments) { while ($allowed = $allowed_departments->fetch_assoc()) { $department_ids[] = (int)$allowed['department_id']; } }
$dept_id = in_array($requested_department, $department_ids, true) ? $requested_department : ($department_ids[0] ?? 0);

$classes_query = $conn->query("SELECT c.id, c.class_name FROM classes c INNER JOIN teacher_class_assignments a ON a.class_id = c.id WHERE a.teacher_user_id = $teacher_user_id AND a.department_id = $dept_id ORDER BY c.class_name ASC");
$allowed_class_ids = [];
$class_options = [];
if ($classes_query) { while ($class = $classes_query->fetch_assoc()) { $allowed_class_ids[] = (int)$class['id']; $class_options[] = $class; } }
$selected_class = in_array($requested_class, $allowed_class_ids, true) ? $requested_class : 0;
$students = $selected_class > 0 ? $conn->query(
    "SELECT s.*, d.name AS department_name, c.class_name
     FROM students s
     LEFT JOIN departments d ON d.id = s.departmentid
     LEFT JOIN classes c ON c.id = s.class_id
    WHERE s.departmentid = '$dept_id' AND s.class_id = '$selected_class'
     ORDER BY s.name ASC"
) : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Students</title>
    <link rel="stylesheet" href="../css/style.css?v=3.0">
</head>
<body>

<div class="dashboard teachers-container" style="max-width: 1000px; margin: 40px auto;">
    <div class="dashboard-header">
        <div>
            <h2>Student Details</h2>
            <p class="subtitle">View students for the selected department and class.</p>
        </div>
        <a href="dashboard.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <div style="margin: 20px 0; display: flex; justify-content: flex-end;">
        <?php if ($selected_class > 0): ?><a href="student_add.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="btn-primary">➕ Add Student</a><?php endif; ?>
    </div>

    <div class="dept-form-box">
        <form method="get" class="dept-form">
            <input type="hidden" name="department_id" value="<?php echo $dept_id; ?>">
            <div class="input-group dept-form-field">
                <label for="class_id">Select Class</label>
                <select id="class_id" name="class_id" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($class_options as $row): ?>
                        <option value="<?php echo (int)$row['id']; ?>" <?php echo ($selected_class == (int)$row['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Department</th>
                    <th>Class</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($students && $students->num_rows > 0): ?>
                    <?php while($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo (int)$student['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['address'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['department_name'] ?? 'Department'); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></td>
                            <td>
                                <div class="directory-actions">
                                    <a href="student_details.php?id=<?php echo (int)$student['id']; ?>&department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="table-view">Details</a>
                                    <a href="student_edit.php?id=<?php echo (int)$student['id']; ?>&department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="table-edit">Edit</a>
                                    <a href="student_delete.php?id=<?php echo (int)$student['id']; ?>&department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="table-delete" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 20px;">No student records found for this selection.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>