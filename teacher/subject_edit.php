<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$dept_id = $_SESSION['departmentid'] ?? 0;

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $res = $conn->query("SELECT * FROM subjects WHERE id='$id' AND departmentid='$dept_id'");
    $sub = $res->fetch_assoc();
}

if (isset($_POST['update_subject'])) {
    $id = $_POST['id'];
    $code = $conn->real_escape_string($_POST['subject_code']);
    $name = $conn->real_escape_string($_POST['name']);
    $credits = $conn->real_escape_string($_POST['credits']);

    $conn->query("UPDATE subjects SET subject_code='$code', name='$name', credits='$credits' WHERE id='$id' AND departmentid='$dept_id'");
    header("Location: view_subjects.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Edit Subject</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<div class="form-container" style="max-width:550px; margin:50px auto; padding:30px; background:#fff; border-radius:8px; border:1px solid var(--border-color);">
    <h3>✏️ Edit Subject</h3><br>
    <form method="post">
        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
        <div class="input-group"><label>Subject Code</label><input type="text" name="subject_code" value="<?php echo $sub['subject_code']; ?>" required></div>
        <div class="input-group"><label>Subject Name</label><input type="text" name="name" value="<?php echo $sub['name']; ?>" required></div>
        <div class="input-group"><label>Credits</label><input type="text" name="credits" value="<?php echo $sub['credits']; ?>"></div>
        <button type="submit" name="update_subject" class="btn-primary" style="width:100%;">💾 Update Subject</button>
    </form>
</div>
</body>
</html><?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$dept_id = $_SESSION['departmentid'] ?? 0;

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $res = $conn->query("SELECT * FROM subjects WHERE id='$id' AND departmentid='$dept_id'");
    $sub = $res->fetch_assoc();
}

if (isset($_POST['update_subject'])) {
    $id = $_POST['id'];
    $code = $conn->real_escape_string($_POST['subject_code']);
    $name = $conn->real_escape_string($_POST['name']);
    $credits = $conn->real_escape_string($_POST['credits']);

    $conn->query("UPDATE subjects SET subject_code='$code', name='$name', credits='$credits' WHERE id='$id' AND departmentid='$dept_id'");
    header("Location: view_subjects.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Edit Subject</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
<div class="form-container" style="max-width:550px; margin:50px auto; padding:30px; background:#fff; border-radius:8px; border:1px solid var(--border-color);">
    <h3>✏️ Edit Subject</h3><br>
    <form method="post">
        <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
        <div class="input-group"><label>Subject Code</label><input type="text" name="subject_code" value="<?php echo $sub['subject_code']; ?>" required></div>
        <div class="input-group"><label>Subject Name</label><input type="text" name="name" value="<?php echo $sub['name']; ?>" required></div>
        <div class="input-group"><label>Credits</label><input type="text" name="credits" value="<?php echo $sub['credits']; ?>"></div>
        <button type="submit" name="update_subject" class="btn-primary" style="width:100%;">💾 Update Subject</button>
    </form>
</div>
</body>
</html>