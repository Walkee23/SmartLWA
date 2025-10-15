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
$search_term = $_GET['search'] ?? '';

// --- Pre-fetch current user's active reservations for validation ---
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

// Build the SQL Query
$sql = "
    SELECT
        b.book_id,
        b.title,
        b.author,
        b.publication_year,
        b.cover_image_url,  -- Fetch the cover image URL
        (SELECT COUNT(*) FROM BookCopies WHERE book_id = b.book_id AND status = 'available') AS available_copies
    FROM Books b
    WHERE b.archived = FALSE
";

$params = [];

// Add search condition if a search term is present
if (!empty($search_term)) {
    // Search by Title OR Author
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
    $like_term = '%' . $search_term . '%';
    $params[] = $like_term;
    $params[] = $like_term;
}

$sql .= " ORDER BY b.title ASC";
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
        /* Sidebar Styling (Consistent) */
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
        
        /* Custom styles for the new card layout */
        .book-cover {
            height: 200px; /* Fixed height for image area */
            object-fit: contain; /* Ensures the image fits without cropping */
            width: 100%;
            padding: 10px;
        }
        .book-card {
            height: 100%; /* Ensures all cards in a row are the same height */
            transition: transform 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h3 class="text-center mb-4 text-white">Smart Library</h3>
        <a href="/SmartLWA/app/views/student_dashboard.php">Dashboard</a>
        <a href="/SmartLWA/app/views/my_reservations.php">Reservations</a>
        <a href="/SmartLWA/app/views/my_borrowed_books.php">Borrowed Books</a>
        <a href="/SmartLWA/app/views/available_books.php" class="active">Available Books</a>
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

        <div class="row mb-4">
            <div class="col-lg-6 col-md-8">
                <form method="GET" action="/SmartLWA/app/views/available_books.php" class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search by title or author" 
                           aria-label="Search" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="/SmartLWA/app/views/available_books.php" class="btn btn-outline-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    while ($book = $stmt->fetch()) {
                        $book_id = $book['book_id'];
                        $is_available = $book['available_copies'] > 0;
                        $has_active_reservation = in_array($book_id, $active_reservations);
                        
                        // Image source: Use the stored URL or a placeholder if missing
                        $image_src = !empty($book['cover_image_url']) 
                                    ? htmlspecialchars($book['cover_image_url']) 
                                    : 'https://via.placeholder.com/150x200?text=No+Cover'; 
                        
                        // Button logic
                        $button_disabled = $has_active_reservation ? 'disabled' : '';
                        $button_text = $has_active_reservation ? 'Reserved' : 'Reserve';
                        $button_tooltip = $has_active_reservation ? 'You already have an active reservation for this book.' : 'Reserve this book title.';

                        $availability_text = $is_available ? "{$book['available_copies']} available" : 'Unavailable';
                        $availability_class = $is_available ? 'text-success' : 'text-danger';
                        ?>
                        
                        <div class="col d-flex align-items-stretch">
                            <div class="card shadow book-card w-100">
                                <img src="<?php echo $image_src; ?>" class="card-img-top book-cover" 
                                     alt="Cover of <?php echo htmlspecialchars($book['title']); ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1 text-primary"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="card-text text-muted mb-2">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    
                                    <div class="mt-auto pt-2">
                                        <p class="mb-1 fw-bold <?php echo $availability_class; ?>">
                                            <?php echo $availability_text; ?>
                                        </p>
                                        <a href="/SmartLWA/app/controllers/ReservationController.php?action=reserve&book_id=<?php echo $book_id; ?>" 
                                            class="btn btn-sm btn-primary w-100 mt-2" 
                                            <?php echo $button_disabled; ?>
                                            title="<?php echo $button_tooltip; ?>" data-bs-toggle="tooltip">
                                            <?php echo $button_text; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-warning text-center">No books found matching your criteria.</div></div>';
                }
            } catch (PDOException $e) {
                echo '<div class="col-12"><div class="alert alert-danger text-center">Error retrieving books: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
            }
            ?>
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