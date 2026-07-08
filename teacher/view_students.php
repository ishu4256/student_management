<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Guard: ගුරුවරයෙකු පමණක් බව සහතික කිරීම
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$dept_id = $_SESSION['departmentid'] ?? 0;

// 💡 තමන්ගේ දෙපාර්තමේන්තුවේ සිසුන් පමණක් ලබා ගැනීම
$query = "SELECT s.*, d.name AS dept_name 
          FROM students s 
          LEFT JOIN departments d ON s.departmentid = d.id 
          WHERE s.departmentid = '$dept_id' 
          ORDER BY s.name ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Department Students</title>
    <link rel="stylesheet" href="../css/style.css?v=2.2">
    <style>
        .table-card { background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .action-btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-edit { background: #e0e7ff; color: #4338ca; }
        .btn-delete { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <div class="table-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <h2>🎓 Students in My Department</h2>
            <a href="student_add.php" class="btn-primary" style="text-decoration:none; padding:10px 20px;">➕ Add New Student</a>
        </div>
    <a href="dashboard.php">⬅ Back to List</a>

        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding:15px; text-align:left; border-bottom:2px solid #e2e8f0;">Name</th>
                    <th style="padding:15px; text-align:left; border-bottom:2px solid #e2e8f0;">Email</th>
                    <th style="padding:15px; text-align:left; border-bottom:2px solid #e2e8f0;">Phone</th>
                    <th style="padding:15px; text-align:left; border-bottom:2px solid #e2e8f0;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="padding:15px; border-bottom:1px solid #e2e8f0;"><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td style="padding:15px; border-bottom:1px solid #e2e8f0;"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td style="padding:15px; border-bottom:1px solid #e2e8f0;"><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                            <td style="padding:15px; border-bottom:1px solid #e2e8f0;">
                                <a href="student_edit.php?id=<?php echo $row['id']; ?>" class="action-btn btn-edit">✏️ Edit</a>
                                <a href="student_delete.php?id=<?php echo $row['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:20px;">No students found in your department.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>