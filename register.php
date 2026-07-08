<?php
include "config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$error = "";
$success = "";

// Form එකේ පෙන්වීමට Departments ලැයිස්තුව Database එකෙන් ලබා ගැනීම
$departments_query = $conn->query("SELECT * FROM departments ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $role = $conn->real_escape_string($_POST['role']);
    $name = $conn->real_escape_string($_POST['name']);
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $department_id = isset($_POST['departmentid']) ? (int)$_POST['departmentid'] : 0;

    // 1. Passwords සමානදැයි පරීක්ෂා කිරීම
    if ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } 
    // 2. Username හෝ Email එක දැනටමත් තිබේදැයි පරීක්ෂා කිරීම
    else {
        $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
        if ($check->num_rows > 0) {
            $error = "❌ Username or Email is already taken!";
        } else {
            // Password එක Hash කිරීම
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 3. Users Table එකට ඇතුළත් කිරීම
            $sql_user = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";

            if ($conn->query($sql_user)) {
                $user_id = $conn->insert_id;

                // 4. Role එක අනුව අදාළ පැතිකඩ (Profile Table) එකට ඇතුළත් කිරීම
                if ($role == 'student') {
                    $sql_profile = "INSERT INTO students (userid, name, email, departmentid, phone, address) 
                                    VALUES ('$user_id', '$name', '$email', '$department_id', '$phone', '$address')";
                } else if ($role == 'teacher') {
                    $sql_profile = "INSERT INTO teachers (userid, name, email, departmentid, phone, address) 
                                    VALUES ('$user_id', '$name', '$email', '$department_id', '$phone', '$address')";
                }

                if (isset($sql_profile) && $conn->query($sql_profile)) {
                    $success = "✅ Registration successful! <a href='login.php'>Click here to Login</a>";
                } else {
                    // Profile එක සෑදීම අසාර්ථක වුවහොත් සාදපු User account එක අයින් කිරීම (Rollback)
                    $conn->query("DELETE FROM users WHERE id = '$user_id'");
                    $error = "❌ Error saving profile details.";
                }
            } else {
                $error = "❌ Error creating user account.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Account</title>
    <link rel="stylesheet" href="css/style.css?v=1.3">
    <style>
        .reg-box { max-width: 550px; margin: 30px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #334155; }
        .input-group input, .input-group select, .input-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #4338ca; }
        .error { color: #b91c1c; background: #fef2f2; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .success { color: #166534; background: #dcfce7; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="reg-box">
    <h2>Create New Account</h2>
    <p style="color: #64748b; margin-bottom: 20px;">Fill in the form below to register your portal account</p>

    <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>

    <form method="post">
        <div class="input-group">
            <label>Select Role</label>
            <select name="role" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
            </select>
        </div>

        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="name" required placeholder="John Doe">
        </div>

        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required placeholder="johndoe123">
        </div>

        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="john@example.com">
        </div>

        <div class="input-group">
            <label>Department</label>
            <select name="departmentid" required>
                <option value="">-- Select Department --</option>
                <?php while($dept = $departments_query->fetch_assoc()): ?>
                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="input-group">
            <label>Phone Number</label>
            <input type="text" name="phone" placeholder="07XXXXXXXX">
        </div>

        <div class="input-group">
            <label>Residential Address</label>
            <textarea name="address" rows="3" placeholder="Enter your full address"></textarea>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Min 6 characters">
        </div>

        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required placeholder="Retype password">
        </div>

        <button type="submit" name="register" class="btn-submit">Register Account</button>
    </form>
    
    <div style="text-align: center; margin-top: 15px; font-size: 14px;">
        Already have an account? <a href="login.php" style="color: #4f46e5; text-decoration: none; font-weight: bold;">Sign In</a>
    </div>
</div>

</body>
</html>