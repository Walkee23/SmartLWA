<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: /SmartLWA/views/student_dashboard.php");
    } else {
        header("Location: /SmartLWA/app/controllers/DashboardController.php");
    }
    exit();
} else {
    header("Location: /SmartLWA/views/login.php");
    exit();
}
