<?php
session_start();
if (isset($_SESSION['error'])) {
    echo "<p style='color:red; text-align: center; font-weight: bold;'>" . $_SESSION['error'] . "</p>";
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartLWA</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Login Container */
        .login-container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 360px;
        }

        .login-container h1 {
            text-align: center;
            color: #333333;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        /* Form Labels */
        .login-container label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555555;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Input Fields */
        .login-container input {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid #cccccc;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .login-container input:focus {
            border-color: #007bff;
            outline: none;
        }

        /* Button */
        .login-container button {
            width: 100%;
            padding: 0.8rem;
            background-color: #007bff;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .login-container button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        /* Error Message */
        .error-message {
            color: #d9534f;
            text-align: center;
            font-weight: bold;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo "<p class='error-message'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }
        ?>
        <form method="POST" action="/SmartLWA/app/controllers/AuthController.php">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>