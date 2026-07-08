<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: ශිෂ්‍යයෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 💡 1. users table එකෙන් ලොග් වී සිටින කෙනාගේ මූලික විස්තර සොයා ගැනීම
$user_main_query = $conn->query("SELECT * FROM users WHERE id = '$user_id'");
$user_data = $user_main_query->fetch_assoc();

// 💡 2. ශිෂ්‍යයාගේ විස්තර සහ දෙපාර්තමේන්තුව සෙවීම
$query = "SELECT s.*, d.name AS dept_name FROM students s 
          LEFT JOIN departments d ON s.departmentid = d.id 
          WHERE s.userid = '$user_id' OR s.id = '$user_id'";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $student_info = $result->fetch_assoc();
    
    // දත්ත තිබේ නම් එකතු කර ගැනීම
    $display_name = !empty($student_info['name']) ? $student_info['name'] : $user_data['username'];
    $display_email = !empty($student_info['email']) ? $student_info['email'] : ($user_data['email'] ?? 'Not Provided');
    $display_dept = !empty($student_info['dept_name']) ? $student_info['dept_name'] : 'General Department';
} else {
    // ශිෂ්‍යයාගේ Extended Profile එකක් නැත්නම් users table එකේ දත්ත පෙන්වයි
    $display_name = $user_data['username'] . " (Student Account)";
    $display_email = $user_data['email'] ?? 'Not Available';
    $display_dept = 'Not Assigned';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
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
            max-width: 600px;
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
    </style>
</head>
<body>

<div class="profile-card">
    <div class="profile-header">
        <div class="avatar">👤</div>
        <h2 class="profile-title">My Profile Details</h2>
    </div>

    <div class="info-group">
        <div class="info-label">Full Name</div>
        <div class="info-value"><?php echo htmlspecialchars($display_name); ?></div>
    </div>

    <div class="info-group">
        <div class="info-label">Active Username</div>
        <div class="info-value" style="color: var(--primary-color);">
            <?php echo htmlspecialchars($user_data['username']); ?>
        </div>
    </div>

    <div class="info-group">
        <div class="info-label">Login Email</div>
        <div class="info-value">
            <?php echo htmlspecialchars($display_email); ?>
        </div>
    </div>

    <div class="info-group">
        <div class="info-label">Assigned Department</div>
        <div class="info-value">
            <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 14px;">
                🏢 <?php echo htmlspecialchars($display_dept); ?>
            </span>
        </div>
    </div>

    <a href="dashboard.php" class="btn-back-modern">⬅ Back to Dashboard</a>
</div>

</body>
</html>