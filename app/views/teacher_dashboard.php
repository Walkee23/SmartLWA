<?php
session_start();

// Protect access: Only teachers should see this view
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../models/database.php';

// Prepare user data
$first_name = htmlspecialchars($_SESSION['first_name']);
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - SmartLWA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: transform 0.2s;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Sidebar Styling */
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

        /* Active link style */
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

        /* Responsive adjustments for smaller screens */
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
    </style>
</head>

<body>
    <!-- 1. Sidebar Navigation -->
    <div class="sidebar">
        <h3 class="text-center mb-4 text-white">Smart Library</h3>
        <a href="/SmartLWA/app/views/teacher_dashboard.php" class="active">Dashboard</a>
        <a href="/SmartLWA/app/views/my_reservations.php">Reservations</a>
        <a href="/SmartLWA/app/views/my_borrowed_books.php">Borrowed Books</a>
        <a href="/SmartLWA/app/views/available_books.php">Available Books</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <div class="main-content">
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col">
                <h1>Welcome, Prof. <?php echo $first_name; ?>!</h1>
                <p class="lead">Manage your library resources and teaching materials.</p>
            </div>
        </div>

        <!-- Statistics Cards Row -->
        <div class="row mb-5">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card dashboard-card bg-primary text-white">
                    <div class="card-body p-3">
                        <h5 class="card-title">Books Borrowed</h5>
                        <?php
                        // Fetch borrowed count
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM BorrowingRecords 
                                             WHERE user_id = ? AND status = 'borrowed'");
                        $stmt->execute([$user_id]);
                        $borrowed_count = $stmt->fetchColumn();
                        ?>
                        <h2 class="mb-0"><?php echo $borrowed_count; ?></h2>
                        <small>Unlimited books allowed</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card dashboard-card bg-success text-white">
                    <div class="card-body p-3">
                        <h5 class="card-title">Active Reservations</h5>
                        <?php
                        // Fetch reservations count
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Reservations 
                                             WHERE user_id = ? AND status IN ('active', 'ready_for_pickup')");
                        $stmt->execute([$user_id]);
                        $reservations_count = $stmt->fetchColumn();
                        ?>
                        <h2 class="mb-0"><?php echo $reservations_count; ?></h2>
                        <small>Current active reservations</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card dashboard-card bg-danger text-white">
                    <div class="card-body p-3">
                        <h5 class="card-title">Clearance Deadline</h5>
                        <?php
                        // Teacher logic: Clearance Deadline (Semester End)
                        $stmt = $pdo->prepare("SELECT end_date FROM AcademicPeriods WHERE is_active = 1 LIMIT 1");
                        $stmt->execute();
                        $end_date_str = $stmt->fetchColumn();
                        
                        $deadline_text = "N/A";
                        if ($end_date_str) {
                            $deadline = new DateTime($end_date_str);
                            $deadline_text = $deadline->format('M d, Y');
                            $now = new DateTime();
                            if ($deadline < $now) {
                                $deadline_text = "OVERDUE"; // Highlight if past deadline
                            }
                        }
                        ?>
                        <h2 class="mb-0"><?php echo $deadline_text; ?></h2>
                        <small>All books due for clearance</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Borrowed Books List (Uses the shared file logic for display) -->
        <div class="row">
            <div class="col-12">
                <h3>My Current Loans</h3>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Book Title</th>
                                <th>Call No.</th>
                                <th>Date Borrowed</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT b.title, bc.call_number, br.borrow_date, br.status, br.due_date
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
                                    echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) . "</td>";
                                    echo "<td><span class='badge bg-" . $status_class . "'>" . $status_text . "</span></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted">You currently have no borrowed books.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
