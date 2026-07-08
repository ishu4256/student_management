<?php
// ... (include සහ session_start කොටස් එලෙසම තබා ගන්න)
include "../config/db.php"; 

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// $conn variable එක null ද කියා පරීක්ෂා කිරීම (debugging සඳහා)
if (!isset($conn)) {
    die("❌ දෝෂයයි: Database connection එක සාර්ථක නැත. කරුණාකර 'config/db.php' ගොනුව නිවැරදිව include කර ඇත්දැයි පරීක්ෂා කරන්න.");
}
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_dept = $_GET['departmentid'] ?? '';

// 2. දෙපාර්තමේන්තු ලැයිස්තුව
$dept_result = $conn->query("SELECT * FROM departments ORDER BY name ASC");

// 3. SQL Query එක පිරිපහදු කිරීම
$where_clauses = ["a.attendance_date = '$selected_date'"];
if (!empty($selected_dept)) {
    $where_clauses[] = "s.departmentid = '$selected_dept'"; 
}
$where_str = implode(" AND ", $where_clauses);

// 3. SQL Query එක වෙනස් කිරීම (INNER JOIN වෙනුවට LEFT JOIN)
// මෙහිදී අපි මුලින්ම Students ලැයිස්තුව ලබාගෙන, පසුව Attendance වාර්තා අමුණමු.

$query = "SELECT s.id AS student_id, s.name AS student_name, d.name AS dept_name, a.status, a.attendance_date
          FROM students s 
          LEFT JOIN departments d ON s.departmentid = d.id
          LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = '$selected_date'
          WHERE 1=1";

if (!empty($selected_dept)) {
    $query .= " AND s.departmentid = '$selected_dept'"; 
}

$query .= " ORDER BY s.name ASC";

$attendance_result = $conn->query($query);

// 4. කාඩ් පත් සඳහා එකතුවන් (Counts) ගණනය කිරීම
$total_students = 0;
$present_count = 0;
$absent_count = 0;

$temp_data = []; // දත්ත තාවකාලිකව ගබඩා කිරීමට
if ($attendance_result && $attendance_result->num_rows > 0) {
    while ($row = $attendance_result->fetch_assoc()) {
        $temp_data[] = $row;
        $total_students++;
        
        // Status පරීක්ෂාව
        $status = strtolower(trim($row['status']));
        if ($status == 'present' || $status == 'p') {
            $present_count++;
        } else {
            $absent_count++;
        }
    }
}
$attendance_rate = $total_students > 0 ? round(($present_count / $total_students) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary</title>
    <link rel="stylesheet" href="../css/style.css?v=1.9">
    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #fff;
            padding: 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            text-align: center;
            box-shadow: var(--shadow);
        }
        .summary-card h4 { font-size: 14px; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; }
        .summary-card p { font-size: 28px; font-weight: 700; margin: 0; }
        
        .card-total { border-left: 5px solid var(--primary-color); color: var(--primary-color); }
        .card-present { border-left: 5px solid var(--success-color); color: var(--success-color); }
        .card-absent { border-left: 5px solid var(--danger-color); color: var(--danger-color); }
        .card-rate { border-left: 5px solid #eab308; color: #eab308; }

        .status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-present { background: #dcfce7; color: #15803d; }
        .badge-absent { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<div class="dashboard summary-container" style="max-width: 1000px; margin: 40px auto;">
    <div class="dashboard-header">
        <div>
            <h2>Attendance Reports & Summary</h2>
            <p class="subtitle">Filter and analyze student daily attendance stats</p>
        </div>
        <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
    </div>

    <!-- Filter Form -->
    <div class="dept-form-box" style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 25px;">
        <form method="get" style="display: flex; flex-direction: row; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            
            <div class="input-group" style="flex: 1; margin-bottom: 0; min-width: 180px;">
                <label style="font-weight: 600; font-size: 13px;">Choose Date</label>
                <input type="date" name="date" value="<?php echo $selected_date; ?>" style="margin-bottom: 0;">
            </div>

            <div class="input-group" style="flex: 1; margin-bottom: 0; min-width: 200px;">
                <label style="font-weight: 600; font-size: 13px;">Department</label>
                <select name="departmentid" style="margin-bottom: 0;">
                    <option value="">All Departments</option>
                    <?php if(isset($dept_result) && $dept_result): ?>
                        <?php while($d = $dept_result->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $selected_dept == $d['id'] ? 'selected' : ''; ?>>
                                <?php echo $d['name']; ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" class="btn-primary" style="width: auto; padding: 12px 25px; margin-top: 0;">Filter Report</button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card card-total">
            <h4>Total Checked</h4>
            <p><?php echo $total_students; ?></p>
        </div>
        <div class="summary-card card-present">
            <h4>Present</h4>
            <p><?php echo $present_count; ?></p>
        </div>
        <div class="summary-card card-absent">
            <h4>Absent</h4>
            <p><?php echo $absent_count; ?></p>
        </div>
        <div class="summary-card card-rate">
            <h4>Attendance Rate</h4>
            <p><?php echo $attendance_rate; ?>%</p>
        </div>
    </div>

    <!-- Detailed Table -->
  <div class="table-container">
    <h3 style="margin-bottom: 15px; color: var(--text-main); font-size: 18px;">Detailed Student Status List</h3>
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Department</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($temp_data)): ?>
                <?php foreach($temp_data as $row): ?>
                    <tr>
                        <td><strong>#<?php echo $row['student_id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['dept_name'] ?? 'General'); ?></td>
                        <td>
                            <?php 
                            $status = strtolower(trim($row['status']));
                            if($status == 'present' || $status == 'p'): ?>
                                <span class="status-badge badge-present">🟢 Present</span>
                            <?php else: ?>
                                <span class="status-badge badge-absent">🔴 Absent</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 25px;">
                        No attendance records found for the selected date or department.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

</body>
</html>