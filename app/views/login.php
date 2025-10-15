<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartLWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom Styles to match the dark card and central layout */
        body {
            /* Light gray background for the overall page */
            background-color: #f0f2f5;
        }

        .login-card {
            /* Dark blue/black background for the card as per the design */
            background-color: #1a233b;
            /* A deep, dark blue-gray */
            color: #ffffff;
            /* White text for contrast */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            /* Stronger shadow for depth */
        }

        .form-label {
            /* Labels on the dark background */
            color: #ccc;
        }

        .form-control {
            /* Input fields should contrast with the dark card */
            background-color: #313a52;
            /* Slightly lighter than the card background */
            border: 1px solid #495470;
            color: #ffffff;
        }

        .form-control:focus {
            /* Highlight on focus */
            background-color: #313a52;
            border-color: #007bff;
            /* Use primary color for focus highlight */
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
            color: #ffffff;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center vh-100 p-3">
    <div class="card login-card w-100" style="max-width: 380px;">
        <div class="card-body p-5">
            <h4 class="card-title text-center mb-4">Smart Library Login</h4>

            <?php
            // Display error message if set
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger text-center" role="alert">';
                echo htmlspecialchars($_SESSION['error']);
                echo '</div>';
                unset($_SESSION['error']);
            }
            ?>

            <form method="POST" action="/SmartLWA/app/controllers/AuthController.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                        required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                        required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Login</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>