<?php
/*
 logout.php
 - User session destroy කරනවා
 - Login page එකට redirect කරනවා
*/

session_start();

// Session variables clear
session_unset();

// Session destroy
session_destroy();

// Login page එකට යවන්න
header("Location: login.php");
exit();
?>
