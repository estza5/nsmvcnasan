-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2025 at 09:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_login_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน (FK to students.id)',
  `status` enum('present','late','absent','on_leave') NOT NULL COMMENT 'สถานะ: มา, สาย, ขาด, ลา',
  `check_in_date` date NOT NULL,
  `check_in_time` datetime DEFAULT current_timestamp() COMMENT 'เวลาที่บันทึก',
  `checked_by_user_id` int(11) NOT NULL COMMENT 'รหัสผู้ที่เช็คชื่อ (FK to users.id)',
  `late_reason` varchar(255) DEFAULT NULL COMMENT 'เหตุผลที่มาสาย'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `status`, `check_in_date`, `check_in_time`, `checked_by_user_id`, `late_reason`) VALUES
(3, 129, 'late', '2025-09-10', '2025-09-10 13:18:36', 12, 'ตื่นสาย');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'ชื่อแผนกวิชา',
  `level` varchar(50) DEFAULT NULL,
  `advisor_id` int(11) DEFAULT NULL COMMENT 'รหัสของครูที่ปรึกษา (FK to users.id)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `level`, `advisor_id`) VALUES
(2, 'เทคโนโลยีธุรกิจดิจิทัล', 'ปวช.1', 12),
(5, 'เทคโนโลยีธุรกิจดิจิทัล', 'ปวช.2', NULL),
(6, 'คอมพิวเตอร์ธุรกิจ', 'ปวช.3/1', NULL),
(7, 'คอมพิวเตอร์ธุรกิจ', 'ปวช.3/2', NULL),
(9, 'การบัญชี', 'ปวช.1', NULL),
(10, 'การบัญชี', 'ปวช.2', NULL),
(11, 'การบัญชี', 'ปวช.3', NULL),
(12, 'ช่างก่อสร้าง', 'ปวช.1', NULL),
(13, 'ช่างก่อสร้าง', 'ปวช.2', NULL),
(14, 'ช่างก่อสร้าง', 'ปวช.3', NULL),
(15, 'ช่างเชื่อมโลหะ', 'ปวช.1', NULL),
(16, 'ช่างเชื่อมโลหะ', 'ปวช.2', NULL),
(17, 'ช่างไฟฟ้า', 'ปวช.1', NULL),
(18, 'ช่างไฟฟ้า', 'ปวช.2', NULL),
(19, 'ช่างไฟฟ้า', 'ปวช.3', NULL),
(20, 'ช่างยนต์', 'ปวช.1', NULL),
(21, 'ช่างยนต์', 'ปวช.2', NULL),
(22, 'ช่างยนต์', 'ปวช.3', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_code` varchar(20) NOT NULL COMMENT 'รหัสนักเรียน',
  `first_name` varchar(100) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(100) NOT NULL COMMENT 'นามสกุล',
  `gender` enum('M','F') NOT NULL COMMENT 'M=ชาย, F=หญิง',
  `department_id` int(11) NOT NULL COMMENT 'รหัสแผนกวิชา (FK to departments.id)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_code`, `first_name`, `last_name`, `gender`, `department_id`) VALUES
(129, '6820201001', 'นางสาวกนกวรรณ', 'พรหมทองนาค', 'F', 9),
(130, '6820201002', 'นางสาวเกวลี', 'อนงค์ทอง', 'F', 9),
(131, '6820201003', 'นางสาวจิตดาพร', 'รักชาติ', 'F', 9),
(132, '6820201004', 'นางสาวณัฐณิฌาณ์', 'โกธยี่', 'F', 9),
(133, '6820201005', 'นางสาวบุลภรณ์', 'หลีแคล้ว', 'F', 9),
(134, '6820201007', 'นางสาวอรณิชา', 'โพธิ์อุลัย', 'F', 9);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('teacher','director','admin') NOT NULL DEFAULT 'teacher',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `department_id` int(11) DEFAULT NULL COMMENT 'รหัสแผนกสำหรับครู',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `first_name`, `last_name`, `password`, `role`, `status`, `department_id`, `created_at`) VALUES
(12, 'estza6', 'estza1122@gmail.com', 'รุ่งระวิน', 'พ่วงขำ', '$2y$10$sw8Ham1qBHc7h1j2j7oS3ubrLlaEa3ei1nhXzkCMj.gmNqUCSwQVW', 'teacher', 'approved', 7, '2025-09-10 10:45:10'),
(13, 'estza4', 'kiuyl086@hotmail.com', 'รุ่งระวิน', 'พ่วงขำ', '$2y$10$ldpD6Dy3NyyN.XbN2gR4/u39fFcDxBpohdiJfsTNyQZDOpgCIdBvK', 'admin', 'approved', NULL, '2025-09-10 11:01:32'),
(14, 'estza5', 'estza1313@gmail.com', 'Rungrawin', 'Phungkham', '$2y$10$nJtCpc8v8uYzASk5pbedu.dkOpmat/X7Scxbja8S4JWhRd0CwhmEW', 'director', 'rejected', NULL, '2025-09-10 12:17:12');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_duty`
--

CREATE TABLE `weekly_duty` (
  `day_of_week` int(1) NOT NULL COMMENT '1=จันทร์, 2=อังคาร, ..., 5=ศุกร์',
  `user_id` int(11) NOT NULL COMMENT 'รหัสครูที่เข้าเวร (FK to users.id)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weekly_duty`
--

INSERT INTO `weekly_duty` (`day_of_week`, `user_id`) VALUES
(3, 12);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_date_unique` (`student_id`,`check_in_time`),
  ADD KEY `checked_by_user_id` (`checked_by_user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name_level_unique` (`name`,`level`),
  ADD KEY `fk_departments_advisor` (`advisor_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_code` (`student_code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `weekly_duty`
--
ALTER TABLE `weekly_duty`
  ADD PRIMARY KEY (`day_of_week`),
  ADD KEY `fk_weekly_duty_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_advisor` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `weekly_duty`
--
ALTER TABLE `weekly_duty`
  ADD CONSTRAINT `fk_weekly_duty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
