<?php
include "config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$department_filter = (int)($_GET['department'] ?? 0);
$class_filter = (int)($_GET['class_id'] ?? 0);
$search_sql = $conn->real_escape_string($search);
$where = [];

if ($search !== '') {
    $where[] = "(s.name LIKE '%$search_sql%' OR s.email LIKE '%$search_sql%' OR s.phone LIKE '%$search_sql%' OR s.id LIKE '%$search_sql%')";
}
if ($department_filter > 0) {
    $where[] = "s.departmentid = $department_filter";
}
if ($class_filter > 0) {
    $where[] = "s.class_id = $class_filter";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$student_query = "SELECT s.*, d.name AS department_name, c.class_name
                  FROM students s
                  LEFT JOIN departments d ON d.id = s.departmentid
                  LEFT JOIN classes c ON c.id = s.class_id
                  $where_sql
                  ORDER BY s.name ASC";
$result = $conn->query($student_query);
$query_error = $result ? '' : $conn->error;
$student_count = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;
$department_count = $conn->query("SELECT COUNT(*) AS total FROM departments")->fetch_assoc()['total'] ?? 0;
$visible_count = $result ? $result->num_rows : 0;
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");

if (isset($_GET['export']) && $_GET['export'] === 'csv' && $result) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student ID', 'Name', 'Email', 'Phone', 'Department', 'Class', 'Sex', 'Date of Birth', 'Parent Name', 'Address']);
    while ($student = $result->fetch_assoc()) {
        fputcsv($output, [$student['id'], $student['name'], $student['email'], $student['phone'] ?? '-', $student['department_name'] ?? '-', $student['class_name'] ?? '-', $student['sex'] ?? '-', $student['date_of_birth'] ?? '-', $student['parents_name'] ?? '-', $student['address'] ?? '-']);
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link rel="stylesheet" href="css/style.css?v=2.7">
</head>
<body>

<div class="dashboard teachers-container">
    <div class="dashboard-header">
        <div class="header-main">
            <span class="eyebrow">STUDENT DIRECTORY</span>
            <h2>Students Management</h2>
            <p class="subtitle">Search, filter and manage student profiles from one organised directory.</p>
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

    <div class="student-stats-grid">
        <div class="student-stat"><span>Total students</span><strong><?php echo number_format($student_count); ?></strong></div>
        <div class="student-stat"><span>Departments</span><strong><?php echo number_format($department_count); ?></strong></div>
        <div class="student-stat"><span>Showing now</span><strong><?php echo number_format($visible_count); ?></strong></div>
    </div>

    <div class="student-action-bar">
        <a href="student_add.php" class="btn-primary">+ Add New Student</a>
        <a href="student.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-export">Export CSV</a>
    </div>

    <div class="student-directory table-container">
        <div class="directory-heading">
            <div>
                <h3>Registered students</h3>
                <p>Find a student by name, email, phone number or ID.</p>
            </div>
        </div>

        <form method="get" action="student.php" class="student-filter-bar">
            <div class="input-group">
                <label for="student-search">Search directory</label>
                <input type="search" id="student-search" name="search" placeholder="Name, email, phone or ID" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="input-group">
                <label for="student-department">Department</label>
                <select id="student-department" name="department">
                    <option value="0">All departments</option>
                    <?php while ($department = $departments->fetch_assoc()): ?>
                        <option value="<?php echo (int)$department['id']; ?>" <?php echo $department_filter === (int)$department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="student-class">Class</label>
                <select id="student-class" name="class_id">
                    <option value="0">All classes</option>
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <option value="<?php echo (int)$class['id']; ?>" <?php echo $class_filter === (int)$class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary student-filter-submit">Apply filters</button>
            <?php if ($search !== '' || $department_filter > 0 || $class_filter > 0): ?>
                <a href="student.php" class="student-clear-filter">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($query_error !== ''): ?>
            <div class="error">Unable to load student records. Please check the database connection.</div>
        <?php elseif ($search !== '' || $department_filter > 0 || $class_filter > 0): ?>
            <p class="student-result-note">Showing <?php echo number_format($visible_count); ?> matching student<?php echo $visible_count === 1 ? '' : 's'; ?>.</p>
        <?php endif; ?>

        <div class="student-table-scroll">
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>Class</th>
                    <th>sex</th>
                    <th>date of birth</th>
                    <th>Parent Name</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo (int)$row['id']; ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($row['department_name'] ?? '-'); ?></span></td>
                            <td><?php echo htmlspecialchars($row['class_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['sex'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['date_of_birth'] ?? '-'); ?></td>
                            <td>
                                <div class="student-cell-group">
                                    <span class="cell-value"><?php echo htmlspecialchars($row['parents_name'] ?? '-'); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="student-cell-group">
                                    <span class="cell-label">Address</span>
                                    <span class="cell-value"><?php echo htmlspecialchars($row['address'] ?? '-'); ?></span>
                                </div>
                            </td>

                            <td>
                                <div class="directory-actions">
                                    <a href="student_edit.php?id=<?php echo (int)$row['id']; ?>" class="table-edit">Edit</a>
                                    <a href="student_delete.php?id=<?php echo (int)$row['id']; ?>" class="table-delete" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="empty-table">No student records match your filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

</body>
</html>