<?php
session_start();

// 1. Protect Access: Only Staff allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

// 2. Include Database Connection
require_once __DIR__ . '/../models/database.php';

// Prepare user data
$first_name = htmlspecialchars($_SESSION['first_name'] ?? 'Staff');

// Check if we have clearance data to show (from the controller)
$clearanceData = $_SESSION['clearance_data'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - SmartLWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #1a233b;
            color: white;
            padding-top: 20px;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 25px;
            margin-bottom: 30px;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            font-size: 16px;
            color: #b0b3b8;
            display: block;
            transition: 0.3s;
        }

        .sidebar a:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar a.active {
            color: #fff;
            background-color: #007bff;
            font-weight: bold;
        }

        .main-content {
            margin-left: 250px;
            padding: 40px;
        }

        .action-btn {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 500;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            width: 100%;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .btn-borrow {
            background-color: #2563eb;
        }

        .btn-return {
            background-color: #16a34a;
        }

        .btn-clear {
            background-color: #06b6d4;
        }

        .info-card {
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            min-height: 500px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">Smart Library</div>
        <a href="/SmartLWA/app/views/staff_dashboard.php" class="active">Dashboard</a>
        <a href="/SmartLWA/app/views/staff_reservations.php">Reservations</a>
        <a href="/SmartLWA/app/views/staff_penalties.php">Penalties</a> <!-- Link Updated -->
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true" style="margin-top: 50px;">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="mb-5">
            <h1 class="fw-bold fst-italic">Welcome, <?php echo $first_name; ?>!</h1>
            <p class="text-muted">Facilitate borrowing, returns, and user clearances here.</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="action-btn btn-borrow" data-bs-toggle="modal" data-bs-target="#borrowModal">Borrow Book</div>
            </div>
            <div class="col-md-4">
                <div class="action-btn btn-return" data-bs-toggle="modal" data-bs-target="#returnModal">Return Book</div>
            </div>
            <div class="col-md-4">
                <div class="action-btn btn-clear" data-bs-toggle="modal" data-bs-target="#clearanceModal">Clear User</div>
            </div>
        </div>

        <!-- Borrower Status Table -->
        <div class="info-card p-4">
            <h5 class="mb-3">Recent Borrower Activity</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Borrowed Books</th>
                            <th>Penalties</th>
                            <th>Clearance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT u.user_id, u.unique_id, u.first_name, u.last_name, u.role,
                                    (SELECT COUNT(*) FROM BorrowingRecords br WHERE br.user_id = u.user_id AND br.status = 'borrowed') as borrowed_count,
                                    (SELECT COALESCE(SUM(amount), 0.00) FROM Penalties p WHERE p.user_id = u.user_id AND p.is_paid = 0) as total_penalty
                                FROM Users u WHERE u.role IN ('student', 'teacher') ORDER BY borrowed_count DESC, total_penalty DESC LIMIT 20";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute();

                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch()) {
                                    $is_cleared = ($row['borrowed_count'] == 0 && $row['total_penalty'] == 0.00);
                                    $status_badge = $is_cleared ? 'bg-success' : 'bg-warning text-dark';
                                    $status_text = $is_cleared ? 'Ready for Clearance' : 'Pending';
                                    $penalty_class = $row['total_penalty'] > 0 ? 'text-danger fw-bold' : 'text-success';

                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['unique_id']) . "</td>
                                        <td>" . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . "</td>
                                        <td><span class='badge bg-secondary'>" . ucfirst($row['role']) . "</span></td>
                                        <td><strong>" . $row['borrowed_count'] . "</strong> <span class='text-muted small'>/ " . ($row['role'] == 'student' ? '3' : '∞') . "</span></td>
                                        <td class='$penalty_class'>$" . number_format($row['total_penalty'], 2) . "</td>
                                        <td><span class='badge $status_badge'>$status_text</span></td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted py-4'>No active students or teachers found.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='6' class='text-danger'>Error loading data.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. CLEARANCE MODAL -->
    <div class="modal fade" id="clearanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <!-- FORM A: Fetch Status -->
                <form action="/SmartLWA/app/controllers/CirculationController.php" method="POST">
                    <div class="modal-header" style="background-color: #06b6d4; color: white;">
                        <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>User Clearance</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="clearance">

                        <div class="mb-3">
                            <label class="form-label">User ID</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="clearance_user_id"
                                    placeholder="e.g. S2023-001"
                                    value="<?php echo $clearanceData['unique_id'] ?? ''; ?>">
                                <button class="btn btn-outline-secondary" type="submit">Fetch Status</button>
                            </div>
                        </div>

                        <!-- Status Preview (Displays only if data was fetched) -->
                        <?php if ($clearanceData): ?>
                            <div class="card bg-light mb-3">
                                <div class="card-body p-3">
                                    <h6 class="card-title fw-bold"><?php echo htmlspecialchars($clearanceData['name']); ?></h6>
                                    <hr class="my-2">

                                    <?php if ($clearanceData['is_cleared']): ?>
                                        <div class="alert alert-success mb-0 p-2">
                                            <i class="fas fa-check-circle"></i> <strong>READY FOR CLEARANCE</strong><br>
                                            <small>Eligible. No active loans or unpaid fines.</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning mb-0 p-2">
                                            <i class="fas fa-exclamation-triangle"></i> <strong>NOT ELIGIBLE</strong><br>
                                            <small>User has outstanding liabilities.</small>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Active Loans:</span>
                                            <strong class="<?php echo $clearanceData['loan_count'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $clearanceData['loan_count']; ?>
                                            </strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Unpaid Fines:</span>
                                            <strong class="<?php echo $clearanceData['unpaid_fines'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                                $<?php echo number_format($clearanceData['unpaid_fines'], 2); ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </form>

                <!-- FORM B: Process/Confirm Clearance (Only if status fetched & cleared) -->
                <?php if ($clearanceData && $clearanceData['is_cleared']): ?>
                    <form action="/SmartLWA/app/controllers/CirculationController.php" method="POST">
                        <input type="hidden" name="action" value="process_clearance">
                        <input type="hidden" name="user_id" value="<?php echo $clearanceData['user_id']; ?>">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn text-white" style="background-color: #06b6d4;">
                                Mark as Cleared
                            </button>
                        </div>
                    </form>
                <?php elseif ($clearanceData && !$clearanceData['is_cleared']): ?>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-light text-danger border-danger" disabled>Resolve Issues First</button>
                    </div>
                <?php else: ?>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-light text-muted" disabled>Fetch Status First</button>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Borrow/Return Modals (Simplified placeholders for brevity, assume fetched from previous steps) -->
    <!-- Note: In your actual file, keep the full Borrow/Return modals code here -->
    <?php include 'partials/borrow_return_modals.php'; // Or paste them back in if you don't have partials 
    ?>

    <!-- 1. BORROW BOOK MODAL -->
    <div class="modal fade" id="borrowModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/SmartLWA/app/controllers/CirculationController.php" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-book-reader me-2"></i>Borrow Book</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="borrow">
                        <div class="mb-3">
                            <label class="form-label">Borrower User ID</label>
                            <input type="text" class="form-control" name="user_id_input" placeholder="e.g. S2023-001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Book ISBN</label>
                            <input type="text" class="form-control" name="book_id_input" placeholder="Enter ISBN" required>
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

    <!-- 2. RETURN BOOK MODAL -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/SmartLWA/app/controllers/CirculationController.php" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-undo me-2"></i>Return Book</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="return">
                        <div class="mb-3">
                            <label class="form-label">Borrower User ID</label>
                            <input type="text" class="form-control" name="user_id_input" placeholder="e.g. S2023-001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Book ISBN</label>
                            <input type="text" class="form-control" name="book_id_input" placeholder="Enter ISBN to Return" required>
                        </div>

                        <hr>
                        <label class="form-label fw-bold">Book Condition:</label>
                        <div class="d-flex gap-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" id="cond_good" value="good" checked>
                                <label class="form-check-label text-success" for="cond_good">Good Condition</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" id="cond_damaged" value="damaged">
                                <label class="form-check-label text-warning" for="cond_damaged">Damaged (Penalty Apply)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" id="cond_lost" value="lost">
                                <label class="form-check-label text-danger" for="cond_lost">Lost Book (Full Cost)</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Process Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Auto-Open Modal Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Check if we have clearance data in PHP session passed to page
            <?php if ($clearanceData): ?>
                var clearanceModal = new bootstrap.Modal(document.getElementById('clearanceModal'));
                clearanceModal.show();
            <?php endif; ?>
        });
    </script>
</body>

</html>