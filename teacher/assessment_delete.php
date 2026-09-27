<?php
include "../config/db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php");
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS assessment_questions (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, question_text TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (assessment_id))");
$conn->query("CREATE TABLE IF NOT EXISTS assessment_students (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, student_id INT NOT NULL, assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE (assessment_id, student_id), INDEX (assessment_id), INDEX (student_id))");
$conn->query("CREATE TABLE IF NOT EXISTS assessment_answers (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, question_id INT NOT NULL, student_id INT NOT NULL, selected_option CHAR(1) NOT NULL, is_correct TINYINT(1) NOT NULL DEFAULT 0, answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY student_question (assessment_id, question_id, student_id), INDEX (student_id))");
$conn->query("CREATE TABLE IF NOT EXISTS assessment_attempts (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, student_id INT NOT NULL, score INT NOT NULL DEFAULT 0, total_questions INT NOT NULL DEFAULT 0, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY one_attempt (assessment_id, student_id), INDEX (student_id))");

$conn->query("CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    assessment_type VARCHAR(30) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    due_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$selected_department = isset($_GET['department_id']) ? (int)$_GET['department_id'] : ($_SESSION['departmentid'] ?? 0);
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$assessment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assessment_id > 0) {
    $conn->query("DELETE FROM assessment_questions WHERE assessment_id = '$assessment_id'");
    $conn->query("DELETE FROM assessment_students WHERE assessment_id = '$assessment_id'");
    $conn->query("DELETE FROM assessment_answers WHERE assessment_id = '$assessment_id'");
    $conn->query("DELETE FROM assessment_attempts WHERE assessment_id = '$assessment_id'");
    $conn->query("DELETE FROM assessments WHERE id = '$assessment_id' AND department_id = '$selected_department' AND class_id = '$selected_class'");
}

header("Location: view_assessments.php?department_id=$selected_department&class_id=$selected_class");
exit();
