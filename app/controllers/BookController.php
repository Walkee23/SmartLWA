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
                $this->getBookAsJson(); // For the search feature
                break;
            default:
                header("Location: /SmartLWA/app/views/librarian_dashboard.php");
                exit();
        }
    }

    private function addBook()
    {
        try {
            $sql = "INSERT INTO Books (isbn, title, author, publisher, publication_year, price) VALUES (?, ?, ?, ?, ?, ?)";
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
            // Archive means setting archived = 1 (Soft Delete)
            $stmt = $this->db->prepare("UPDATE Books SET archived = 1 WHERE book_id = ?");
            $stmt->execute([$book_id]);

            $_SESSION['success'] = "Book ID $book_id has been archived.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error archiving book.";
        }
        header("Location: /SmartLWA/app/views/librarian_dashboard.php");
        exit();
    }

    // Helper: Returns JSON data for AJAX calls
    private function getBookAsJson()
    {
        header('Content-Type: application/json');
        $query = $_GET['query'] ?? ''; // Can be ID or ISBN

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
}

// Instantiate and Run
$controller = new BookController($pdo);
$controller->handleRequest();
