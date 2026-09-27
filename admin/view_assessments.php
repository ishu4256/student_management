<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    assessment_type VARCHAR(30) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    due_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$search = trim($_GET['search'] ?? '');
$search_sql = $conn->real_escape_string($search);

$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes = $selected_department > 0 ? $conn->query("SELECT id, class_name FROM classes WHERE department_id = '$selected_department' ORDER BY class_name ASC") : false;

$condition = '1=1';
if ($selected_department > 0) {
    $condition .= " AND a.department_id = '$selected_department'";
}
if ($selected_class > 0) {
    $condition .= " AND a.class_id = '$selected_class'";
}
if ($search !== '') {
    $condition .= " AND (a.title LIKE '%$search_sql%' OR a.assessment_type LIKE '%$search_sql%' OR a.description LIKE '%$search_sql%')";
}

$assessments = $conn->query("SELECT a.*, d.name AS dept_name, c.class_name
    FROM assessments a
    LEFT JOIN departments d ON d.id = a.department_id
    LEFT JOIN classes c ON c.id = a.class_id
    WHERE $condition
    ORDER BY d.name ASC, c.class_name ASC, a.due_date DESC");
$query_error = $assessments ? '' : $conn->error;
$total_assessments = $conn->query("SELECT COUNT(*) AS total FROM assessments")->fetch_assoc()['total'] ?? 0;
$upcoming_assessments = $conn->query("SELECT COUNT(*) AS total FROM assessments WHERE due_date IS NOT NULL AND due_date >= CURDATE()")->fetch_assoc()['total'] ?? 0;
$overdue_assessments = $conn->query("SELECT COUNT(*) AS total FROM assessments WHERE due_date IS NOT NULL AND due_date < CURDATE()")->fetch_assoc()['total'] ?? 0;
$visible_assessments = $assessments ? $assessments->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Assessment View</title>
    <link rel="stylesheet" href="../css/style.css?v=2.6">
    <style>
        body { background:#f1f5f9; font-family:'Segoe UI', sans-serif; }
        .assessment-card { background:#fff; border-radius:16px; padding:30px; max-width:1100px; margin:40px auto; box-shadow:0 10px 25px -5px rgba(0,0,0,0.08); }
        .modern-table { width:100%; border-collapse:separate; border-spacing:0; }
        .modern-table th { background:#f8fafc; padding:12px; text-align:left; border-bottom:1px solid #e2e8f0; }
        .modern-table td { padding:12px; border-bottom:1px solid #e2e8f0; }
        .pill { display:inline-block; padding:4px 10px; border-radius:999px; background:#e0e7ff; color:#3730a3; font-size:12px; font-weight:700; }
    </style>
</head>
<body>
<div class="assessment-card">
        <div class="assessment-header">
        <div>
            <span class="eyebrow">ACADEMIC OPERATIONS</span>
            <h2>Assessment Overview</h2>
            <p class="subtitle">Monitor quizzes, assignments and papers across every department and class.</p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back</a>
    </div>

    <div class="assessment-stats">
        <div class="assessment-stat"><span>Total assessments</span><strong><?php echo number_format($total_assessments); ?></strong></div>
        <div class="assessment-stat assessment-stat-upcoming"><span>Upcoming</span><strong><?php echo number_format($upcoming_assessments); ?></strong></div>
        <div class="assessment-stat assessment-stat-overdue"><span>Overdue</span><strong><?php echo number_format($overdue_assessments); ?></strong></div>
        <div class="assessment-stat"><span>Showing now</span><strong><?php echo number_format($visible_assessments); ?></strong></div>
    </div>

    <form method="get" action="view_assessments.php" class="assessment-filter-form">
        <div class="input-group assessment-search-field">
            <label for="assessment-search">Search assessments</label>
            <input type="search" id="assessment-search" name="search" placeholder="Title, type or description" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="input-group">
            <label for="department_id">Department</label>
            <select id="department_id" name="department_id">
                <option value="">-- All Departments --</option>
                <?php while ($dept = $departments->fetch_assoc()): ?>
                    <option value="<?php echo (int)$dept['id']; ?>" <?php echo ($selected_department == (int)$dept['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="input-group">
            <label for="class_id">Class</label>
            <select id="class_id" name="class_id">
                <option value="">-- All Classes --</option>
                <?php if ($classes): while ($class = $classes->fetch_assoc()): ?>
                    <option value="<?php echo (int)$class['id']; ?>" <?php echo ($selected_class == (int)$class['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class['class_name']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary assessment-filter-submit">Apply filters</button>
        <?php if ($search !== '' || $selected_department > 0 || $selected_class > 0): ?>
            <a href="view_assessments.php" class="assessment-clear-filter">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($query_error !== ''): ?>
        <div class="error">Unable to load assessments. Please check the database connection.</div>
    <?php elseif ($search !== '' || $selected_department > 0 || $selected_class > 0): ?>
        <p class="assessment-result-note">Showing <?php echo number_format($visible_assessments); ?> matching assessment<?php echo $visible_assessments === 1 ? '' : 's'; ?>.</p>
    <?php endif; ?>

    <div style="overflow-x:auto;">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Class</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assessments && $assessments->num_rows > 0): ?>
                    <?php while ($row = $assessments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['dept_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['class_name'] ?? '-'); ?></td>
                            <td><span class="pill"><?php echo htmlspecialchars(ucfirst($row['assessment_type'])); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['due_date'] ?? '-'); ?></td>
                            <td>
                                <?php if (empty($row['due_date'])): ?>
                                    <span class="assessment-status status-no-date">No due date</span>
                                <?php elseif ($row['due_date'] < date('Y-m-d')): ?>
                                    <span class="assessment-status status-overdue">Overdue</span>
                                <?php else: ?>
                                    <span class="assessment-status status-upcoming">Upcoming</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="assessment-empty">No assessments match the selected filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
