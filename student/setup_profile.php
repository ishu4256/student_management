<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: ශිෂ්‍යයෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

if (isset($_POST['update_profile'])) {
    $new_username = $conn->real_escape_string($_POST['new_username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "❌ Password must be at least 6 characters long!";
    } elseif ($new_password == '123456') {
        $error = "❌ You cannot use the default password! Please choose a new one.";
    } else {
        // Username එක දැනටමත් වෙනත් අයෙක් පාවිච්චි කරනවාදැයි බැලීම
        $check_user = $conn->query("SELECT id FROM users WHERE username='$new_username' AND id != '$user_id'");
        
        if ($check_user->num_rows > 0) {
            $error = "❌ This username is already taken! Try another one.";
        } else {
            // 💡 Database එකේ Users table එකේ දත්ත Update කිරීම
            // සටහන: ඔබේ සිස්ටම් එකේ password hash කරනවා නම් password_hash($new_password, PASSWORD_BCRYPT) භාවිතා කරන්න.
            $update = $conn->query("UPDATE users SET username='$new_username', password='$new_password' WHERE id='$user_id'");
            
            if ($update) {
                $success = "✅ Profile updated successfully! Redirecting to dashboard...";
                // තත්පර 2කින් Dashboard එකට යොමු කිරීම
                header("refresh:2; url=dashboard.php");
            } else {
                $error = "❌ Something went wrong. Please try again.";
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
    <title>Setup Your Account - Student</title>
    <link rel="stylesheet" href="../css/style.css?v=1.2">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .setup-box { max-width: 500px; margin: 60px auto; background: #fff; padding: 35px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .setup-title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 5px; }
        .setup-subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        .alert { padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
        .alert-danger { background: #fee2e2; color: #b91c1c; }
        .alert-success { background: #dcfce7; color: #15803d; }
    </style>
</head>
<body>

<div class="container">
    <div class="setup-box">
        <h2 class="setup-title">🔒 Setup Your Account</h2>
        <p class="setup-subtitle">This is your first login. Please choose a custom username and password for future logins.</p>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <label>New Username (e.g., kasun99)</label>
                <input type="text" name="new_username" required placeholder="Create a new username">
            </div>
            
            <div class="input-group">
                <label>New Password</label>
                <input type="password" name="new_password" required placeholder="Minimum 6 characters">
            </div>

            <div class="input-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Repeat your new password">
            </div>

            <button type="submit" name="update_profile" class="btn-primary" style="width: 100%; margin-top: 10px;">💾 Save & Continue</button>
        </form>
    </div>
</div>

</body>
</html>