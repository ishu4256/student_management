<?php
include "config/db.php";

// සිසුන්ගේ දත්ත ලබා ගැනීම
$result = $conn->query("SELECT * FROM students ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="css/style.css?v=1.7">
</head>
<body>

<div class="dashboard teachers-container">
    <div class="dashboard-header">
        <div class="header-main">
            <h2>Students Management</h2>
            <p class="subtitle">View and manage registered student records</p>
        </div>
        <a href="admin/dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <!-- 💡 සාර්ථකව සිදු වූ ක්‍රියාවන් පිළිබඳ පණිවිඩ පෙන්වීම -->
    <?php if(isset($_GET['success'])): ?>
        <div class="success">
            <?php 
                if($_GET['success'] == 'updated') echo "✅ Student details updated successfully!";
                if($_GET['success'] == 'deleted') echo "🗑️ Student record deleted successfully!";
            ?>
        </div>
    <?php endif; ?>

    <!-- Add Student Button -->
    <div style="margin-bottom: 25px; text-align: right;">
        <a href="student_add.php" class="btn-primary" style="display: inline-block; width: auto; padding: 12px 24px; background: var(--success-color) !important; text-decoration: none;">➕ Add New Student</a>
    </div>

    <!-- ශිෂ්‍ය දත්ත වගුව -->
    <div class="table-container">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>sex</th>
                    <th>date of birth</th>
                    <th>parents name</th>
                    <th>Address</th>
                    <th>Actions</th> <!-- 💡 අලුත් තීරුව -->
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone'] ?? '-'; ?></td>
                            <td><?php echo $row['department_name'] ?? '-'; ?></td>
                            <td><?php echo $row['sex'] ?? '-'; ?></td>
                            <td><?php echo $row['date_of_birth'] ?? '-'; ?></td>
                            <td><?php echo $row['parents_name'] ?? '-'; ?></td>
                                                        <td><?php echo $row['address'] ?? '-'; ?></td>

                            <td>
                                <!-- 💡 Edit සහ Delete බොත්තම් ලින්ක්ස් -->
                                <a href="student_edit.php?id=<?php echo $row['id']; ?>" style="color: var(--primary-color); margin-right: 12px; text-decoration: none; font-weight: 600;">✏️ Edit</a>
                                <a href="student_delete.php?id=<?php echo $row['id']; ?>" style="color: var(--danger-color); text-decoration: none; font-weight: 600;" onclick="return confirm('Are you sure you want to delete this student?');">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 20px;">No student records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>