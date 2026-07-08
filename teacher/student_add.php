<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php"); exit();
}

$department_id = $_SESSION['departmentid'] ?? 0;
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']); // 💡 ගුරුවරයා ඇතුළත් කරන Username එක
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    
    // Default password as 123456
    $password = password_hash('123456', PASSWORD_DEFAULT); 

    // Username හෝ Email දැනටමත් තිබේදැයි පරීක්ෂා කිරීම
    $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    
    if ($check->num_rows > 0) {
        $error = "❌ Username or Email is already taken!";
    } else {
        // 1. Users Table එකට දැමීම
        $sql_user = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'student')";
        
        if ($conn->query($sql_user)) {
            $user_id = $conn->insert_id;
            
            // 2. Students Table එකට දැමීම
            $sql_student = "INSERT INTO students (userid, name, email, departmentid, phone, address) 
                            VALUES ('$user_id', '$name', '$email', '$department_id', '$phone', '$address')";
            
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
    <link rel="stylesheet" href="../css/style.css?v=2.2">
</head>
<body>

<div style="max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h2>➕ Register New Student</h2>
    
    <?php if($error): ?><div style="color:red; margin-bottom:15px;"><?php echo $error; ?></div><?php endif; ?>
    <?php if($success): ?><div style="color:green; margin-bottom:15px;"><?php echo $success; ?></div><?php endif; ?>

    <form method="post">
    <label>Full Name</label>
    <input type="text" name="name" required style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Username (Set by Teacher)</label>
    <input type="text" name="username" required placeholder="e.g., student01" style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Email</label>
    <input type="email" name="email" required style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Phone</label>
    <input type="text" name="phone" style="width:100%; padding:10px; margin:5px 0;">
    
    <label>Address</label>
    <textarea name="address" style="width:100%; padding:10px; margin:5px 0;"></textarea>
    
    <p style="font-size: 12px; color: #666;">ℹ️ Default password will be <b>123456</b></p>
    
    <button type="submit" name="add_student" style="width:100%; padding:12px; background:#4f46e5; color:white; border:none; border-radius:6px; cursor:pointer;">Save Student</button>
</form>
    <br>
    <a href="view_students.php">⬅ Back to List</a>
</div>

</body>
</html>