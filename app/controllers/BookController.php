<?php
session_start();
require_once __DIR__ . '/../models/database.php';

// Security: Only Librarians
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

class BookController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function handleRequest()
    {
        // Support both GET and POST
        $action = $_REQUEST['action'] ?? '';

        switch ($action) {
            case 'add_book':
                $this->addBook();
                break;
            case 'update_book':
                $this->updateBook();
                break;
            case 'archive_book':
                $this->archiveBook();
                break;
            case 'get_book_json':
                $this->getBookAsJson();
                break;
            // --- ACTIONS FOR COPIES ---
            case 'get_book_copies':
                $this->getBookCopies();
                break;
            case 'add_copy': // Manual add (kept for compatibility)
                $this->addCopy();
                break;
            case 'add_copy_auto': // NEW: Automatic generation
                $this->addCopyAuto();
                break;
            case 'update_copy':
                $this->updateCopy();
                break;
            case 'delete_copy':
                $this->deleteCopy();
                break;
            // -----------------------------
            default:
                header("Location: /SmartLWA/app/views/librarian_dashboard.php");
                exit();
        }
    }

    private function addBook()
    {
        try {
            // Force total_copies to 0 on creation
            $sql = "INSERT INTO Books (isbn, title, author, publisher, publication_year, price, total_copies) VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $_POST['isbn'],
                $_POST['title'],
                $_POST['author'],
                $_POST['publisher'],
                $_POST['year'],
                $_POST['price']
            ]);
            $_SESSION['success'] = "Book added successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error adding book: " . $e->getMessage();
        }
        header("Location: /SmartLWA/app/views/librarian_dashboard.php");
        exit();
    }

    private function updateBook()
    {
        try {
            $book_id = $_POST['book_id'];
            $sql = "UPDATE Books SET isbn = ?, title = ?, author = ?, publisher = ?, publication_year = ?, price = ? WHERE book_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $_POST['isbn'],
                $_POST['title'],
                $_POST['author'],
                $_POST['publisher'],
                $_POST['year'],
                $_POST['price'],
                $book_id
            ]);
            $_SESSION['success'] = "Book ID $book_id updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating book.";
        }
        header("Location: /SmartLWA/app/views/librarian_dashboard.php");
        exit();
    }

    private function archiveBook()
    {
        try {
            $book_id = $_POST['book_id'];
            $stmt = $this->db->prepare("UPDATE Books SET archived = 1 WHERE book_id = ?");
            $stmt->execute([$book_id]);
            $_SESSION['success'] = "Book ID $book_id has been archived.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error archiving book.";
        }
        header("Location: /SmartLWA/app/views/librarian_dashboard.php");
        exit();
    }

    private function getBookAsJson()
    {
        header('Content-Type: application/json');
        $query = $_GET['query'] ?? '';

        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Empty query']);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM Books WHERE book_id = ? OR isbn = ? LIMIT 1");
        $stmt->execute([$query, $query]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($book) {
            echo json_encode(['success' => true, 'data' => $book]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Book not found']);
        }
        exit();
    }

    // --- METHODS FOR MANAGING COPIES ---

    private function getBookCopies()
    {
        header('Content-Type: application/json');
        $bookId = $_GET['book_id'] ?? '';

        try {
            $stmt = $this->db->prepare("SELECT * FROM BookCopies WHERE book_id = ? ORDER BY copy_id ASC");
            $stmt->execute([$bookId]);
            $copies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Also fetch book details for title display
            $stmtBook = $this->db->prepare("SELECT title FROM Books WHERE book_id = ?");
            $stmtBook->execute([$bookId]);
            $book = $stmtBook->fetch();

            echo json_encode(['success' => true, 'copies' => $copies, 'book_title' => $book['title'] ?? 'Unknown']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // NEW: Automatically generate Copy Identifiers
    private function addCopyAuto()
    {
        header('Content-Type: application/json');
        $bookId = $_POST['book_id'] ?? '';

        if (empty($bookId)) {
            echo json_encode(['success' => false, 'message' => 'Book ID is missing.']);
            exit();
        }

        try {
            $this->db->beginTransaction();

            // 1. Get Book Details
            $stmt = $this->db->prepare("SELECT author, title FROM Books WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch();

            if (!$book) throw new Exception("Book not found.");

            // 2. Generate Call Number
            // Pattern: [Author3Chars]-[BookID]-C[NextNum]
            // Clean author name: remove spaces, take first 3 chars
            $authorCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $book['author']), 0, 3));
            if (strlen($authorCode) < 3) $authorCode = str_pad($authorCode, 3, 'X');

            // Get current count to determine next copy number
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM BookCopies WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $currentCount = $stmt->fetchColumn();
            $nextNum = $currentCount + 1;

            // Format: AUTH-0000-C00
            $callNumber = sprintf("%s-%04d-C%02d", $authorCode, $bookId, $nextNum);

            // 3. Generate Barcode
            // Pattern: BC-[BookID]-[Timestamp]-[Random] to ensure global uniqueness without locking table for ID prediction
            $barcode = "BC-" . $bookId . "-" . time() . rand(10, 99);

            // 4. Insert
            $stmt = $this->db->prepare("INSERT INTO BookCopies (book_id, call_number, barcode, status) VALUES (?, ?, ?, 'available')");
            $stmt->execute([$bookId, $callNumber, $barcode]);

            // 5. Update Total Count
            $this->db->prepare("UPDATE Books SET total_copies = total_copies + 1 WHERE book_id = ?")->execute([$bookId]);

            $this->db->commit();

            // Return success with the new data to update UI instantly
            echo json_encode([
                'success' => true,
                'message' => 'Copy generated successfully!',
                'copy_data' => [
                    'call_number' => $callNumber,
                    'barcode' => $barcode,
                    'status' => 'available'
                ]
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
        }
        exit();
    }

    private function addCopy()
    {
        header('Content-Type: application/json');
        $bookId = $_POST['book_id'];
        $callNum = $_POST['call_number'];
        $barcode = $_POST['barcode'];

        try {
            $stmt = $this->db->prepare("INSERT INTO BookCopies (book_id, call_number, barcode, status) VALUES (?, ?, ?, 'available')");
            $stmt->execute([$bookId, $callNum, $barcode]);

            // Update total count in Books table
            $this->db->prepare("UPDATE Books SET total_copies = total_copies + 1 WHERE book_id = ?")->execute([$bookId]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
        }
        exit();
    }

    private function updateCopy()
    {
        header('Content-Type: application/json');
        $copyId = $_POST['copy_id'];
        $callNum = $_POST['call_number'];
        $barcode = $_POST['barcode'];
        $status = $_POST['status'];

        try {
            $stmt = $this->db->prepare("UPDATE BookCopies SET call_number = ?, barcode = ?, status = ? WHERE copy_id = ?");
            $stmt->execute([$callNum, $barcode, $status, $copyId]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
        }
        exit();
    }

    private function deleteCopy()
    {
        header('Content-Type: application/json');
        $copyId = $_POST['copy_id'];
        $bookId = $_POST['book_id']; // Needed to update count

        try {
            $stmt = $this->db->prepare("DELETE FROM BookCopies WHERE copy_id = ?");
            $stmt->execute([$copyId]);

            // Decrease count
            $this->db->prepare("UPDATE Books SET total_copies = GREATEST(total_copies - 1, 0) WHERE book_id = ?")->execute([$bookId]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
        }
        exit();
    }
}

// Instantiate and Run
$controller = new BookController($pdo);
$controller->handleRequest();
