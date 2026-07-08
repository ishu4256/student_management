<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$student = $conn->query("SELECT id, departmentid FROM students WHERE userid = '$user_id'")->fetch_assoc();
$student_id = $student['id'] ?? 0;
$dept_id = $student['departmentid'] ?? 0;

// 1. Enroll කිරීමේ තර්කනය (Enroll Button එක Click කළ විට)
if (isset($_GET['enroll_id'])) {
    $subject_id = (int)$_GET['enroll_id'];
    $conn->query("INSERT INTO enrollments (student_id, subject_id) VALUES ('$student_id', '$subject_id')");
    header("Location: subjects.php"); // නැවත load කිරීම
}

// 2. ශිෂ්‍යයා ලියාපදිංචි වී ඇති විෂයයන්
$enrolled_query = $conn->query("SELECT s.* FROM subjects s INNER JOIN enrollments e ON s.id = e.subject_id WHERE e.student_id = '$student_id'");

// 3. එම දෙපාර්තමේන්තුවේ ඇති නමුත් ශිෂ්‍යයා තවමත් ලියාපදිංචි වී නැති විෂයයන් (Available)
$available_query = $conn->query("SELECT * FROM subjects WHERE departmentid = '$dept_id' AND id NOT IN (SELECT subject_id FROM enrollments WHERE student_id = '$student_id')");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Subjects</title>
    <link rel="stylesheet" href="../css/style.css?v=2.4">
    <style>
        .subject-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .subject-info h3 { margin: 0; color: #1e293b; }
        .badge { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="dashboard-container" style="max-width: 800px; margin: 40px auto;">
    <h2>📚 My Subjects</h2>
            <a href="dashboard.php" class="btn-secondary">⬅ Back to Dashboard</a>

    <h3>Enrolled Subjects</h3>
    <?php while($row = $enrolled_query->fetch_assoc()): ?>
        <div class="subject-card" style="border-left: 5px solid #16a34a;">
            <div><h3><?php echo $row['name']; ?></h3><p>Code: <?php echo isset($row['code']) ? htmlspecialchars($row['code']) : 'N/A'; ?></p></div>
            <span class="badge" style="background:#dcfce7; color:#166534;">Enrolled</span>
        </div>
    <?php endwhile; ?>

    <hr style="margin: 40px 0;">

    <h3>Available to Enroll</h3>
    <?php while($row = $available_query->fetch_assoc()): ?>
        <div class="subject-card">
            <div><h3><?php echo $row['name']; ?></h3><p>Code: <?php echo isset($row['code']) ? htmlspecialchars($row['code']) : 'N/A'; ?></p></div>
            <a href="subjects.php?enroll_id=<?php echo $row['id']; ?>" class="btn-primary" style="text-decoration:none; padding:8px 16px;">➕ Enroll</a>
        </div>
    <?php endwhile; ?>
</div>
</body>
</html>