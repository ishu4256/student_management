<?php
include "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$department_filter = (int)($_GET['department'] ?? 0);
$search_sql = $conn->real_escape_string($search);
$where = [];

if ($search !== '') {
    $where[] = "(t.name LIKE '%$search_sql%' OR t.email LIKE '%$search_sql%' OR t.userid LIKE '%$search_sql%')";
}
if ($department_filter > 0) {
    $where[] = "t.departmentid = $department_filter";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$result = $conn->query("SELECT t.*, d.name AS dept_name FROM teachers t LEFT JOIN departments d ON t.departmentid = d.id $where_sql ORDER BY t.name ASC");
$query_error = $result ? '' : $conn->error;
$teacher_count = $conn->query("SELECT COUNT(*) AS total FROM teachers")->fetch_assoc()['total'] ?? 0;
$department_count = $conn->query("SELECT COUNT(*) AS total FROM departments")->fetch_assoc()['total'] ?? 0;
$filtered_count = $result ? $result->num_rows : 0;
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers</title>
    <link rel="stylesheet" href="../css/style.css?v=2.5">
</head>
<body>

<div class="dashboard teachers-container">
    <div class="dashboard-header">
        <div class="header-main">
            <span class="eyebrow">PEOPLE DIRECTORY</span>
            <h2>Teachers Management</h2>
            <p class="subtitle">Keep staff profiles, contact details and department assignments up to date.</p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success">Teacher record <?php echo htmlspecialchars($_GET['success']); ?> successfully.</div>
        <?php if ($_GET['success'] === 'added' && isset($_GET['default_password'])): ?>
            <div class="info-box">Default login password: <strong><?php echo htmlspecialchars($_GET['default_password']); ?></strong>. Ask the teacher to change it after first login.</div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="teacher-stats-grid">
        <div class="teacher-stat"><span>Total teachers</span><strong><?php echo number_format($teacher_count); ?></strong></div>
        <div class="teacher-stat"><span>Departments</span><strong><?php echo number_format($department_count); ?></strong></div>
        <div class="teacher-stat"><span>Showing now</span><strong><?php echo number_format($filtered_count); ?></strong></div>
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

    <div class="teacher-directory table-container">
        <div class="directory-heading">
            <div>
                <h3>Registered teachers</h3>
                <p>Search by name, email or user ID and narrow the list by department.</p>
            </div>
            <a href="teacher_add.php" class="btn-primary btn-compact">+ Add teacher</a>
        </div>

        <form method="get" action="teachers.php" class="teacher-filter-bar">
            <div class="input-group">
                <label for="teacher-search">Search directory</label>
                <input type="search" id="teacher-search" name="search" placeholder="Name, email or user ID" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="input-group">
                <label for="teacher-department">Department</label>
                <select id="teacher-department" name="department">
                    <option value="0">All departments</option>
                    <?php while ($department = $departments->fetch_assoc()): ?>
                        <option value="<?php echo (int)$department['id']; ?>" <?php echo $department_filter === (int)$department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="apply_filters" value="1" class="btn-primary filter-submit">Apply filters</button>
            <?php if ($search !== '' || $department_filter > 0): ?>
                <a href="teachers.php" class="clear-filter">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($query_error !== ''): ?>
            <div class="error">Unable to load teacher records. Please check the database connection.</div>
        <?php elseif ($search !== '' || $department_filter > 0): ?>
            <p class="filter-result-note">Showing <?php echo number_format($filtered_count); ?> matching teacher<?php echo $filtered_count === 1 ? '' : 's'; ?>.</p>
        <?php endif; ?>

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
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($row['userid']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['dept_name'] ?? 'N/A'); ?></span></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['address'] ?? '-'); ?></td>
                            <td>
                                <div class="directory-actions">
                                    <a href="teacher_edit.php?id=<?php echo urlencode($row['userid']); ?>" class="table-edit">Edit</a>
                                    <a href="teacher_delete.php?id=<?php echo urlencode($row['userid']); ?>" class="table-delete" onclick="return confirm('Are you sure you want to delete this teacher?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-table">No teacher records match your filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>