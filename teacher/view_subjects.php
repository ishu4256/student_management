<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php"); exit();
}

$dept_id = $_SESSION['departmentid'] ?? 0;
$subjects = $conn->query("SELECT * FROM subjects WHERE departmentid = '$dept_id' ORDER BY subject_code ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
    <link rel="stylesheet" href="../css/style.css?v=2.3">
    <style>
        /* Modern UI & Color Variables */
        :root {
            --bg-soft: #f8fafc;
            --text-dark: #1e293b;
            --border-soft: #e2e8f0;
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-dark);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .subjects-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            border: 1px solid var(--border-soft);
        }

        .dashboard-header {
            border-bottom: 2px solid var(--bg-soft);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .main-title {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        /* Buttons Styling */
        .btn-back-modern {
            display: inline-flex;
            align-items: center;
            background: #ffffff;
            color: #475569;
            border: 1px solid var(--border-soft);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-back-modern:hover {
            background: #f8fafc;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .btn-add-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--success-color);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }

        .btn-add-modern:hover {
            background: #059669;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }

        /* Table Styling */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        .modern-table th {
            background: var(--bg-soft);
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            border-bottom: 2px solid var(--border-soft);
            text-align: left;
        }

        .modern-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-soft);
            font-size: 15px;
            color: #334155;
            vertical-align: middle;
        }

        .modern-table tr:last-child td {
            border-bottom: none;
        }

        .modern-table tr:hover td {
            background-color: #f8fafc;
        }

        /* Component Badges */
        .code-badge {
            background: #e0f2fe;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            display: inline-block;
        }

        .credits-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border-soft);
        }

        .subject-name {
            font-weight: 600;
            color: #0f172a;
        }

        /* Action Links as Minimal Buttons */
        .action-link {
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .action-edit {
            color: var(--primary-color);
            background: #e0e7ff;
            margin-right: 8px;
        }

        .action-edit:hover {
            background: #c7d2fe;
        }

        .action-delete {
            color: var(--danger-color);
            background: #fee2e2;
        }

        .action-delete:hover {
            background: #fecaca;
        }

        .no-data {
            text-align: center;
            padding: 40px !important;
            color: #94a3b8;
            font-size: 15px;
        }
    </style>
</head>
<body>

<div class="dashboard container" style="max-width: 1000px; margin: 50px auto; padding: 0 15px;">
    
    <div class="subjects-card">
        <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="main-title">📚 Manage Department Subjects</h2>
            <a href="dashboard.php" class="btn-back-modern">⬅ Back to Dashboard</a>
        </div>

        <div style="margin: 20px 0; display: flex; justify-content: flex-end;">
            <a href="subject_add.php" class="btn-add-modern">➕ Add New Subject</a>
        </div>

        <div class="table-container" style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Code</th>
                        <th style="width: 45%;">Subject Name</th>
                        <th style="width: 15%;">Credits</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($subjects && $subjects->num_rows > 0): ?>
                        <?php while($row = $subjects->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="code-badge"><?php echo $row['subject_code']; ?></span>
                                </td>
                                <td>
                                    <span class="subject-name"><?php echo $row['name']; ?></span>
                                </td>
                                <td>
                                    <span class="credits-badge">🪙 <?php echo $row['credits']; ?> Credits</span>
                                </td>
                                <td>
                                    <a href="subject_edit.php?id=<?php echo $row['id']; ?>" class="action-link action-edit">✏️ Edit</a>
                                    <a href="subject_delete.php?id=<?php echo $row['id']; ?>" class="action-link action-delete" onclick="return confirm('Are you sure you want to delete this subject?');">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data">
                                📭 No subjects found for your department.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>