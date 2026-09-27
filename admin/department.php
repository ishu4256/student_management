<?php
include "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (department_id)
)");

$class_error = '';
$edit_class_id_from_post = 0;

if (isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name'] ?? '');
    $class_department_id = (int)($_POST['class_department_id'] ?? 0);

    if ($class_name === '' || $class_department_id <= 0) {
        $class_error = 'Please select a department and enter a class name.';
    } else {
        $class_name_sql = $conn->real_escape_string($class_name);
        $duplicate = $conn->query("SELECT id FROM classes WHERE department_id = $class_department_id AND LOWER(class_name) = LOWER('$class_name_sql')");
        if ($duplicate && $duplicate->num_rows > 0) {
            $class_error = 'This class already exists in the selected department.';
        } elseif ($conn->query("INSERT INTO classes (department_id, class_name) VALUES ($class_department_id, '$class_name_sql')")) {
            header("Location: department.php?success=class_added&class_department=$class_department_id");
            exit();
        } else {
            $class_error = 'Unable to add the class. Please try again.';
        }
    }
}

if (isset($_POST['update_class'])) {
    $class_id = (int)($_POST['class_id'] ?? 0);
    $edit_class_id_from_post = $class_id;
    $class_name = trim($_POST['class_name'] ?? '');
    $class_department_id = (int)($_POST['class_department_id'] ?? 0);

    if ($class_id <= 0 || $class_name === '' || $class_department_id <= 0) {
        $class_error = 'Please provide a valid department and class name.';
    } else {
        $class_name_sql = $conn->real_escape_string($class_name);
        $duplicate = $conn->query("SELECT id FROM classes WHERE department_id = $class_department_id AND LOWER(class_name) = LOWER('$class_name_sql') AND id != $class_id");
        if ($duplicate && $duplicate->num_rows > 0) {
            $class_error = 'This class already exists in the selected department.';
        } elseif ($conn->query("UPDATE classes SET department_id = $class_department_id, class_name = '$class_name_sql' WHERE id = $class_id")) {
            header("Location: department.php?success=class_updated&class_department=$class_department_id");
            exit();
        } else {
            $class_error = 'Unable to update the class. Please try again.';
        }
    }
}

if (isset($_GET['delete_class_id'])) {
    $delete_class_id = (int)$_GET['delete_class_id'];
    $class_department_id = (int)($_GET['class_department'] ?? 0);
    if ($delete_class_id > 0 && $conn->query("DELETE FROM classes WHERE id = $delete_class_id")) {
        header("Location: department.php?success=class_deleted&class_department=$class_department_id");
        exit();
    }
    $class_error = 'Unable to delete this class. It may already be linked to records.';
}

if (isset($_POST['add_dept'])) {
    $dept_name = $_POST['dept_name'] ?? '';

    if (!empty($dept_name)) {
        $dept_name = $conn->real_escape_string($dept_name);
        $conn->query("INSERT INTO departments (name) VALUES ('$dept_name')");
        header("Location: department.php?success=added");
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM departments WHERE id = '$delete_id'");
    header("Location: department.php?success=deleted");
    exit();
}

$edit_dept = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM departments WHERE id = '$edit_id'");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_dept = $edit_result->fetch_assoc();
    }
}

if (isset($_POST['update_dept'])) {
    $department_id = (int)$_POST['department_id'];
    $dept_name = $_POST['dept_name'] ?? '';

    if ($department_id > 0 && !empty($dept_name)) {
        $dept_name = $conn->real_escape_string($dept_name);
        $conn->query("UPDATE departments SET name = '$dept_name' WHERE id = '$department_id'");
        header("Location: department.php?success=updated");
        exit();
    }
}

$result = $conn->query("SELECT * FROM departments ORDER BY id DESC");
$selected_class_department = (int)($_GET['class_department'] ?? ($edit_dept['id'] ?? 0));
$edit_class = null;
if (isset($_GET['edit_class_id']) || $edit_class_id_from_post > 0) {
    $edit_class_id = isset($_GET['edit_class_id']) ? (int)$_GET['edit_class_id'] : $edit_class_id_from_post;
    $edit_class_result = $conn->query("SELECT * FROM classes WHERE id = $edit_class_id");
    if ($edit_class_result && $edit_class_result->num_rows > 0) {
        $edit_class = $edit_class_result->fetch_assoc();
        $selected_class_department = (int)$edit_class['department_id'];
    }
}
$class_departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes_result = $selected_class_department > 0
    ? $conn->query("SELECT c.*, d.name AS department_name FROM classes c LEFT JOIN departments d ON d.id = c.department_id WHERE c.department_id = $selected_class_department ORDER BY c.class_name ASC")
    : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments</title>
    <link rel="stylesheet" href="../css/style.css?v=2.8">
</head>
<body>

<div class="dashboard department-container">
    <div class="dashboard-header">
        <div>
            <h2>Department Management</h2>
            <p class="subtitle">Create, edit and manage university/school departments</p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
        <div class="success">Department added successfully.</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
        <div class="success">Department deleted successfully.</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
        <div class="success">Department updated successfully.</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'class_added'): ?>
        <div class="success">Class added successfully.</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'class_updated'): ?>
        <div class="success">Class updated successfully.</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'class_deleted'): ?>
        <div class="success">Class deleted successfully.</div>
    <?php endif; ?>

    <?php if ($class_error !== ''): ?>
        <div class="error"><?php echo htmlspecialchars($class_error); ?></div>
    <?php endif; ?>

    <div class="dept-form-box">
        <h3>➕ Add New Department</h3>
        
        <form method="post" class="dept-form">
            <div class="input-group dept-form-field">
                <label for="dept_name">Department Name</label>
                <input type="text" id="dept_name" name="dept_name" placeholder="E.g. Computer Science, Mathematics" required>
            </div>
            <button type="submit" name="add_dept" class="btn-primary dept-submit">Save Department</button>
        </form>
    </div>

    <?php if ($edit_dept): ?>
        <div class="dept-edit-box" id="department-editor">
            <h3>✏️ Edit Department</h3>
            <form method="post" class="dept-form">
                <input type="hidden" name="department_id" value="<?php echo (int)$edit_dept['id']; ?>">
                <div class="input-group dept-form-field">
                    <label for="edit_dept_name">Department Name</label>
                    <input type="text" id="edit_dept_name" name="dept_name" value="<?php echo htmlspecialchars($edit_dept['name']); ?>" required>
                </div>
                <button type="submit" name="update_dept" class="btn-primary dept-submit">Update Department</button>
                <a href="department.php" class="btn-back">Cancel</a>
            </form>
        </div>
    <?php endif; ?>

    <div class="class-management-panel">
        <div class="class-panel-heading">
            <div>
                <span class="eyebrow">ACADEMIC STRUCTURE</span>
                <h3>Manage classes by department</h3>
                <p>Add, rename, move or remove classes under a selected department.</p>
            </div>
        </div>

        <form method="get" class="class-selector-form">
            <div class="input-group">
                <label for="class_department">Select Department</label>
                <select id="class_department" name="class_department" onchange="this.form.submit()">
                    <option value="0">Choose a department</option>
                    <?php if ($class_departments): while ($class_department = $class_departments->fetch_assoc()): ?>
                        <option value="<?php echo (int)$class_department['id']; ?>" <?php echo $selected_class_department === (int)$class_department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class_department['name']); ?></option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
        </form>

        <?php if ($edit_class): ?>
            <form method="post" class="class-form class-edit-form" id="class-editor">
                <input type="hidden" name="class_id" value="<?php echo (int)$edit_class['id']; ?>">
                <div class="input-group">
                    <label for="edit_class_name">Class Name</label>
                    <input type="text" id="edit_class_name" name="class_name" value="<?php echo htmlspecialchars($edit_class['class_name']); ?>" required>
                </div>
                <div class="input-group">
                    <label for="edit_class_department">Department</label>
                    <select id="edit_class_department" name="class_department_id" required>
                        <?php $edit_departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC"); while ($department = $edit_departments->fetch_assoc()): ?>
                            <option value="<?php echo (int)$department['id']; ?>" <?php echo (int)$edit_class['department_id'] === (int)$department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="update_class" class="btn-primary class-submit">Save changes</button>
                <a href="department.php?class_department=<?php echo $selected_class_department; ?>" class="btn-back">Cancel</a>
            </form>
        <?php elseif ($selected_class_department > 0): ?>
            <form method="post" class="class-form">
                <input type="hidden" name="class_department_id" value="<?php echo $selected_class_department; ?>">
                <div class="input-group">
                    <label for="class_name">New Class Name</label>
                    <input type="text" id="class_name" name="class_name" placeholder="e.g. Grade 10A or Year 1" required>
                </div>
                <button type="submit" name="add_class" class="btn-primary class-submit">+ Add class</button>
            </form>
        <?php endif; ?>

        <?php if ($selected_class_department > 0): ?>
            <div class="class-list-heading"><h4>Classes in selected department</h4></div>
            <div class="class-list">
                <?php if ($classes_result && $classes_result->num_rows > 0): ?>
                    <?php while ($class = $classes_result->fetch_assoc()): ?>
                        <div class="class-list-item">
                            <span><strong><?php echo htmlspecialchars($class['class_name']); ?></strong><small><?php echo htmlspecialchars($class['department_name']); ?></small></span>
                            <div class="table-actions">
                                <a href="./department.php?edit_class_id=<?php echo (int)$class['id']; ?>&class_department=<?php echo $selected_class_department; ?>#class-editor" class="btn-action btn-edit" title="Edit this class">Edit</a>
                                <a href="department.php?delete_class_id=<?php echo (int)$class['id']; ?>&class_department=<?php echo $selected_class_department; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this class? Existing student and assessment links may be affected.');">Delete</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty-state">No classes added for this department yet.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">Select a department to manage its classes.</p>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <h3>Existing Departments</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 150px;">Department ID</th>
                    <th>Department Name</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td><span class="badge" style="background-color: #f1f5f9; color: var(--text-main); padding: 6px 12px; font-size: 14px;"><?php echo htmlspecialchars($row['name']); ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a href="./department.php?edit_id=<?php echo (int)$row['id']; ?>#department-editor" class="btn-action btn-edit" title="Edit this department">Edit</a>
                                    <a href="department.php?delete_id=<?php echo (int)$row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this department?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 20px;">No departments found. Create one above!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>