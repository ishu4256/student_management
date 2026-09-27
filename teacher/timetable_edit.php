<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$slot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$department_name = $conn->query("SELECT name FROM departments WHERE id = '$selected_department'")->fetch_assoc()['name'] ?? 'Selected Department';
$class_name = $conn->query("SELECT class_name FROM classes WHERE id = '$selected_class'")->fetch_assoc()['class_name'] ?? 'Selected Class';

$columns_query = $conn->query("SHOW COLUMNS FROM timetable");
$columns = [];
while ($col = $columns_query->fetch_assoc()) {
    $columns[] = $col['Field'];
}

$slot = null;
if ($slot_id > 0) {
    $slot = $conn->query("SELECT * FROM timetable WHERE id = '$slot_id'")->fetch_assoc();
}

$subjects = $conn->query("SELECT id, subject_code, name FROM subjects WHERE departmentid = '$selected_department' ORDER BY subject_code ASC");

if (isset($_POST['update_timetable'])) {
    $subject_id = (int)$_POST['subject_id'];
    $day = $conn->real_escape_string($_POST['day']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $classroom = $conn->real_escape_string($_POST['classroom']);

    $updates = [
        "day = '$day'",
        "start_time = '$start_time'",
        "end_time = '$end_time'",
        "classroom = '$classroom'"
    ];

    if (in_array('subject_id', $columns)) {
        $updates[] = "subject_id = '$subject_id'";
    } elseif (in_array('subject_code', $columns)) {
        $subject_row = $conn->query("SELECT subject_code FROM subjects WHERE id = '$subject_id'")->fetch_assoc();
        $updates[] = "subject_code = '" . $conn->real_escape_string($subject_row['subject_code'] ?? '') . "'";
    } elseif (in_array('subject', $columns)) {
        $subject_row = $conn->query("SELECT name FROM subjects WHERE id = '$subject_id'")->fetch_assoc();
        $updates[] = "subject = '" . $conn->real_escape_string($subject_row['name'] ?? '') . "'";
    }

    if (in_array('class_id', $columns)) {
        $updates[] = "class_id = '$selected_class'";
    } elseif (in_array('class', $columns)) {
        $updates[] = "class = '$selected_class'";
    }

    $conn->query("UPDATE timetable SET " . implode(', ', $updates) . " WHERE id = '$slot_id'");
    header("Location: view_timetable.php?department_id=$selected_department&class_id=$selected_class");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Timetable</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
</head>
<body>
<div class="form-container" style="max-width: 720px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--shadow);">
    <div class="form-header">
        <div>
            <h2>✏️ Edit Timetable Slot</h2>
            <p class="subtitle">Department: <strong><?php echo htmlspecialchars($department_name); ?></strong> / Class: <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
        </div>
        <a href="view_timetable.php?department_id=<?php echo $selected_department; ?>&class_id=<?php echo $selected_class; ?>" class="btn-back">⬅ Back</a>
    </div>

    <?php if (!$slot): ?>
        <div class="error">Timetable slot not found.</div>
    <?php else: ?>
        <form method="post">
            <div class="form-row">
                <div class="input-group">
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required>
                        <option value="">-- Select Subject --</option>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo (int)$subject['id']; ?>" <?php echo (($slot['subject_id'] ?? $slot['subject_code'] ?? '') == $subject['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="day">Day</label>
                    <select id="day" name="day" required>
                        <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                            <option value="<?php echo $d; ?>" <?php echo ($slot['day'] == $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="input-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" id="start_time" name="start_time" value="<?php echo htmlspecialchars($slot['start_time'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label for="end_time">End Time</label>
                    <input type="time" id="end_time" name="end_time" value="<?php echo htmlspecialchars($slot['end_time'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="input-group">
                <label for="classroom">Classroom / Hall</label>
                <input type="text" id="classroom" name="classroom" value="<?php echo htmlspecialchars($slot['classroom'] ?? $slot['hall_no'] ?? $slot['room'] ?? ''); ?>" required>
            </div>

            <button type="submit" name="update_timetable" class="btn-submit" style="width:100%;">💾 Update Timetable Slot</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
