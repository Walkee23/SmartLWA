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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Returns - SmartLWA</title>
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

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">Smart Library</div>
        <a href="/SmartLWA/app/views/staff_dashboard.php">Dashboard</a>
        <a href="/SmartLWA/app/views/staff_reservations.php">Reservations</a>
        <a href="/SmartLWA/app/views/staff_returns.php" class="active">Returns</a>
        <a href="/SmartLWA/app/views/staff_penalties.php">Penalties</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold">Book Returns</h1>
                <p class="text-muted">View active loans and process book returns.</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <form method="GET" action="/SmartLWA/app/views/staff_returns.php" class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search Student ID or Name..." 
                           name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="/SmartLWA/app/views/staff_returns.php" class="btn btn-outline-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Active Loans Table -->
        <div class="card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User ID</th>
                            <th>Student Name</th>
                            <th>Books Borrowed</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Build Query: Group active loans by User
                            $sql = "
                                SELECT 
                                    u.user_id, u.unique_id, u.first_name, u.last_name,
                                    COUNT(br.record_id) as borrowed_count
                                FROM BorrowingRecords br
                                JOIN Users u ON br.user_id = u.user_id
                                WHERE br.status = 'borrowed'
                            ";

                            $params = [];
                            if (!empty($search_term)) {
                                $sql .= " AND (u.unique_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                                $term = '%' . $search_term . '%';
                                $params = [$term, $term, $term];
                            }

                            $sql .= " GROUP BY u.user_id ORDER BY borrowed_count DESC";
                            
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);

                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch()) {
                                    $userId = $row['user_id'];
                                    
                                    echo "<tr>";
                                    echo "<td><span class='badge bg-light text-dark border'>{$row['unique_id']}</span></td>";
                                    echo "<td>" . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . "</td>";
                                    echo "<td><span class='badge bg-primary fs-6'>{$row['borrowed_count']}</span></td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-info text-white' onclick='fetchUserLoans($userId)'>
                                                <i class='fas fa-book-reader'></i> View Details
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center text-muted py-5'>No active loans found.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='4' class='text-danger'>Error loading data.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- LOAN DETAILS MODAL -->
    <div class="modal fade" id="loanDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-book me-2"></i>Borrowed Books</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 id="modal_user_name" class="mb-0">Student Name</h4>
                        <span class="badge bg-secondary fs-6" id="modal_user_unique_id">ID: 000</span>
                    </div>
                    <hr>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="modal_loans_body">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- PROCESS RETURN MODAL (Autofilled) -->
    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/SmartLWA/app/controllers/CirculationController.php" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Process Return</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="return">
                        
                        <div class="mb-3">
                            <label class="form-label">Borrower User ID</label>
                            <input type="text" class="form-control" name="user_id_input" id="return_user_id" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Book ISBN</label>
                            <input type="text" class="form-control" name="book_id_input" id="return_book_isbn" readonly>
                        </div>
                        
                        <hr>
                        <label class="form-label fw-bold">Condition & Penalties:</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" value="good" checked>
                                <label class="form-check-label">
                                    <span class="fw-bold text-success">Good Condition</span> 
                                    <span class="text-muted ms-2">- No penalty</span>
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" value="overdue">
                                <label class="form-check-label">
                                    <span class="fw-bold text-warning">Overdue</span>
                                    <span class="text-danger ms-2 fw-bold">+ $50.00 Fine</span>
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" value="damaged">
                                <label class="form-check-label">
                                    <span class="fw-bold text-warning">Damaged</span>
                                    <span class="text-danger ms-2 fw-bold">+ 50% of Book Cost</span>
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="condition" value="lost">
                                <label class="form-check-label">
                                    <span class="fw-bold text-danger">Lost</span>
                                    <span class="text-danger ms-2 fw-bold">+ 100% of Book Cost</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#loanDetailsModal">Back to List</button>
                        <button type="submit" class="btn btn-success">Process Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global var to store current User ID for reference
        let currentUserUniqueId = '';

        function fetchUserLoans(userId) {
            fetch(`/SmartLWA/app/controllers/CirculationController.php?action=get_user_loans_json&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById('modal_user_name').innerText = data.user.name;
                        document.getElementById('modal_user_unique_id').innerText = data.user.unique_id;
                        currentUserUniqueId = data.user.unique_id;

                        const tbody = document.getElementById('modal_loans_body');
                        tbody.innerHTML = '';
                        
                        if(data.loans.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="3" class="text-center">No active loans.</td></tr>';
                        } else {
                            data.loans.forEach(loan => {
                                const row = `
                                    <tr>
                                        <td>
                                            <strong>${loan.title}</strong><br>
                                            <small class="text-muted">ISBN: ${loan.isbn}</small>
                                        </td>
                                        <td>${loan.due_date_formatted}</td>
                                        <td>
                                            <button class="btn btn-sm btn-success" 
                                                onclick="openReturnModal('${loan.isbn}')">
                                                Return
                                            </button>
                                        </td>
                                    </tr>
                                `;
                                tbody.innerHTML += row;
                            });
                        }

                        var myModal = new bootstrap.Modal(document.getElementById('loanDetailsModal'));
                        myModal.show();
                    } else {
                        alert("Error fetching details: " + data.message);
                    }
                })
                .catch(err => console.error(err));
        }

        function openReturnModal(isbn) {
            // Hide Details Modal
            const detailsEl = document.getElementById('loanDetailsModal');
            const detailsModal = bootstrap.Modal.getInstance(detailsEl);
            if (detailsModal) detailsModal.hide();

            // Autofill Return Modal
            document.getElementById('return_user_id').value = currentUserUniqueId;
            document.getElementById('return_book_isbn').value = isbn;

            // Show Return Modal
            var returnModal = new bootstrap.Modal(document.getElementById('returnModal'));
            returnModal.show();
        }
    </script>
</body>
</html>