<?php
session_start();
require_once __DIR__ . '/../models/database.php';

// Protect the controller: Only logged-in users (students/teachers) can reserve
if (!isset($_SESSION['user_id'])) {
    header("Location: /SmartLWA/views/login.php");
    exit();
}

class ReservationController
{
    private $db;
    private $userId;

    public function __construct($db)
    {
        $this->db = $db;
        $this->userId = $_SESSION['user_id'];
    }

    public function handleReservationAction()
    {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'reserve':
                $this->reserveBook($_GET['book_id'] ?? null);
                break;
            case 'cancel':
                $this->cancelReservation($_GET['res_id'] ?? null);
                break;
            default:
                $_SESSION['error'] = "Invalid reservation action.";
                $this->redirectBack();
        }
    }

    private function reserveBook($bookId)
    {
        if (empty($bookId) || !is_numeric($bookId)) {
            $_SESSION['error'] = "Invalid book selected.";
            $this->redirectBack();
        }

        try {
            // Check if the book exists and is not archived
            $stmt = $this->db->prepare("SELECT book_id FROM Books WHERE book_id = ? AND archived = FALSE");
            $stmt->execute([$bookId]);
            if ($stmt->rowCount() === 0) {
                $_SESSION['error'] = "Book not found or is no longer available.";
                $this->redirectBack();
            }

            // CHECK 1: Check if the user already has an active or ready_for_pickup reservation for this book
            $stmt = $this->db->prepare("SELECT reservation_id FROM Reservations 
                                      WHERE user_id = ? AND book_id = ? AND status IN ('active', 'ready_for_pickup')");
            $stmt->execute([$this->userId, $bookId]);
            
            // FIX: If a row is found, redirect immediately with an error message.
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "You already have an active reservation for this book (Book ID: {$bookId}).";
                $this->redirectBack();
                return; // Ensure the script stops here
            }
            
            // CHECK 2 (OPTIONAL but good practice for students): Check if the student has reached their 3-book limit
            // Since this is just a reservation, limits usually apply to *borrowing*, but a hard cap on reservations might be policy.
            // Based on your database schema (unique key constraint being the issue), the above check is the primary fix.


            // Determine the reservation expiry date (e.g., 7 days from now)
            $expiryDate = date('Y-m-d H:i:s', strtotime('+7 days'));

            // Insert the new reservation record
            $stmt = $this->db->prepare("
                INSERT INTO Reservations (book_id, user_id, expiry_date, status) 
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([$bookId, $this->userId, $expiryDate]);

            $_SESSION['success'] = "Book successfully reserved! You have 7 days to pick it up once it becomes ready.";
            header("Location: /SmartLWA/app/views/my_reservations.php");
            exit();
        } catch (PDOException $e) {
            // Catching the integrity violation is now redundant, but kept as a safeguard.
            $_SESSION['error'] = "Database error: Could not place reservation. " . htmlspecialchars($e->getMessage());
            $this->redirectBack();
        }
    }

    private function cancelReservation($reservationId)
    {
        if (empty($reservationId) || !is_numeric($reservationId)) {
            $_SESSION['error'] = "Invalid reservation ID.";
            $this->redirectBack();
        }

        try {
            // Cancel the reservation, ensuring it belongs to the current user
            $stmt = $this->db->prepare("
                UPDATE Reservations SET status = 'cancelled' 
                WHERE reservation_id = ? AND user_id = ? AND status IN ('active', 'ready_for_pickup')
            ");
            $stmt->execute([$reservationId, $this->userId]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Reservation successfully cancelled.";
            } else {
                $_SESSION['error'] = "Reservation not found, or cannot be cancelled in its current state.";
            }

            header("Location: /SmartLWA/app/views/my_reservations.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: Could not cancel reservation.";
            header("Location: /SmartLWA/app/views/my_reservations.php");
            exit();
        }
    }

    private function redirectBack()
    {
        // Redirects to the available books page, which is where the reserve action originates
        header("Location: /SmartLWA/app/views/available_books.php");
        exit();
    }
}

// Instantiate and run the controller
$controller = new ReservationController($pdo);
$controller->handleReservationAction();
