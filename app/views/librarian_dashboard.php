<?php
session_start();

// 1. Protect Access: Only librarians allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: /SmartLWA/views/login.php");
    exit(); 
} 

// 2. Include Database Connection
require_once __DIR__ . '/../models/database.php';

// Prepare user data
$first_name = htmlspecialchars($_SESSION['first_name'] ?? 'Admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard - SmartLWA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; }

        /* Sidebar Styling */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0; left: 0;
            background-color: #1a233b;
            color: white;
            padding-top: 20px;
            z-index: 1000;
        }
        .sidebar-header { padding: 0 25px; margin-bottom: 30px; font-size: 1.2rem; font-weight: 500; }
        .sidebar a { padding: 15px 25px; text-decoration: none; font-size: 16px; color: #b0b3b8; display: block; transition: 0.3s; }
        .sidebar a:hover { color: #fff; background-color: rgba(255,255,255,0.1); }

        /* Main Content Area */
        .main-content { margin-left: 250px; padding: 40px; }

        /* Action Buttons Styling */
        .action-btn {
            height: 100px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 500; color: white;
            border-radius: 8px; text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none; width: 100%;
        }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: white; }
        .btn-add { background-color: #2563eb; }
        .btn-update { background-color: #16a34a; }
        .btn-archive { background-color: #dc2626; }
        .btn-covers { background-color: #6f42c1; } /* New button color */

        .inventory-card {
            background: white; border-radius: 8px; border: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); min-height: 500px; 
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">Smart Library</div>
        <a href="#" class="text-white">Dashboard</a>
        <a href="/SmartLWA/app/views/available_books.php">Book Inventory</a>
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <div class="mb-5">
            <h1 class="fw-bold fst-italic">Welcome, <?php echo $first_name; ?>!</h1>
            <p class="text-muted">Manage your library inventory and book records here.</p>
        </div>

        <!-- Alerts -->
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

        <!-- Action Buttons -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <button class="action-btn btn-add" data-bs-toggle="modal" data-bs-target="#addBookModal">Add Book</button>
            </div>
            <div class="col-md-3">
                <button class="action-btn btn-update" data-bs-toggle="modal" data-bs-target="#updateBookModal">Update Book</button>
            </div>
            <div class="col-md-3">
                <button class="action-btn btn-archive" data-bs-toggle="modal" data-bs-target="#archiveBookModal">Archive Book</button>
            </div>
            <!-- New Button -->
            <div class="col-md-3">
                <button class="action-btn btn-covers" id="btnFetchCovers" onclick="updateBookCovers()">
                    <span id="coverBtnText">Update Covers</span>
                    <span id="coverBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="inventory-card p-4">
            <h5 class="mb-3">Recent Inventory</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Book ID</th>
                            <th>ISBN</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT * FROM Books ORDER BY book_id DESC LIMIT 50");
                            $stmt->execute();
                            if ($stmt->rowCount() > 0) {
                                while ($row = $stmt->fetch()) {
                                    $status = $row['archived'] ? 'Archived' : 'Active';
                                    $status_badge = $row['archived'] ? 'bg-secondary' : 'bg-success';
                                    $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                    
                                    echo "<tr>";
                                    echo "<td>" . $row['book_id'] . "</td>";
                                    echo "<td>" . htmlspecialchars($row['isbn'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                                    echo "<td><span class='badge $status_badge'>$status</span></td>";
                                    echo "<td>
                                            <button class='btn btn-sm btn-outline-primary me-1' onclick='openUpdateModal($row_json)'><i class='fas fa-edit'></i></button>
                                            <button class='btn btn-sm btn-outline-danger' onclick='openArchiveModal($row_json)'><i class='fas fa-trash'></i></button>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted'>No books found.</td></tr>";
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

    <!-- MODAL 1: ADD BOOK -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/SmartLWA/app/controllers/BookController.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_book">
                        <div class="mb-3"><label>ISBN</label><input type="text" name="isbn" class="form-control" required></div>
                        <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                        <div class="mb-3"><label>Author</label><input type="text" name="author" class="form-control" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Publisher</label><input type="text" name="publisher" class="form-control"></div>
                            <div class="col-md-6 mb-3"><label>Year</label><input type="number" name="year" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label>Price (₱)</label><input type="number" step="0.01" name="price" class="form-control" required></div> <!-- Changed to ₱ -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: UPDATE BOOK (With Search) -->
    <div class="modal fade" id="updateBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/SmartLWA/app/controllers/BookController.php" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Update Book</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Search Section -->
                        <div class="input-group mb-3">
                            <input type="text" id="search_update_input" class="form-control" placeholder="Enter Book ID or ISBN">
                            <button class="btn btn-outline-secondary" type="button" onclick="fetchBookDetails('update')">Find</button>
                        </div>
                        <hr>
                        
                        <!-- Edit Form -->
                        <input type="hidden" name="action" value="update_book">
                        <input type="hidden" name="book_id" id="update_book_id">
                        
                        <div class="mb-3"><label>ISBN</label><input type="text" id="update_isbn" name="isbn" class="form-control"></div>
                        <div class="mb-3"><label>Title</label><input type="text" id="update_title" name="title" class="form-control" required></div>
                        <div class="mb-3"><label>Author</label><input type="text" id="update_author" name="author" class="form-control" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Publisher</label><input type="text" id="update_publisher" name="publisher" class="form-control"></div>
                            <div class="col-md-6 mb-3"><label>Year</label><input type="number" id="update_year" name="year" class="form-control"></div>
                        </div>
                        <div class="mb-3"><label>Price (₱)</label><input type="number" step="0.01" id="update_price" name="price" class="form-control" required></div> <!-- Changed to ₱ -->
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: ARCHIVE BOOK (With Search) -->
    <div class="modal fade" id="archiveBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="/SmartLWA/app/controllers/BookController.php" method="POST">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Archive Book</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Search for a book to archive (remove from circulation).</p>
                        <!-- Search Section -->
                        <div class="input-group mb-3">
                            <input type="text" id="search_archive_input" class="form-control" placeholder="Enter Book ID or ISBN">
                            <button class="btn btn-outline-secondary" type="button" onclick="fetchBookDetails('archive')">Find</button>
                        </div>
                        
                        <div id="archive_preview" class="alert alert-light border d-none">
                            <strong>Found:</strong> <span id="archive_book_title"></span><br>
                            <small class="text-muted">ID: <span id="archive_book_id_display"></span></small>
                        </div>

                        <input type="hidden" name="action" value="archive_book">
                        <input type="hidden" name="book_id" id="archive_book_id">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger" id="btn_confirm_archive" disabled>Confirm Archive</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS for Search & Filling Forms -->
    <script>
        // 1. Helpers to simply fill form data (Does NOT open modal)
        function fillUpdateForm(book) {
            document.getElementById('update_book_id').value = book.book_id;
            document.getElementById('update_isbn').value = book.isbn || '';
            document.getElementById('update_title').value = book.title;
            document.getElementById('update_author').value = book.author;
            document.getElementById('update_publisher').value = book.publisher || '';
            document.getElementById('update_year').value = book.publication_year || '';
            document.getElementById('update_price').value = book.price || '';
        }

        function fillArchiveForm(book) {
            document.getElementById('archive_book_id').value = book.book_id;
            document.getElementById('archive_book_id_display').innerText = book.book_id;
            document.getElementById('archive_book_title').innerText = book.title;
            
            // Show preview and enable button
            document.getElementById('archive_preview').classList.remove('d-none');
            document.getElementById('btn_confirm_archive').disabled = false;
        }

        // 2. Functions called when clicking the Table Icons (Fills AND Opens Modal)
        function openUpdateModal(book) {
            fillUpdateForm(book);
            var myModal = new bootstrap.Modal(document.getElementById('updateBookModal'));
            myModal.show();
        }

        function openArchiveModal(book) {
            fillArchiveForm(book);
            var myModal = new bootstrap.Modal(document.getElementById('archiveBookModal'));
            myModal.show();
        }

        // 3. AJAX Function for the "Find" buttons (Fills ONLY, does NOT re-open modal)
        function fetchBookDetails(type) {
            let inputId = type === 'update' ? 'search_update_input' : 'search_archive_input';
            let query = document.getElementById(inputId).value;

            if(!query) { alert("Please enter a Book ID or ISBN"); return; }

            // Call the Controller
            fetch(`/SmartLWA/app/controllers/BookController.php?action=get_book_json&query=${query}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Here is the fix: We call the fill functions, NOT the open functions
                        if(type === 'update') {
                            fillUpdateForm(data.data);
                        } else {
                            fillArchiveForm(data.data);
                        }
                    } else {
                        alert("Book not found!");
                    }
                })
                .catch(err => console.error(err));
        }

        // 4. Function to Update Book Covers (AJAX)
        function updateBookCovers() {
            const btn = document.getElementById('btnFetchCovers');
            const spinner = document.getElementById('coverBtnSpinner');
            const text = document.getElementById('coverBtnText');

            // Disable button and show spinner
            btn.disabled = true;
            spinner.classList.remove('d-none');
            text.innerText = "Updating...";

            fetch('/SmartLWA/fetch_book_covers.php?mode=json')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert(data.message);
                        location.reload(); // Reload to see new covers (if displayed)
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error fetching covers:', err);
                    alert("An error occurred while fetching covers.");
                })
                .finally(() => {
                    // Re-enable button
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                    text.innerText = "Update Covers";
                });
        }
    </script>
</body>
</html>