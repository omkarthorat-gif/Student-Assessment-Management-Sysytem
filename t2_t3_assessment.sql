-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2025 at 07:21 AM
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
-- Database: `t2_t3_assessment`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE `admin_notes` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` varchar(10) NOT NULL,
  `dept_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`) VALUES
('AE', 'Agricultural Engineering'),
('BCA', 'Bachelor of Computer Applications'),
('BIOINFO', 'Bioinformatics'),
('BME', 'Biomedical Engineering'),
('BT', 'Biotechnology'),
('CE', 'Chemical Engineering'),
('CIVIL', 'Civil Engineering'),
('CSE', 'Computer Science & Engineering'),
('EEE', 'Electrical & Electronics Engineering'),
('ECE', 'Electronics & Communications Engg.'),
('ECM', 'Electronics & Computer Engineering'),
('FT', 'Food Technology'),
('IT', 'Information Technology'),
('ME', 'Mechanical Engineering'),
('MECH', 'Mechatronics'),
('PE', 'Petroleum Engineering'),
('TT', 'Textile Technology');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` char(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `dept_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `name`, `dept_id`) VALUES
('1000', 'Dr.K.Sujatha', 'IT'),
('1001', 'Radha Madhavi', 'IT'),
('1002', 'Hemnatha Kumar Bhuyan', 'IT'),
('1824', 'Praveen Kumar', 'IT');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_subject_assign`
--

CREATE TABLE `faculty_subject_assign` (
  `faculty_id` char(10) NOT NULL,
  `subject_id` varchar(10) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `section_name` char(1) NOT NULL,
  `dept_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_subject_assign`
--

INSERT INTO `faculty_subject_assign` (`faculty_id`, `subject_id`, `year`, `semester`, `section_name`, `dept_id`) VALUES
('1000', '22IT100', 3, 2, 'A', 'IT'),
('1000', '22IT100', 3, 2, 'B', 'IT'),
('1001', '22IT104', 3, 2, 'A', 'IT'),
('1002', '22IT205', 2, 1, 'A', 'IT'),
('1824', '22IT202', 3, 2, 'A', 'IT');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `submission_id` int(11) NOT NULL,
  `t2_mark1` int(11) DEFAULT NULL CHECK (`t2_mark1` between 0 and 5),
  `t2_mark2` int(11) DEFAULT NULL CHECK (`t2_mark2` between 0 and 5),
  `t3_mark1` int(11) DEFAULT NULL CHECK (`t3_mark1` between 0 and 5),
  `t3_mark2` int(11) DEFAULT NULL CHECK (`t3_mark2` between 0 and 5),
  `total_t2_marks` int(11) GENERATED ALWAYS AS (`t2_mark1` + `t2_mark2`) STORED,
  `total_t3_marks` int(11) GENERATED ALWAYS AS (`t3_mark1` + `t3_mark2`) STORED,
  `assessed_status` enum('Yes','No') DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`submission_id`, `t2_mark1`, `t2_mark2`, `t3_mark1`, `t3_mark2`, `assessed_status`) VALUES
(4, 4, 3, NULL, NULL, 'Yes'),
(5, 5, 4, NULL, NULL, 'Yes'),
(6, NULL, NULL, 4, 5, 'Yes'),
(7, 4, 4, NULL, NULL, 'Yes'),
(8, NULL, NULL, NULL, NULL, 'No');

--
-- Triggers `marks`
--
DELIMITER $$
CREATE TRIGGER `update_assessed_status` BEFORE UPDATE ON `marks` FOR EACH ROW BEGIN
    -- Only update assessed_status if it was previously 'No' and meaningful marks are provided
    IF OLD.assessed_status = 'No' AND (
        (NEW.t2_mark1 IS NOT NULL AND NEW.t2_mark1 > 0) OR 
        (NEW.t2_mark2 IS NOT NULL AND NEW.t2_mark2 > 0) OR 
        (NEW.t3_mark1 IS NOT NULL AND NEW.t3_mark1 > 0) OR 
        (NEW.t3_mark2 IS NOT NULL AND NEW.t3_mark2 > 0)
    ) THEN
        SET NEW.assessed_status = 'Yes';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_name` char(1) NOT NULL CHECK (`section_name` in ('A','B','C','D','E','F')),
  `year` int(11) NOT NULL CHECK (`year` between 1 and 4),
  `dept_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_name`, `year`, `dept_id`) VALUES
('A', 1, 'CSE'),
('A', 1, 'ECE'),
('A', 1, 'EEE'),
('A', 1, 'IT'),
('A', 1, 'ME'),
('A', 2, 'CSE'),
('A', 2, 'ECE'),
('A', 2, 'EEE'),
('A', 2, 'IT'),
('A', 2, 'ME'),
('A', 3, 'CSE'),
('A', 3, 'ECE'),
('A', 3, 'EEE'),
('A', 3, 'IT'),
('A', 3, 'ME'),
('A', 4, 'CSE'),
('A', 4, 'ECE'),
('A', 4, 'EEE'),
('A', 4, 'IT'),
('A', 4, 'ME'),
('B', 1, 'CSE'),
('B', 1, 'ECE'),
('B', 1, 'EEE'),
('B', 1, 'IT'),
('B', 1, 'ME'),
('B', 2, 'CSE'),
('B', 2, 'ECE'),
('B', 2, 'EEE'),
('B', 2, 'IT'),
('B', 2, 'ME'),
('B', 3, 'CSE'),
('B', 3, 'ECE'),
('B', 3, 'EEE'),
('B', 3, 'IT'),
('B', 3, 'ME'),
('B', 4, 'CSE'),
('B', 4, 'ECE'),
('B', 4, 'EEE'),
('B', 4, 'IT'),
('B', 4, 'ME'),
('C', 1, 'CSE'),
('C', 2, 'CSE'),
('C', 3, 'CSE'),
('C', 4, 'CSE');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `reg_no` char(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `year` int(11) NOT NULL CHECK (`year` between 1 and 4),
  `section_name` char(1) NOT NULL,
  `dept_id` varchar(10) NOT NULL,
  `semester` int(11) NOT NULL CHECK (`semester` in (1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`reg_no`, `name`, `year`, `section_name`, `dept_id`, `semester`) VALUES
('221FA07001', 'Siri Kumar', 3, 'A', 'IT', 2),
('221FA07002', 'Sravya', 3, 'A', 'IT', 2),
('221FA07003', 'Manoj Kumar', 3, 'A', 'IT', 2),
('221FA07005', 'Dinesh Kumar', 3, 'A', 'IT', 2),
('221FA07015', 'Mallikharjun', 3, 'A', 'IT', 2),
('221FA07057', 'Nagasatwika', 3, 'A', 'IT', 2);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` varchar(10) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `dept_id` varchar(10) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `dept_id`, `year`, `semester`) VALUES
('22IT100', 'DevOps', 'IT', 3, 2),
('22IT104', 'Mobile Application Development', 'IT', 3, 2),
('22IT108', 'Cloud Computing', 'IT', 3, 2),
('22IT202', 'Deep Learning', 'IT', 3, 2),
('22IT205', 'DBMS', 'IT', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `subject_allocation`
--

CREATE TABLE `subject_allocation` (
  `subject_id` varchar(10) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `section_name` char(1) NOT NULL,
  `dept_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_allocation`
--

INSERT INTO `subject_allocation` (`subject_id`, `year`, `semester`, `section_name`, `dept_id`) VALUES
('22IT100', 3, 2, 'A', 'IT'),
('22IT100', 3, 2, 'B', 'IT'),
('22IT104', 3, 2, 'A', 'IT'),
('22IT104', 3, 2, 'B', 'IT'),
('22IT108', 3, 2, 'A', 'IT'),
('22IT202', 3, 2, 'A', 'IT'),
('22IT202', 3, 2, 'B', 'IT'),
('22IT205', 2, 1, 'A', 'IT'),
('22IT205', 2, 1, 'B', 'IT');

-- --------------------------------------------------------

--
-- Table structure for table `t2_t3_submissions`
--

CREATE TABLE `t2_t3_submissions` (
  `submission_id` int(11) NOT NULL,
  `reg_no` char(10) NOT NULL,
  `subject_id` varchar(10) NOT NULL,
  `year` int(11) NOT NULL,
  `semester` int(11) NOT NULL,
  `document_type` enum('T2','T3') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_status` enum('Uploaded','Not Uploaded') DEFAULT 'Uploaded',
  `upload_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `t2_t3_submissions`
--

INSERT INTO `t2_t3_submissions` (`submission_id`, `reg_no`, `subject_id`, `year`, `semester`, `document_type`, `file_path`, `upload_status`, `upload_date`) VALUES
(4, '221FA07057', '22IT100', 3, 2, 'T2', '../uploads/submissions/221FA07057_22IT100_T2_1740811360.docx', 'Uploaded', '2025-03-01'),
(5, '221FA07015', '22IT100', 3, 2, 'T2', '../uploads/submissions/221FA07015_22IT100_T2_1740817175.docx', 'Uploaded', '2025-03-01'),
(6, '221FA07057', '22IT100', 3, 2, 'T3', '../uploads/submissions/221FA07057_22IT100_T3_1741242334.pptx', 'Uploaded', '2025-03-06'),
(7, '221FA07057', '22IT104', 3, 2, 'T2', '../uploads/submissions/221FA07057_22IT104_T2_1741255568.docx', 'Uploaded', '2025-03-06'),
(8, '221FA07015', '22IT100', 3, 2, 'T3', '../uploads/submissions/221FA07015_22IT100_T3_1741318491.pptx', 'Uploaded', '2025-03-07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('Admin','Student','Faculty') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`username`, `password`, `role`) VALUES
('1000', '$2y$10$RjLiuKhX3ryN/5LX4EB0ZeYHsllRe9hydON..rBKXS9i.QqIUb3iG', 'Faculty'),
('1001', '$2y$10$VpabCME9e7gs53xoxsQbke/dYtf.zjdxS9L0129HNtJbpSq7SPeq6', 'Faculty'),
('1002', '$2y$10$BH.mQTONvwGEVHN7.NI9.e9Lj5Sfq1f6NhZsfuZCsfMdpMKENCsWO', 'Faculty'),
('1824', '$2y$10$.xAUxE1AhhOdNPJW.VCnku/93ZmRaq4.4ujNZe7ycpc/ZdhUE4wZS', 'Faculty'),
('221FA07001', '$2y$10$FCZ5WaB9G0WCKba7KUDWJ.jP30q81yq5qp4hFls9qRyhfosJlt9vu', 'Student'),
('221FA07002', '$2y$10$3UExYkEUnnXw/kAfKEUn4.JIjOTF25u3GhV9sA3NAtxbS3adcdQXy', 'Student'),
('221FA07003', '$2y$10$OKyddUHj3wyAW13O81F3peJy7PCLSvQYijWD0Tu529G2tQ0ZJUudO', 'Student'),
('221FA07005', '$2y$10$E.k/el1ypV16FPcDwb/YE./vitO6eEWdi4CSmipvkPaVVQi8ABkRS', 'Student'),
('221FA07015', '$2y$10$66062yAF.2gqgdL16xz96.X0ya5DpYL2KvKGz800PY274mNrc8Wde', 'Student'),
('221FA07057', '$2y$10$H9v3Q65XDuuwML47Gazp3uyLWDCVotJ9vLE0wWHkOq1nR2zIwCaBW', 'Student'),
('admin', 'admin123', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `year_semester`
--

CREATE TABLE `year_semester` (
  `year` int(11) NOT NULL CHECK (`year` between 1 and 4),
  `semester` int(11) NOT NULL CHECK (`semester` in (1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `year_semester`
--

INSERT INTO `year_semester` (`year`, `semester`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(4, 1),
(4, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `dept_name` (`dept_name`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `faculty_subject_assign`
--
ALTER TABLE `faculty_subject_assign`
  ADD PRIMARY KEY (`faculty_id`,`subject_id`,`year`,`semester`,`section_name`,`dept_id`),
  ADD KEY `subject_id` (`subject_id`,`year`,`semester`,`section_name`,`dept_id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`submission_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_name`,`year`,`dept_id`),
  ADD KEY `year` (`year`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`reg_no`),
  ADD KEY `section_name` (`section_name`,`year`,`dept_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `year` (`year`,`semester`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`,`year`,`semester`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `year` (`year`,`semester`);

--
-- Indexes for table `subject_allocation`
--
ALTER TABLE `subject_allocation`
  ADD PRIMARY KEY (`subject_id`,`year`,`semester`,`section_name`,`dept_id`),
  ADD KEY `section_name` (`section_name`,`year`,`dept_id`);

--
-- Indexes for table `t2_t3_submissions`
--
ALTER TABLE `t2_t3_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `reg_no` (`reg_no`),
  ADD KEY `subject_id` (`subject_id`,`year`,`semester`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `year_semester`
--
ALTER TABLE `year_semester`
  ADD PRIMARY KEY (`year`,`semester`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notes`
--
ALTER TABLE `admin_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `t2_t3_submissions`
--
ALTER TABLE `t2_t3_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `faculty_subject_assign`
--
ALTER TABLE `faculty_subject_assign`
  ADD CONSTRAINT `faculty_subject_assign_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`),
  ADD CONSTRAINT `faculty_subject_assign_ibfk_2` FOREIGN KEY (`subject_id`,`year`,`semester`,`section_name`,`dept_id`) REFERENCES `subject_allocation` (`subject_id`, `year`, `semester`, `section_name`, `dept_id`);

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `t2_t3_submissions` (`submission_id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`year`) REFERENCES `year_semester` (`year`),
  ADD CONSTRAINT `sections_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`section_name`,`year`,`dept_id`) REFERENCES `sections` (`section_name`, `year`, `dept_id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`year`,`semester`) REFERENCES `year_semester` (`year`, `semester`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`),
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`year`,`semester`) REFERENCES `year_semester` (`year`, `semester`);

--
-- Constraints for table `subject_allocation`
--
ALTER TABLE `subject_allocation`
  ADD CONSTRAINT `subject_allocation_ibfk_1` FOREIGN KEY (`subject_id`,`year`,`semester`) REFERENCES `subjects` (`subject_id`, `year`, `semester`),
  ADD CONSTRAINT `subject_allocation_ibfk_2` FOREIGN KEY (`section_name`,`year`,`dept_id`) REFERENCES `sections` (`section_name`, `year`, `dept_id`);

--
-- Constraints for table `t2_t3_submissions`
--
ALTER TABLE `t2_t3_submissions`
  ADD CONSTRAINT `t2_t3_submissions_ibfk_1` FOREIGN KEY (`reg_no`) REFERENCES `students` (`reg_no`),
  ADD CONSTRAINT `t2_t3_submissions_ibfk_2` FOREIGN KEY (`subject_id`,`year`,`semester`) REFERENCES `subjects` (`subject_id`, `year`, `semester`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
