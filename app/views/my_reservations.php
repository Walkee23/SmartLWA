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
$user_role = $_SESSION['role'];

// --- DYNAMIC CONTENT SETUP ---
// Determine Dashboard Link based on role
$dashboard_link = ($user_role === 'teacher') 
    ? '/SmartLWA/app/views/teacher_dashboard.php' 
    : '/SmartLWA/app/views/student_dashboard.php';
// -----------------------------
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - SmartLWA</title>
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
        
        /* Active link style for Reservations */
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
        /* FIX: Use min-height to ensure card is tall enough, and rely on flex to push the footer */
        .reservations-card {
            min-height: 85vh;
        }
    </style>
</head>

<body>
    <!-- 1. Sidebar Navigation (Uses dynamic dashboard link) -->
    <div class="sidebar">
        <h3 class="text-center mb-4 text-white">Smart Library</h3>
        <!-- Dynamic Dashboard link -->
        <a href="<?php echo $dashboard_link; ?>">Dashboard</a>
        <a href="/SmartLWA/app/views/my_reservations.php" class="active">Reservations</a>
        <!-- Shared Borrowed Books link -->
        <a href="/SmartLWA/app/views/my_borrowed_books.php">Borrowed Books</a> 
        <a href="/SmartLWA/app/views/available_books.php">Available Books</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <div class="main-content">
        <h1 class="mb-4">My Reservations</h1>
        <p class="lead text-muted">All active reservations for your account. You will receive a notification when a copy is ready for pickup.</p>

        <div class="card shadow reservations-card d-flex flex-column">
            
            <div class="card-body p-4 flex-grow-1">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Book ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Date Reserved</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query to fetch active reservations for the user
                            $stmt = $pdo->prepare("
                                SELECT 
                                    r.reservation_id, 
                                    r.book_id, 
                                    r.reservation_date, 
                                    r.status, 
                                    r.expiry_date,
                                    b.title, 
                                    b.author
                                FROM Reservations r
                                JOIN Books b ON r.book_id = b.book_id
                                WHERE r.user_id = ? AND r.status IN ('active', 'ready_for_pickup')
                                ORDER BY r.reservation_date ASC
                            ");
                            $stmt->execute([$user_id]);

                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch()) {
                                    $status_class = match ($row['status']) {
                                        'ready_for_pickup' => 'success',
                                        'active' => 'warning',
                                        default => 'secondary',
                                    };
                                    $action_button = ($row['status'] == 'active') ?
                                        '<a href="/SmartLWA/app/controllers/ReservationController.php?action=cancel&res_id=' . $row['reservation_id'] . '" class="btn btn-sm btn-outline-danger">Cancel</a>' :
                                        '<span class="text-success small fw-bold">Ready for Pickup</span>';

                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['book_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                                    echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($row['reservation_date']))) . "</td>";
                                    echo '<td><span class="badge bg-' . $status_class . '">' . ucfirst($row['status']) . '</span></td>';
                                    echo "<td>" . $action_button . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center text-muted">You have no active reservations. Reserve a book now!</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-white text-end border-0 pt-0">
                <a href="/SmartLWA/app/views/available_books.php" class="btn btn-primary px-4 py-2">Reserve Book</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
