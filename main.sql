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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.comments: ~0 rows (approximately)
INSERT INTO `comments` (`id`, `user_id`, `post_id`, `content`, `created_at`) VALUES
	(1, 1, 1, 'dd', '2025-06-10 22:47:54'),
	(2, 1, 1, 'dd got to me help', '2025-06-10 22:48:59');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.likes: ~0 rows (approximately)
INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
	(1, 1, 1, '2025-06-10 21:36:36');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.marketplace_items: ~0 rows (approximately)
INSERT INTO `marketplace_items` (`id`, `seller_id`, `title`, `description`, `price`, `image_url`, `category`, `status`, `created_at`) VALUES
	(1, 1, 'cow', 'A 400kg grass fed cow', 12000.50, 'uploads/marketplace/6848b6bd1e19b.jpg', 'meat', 'available', '2025-06-10 22:50:37');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.messages: ~0 rows (approximately)

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.notifications: ~0 rows (approximately)

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.posts: ~0 rows (approximately)
INSERT INTO `posts` (`id`, `user_id`, `username`, `profile_pic`, `content`, `media_url`, `created_at`) VALUES
	(1, 1, 'saad', 'uploads/profile_pictures/6848a74682a2d_istockphoto-1303739150-612x612.jpg', 'Hi im a new user!!', '', '2025-06-10 21:36:29'),
	(2, 1, 'saad', 'uploads/profile_pictures/6848a74682a2d_istockphoto-1303739150-612x612.jpg', 'm y frien hh', 'uploads/post_media/6848b6d4c7ddd_mohahh.jpg', '2025-06-10 22:51:17'),
	(3, 1, 'saad', 'uploads/profile_pictures/6848a74682a2d_istockphoto-1303739150-612x612.jpg', 'dance', 'uploads/post_media/6848b81f1f352_eb9a0b7f-a085-4038-b1b0-af840e8a4132.mp4', '2025-06-10 22:56:41');


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
  `user_type` enum('farmer','user') NOT NULL DEFAULT 'user',
  `user_tag` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.users: ~0 rows (approximately)
INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `profile_pic`, `created_at`, `bio`, `user_type`, `user_tag`, `gender`) VALUES
	(1, 'saad', 'saadboukdaire1@gmail.com', '+212693667462', '$2y$10$Zn9AwZJC71WY8RD59OZm7ewyEXeFG98c4HmQtmrEnZOmMkw7cOB3K', 'uploads/profile_pictures/6848a74682a2d_istockphoto-1303739150-612x612.jpg', '2025-06-10 21:35:56', 'Im CEO of farmx', 'farmer', 'FarmX Producer', 'Male');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
