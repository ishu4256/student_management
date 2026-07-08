<?php
include "../config/db.php";

$search_result = null;
$search_query = "";

if (isset($_POST['search'])) {
    $search_query = $_POST['query'];
    // නම හෝ ID එක අනුව සෙවීම
    $search_result = $conn->query("SELECT t.*, d.name AS dept_name FROM teachers t LEFT JOIN departments d ON t.departmentid = d.id WHERE t.name LIKE '%$search_query%' OR t.userid = '$search_query'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Teachers</title>
    <link rel="stylesheet" href="../css/style.css?v=1.5">
</head>
<body>

<div class="dashboard teachers-container">
    <div class="dashboard-header">
        <div>
            <h2>Search Teachers</h2>
            <p class="subtitle">Find teacher profiles by name or User ID</p>
        </div>
        <a href="teachers.php" class="btn-back">⬅ Back</a>
    </div>

    <form method="post" style="display: flex; flex-direction: row; gap: 10px; margin-bottom: 30px;">
        <input type="text" name="query" placeholder="Type Teacher Name or User ID..." value="<?php echo $search_query; ?>" style="margin-bottom: 0;" required>
        <button type="submit" name="search" class="btn-primary" style="width: auto; white-space: nowrap; margin-top: 0;">🔍 Search</button>
    </form>

    <?php if($search_result): ?>
        <div class="table-container">
            <h3>Search Results (<?php echo $search_result->num_rows; ?> found)</h3>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($search_result->num_rows > 0): ?>
                        <?php while($row = $search_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['userid']; ?></strong></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['dept_name'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['phone'] ?? '-'; ?></td>
                                <td>
                                    <a href="teacher_edit.php?id=<?php echo $row['userid']; ?>" style="color: var(--primary-color); margin-right: 10px;">✏️ Edit</a>
                                    <a href="teacher_delete.php?id=<?php echo $row['userid']; ?>" style="color: var(--danger-color);">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted);">No matching teachers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>