<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') { header("Location: ../login.php"); exit(); }
$dept_id = $_SESSION['departmentid'] ?? 0;

if (isset($_POST['add_subject'])) {
    $code = $conn->real_escape_string($_POST['subject_code']);
    $name = $conn->real_escape_string($_POST['name']);
    $credits = $conn->real_escape_string($_POST['credits']);

    if (!empty($code) && !empty($name)) {
        $conn->query("INSERT INTO subjects (subject_code, name, credits, departmentid) VALUES ('$code', '$name', '$credits', '$dept_id')");
        header("Location: view_subjects.php"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Add Subject</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<div class="form-container" style="max-width:550px; margin:50px auto; padding:30px; background:#fff; border-radius:8px; border:1px solid var(--border-color);">
    <h3>➕ Add New Subject</h3><br>
    <form method="post">
        <div class="input-group"><label>Subject Code</label><input type="text" name="subject_code" required placeholder="SENG 11223"></div>
        <div class="input-group"><label>Subject Name</label><input type="text" name="name" required placeholder="Database Systems"></div>
        <div class="input-group"><label>Credits</label><input type="text" name="credits" value="3"></div>
        <button type="submit" name="add_subject" class="btn-primary" style="width:100%;">💾 Save Subject</button>
    </form>
</div>
</body>
</html>