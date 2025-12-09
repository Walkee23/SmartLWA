Smart Library Web System (SmartLWA)

SmartLWA is a web-based library management system designed to streamline library operations for students, teachers, librarians, and staff. This project was developed to meet specific academic requirements for a multi-user environment with role-based access control and specific borrowing rules.

📖 Project Overview

The objective of this system is to manage library operations for four distinct user roles, ensuring efficient handling of borrowing, returning, reservations, and user clearance.

Key Goals

Role-Based Management: distinct interfaces and permissions for Students, Teachers, Librarians, and Staff.

Inventory Control: Real-time management of book copies and metadata.

Circulation Management: Handling loans, returns, and penalty calculations.

Clearance Automation: Automated checking of liabilities (unpaid fines or unreturned books) for student/teacher clearance.

🚀 Features by Role

🎓 Student

Borrowing Limit: Restricted to borrowing up to 3 books per semester.

Reservations: Ability to browse the catalog and reserve books online.

Dashboard: View current loans, due dates, and active reservations.

Clearance: Must return all books and pay fines to be marked as "Cleared".

👨‍🏫 Teacher

Unlimited Borrowing: No cap on the number of books borrowed.

Semester Clearance: Must return all books by the end of the academic semester.

Resource Management: Access to reserve books for class requirements.

📚 Librarian

Inventory Management: Add, update, and archive book titles.

Copy Management: Manage physical copies (barcodes, call numbers) for each title.

Metadata: Auto-fetch book covers via Open Library API.

💼 Staff

Circulation Desk: Facilitate physical borrowing and returning of books.

Penalty System: Calculate and process payments for overdue, damaged, or lost books.

Clearance Processing: View borrower status and officially clear users who have no outstanding liabilities.

🛠️ Technology Stack

Backend: PHP (Native/Vanilla) using PDO for database interactions.

Frontend: HTML5, CSS3, JavaScript.

Styling: Bootstrap 5.3.2 (Responsive Design).

Database: MySQL / MariaDB.

Architecture: MVC (Model-View-Controller) pattern.

📂 Project Structure

SmartLWA/
├── app/
│   ├── controllers/   # Logic for Auth, Books, Circulation, etc.
│   ├── models/        # Database connection and queries
│   └── views/         # Role-specific dashboards and UI pages
├── public/            # Entry point (index.php)
├── config.php         # Database configuration
├── .htaccess          # URL rewriting rules
└── smartlwa database schema.sql # Database import file


⚙️ Installation & Setup

Clone the Repository

git clone [https://github.com/yourusername/smartlwa.git](https://github.com/yourusername/smartlwa.git)


Database Setup

Create a MySQL database named SmartLWA.

Import the smartlwa database schema.sql file located in the root directory.

Note: The SQL file includes default users for testing (e.g., student, teacher, staff, librarian).

Configuration

Open config.php.

Update the database credentials if necessary:

define('DB_HOST', 'localhost');
define('DB_NAME', 'SmartLWA');
define('DB_USER', 'root');
define('DB_PASS', '');


Server Requirements

PHP 8.0 or higher.

Apache Server (XAMPP/WAMP/MAMP).

Important: Ensure mod_rewrite is enabled in Apache for the .htaccess routing to work correctly.

Access the App

Place the project folder in your web root (e.g., htdocs).

Navigate to http://localhost/SmartLWA/ in your browser.

🧪 Default Login Credentials (For Testing)

Role

Email

Password

Librarian

maria.librarian@lwa.edu

password123

Staff

ella.staff@lwa.edu

password123

Teacher

mark.teacher@lwa.edu

password123

Student

john.doe@lwa.edu

password123

(Note: Passwords in the database are hashed. If you need to reset them, use the generatehash.php utility included in the repo.)

📝 License

This project is for educational purposes as part of the Library System Activity.