<?php
session_start();
require_once __DIR__ . '/../models/database.php';

// Security: Only Staff or Librarians can access circulation
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'librarian'])) {
    if(isset($_GET['action']) && strpos($_GET['action'], 'json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: /SmartLWA/views/login.php");
    exit();
}

class CirculationController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

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
            case 'pay_all_penalties':
                $this->processPayAll();
                break;
            case 'get_user_penalties_json':
                $this->getUserPenaltiesJson();
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

            // 2. Find Book by ISBN
            $stmt = $this->db->prepare("SELECT book_id, title FROM Books WHERE isbn = ? LIMIT 1");
            $stmt->execute([$isbn]);
            $book = $stmt->fetch();

            if (!$book) {
                throw new Exception("Book with ISBN '$isbn' not found in database.");
            }

            // 3. Find an AVAILABLE copy
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
            $stmt = $this->db->prepare("INSERT INTO BorrowingRecords (copy_id, book_id, user_id, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
            $stmt->execute([$copy['copy_id'], $book['book_id'], $user['user_id'], $dueDate]);

            // 7. Update Copy Status
            $stmt = $this->db->prepare("UPDATE BookCopies SET status = 'on_loan' WHERE copy_id = ?");
            $stmt->execute([$copy['copy_id']]);

            // 8. Fulfill Reservation (Mark fulfilled if exists)
            $stmt = $this->db->prepare("UPDATE Reservations SET status = 'fulfilled' 
                                      WHERE user_id = ? AND book_id = ? AND status IN ('active', 'ready_for_pickup')");
            $stmt->execute([$user['user_id'], $book['book_id']]);

            // --- FIX: REVOKE CLEARANCE STATUS ---
            // Whenever a user borrows a book, their clearance must be revoked immediately.
            $stmt = $this->db->prepare("UPDATE Users SET is_cleared = 0 WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            // ------------------------------------

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
        $condition = $_POST['condition'] ?? 'good'; 

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
                SELECT record_id, due_date, copy_id 
                FROM BorrowingRecords 
                WHERE user_id = ? 
                  AND book_id = ? 
                  AND status = 'borrowed' 
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
            $newCopyStatus = 'available';
            if ($condition === 'damaged') $newCopyStatus = 'in_repair';
            if ($condition === 'lost') $newCopyStatus = 'lost';
            
            $stmt = $this->db->prepare("UPDATE BookCopies SET status = ? WHERE copy_id = ?");
            $stmt->execute([$newCopyStatus, $loan['copy_id']]);

            // 6. Handle Penalties
            $messages = [];
            $messages[] = "Book returned successfully.";

            $penaltyIncurred = false;

            if ($status === 'overdue') {
                $penaltyAmount = 50.00; 
                $stmt = $this->db->prepare("INSERT INTO Penalties (user_id, record_id, amount, reason) VALUES (?, ?, ?, 'Overdue Fine')");
                $stmt->execute([$user['user_id'], $loan['record_id'], $penaltyAmount]);
                $messages[] = "Overdue fine of $50.00 applied.";
                $penaltyIncurred = true;
            }

            $basePrice = $book['price'] ?: 100.00; 
            
            if ($condition === 'damaged') {
                $damageFee = $basePrice * 0.50; 
                $stmt = $this->db->prepare("INSERT INTO Penalties (user_id, record_id, amount, reason) VALUES (?, ?, ?, 'Damaged Book Fee (50%)')");
                $stmt->execute([$user['user_id'], $loan['record_id'], $damageFee]);
                $messages[] = "Damage fee of $" . number_format($damageFee, 2) . " applied.";
                $penaltyIncurred = true;
            } elseif ($condition === 'lost') {
                $lostFee = $basePrice; 
                $stmt = $this->db->prepare("INSERT INTO Penalties (user_id, record_id, amount, reason) VALUES (?, ?, ?, 'Lost Book Replacement')");
                $stmt->execute([$user['user_id'], $loan['record_id'], $lostFee]);
                $messages[] = "Lost book fee of $" . number_format($lostFee, 2) . " applied.";
                $penaltyIncurred = true;
            }

            // --- OPTIONAL FIX: REVOKE CLEARANCE ON PENALTY ---
            // If they returned a book but got a fine, they are not cleared.
            if ($penaltyIncurred) {
                $stmt = $this->db->prepare("UPDATE Users SET is_cleared = 0 WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
            }
            // -------------------------------------------------

            $this->db->commit();
            $_SESSION['success'] = implode(" ", $messages);

        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirectBack();
    }

    private function checkClearance() {
        $uniqueId = trim($_POST['clearance_user_id'] ?? '');

        if (empty($uniqueId)) {
            $_SESSION['error'] = "Enter a User ID to check.";
            $this->redirectBack();
        }

        try {
            $stmt = $this->db->prepare("SELECT user_id, unique_id, first_name, last_name, is_cleared FROM Users WHERE unique_id = ?");
            $stmt->execute([$uniqueId]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("User not found.");
            }

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM BorrowingRecords WHERE user_id = ? AND status = 'borrowed'");
            $stmt->execute([$user['user_id']]);
            $loanCount = $stmt->fetchColumn();

            $stmt = $this->db->prepare("SELECT SUM(amount) FROM Penalties WHERE user_id = ? AND is_paid = 0");
            $stmt->execute([$user['user_id']]);
            $unpaidFines = $stmt->fetchColumn() ?: 0.00;

            $isEligible = ($loanCount == 0 && $unpaidFines == 0);
            $alreadyCleared = ($user['is_cleared'] == 1);

            $_SESSION['clearance_data'] = [
                'user_id' => $user['user_id'], 
                'unique_id' => $user['unique_id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'loan_count' => $loanCount,
                'unpaid_fines' => $unpaidFines,
                'is_eligible' => $isEligible,
                'already_cleared' => $alreadyCleared
            ];

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        $this->redirectBack();
    }

    private function processClearance() {
        $userId = $_POST['user_id'] ?? '';
        
        if (empty($userId)) {
            $_SESSION['error'] = "Invalid User.";
            $this->redirectBack();
        }

        try {
            $stmt = $this->db->prepare("UPDATE Users SET is_cleared = 1 WHERE user_id = ?");
            $stmt->execute([$userId]);

            $_SESSION['success'] = "User has been officially marked as CLEARED.";
            
            if (isset($_SESSION['clearance_data'])) {
                $_SESSION['clearance_data']['is_cleared'] = true;
                $_SESSION['clearance_data']['already_cleared'] = true;
                $_SESSION['clearance_data']['is_eligible'] = true;
            }

        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
        
        $this->redirectBack();
    }

    private function getUserPenaltiesJson() {
        header('Content-Type: application/json');
        $userId = $_GET['user_id'] ?? '';

        if (empty($userId)) {
            echo json_encode(['success' => false, 'message' => 'No User ID provided']);
            exit();
        }

        try {
            $stmt = $this->db->prepare("SELECT user_id, unique_id, first_name, last_name FROM Users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit();
            }

            $stmt = $this->db->prepare("
                SELECT penalty_id, amount, reason, created_at 
                FROM Penalties 
                WHERE user_id = ? AND is_paid = 0
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $penalties = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($penalties as &$p) {
                $p['date'] = date('M d, Y', strtotime($p['created_at']));
            }

            echo json_encode([
                'success' => true,
                'user' => [
                    'user_id' => $user['user_id'],
                    'unique_id' => $user['unique_id'],
                    'name' => $user['first_name'] . ' ' . $user['last_name']
                ],
                'penalties' => $penalties
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit();
    }

    private function processPayAll() {
        $userId = $_POST['user_id'] ?? '';
        $method = $_POST['payment_method'] ?? 'Cash';

        if (empty($userId)) {
            $_SESSION['error'] = "Invalid user for payment.";
            $this->redirectBack();
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT penalty_id, amount FROM Penalties WHERE user_id = ? AND is_paid = 0");
            $stmt->execute([$userId]);
            $unpaid = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($unpaid)) {
                throw new Exception("No unpaid penalties found for this user.");
            }

            $totalPaid = 0;

            foreach ($unpaid as $p) {
                $upd = $this->db->prepare("UPDATE Penalties SET is_paid = 1 WHERE penalty_id = ?");
                $upd->execute([$p['penalty_id']]);

                $ins = $this->db->prepare("INSERT INTO Payments (penalty_id, user_id, amount_paid, method) VALUES (?, ?, ?, ?)");
                $ins->execute([$p['penalty_id'], $userId, $p['amount'], $method]);
                
                $totalPaid += $p['amount'];
            }

            $this->db->commit();
            $_SESSION['success'] = "Successfully processed payment of $" . number_format($totalPaid, 2) . ". All penalties cleared.";

        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = "Payment failed: " . $e->getMessage();
        }

        header("Location: /SmartLWA/app/views/staff_penalties.php");
        exit();
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