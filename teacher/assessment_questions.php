<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS assessment_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    question_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (assessment_id)
)");
foreach (['option_a' => 'VARCHAR(255) NULL', 'option_b' => 'VARCHAR(255) NULL', 'option_c' => 'VARCHAR(255) NULL', 'option_d' => 'VARCHAR(255) NULL', 'correct_option' => "CHAR(1) NULL"] as $column => $definition) {
    $column_check = $conn->query("SHOW COLUMNS FROM assessment_questions LIKE '$column'");
    if ($column_check && $column_check->num_rows === 0) {
        $conn->query("ALTER TABLE assessment_questions ADD COLUMN $column $definition");
    }
}

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$assessment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$assessment = $conn->query("SELECT * FROM assessments WHERE id = '$assessment_id' AND department_id = '$selected_department' AND class_id = '$selected_class'")->fetch_assoc();
$is_quiz = ($assessment['assessment_type'] ?? '') === 'quiz';
$questions = $conn->query("SELECT * FROM assessment_questions WHERE assessment_id = '$assessment_id' ORDER BY id ASC");
$edit_question = null;
if (isset($_GET['edit_question'])) {
    $edit_question_id = (int)$_GET['edit_question'];
    $edit_question_result = $conn->query("SELECT * FROM assessment_questions WHERE id = $edit_question_id AND assessment_id = $assessment_id");
    $edit_question = $edit_question_result ? $edit_question_result->fetch_assoc() : null;
}

if (isset($_POST['add_question'])) {
    $question_text = $conn->real_escape_string($_POST['question_text']);
    $option_a = $conn->real_escape_string(trim($_POST['option_a'] ?? ''));
    $option_b = $conn->real_escape_string(trim($_POST['option_b'] ?? ''));
    $option_c = $conn->real_escape_string(trim($_POST['option_c'] ?? ''));
    $option_d = $conn->real_escape_string(trim($_POST['option_d'] ?? ''));
    $correct_option = strtoupper($_POST['correct_option'] ?? '');
    if (!empty($question_text) && (!$is_quiz || ($option_a !== '' && $option_b !== '' && $option_c !== '' && $option_d !== '' && in_array($correct_option, ['A', 'B', 'C', 'D'], true)))) {
        $correct_sql = $conn->real_escape_string($correct_option);
        $conn->query("INSERT INTO assessment_questions (assessment_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES ('$assessment_id', '$question_text', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_sql')");
        header("Location: assessment_questions.php?id=$assessment_id&department_id=$selected_department&class_id=$selected_class");
        exit();
    }
}

if (isset($_POST['update_question'])) {
    $question_id = (int)($_POST['question_id'] ?? 0);
    $question_text = $conn->real_escape_string(trim($_POST['question_text'] ?? ''));
    $option_a = $conn->real_escape_string(trim($_POST['option_a'] ?? ''));
    $option_b = $conn->real_escape_string(trim($_POST['option_b'] ?? ''));
    $option_c = $conn->real_escape_string(trim($_POST['option_c'] ?? ''));
    $option_d = $conn->real_escape_string(trim($_POST['option_d'] ?? ''));
    $correct_option = strtoupper($_POST['correct_option'] ?? '');
    if ($question_id > 0 && $question_text !== '' && (!$is_quiz || ($option_a !== '' && $option_b !== '' && $option_c !== '' && $option_d !== '' && in_array($correct_option, ['A', 'B', 'C', 'D'], true)))) {
        $correct_sql = $conn->real_escape_string($correct_option);
        $conn->query("UPDATE assessment_questions SET question_text = '$question_text', option_a = '$option_a', option_b = '$option_b', option_c = '$option_c', option_d = '$option_d', correct_option = '$correct_sql' WHERE id = '$question_id' AND assessment_id = '$assessment_id'");
        header("Location: assessment_questions.php?id=$assessment_id&department_id=$selected_department&class_id=$selected_class");
        exit();
    }
}

if (isset($_GET['delete_question'])) {
    $question_id = (int)$_GET['delete_question'];
    $conn->query("DELETE FROM assessment_questions WHERE id = '$question_id' AND assessment_id = '$assessment_id'");
    header("Location: assessment_questions.php?id=$assessment_id&department_id=$selected_department&class_id=$selected_class");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assessment Questions</title>
    <link rel="stylesheet" href="../css/style.css?v=3.3">
</head>
<body>
<div class="form-container" style="max-width: 900px; margin:50px auto; padding:30px; background:#fff; border-radius:16px; box-shadow:var(--shadow);">
    <div class="form-header">
        <div>
            <h2>❓ Assessment Questions</h2>
            <p class="subtitle">Assessment: <strong><?php echo htmlspecialchars($assessment['title'] ?? ''); ?></strong></p>
        </div>
        <a href="view_assessments.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!$assessment): ?>
        <div class="error">Assessment not found.</div>
    <?php else: ?>
        <?php if ($edit_question): ?>
            <form method="post" style="margin-bottom:24px;">
                <input type="hidden" name="question_id" value="<?php echo (int)$edit_question['id']; ?>">
                <div class="input-group">
                    <label for="edit_question_text">Edit Question</label>
                    <textarea id="edit_question_text" name="question_text" rows="4" required><?php echo htmlspecialchars($edit_question['question_text']); ?></textarea>
                </div>
                <?php if ($is_quiz): ?>
                    <div class="quiz-options-grid">
                        <?php foreach (['a', 'b', 'c', 'd'] as $option): ?><div class="input-group"><label>Answer <?php echo strtoupper($option); ?></label><input type="text" name="option_<?php echo $option; ?>" value="<?php echo htmlspecialchars($edit_question['option_' . $option] ?? ''); ?>" required></div><?php endforeach; ?>
                    </div>
                    <div class="input-group"><label>Correct answer</label><select name="correct_option" required><option value="">Select correct answer</option><?php foreach (['A', 'B', 'C', 'D'] as $option): ?><option value="<?php echo $option; ?>" <?php echo (($edit_question['correct_option'] ?? '') === $option) ? 'selected' : ''; ?>>Answer <?php echo $option; ?></option><?php endforeach; ?></select></div>
                <?php endif; ?>
                <button type="submit" name="update_question" class="btn-submit" style="width:100%;">💾 Save Question</button>
            </form>
        <?php endif; ?>

        <form method="post" style="margin-bottom:24px;">
            <div class="input-group">
                <label for="question_text">Add Question</label>
                <textarea id="question_text" name="question_text" rows="4" placeholder="Enter a question for this assignment or quiz..." required></textarea>
            </div>
            <?php if ($is_quiz): ?>
                <div class="quiz-options-grid">
                    <?php foreach (['a', 'b', 'c', 'd'] as $option): ?><div class="input-group"><label>Answer <?php echo strtoupper($option); ?></label><input type="text" name="option_<?php echo $option; ?>" required placeholder="Enter answer <?php echo strtoupper($option); ?>"></div><?php endforeach; ?>
                </div>
                <div class="input-group"><label>Correct answer</label><select name="correct_option" required><option value="">Select correct answer</option><option value="A">Answer A</option><option value="B">Answer B</option><option value="C">Answer C</option><option value="D">Answer D</option></select></div>
            <?php endif; ?>
            <button type="submit" name="add_question" class="btn-submit" style="width:100%;">💾 Add Question</button>
        </form>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question & answers</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($questions && $questions->num_rows > 0): ?>
                        <?php $i = 1; while ($row = $questions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['question_text']); ?></strong><?php if ($is_quiz && !empty($row['option_a'])): ?><div class="teacher-mcq-preview"><span>A. <?php echo htmlspecialchars($row['option_a']); ?></span><span>B. <?php echo htmlspecialchars($row['option_b']); ?></span><span>C. <?php echo htmlspecialchars($row['option_c']); ?></span><span>D. <?php echo htmlspecialchars($row['option_d']); ?></span><strong>Correct: <?php echo htmlspecialchars($row['correct_option']); ?></strong></div><?php endif; ?></td>
                                <td>
                                    <a href="assessment_questions.php?id=<?php echo $assessment_id; ?>&department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>&edit_question=<?php echo (int)$row['id']; ?>" class="action-link action-edit">✏️ Edit</a>
                                    <a href="assessment_questions.php?id=<?php echo $assessment_id; ?>&department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>&delete_question=<?php echo (int)$row['id']; ?>" class="action-link action-delete" onclick="return confirm('Delete this question?');">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align:center; padding:20px; color:#64748b;">No questions added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
