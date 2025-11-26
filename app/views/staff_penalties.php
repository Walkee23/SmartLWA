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
    <title>Manage Penalties - SmartLWA</title>
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
        <a href="/SmartLWA/app/views/staff_returns.php">Returns</a>
        <a href="/SmartLWA/app/views/staff_penalties.php" class="active">Penalties</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold">Outstanding Penalties</h1>
                <p class="text-muted">Review user records and collect payments.</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <form method="GET" action="/SmartLWA/app/views/staff_penalties.php" class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search Student ID or Name..." 
                           name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="/SmartLWA/app/views/staff_penalties.php" class="btn btn-outline-secondary ms-2">Clear</a>
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

        <!-- Penalties Table -->
        <div class="card p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User ID</th>
                            <th>Student Name</th>
                            <th>Total Unpaid</th>
                            <th>Pending Items</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Build Query: Group penalties by User to show a summary row per student
                            $sql = "
                                SELECT 
                                    u.user_id, u.unique_id, u.first_name, u.last_name,
                                    SUM(p.amount) as total_due,
                                    COUNT(p.penalty_id) as pending_count
                                FROM Penalties p
                                JOIN Users u ON p.user_id = u.user_id
                                WHERE p.is_paid = 0
                            ";

                            $params = [];
                            if (!empty($search_term)) {
                                $sql .= " AND (u.unique_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
                                $term = '%' . $search_term . '%';
                                $params = [$term, $term, $term];
                            }

                            $sql .= " GROUP BY u.user_id ORDER BY total_due DESC";
                            
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);

                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch()) {
                                    $amount = number_format($row['total_due'], 2);
                                    $userId = $row['user_id'];
                                    
                                    echo "<tr>";
                                    echo "<td><span class='badge bg-light text-dark border'>{$row['unique_id']}</span></td>";
                                    echo "<td>" . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . "</td>";
                                    echo "<td class='text-danger fw-bold'>$$amount</td>";
                                    echo "<td><span class='badge bg-warning text-dark'>{$row['pending_count']} Records</span></td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-info text-white' onclick='fetchUserDetails($userId)'>
                                                <i class='fas fa-file-invoice'></i> View Details
                                            </button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center text-muted py-5'>No outstanding penalties found.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='5' class='text-danger'>Error loading data.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- USER DETAILS & PAYMENT MODAL -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg"> <!-- Large modal for better view -->
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-user-tag me-2"></i>User Record Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- User Info Section -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 id="modal_user_name" class="mb-0">Student Name</h4>
                        <span class="badge bg-secondary fs-6" id="modal_user_id">ID: 000</span>
                    </div>
                    <hr>

                    <!-- Penalties List -->
                    <h6 class="fw-bold text-danger mb-2">Unpaid Penalties</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Reason / Item</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="modal_penalties_body">
                                <!-- Populated by JS -->
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total Due:</td>
                                    <td class="text-end fw-bold text-danger fs-5" id="modal_total_due">$0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Payment Form -->
                    <form action="/SmartLWA/app/controllers/CirculationController.php" method="POST" id="paymentForm">
                        <input type="hidden" name="action" value="pay_all_penalties">
                        <input type="hidden" name="user_id" id="pay_user_id">
                        
                        <div class="card bg-light border-0 p-3">
                            <h6 class="card-title">Process Payment</h6>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method">
                                        <option value="Cash">Cash</option>
                                        <option value="GCash">GCash</option>
                                        <option value="Card">Credit/Debit Card</option>
                                    </select>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check-circle"></i> Accept Full Payment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function fetchUserDetails(userId) {
            // 1. Call the Controller API (we need to create this action)
            fetch(`/SmartLWA/app/controllers/CirculationController.php?action=get_user_penalties_json&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // 2. Fill Modal Data
                        document.getElementById('modal_user_name').innerText = data.user.name;
                        document.getElementById('modal_user_id').innerText = 'ID: ' + data.user.unique_id;
                        document.getElementById('pay_user_id').value = data.user.user_id;

                        const tbody = document.getElementById('modal_penalties_body');
                        tbody.innerHTML = ''; // Clear old rows
                        
                        let total = 0;

                        data.penalties.forEach(p => {
                            total += parseFloat(p.amount);
                            const row = `
                                <tr>
                                    <td>${p.date}</td>
                                    <td>${p.reason}</td>
                                    <td class="text-end">$${parseFloat(p.amount).toFixed(2)}</td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });

                        document.getElementById('modal_total_due').innerText = '$' + total.toFixed(2);

                        // 3. Open Modal
                        var myModal = new bootstrap.Modal(document.getElementById('detailsModal'));
                        myModal.show();
                    } else {
                        alert("Error fetching details: " + data.message);
                    }
                })
                .catch(err => console.error(err));
        }
    </script>
</body>
</html>