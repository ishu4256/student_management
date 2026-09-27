<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$account = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$account_email = $conn->real_escape_string($account['email'] ?? '');
$student = $conn->query("SELECT id, name, departmentid, class_id FROM students WHERE userid = $user_id OR ('$account_email' <> '' AND email = '$account_email') ORDER BY (userid = $user_id) DESC LIMIT 1")->fetch_assoc();
$student_id = (int)($student['id'] ?? 0);
$student_department = (int)($student['departmentid'] ?? 0);
$student_class = (int)($student['class_id'] ?? 0);

$conn->query("CREATE TABLE IF NOT EXISTS assessment_questions (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, question_text TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (assessment_id))");
$conn->query("CREATE TABLE IF NOT EXISTS assessment_students (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, student_id INT NOT NULL, assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE (assessment_id, student_id), INDEX (assessment_id), INDEX (student_id))");
$conn->query("CREATE TABLE IF NOT EXISTS assessment_answers (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, question_id INT NOT NULL, student_id INT NOT NULL, selected_option CHAR(1) NOT NULL, is_correct TINYINT(1) NOT NULL DEFAULT 0, answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY student_question (assessment_id, question_id, student_id), INDEX (student_id))");
$conn->query("CREATE TABLE IF NOT EXISTS assessment_attempts (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, student_id INT NOT NULL, score INT NOT NULL DEFAULT 0, total_questions INT NOT NULL DEFAULT 0, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY one_attempt (assessment_id, student_id), INDEX (student_id))");
$due_time_column = $conn->query("SHOW COLUMNS FROM assessments LIKE 'due_time'");
if ($due_time_column && $due_time_column->num_rows === 0) { $conn->query("ALTER TABLE assessments ADD COLUMN due_time TIME NULL AFTER due_date"); }

$question_columns = ['option_a' => 'VARCHAR(255) NULL', 'option_b' => 'VARCHAR(255) NULL', 'option_c' => 'VARCHAR(255) NULL', 'option_d' => 'VARCHAR(255) NULL', 'correct_option' => 'CHAR(1) NULL'];
foreach ($question_columns as $column => $definition) {
    $column_check = $conn->query("SHOW COLUMNS FROM assessment_questions LIKE '$column'");
    if ($column_check && $column_check->num_rows === 0) { $conn->query("ALTER TABLE assessment_questions ADD COLUMN $column $definition"); }
}

$quiz_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $assessment_id = (int)($_POST['assessment_id'] ?? 0);
    $assessment_check = $conn->query("SELECT id, assessment_type, department_id, class_id FROM assessments WHERE id = $assessment_id AND department_id = $student_department AND class_id = $student_class");
    $assessment_row = $assessment_check ? $assessment_check->fetch_assoc() : null;
    $attempt_check = $conn->query("SELECT id FROM assessment_attempts WHERE assessment_id = $assessment_id AND student_id = $student_id");
    $deadline_check = $conn->query("SELECT due_date, due_time FROM assessments WHERE id = $assessment_id")->fetch_assoc();
    $deadline = !empty($deadline_check['due_date']) ? $deadline_check['due_date'] . ' ' . (!empty($deadline_check['due_time']) ? $deadline_check['due_time'] : '23:59:59') : null;
    if (!$assessment_row || $assessment_row['assessment_type'] !== 'quiz') {
        $quiz_message = 'This quiz is not available for your current class.';
    } elseif ($attempt_check && $attempt_check->num_rows > 0) {
        $quiz_message = 'You have already submitted this quiz. Only one attempt is allowed.';
    } elseif ($deadline && strtotime($deadline) < time()) {
        $quiz_message = 'This quiz is closed because the due date and time have passed.';
    } else {
        $answers = $_POST['answers'] ?? [];
        $score = 0;
        $total = 0;
        $question_result = $conn->query("SELECT id, correct_option FROM assessment_questions WHERE assessment_id = $assessment_id");
        while ($question = $question_result->fetch_assoc()) {
            $total++;
            $question_id = (int)$question['id'];
            $selected = strtoupper($answers[$question_id] ?? '');
            $correct = $selected !== '' && $selected === strtoupper($question['correct_option'] ?? '') ? 1 : 0;
            $score += $correct;
            if (in_array($selected, ['A', 'B', 'C', 'D'], true)) {
                $selected_sql = $conn->real_escape_string($selected);
                $conn->query("INSERT INTO assessment_answers (assessment_id, question_id, student_id, selected_option, is_correct) VALUES ($assessment_id, $question_id, $student_id, '$selected_sql', $correct) ON DUPLICATE KEY UPDATE selected_option='$selected_sql', is_correct=$correct, answered_at=CURRENT_TIMESTAMP");
            }
        }
        $conn->query("INSERT INTO assessment_attempts (assessment_id, student_id, score, total_questions) VALUES ($assessment_id, $student_id, $score, $total)");
        $quiz_message = "Quiz submitted. Your score: $score / $total";
    }
}

$assessments = $conn->query("SELECT DISTINCT a.*, d.name AS department_name, c.class_name
    FROM assessments a
    LEFT JOIN assessment_students assigned ON assigned.assessment_id = a.id AND assigned.student_id = $student_id
    LEFT JOIN departments d ON d.id = a.department_id
    LEFT JOIN classes c ON c.id = a.class_id
    WHERE (assigned.student_id IS NOT NULL OR (a.department_id = $student_department AND a.class_id = $student_class))
    AND (a.assessment_type <> 'quiz' OR NOT EXISTS (SELECT 1 FROM assessment_attempts submitted WHERE submitted.assessment_id = a.id AND submitted.student_id = $student_id))
    AND (a.assessment_type <> 'quiz' OR a.due_date IS NULL OR STR_TO_DATE(CONCAT(a.due_date, ' ', COALESCE(NULLIF(a.due_time, ''), '23:59:59')), '%Y-%m-%d %H:%i:%s') >= NOW())
    ORDER BY (a.due_date IS NULL), a.due_date ASC, a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assessments</title>
    <link rel="stylesheet" href="../css/style.css?v=3.2">
</head>
<body>
<div class="dashboard student-assessments-page">
    <div class="dashboard-header">
        <div><span class="eyebrow">LEARNING PORTAL</span><h2>My Assessments</h2><p class="subtitle">View assignments, quizzes, papers and questions assigned to you.</p></div>
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
    <?php if ($quiz_message !== ''): ?><div class="success"><?php echo htmlspecialchars($quiz_message); ?></div><?php endif; ?>
    <div class="student-assessment-list">
        <?php if ($assessments && $assessments->num_rows > 0): while ($assessment = $assessments->fetch_assoc()): ?>
            <?php $questions = $conn->query("SELECT * FROM assessment_questions WHERE assessment_id = " . (int)$assessment['id'] . " ORDER BY id ASC"); $is_overdue = !empty($assessment['due_date']) && (($assessment['due_date'] . ' ' . (!empty($assessment['due_time']) ? $assessment['due_time'] : '23:59:59')) < date('Y-m-d H:i:s')); ?>
            <article class="student-assessment-card">
                <div class="student-assessment-heading">
                    <div><span class="pill"><?php echo htmlspecialchars(ucfirst($assessment['assessment_type'])); ?></span><h3><?php echo htmlspecialchars($assessment['title']); ?></h3><p><?php echo htmlspecialchars($assessment['department_name'] ?? '-'); ?> · <?php echo htmlspecialchars($assessment['class_name'] ?? '-'); ?></p></div>
                    <span class="assessment-status <?php echo $is_overdue ? 'status-overdue' : 'status-upcoming'; ?>"><?php echo $is_overdue ? 'Overdue' : 'Active'; ?></span>
                </div>
                <?php if (!empty($assessment['description'])): ?><p class="assessment-description"><?php echo nl2br(htmlspecialchars($assessment['description'])); ?></p><?php endif; ?>
                <div class="student-assessment-meta"><span>Due: <strong><?php echo htmlspecialchars(($assessment['due_date'] ?? 'No due date') . (!empty($assessment['due_time']) ? ' ' . $assessment['due_time'] : '')); ?></strong></span><?php if (!empty($assessment['document_path'])): ?><a href="../<?php echo htmlspecialchars($assessment['document_path']); ?>" target="_blank" rel="noopener" class="btn-export">Download Word document</a><?php endif; ?></div>
                <?php if ($questions && $questions->num_rows > 0): ?>
                    <?php if ($assessment['assessment_type'] === 'quiz'): ?><form method="post" class="student-quiz-form"><input type="hidden" name="assessment_id" value="<?php echo (int)$assessment['id']; ?>"><?php endif; ?>
                    <div class="assessment-question-list"><h4><?php echo $assessment['assessment_type'] === 'quiz' ? 'Quiz questions' : 'Questions'; ?></h4><ol><?php while ($question = $questions->fetch_assoc()): ?><li><strong><?php echo htmlspecialchars($question['question_text']); ?></strong><?php if ($assessment['assessment_type'] === 'quiz' && !empty($question['option_a'])): ?><div class="student-mcq-options student-mcq-inputs"><label><input type="radio" name="answers[<?php echo (int)$question['id']; ?>]" value="A" required> A. <?php echo htmlspecialchars($question['option_a']); ?></label><label><input type="radio" name="answers[<?php echo (int)$question['id']; ?>]" value="B"> B. <?php echo htmlspecialchars($question['option_b']); ?></label><label><input type="radio" name="answers[<?php echo (int)$question['id']; ?>]" value="C"> C. <?php echo htmlspecialchars($question['option_c']); ?></label><label><input type="radio" name="answers[<?php echo (int)$question['id']; ?>]" value="D"> D. <?php echo htmlspecialchars($question['option_d']); ?></label></div><?php elseif (!empty($question['option_a'])): ?><div class="student-mcq-options"><span>A. <?php echo htmlspecialchars($question['option_a']); ?></span><span>B. <?php echo htmlspecialchars($question['option_b']); ?></span><span>C. <?php echo htmlspecialchars($question['option_c']); ?></span><span>D. <?php echo htmlspecialchars($question['option_d']); ?></span></div><?php endif; ?></li><?php endwhile; ?></ol></div>
                    <?php if ($assessment['assessment_type'] === 'quiz'): ?><button type="submit" name="submit_quiz" class="btn-primary quiz-submit-button">Submit quiz</button></form><?php endif; ?>
                <?php endif; ?>
            </article>
        <?php endwhile; else: ?>
            <div class="student-assessment-card empty-state">No assessments have been assigned to you yet.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
