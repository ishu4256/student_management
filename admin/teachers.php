<?php
include "../config/db.php";

// ගුරුවරුන්ගේ සහ ඔවුන්ට අදාළ දෙපාර්තමේන්තු වල නම් එකට ලබා ගැනීම
$result = $conn->query("SELECT t.*, d.name AS dept_name FROM teachers t LEFT JOIN departments d ON t.departmentid = d.id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers</title>
    <link rel="stylesheet" href="../css/style.css?v=1.5">
</head>
<body>

<div class="dashboard teachers-container">
    <div class="dashboard-header">
        <div class="header-main">
            <h2>Teachers Management</h2>
            <p class="subtitle">Add, edit, delete or search teacher records</p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <div class="action-grid">
        <a href="teacher_add.php" class="action-card card-add">
            <span class="action-icon">➕</span>
            <span class="action-title">Add New Teacher</span>
        </a>
        <a href="teacher_edit.php" class="action-card card-edit">
            <span class="action-icon">✏️</span>
            <span class="action-title">Edit Teacher Info</span>
        </a>
        <a href="teacher_delete.php" class="action-card card-delete">
            <span class="action-icon">🗑️</span>
            <span class="action-title">Delete Teacher</span>
        </a>
        <a href="teacher_search.php" class="action-card card-search">
            <span class="action-icon">🔍</span>
            <span class="action-title">Search & Manage</span>
        </a>
    </div>

    <div class="table-container" style="margin-top: 40px;">
        <h3 style="margin-bottom: 15px; color: var(--text-main); font-size: 20px;">Registered Teachers List</h3>
        <table>
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['userid']; ?></strong></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><span class="badge"><?php echo $row['dept_name'] ?? 'N/A'; ?></span></td>
                            <td><?php echo $row['phone'] ?? '-'; ?></td>
                            <td><?php echo $row['address'] ?? '-'; ?></td>
                            <td>
                                <a href="teacher_edit.php?id=<?php echo $row['userid']; ?>" style="color: var(--primary-color); margin-right: 10px;">✏️ Edit</a>
                                <a href="teacher_delete.php?id=<?php echo $row['userid']; ?>" style="color: var(--danger-color);" onclick="return confirm('Are you sure you want to delete this teacher?');">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted);">No teacher records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>