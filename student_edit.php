<?php
include "config/db.php";

$student = null;

// වගුවෙන් එවන ID එක හරහා ශිෂ්‍යයාගේ පැරණි දත්ත ලබා ගැනීම
if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $res = $conn->query("SELECT * FROM students WHERE id='$id'");
    if ($res->num_rows == 1) {
        $student = $res->fetch_assoc();
    } else {
        header("Location: student.php");
        exit();
    }
} else {
    header("Location: student.php");
    exit();
}

// දත්ත වෙනස් කර Save කළ පසු Database එක Update කිරීම
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);

    $conn->query("UPDATE students SET name='$name', email='$email', phone='$phone', address='$address' WHERE id='$id'");
    
    header("Location: student.php?success=updated");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="css/style.css?v=1.7">
</head>
<body>

<div class="form-container" style="max-width: 700px; margin: 50px auto; padding: 40px; background: var(--card-bg); border-radius: var(--radius-md); box-shadow: var(--shadow); border: 1px solid var(--border-color);">
    <div class="form-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 25px;">
        <div>
            <h2 style="color: var(--primary-color); margin: 0;">Edit Student Profile</h2>
            <p class="subtitle" style="margin: 5px 0 0 0;">Modify student registration details</p>
        </div>
        <a href="student.php" class="btn-back">⬅ Back</a>
    </div>

    <form method="post">
        <!-- ශිෂ්‍යයාගේ ID එක සැඟවුණු field එකක් ලෙස යැවීම -->
        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">

        <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            <div class="input-group">
                <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">Student Name</label>
                <input type="text" name="name" value="<?php echo $student['name']; ?>" required>
            </div>
            <div class="input-group">
                <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">Email Address</label>
                <input type="email" name="email" value="<?php echo $student['email']; ?>" required>
            </div>
        </div>

        <div class="input-group" style="margin-top: 15px;">
            <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">Phone Number</label>
            <input type="text" name="phone" value="<?php echo $student['phone']; ?>">
        </div>

        <div class="input-group" style="margin-top: 15px; margin-bottom: 20px;">
            <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">Address</label>
            <textarea name="address" rows="3"><?php echo $student['address']; ?></textarea>
        </div>

        <button type="submit" name="update" class="btn-primary" style="width: 100%; padding: 14px; font-weight: 600;">💾 Save Changes</button>
    </form>
</div>

</body>
</html>