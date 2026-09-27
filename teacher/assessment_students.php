<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

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
$assessment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$assessment = $conn->query("SELECT * FROM assessments WHERE id = '$assessment_id' AND department_id = '$selected_department' AND class_id = '$selected_class'")->fetch_assoc();
$students = $conn->query("SELECT s.id, s.name, s.email, s.phone, s.address FROM students s WHERE s.departmentid = '$selected_department'" . ($selected_class > 0 ? " AND s.class_id = '$selected_class'" : "") . " ORDER BY s.name ASC");

if (isset($_POST['assign_student'])) {
    $student_id = (int)$_POST['student_id'];
    if ($student_id > 0) {
        $conn->query("INSERT INTO assessment_students (assessment_id, student_id) VALUES ('$assessment_id', '$student_id') ON DUPLICATE KEY UPDATE student_id = student_id");
        header("Location: assessment_students.php?id=$assessment_id&department_id=$selected_department&class_id=$selected_class");
        exit();
    }
}

if (isset($_GET['remove_student'])) {
    $student_id = (int)$_GET['remove_student'];
    $conn->query("DELETE FROM assessment_students WHERE assessment_id = '$assessment_id' AND student_id = '$student_id'");
    header("Location: assessment_students.php?id=$assessment_id&department_id=$selected_department&class_id=$selected_class");
    exit();
}

$assigned_students = $conn->query("SELECT s.id, s.name, s.email, s.phone, s.address
    FROM assessment_students a
    JOIN students s ON s.id = a.student_id
    WHERE a.assessment_id = '$assessment_id' ORDER BY s.name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assessment Students</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>
<div class="form-container" style="max-width: 1100px; margin:50px auto; padding:30px; background:#fff; border-radius:16px; box-shadow:var(--shadow);">
    <div class="form-header">
        <div>
            <h2>👨‍🎓 Assigned Students</h2>
            <p class="subtitle">Assessment: <strong><?php echo htmlspecialchars($assessment['title'] ?? ''); ?></strong></p>
        </div>
        <a href="view_assessments.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!$assessment): ?>
        <div class="error">Assessment not found.</div>
    <?php else: ?>
        <form method="post" style="margin-bottom:24px;">
            <div class="form-row">
                <div class="input-group">
                    <label for="student_id">Assign Student</label>
                    <select id="student_id" name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <option value="<?php echo (int)$student['id']; ?>"><?php echo htmlspecialchars($student['name'] . ' - ' . $student['email']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="assign_student" class="btn-submit" style="width:100%;">💾 Assign Student</button>
        </form>

        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assigned_students && $assigned_students->num_rows > 0): ?>
                        <?php while ($row = $assigned_students->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td>
                                    <a href="assessment_students.php?id=<?php echo $assessment_id; ?>&department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>&remove_student=<?php echo (int)$row['id']; ?>" class="action-link action-delete" onclick="return confirm('Remove this student from the assessment?');">🗑️ Remove</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No students assigned to this assessment yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
