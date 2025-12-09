-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 04:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smartlwa`
--
CREATE DATABASE IF NOT EXISTS `smartlwa` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `smartlwa`;

-- --------------------------------------------------------

--
-- Table structure for table `academicperiods`
--

CREATE TABLE `academicperiods` (
  `period_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'e.g., Spring 2025, First Semester',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Only one period should be active at a time.',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academicperiods`
--

INSERT INTO `academicperiods` (`period_id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(1, '1st Semester AY 2025-2026', '2025-08-01', '2025-12-15', 1, '2025-10-14 06:05:08'),
(2, '2nd Semester AY 2025-2026', '2026-01-10', '2026-05-20', 0, '2025-10-14 06:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `bookcopies`
--

CREATE TABLE `bookcopies` (
  `copy_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `call_number` varchar(50) NOT NULL COMMENT 'Unique identifier for physical item location (e.g., FIC SAL C1-01, FIC SAL C1-02)',
  `barcode` varchar(100) NOT NULL COMMENT 'Scannable unique identifier for the specific copy',
  `status` enum('available','on_loan','in_repair','lost','withdrawn') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookcopies`
--

INSERT INTO `bookcopies` (`copy_id`, `book_id`, `call_number`, `barcode`, `status`, `created_at`) VALUES
(1, 1, 'QA76.76.M52 M37 2008 C1', 'BC1001', 'in_repair', '2025-10-14 06:05:08'),
(2, 1, 'QA76.76.M52 M37 2008 C2', 'BC1002', 'available', '2025-10-14 06:05:08'),
(3, 2, 'QA76.9.D3 K54 2017', 'BC2001', 'in_repair', '2025-10-14 06:05:08'),
(4, 3, 'QA76.64 G35 1994', 'BC3001', 'available', '2025-10-14 06:05:08'),
(1000, 100, 'FIC TOL H1', 'BARC1000', 'available', '2025-10-20 05:32:35'),
(1001, 100, 'FIC TOL H2', 'BARC1001', 'available', '2025-10-20 05:32:35'),
(1002, 100, 'FIC TOL H3', 'BARC1002', 'available', '2025-10-20 05:32:35'),
(1003, 100, 'FIC TOL H4', 'BARC1003', 'available', '2025-10-20 05:32:35'),
(1004, 100, 'FIC TOL H5', 'BARC1004', 'available', '2025-10-20 05:32:35'),
(1005, 100, 'FIC TOL H6', 'BARC1005', 'available', '2025-10-20 05:32:35'),
(1006, 100, 'FIC TOL H7', 'BARC1006', 'available', '2025-10-20 05:32:35'),
(1007, 100, 'FIC TOL H8', 'BARC1007', 'available', '2025-10-20 05:32:35'),
(1008, 100, 'FIC TOL H9', 'BARC1008', 'available', '2025-10-20 05:32:35'),
(1009, 100, 'FIC TOL H10', 'BARC1009', 'available', '2025-10-20 05:32:35'),
(1010, 100, 'FIC TOL H11', 'BARC1010', 'available', '2025-10-20 05:32:35'),
(1011, 100, 'FIC TOL H12', 'BARC1011', 'available', '2025-10-20 05:32:35'),
(1012, 100, 'FIC TOL H13', 'BARC1012', 'available', '2025-10-20 05:32:35'),
(1013, 100, 'FIC TOL H14', 'BARC1013', 'available', '2025-10-20 05:32:35'),
(1014, 100, 'FIC TOL H15', 'BARC1014', 'available', '2025-10-20 05:32:35'),
(1015, 101, 'FIC SAL C1', 'BARC1015', 'available', '2025-10-20 05:32:35'),
(1016, 101, 'FIC SAL C2', 'BARC1016', 'available', '2025-10-20 05:32:35'),
(1017, 101, 'FIC SAL C3', 'BARC1017', 'available', '2025-10-20 05:32:35'),
(1018, 101, 'FIC SAL C4', 'BARC1018', 'available', '2025-10-20 05:32:35'),
(1019, 101, 'FIC SAL C5', 'BARC1019', 'available', '2025-10-20 05:32:35'),
(1020, 101, 'FIC SAL C6', 'BARC1020', 'available', '2025-10-20 05:32:35'),
(1021, 101, 'FIC SAL C7', 'BARC1021', 'available', '2025-10-20 05:32:35'),
(1022, 101, 'FIC SAL C8', 'BARC1022', 'available', '2025-10-20 05:32:35'),
(1023, 102, 'FIC LEE T1', 'BARC1023', 'available', '2025-10-20 05:32:35'),
(1024, 102, 'FIC LEE T2', 'BARC1024', 'available', '2025-10-20 05:32:35'),
(1025, 102, 'FIC LEE T3', 'BARC1025', 'available', '2025-10-20 05:32:35'),
(1026, 102, 'FIC LEE T4', 'BARC1026', 'available', '2025-10-20 05:32:35'),
(1027, 102, 'FIC LEE T5', 'BARC1027', 'available', '2025-10-20 05:32:35'),
(1028, 102, 'FIC LEE T6', 'BARC1028', 'available', '2025-10-20 05:32:35'),
(1029, 102, 'FIC LEE T7', 'BARC1029', 'available', '2025-10-20 05:32:35'),
(1030, 102, 'FIC LEE T8', 'BARC1030', 'available', '2025-10-20 05:32:35'),
(1031, 102, 'FIC LEE T9', 'BARC1031', 'available', '2025-10-20 05:32:35'),
(1032, 102, 'FIC LEE T10', 'BARC1032', 'available', '2025-10-20 05:32:35'),
(1033, 102, 'FIC LEE T11', 'BARC1033', 'available', '2025-10-20 05:32:35'),
(1034, 102, 'FIC LEE T12', 'BARC1034', 'available', '2025-10-20 05:32:35'),
(1035, 102, 'FIC LEE T13', 'BARC1035', 'available', '2025-10-20 05:32:35'),
(1036, 102, 'FIC LEE T14', 'BARC1036', 'available', '2025-10-20 05:32:35'),
(1037, 102, 'FIC LEE T15', 'BARC1037', 'available', '2025-10-20 05:32:35'),
(1038, 102, 'FIC LEE T16', 'BARC1038', 'available', '2025-10-20 05:32:35'),
(1039, 102, 'FIC LEE T17', 'BARC1039', 'available', '2025-10-20 05:32:35'),
(1040, 102, 'FIC LEE T18', 'BARC1040', 'available', '2025-10-20 05:32:35'),
(1041, 102, 'FIC LEE T19', 'BARC1041', 'available', '2025-10-20 05:32:35'),
(1042, 102, 'FIC LEE T20', 'BARC1042', 'available', '2025-10-20 05:32:35'),
(1043, 103, 'FIC BRO D1', 'BARC1043', 'available', '2025-10-20 05:32:35'),
(1044, 103, 'FIC BRO D2', 'BARC1044', 'available', '2025-10-20 05:32:35'),
(1045, 103, 'FIC BRO D3', 'BARC1045', 'available', '2025-10-20 05:32:35'),
(1046, 103, 'FIC BRO D4', 'BARC1046', 'available', '2025-10-20 05:32:35'),
(1047, 103, 'FIC BRO D5', 'BARC1047', 'available', '2025-10-20 05:32:35'),
(1048, 104, 'FIC FIT G1', 'BARC1048', 'available', '2025-10-20 05:32:35'),
(1049, 104, 'FIC FIT G2', 'BARC1049', 'available', '2025-10-20 05:32:35'),
(1050, 104, 'FIC FIT G3', 'BARC1050', 'available', '2025-10-20 05:32:35'),
(1051, 104, 'FIC FIT G4', 'BARC1051', 'available', '2025-10-20 05:32:35'),
(1052, 104, 'FIC FIT G5', 'BARC1052', 'available', '2025-10-20 05:32:35'),
(1053, 104, 'FIC FIT G6', 'BARC1053', 'available', '2025-10-20 05:32:35'),
(1054, 104, 'FIC FIT G7', 'BARC1054', 'available', '2025-10-20 05:32:35'),
(1055, 104, 'FIC FIT G8', 'BARC1055', 'available', '2025-10-20 05:32:35'),
(1056, 104, 'FIC FIT G9', 'BARC1056', 'available', '2025-10-20 05:32:35'),
(1057, 104, 'FIC FIT G10', 'BARC1057', 'available', '2025-10-20 05:32:35'),
(1058, 104, 'FIC FIT G11', 'BARC1058', 'available', '2025-10-20 05:32:35'),
(1059, 104, 'FIC FIT G12', 'BARC1059', 'available', '2025-10-20 05:32:35'),
(1060, 105, 'NON HAR S1', 'BARC1060', 'available', '2025-10-20 05:32:36'),
(1061, 105, 'NON HAR S2', 'BARC1061', 'available', '2025-10-20 05:32:36'),
(1062, 105, 'NON HAR S3', 'BARC1062', 'available', '2025-10-20 05:32:36'),
(1063, 105, 'NON HAR S4', 'BARC1063', 'available', '2025-10-20 05:32:36'),
(1064, 105, 'NON HAR S5', 'BARC1064', 'available', '2025-10-20 05:32:36'),
(1065, 105, 'NON HAR S6', 'BARC1065', 'available', '2025-10-20 05:32:36'),
(1066, 105, 'NON HAR S7', 'BARC1066', 'available', '2025-10-20 05:32:36'),
(1067, 105, 'NON HAR S8', 'BARC1067', 'available', '2025-10-20 05:32:36'),
(1068, 105, 'NON HAR S9', 'BARC1068', 'available', '2025-10-20 05:32:36'),
(1069, 105, 'NON HAR S10', 'BARC1069', 'available', '2025-10-20 05:32:36'),
(1070, 106, 'TEC LUT P1', 'BARC1070', 'in_repair', '2025-10-20 05:32:36'),
(1071, 106, 'TEC LUT P2', 'BARC1071', 'available', '2025-10-20 05:32:36'),
(1072, 106, 'TEC LUT P3', 'BARC1072', 'available', '2025-10-20 05:32:36'),
(1073, 106, 'TEC LUT P4', 'BARC1073', 'available', '2025-10-20 05:32:36'),
(1074, 106, 'TEC LUT P5', 'BARC1074', 'available', '2025-10-20 05:32:36'),
(1075, 106, 'TEC LUT P6', 'BARC1075', 'available', '2025-10-20 05:32:36'),
(1076, 106, 'TEC LUT P7', 'BARC1076', 'available', '2025-10-20 05:32:36'),
(1077, 107, 'FIC ROW H1', 'BARC1077', 'available', '2025-10-20 05:32:36'),
(1078, 107, 'FIC ROW H2', 'BARC1078', 'available', '2025-10-20 05:32:36'),
(1079, 107, 'FIC ROW H3', 'BARC1079', 'available', '2025-10-20 05:32:36'),
(1080, 107, 'FIC ROW H4', 'BARC1080', 'available', '2025-10-20 05:32:36'),
(1081, 107, 'FIC ROW H5', 'BARC1081', 'available', '2025-10-20 05:32:36'),
(1082, 107, 'FIC ROW H6', 'BARC1082', 'available', '2025-10-20 05:32:36'),
(1083, 107, 'FIC ROW H7', 'BARC1083', 'available', '2025-10-20 05:32:36'),
(1084, 107, 'FIC ROW H8', 'BARC1084', 'available', '2025-10-20 05:32:36'),
(1085, 107, 'FIC ROW H9', 'BARC1085', 'available', '2025-10-20 05:32:36'),
(1086, 107, 'FIC ROW H10', 'BARC1086', 'available', '2025-10-20 05:32:36'),
(1087, 107, 'FIC ROW H11', 'BARC1087', 'available', '2025-10-20 05:32:36'),
(1088, 107, 'FIC ROW H12', 'BARC1088', 'available', '2025-10-20 05:32:36'),
(1089, 107, 'FIC ROW H13', 'BARC1089', 'available', '2025-10-20 05:32:36'),
(1090, 107, 'FIC ROW H14', 'BARC1090', 'available', '2025-10-20 05:32:36'),
(1091, 107, 'FIC ROW H15', 'BARC1091', 'available', '2025-10-20 05:32:36'),
(1092, 107, 'FIC ROW H16', 'BARC1092', 'available', '2025-10-20 05:32:36'),
(1093, 107, 'FIC ROW H17', 'BARC1093', 'available', '2025-10-20 05:32:36'),
(1094, 107, 'FIC ROW H18', 'BARC1094', 'available', '2025-10-20 05:32:36'),
(1095, 108, 'FIC ADA H1', 'BARC1095', 'available', '2025-10-20 05:32:36'),
(1096, 108, 'FIC ADA H2', 'BARC1096', 'available', '2025-10-20 05:32:36'),
(1097, 108, 'FIC ADA H3', 'BARC1097', 'available', '2025-10-20 05:32:36'),
(1098, 108, 'FIC ADA H4', 'BARC1098', 'available', '2025-10-20 05:32:36'),
(1099, 108, 'FIC ADA H5', 'BARC1099', 'available', '2025-10-20 05:32:36'),
(1100, 108, 'FIC ADA H6', 'BARC1100', 'available', '2025-10-20 05:32:36'),
(1101, 108, 'FIC ADA H7', 'BARC1101', 'available', '2025-10-20 05:32:36'),
(1102, 108, 'FIC ADA H8', 'BARC1102', 'available', '2025-10-20 05:32:36'),
(1103, 108, 'FIC ADA H9', 'BARC1103', 'available', '2025-10-20 05:32:36'),
(1104, 109, 'FIC OWE W1', 'BARC1104', 'available', '2025-10-20 05:32:36'),
(1105, 109, 'FIC OWE W2', 'BARC1105', 'available', '2025-10-20 05:32:36'),
(1106, 109, 'FIC OWE W3', 'BARC1106', 'available', '2025-10-20 05:32:36'),
(1107, 109, 'FIC OWE W4', 'BARC1107', 'available', '2025-10-20 05:32:36'),
(1108, 109, 'FIC OWE W5', 'BARC1108', 'available', '2025-10-20 05:32:36'),
(1109, 109, 'FIC OWE W6', 'BARC1109', 'available', '2025-10-20 05:32:36'),
(1110, 109, 'FIC OWE W7', 'BARC1110', 'available', '2025-10-20 05:32:36'),
(1111, 109, 'FIC OWE W8', 'BARC1111', 'available', '2025-10-20 05:32:36'),
(1112, 109, 'FIC OWE W9', 'BARC1112', 'available', '2025-10-20 05:32:36'),
(1113, 109, 'FIC OWE W10', 'BARC1113', 'available', '2025-10-20 05:32:36'),
(1114, 109, 'FIC OWE W11', 'BARC1114', 'available', '2025-10-20 05:32:36'),
(1115, 109, 'FIC OWE W12', 'BARC1115', 'available', '2025-10-20 05:32:36'),
(1116, 109, 'FIC OWE W13', 'BARC1116', 'available', '2025-10-20 05:32:36'),
(1117, 109, 'FIC OWE W14', 'BARC1117', 'available', '2025-10-20 05:32:36'),
(1118, 112, 'CALL-00112-01', 'BAR-00112-01', 'available', '2025-11-26 07:36:35'),
(1119, 111, 'GRE-0111-C01', 'BC-111-176414369459', 'on_loan', '2025-11-26 07:54:54'),
(1120, 111, 'GRE-0111-C02', 'BC-111-176414369521', 'available', '2025-11-26 07:54:55'),
(1121, 111, 'GRE-0111-C03', 'BC-111-176414369654', 'available', '2025-11-26 07:54:56'),
(1122, 111, 'GRE-0111-C04', 'BC-111-176414369717', 'available', '2025-11-26 07:54:57'),
(1123, 111, 'GRE-0111-C05', 'BC-111-176414369931', 'available', '2025-11-26 07:54:59'),
(1124, 111, 'GRE-0111-C06', 'BC-111-176414370058', 'available', '2025-11-26 07:55:00'),
(1125, 111, 'GRE-0111-C07', 'BC-111-176414370142', 'available', '2025-11-26 07:55:01'),
(1126, 111, 'GRE-0111-C08', 'BC-111-176414370145', 'available', '2025-11-26 07:55:01'),
(1127, 111, 'GRE-0111-C09', 'BC-111-176414370293', 'available', '2025-11-26 07:55:02');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(150) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL COMMENT 'Price of the book in your currency',
  `cover_image_url` varchar(512) DEFAULT NULL COMMENT 'URL fetched from Google Books API for cover image.',
  `total_copies` int(11) NOT NULL DEFAULT 1 COMMENT 'Total number of physical copies (maintained by application or trigger).',
  `archived` tinyint(1) DEFAULT 0 COMMENT 'Set to true if title is removed from circulation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `isbn`, `title`, `author`, `publisher`, `publication_year`, `price`, `cover_image_url`, `total_copies`, `archived`, `created_at`) VALUES
(1, '9780132350884', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', '2008', 850.00, 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg', 3, 0, '2025-10-14 06:05:08'),
(2, '9781492078005', 'Designing Data-Intensive Applications', 'Martin Kleppmann', 'O\'Reilly Media', '2017', 1200.00, 'https://covers.openlibrary.org/b/isbn/9781492078005-L.jpg', 2, 0, '2025-10-14 06:05:08'),
(3, '9780201633610', 'Design Patterns: Elements of Reusable Object-Oriented Software', 'Erich Gamma', 'Addison-Wesley', '1994', 950.00, 'https://covers.openlibrary.org/b/isbn/9780201633610-L.jpg', 1, 0, '2025-10-14 06:05:08'),
(100, '9780547928227', 'The Hobbit', 'J.R.R. Tolkien', 'Houghton Mifflin', '1937', NULL, 'https://covers.openlibrary.org/b/isbn/9780547928227-L.jpg', 15, 0, '2025-10-20 05:32:35'),
(101, '9780321765723', 'The Catcher in the Rye', 'J.D. Salinger', 'Little, Brown', '1951', NULL, 'https://covers.openlibrary.org/b/isbn/9780321765723-L.jpg', 8, 0, '2025-10-20 05:32:35'),
(102, '9780061120084', 'To Kill a Mockingbird', 'Harper Lee', 'Harper Perennial', '1960', NULL, 'https://covers.openlibrary.org/b/isbn/9780061120084-L.jpg', 20, 0, '2025-10-20 05:32:35'),
(103, '9780385537858', 'The Da Vinci Code', 'Dan Brown', 'Doubleday', '2003', NULL, 'https://covers.openlibrary.org/b/isbn/9780385537858-L.jpg', 5, 0, '2025-10-20 05:32:35'),
(104, '9780743273565', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', '1925', NULL, 'https://covers.openlibrary.org/b/isbn/9780743273565-L.jpg', 12, 0, '2025-10-20 05:32:35'),
(105, '9780743272223', 'Sapiens: A Brief History', 'Yuval Noah Harari', 'Harper', '2014', NULL, 'https://covers.openlibrary.org/b/isbn/9780743272223-L.jpg', 10, 0, '2025-10-20 05:32:35'),
(106, '9781449331818', 'Learning Python', 'Mark Lutz', 'O\'Reilly Media', '2013', NULL, 'https://covers.openlibrary.org/b/isbn/9781449331818-L.jpg', 7, 0, '2025-10-20 05:32:35'),
(107, '9780590353403', 'Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', 'Scholastic', '1997', NULL, 'https://covers.openlibrary.org/b/isbn/9780590353403-L.jpg', 18, 0, '2025-10-20 05:32:35'),
(108, '9780345391803', 'The Hitchhiker\'s Guide to the Galaxy', 'Douglas Adams', 'Del Rey', '1979', NULL, 'https://covers.openlibrary.org/b/isbn/9780345391803-L.jpg', 9, 0, '2025-10-20 05:32:35'),
(109, '9781984801944', 'Where the Crawdads Sing', 'Delia Owens', 'G.P. Putnam\'s Sons', '2018', NULL, 'https://covers.openlibrary.org/b/isbn/9781984801944-L.jpg', 14, 0, '2025-10-20 05:32:35'),
(110, '222', 'Jerich\'s Test', 'Jerich', 'Jerich', '2025', 25.99, NULL, 1, 1, '2025-11-25 13:43:23'),
(111, '0-06-039144-8', 'Wicked: the life and times of the Wicked Witch of the West', 'Gregory Maguire', 'ReganBooks', '1995', 128.50, 'https://covers.openlibrary.org/b/isbn/0-06-039144-8-L.jpg', 10, 0, '2025-11-26 07:29:40'),
(112, '111', 'test', 'test', 'test', '2004', 222.00, NULL, 1, 0, '2025-11-26 07:36:35'),
(113, 'test1', 'test1', 'test1', 'test1', '0000', 22.00, NULL, 1, 0, '2025-11-26 07:46:27');

-- --------------------------------------------------------

--
-- Table structure for table `borrowingrecords`
--

CREATE TABLE `borrowingrecords` (
  `record_id` int(11) NOT NULL,
  `copy_id` int(11) NOT NULL COMMENT 'The specific physical book copy that was borrowed.',
  `book_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `borrow_date` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` date NOT NULL,
  `return_date` datetime DEFAULT NULL COMMENT 'NULL if the book is still out',
  `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
  `period_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowingrecords`
--

INSERT INTO `borrowingrecords` (`record_id`, `copy_id`, `book_id`, `user_id`, `borrow_date`, `due_date`, `return_date`, `status`, `period_id`) VALUES
(1, 2, 1, 2, '2025-09-01 00:00:00', '2025-09-15', '2025-11-25 17:09:28', 'overdue', 1),
(2, 3, 2, 4, '2025-08-20 00:00:00', '2025-09-05', NULL, 'returned', 1),
(3, 1, 1, 5, '2025-11-25 23:19:09', '2025-12-02', '2025-11-25 16:29:37', 'returned', NULL),
(4, 1, 1, 5, '2025-11-25 23:29:47', '2025-12-02', '2025-11-25 16:30:37', 'returned', NULL),
(5, 4, 3, 4, '2025-11-25 23:39:54', '2025-12-02', '2025-11-25 16:40:18', 'returned', NULL),
(6, 1070, 106, 5, '2025-11-26 13:16:12', '2025-12-03', '2025-11-26 06:16:47', 'returned', NULL),
(7, 1095, 108, 5, '2025-11-26 13:58:06', '2025-12-03', '2025-11-26 07:42:34', 'returned', NULL),
(8, 4, 3, 5, '2025-11-26 14:06:14', '2025-12-03', '2025-11-26 08:14:05', 'overdue', NULL),
(9, 1060, 105, 5, '2025-11-26 14:28:13', '2025-12-03', '2025-11-26 08:58:02', 'returned', NULL),
(10, 3, 2, 2, '2025-11-26 14:28:44', '2025-12-03', '2025-11-26 07:42:50', 'returned', NULL),
(11, 1071, 106, 2, '2025-11-26 14:32:49', '2025-12-03', '2025-11-26 07:43:12', 'returned', NULL),
(12, 1119, 111, 5, '2025-11-28 09:25:12', '2025-12-05', NULL, 'borrowed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `penalty_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `method` varchar(50) DEFAULT NULL COMMENT 'e.g., Cash, G-Cash, Credit Card'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `penalty_id`, `user_id`, `amount_paid`, `payment_date`, `method`) VALUES
(1, 2, 4, 50.00, '2025-10-14 14:05:08', 'cash'),
(2, 3, 5, 850.00, '2025-11-25 23:52:58', 'Cash'),
(3, 1, 2, 150.00, '2025-11-26 00:02:36', 'Cash'),
(4, 4, 2, 50.00, '2025-11-26 00:09:35', 'Cash'),
(5, 5, 5, 50.00, '2025-11-26 13:19:18', 'Cash'),
(6, 6, 2, 600.00, '2025-11-26 14:43:20', 'Cash'),
(7, 7, 5, 50.00, '2025-11-28 09:24:18', 'Cash');

-- --------------------------------------------------------

--
-- Table structure for table `penalties`
--

CREATE TABLE `penalties` (
  `penalty_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `record_id` int(11) DEFAULT NULL COMMENT 'Links penalty to a specific borrowing record (e.g., overdue fine)',
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL COMMENT 'e.g., Overdue, Book Damage, Lost Book',
  `is_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penalties`
--

INSERT INTO `penalties` (`penalty_id`, `user_id`, `record_id`, `amount`, `reason`, `is_paid`, `created_at`) VALUES
(1, 2, 1, 150.00, 'Overdue book return', 1, '2025-10-14 06:05:08'),
(2, 4, 2, 50.00, 'Lost book copy', 1, '2025-10-14 06:05:08'),
(3, 5, 4, 850.00, 'Book Damage Fee', 1, '2025-11-25 15:30:37'),
(4, 2, 1, 50.00, 'Overdue Fine', 1, '2025-11-25 16:09:28'),
(5, 5, 6, 50.00, 'Damaged Book Fee (50%)', 1, '2025-11-26 05:16:47'),
(6, 2, 10, 600.00, 'Damaged Book Fee (50%)', 1, '2025-11-26 06:42:50'),
(7, 5, 8, 50.00, 'Overdue Fine', 1, '2025-11-26 07:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reservation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expiry_date` datetime NOT NULL COMMENT 'Date when reservation is automatically cancelled if not picked up',
  `status` enum('active','ready_for_pickup','fulfilled','cancelled') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `book_id`, `user_id`, `reservation_date`, `expiry_date`, `status`) VALUES
(16, 1, 5, '2025-10-20 13:23:08', '2025-10-27 07:23:08', 'fulfilled'),
(17, 3, 4, '2025-10-20 13:23:25', '2025-10-27 07:23:25', 'fulfilled'),
(18, 110, 5, '2025-11-25 22:30:32', '2025-12-02 15:30:32', 'cancelled'),
(19, 106, 5, '2025-11-26 13:13:16', '2025-12-03 06:13:16', 'fulfilled'),
(20, 108, 5, '2025-11-26 13:57:33', '2025-12-03 06:57:33', 'fulfilled'),
(21, 3, 5, '2025-11-26 14:05:54', '2025-12-03 07:05:54', 'fulfilled'),
(22, 105, 5, '2025-11-26 14:23:56', '2025-12-03 07:23:56', 'fulfilled'),
(23, 2, 2, '2025-11-26 14:28:27', '2025-12-03 07:28:27', 'fulfilled'),
(24, 106, 2, '2025-11-26 14:31:17', '2025-12-03 07:31:17', 'fulfilled'),
(27, 111, 5, '2025-11-28 09:23:51', '2025-12-05 02:23:51', 'fulfilled');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `unique_id` varchar(50) NOT NULL COMMENT 'Student ID, Employee Code, etc.',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Stores the secure hash of the password.',
  `role` enum('student','teacher','librarian','staff') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `unique_id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `is_active`, `is_cleared`, `created_at`, `updated_at`) VALUES
(1, 'L1003', 'Maria', 'Librarian', 'maria.librarian@lwa.edu', '$2y$10$h2zLRiSsEIoOXWvESZbm5Oney062JhdbY6nvfoSx7LJ46vSTzuwgS', 'librarian', 1, 0, '2025-10-14 06:05:08', '2025-10-14 06:22:02'),
(2, 'S2003', 'John', 'Doe', 'john.doe@lwa.edu', '$2y$10$h2zLRiSsEIoOXWvESZbm5Oney062JhdbY6nvfoSx7LJ46vSTzuwgS', 'student', 1, 1, '2025-10-14 06:05:08', '2025-11-26 06:43:29'),
(3, 'ST3001', 'Ella', 'Staff', 'ella.staff@lwa.edu', '$2y$10$h2zLRiSsEIoOXWvESZbm5Oney062JhdbY6nvfoSx7LJ46vSTzuwgS', 'staff', 1, 0, '2025-10-14 06:05:08', '2025-10-14 06:22:02'),
(4, 'T4001', 'Mark', 'Teacher', 'mark.teacher@lwa.edu', '$2y$10$h2zLRiSsEIoOXWvESZbm5Oney062JhdbY6nvfoSx7LJ46vSTzuwgS', 'teacher', 1, 0, '2025-10-14 06:05:08', '2025-10-14 06:22:02'),
(5, 'S2004', 'Jerich', 'Malapit', 'jerich.malapit@ctu.edu.ph', '$2y$10$h2zLRiSsEIoOXWvESZbm5Oney062JhdbY6nvfoSx7LJ46vSTzuwgS', 'student', 1, 0, '2025-10-14 06:12:05', '2025-11-26 06:06:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academicperiods`
--
ALTER TABLE `academicperiods`
  ADD PRIMARY KEY (`period_id`);

--
-- Indexes for table `bookcopies`
--
ALTER TABLE `bookcopies`
  ADD PRIMARY KEY (`copy_id`),
  ADD UNIQUE KEY `call_number` (`call_number`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `borrowingrecords`
--
ALTER TABLE `borrowingrecords`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `copy_id` (`copy_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_borrowingrecords_period` (`period_id`),
  ADD KEY `fk_borrowing_book` (`book_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `penalty_id` (`penalty_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `penalties`
--
ALTER TABLE `penalties`
  ADD PRIMARY KEY (`penalty_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD UNIQUE KEY `user_book_active_reservation` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `unique_id` (`unique_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academicperiods`
--
ALTER TABLE `academicperiods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bookcopies`
--
ALTER TABLE `bookcopies`
  MODIFY `copy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1130;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `borrowingrecords`
--
ALTER TABLE `borrowingrecords`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `penalties`
--
ALTER TABLE `penalties`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookcopies`
--
ALTER TABLE `bookcopies`
  ADD CONSTRAINT `bookcopies_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `borrowingrecords`
--
ALTER TABLE `borrowingrecords`
  ADD CONSTRAINT `borrowingrecords_ibfk_1` FOREIGN KEY (`copy_id`) REFERENCES `bookcopies` (`copy_id`),
  ADD CONSTRAINT `borrowingrecords_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_borrowing_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `fk_borrowingrecords_period` FOREIGN KEY (`period_id`) REFERENCES `academicperiods` (`period_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`penalty_id`) REFERENCES `penalties` (`penalty_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `penalties`
--
ALTER TABLE `penalties`
  ADD CONSTRAINT `penalties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `penalties_ibfk_2` FOREIGN KEY (`record_id`) REFERENCES `borrowingrecords` (`record_id`) ON DELETE SET NULL;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
