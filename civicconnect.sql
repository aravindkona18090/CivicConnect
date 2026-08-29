SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `civicconnect` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `civicconnect`;

-- --------------------------------------------------------
-- Table: admin1
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admin1`;
CREATE TABLE IF NOT EXISTS `admin1` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin1` (`id`, `username`, `email`, `mobile`, `password`, `created_at`) VALUES
(1, 'admin', 'civicconnect24@gmail.com', '0000000000', '$2y$10$DrrsgUlvz1vdiAl798536.HLm7WURV8Y17noNLovojgMiulycb1fW', '2025-09-20 08:08:25');

-- --------------------------------------------------------
-- Table: people
-- --------------------------------------------------------
DROP TABLE IF EXISTS `people`;
CREATE TABLE IF NOT EXISTS `people` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) DEFAULT '',
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT '',
  `door` varchar(50) DEFAULT '',
  `street` varchar(100) DEFAULT '',
  `area` varchar(100) DEFAULT '',
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT '',
  `pincode` varchar(20) DEFAULT '',
  `language` varchar(5) DEFAULT 'en',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: workers
-- --------------------------------------------------------
DROP TABLE IF EXISTS `workers`;
CREATE TABLE IF NOT EXISTS `workers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: problems
-- --------------------------------------------------------
DROP TABLE IF EXISTS `problems`;
CREATE TABLE IF NOT EXISTS `problems` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `street` varchar(100) NOT NULL,
  `area` varchar(100) NOT NULL,
  `city` varchar(50) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `worker_id` int DEFAULT NULL,
  `allocated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `after_photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_problems_people` (`user_id`),
  KEY `fk_problems_workers` (`worker_id`),
  CONSTRAINT `fk_problems_people` FOREIGN KEY (`user_id`) REFERENCES `people` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_problems_workers` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
ALTER TABLE problems
ADD COLUMN lat DECIMAL(10,8) DEFAULT NULL,
ADD COLUMN lng DECIMAL(11,8) DEFAULT NULL,
ADD COLUMN ai_severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
ADD COLUMN ai_category VARCHAR(50) DEFAULT NULL;
COMMIT;
