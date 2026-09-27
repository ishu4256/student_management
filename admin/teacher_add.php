<?php
include "../config/db.php";

if (isset($_POST['save'])) {

    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
    $departmentid = (int)($_POST['departmentid'] ?? 0);
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $user_check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
        if ($user_check && $user_check->num_rows > 0) {
            $error = 'Username or email is already taken.';
        } else {
            $password_hash = $conn->real_escape_string(password_hash($password, PASSWORD_DEFAULT));
            $conn->query("INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password_hash', 'teacher')");
            $userid = $conn->insert_id;

            $conn->query("INSERT INTO teachers (name, email, departmentid, userid, phone, address)
                VALUES ('$name', '$email', '$departmentid', '$userid', '$phone', '$address')");

            header("Location: teachers.php?success=added");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Teacher</title>
    <link rel="stylesheet" href="../css/style.css?v=4.0">
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

        <?php if (!empty($error)): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

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
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="E.g. johnteacher" required>
            </div>
        </div>

        <div class="form-row">
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Retype password" required>
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