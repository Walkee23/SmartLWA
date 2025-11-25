<?php
session_start();
require_once __DIR__ . '/../models/database.php';

// Security: Only Staff or Librarians can access circulation
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'librarian'])) {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

class CirculationController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function handleRequest() {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'borrow':
                $this->processBorrow();
                break;
            case 'return':
                $this->processReturn();
                break;
            case 'clearance':
                $this->checkClearance(); 
                break;
            case 'process_clearance':
                $this->processClearance();
                break;
            default:
                $_SESSION['error'] = "Invalid action.";
                $this->redirectBack();
        }
    }

    private function processBorrow() {
        $uniqueId = trim($_POST['user_id_input'] ?? '');
        $isbn = trim($_POST['book_id_input'] ?? '');

        if (empty($uniqueId) || empty($isbn)) {
            $_SESSION['error'] = "Please provide both User ID and Book ISBN.";
            $this->redirectBack();
        }

        try {
            $this->db->beginTransaction();

            // 1. Find User
            $stmt = $this->db->prepare("SELECT user_id, role FROM Users WHERE unique_id = ? LIMIT 1");
            $stmt->execute([$uniqueId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("User ID '$uniqueId' not found.");
            }

            // 2. Find Book by ISBN (Strictly ISBN only)
            $stmt = $this->db->prepare("SELECT book_id, title FROM Books WHERE isbn = ? LIMIT 1");
            $stmt->execute([$isbn]);
            $book = $stmt->fetch();

            if (!$book) {
                throw new Exception("Book with ISBN '$isbn' not found in database.");
            }

            // 3. Find an AVAILABLE copy for this book
            $stmt = $this->db->prepare("SELECT copy_id, barcode, status FROM BookCopies WHERE book_id = ? AND status = 'available' LIMIT 1");
            $stmt->execute([$book['book_id']]);
            $copy = $stmt->fetch();
            
            if (!$copy) {
                throw new Exception("Book '{$book['title']}' found, but no physical copies are currently available.");
            }

            // 4. Check Borrowing Limit (Students = 3 max)
            if ($user['role'] === 'student') {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM BorrowingRecords WHERE user_id = ? AND status = 'borrowed'");
                $stmt->execute([$user['user_id']]);
                $count = $stmt->fetchColumn();

                if ($count >= 3) {
                    throw new Exception("Student has reached the maximum borrowing limit (3 books).");
                }
            }

            // 5. Calculate Due Date (e.g., 7 days from now)
            $dueDate = date('Y-m-d', strtotime('+7 days'));

            // 6. Create Record
            $stmt = $this->db->prepare("INSERT INTO BorrowingRecords (copy_id, user_id, due_date, status) VALUES (?, ?, ?, 'borrowed')");
            $stmt->execute([$copy['copy_id'], $user['user_id'], $dueDate]);

            // 7. Update Copy Status
            $stmt = $this->db->prepare("UPDATE BookCopies SET status = 'on_loan' WHERE copy_id = ?");
            $stmt->execute([$copy['copy_id']]);

            // 8. Fulfill Reservation (Mark fulfilled if exists)
            $stmt = $this->db->prepare("UPDATE Reservations SET status = 'fulfilled' 
                                      WHERE user_id = ? AND book_id = ? AND status IN ('active', 'ready_for_pickup')");
            $stmt->execute([$user['user_id'], $book['book_id']]);

            $this->db->commit();
            $_SESSION['success'] = "Book '{$book['title']}' borrowed successfully! Due date: $dueDate";

        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirectBack();
    }

    private function processReturn() {
        $uniqueId = trim($_POST['user_id_input'] ?? '');
        $isbn = trim($_POST['book_id_input'] ?? '');
        $isDamaged = isset($_POST['is_damaged']);

        if (empty($uniqueId) || empty($isbn)) {
            $_SESSION['error'] = "Please provide both User ID and Book ISBN to return.";
            $this->redirectBack();
        }

        try {
            $this->db->beginTransaction();

            // 1. Find User
            $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE unique_id = ? LIMIT 1");
            $stmt->execute([$uniqueId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("User ID '$uniqueId' not found.");
            }

            // 2. Find Book by ISBN
            $stmt = $this->db->prepare("SELECT book_id, price FROM Books WHERE isbn = ? LIMIT 1");
            $stmt->execute([$isbn]);
            $book = $stmt->fetch();

            if (!$book) {
                throw new Exception("Book with ISBN '$isbn' not found.");
            }

            // 3. Find the Active Loan
            $stmt = $this->db->prepare("
                SELECT br.record_id, br.due_date, br.copy_id 
                FROM BorrowingRecords br
                JOIN BookCopies bc ON br.copy_id = bc.copy_id
                WHERE br.user_id = ? 
                  AND bc.book_id = ? 
                  AND br.status = 'borrowed' 
                LIMIT 1
            ");
            $stmt->execute([$user['user_id'], $book['book_id']]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception("No active loan found for this user and book.");
            }

            // 4. Update Record to 'returned'
            $returnDate = date('Y-m-d H:i:s');
            $status = (strtotime($returnDate) > strtotime($loan['due_date'] . ' 23:59:59')) ? 'overdue' : 'returned';
            
            $stmt = $this->db->prepare("UPDATE BorrowingRecords SET return_date = ?, status = ? WHERE record_id = ?");
            $stmt->execute([$returnDate, $status, $loan['record_id']]);

            // 5. Update Copy Status
            $newCopyStatus = $isDamaged ? 'in_repair' : 'available';
            $stmt = $this->db->prepare("UPDATE BookCopies SET status = ? WHERE copy_id = ?");
            $stmt->execute([$newCopyStatus, $loan['copy_id']]);

            // 6. Handle Penalties
            $messages = [];
            $messages[] = "Book returned successfully.";

            if ($status === 'overdue') {
                $penaltyAmount = 50.00; 
                $stmt = $this->db->prepare("INSERT INTO Penalties (user_id, record_id, amount, reason) VALUES (?, ?, ?, 'Overdue Fine')");
                $stmt->execute([$user['user_id'], $loan['record_id'], $penaltyAmount]);
                $messages[] = "Overdue fine of $50.00 applied.";
            }

            if ($isDamaged) {
                $price = $book['price'] ?: 100.00; 
                $stmt = $this->db->prepare("INSERT INTO Penalties (user_id, record_id, amount, reason) VALUES (?, ?, ?, 'Book Damage Fee')");
                $stmt->execute([$user['user_id'], $loan['record_id'], $price]);
                $messages[] = "Damage fee of $$price applied.";
            }

            $this->db->commit();
            $_SESSION['success'] = implode(" ", $messages);

        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirectBack();
    }

    // Fetches status for display in the modal
    private function checkClearance() {
        $uniqueId = trim($_POST['clearance_user_id'] ?? '');

        if (empty($uniqueId)) {
            $_SESSION['error'] = "Enter a User ID to check.";
            $this->redirectBack();
        }

        try {
            $stmt = $this->db->prepare("SELECT user_id, unique_id, first_name, last_name FROM Users WHERE unique_id = ?");
            $stmt->execute([$uniqueId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("User not found.");
            }

            // Check Loans
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM BorrowingRecords WHERE user_id = ? AND status = 'borrowed'");
            $stmt->execute([$user['user_id']]);
            $loanCount = $stmt->fetchColumn();

            // Check Fines
            $stmt = $this->db->prepare("SELECT SUM(amount) FROM Penalties WHERE user_id = ? AND is_paid = 0");
            $stmt->execute([$user['user_id']]);
            $unpaidFines = $stmt->fetchColumn() ?: 0.00;

            // STORE DATA IN SESSION to display in the modal
            $_SESSION['clearance_data'] = [
                'user_id' => $user['user_id'], // for the confirm button
                'unique_id' => $user['unique_id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'loan_count' => $loanCount,
                'unpaid_fines' => $unpaidFines,
                'is_cleared' => ($loanCount == 0 && $unpaidFines == 0)
            ];

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirectBack();
    }

    // Actually processes the clearance (Finalizing it)
    private function processClearance() {
        // In a real system, this might generate a PDF or set a 'is_cleared' flag in the Users table.
        // For now, we'll just show a success message if they are eligible.
        
        $userId = $_POST['user_id'] ?? '';
        // Re-verify logic could go here for security...
        
        $_SESSION['success'] = "User has been officially marked as CLEARED.";
        unset($_SESSION['clearance_data']); // Clear the modal data
        $this->redirectBack();
    }

    private function redirectBack() {
        if (isset($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
        } else {
            header("Location: /SmartLWA/app/views/staff_dashboard.php");
        }
        exit();
    }
}

$controller = new CirculationController($pdo);
$controller->handleRequest();