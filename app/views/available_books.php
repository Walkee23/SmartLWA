<?php
session_start();

// Protect access: Only logged-in users should see this view
if (!isset($_SESSION['user_id'])) {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

// Include database connection
require_once __DIR__ . '/../models/database.php';

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Get role for dynamic logic
$search_term = $_GET['search'] ?? '';

// --- 1. ROLE CONFIGURATION ---
$is_librarian = ($user_role === 'librarian');

if ($is_librarian) {
    $page_title = "Book Inventory";
    $dashboard_link = '/SmartLWA/app/views/librarian_dashboard.php';
} elseif ($user_role === 'teacher') {
    $page_title = "Available Books";
    $dashboard_link = '/SmartLWA/app/views/teacher_dashboard.php';
} else {
    $page_title = "Available Books";
    $dashboard_link = '/SmartLWA/app/views/student_dashboard.php';
}

// --- 2. PRE-FETCH DATA (Non-Librarians Only) ---
$active_reservations = [];
if (!$is_librarian) {
    try {
        // Prevent reserving the same book twice
        $stmt = $pdo->prepare("SELECT book_id FROM Reservations 
                               WHERE user_id = ? AND status IN ('active', 'ready_for_pickup')");
        $stmt->execute([$user_id]);
        $active_reservations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Log error
    }
}

// --- 3. BUILD QUERY ---
// Librarians need more data (ISBN, Price, etc.) for the Edit form
$sql = "
    SELECT
        b.book_id,
        b.isbn,
        b.title,
        b.author,
        b.publisher,
        b.publication_year,
        b.price,
        b.archived,
        b.cover_image_url,  
        (SELECT COUNT(*) FROM BookCopies WHERE book_id = b.book_id AND status = 'available') AS available_copies
    FROM Books b
    WHERE 1=1 
";

// Filter: Non-librarians should NOT see archived books
if (!$is_librarian) {
    $sql .= " AND b.archived = FALSE";
}

$params = [];

// Search Logic
if (!empty($search_term)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $like_term = '%' . $search_term . '%';
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
}

$sql .= " ORDER BY b.title ASC";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - SmartLWA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Sidebar Styling */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0; left: 0;
            background-color: #1a233b;
            color: white;
            padding-top: 20px;
            z-index: 100;
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
        
        .sidebar a.active {
            color: #fff;
            background-color: #007bff;
            font-weight: bold;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        @media (max-width: 992px) {
            .sidebar { width: 100%; height: auto; position: relative; padding-top: 0; }
            .main-content { margin-left: 0; }
        }
        
        .book-cover {
            height: 200px; 
            object-fit: contain; 
            width: 100%;
            padding: 10px;
            background-color: #f8f9fa;
        }
        .book-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
        }
        
        /* Badge for Archived Books */
        .archived-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
    </style>
</head>

<body>
    <!-- 1. Sidebar Navigation (Role-Aware) -->
    <div class="sidebar">
        <h3 class="text-center mb-4 text-white">Smart Library</h3>
        <a href="<?php echo $dashboard_link; ?>">Dashboard</a>
        
        <?php if ($is_librarian): ?>
            <!-- Librarian Links -->
            <a href="/SmartLWA/app/views/available_books.php" class="active">Book Inventory</a>
            <!-- You can add more librarian links here -->
        <?php else: ?>
            <!-- Student/Teacher Links -->
            <a href="/SmartLWA/app/views/my_reservations.php">Reservations</a>
            <a href="/SmartLWA/app/views/my_borrowed_books.php">Borrowed Books</a>
            <a href="/SmartLWA/app/views/available_books.php" class="active">Available Books</a>
        <?php endif; ?>
        
        <a href="/SmartLWA/app/controllers/AuthController.php?logout=true">Logout</a>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo $page_title; ?></h1>
            <?php if ($is_librarian): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookModal">
                    <i class="fas fa-plus"></i> Add New Book
                </button>
            <?php endif; ?>
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

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-lg-6 col-md-8">
                <form method="GET" action="/SmartLWA/app/views/available_books.php" class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search by title, author, or ISBN" 
                           name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="/SmartLWA/app/views/available_books.php" class="btn btn-outline-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Book Cards Grid -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    while ($book = $stmt->fetch()) {
                        $book_id = $book['book_id'];
                        $image_src = !empty($book['cover_image_url']) 
                                    ? htmlspecialchars($book['cover_image_url']) 
                                    : 'https://via.placeholder.com/150x200?text=No+Cover';
                        
                        // JSON Encode book data for Librarian JS
                        $book_json = htmlspecialchars(json_encode($book), ENT_QUOTES, 'UTF-8');
                        ?>
                        
                        <div class="col d-flex align-items-stretch">
                            <div class="card shadow book-card w-100 <?php echo $book['archived'] ? 'border-danger' : ''; ?>">
                                
                                <?php if ($is_librarian && $book['archived']): ?>
                                    <div class="archived-overlay">
                                        <span class="badge bg-danger">ARCHIVED</span>
                                    </div>
                                <?php endif; ?>

                                <img src="<?php echo $image_src; ?>" class="card-img-top book-cover" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>">
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1 text-primary"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="card-text text-muted mb-1 small">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    
                                    <?php if($is_librarian): ?>
                                        <p class="card-text text-muted small mb-2">ID: <?php echo $book['book_id']; ?> | ISBN: <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></p>
                                    <?php endif; ?>

                                    <div class="mt-auto pt-2">
                                        <?php if ($is_librarian): ?>
                                            <!-- LIBRARIAN ACTIONS -->
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-sm btn-outline-primary" onclick='openUpdateModal(<?php echo $book_json; ?>)'>
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <?php if (!$book['archived']): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick='openArchiveModal(<?php echo $book_json; ?>)'>
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>Archived</button>
                                                <?php endif; ?>
                                            </div>

                                        <?php else: ?>
                                            <!-- STUDENT/TEACHER ACTIONS -->
                                            <?php 
                                            $is_available = $book['available_copies'] > 0;
                                            $has_active_reservation = in_array($book_id, $active_reservations);
                                            $button_disabled = $has_active_reservation ? 'disabled' : '';
                                            $button_text = $has_active_reservation ? 'Reserved' : 'Reserve';
                                            $availability_class = $is_available ? 'text-success' : 'text-danger';
                                            $availability_text = $is_available ? "{$book['available_copies']} available" : 'Unavailable';
                                            ?>
                                            <p class="mb-1 fw-bold <?php echo $availability_class; ?>">
                                                <?php echo $availability_text; ?>
                                            </p>
                                            <a href="/SmartLWA/app/controllers/ReservationController.php?action=reserve&book_id=<?php echo $book_id; ?>" 
                                                class="btn btn-sm btn-primary w-100 mt-2" 
                                                <?php echo $button_disabled; ?>>
                                                <?php echo $button_text; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-warning text-center">No books found.</div></div>';
                }
            } catch (PDOException $e) {
                echo '<div class="col-12"><div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
            }
            ?>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODALS (Only rendered for Librarians)      -->
    <!-- ========================================== -->
    <?php if ($is_librarian): ?>
        
        <!-- ADD BOOK MODAL -->
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
                            <button type="submit" class="btn btn-primary">Save Book</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- UPDATE BOOK MODAL -->
        <div class="modal fade" id="updateBookModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="/SmartLWA/app/controllers/BookController.php" method="POST">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Edit Book Details</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
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
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ARCHIVE BOOK MODAL -->
        <div class="modal fade" id="archiveBookModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="/SmartLWA/app/controllers/BookController.php" method="POST">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Archive Book</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to archive this book? It will be hidden from students and teachers.</p>
                            <div class="alert alert-light border">
                                <strong>Title:</strong> <span id="archive_book_title"></span><br>
                                <small class="text-muted">Book ID: <span id="archive_book_id_display"></span></small>
                            </div>
                            <input type="hidden" name="action" value="archive_book">
                            <input type="hidden" name="book_id" id="archive_book_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Confirm Archive</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($is_librarian): ?>
    <script>
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
        }

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
    </script>
    <?php endif; ?>

</body>
</html>