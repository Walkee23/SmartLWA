<?php
session_start();

// protect access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../models/database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SmartLWA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .dashboard-card {
            transition: transform 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SmartLWA Library</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/SmartLWA/app/views/student_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SmartLWA/app/views/available_books.php">Available Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SmartLWA/app/views/my_borrowed_books.php">My Borrowed Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/SmartLWA/app/views/my_reservations.php">My Reservations</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3 text-white">
                        Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . " " . $_SESSION['last_name']); ?>
                    </span>
                    <a href="/SmartLWA/app/controllers/AuthController.php?logout=true"
                        class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <!-- Main Content -->
    <div class="container">
        <!-- Statistics Cards Row -->
        <div class="row justify-content-end mb-4">
            <div class="col-md-3">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body p-2">
                        <h6 class="card-title">Books Borrowed</h6>
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM BorrowingRecords 
                                             WHERE user_id = ? AND status = 'borrowed'");
                        $stmt->execute([$_SESSION['user_id']]);
                        $borrowed_count = $stmt->fetchColumn();
                        ?>
                        <h3><?php echo $borrowed_count; ?>/3</h3>
                        <small>Maximum allowed: 3 books</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body p-2">
                        <h6 class="card-title">Active Reservations</h6>
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Reservations 
                                             WHERE user_id = ? AND status = 'active'");
                        $stmt->execute([$_SESSION['user_id']]);
                        $reservations_count = $stmt->fetchColumn();
                        ?>
                        <h3><?php echo $reservations_count; ?></h3>
                        <small>Current reservations</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card dashboard-card bg-info text-white">
                    <div class="card-body p-2">
                        <h6 class="card-title">Due Soon</h6>
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM BorrowingRecords 
                                             WHERE user_id = ? AND status = 'borrowed' 
                                             AND due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)");
                        $stmt->execute([$_SESSION['user_id']]);
                        $due_soon_count = $stmt->fetchColumn();
                        ?>
                        <h3><?php echo $due_soon_count; ?></h3>
                        <small>Books due within 3 days</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col">
                <h2>Welcome to Your Library Dashboard</h2>
                <p class="lead">Manage your borrowed books and reservations all in one place.</p>
            </div>
        </div>

        <!-- Create my_borrowed_books.php for the borrowed books view -->
        <div class="row">
            <div class="col">
                <h3>My Borrowed Books</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date Borrowed</th>
                                <th>Book Title</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT b.title, br.borrow_date, br.status, br.due_date
                                FROM BorrowingRecords br
                                JOIN BookCopies bc ON br.copy_id = bc.copy_id
                                JOIN Books b ON bc.book_id = b.book_id
                                WHERE br.user_id = ?
                                ORDER BY br.borrow_date DESC
                            ");
                            $stmt->execute([$_SESSION['user_id']]);
                            while ($row = $stmt->fetch()) {
                                $due_date = new DateTime($row['due_date']);
                                $now = new DateTime();
                                $is_overdue = $due_date < $now;

                                echo "<tr>";
                                echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($row['borrow_date']))) . "</td>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) . "</td>";
                                echo "<td><span class='badge bg-" .
                                    ($is_overdue ? 'danger' : 'primary') . "'>" .
                                    ($is_overdue ? 'Overdue' : 'Borrowed') . "</span></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </

                <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>