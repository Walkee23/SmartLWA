<?php
session_start();

// Protect access: Only students/teachers should see this view
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'student' && $_SESSION['role'] !== 'teacher')) {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../models/database.php';

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Borrowed Books - SmartLWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #1a233b;
            color: white;
            padding-top: 20px;
        }

        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 16px;
            color: #ccc;
            display: block;
        }

        .sidebar a:hover:not(.active) {
            color: #fff;
            background-color: #0056b3;
        }
        
        /* Active link style for Borrowed Books */
        .sidebar a.active {
            color: #fff;
            background-color: #007bff;
            font-weight: bold;
        }
        
        /* Content area adjustment */
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding-top: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
        /* Card to match the vertical space of the other views */
        .borrowing-card {
            min-height: 85vh; 
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h3 class="text-center mb-4 text-white">Smart Library</h3>
        <a href="/SmartLWA/app/views/student_dashboard.php">Dashboard</a>
        <a href="/SmartLWA/app/views/my_reservations.php">Reservations</a>
        <a href="/SmartLWA/app/views/my_borrowed_books.php" class="active">Borrowed Books</a>
        <a href="/SmartLWA/app/views/available_books.php">Available Books</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <div class="main-content">
        <h1 class="mb-4">My Borrowed Books</h1>
        <p class="lead text-muted">A list of all books currently checked out to your account. Remember to return them by the due date to avoid penalties.</p>

        <div class="card shadow borrowing-card">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Book Title</th>
                                <th>Call Number</th>
                                <th>Date Borrowed</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query to fetch active borrowed books for the user
                            $stmt = $pdo->prepare("
                                SELECT 
                                    b.title, 
                                    bc.call_number, 
                                    br.borrow_date, 
                                    br.due_date
                                FROM BorrowingRecords br
                                JOIN BookCopies bc ON br.copy_id = bc.copy_id
                                JOIN Books b ON bc.book_id = b.book_id
                                WHERE br.user_id = ? AND br.status = 'borrowed'
                                ORDER BY br.due_date ASC
                            ");
                            $stmt->execute([$user_id]);

                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch()) {
                                    $due_date = new DateTime($row['due_date']);
                                    $now = new DateTime();
                                    $is_overdue = $due_date < $now;
                                    
                                    $status_class = $is_overdue ? 'danger' : 'primary';
                                    $status_text = $is_overdue ? 'Overdue' : 'Borrowed';

                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['call_number']) . "</td>";
                                    echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($row['borrow_date']))) . "</td>";
                                    echo '<td><span class="' . ($is_overdue ? 'text-danger fw-bold' : 'text-primary') . '">' . htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) . '</span></td>';
                                    echo '<td><span class="badge bg-' . $status_class . '">' . $status_text . '</span></td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted">You currently have no books checked out.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-white text-end border-0 pt-0">
                <a href="/SmartLWA/app/views/available_books.php" class="btn btn-primary px-4 py-2">Browse More Books</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>