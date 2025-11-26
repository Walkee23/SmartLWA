<?php
// Set execution time higher for large libraries
set_time_limit(300); 

// Include the database connection setup
require_once __DIR__ . '/app/models/database.php';

// Check if running in JSON mode (for AJAX)
$is_json_mode = isset($_GET['mode']) && $_GET['mode'] === 'json';

if ($is_json_mode) {
    header('Content-Type: application/json');
}

// Define the desired cover size: S (Small), M (Medium), L (Large)
$cover_size = 'L'; 
$books_updated = 0;

if (!$is_json_mode) {
    echo "<h1>Starting Book Cover Fetcher...</h1>";
}

try {
    // 1. Get all books that have an ISBN but are missing a cover URL
    // NOTE: You might want to remove "AND cover_image_url IS NULL" if you want to force update ALL covers
    // For now, I'll keep it to update only missing ones to be efficient, unless you want to force update.
    // If you want a "Force Update" button, remove "AND cover_image_url IS NULL".
    
    // Let's update logic to fetch ALL non-archived books with ISBNs to ensure they are current
    $stmt = $pdo->prepare("SELECT book_id, isbn FROM Books 
                           WHERE isbn IS NOT NULL AND isbn != '' AND archived = FALSE");
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$is_json_mode) {
        echo "<p>Found " . count($books) . " books to check.</p>";
    }

    // 2. Loop through each book and generate/store the Open Library URL
    foreach ($books as $book) {
        $book_id = $book['book_id'];
        $isbn = $book['isbn'];

        // Open Library URL Pattern
        // Example: https://covers.openlibrary.org/b/isbn/9780321765723-L.jpg
        $open_library_url = "https://covers.openlibrary.org/b/isbn/" . urlencode($isbn) . "-" . $cover_size . ".jpg";

        // 3. Update the database
        $update_stmt = $pdo->prepare("UPDATE Books SET cover_image_url = ? WHERE book_id = ?");
        $update_stmt->execute([$open_library_url, $book_id]);

        $books_updated++;
        if (!$is_json_mode) {
            echo "<p>✅ Updated Book ID {$book_id} ({$isbn}) with URL: {$open_library_url}</p>";
        }
    }

    if ($is_json_mode) {
        echo json_encode([
            'success' => true, 
            'message' => "Process Complete! {$books_updated} book covers updated/checked."
        ]);
    } else {
        echo "<h2>Process Complete! Total books updated: {$books_updated}</h2>";
    }

} catch (PDOException $e) {
    if ($is_json_mode) {
        echo json_encode([
            'success' => false, 
            'message' => "Database Error: " . $e->getMessage()
        ]);
    } else {
        die("Database Error: " . $e->getMessage());
    }
}
?>