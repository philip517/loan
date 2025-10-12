-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2025 at 04:04 AM
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
-- Database: `sefa_satty`
--

-- --------------------------------------------------------

--
-- Table structure for table `budget`
--

CREATE TABLE `budget` (
  `budget_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `balance` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kin`
--

CREATE TABLE `kin` (
  `kin_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `nrc_number` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `loan_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `loan_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `duration` int(11) NOT NULL,
  `interest` decimal(5,2) DEFAULT 0.00,
  `loan_start_date` date DEFAULT NULL,
  `loan_end_date` date DEFAULT NULL,
  `collateral_name` varchar(255) DEFAULT NULL,
  `image1` longblob DEFAULT NULL,
  `image2` longblob DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `user_id_image` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_details`
--

CREATE TABLE `login_details` (
  `login_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_phrase` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_details`
--

INSERT INTO `login_details` (`login_id`, `username`, `password`, `password_reset_phrase`, `user_id`) VALUES
(26, 'PhilipMUMBI', '$2y$10$UcHdtJx1QCgXEY8rZGFX1elF7IBlvBSN9Yt/uSYsZCC1927BQMFGu', NULL, 26),
(27, 'ShadrickMumbi', '$2y$10$K38E5C7rQLGx.SagDR93OuUJrRuKSwsZqLt3FymPBQCEQxBMng6S.', '12345', 27),
(28, 'KasmanMan', '$2y$10$2mbFZybdhB4G5ZLmsU1Qn.1QjG4ThKij.RKRGehZsbqE/f14wJnze', '1234', 28);

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `message_id` int(11) NOT NULL,
  `status` enum('sent','received','read') DEFAULT 'sent',
  `topic` varchar(255) DEFAULT NULL,
  `message_text` text DEFAULT NULL,
  `type` enum('user_to_admin','admin_to_user') DEFAULT 'user_to_admin',
  `loan_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `NRC` text DEFAULT NULL,
  `id_image` longblob DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT 'Male',
  `date_of_birth` date DEFAULT NULL,
  `address` varchar(100) NOT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `role` enum('client','admin','super_admin') DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_table`
--

INSERT INTO `user_table` (`user_id`, `first_name`, `last_name`, `username`, `phone`, `NRC`, `id_image`, `gender`, `date_of_birth`, `address`, `occupation`, `email`, `nationality`, `role`) VALUES
(26, 'Philip', 'MUMBI', 'PhilipMUMBI', NULL, '', '', 'Male', NULL, '', NULL, 'admin@gmail.com', NULL, 'admin'),
(27, 'Shadrick', 'Mumbi', 'ShadrickMumbi', '1234567890', '11212312312', '', 'Male', '2025-10-01', '1234', 'business', 'example@gmail.com', NULL, 'admin'),
(28, 'Kasman', 'Man', 'KasmanMan', '01235245658', '1231216257', '', 'Male', '0000-00-00', '2025', 'student', 'kasman@gmail.com', NULL, 'client');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`budget_id`);

--
-- Indexes for table `kin`
--
ALTER TABLE `kin`
  ADD PRIMARY KEY (`kin_id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_details`
--
ALTER TABLE `login_details`
  ADD PRIMARY KEY (`login_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budget`
--
ALTER TABLE `budget`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kin`
--
ALTER TABLE `kin`
  MODIFY `kin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `loan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_details`
--
ALTER TABLE `login_details`
  MODIFY `login_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kin`
--
ALTER TABLE `kin`
  ADD CONSTRAINT `kin_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`loan_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `login_details`
--
ALTER TABLE `login_details`
  ADD CONSTRAINT `login_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`loan_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
