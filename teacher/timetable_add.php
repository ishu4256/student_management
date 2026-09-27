<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);

$teacher_column = $conn->query("SHOW COLUMNS FROM timetable LIKE 'teacher_user_id'");
if ($teacher_column && $teacher_column->num_rows === 0) {
    $conn->query("ALTER TABLE timetable ADD COLUMN teacher_user_id INT NULL AFTER id");
}

$columns_query = $conn->query("SHOW COLUMNS FROM timetable");
$columns = [];
while ($col = $columns_query->fetch_assoc()) {
    $columns[] = $col['Field'];
}

$department_name = $conn->query("SELECT name FROM departments WHERE id = '$selected_department'")->fetch_assoc()['name'] ?? 'Selected Department';
$class_name = $conn->query("SELECT class_name FROM classes WHERE id = '$selected_class'")->fetch_assoc()['class_name'] ?? 'Selected Class';
$subjects = $selected_class > 0 ? $conn->query("SELECT id, subject_code, name FROM subjects WHERE departmentid = '$selected_department' AND class_id = '$selected_class' ORDER BY subject_code ASC") : false;

$error_message = '';

if (isset($_POST['add_timetable'])) {
    $subject_id = (int)$_POST['subject_id'];
    $day = $conn->real_escape_string($_POST['day']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $classroom = $conn->real_escape_string($_POST['classroom']);

    $subject_check = $conn->query("SELECT id FROM subjects WHERE id = '$subject_id' AND departmentid = '$selected_department' AND class_id = '$selected_class'");

    if ($subject_check && $subject_check->num_rows > 0) {
        $insert_cols = ['day', 'start_time', 'end_time', 'classroom'];
        $insert_values = ["'$day'", "'$start_time'", "'$end_time'", "'$classroom'"];

        $insert_cols[] = 'teacher_user_id';
        $insert_values[] = "'$teacher_user_id'";

        if (in_array('subject_id', $columns)) {
            $insert_cols[] = 'subject_id';
            $insert_values[] = "'$subject_id'";
        } elseif (in_array('subject_code', $columns)) {
            $subject_row = $conn->query("SELECT subject_code FROM subjects WHERE id = '$subject_id'")->fetch_assoc();
            $insert_cols[] = 'subject_code';
            $insert_values[] = "'" . $conn->real_escape_string($subject_row['subject_code'] ?? '') . "'";
        } elseif (in_array('subject', $columns)) {
            $subject_row = $conn->query("SELECT name FROM subjects WHERE id = '$subject_id'")->fetch_assoc();
            $insert_cols[] = 'subject';
            $insert_values[] = "'" . $conn->real_escape_string($subject_row['name'] ?? '') . "'";
        }

        if (in_array('class_id', $columns)) {
            $insert_cols[] = 'class_id';
            $insert_values[] = "'$selected_class'";
        } elseif (in_array('class', $columns)) {
            $insert_cols[] = 'class';
            $insert_values[] = "'$selected_class'";
        }

        $sql = "INSERT INTO timetable (" . implode(', ', $insert_cols) . ") VALUES (" . implode(', ', $insert_values) . ")";
        $conn->query($sql);

        header("Location: view_timetable.php?department_id=$selected_department&class_id=$selected_class");
        exit();
    } else {
        $error_message = 'Please select a valid subject from the selected department.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Timetable</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>
<div class="form-container" style="max-width: 720px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
    <div class="form-header">
        <div>
            <h2>🗓️ Add Timetable Slot</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong> / Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
        </div>
        <a href="view_timetable.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="error" style="margin-bottom: 16px;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-row">
            <div class="input-group">
                <label for="subject_id">Subject</label>
                <select id="subject_id" name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php while ($subject = $subjects->fetch_assoc()): ?>
                        <option value="<?php echo (int)$subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="day">Day</label>
                <select id="day" name="day" required>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="input-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>
            <div class="input-group">
                <label for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>
        </div>

        <div class="input-group">
            <label for="classroom">Classroom / Hall</label>
            <input type="text" id="classroom" name="classroom" required placeholder="Room 101">
        </div>

        <button type="submit" name="add_timetable" class="btn-submit" style="width:100%;">💾 Save Timetable Slot</button>
    </form>
</div>
</body>
</html>
