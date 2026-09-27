<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit(); }

$subject_class_column = $conn->query("SHOW COLUMNS FROM subjects LIKE 'class_id'");
if ($subject_class_column && $subject_class_column->num_rows === 0) { $conn->query("ALTER TABLE subjects ADD COLUMN class_id INT NULL AFTER departmentid"); }
$error = '';
$department_id = (int)($_POST['department_id'] ?? $_GET['department_id'] ?? 0);
$class_id = (int)($_POST['class_id'] ?? $_GET['class_id'] ?? 0);
$edit_id = (int)($_GET['edit_id'] ?? 0);

if ($edit_id > 0) {
    $edit_target = $conn->query("SELECT departmentid, class_id FROM subjects WHERE id = $edit_id LIMIT 1");
    if ($edit_target && $edit_target->num_rows > 0) {
        $edit_target_data = $edit_target->fetch_assoc();
        $department_id = (int)$edit_target_data['departmentid'];
        $class_id = (int)$edit_target_data['class_id'];
    }
}

if (isset($_POST['save_subject'])) {
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $code = $conn->real_escape_string(strtoupper(trim($_POST['subject_code'] ?? '')));
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $credits = $conn->real_escape_string(trim($_POST['credits'] ?? ''));
    $valid_class = $conn->query("SELECT id FROM classes WHERE id = $class_id AND department_id = $department_id");
    if ($department_id <= 0 || $class_id <= 0 || !$valid_class || $valid_class->num_rows === 0 || $code === '' || $name === '') {
        $error = 'Select a valid department/class and complete all subject fields.';
    } else {
        $duplicate = $conn->query("SELECT id FROM subjects WHERE subject_code = '$code' AND departmentid = $department_id AND class_id = $class_id AND id != $subject_id");
        if ($duplicate && $duplicate->num_rows > 0) { $error = 'This subject code already exists in the selected class.'; }
        elseif ($subject_id > 0) { $conn->query("UPDATE subjects SET subject_code='$code', name='$name', credits='$credits', departmentid=$department_id, class_id=$class_id WHERE id=$subject_id"); header("Location: subjects.php?department_id=$department_id&class_id=$class_id&success=updated"); exit(); }
        elseif ($conn->query("INSERT INTO subjects (subject_code, name, credits, departmentid, class_id) VALUES ('$code', '$name', '$credits', $department_id, $class_id)")) { header("Location: subjects.php?department_id=$department_id&class_id=$class_id&success=added"); exit(); }
        else { $error = 'Unable to save subject.'; }
    }
}
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM subjects WHERE id=$delete_id AND departmentid=$department_id AND class_id=$class_id");
    header("Location: subjects.php?department_id=$department_id&class_id=$class_id&success=deleted"); exit();
}
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes = $department_id > 0 ? $conn->query("SELECT id, class_name FROM classes WHERE department_id=$department_id ORDER BY class_name ASC") : false;
$subjects = $class_id > 0 ? $conn->query("SELECT * FROM subjects WHERE departmentid=$department_id AND class_id=$class_id ORDER BY subject_code ASC") : false;
$editing = $edit_id > 0 ? $conn->query("SELECT * FROM subjects WHERE id=$edit_id AND departmentid=$department_id AND class_id=$class_id")->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Class Subjects</title><link rel="stylesheet" href="../css/style.css?v=3.8"></head>
<body><div class="dashboard admin-subjects-page">
    <div class="dashboard-header"><div><span class="eyebrow">ACADEMIC MANAGEMENT</span><h2>Class Subjects</h2><p class="subtitle">Add, edit and delete subjects for a specific department and class.</p></div><a href="dashboard.php" class="btn-back">Back to Dashboard</a></div>
    <?php if (isset($_GET['success'])): ?><div class="success">Subject <?php echo htmlspecialchars($_GET['success']); ?> successfully.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <section class="enrollment-panel">
        <form method="get" class="admin-subject-filter"><div class="input-group"><label>Department</label><select name="department_id" onchange="this.form.submit()"><option value="0">Select department</option><?php while ($department = $departments->fetch_assoc()): ?><option value="<?php echo (int)$department['id']; ?>" <?php echo $department_id === (int)$department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option><?php endwhile; ?></select></div><div class="input-group"><label>Class</label><select name="class_id" onchange="this.form.submit()"><option value="0">Select class</option><?php if ($classes): while ($class = $classes->fetch_assoc()): ?><option value="<?php echo (int)$class['id']; ?>" <?php echo $class_id === (int)$class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option><?php endwhile; endif; ?></select></div></form>
        <?php if ($class_id > 0): ?><form method="post" class="admin-subject-form" id="subject-editor"><input type="hidden" name="subject_id" value="<?php echo (int)($editing['id'] ?? 0); ?>"><input type="hidden" name="department_id" value="<?php echo $department_id; ?>"><input type="hidden" name="class_id" value="<?php echo $class_id; ?>"><div class="input-group"><label>Subject code</label><input type="text" name="subject_code" value="<?php echo htmlspecialchars($editing['subject_code'] ?? ''); ?>" required></div><div class="input-group"><label>Subject name</label><input type="text" name="name" value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>" required></div><div class="input-group"><label>Credits</label><input type="text" name="credits" value="<?php echo htmlspecialchars($editing['credits'] ?? '3'); ?>"></div><button type="submit" name="save_subject" class="btn-primary subject-save-button"><?php echo $editing ? 'Save changes' : '+ Add subject'; ?></button><?php if ($editing): ?><a href="subjects.php?department_id=<?php echo $department_id; ?>&class_id=<?php echo $class_id; ?>" class="btn-back">Cancel</a><?php endif; ?></form>
        <div class="table-container"><table><thead><tr><th>Code</th><th>Subject</th><th>Credits</th><th>Actions</th></tr></thead><tbody><?php if ($subjects && $subjects->num_rows > 0): while ($subject = $subjects->fetch_assoc()): ?><tr><td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td><td><?php echo htmlspecialchars($subject['name']); ?></td><td><?php echo htmlspecialchars($subject['credits'] ?? '-'); ?></td><td><div class="directory-actions"><a class="table-edit" href="subjects.php?department_id=<?php echo $department_id; ?>&class_id=<?php echo $class_id; ?>&edit_id=<?php echo (int)$subject['id']; ?>#subject-editor" title="Edit subject">Edit</a><a class="table-delete" href="subjects.php?department_id=<?php echo $department_id; ?>&class_id=<?php echo $class_id; ?>&delete_id=<?php echo (int)$subject['id']; ?>" onclick="return confirm('Delete this subject?');">Delete</a></div></td></tr><?php endwhile; else: ?><tr><td colspan="4" class="empty-table">No subjects in this class yet.</td></tr><?php endif; ?></tbody></table></div><?php else: ?><p class="empty-state">Select a department and class to manage subjects.</p><?php endif; ?>
    </section>
</div></body></html>
