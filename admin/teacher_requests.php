<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS teacher_enrollment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT NOT NULL,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (teacher_user_id), INDEX (department_id), INDEX (class_id), INDEX (status)
)");
$conn->query("CREATE TABLE IF NOT EXISTS teacher_class_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_user_id INT NOT NULL,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY teacher_class (teacher_user_id, class_id), INDEX (department_id)
)");

$action_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_action'])) {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['request_action'];
    $admin_id = (int)($_SESSION['user_id'] ?? 0);
    $request_result = $conn->query("SELECT * FROM teacher_enrollment_requests WHERE id = $request_id AND status = 'pending'");
    $request = $request_result ? $request_result->fetch_assoc() : null;

    if (!$request) {
        $action_error = 'This request is no longer pending.';
    } elseif ($action === 'approve') {
        $teacher_id = (int)$request['teacher_user_id'];
        $department_id = (int)$request['department_id'];
        $class_id = (int)$request['class_id'];
        $valid_class = $conn->query("SELECT id FROM classes WHERE id = $class_id AND department_id = $department_id");

        if (!$valid_class || $valid_class->num_rows === 0) {
            $action_error = 'The requested class no longer belongs to that department.';
        } else {
            $conn->begin_transaction();
            $approved = $conn->query("UPDATE teacher_enrollment_requests SET status = 'approved', reviewed_by = $admin_id, reviewed_at = NOW() WHERE id = $request_id");
            $assigned = $conn->query("INSERT IGNORE INTO teacher_class_assignments (teacher_user_id, department_id, class_id) VALUES ($teacher_id, $department_id, $class_id)");
            $teacher_updated = $conn->query("UPDATE teachers SET departmentid = $department_id WHERE userid = $teacher_id");
            if ($approved && $assigned && $teacher_updated) {
                $conn->commit();
                header("Location: teacher_requests.php?success=approved");
                exit();
            }
            $conn->rollback();
            $action_error = 'Approval failed. No changes were saved.';
        }
    } elseif ($action === 'reject') {
        if ($conn->query("UPDATE teacher_enrollment_requests SET status = 'rejected', reviewed_by = $admin_id, reviewed_at = NOW() WHERE id = $request_id")) {
            header("Location: teacher_requests.php?success=rejected");
            exit();
        }
        $action_error = 'Unable to reject this request.';
    }
}

$pending_requests = $conn->query("SELECT r.*, t.name AS teacher_name, t.email AS teacher_email, d.name AS department_name, c.class_name FROM teacher_enrollment_requests r LEFT JOIN teachers t ON t.userid = r.teacher_user_id LEFT JOIN departments d ON d.id = r.department_id LEFT JOIN classes c ON c.id = r.class_id WHERE r.status = 'pending' ORDER BY r.created_at ASC");
$reviewed_requests = $conn->query("SELECT r.*, t.name AS teacher_name, d.name AS department_name, c.class_name FROM teacher_enrollment_requests r LEFT JOIN teachers t ON t.userid = r.teacher_user_id LEFT JOIN departments d ON d.id = r.department_id LEFT JOIN classes c ON c.id = r.class_id WHERE r.status != 'pending' ORDER BY r.reviewed_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Enrollment Requests</title>
    <link rel="stylesheet" href="../css/style.css?v=2.9">
</head>
<body>
<div class="dashboard enrollment-page admin-requests-page">
    <div class="dashboard-header">
        <div><span class="eyebrow">ADMIN APPROVALS</span><h2>Teacher Enrollment Requests</h2><p class="subtitle">Review department and class access requests before they become active.</p></div>
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
    <?php if (isset($_GET['success'])): ?><div class="success">Request <?php echo htmlspecialchars($_GET['success']); ?> successfully.</div><?php endif; ?>
    <?php if ($action_error !== ''): ?><div class="error"><?php echo htmlspecialchars($action_error); ?></div><?php endif; ?>

    <section class="enrollment-panel">
        <div class="panel-heading"><div><h3>Pending approvals</h3><p>Approve only requests that should have access.</p></div></div>
        <div class="request-table-wrap">
            <table>
                <thead><tr><th>Teacher</th><th>Department</th><th>Class</th><th>Requested</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($pending_requests && $pending_requests->num_rows > 0): while ($request = $pending_requests->fetch_assoc()): ?>
                    <tr><td><strong><?php echo htmlspecialchars($request['teacher_name'] ?? 'Unknown'); ?></strong><small class="table-subtext"><?php echo htmlspecialchars($request['teacher_email'] ?? '-'); ?></small></td><td><?php echo htmlspecialchars($request['department_name'] ?? '-'); ?></td><td><?php echo htmlspecialchars($request['class_name'] ?? '-'); ?></td><td><?php echo htmlspecialchars($request['created_at']); ?></td><td><div class="request-actions"><form method="post"><input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>"><button type="submit" name="request_action" value="approve" class="btn-action btn-approve">Approve</button><button type="submit" name="request_action" value="reject" class="btn-action btn-delete" onclick="return confirm('Reject this enrollment request?');">Reject</button></form></div></td></tr>
                <?php endwhile; else: ?><tr><td colspan="5" class="empty-table">No pending enrollment requests.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="enrollment-panel request-history">
        <div class="panel-heading"><div><h3>Recent decisions</h3><p>Latest approved and rejected requests.</p></div></div>
        <div class="enrollment-list">
        <?php if ($reviewed_requests && $reviewed_requests->num_rows > 0): while ($request = $reviewed_requests->fetch_assoc()): ?>
            <div class="enrollment-item"><div><strong><?php echo htmlspecialchars($request['teacher_name'] ?? 'Unknown'); ?> · <?php echo htmlspecialchars($request['department_name'] ?? '-'); ?> / <?php echo htmlspecialchars($request['class_name'] ?? '-'); ?></strong><small>Reviewed <?php echo htmlspecialchars($request['reviewed_at'] ?? '-'); ?></small></div><span class="status-chip status-<?php echo htmlspecialchars($request['status']); ?>"><?php echo ucfirst(htmlspecialchars($request['status'])); ?></span></div>
        <?php endwhile; else: ?><p class="empty-state">No reviewed requests yet.</p><?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
