<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') { header("Location: ../login.php"); exit(); }
$subject_class_column = $conn->query("SHOW COLUMNS FROM subjects LIKE 'class_id'");
if ($subject_class_column && $subject_class_column->num_rows === 0) { $conn->query("ALTER TABLE subjects ADD COLUMN class_id INT NULL AFTER departmentid"); }

function makeDepartmentPrefix($departmentName) {
    $words = preg_split('/\s+/', trim($departmentName));
    $prefix = '';

    foreach ($words as $word) {
        $cleanWord = preg_replace('/[^A-Za-z]/', '', $word);
        if ($cleanWord !== '') {
            $prefix .= strtoupper(substr($cleanWord, 0, 1));
        }
    }

    return strtoupper(substr($prefix, 0, 4)) ?: 'DEP';
}

$dept_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$class_id = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
$department_name = $conn->query("SELECT name FROM departments WHERE id = '$dept_id'")->fetch_assoc()['name'] ?? 'Selected Department';
$department_prefix = makeDepartmentPrefix($department_name);
$error_message = '';

if (isset($_POST['add_subject'])) {
    $raw_code = strtoupper(trim($_POST['subject_code']));
    $raw_code = preg_replace('/\s+/', ' ', $raw_code);

    if (!preg_match('/^' . preg_quote($department_prefix, '/') . '/', $raw_code)) {
        $raw_code = $department_prefix . '-' . ltrim($raw_code, '-');
    }

    $code = $conn->real_escape_string($raw_code);
    $name = $conn->real_escape_string($_POST['name']);
    $credits = $conn->real_escape_string($_POST['credits']);

    if (!empty($code) && !empty($name)) {
        $check = $conn->query("SELECT id FROM subjects WHERE subject_code = '$code' AND departmentid = '$dept_id'");

        if ($check && $check->num_rows > 0) {
            $error_message = 'This subject code already exists in this department.';
        } else {
            $conn->query("INSERT INTO subjects (subject_code, name, credits, departmentid, class_id) VALUES ('$code', '$name', '$credits', '$dept_id', '$class_id')");
            header("Location: view_subjects.php?department_id=$dept_id&class_id=$class_id"); exit();
        }
    } else {
        $error_message = 'Please enter a valid subject code and name.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subject</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>
<div class="form-container" style="max-width: 620px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
    <div class="form-header">
        <div>
            <h2>➕ Add New Subject</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong></p>
        </div>
        <a href="view_subjects.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $class_id; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="error" style="margin-bottom: 16px;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
        <div class="form-row">
            <div class="input-group">
                <label for="subject_code">Subject Code</label>
                <input type="text" id="subject_code" name="subject_code" required placeholder="<?php echo htmlspecialchars($department_prefix . '-101'); ?>">
            </div>
            <div class="input-group">
                <label for="credits">Credits</label>
                <input type="text" id="credits" name="credits" value="3">
            </div>
        </div>

        <div class="input-group">
            <label for="name">Subject Name</label>
            <input type="text" id="name" name="name" required placeholder="Database Systems">
        </div>

        <button type="submit" name="add_subject" class="btn-submit" style="width:100%;">💾 Save Subject</button>
    </form>
</div>
</body>
</html>