<?php
include "../config/db.php";

// Table එකේ Delete ක්ලික් කර කෙලින්ම ID එකක් ආවොත්
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $conn->query("DELETE FROM teachers WHERE userid='$id'");
    header("Location: teachers.php?success=deleted");
    exit();
}

// Form එකෙන් ID එක දමා Delete කලොත්
if (isset($_POST['delete'])) {
    $id = $_POST['userid'];
    
    // මුලින්ම එවැනි ID එකක් ඉන්නවද බලනවා
    $check = $conn->query("SELECT * FROM teachers WHERE userid='$id'");
    if($check->num_rows > 0) {
        $conn->query("DELETE FROM teachers WHERE userid='$id'");
        header("Location: teachers.php?success=deleted");
        exit();
    } else {
        $error = "❌ No teacher found with that User ID!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Teacher</title>
    <link rel="stylesheet" href="../css/style.css?v=1.5">
</head>
<body>

<div class="form-container" style="max-width: 500px;">
    <div class="form-header">
        <div>
            <h2>Delete Teacher</h2>
            <p class="subtitle" style="color: var(--danger-color);">Warning: This action cannot be undone!</p>
        </div>
        <a href="teachers.php" class="btn-back">⬅ Back</a>
    </div>

    <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="post" onsubmit="return confirm('Are you absolutely sure you want to delete this teacher?');">
        <div class="input-group">
            <label>Enter Teacher User ID to Remove</label>
            <input type="number" name="userid" placeholder="E.g. 5" required>
        </div>
        <button type="submit" name="delete" class="btn-submit" style="background: var(--danger-color) !important;">🗑️ Permanent Delete</button>
    </form>
</div>

</body>
</html>