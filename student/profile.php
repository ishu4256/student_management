<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: ශිෂ්‍යයෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// 💡 1. users table එකෙන් ලොග් වී සිටින කෙනාගේ මූලික විස්තර සොයා ගැනීම
$user_main_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user_data = $user_main_query->fetch_assoc();
$student_info = [];

// Student profile lookup supports accounts created with either matching user ID or email.
$account_email = $conn->real_escape_string($user_data['email'] ?? '');
$query = "SELECT s.*, d.name AS dept_name, c.class_name FROM students s
          LEFT JOIN departments d ON s.departmentid = d.id
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE s.userid = $user_id OR ('$account_email' <> '' AND s.email = '$account_email')
          ORDER BY (s.userid = $user_id) DESC LIMIT 1";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $student_info = $result->fetch_assoc();
    
    // දත්ත තිබේ නම් එකතු කර ගැනීම
    $display_name = !empty($student_info['name']) ? $student_info['name'] : $user_data['username'];
    $display_email = !empty($student_info['email']) ? $student_info['email'] : ($user_data['email'] ?? 'Not Provided');
    $display_dept = !empty($student_info['dept_name']) ? $student_info['dept_name'] : 'General Department';
    $display_class = !empty($student_info['class_name']) ? $student_info['class_name'] : 'Not Assigned';
} else {
    // ශිෂ්‍යයාගේ Extended Profile එකක් නැත්නම් users table එකේ දත්ත පෙන්වයි
    $display_name = $user_data['username'] . " (Student Account)";
    $display_email = $user_data['email'] ?? 'Not Available';
    $display_dept = 'Not Assigned';
    $display_class = 'Not Assigned';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../css/style.css?v=3.7">
    <style>
        :root {
            --bg-soft: #f8fafc;
            --text-dark: #1e293b;
            --border-soft: #e2e8f0;
            --primary-color: #4f46e5;
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-dark);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 40px;
            max-width: 850px;
            margin: 60px auto;
            border: 1px solid var(--border-soft);
        }

        .profile-header {
            text-align: center;
            border-bottom: 2px solid var(--bg-soft);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .avatar {
            width: 80px;
            height: 80px;
            background: #e0e7ff;
            color: var(--primary-color);
            font-size: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px auto;
        }

        .profile-title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .info-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--bg-soft);
        }

        .profile-info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .profile-info-grid .info-group { margin: 0; padding: 16px; border: 1px solid var(--border-soft); border-radius: 10px; background: #fff; }
        .profile-info-grid .full-span { grid-column: 1 / -1; }
        .profile-badge { display: inline-flex; align-items: center; width: fit-content; padding: 5px 10px; border-radius: 999px; background: #e0e7ff; color: var(--primary-color); font-size: 13px; }
        .profile-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 24px; }

        .info-group:last-of-type {
            border-bottom: none;
        }

        .info-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            color: #0f172a;
            font-weight: 600;
        }

        .btn-back-modern {
            display: block;
            text-align: center;
            background: #ffffff;
            color: #475569;
            border: 1px solid var(--border-soft);
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-top: 25px;
        }

        .btn-back-modern:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateY(-1px);
        }

        @media (max-width: 650px) {
            .profile-card { margin: 20px 15px; padding: 22px; }
            .profile-info-grid { grid-template-columns: 1fr; }
            .profile-info-grid .full-span { grid-column: auto; }
            .profile-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="profile-card">
    <div class="profile-header">
        <div class="avatar">👤</div>
        <h2 class="profile-title">My Profile Details</h2>
    </div>

    <div class="profile-info-grid">
        <div class="info-group"><div class="info-label">Full Name</div><div class="info-value"><?php echo htmlspecialchars($display_name); ?></div></div>
        <div class="info-group"><div class="info-label">Username</div><div class="info-value" style="color: var(--primary-color);"><?php echo htmlspecialchars($user_data['username'] ?? '-'); ?></div></div>
        <div class="info-group"><div class="info-label">Email</div><div class="info-value"><?php echo htmlspecialchars($display_email); ?></div></div>
        <div class="info-group"><div class="info-label">Phone</div><div class="info-value"><?php echo htmlspecialchars($student_info['phone'] ?? 'Not Provided'); ?></div></div>
        <div class="info-group"><div class="info-label">Department</div><div class="info-value"><span class="profile-badge">🏢 <?php echo htmlspecialchars($display_dept); ?></span></div></div>
        <div class="info-group"><div class="info-label">Class</div><div class="info-value"><span class="profile-badge">🎓 <?php echo htmlspecialchars($display_class); ?></span></div></div>
        <div class="info-group"><div class="info-label">Sex</div><div class="info-value"><?php echo htmlspecialchars($student_info['sex'] ?? 'Not Provided'); ?></div></div>
        <div class="info-group"><div class="info-label">Date of Birth</div><div class="info-value"><?php echo htmlspecialchars($student_info['date_of_birth'] ?? 'Not Provided'); ?></div></div>
        <div class="info-group"><div class="info-label">Parent / Guardian</div><div class="info-value"><?php echo htmlspecialchars($student_info['parents_name'] ?? 'Not Provided'); ?></div></div>
        <div class="info-group full-span"><div class="info-label">Address</div><div class="info-value"><?php echo htmlspecialchars($student_info['address'] ?? 'Not Provided'); ?></div></div>
    </div>

    <div class="profile-actions">
        <a href="profile_edit.php" class="btn-primary" style="text-decoration:none; text-align:center;">Edit Details</a>
        <a href="class_enrollment.php" class="btn-primary" style="text-decoration:none; text-align:center;">🎓 Manage Class</a>
        <a href="dashboard.php" class="btn-back-modern">⬅ Back to Dashboard</a>
    </div>
</div>

</body>
</html>