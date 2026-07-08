<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: ශිෂ්‍යයෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ශිෂ්‍යයාගේ නම database එකෙන් ලබා ගැනීම (ලස්සනට Welcome කරන්න)
// ⚠️ සටහන: ඔබේ students table එකේ userid ලෙස column එකක් ඇති බව උපකල්පනය කර ඇත
$student_name = "Student";
$student_query = $conn->query("SELECT name FROM students WHERE userid = '$user_id'");
if($student_query && $student_query->num_rows > 0) {
    $student_name = $student_query->fetch_assoc()['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --border: #e2e8f0;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-dark);
            font-family: 'Segoe UI', system-ui, sans-serif;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Top Header Styling */
        .welcome-header {
            background: #ffffff;
            padding: 25px 35px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 35px;
        }

        .welcome-text h2 {
            margin: 0 0 5px 0;
            font-size: 26px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .welcome-text p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .btn-logout {
            background: #fee2e2;
            color: #ef4444;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        /* Navigation Cards Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
        }

        .menu-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 30px 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px -5px rgba(79, 70, 229, 0.15);
            border-color: #c7d2fe;
            background: #fafafa;
        }

        .card-icon {
            font-size: 40px;
            margin-bottom: 15px;
            background: #f0fdf4;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        /* එකිනෙකට වෙනස් වර්ණ අයිකන් පසුබිම් සඳහා */
        .menu-card:nth-child(1) .card-icon { background: #eff6ff; color: #3b82f6; } /* Profile */
        .menu-card:nth-child(2) .card-icon { background: #ecfdf5; color: #10b981; } /* Attendance */
        .menu-card:nth-child(3) .card-icon { background: #fef2f2; color: #ef4444; } /* Subjects */
        .menu-card:nth-child(4) .card-icon { background: #fdf4ff; color: #d946ef; } /* Timetable */

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .card-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <div class="welcome-header">
        <div class="welcome-text">
            <h2>🎓 Welcome Back, <?php echo htmlspecialchars($student_name); ?>!</h2>
            <p>Access your portal to manage your profile, view classes, and check attendance.</p>
        </div>
        <a href="../logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <div class="menu-grid">
        
        <a href="profile.php" class="menu-card">
            <div class="card-icon">👤</div>
            <div class="card-title">My Profile</div>
            <p class="card-desc">View and manage your personal details</p>
        </a>

        <a href="attendance.php" class="menu-card">
            <div class="card-icon">📝</div>
            <div class="card-title">View Attendance</div>
            <p class="card-desc">Check your overall attendance logs</p>
        </a>

        <a href="subjects.php" class="menu-card">
            <div class="card-icon">📚</div>
            <div class="card-title">My Subjects</div>
            <p class="card-desc">List of course modules in your batch</p>
        </a>

        <a href="timetable.php" class="menu-card">
            <div class="card-icon">📅</div>
            <div class="card-title">Class Timetable</div>
            <p class="card-desc">View weekly lectures schedule</p>
        </a>

    </div>

</div>

</body>
</html>