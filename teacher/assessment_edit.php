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
$document_column = $conn->query("SHOW COLUMNS FROM assessments LIKE 'document_path'");
if ($document_column && $document_column->num_rows === 0) {
    $conn->query("ALTER TABLE assessments ADD COLUMN document_path VARCHAR(255) NULL");
}
$due_time_column = $conn->query("SHOW COLUMNS FROM assessments LIKE 'due_time'");
if ($due_time_column && $due_time_column->num_rows === 0) { $conn->query("ALTER TABLE assessments ADD COLUMN due_time TIME NULL AFTER due_date"); }

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$assessment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$department_name = $conn->query("SELECT name FROM departments WHERE id = '$selected_department'")->fetch_assoc()['name'] ?? 'Selected Department';
$class_name = $conn->query("SELECT class_name FROM classes WHERE id = '$selected_class'")->fetch_assoc()['class_name'] ?? 'Selected Class';
$assessment = $conn->query("SELECT * FROM assessments WHERE id = '$assessment_id' AND department_id = '$selected_department' AND class_id = '$selected_class'")->fetch_assoc();

if (isset($_POST['update_assessment'])) {
    $type = $conn->real_escape_string($_POST['assessment_type']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $due_date = $conn->real_escape_string($_POST['due_date']);
    $due_time = $conn->real_escape_string($_POST['due_time'] ?? '');

    $document_sql = '';
    if (!empty($_FILES['document']['name'])) {
        $extension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['doc', 'docx'], true) || $_FILES['document']['error'] !== UPLOAD_ERR_OK || $_FILES['document']['size'] > 8 * 1024 * 1024) {
            $error_message = 'Please upload a valid Word document (.doc or .docx) under 8MB.';
        } else {
            $upload_dir = __DIR__ . '/../uploads/assessments';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            $safe_name = 'assessment_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            if (move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . '/' . $safe_name)) {
                $document_sql = ", document_path='uploads/assessments/$safe_name'";
            }
        }
    }

    $conn->query("UPDATE assessments SET assessment_type='$type', title='$title', description='$description', due_date='$due_date', due_time='$due_time' $document_sql
        WHERE id='$assessment_id' AND department_id='$selected_department' AND class_id='$selected_class'");

    header("Location: view_assessments.php?department_id=$selected_department&class_id=$selected_class");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Assessment</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>
<div class="form-container" style="max-width: 700px; margin:50px auto; padding:30px; background:#fff; border-radius:16px; box-shadow:var(--shadow);">
    <div class="form-header">
        <div>
            <h2>✏️ Edit Assessment</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong> / Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
        </div>
        <a href="view_assessments.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!$assessment): ?>
        <div class="error">Assessment not found.</div>
    <?php else: ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-row">
                <div class="input-group">
                    <label for="assessment_type">Type</label>
                    <select id="assessment_type" name="assessment_type" required>
                        <option value="quiz" <?php echo ($assessment['assessment_type'] === 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                        <option value="assignment" <?php echo ($assessment['assessment_type'] === 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                        <option value="paper" <?php echo ($assessment['assessment_type'] === 'paper') ? 'selected' : ''; ?>>Paper</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($assessment['due_date'] ?? ''); ?>">
                </div>
                <div class="input-group">
                    <label for="due_time">Due Time</label>
                    <input type="time" id="due_time" name="due_time" value="<?php echo htmlspecialchars($assessment['due_time'] ?? ''); ?>">
                </div>
            </div>

            <div class="input-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($assessment['title']); ?>">
            </div>

            <div class="input-group">
                <label for="description">Details</label>
                <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($assessment['description'] ?? ''); ?></textarea>
            </div>

            <?php if (!empty($assessment['document_path'])): ?>
                <p class="form-help">Current document: <a href="../<?php echo htmlspecialchars($assessment['document_path']); ?>" target="_blank" rel="noopener">Download Word document</a></p>
            <?php endif; ?>
            <div class="input-group">
                <label for="document">Replace Word Document (optional)</label>
                <input type="file" id="document" name="document" accept=".doc,.docx">
            </div>

            <button type="submit" name="update_assessment" class="btn-submit" style="width:100%;">💾 Update Assessment</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
