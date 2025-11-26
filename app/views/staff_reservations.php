<?php
session_start();

// 1. Protect Access: Only Staff allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: /SmartLWA/views/login.php");
    exit(); 
} 

require_once __DIR__ . '/../models/database.php';
$first_name = htmlspecialchars($_SESSION['first_name'] ?? 'Staff');
$search_term = $_GET['search'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reservations - SmartLWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            height: 100vh; width: 250px; position: fixed; top: 0; left: 0;
            background-color: #1a233b; color: white; padding-top: 20px; z-index: 1000;
        }
        .sidebar-header { padding: 0 25px; margin-bottom: 30px; font-size: 1.2rem; font-weight: 500; }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 16px; color: #b0b3b8; display: block; transition: 0.3s; }
        .sidebar a:hover { color: #fff; background-color: rgba(255,255,255,0.1); }
        .sidebar a.active { color: #fff; background-color: #007bff; font-weight: bold; }
        .main-content { margin-left: 250px; padding: 40px; }
        .card { border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="sidebar-header">Smart Library</div>
        <a href="/SmartLWA/app/views/staff_dashboard.php">Dashboard</a>
        <a href="/SmartLWA/app/views/staff_reservations.php" class="active">Reservations</a>
        <a href="/SmartLWA/app/views/staff_penalties.php">Penalties</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold">Reservation Requests</h1>
                <p class="text-muted">Process active reservations and prepare books for pickup.</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-6">
                <form method="GET" action="/SmartLWA/app/views/staff_reservations.php" class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search Student Name or ID..." 
                           name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="/SmartLWA/app/views/staff_reservations.php" class="btn btn-outline-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Book Title</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Build Query
                            $sql = "
                                SELECT 
                                    r.reservation_id, r.reservation_date, r.status,
                                    u.first_name, u.last_name, u.unique_id,
                                    b.title, b.isbn
                                FROM Reservations r
                                JOIN Users u ON r.user_id = u.user_id
                                JOIN Books b ON r.book_id = b.book_id
                                WHERE r.status IN ('active', 'ready_for_pickup')
                            ";

                            $params = [];
                            if (!empty($search_term)) {
                                $sql .= " AND (u.unique_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR b.title LIKE ?)";
                                $term = '%' . $search_term . '%';
                                $params = [$term, $term, $term, $term];
                            }

                            $sql .= " ORDER BY r.reservation_date ASC";

                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);

                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch()) {
                                    $status_class = ($row['status'] == 'active') ? 'bg-warning text-dark' : 'bg-success';
                                    $date = date('M d, Y', strtotime($row['reservation_date']));
                                    
                                    $student_id = htmlspecialchars($row['unique_id']);
                                    $book_title = htmlspecialchars($row['title'], ENT_QUOTES);
                                    // Fetch ISBN for autofill
                                    $book_isbn  = htmlspecialchars($row['isbn'] ?? '');
                                    
                                    echo "<tr>";
                                    echo "<td>{$date}</td>";
                                    echo "<td>" . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . "</td>";
                                    echo "<td><span class='badge bg-light text-dark border'>{$student_id}</span></td>";
                                    echo "<td>{$book_title}</td>";
                                    echo "<td><span class='badge {$status_class}'>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</span></td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-primary' 
                                                onclick='openFulfillModal(\"{$student_id}\", \"{$book_title}\", \"{$book_isbn}\")'>
                                                <i class='fas fa-check-circle'></i> Fulfill / Borrow
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-5'>No active reservations found matching your search.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='6' class='text-danger'>Error loading reservations.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="fulfillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/SmartLWA/app/controllers/CirculationController.php" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Fulfill Reservation</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="borrow">
                        
                        <div class="alert alert-info">
                            <strong>Book:</strong> <span id="modal_book_title"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Student ID (Auto-filled)</label>
                            <input type="text" class="form-control" name="user_id_input" id="modal_student_id" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Book ISBN</label>
                            <input type="text" class="form-control" name="book_id_input" id="modal_book_isbn" placeholder="Enter Book ISBN" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Borrow</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openFulfillModal(studentId, bookTitle, isbn) {
            // Autofill Student ID
            document.getElementById('modal_student_id').value = studentId;
            // Display Title
            document.getElementById('modal_book_title').innerText = bookTitle;
            // Autofill ISBN
            document.getElementById('modal_book_isbn').value = isbn;
            
            var myModal = new bootstrap.Modal(document.getElementById('fulfillModal'));
            myModal.show();
        }
    </script>
</body>
</html>