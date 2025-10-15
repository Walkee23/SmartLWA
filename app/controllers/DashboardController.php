<?php
session_start();

// Protect access
if (!isset($_SESSION['user_id'])) {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

// Redirect to the appropriate dashboard based on role
switch ($_SESSION['role']) {
    case 'student':
        include_once __DIR__ . '/../views/student_dashboard.php';
        break;
    case 'teacher':
        include_once __DIR__ . '/../views/teacher_dashboard.php';
        break;
    case 'staff':
        include_once __DIR__ . '/../views/staff_dashboard.php';
        break;
    case 'librarian':
        include_once __DIR__ . '/../views/librarian_dashboard.php';
        break;
    default:
        $_SESSION['error'] = "Invalid role.";
        header("Location: /SmartLWA/views/login.php");
        exit();
}
?>