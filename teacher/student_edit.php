<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$dept_id = $_SESSION['departmentid'] ?? 0;
$teacher_user_id = (int)($_SESSION['user_id'] ?? 0);
$class_id = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
$student = null;

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    // ආරක්ෂාව සඳහා තමන්ගේ දෙපාර්තමේන්තුවේ ශිෂ්‍යයෙක්දැයි පමණක් පරීක්ෂා කරයි
    $res = $conn->query("SELECT s.* FROM students s INNER JOIN teacher_class_assignments a ON a.class_id = s.class_id AND a.department_id = s.departmentid WHERE s.id='$id' AND s.departmentid='$dept_id' AND s.class_id='$class_id' AND a.teacher_user_id = $teacher_user_id");
    if ($res->num_rows == 1) {
        $student = $res->fetch_assoc();
    } else {
        header("Location: view_students.php");
        exit();
    }
}

if (isset($_POST['update_student'])) {
    $id = $_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);

    $conn->query("UPDATE students SET name='$name', email='$email', phone='$phone', address='$address' WHERE id='$id' AND departmentid='$dept_id' AND class_id='$class_id' AND EXISTS (SELECT 1 FROM teacher_class_assignments a WHERE a.teacher_user_id = $teacher_user_id AND a.class_id = students.class_id)");
    header("Location: view_students.php?department_id=$dept_id&class_id=$class_id&success=updated");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="../css/style.css?v=2.2">
</head>
<body>

<div class="form-container" style="max-width: 650px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <h3>✏️ Edit Student Profile</h3>
        <a href="view_students.php?department_id=<?php echo $dept_id; ?>&class_id=<?php echo $class_id; ?>" class="btn-back">⬅ Back</a>
    </div>

    <form method="post">
        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
        <div class="input-group">
            <label style="font-weight:600; font-size:13px;">Full Name</label>
            <input type="text" name="name" value="<?php echo $student['name']; ?>" required>
        </div>
        <div class="input-group">
            <label style="font-weight:600; font-size:13px;">Email Address</label>
            <input type="email" name="email" value="<?php echo $student['email']; ?>" required>
        </div>
        <div class="input-group">
            <label style="font-weight:600; font-size:13px;">Phone Number</label>
            <input type="text" name="phone" value="<?php echo $student['phone']; ?>">
        </div>
        <div class="input-group">
            <label style="font-weight:600; font-size:13px;">Address</label>
            <textarea name="address" rows="3"><?php echo $student['address']; ?></textarea>
        </div>
        <button type="submit" name="update_student" class="btn-primary" style="width: 100%; padding: 12px; font-weight: 600;">💾 Save Changes</button>
    </form>
</div>

</body>
</html>