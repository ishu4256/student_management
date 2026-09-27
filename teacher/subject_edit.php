<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}
$subject_class_column = $conn->query("SHOW COLUMNS FROM subjects LIKE 'class_id'");
if ($subject_class_column && $subject_class_column->num_rows === 0) { $conn->query("ALTER TABLE subjects ADD COLUMN class_id INT NULL AFTER departmentid"); }

$dept_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$class_id = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
$department_name = $conn->query("SELECT name FROM departments WHERE id = '$dept_id'")->fetch_assoc()['name'] ?? 'Selected Department';
$sub = null;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = $conn->query("SELECT * FROM subjects WHERE id='$id' AND departmentid='$dept_id' AND class_id='$class_id'");
    $sub = $res->fetch_assoc();
}

if (isset($_POST['update_subject'])) {
    $id = (int)$_POST['id'];
    $code = $conn->real_escape_string($_POST['subject_code']);
    $name = $conn->real_escape_string($_POST['name']);
    $credits = $conn->real_escape_string($_POST['credits']);

    if (!empty($code) && !empty($name) && $id > 0) {
        $conn->query("UPDATE subjects SET subject_code='$code', name='$name', credits='$credits', class_id='$class_id' WHERE id='$id' AND departmentid='$dept_id' AND class_id='$class_id'");
        header("Location: view_subjects.php?department_id=$dept_id&class_id=$class_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>
<div class="form-container" style="max-width: 620px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
    <div class="form-header">
        <div>
            <h2>✏️ Edit Subject</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong></p>
        </div>
        <a href="view_subjects.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $class_id; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!$sub): ?>
        <div class="error">Subject not found for the selected department.</div>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="id" value="<?php echo (int)$sub['id']; ?>">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <div class="input-group">
                <label for="subject_code">Subject Code</label>
                <input type="text" id="subject_code" name="subject_code" value="<?php echo htmlspecialchars($sub['subject_code']); ?>" required>
            </div>
            <div class="input-group">
                <label for="name">Subject Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($sub['name']); ?>" required>
            </div>
            <div class="input-group">
                <label for="credits">Credits</label>
                <input type="text" id="credits" name="credits" value="<?php echo htmlspecialchars($sub['credits']); ?>">
            </div>
            <button type="submit" name="update_subject" class="btn-submit" style="width:100%;">💾 Update Subject</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>