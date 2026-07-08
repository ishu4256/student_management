<?php
// config ෆෝල්ඩරය සොයා ගැනීමට පියවරක් පිටුපසට (../) යා යුතුය
include "../config/db.php";

// 1. අලුත් දෙපාර්තමේන්තුවක් සුරැකීමේ ක්‍රියාවලිය (Insert)
if (isset($_POST['add_dept'])) {
    $dept_name = $_POST['dept_name'];

    if (!empty($dept_name)) {
        $dept_name = $conn->real_escape_string($dept_name);
        $conn->query("INSERT INTO departments (name) VALUES ('$dept_name')");
        header("Location: department.php?success=added");
        exit();
    }
}

// 2. දැනට සිටින දෙපාර්තමේන්තු ලැයිස්තුව ලබා ගැනීම (Select)
$result = $conn->query("SELECT * FROM departments ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments</title>
    <link rel="stylesheet" href="css/style.css?v=1.6">
</head>
<body>

<div class="dashboard department-container">
    <div class="dashboard-header">
        <div>
            <h2>Department Management</h2>
            <p class="subtitle">Create and view university/school departments</p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <div class="dept-form-box" style="background: #f8fafc; padding: 25px; border-radius: var(--radius-md); border: 1px dashed var(--border-color); margin-bottom: 35px;">
        <h3 style="margin-bottom: 15px; font-size: 18px; color: var(--primary-color);">➕ Add New Department</h3>
        
        <form method="post" style="display: flex; flex-direction: row; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <div class="input-group" style="flex: 1; margin-bottom: 0; min-width: 250px;">
                <label for="dept_name" style="font-size: 13px; font-weight: 600;">Department Name</label>
                <input type="text" id="dept_name" name="dept_name" placeholder="E.g. Computer Science, Mathematics" style="margin-bottom: 0;" required>
            </div>
            <button type="submit" name="add_dept" class="btn-primary" style="width: auto; padding: 12px 25px; margin-top: 0; white-space: nowrap;">Save Department</button>
        </form>
    </div>

    <div class="table-container">
        <h3 style="margin-bottom: 15px; color: var(--text-main); font-size: 19px;">Existing Departments</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 150px;">Department ID</th>
                    <th>Department Name</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $row['id']; ?></strong></td>
                            <td><span class="badge" style="background-color: #f1f5f9; color: var(--text-main); padding: 6px 12px; font-size: 14px;"><?php echo $row['name']; ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 20px;">No departments found. Create one above!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>