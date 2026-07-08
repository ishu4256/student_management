<?php
include "config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: ඇඩ්මින් කෙනෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$error = "";
$success = "";

// 💡 සිස්ටම් එකේ ඇති සියලුම දෙපාර්තමේන්තු ලැයිස්තුව ලබා ගැනීම
$departments_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    // Form එකෙන් එන දත්ත ලබා ගැනීම
    $student_name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $username = $conn->real_escape_string($_POST['username']); // 💡 ඇඩ්මින් දෙන Username එක
    $department_id = $conn->real_escape_string($_POST['department_id']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $sex=$conn->real_escape_string($_POST['sex']);
    $date_of_birth=$conn->real_escape_string($_POST['date_of_birth']);
    $parents_name=$conn->real_escape_string($_POST['parents_name']);
    // Default Password
    $password = '123456'; 

    // Username එක හෝ Email එක දැනටමත් පාවිච්චි කර ඇත්දැයි බැලීම
    $check_user = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    
    if ($check_user->num_rows > 0) {
        $error = "❌ Username or Email address is already taken!";
    } else if (empty($department_id)) {
        $error = "❌ Please select a valid department!";
    } else {
        // ➡️ පියවර 1: users table එකට ඇතුළත් කිරීම
        $user_query = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', 'student')";
        
        if ($conn->query($user_query)) {
            $new_user_id = $conn->insert_id; // අලුතින් හැදුණු user ID එක ගැනීම

            // ➡️ පියවර 2: students table එකට ඇතුළත් කිරීම
            $student_query = "INSERT INTO students (userid, name, email, departmentid, phone, address, sex, date_of_birth, parents_name) 
                              VALUES ('$new_user_id', '$student_name', '$email', '$department_id', '$phone', '$address', '$sex', '$date_of_birth', '$parents_name'  )";
            
            if ($conn->query($student_query)) {
                $success = "✅ Student registered successfully! (Default Password: 123456)";
            } else {
                // Foreign Key Error එකක් ආවොත් Rollback කිරීම
                $conn->query("DELETE FROM users WHERE id = '$new_user_id'");
                $error = "❌ Error adding to students table: " . $conn->error;
            }
        } else {
            $error = "❌ Error creating user login: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Student</title>
    <link rel="stylesheet" href="../css/style.css?v=2.6">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .admin-card { max-width: 600px; margin: 50px auto; background: #fff; padding: 35px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; }
        .alert { padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
        .alert-danger { background: #fee2e2; color: #b91c1c; }
        .alert-success { background: #dcfce7; color: #15803d; }
        .info-box { background: #f0fdf4; color: #166534; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 25px; border-left: 4px solid #22c55e; }
        
        .form-control {
            width: 100%; padding: 11px; margin-top: 6px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; box-sizing: border-box; outline: none; transition: border 0.2s;
        }
        .form-control:focus { border-color: #4f46e5; }
        .input-group { margin-bottom: 18px; }
        .label-text { font-weight: 600; font-size: 13px; color: #475569; }
    </style>
</head>
<body>

<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px;">
        <h3 class="title">🛡️ Admin: Add New Student</h3>
        <a href="admin/dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if(!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="info-box">
        🔑 <strong>Admin Customization:</strong> You can define a custom username for this student. The login password defaults to <code>123456</code>.
    </div>

    <form method="post">
        <div class="input-group">
            <label class="label-text">Student Full Name</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g., Nimal Silva">
        </div>

        <div class="input-group">
            <label class="label-text">Custom Username</label>
            <input type="text" name="username" class="form-control" required placeholder="e.g., nimal99">
        </div>
        
        <div class="input-group">
            <label class="label-text">Email Address</label>
            <input type="email" name="email" class="form-control" required placeholder="e.g., nimal@example.com">
        </div>
<div class="input-group">
        <label class="label-text">Phone Number</label>
        <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX">
    </div>

    <div class="input-group">
        <label class="label-text">Address</label>
        <textarea name="address" class="form-control" rows="3" placeholder="Enter full address"></textarea>
    </div>
      <div class="input-group">
            <label class="label-text">Sex</label>
            <select name="sex" class="form-control" required>
                <option value="">-- Select Sex --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
        </div>
        <div class="input-group">
            <label class="label-text">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" required>
        </div>
        <div class="input-group">
            <label class="label-text">Parents' Name</label>
            <input type="text" name="parents_name" class="form-control" required placeholder="Enter parents' full name">
        </div>
        <div class="input-group">
            <label class="label-text">Assign Department</label>
            <select name="department_id" class="form-control" required>
                <option value="">-- Choose Student's Department --</option>
                <?php if ($departments_query && $departments_query->num_rows > 0): ?>
                    <?php while ($dept = $departments_query->fetch_assoc()): ?>
                        <option value="<?php echo $dept['id']; ?>">
                            🏢 <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="" disabled>No Departments Found! Create one first.</option>
                <?php endif; ?>
            </select>
        </div>

        <div style="margin-top: 25px;">
            <button type="submit" name="add_student" class="btn-primary" style="width: 100%; padding: 12px; font-weight: 700; background:#4f46e5; color:white; border:none; border-radius:8px; cursor:pointer; font-size:15px;">💾 Register Student</button>
        </div>
    </form>
</div>

</body>
</html>