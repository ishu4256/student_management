<?php
include "config/db.php";

// 💡 Session එකක් දැනටමත් නැත්නම් පමණක් අලුතින් ආරම්භ කරයි
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // පරිශීලකයා සෙවීම
    $user_query = $conn->query("SELECT * FROM users WHERE username='$username'");

    if ($user_query->num_rows == 1) {
        $user = $user_query->fetch_assoc();
        
        // password_verify භාවිතා කර හැෂ් කළ පාස්වර්ඩ් පරීක්ෂා කිරීම
       // 💡 password_verify වෙනුවට සෘජුවම පාස්වර්ඩ් සමානදැයි බලයි (Plain-text check)
        if ($password === $user['password']) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // 1. ගුරුවරයා (Teacher)
            if ($user['role'] == 'teacher') {
                $teacher_query = $conn->query("SELECT departmentid FROM teachers WHERE userid='{$user['id']}'");
                $teacher_data = $teacher_query->fetch_assoc();
                $_SESSION['departmentid'] = $teacher_data['departmentid'] ?? 0;
                header("Location: teacher/dashboard.php");
                exit();
            } 
            // 2. ශිෂ්‍යයා (Student)
            else if ($user['role'] == 'student') {
                // මුල්වරට ලොග් වන්නේ නම් (Default password: 123456)
                if ($password == '123456') {
                    header("Location: student/setup_profile.php");
                } else {
                    header("Location: student/dashboard.php");
                }
                exit();
            } 
            // 3. පරිපාලක (Admin)
            else if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
                exit();
            }

        } else {
            $error = "❌ Invalid Password!";
        }
    } else {
        $error = "❌ User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Management System</title>
<!-- පැරණි ලින්ක් එක වෙනුවට මෙන්න මේ විදිහට අගට ?v=1.1 එකතු කරන්න -->
<link rel="stylesheet" href="css/style.css?v=1.1"></head>
<body>
<div class="container">
    <div class="login-box">
        <h2>Welcome Back</h2>
        <p class="subtitle">Please sign in to your account</p>

        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="btn-primary">Sign In</button>
            
            <div class="form-footer">
                <span>Don't have an account?</span>
                <a href="register.php" class="btn-secondary">Create New Account</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>