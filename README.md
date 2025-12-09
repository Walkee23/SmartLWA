# 📚 Smart Library Web System (SmartLWA)

> **Project Title:** SMARTLWA - Smart Library Web System for Multi-User Management

A robust, role-based **Library Management System** built with **Vanilla PHP** and **MySQL**. This application is designed to streamline library operations for Students, Teachers, Librarians, and Staff, enforcing specific academic borrowing rules and automating clearance procedures.

---

## 🚀 Project Overview

The **SmartLWA** system manages the complete lifecycle of library resources—from book acquisition to borrowing, returning, and penalty calculation. It is architected to support four distinct user roles, each with specific privileges and business rules defined in the project brief.

### 🔑 Key Features

* **Role-Based Access Control (RBAC):** Secure login redirection for Students, Teachers, Librarians, and Staff.
* **Automated Circulation:** Logic for borrowing, returning, and handling overdue items.
* **Real-Time Inventory:** Automatic tracking of available vs. total book copies.
* **Penalty & Clearance System:** Automated calculation of fines for overdue/damaged books and status checking for user clearance.
* **Metadata Integration:** Automatic fetching of book covers using the Open Library API.

---

## 👥 User Roles & Business Rules

The system enforces specific operational rules based on the user type:

### 🎓 1. Student
* **Borrowing Limit:** Strictly restricted to borrowing **up to 3 books** per semester.
* **Reservations:** Can browse the catalog and reserve books online.
* **Clearance:** Must return all books and pay outstanding penalties to be marked as "Cleared".

### 🍎 2. Teacher
* **Borrowing Limit:** **Unlimited** book borrowing privileges.
* **Clearance:** Mandatory return of all borrowed items at the end of the academic semester.
* **Resource Management:** Access to reserve materials for class requirements.

### 📖 3. Librarian
* **Inventory Control:** Full authority to **add, update, and archive** book titles.
* **Copy Management:** Manage physical copies (barcodes, call numbers).
* **Digital Assets:** Updates book cover images via API integration.

### 🛡️ 4. Staff
* **Circulation Desk:** Facilitates the physical borrowing and returning process.
* **Penalty Management:** Processes fines for overdue, damaged, or lost books.
* **Clearance Processing:** Audits borrower records and officially clears users who have settled liabilities.

---

## 🛠️ Technology Stack

* **Backend:** Vanilla PHP 8.0+ (MVC Architecture)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, JavaScript (ES6)
* **Styling:** Bootstrap 5.3.2 (Responsive)
* **Server:** Apache (requires `mod_rewrite`)

---

## 📂 Folder Structure

```text
SmartLWA/
├── app/
│   ├── controllers/      # Logic for Auth, Books, and Circulation
│   ├── models/           # Database connection instance
│   └── views/            # Role-specific Dashboards and UI components
├── public/
│   ├── index.php         # Application entry point
│   ├── css/            
│   └── js/               # Assets and images
├── config.php            # Database configuration constants
├── fetch_book_covers.php # Script to sync book covers
├── generatehash.php      # Utility for password hashing
├── smartlwa database schema.sql  # Database import file
└── .htaccess             # URL routing rules
```

---

## ⚙️ Installation Guide

### 1. Clone the Repository

```text
git clone https://github.com/Walkee23/SmartLWA.git
cd SmartLWA
```

### 2. Database Setup
* Create a MySQL database named **SmartLWA**.
* Import the SQL file smartlwa database schema.sql into your database.

### 3. Configuration
* Open config.php and verify your database credentials:

```text
define('DB_HOST', 'localhost');
define('DB_NAME', 'SmartLWA');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Server Configuration
* Ensure your Apache server has mod_rewrite enabled.
* Place the project in your web root (e.g., htdocs).
* Access via browser: http://localhost/SmartLWA/

---

## 🔐 Default Credentials

The system comes pre-seeded with users for testing purposes. All accounts share the same password for demonstration ease.
| Role      | Email                   | Password          |
|-----------|-------------------------|-------------------|
| Student   | john.doe@lwa.edu        | password123       |
| Teacher   | mark.teacher@lwa.edu    | password123       |
| Librarian | maria.librarian@lwa.edu | password123       |
| Staff     | ella.staff@lwa.edu      | password123       |

*Note: Passwords are securely hashed in the database. Use generatehash.php if you need to create new credentials.*

---

**⚠️ Disclaimer:** This **Smart Library Web System** was developed for **educational and academic purposes only** as a requirement for the *Library Web Application* project brief. It is intended as a school activity demonstration and is not designed for commercial use or production environments. All data, user accounts, and book records included in this repository are fictitious and used solely for testing and demonstration purposes.