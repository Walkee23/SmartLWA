<?php
session_start();

// Protect access: Only logged-in users (students/teachers) should see this view
if (!isset($_SESSION['user_id'])) {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../models/database.php';

$user_id = $_SESSION['user_id'];

// --- RECOMMENDED FEATURE: Pre-fetch current user's active reservations for validation ---
$active_reservations = [];
try {
    $stmt = $pdo->prepare("SELECT book_id FROM Reservations 
                           WHERE user_id = ? AND status IN ('active', 'ready_for_pickup')");
    $stmt->execute([$user_id]);
    $active_reservations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Optionally log this error
}
// ---------------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Books - SmartLWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Sidebar Styling (Copied for consistency) */
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
        
        /* Active link style for Available Books */
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
        
        .books-card {
            min-height: 80vh;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h3 class="text-center mb-4 text-white">Smart Library</h3>
        <a href="/SmartLWA/app/views/student_dashboard.php">Dashboard</a>
        <a href="/SmartLWA/app/views/my_reservations.php">Reservations</a>
        <a href="/SmartLWA/app/views/my_borrowed_books.php">Borrowing</a>
        <a href="/SmartLWA/app/views/available_books.php" class="active">Available Books</a>
        <a href="#">Penalties</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <div class="main-content">
        <h1 class="mb-4">Available Books</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow books-card">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Book ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Availability</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query to select all non-archived books and count available copies
                            $stmt = $pdo->prepare("
                                SELECT
                                    b.book_id,
                                    b.title,
                                    b.author,
                                    (SELECT COUNT(*) FROM BookCopies WHERE book_id = b.book_id AND status = 'available') AS available_copies
                                FROM Books b
                                WHERE b.archived = FALSE
                                ORDER BY b.title ASC
                            ");
                            $stmt->execute();

                            if ($stmt->rowCount() > 0) {
                                while ($book = $stmt->fetch()) {
                                    $book_id = $book['book_id'];
                                    $is_available = $book['available_copies'] > 0;
                                    $has_active_reservation = in_array($book_id, $active_reservations);
                                    
                                    // Determine button state and text
                                    $button_disabled = $has_active_reservation ? 'disabled' : '';
                                    $button_text = $has_active_reservation ? 'Reserved' : 'Reserve';
                                    $button_tooltip = $has_active_reservation ? 'You already have an active reservation for this book.' : '';
                                    
                                    // Determine availability badge
                                    $badge_class = $is_available ? 'success' : 'warning';
                                    $availability_text = $is_available ? "{$book['available_copies']} available" : 'No copies available';

                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($book_id) . "</td>";
                                    echo "<td>" . htmlspecialchars($book['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($book['author']) . "</td>";
                                    echo '<td><span class="badge bg-' . $badge_class . '">' . $availability_text . '</span></td>';
                                    echo '<td>';
                                    
                                    // Action Button linking to the ReservationController
                                    echo '<a href="/SmartLWA/app/controllers/ReservationController.php?action=reserve&book_id=' . $book_id . '" 
                                            class="btn btn-sm btn-primary" ' . $button_disabled . '
                                            title="' . $button_tooltip . '" data-bs-toggle="tooltip">';
                                    echo $button_text;
                                    echo '</a>';
                                    
                                    echo '</td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">No books found in the catalog.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips (for the 'Reserved' button)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>

</html>