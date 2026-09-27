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
$department_name = $conn->query("SELECT name FROM departments WHERE id = '$selected_department'")->fetch_assoc()['name'] ?? 'Selected Department';
$class_name = $conn->query("SELECT class_name FROM classes WHERE id = '$selected_class'")->fetch_assoc()['class_name'] ?? 'Selected Class';
$error_message = '';

if (isset($_POST['add_assessment'])) {
    $type = $conn->real_escape_string($_POST['assessment_type']);
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $due_date = $conn->real_escape_string($_POST['due_date']);
    $due_time = $conn->real_escape_string($_POST['due_time'] ?? '');
    $teacher_id = $_SESSION['user_id'] ?? 0;

    $document_path = '';
    if (!empty($_FILES['document']['name'])) {
        $allowed_extensions = ['doc', 'docx'];
        $extension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions, true) || $_FILES['document']['error'] !== UPLOAD_ERR_OK || $_FILES['document']['size'] > 8 * 1024 * 1024) {
            $error_message = 'Please upload a valid Word document (.doc or .docx) under 8MB.';
        } else {
            $upload_dir = __DIR__ . '/../uploads/assessments';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
            $safe_name = 'assessment_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            if (move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . '/' . $safe_name)) {
                $document_path = 'uploads/assessments/' . $safe_name;
            } else {
                $error_message = 'The Word document could not be uploaded.';
            }
        }
    }

    if ($error_message === '' && !empty($type) && !empty($title) && $selected_department > 0 && $selected_class > 0) {
        $document_sql = $conn->real_escape_string($document_path);
        $conn->query("INSERT INTO assessments (department_id, class_id, assessment_type, title, description, due_date, due_time, document_path, created_by)
            VALUES ('$selected_department', '$selected_class', '$type', '$title', '$description', '$due_date', '$due_time', '$document_sql', '$teacher_id')");
        $new_assessment_id = $conn->insert_id;
        if ($type === 'quiz') {
            header("Location: assessment_questions.php?id=$new_assessment_id&department_id=$selected_department&class_id=$selected_class");
        } else {
            header("Location: view_assessments.php?department_id=$selected_department&class_id=$selected_class");
        }
        exit();
    } else {
        $error_message = 'Please fill all required fields and select a department/class.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Assessment</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>
<div class="form-container" style="max-width: 700px; margin:50px auto; padding:30px; background:#fff; border-radius:16px; box-shadow:var(--shadow);">
    <div class="form-header">
        <div>
            <h2>➕ Add New Assessment</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong> / Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
        </div>
        <a href="view_assessments.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="error" style="margin-bottom:16px;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-row">
            <div class="input-group">
                <label for="assessment_type">Type</label>
                <select id="assessment_type" name="assessment_type" required>
                    <option value="quiz">Quiz</option>
                    <option value="assignment">Assignment</option>
                    <option value="paper">Paper</option>
                </select>
            </div>
            <div class="input-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date">
            </div>
            <div class="input-group">
                <label for="due_time">Due Time</label>
                <input type="time" id="due_time" name="due_time">
            </div>
        </div>

        <div class="input-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required placeholder="Midterm Assignment">
        </div>

        <div class="input-group">
            <label for="description">Details</label>
            <textarea id="description" name="description" rows="5" placeholder="Enter assessment details"></textarea>
        </div>

        <div class="input-group">
            <label for="document">Word Document (optional)</label>
            <input type="file" id="document" name="document" accept=".doc,.docx">
            <small class="form-help">Upload a .doc or .docx file, maximum 8MB.</small>
        </div>

        <button type="submit" name="add_assessment" class="btn-submit" style="width:100%;">💾 Save Assessment</button>
    </form>
</div>
</body>
</html>
