<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php"); exit();
}

$subject_class_column = $conn->query("SHOW COLUMNS FROM subjects LIKE 'class_id'");
if ($subject_class_column && $subject_class_column->num_rows === 0) {
    $conn->query("ALTER TABLE subjects ADD COLUMN class_id INT NULL AFTER departmentid");
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_class_assignments (id INT AUTO_INCREMENT PRIMARY KEY, teacher_user_id INT NOT NULL, department_id INT NOT NULL, class_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY teacher_class (teacher_user_id, class_id), INDEX (department_id))");
$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);
$allowed_departments = $conn->query("SELECT DISTINCT department_id FROM teacher_class_assignments WHERE teacher_user_id = $teacher_user_id");
$department_ids = [];
if ($allowed_departments) { while ($allowed = $allowed_departments->fetch_assoc()) { $department_ids[] = (int)$allowed['department_id']; } }
$requested_department = (int)($_GET['department_id'] ?? 0);
$dept_id = in_array($requested_department, $department_ids, true) ? $requested_department : ($department_ids[0] ?? 0);
$requested_class = (int)($_GET['class_id'] ?? 0);
$classes = $conn->query("SELECT c.id, c.class_name FROM classes c INNER JOIN teacher_class_assignments a ON a.class_id = c.id WHERE a.teacher_user_id = $teacher_user_id AND a.department_id = $dept_id ORDER BY c.class_name ASC");
$class_options = [];
$class_ids = [];
if ($classes) { while ($class = $classes->fetch_assoc()) { $class_options[] = $class; $class_ids[] = (int)$class['id']; } }
$selected_class = in_array($requested_class, $class_ids, true) ? $requested_class : 0;
$departments = $conn->query("SELECT id, name FROM departments WHERE id IN (" . ($department_ids ? implode(',', $department_ids) : '0') . ") ORDER BY name ASC");
$department_name = $conn->query("SELECT name FROM departments WHERE id = '$dept_id'")->fetch_assoc()['name'] ?? 'Selected Department';
$class_name = $selected_class > 0 ? ($conn->query("SELECT class_name FROM classes WHERE id = '$selected_class'")->fetch_assoc()['class_name'] ?? 'Selected Class') : 'Select a class';
$subjects = $selected_class > 0 ? $conn->query("SELECT * FROM subjects WHERE departmentid = '$dept_id' AND class_id = '$selected_class' ORDER BY subject_code ASC") : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
    <link rel="stylesheet" href="../css/style.css?v=3.0">
    <style>
        /* Modern UI & Color Variables */
        :root {
            --bg-soft: #f8fafc;
            --text-dark: #1e293b;
            --border-soft: #e2e8f0;
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-dark);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .subjects-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            border: 1px solid var(--border-soft);
        }

        .dashboard-header {
            border-bottom: 2px solid var(--bg-soft);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .main-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        /* Buttons Styling */
        .btn-back-modern {
            display: inline-flex;
            align-items: center;
            background: #ffffff;
            color: #475569;
            border: 1px solid var(--border-soft);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-back-modern:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .btn-add-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--success-color);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }

        .btn-add-modern:hover {
            background: #059669;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }

        /* Table Styling */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        .modern-table th {
            background: var(--bg-soft);
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            border-bottom: 2px solid var(--border-soft);
            text-align: left;
        }

        .modern-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-soft);
            font-size: 15px;
            color: #334155;
            vertical-align: middle;
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        .modern-table tr:hover td {
            background-color: #f8fafc;
        }

        /* Component Badges */
        .code-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            display: inline-block;
        }

        .credits-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border-soft);
        }

        .subject-name {
            font-weight: 600;
            color: #0f172a;
        }

        /* Action Links as Minimal Buttons */
        .action-link {
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .action-edit {
            color: var(--primary-color);
            background: #e0e7ff;
            margin-right: 8px;
        }

        .action-edit:hover {
            background: #c7d2fe;
        }

        .action-delete {
            color: var(--danger-color);
            background: #fee2e2;
        }

        .action-delete:hover {
            background: #fecaca;
        }

        .no-data {
            text-align: center;
            padding: 40px !important;
            color: #94a3b8;
            font-size: 15px;
        }
    </style>
</head>
<body>

<div class="dashboard container" style="max-width: 1000px; margin: 50px auto; padding: 0 15px;">
    
    <div class="subjects-card">
        <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2 class="main-title">📚 Manage Department Subjects</h2>
                <p style="margin-top: 8px; color: #64748b;">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong> · Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
            </div>
            <a href="dashboard.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back-modern">⬅ Back to Dashboard</a>
        </div>

        <div class="dept-form-box" style="margin-bottom: 20px;">
            <form method="get" class="dept-form">
                <div class="input-group dept-form-field">
                    <label for="department_id">Select Department</label>
                    <select id="department_id" name="department_id" onchange="this.form.submit()">
                        <option value="">-- Select Department --</option>
                        <?php while ($department = $departments->fetch_assoc()): ?>
                            <option value="<?php echo (int)$department['id']; ?>" <?php echo ($dept_id == (int)$department['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group dept-form-field">
                    <label for="class_id">Select Class</label>
                    <select id="class_id" name="class_id" onchange="this.form.submit()">
                        <option value="">-- Select Class --</option>
                        <?php foreach ($class_options as $class): ?>
                            <option value="<?php echo (int)$class['id']; ?>" <?php echo $selected_class === (int)$class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div style="margin: 20px 0; display: flex; justify-content: flex-end;">
            <a href="subject_add.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="btn-add-modern">➕ Add New Subject</a>
        </div>

        <div class="table-container" style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Code</th>
                        <th style="width: 45%;">Subject Name</th>
                        <th style="width: 15%;">Credits</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($subjects && $subjects->num_rows > 0): ?>
                        <?php while($row = $subjects->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="code-badge"><?php echo $row['subject_code']; ?></span>
                                </td>
                                <td>
                                    <span class="subject-name"><?php echo $row['name']; ?></span>
                                </td>
                                <td>
                                    <span class="credits-badge">🪙 <?php echo $row['credits']; ?> Credits</span>
                                </td>
                                <td>
                                    <a href="subject_edit.php?id=<?php echo $row['id']; ?>&department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="action-link action-edit">✏️ Edit</a>
                                    <a href="subject_delete.php?id=<?php echo $row['id']; ?>&department_id=<?php echo $dept_id; ?>&class_id=<?php echo $selected_class; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this subject?');">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data">
                                📭 No subjects found for your department.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>