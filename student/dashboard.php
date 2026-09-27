<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: ශිෂ්‍යයෙක්දැයි පරීක්ෂා කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ශිෂ්‍යයාගේ නම database එකෙන් ලබා ගැනීම (ලස්සනට Welcome කරන්න)
// ⚠️ සටහන: ඔබේ students table එකේ userid ලෙස column එකක් ඇති බව උපකල්පනය කර ඇත
$student_name = "Student";
$student = null;
$account_user = $conn->query("SELECT email FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$account_email = $conn->real_escape_string($account_user['email'] ?? '');
$student_query = $conn->query("SELECT s.*, d.name AS department_name, c.class_name
    FROM students s
    LEFT JOIN departments d ON d.id = s.departmentid
    LEFT JOIN classes c ON c.id = s.class_id
    WHERE s.userid = $user_id OR ('$account_email' <> '' AND s.email = '$account_email')
    ORDER BY (s.userid = $user_id) DESC LIMIT 1");
if($student_query && $student_query->num_rows > 0) {
    $student = $student_query->fetch_assoc();
    $student_name = $student['name'];
}
$student_id = (int)($student['id'] ?? 0);
$department_name = $student['department_name'] ?? 'Not assigned';
$class_name = $student['class_name'] ?? 'Not assigned';
$conn->query("CREATE TABLE IF NOT EXISTS assessment_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (assessment_id, student_id),
    INDEX (assessment_id),
    INDEX (student_id)
)");
$attendance = $conn->query("SELECT COUNT(*) AS total_days,
    SUM(CASE WHEN LOWER(status) IN ('present', 'p') THEN 1 ELSE 0 END) AS present_days,
    SUM(CASE WHEN LOWER(status) IN ('absent', 'a') THEN 1 ELSE 0 END) AS absent_days
    FROM attendance WHERE student_id = $student_id")->fetch_assoc();
$total_days = (int)($attendance['total_days'] ?? 0);
$present_days = (int)($attendance['present_days'] ?? 0);
$absent_days = (int)($attendance['absent_days'] ?? 0);
$attendance_rate = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;
$student_subjects = [];
$subject_result = $conn->query("SELECT subject_code, name FROM subjects WHERE departmentid = " . (int)($student['departmentid'] ?? 0) . " AND class_id = " . (int)($student['class_id'] ?? 0) . " ORDER BY subject_code ASC, name ASC");
if ($subject_result) {
    while ($subject = $subject_result->fetch_assoc()) {
        $student_subjects[] = $subject;
    }
}
$subject_count = count($student_subjects);
$assessment_count = $conn->query("SELECT COUNT(*) AS total FROM assessment_students WHERE student_id = $student_id")->fetch_assoc()['total'] ?? 0;
$next_assessment = $conn->query("SELECT a.title, a.assessment_type, a.description, a.due_date, a.due_time FROM assessments a LEFT JOIN assessment_students assigned ON assigned.assessment_id = a.id AND assigned.student_id = $student_id WHERE (assigned.student_id IS NOT NULL OR (a.department_id = " . (int)($student['departmentid'] ?? 0) . " AND a.class_id = " . (int)($student['class_id'] ?? 0) . ")) AND (a.due_date IS NULL OR a.due_date >= CURDATE()) ORDER BY a.due_date IS NULL, a.due_date ASC LIMIT 1")->fetch_assoc();
$today_label = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../css/style.css?v=3.4">
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

        .student-home .eyebrow {
            display: block;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            margin-bottom: 8px;
        }

        .student-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 34px;
        }

        .student-summary-card {
            min-height: 126px;
            padding: 19px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);
        }

        .student-summary-card > span { display: block; color: var(--text-muted); font-size: 12px; margin-bottom: 10px; }
        .student-summary-card strong { display: block; color: var(--text-dark); font-size: 25px; overflow-wrap: anywhere; }
        .student-summary-card small { display: block; color: var(--text-muted); font-size: 12px; margin-top: 7px; }
        .summary-attendance { border-top: 4px solid #10b981; }
        .summary-attendance strong { color: #059669; }
        .summary-next { border-top: 4px solid var(--primary); }
        .summary-next strong { font-size: 17px; }
        .summary-assessment-details { margin: 9px 0 0; color: var(--text-muted); font-size: 12px; line-height: 1.4; }
        .summary-details-link { display: inline-block; margin-top: 9px; color: var(--primary); font-size: 12px; font-weight: 700; text-decoration: none; }
        .subjects-summary-card strong { margin-bottom: 7px; }
        .dashboard-subject-list { display: flex; flex-direction: column; gap: 4px; max-height: 78px; overflow-y: auto; }
        .dashboard-subject-list span { color: var(--text-dark); font-size: 12px; line-height: 1.35; }
        .student-summary-card .progress-track { margin-top: 14px; height: 6px; }
        .student-summary-card .progress-track span { background: linear-gradient(90deg, #10b981, #34d399); }
        .student-section-heading { margin-bottom: 14px; }
        .student-section-heading h3 { margin: 0 0 4px; font-size: 19px; }
        .student-section-heading p { margin: 0; color: var(--text-muted); font-size: 13px; }

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

        @media (max-width: 900px) {
            .student-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 600px) {
            .dashboard-container { margin: 20px auto !important; }
            .welcome-header { padding: 22px; margin-bottom: 22px; }
            .student-summary-grid { grid-template-columns: 1fr; gap: 10px; margin-bottom: 26px; }
            .welcome-header .btn-logout { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="dashboard-container student-home">

    <div class="welcome-header">
        <div class="welcome-text">
            <span class="eyebrow">STUDENT PORTAL • <?php echo htmlspecialchars($today_label); ?></span>
            <h2>Welcome back, <?php echo htmlspecialchars($student_name); ?></h2>
            <p><?php echo htmlspecialchars($department_name); ?> · <?php echo htmlspecialchars($class_name); ?></p>
        </div>
        <a href="../logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <div class="student-summary-grid">
        <div class="student-summary-card summary-attendance"><span>Attendance rate</span><strong><?php echo $attendance_rate; ?>%</strong><small><?php echo $present_days; ?> present of <?php echo $total_days; ?> days</small><div class="progress-track"><span style="width: <?php echo $attendance_rate; ?>%"></span></div></div>
        <div class="student-summary-card subjects-summary-card"><span>Enrolled subjects</span><strong><?php echo number_format($subject_count); ?></strong><?php if ($student_subjects): ?><div class="dashboard-subject-list"><?php foreach ($student_subjects as $subject): ?><span><?php echo htmlspecialchars($subject['subject_code'] . ' · ' . $subject['name']); ?></span><?php endforeach; ?></div><?php else: ?><small>No subjects assigned to your class yet</small><?php endif; ?><a href="subjects.php" class="summary-details-link">View all subjects →</a></div>
        <div class="student-summary-card summary-next"><span>Next assessment</span><strong><?php echo htmlspecialchars($next_assessment['title'] ?? 'None yet'); ?></strong><small><?php echo !empty($next_assessment['due_date']) ? 'Due ' . htmlspecialchars($next_assessment['due_date'] . (!empty($next_assessment['due_time']) ? ' ' . $next_assessment['due_time'] : '')) : 'No upcoming deadline'; ?></small><?php if (!empty($next_assessment['description'])): ?><p class="summary-assessment-details"><?php echo htmlspecialchars(mb_strimwidth($next_assessment['description'], 0, 90, '...')); ?></p><?php endif; ?><a href="assessments.php" class="summary-details-link">View assignment details →</a></div>
    </div>

    <div class="student-section-heading"><div><span class="eyebrow">YOUR LEARNING SPACE</span><h3>Quick access</h3><p>Everything you need for your daily study routine.</p></div></div>

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

        <a href="assessments.php" class="menu-card">
            <div class="card-icon">🧪</div>
            <div class="card-title">My Assessments</div>
            <p class="card-desc">View quizzes, papers and assignments</p>
        </a>

        <a href="class_enrollment.php" class="menu-card">
            <div class="card-icon">🎓</div>
            <div class="card-title">Class Enrollment</div>
            <p class="card-desc">Choose or change your department class</p>
        </a>

    </div>

</div>

</body>
</html>