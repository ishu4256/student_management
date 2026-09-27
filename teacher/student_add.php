<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php"); exit();
}

$department_id = (int)($_GET['department_id'] ?? $_POST['department_id'] ?? $_SESSION['departmentid'] ?? 0);
$selected_class = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes = $department_id > 0 ? $conn->query("SELECT id, class_name FROM classes WHERE department_id = $department_id ORDER BY class_name ASC") : false;
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']); // 💡 ගුරුවරයා ඇතුළත් කරන Username එක
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $sex = $conn->real_escape_string($_POST['sex']);
    $date_of_birth = $conn->real_escape_string($_POST['date_of_birth']);
    $parents_name = $conn->real_escape_string($_POST['parents_name']);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $valid_class = $conn->query("SELECT id FROM classes WHERE id = $class_id AND department_id = $department_id");
    
    // Default password as 123456
    $password = password_hash('123456', PASSWORD_DEFAULT); 

    // Username හෝ Email දැනටමත් තිබේදැයි පරීක්ෂා කිරීම
    $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    
    if ($department_id <= 0 || $class_id <= 0 || !$valid_class || $valid_class->num_rows === 0) {
        $error = "❌ Please select a valid department and class.";
    } elseif ($check->num_rows > 0) {
        $error = "❌ Username or Email is already taken!";
    } else {
        // 1. Users Table එකට දැමීම
        $sql_user = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'student')";
        
        if ($conn->query($sql_user)) {
            $user_id = $conn->insert_id;
            
            // 2. Students Table එකට දැමීම
            $sql_student = "INSERT INTO students (userid, name, email, departmentid, class_id, phone, address, sex, date_of_birth, parents_name)
                            VALUES ('$user_id', '$name', '$email', '$department_id', '$class_id', '$phone', '$address', '$sex', '$date_of_birth', '$parents_name')";
            
            if ($conn->query($sql_student)) {
                $success = "✅ Student added successfully! (Username: $username, Password: 123456)";
            } else {
                $conn->query("DELETE FROM users WHERE id = '$user_id'");
                $error = "❌ Error saving student details.";
            }
        } else {
            $error = "❌ Error creating user account.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Student</title>
    <link rel="stylesheet" href="../css/style.css?v=3.1">
</head>
<body>

<div style="max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h2>➕ Register New Student</h2>
    
    <?php if($error): ?><div style="color:red; margin-bottom:15px;"><?php echo $error; ?></div><?php endif; ?>
    <?php if($success): ?><div style="color:green; margin-bottom:15px;"><?php echo $success; ?></div><?php endif; ?>

    <form method="post">
    <div class="input-group">
        <label>Department</label>
        <select name="department_id" required onchange="this.form.submit()" class="form-control">
            <option value="">-- Select Department --</option>
            <?php if ($departments): while ($department = $departments->fetch_assoc()): ?>
                <option value="<?php echo (int)$department['id']; ?>" <?php echo $department_id === (int)$department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option>
            <?php endwhile; endif; ?>
        </select>
    </div>

    <div class="input-group">
        <label>Class</label>
        <select name="class_id" required class="form-control">
            <option value="">-- Select Class --</option>
            <?php if ($classes): while ($class = $classes->fetch_assoc()): ?>
                <option value="<?php echo (int)$class['id']; ?>" <?php echo $selected_class === (int)$class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
            <?php endwhile; endif; ?>
        </select>
    </div>
    <label>Full Name</label>
    <input type="text" name="name" required style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Username (Set by Teacher)</label>
    <input type="text" name="username" required placeholder="e.g., student01" style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Email</label>
    <input type="email" name="email" required style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Phone</label>
    <input type="text" name="phone" style="width:100%; padding:10px; margin:5px 0;">

    <label>Sex</label>
    <select name="sex" required style="width:100%; padding:10px; margin:5px 0;">
        <option value="">-- Select Sex --</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select>

    <label>Date of Birth</label>
    <input type="date" name="date_of_birth" required style="width:100%; padding:10px; margin:5px 0;">

    <label>Parent Name</label>
    <input type="text" name="parents_name" required placeholder="Enter parent or guardian name" style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Address</label>
    <textarea name="address" style="width:100%; padding:10px; margin:5px 0;"></textarea>
    
    <p style="font-size: 12px; color: #666;">ℹ️ Default password will be <b>123456</b></p>
    
    <button type="submit" name="add_student" style="width:100%; padding:12px; background:#4f46e5; color:white; border:none; border-radius:6px; cursor:pointer;">Save Student</button>
</form>
    <br>
    <a href="view_students.php?department_id=<?php echo (int)$department_id; ?>&class_id=<?php echo $selected_class; ?>">⬅ Back to List</a>
</div>

</body>
</html>