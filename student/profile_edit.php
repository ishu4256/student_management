<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user = $conn->query("SELECT * FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
$user_email = $conn->real_escape_string($user['email'] ?? '');
$student_result = $conn->query("SELECT s.* FROM students s WHERE s.userid = $user_id OR ('$user_email' <> '' AND s.email = '$user_email') ORDER BY (s.userid = $user_id) DESC LIMIT 1");
$student = $student_result ? $student_result->fetch_assoc() : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $sex = $conn->real_escape_string(trim($_POST['sex'] ?? ''));
    $date_of_birth = $conn->real_escape_string(trim($_POST['date_of_birth'] ?? ''));
    $parents_name = $conn->real_escape_string(trim($_POST['parents_name'] ?? ''));

    if (!$student || $student_id !== (int)$student['id'] || $name === '' || $email === '') {
        $error = 'Please enter a valid name and email.';
    } else {
        $duplicate_email = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $user_id");
        if ($duplicate_email && $duplicate_email->num_rows > 0) {
            $error = 'This email address is already used by another account.';
        } else {
            $updated = $conn->query("UPDATE students SET name='$name', email='$email', phone='$phone', address='$address', sex='$sex', date_of_birth='$date_of_birth', parents_name='$parents_name' WHERE id=$student_id AND (userid=$user_id OR email='$user_email')");
            $user_updated = $conn->query("UPDATE users SET email='$email' WHERE id=$user_id");
            if ($updated && $user_updated) {
                header('Location: profile.php?success=updated');
                exit();
            }
            $error = 'Unable to update your profile. Please try again.';
        }
    }
}

if (!$student) {
    $student = ['id' => 0, 'name' => '', 'email' => $user['email'] ?? '', 'phone' => '', 'address' => '', 'sex' => '', 'date_of_birth' => '', 'parents_name' => ''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit My Profile</title>
    <link rel="stylesheet" href="../css/style.css?v=3.7">
</head>
<body>
<div class="dashboard profile-edit-page">
    <div class="dashboard-header"><div><span class="eyebrow">PERSONAL DETAILS</span><h2>Edit My Profile</h2><p class="subtitle">Keep your contact and personal information up to date.</p></div><a href="profile.php" class="btn-back">Back to Profile</a></div>
    <?php if ($error !== ''): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" class="profile-edit-form">
        <input type="hidden" name="student_id" value="<?php echo (int)$student['id']; ?>">
        <div class="form-row"><div class="input-group"><label for="name">Full name</label><input id="name" type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required></div><div class="input-group"><label for="email">Email</label><input id="email" type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required></div></div>
        <div class="form-row"><div class="input-group"><label for="phone">Phone</label><input id="phone" type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"></div><div class="input-group"><label for="sex">Sex</label><select id="sex" name="sex"><option value="">Select</option><option value="Male" <?php echo ($student['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option><option value="Female" <?php echo ($student['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option></select></div></div>
        <div class="form-row"><div class="input-group"><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>"></div><div class="input-group"><label for="parents_name">Parent / guardian name</label><input id="parents_name" type="text" name="parents_name" value="<?php echo htmlspecialchars($student['parents_name'] ?? ''); ?>"></div></div>
        <div class="input-group"><label for="address">Address</label><textarea id="address" name="address" rows="4"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea></div>
        <button type="submit" name="save_profile" class="btn-primary">Save profile changes</button>
    </form>
</div>
</body>
</html>
