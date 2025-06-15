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
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.marketplace_items: ~10 rows (approximately)
INSERT INTO `marketplace_items` (`id`, `seller_id`, `title`, `description`, `price`, `image_url`, `category`, `status`, `created_at`) VALUES
	(140, 7, 'Tomates Bio du Souss', 'Tomates biologiques fraîches, cultivées sans pesticides. Idéales pour salades et sauces. Prix au kg.', 8.50, NULL, 'Fruits et Légumes', 'available', '2025-06-09 18:18:27'),
	(141, 11, 'Oranges de Berkane', 'Oranges juteuses et sucrées de la région de Berkane. Calibre moyen à gros. Minimum 10kg.', 6.00, NULL, 'Fruits et Légumes', 'available', '2025-06-10 18:18:27'),
	(142, 12, 'Dattes Mejhoul Premium', 'Dattes Mejhoul de qualité supérieure, récoltées à la main. Emballage de 5kg disponible.', 120.00, 'uploads/marketplace/684dc38e3a2e5.jpg', 'vegetables', 'available', '2025-06-11 18:18:27'),
	(143, 7, 'Système d\'irrigation goutte à goutte', 'Kit complet pour 1 hectare, incluant tuyaux, goutteurs et programmateur. État neuf.', 3500.00, NULL, 'Matériel Agricole', 'available', '2025-06-12 18:18:27'),
	(144, 11, 'Motoculteur Honda', 'Motoculteur 7CV, peu utilisé, révision récente. Parfait pour petites parcelles.', 8500.00, NULL, 'Matériel Agricole', 'available', '2025-06-13 18:18:27'),
	(145, 12, 'Semences de tomates résistantes', 'Variété hybride résistante aux maladies. Sachet de 1000 graines. Haut rendement garanti.', 250.00, 'uploads/marketplace/684dc355aa73c.jpg', 'vegetables', 'available', '2025-06-14 18:18:27'),
	(146, 7, 'Plants d\'oliviers Picholine', 'Jeunes plants de 2 ans, variété Picholine marocaine. Minimum 50 plants.', 35.00, 'uploads/marketplace/684dbe8c76417.jpeg', 'vegetables', 'available', '2025-06-14 18:18:27'),
	(147, 11, 'Huile d\'olive extra vierge', 'Première pression à froid, acidité < 0.8%. Bidon de 5L. Production 2024.', 200.00, 'uploads/marketplace/684dbe64428a4.jpg', 'vegetables', 'available', '2025-06-14 18:18:27'),
	(148, 12, 'Miel de thym du Moyen Atlas', 'Miel pur, récolte 2024. Propriétés antiseptiques reconnues. Pot de 1kg.', 150.00, 'uploads/marketplace/684dbd59126be.jpg', 'vegetables', 'available', '2025-06-14 18:18:27'),
	(149, 7, 'Huile d\'argan alimentaire', 'Pressée à froid, 100% pure. Bouteille de 250ml. Certifiée bio.', 120.00, NULL, 'Produits Transformés', 'available', '2025-06-14 18:18:27');

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
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.posts: ~6 rows (approximately)
INSERT INTO `posts` (`id`, `user_id`, `username`, `profile_pic`, `content`, `media_url`, `created_at`) VALUES
	(102, 7, 'mouad', 'Images/profile.jpg', 'Première récolte d\'olives de la saison ! La qualité est exceptionnelle cette année grâce aux pluies du printemps. #Olives #Agriculture #Maroc', NULL, '2025-06-09 18:18:26'),
	(103, 11, 'othman', 'uploads/profile_pictures/684dc06996087_portrait-happy-man-farmer-smiling-garden_1429-9612.jpg', 'Nouveau système d\'irrigation goutte à goutte installé dans mes champs de tomates. L\'économie d\'eau est remarquable !', NULL, '2025-06-10 18:18:26'),
	(104, 12, 'saadbk', 'Images/profile.jpg', 'Les oranges de la région de Béni Mellal sont prêtes ! Qui est intéressé pour une commande groupée ?', NULL, '2025-06-11 18:18:26'),
	(105, 7, 'mouad', 'Images/profile.jpg', 'Conseils du jour : N\'oubliez pas de traiter vos arbres fruitiers contre les parasites avant la floraison !', NULL, '2025-06-12 18:18:26'),
	(106, 11, 'othman', 'uploads/profile_pictures/684dc06996087_portrait-happy-man-farmer-smiling-garden_1429-9612.jpg', 'Belle journée au souk de Khemisset ! Les prix des légumes sont très intéressants cette semaine.', NULL, '2025-06-13 18:18:26'),
	(107, 12, 'saadbk', 'Images/profile.jpg', 'Formation sur l\'agriculture biologique à Meknès la semaine prochaine. Qui veut m\'accompagner ?', NULL, '2025-06-14 18:18:26');

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.users: ~8 rows (approximately)
INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `profile_pic`, `created_at`, `bio`, `user_type`, `user_tag`, `gender`) VALUES
	(6, 'hiba', 'hiba@hotmail.com', '+212792438573', '$2y$10$kPfnG1RpPOWXR3xT.pA7MufLKdJMl94j3K8ZHgF7LIcGHrx07o0nm', 'Images/profile.jpg', '2025-06-12 10:57:27', NULL, 'consommateur', 'Membre FarmX', 'female'),
	(7, 'mouad', 'mouad@gmail.com', '+212702349832', '$2y$10$HkFpJjfhfCSEsPdOJyWyLuGJpwGe1KiBcAcEBz9i0r4Hfbw3ZkMqu', 'Images/profile.jpg', '2025-06-12 11:01:13', NULL, 'farmer', 'Producteur FarmX', 'male'),
	(8, 'hassan', 'hassan@gmail.com', '+212639284573', '$2y$10$JhzL0mqA3.fl36CZUDdd0.snrq6r1CMkoWlwRrf7cB0T/WOGHNOdS', 'Images/profile.jpg', '2025-06-12 11:02:50', NULL, 'consommateur', 'Membre FarmX', 'male'),
	(9, 'mohamed', 'mohamed@gmail.com', '+212632432423', '$2y$10$SpAi3kN.eeaOyBEl7c0CAOPyR.yL4B.2g13NTAuEwymuTe7ZQJ2d2', 'Images/profile.jpg', '2025-06-12 11:06:13', NULL, 'consommateur', 'Membre FarmX', 'male'),
	(10, 'maroua', 'maroua@gmail.com', '+212732948176', '$2y$10$OEzeNT76M6bP73nEO2KEYOdw/56mH6x1U0azeWoyPuCYub.cWILiO', 'Images/profile.jpg', '2025-06-12 11:10:13', NULL, 'consommateur', 'Membre FarmX', 'female'),
	(11, 'othmane', 'othmane123@gmail.com', '+212698347543', '$2y$10$GT.IZSMCsdmmVMf6J7UE5eeQ1i0Ymvt8C9yQ6qy7CjSBHSUa85.Pm', 'uploads/profile_pictures/684dc06996087_portrait-happy-man-farmer-smiling-garden_1429-9612.jpg', '2025-06-12 11:21:08', 'Nouveau dans le monde agricole mais motivé à 100% 💪 | Intéressé par les techniques innovantes et durables | Objectif : faire évoluer mon exploitation familiale 👨‍👩‍👧‍👦 | 📍Tanger-Tétouan-Al Hoceïma', 'farmer', 'Producteur FarmX', 'male'),
	(12, 'saad', 'saadboukdaire1@gmail.com', '+212693667462', '$2y$10$sI9XGoNrNnmKIvzSfkyhhuVZBu8vcXxZbNV6oqftLzRKe193Q66bi', 'uploads/profile_pictures/684b0d4c6e066_istockphoto-1303739150-612x612.jpg', '2025-06-12 12:48:47', 'Je vend des produits bio', 'farmer', 'Producteur FarmX', 'male'),
	(13, 'mouadox', 'mouadox@gmail.com', '+212623490839', '$2y$10$zF4p9F8npP2uYsQ48SSWAOv0JhtvoI529nozK7bhwOfjEdQvvXy/a', 'Images/profile.jpg', '2025-06-12 16:55:22', NULL, 'farmer', 'Producteur FarmX', 'male');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
