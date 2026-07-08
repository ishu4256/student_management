<?php
include "../config/db.php";

$teacher = null;
$search_id = '';

// වගුවෙන් කෙලින්ම ID එකක් ආවොත්
if (isset($_GET['id'])) {
    $search_id = $_GET['id'];
}

// ID එක සෙවූ විට පැරණි දත්ත ලබා ගැනීම
if (!empty($search_id) || isset($_POST['search'])) {
    $id = !empty($search_id) ? $search_id : $_POST['userid'];
    $res = $conn->query("SELECT * FROM teachers WHERE userid='$id'");
    if ($res->num_rows == 1) {
        $teacher = $res->fetch_assoc();
    } else {
        $error = "❌ Teacher ID not found!";
    }
}

// දත්ත යාවත්කාලීන කිරීම (Update)
if (isset($_POST['update'])) {
    $userid = $_POST['userid'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $departmentid = $_POST['departmentid'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $conn->query("UPDATE teachers SET name='$name', email='$email', departmentid='$departmentid', phone='$phone', address='$address' WHERE userid='$userid'");
    header("Location: teachers.php?success=updated");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher Info</title>
    <link rel="stylesheet" href="../css/style.css?v=1.5">
</head>
<body>

<div class="form-container">
    <div class="form-header">
        <div>
            <h2>Edit Teacher Information</h2>
            <p class="subtitle">Search by User ID and update details</p>
        </div>
        <a href="teachers.php" class="btn-back">⬅ Back</a>
    </div>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

    <?php if(!$teacher): ?>
    <form method="post" style="margin-bottom: 20px;">
        <div class="input-group">
            <label>Enter Teacher User ID to Edit</label>
            <input type="number" name="userid" placeholder="Enter User ID (E.g. 1)" required>
        </div>
        <button type="submit" name="search" class="btn-primary">🔍 Find Teacher</button>
    </form>
    <?php endif; ?>

    <?php if($teacher): ?>
    <form method="post">
        <input type="hidden" name="userid" value="<?php echo $teacher['userid']; ?>">
        
        <div class="form-row">
            <div class="input-group">
                <label>Teacher Name</label>
                <input type="text" name="name" value="<?php echo $teacher['name']; ?>" required>
            </div>
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo $teacher['email']; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="input-group">
                <label>Department</label>
                <select name="departmentid" required>
                    <?php
                    $deps = $conn->query("SELECT * FROM departments");
                    while($d = $deps->fetch_assoc()){
                        $selected = ($d['id'] == $teacher['departmentid']) ? 'selected' : '';
                        echo "<option value='{$d['id']}' $selected>{$d['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="input-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?php echo $teacher['phone']; ?>">
            </div>
        </div>

        <div class="input-group">
            <label>Address</label>
            <textarea name="address" rows="3"><?php echo $teacher['address']; ?></textarea>
        </div>

        <button type="submit" name="update" class="btn-submit" style="background: var(--primary-color) !important;">💾 Save Changes</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>