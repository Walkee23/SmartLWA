<?php
session_start();

// Include the database connection
require_once __DIR__ . '/../models/database.php';

class AuthController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function login($email, $password)
    {
        // Query to fetch user details
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];

            // Redirect to the appropriate dashboard based on role
            switch ($user['role']) {
                case 'student':
                    header("Location: /SmartLWA/views/student_dashboard.php");
                    break;
                case 'teacher':
                    header("Location: /SmartLWA/views/teacher_dashboard.php");
                    break;
                case 'staff':
                    header("Location: /SmartLWA/views/staff_dashboard.php");
                    break;
                case 'librarian':
                    header("Location: /SmartLWA/views/librarian_dashboard.php");
                    break;
                default:
                    $_SESSION['error'] = "Invalid role.";
                    header("Location: /SmartLWA/views/login.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            header("Location: /SmartLWA/views/login.php");
            exit();
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new AuthController($pdo);
    $auth->login($email, $password);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /SmartLWA/views/login.php");
    exit();
}


?>