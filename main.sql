-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for farmx
CREATE DATABASE IF NOT EXISTS `farmx` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `farmx`;

-- Dumping structure for table farmx.comments
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.comments: ~0 rows (approximately)

-- Dumping structure for table farmx.likes
CREATE TABLE IF NOT EXISTS `likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `post_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`user_id`,`post_id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.likes: ~0 rows (approximately)

-- Dumping structure for table farmx.marketplace_items
CREATE TABLE IF NOT EXISTS `marketplace_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('available','sold','pending') DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `marketplace_items_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.marketplace_items: ~0 rows (approximately)

-- Dumping structure for table farmx.messages
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.messages: ~0 rows (approximately)
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `content`, `is_read`, `created_at`) VALUES
	(2, 12, 7, 'fixing problem simma kms', 0, '2025-06-12 13:20:18'),
	(3, 12, 7, 'fixing problem simma kms', 0, '2025-06-12 13:20:26'),
	(4, 12, 7, 'fixing problem simma kms', 0, '2025-06-12 13:23:06'),
	(5, 12, 7, 'imma kms', 0, '2025-06-12 13:23:28'),
	(6, 12, 7, 'aijsndasijnda', 0, '2025-06-12 13:23:32'),
	(7, 12, 7, 'asdwqerlñkmrwqer', 0, '2025-06-12 13:23:34'),
	(8, 12, 7, 'qwe`pflokmewfòpk', 0, '2025-06-12 13:23:35'),
	(9, 12, 7, 'àlpfdma`s', 0, '2025-06-12 13:23:36'),
	(10, 12, 7, '`+pas,fpas', 0, '2025-06-12 13:23:36');

-- Dumping structure for table farmx.notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `type` enum('like','comment','message') NOT NULL,
  `content` text NOT NULL,
  `post_id` int DEFAULT NULL,
  `message_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `sender_id` (`sender_id`),
  KEY `post_id` (`post_id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.notifications: ~0 rows (approximately)
INSERT INTO `notifications` (`id`, `user_id`, `sender_id`, `type`, `content`, `post_id`, `message_id`, `is_read`, `created_at`) VALUES
	(2, 7, 12, 'message', 'vous a envoyé un message', NULL, 2, 0, '2025-06-12 13:20:18'),
	(3, 7, 12, 'message', 'vous a envoyé un message', NULL, 3, 0, '2025-06-12 13:20:26'),
	(4, 7, 12, 'message', 'vous a envoyé un message', NULL, 4, 0, '2025-06-12 13:23:06'),
	(5, 7, 12, 'message', 'vous a envoyé un message', NULL, 5, 0, '2025-06-12 13:23:28'),
	(6, 7, 12, 'message', 'vous a envoyé un message', NULL, 6, 0, '2025-06-12 13:23:32'),
	(7, 7, 12, 'message', 'vous a envoyé un message', NULL, 7, 0, '2025-06-12 13:23:34'),
	(8, 7, 12, 'message', 'vous a envoyé un message', NULL, 8, 0, '2025-06-12 13:23:35'),
	(9, 7, 12, 'message', 'vous a envoyé un message', NULL, 9, 0, '2025-06-12 13:23:36'),
	(10, 7, 12, 'message', 'vous a envoyé un message', NULL, 10, 0, '2025-06-12 13:23:36');

-- Dumping structure for table farmx.posts
CREATE TABLE IF NOT EXISTS `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'Images/profile.jpg',
  `content` text,
  `media_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.posts: ~0 rows (approximately)
INSERT INTO `posts` (`id`, `user_id`, `username`, `profile_pic`, `content`, `media_url`, `created_at`) VALUES
	(8, 12, 'saadbk', 'uploads/profile_pictures/684ad211bad57_istockphoto-1303739150-612x612.jpg', 'saad mf', '', '2025-06-12 13:19:52');

-- Dumping structure for table farmx.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'Images/profile.jpg',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `bio` text,
  `user_type` varchar(20) NOT NULL,
  `user_tag` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.users: ~6 rows (approximately)
INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `profile_pic`, `created_at`, `bio`, `user_type`, `user_tag`, `gender`) VALUES
	(6, 'hiba', 'hiba@hotmail.com', '+212792438573', '$2y$10$kPfnG1RpPOWXR3xT.pA7MufLKdJMl94j3K8ZHgF7LIcGHrx07o0nm', 'Images/profile.jpg', '2025-06-12 10:57:27', NULL, 'consommateur', 'Membre FarmX', 'female'),
	(7, 'mouad', 'mouad@gmail.com', '+212702349832', '$2y$10$HkFpJjfhfCSEsPdOJyWyLuGJpwGe1KiBcAcEBz9i0r4Hfbw3ZkMqu', 'Images/profile.jpg', '2025-06-12 11:01:13', NULL, 'farmer', 'Producteur FarmX', 'male'),
	(8, 'hassan', 'hassan@gmail.com', '+212639284573', '$2y$10$JhzL0mqA3.fl36CZUDdd0.snrq6r1CMkoWlwRrf7cB0T/WOGHNOdS', 'Images/profile.jpg', '2025-06-12 11:02:50', NULL, 'consommateur', 'Membre FarmX', 'male'),
	(9, 'mohamed', 'mohamed@gmail.com', '+212632432423', '$2y$10$SpAi3kN.eeaOyBEl7c0CAOPyR.yL4B.2g13NTAuEwymuTe7ZQJ2d2', 'Images/profile.jpg', '2025-06-12 11:06:13', NULL, 'consommateur', 'Membre FarmX', 'male'),
	(10, 'maroua', 'maroua@gmail.com', '+212732948176', '$2y$10$OEzeNT76M6bP73nEO2KEYOdw/56mH6x1U0azeWoyPuCYub.cWILiO', 'Images/profile.jpg', '2025-06-12 11:10:13', NULL, 'consommateur', 'Membre FarmX', 'female'),
	(11, 'othman', 'othman@gmail.com', '+212698347543', '$2y$10$GT.IZSMCsdmmVMf6J7UE5eeQ1i0Ymvt8C9yQ6qy7CjSBHSUa85.Pm', 'Images/profile.jpg', '2025-06-12 11:21:08', NULL, 'farmer', 'Producteur FarmX', 'male'),
	(12, 'saadbk', 'saadboukdaire2@gmail.com', '+212693667460', '$2y$10$sI9XGoNrNnmKIvzSfkyhhuVZBu8vcXxZbNV6oqftLzRKe193Q66bi', 'uploads/profile_pictures/684ad211bad57_istockphoto-1303739150-612x612.jpg', '2025-06-12 12:48:47', 'je suis hh', 'farmer', 'Producteur FarmX', 'male');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
