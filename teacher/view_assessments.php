<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    assessment_type VARCHAR(30) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    due_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS assessment_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    question_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (assessment_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS assessment_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (assessment_id, student_id),
    INDEX (assessment_id),
    INDEX (student_id)
)");

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes = $conn->query("SELECT id, class_name FROM classes WHERE department_id = '$selected_department' ORDER BY class_name ASC");
$department_name = $conn->query("SELECT name FROM departments WHERE id = '$selected_department'")->fetch_assoc()['name'] ?? 'Selected Department';
$class_name = $conn->query("SELECT class_name FROM classes WHERE id = '$selected_class'")->fetch_assoc()['class_name'] ?? 'Selected Class';

$condition = "department_id = '$selected_department'";
if ($selected_class > 0) {
    $condition .= " AND class_id = '$selected_class'";
}

$assessments = $conn->query("SELECT * FROM assessments WHERE $condition ORDER BY due_date DESC, assessment_type ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assessments</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .assessment-card { background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08); max-width: 1100px; margin: 40px auto; }
        .btn-add-modern { display:inline-flex; align-items:center; background:#10b981; color:#fff; padding:10px 18px; border-radius:8px; text-decoration:none; font-weight:700; }
        .modern-table { width:100%; border-collapse:separate; border-spacing:0; }
        .modern-table th { background:#f8fafc; padding:12px; text-align:left; border-bottom:1px solid #e2e8f0; }
        .modern-table td { padding:12px; border-bottom:1px solid #e2e8f0; }
        .pill { display:inline-block; padding:4px 10px; border-radius:999px; background:#e0e7ff; color:#3730a3; font-size:12px; font-weight:700; }
        .assessment-actions { display:flex; flex-wrap:wrap; gap:7px; min-width:260px; }
        .action-link { display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; padding:7px 10px; border-radius:6px; font-size:12px; white-space:nowrap; }
        .action-edit { background:#e0f2fe; color:#0369a1; }
        .action-delete { background:#fee2e2; color:#b91c1c; }
    </style>
</head>
<body>
<div class="assessment-card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
        <div>
            <h2>🧪 Manage Quiz / Assignment / Paper</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong> / Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
        </div>
        <a href="dashboard.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back</a>
    </div>

    <form method="get" class="teacher-filter-form" style="margin-bottom:20px;">
        <div class="input-group" style="display:inline-block; margin-right:15px; min-width:240px;">
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

        <div class="input-group" style="display:inline-block; min-width:240px;">
            <label for="class_id">Select Class</label>
            <select id="class_id" name="class_id" onchange="this.form.submit()">
                <option value="">-- Select Class --</option>
                <?php while ($class = $classes->fetch_assoc()): ?>
                    <option value="<?php echo (int)$class['id']; ?>" <?php echo ($selected_class == (int)$class['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class['class_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>

    <div style="margin: 10px 0 20px; display:flex; justify-content:flex-end;">
        <a href="assessment_add.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-add-modern">➕ Add Assessment</a>
    </div>

    <div style="overflow-x:auto;">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assessments && $assessments->num_rows > 0): ?>
                    <?php while ($row = $assessments->fetch_assoc()): ?>
                        <tr>
                            <td><span class="pill"><?php echo htmlspecialchars(ucfirst($row['assessment_type'])); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(($row['due_date'] ?? '-') . (!empty($row['due_time']) ? ' ' . $row['due_time'] : '')); ?></td>
                            <td>
                                <div class="assessment-actions">
                                    <?php if (!empty($row['document_path'])): ?><a href="../<?php echo htmlspecialchars($row['document_path']); ?>" class="action-link action-edit" target="_blank" rel="noopener">📄 Word</a><?php endif; ?>
                                    <a href="assessment_questions.php?id=<?php echo (int)$row['id']; ?>&department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-link action-edit">❓ Questions</a>
                                    <a href="assessment_students.php?id=<?php echo (int)$row['id']; ?>&department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-link action-edit">👨‍🎓 Students</a>
                                    <a href="assessment_edit.php?id=<?php echo (int)$row['id']; ?>&department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-link action-edit">✏️ Edit</a>
                                    <a href="assessment_delete.php?id=<?php echo (int)$row['id']; ?>&department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="action-link action-delete" onclick="return confirm('Delete this assessment, its questions and student assignments?');">🗑️ Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px; color:#64748b;">No assessments found for the selected department/class.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
