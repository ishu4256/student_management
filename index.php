<?php
include "config/db.php";

/*
 index.php
 - User login වෙලා නම් role අනුව dashboard එකට යවනවා
 - Login වෙලා නැත්නම් login.php එකට යවනවා
*/

if (!isset($_SESSION['role'])) {
    // Login වෙලා නැත්නම්
    header("Location: login.php");
    exit();
}

// Role check කරලා redirect
if ($_SESSION['role'] == 'admin') {
    header("Location: admin/dashboard.php");
} elseif ($_SESSION['role'] == 'teacher') {
    header("Location: teacher/dashboard.php");
} elseif ($_SESSION['role'] == 'student') {
    header("Location: student/dashboard.php");
} else {
    // Unknown role
    header("Location: login.php");
}
exit();
?>
