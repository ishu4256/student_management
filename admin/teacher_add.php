<?php
include "../config/db.php";

if (isset($_POST['save'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $departmentid = $_POST['departmentid'];
    $userid = $_POST['userid'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // සටහන: SQL Injection වලින් ආරක්ෂා වීමට ඉදිරියේදී prepare statements භාවිතා කරන්න.
    $conn->query("INSERT INTO teachers
    (name, email, departmentid, userid, phone, address)
    VALUES
    ('$name', '$email', '$departmentid', '$userid', '$phone', '$address')");

    header("Location: teachers.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Teacher</title>
    <link rel="stylesheet" href="../css/style.css?v=1.4">
</head>
<body>

<div class="form-container">
    <div class="form-header">
        <div>
            <h2>Add New Teacher</h2>
            <p class="subtitle">Enter the teacher's details below to register</p>
        </div>
        <a href="teachers.php" class="btn-back">⬅ Back</a>
    </div>

    <form method="post">
        <div class="form-row">
            <div class="input-group">
                <label for="name">Teacher Name</label>
                <input type="text" id="name" name="name" placeholder="E.g. John Doe" required>
            </div>

            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="E.g. john@school.com" required>
            </div>
        </div>

        <div class="form-row">
            <div class="input-group">
                <label for="departmentid">Department</label>
                <select id="departmentid" name="departmentid" required>
                    <option value="">Select Department</option>
                    <?php
                    $deps = $conn->query("SELECT * FROM departments");
                    while($d = $deps->fetch_assoc()){
                        echo "<option value='{$d['id']}'>{$d['name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="input-group">
                <label for="userid">User ID</label>
                <input type="number" id="userid" name="userid" placeholder="System User ID" required>
            </div>
        </div>

        <div class="form-row">
            <div class="input-group full-width">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" placeholder="E.g. 0771234567">
            </div>
        </div>

        <div class="input-group">
            <label for="address">Residential Address</label>
            <textarea id="address" name="address" placeholder="Enter home address here..." rows="3"></textarea>
        </div>

        <button type="submit" name="save" class="btn-submit">➕ Save Teacher Details</button>
    </form>
</div>

</body>
</html>