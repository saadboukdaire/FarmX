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

-- Dumping structure for table farmx.translations
CREATE TABLE IF NOT EXISTS `translations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `language_code` varchar(2) NOT NULL,
  `translation_key` varchar(50) NOT NULL,
  `translation_value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_translation` (`language_code`,`translation_key`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table farmx.translations: ~108 rows (approximately)
INSERT INTO `translations` (`id`, `language_code`, `translation_key`, `translation_value`, `created_at`, `updated_at`) VALUES
	(1, 'en', 'welcome', 'Welcome back!', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(2, 'en', 'username_placeholder', 'Username or Email', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(3, 'en', 'password_placeholder', 'Password', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(4, 'en', 'login', 'Login', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(5, 'en', 'no_account', 'Don\'t have an account?', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(6, 'en', 'register', 'Register', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(7, 'en', 'ok', 'OK', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(8, 'en', 'login_success', 'Login successful!', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(9, 'en', 'login_error', 'Invalid username or password', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(10, 'en', 'server_error', 'Server error occurred', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(11, 'en', 'create_account', 'Create Account', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(12, 'en', 'email_placeholder', 'Email', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(13, 'en', 'confirm_password_placeholder', 'Confirm password', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(14, 'en', 'account_type_label', 'Account Type:', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(15, 'en', 'farmer_label', 'Farmer', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(16, 'en', 'consumer_label', 'Consumer', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(17, 'en', 'gender_label', 'Gender (Optional):', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(18, 'en', 'male_label', 'Male', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(19, 'en', 'female_label', 'Female', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(20, 'en', 'have_account', 'Already have an account?', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(21, 'en', 'phone_placeholder', '6XXXXXXXX or 7XXXXXXXX', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(22, 'fr', 'welcome', 'Bienvenue!', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(23, 'fr', 'username_placeholder', 'Nom d\'utilisateur ou Email', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(24, 'fr', 'password_placeholder', 'Mot de passe', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(25, 'fr', 'login', 'Connexion', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(26, 'fr', 'no_account', 'Pas de compte?', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(27, 'fr', 'register', 'S\'inscrire', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(28, 'fr', 'ok', 'OK', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(29, 'fr', 'login_success', 'Connexion réussie!', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(30, 'fr', 'login_error', 'Nom d\'utilisateur ou mot de passe invalide', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(31, 'fr', 'server_error', 'Une erreur de serveur s\'est produite', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(32, 'fr', 'create_account', 'Créer un compte', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(33, 'fr', 'email_placeholder', 'Email', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(34, 'fr', 'confirm_password_placeholder', 'Confirmer le mot de passe', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(35, 'fr', 'account_type_label', 'Type de compte:', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(36, 'fr', 'farmer_label', 'Agriculteur', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(37, 'fr', 'consumer_label', 'Consommateur', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(38, 'fr', 'gender_label', 'Genre (Optionnel):', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(39, 'fr', 'male_label', 'Homme', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(40, 'fr', 'female_label', 'Femme', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(41, 'fr', 'have_account', 'Vous avez déjà un compte?', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(42, 'fr', 'phone_placeholder', '6XXXXXXXX ou 7XXXXXXXX', '2025-06-10 20:53:12', '2025-06-10 20:53:12'),
	(43, 'en', 'home', 'Home', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(44, 'en', 'messages', 'Messages', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(45, 'en', 'profile', 'Profile', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(46, 'en', 'logout', 'Logout', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(47, 'en', 'switch_to_french', 'Switch to French', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(48, 'en', 'switch_to_english', 'Switch to English', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(49, 'en', 'whats_on_mind', 'What\'s on your mind?', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(50, 'en', 'photo', 'Photo', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(51, 'en', 'video', 'Video', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(52, 'en', 'post', 'Post', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(53, 'en', 'uploading_image', 'Uploading image...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(54, 'en', 'uploading_video', 'Uploading video...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(55, 'en', 'media_attached', 'Media attached', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(56, 'en', 'write_comment', 'Write a comment...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(57, 'en', 'view_in_feed', 'View in Feed', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(58, 'en', 'delete_post', 'Delete Post', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(59, 'en', 'edit_profile', 'Edit Profile', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(60, 'en', 'member_since', 'Member since', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(61, 'en', 'email', 'Email', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(62, 'en', 'phone', 'Phone', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(63, 'en', 'gender', 'Gender', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(64, 'en', 'bio', 'Bio', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(65, 'en', 'search_users', 'Search users...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(66, 'en', 'type_message', 'Type a message...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(67, 'en', 'send', 'Send', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(68, 'en', 'no_messages', 'No messages yet', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(69, 'en', 'start_conversation', 'Start a conversation', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(70, 'en', 'weather', 'Weather', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(71, 'en', 'loading_weather', 'Loading weather data...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(72, 'en', 'temperature', 'Temperature', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(73, 'en', 'humidity', 'Humidity', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(74, 'en', 'wind', 'Wind', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(75, 'en', 'feels_like', 'Feels like', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(76, 'fr', 'home', 'Accueil', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(77, 'fr', 'messages', 'Messages', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(78, 'fr', 'profile', 'Profil', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(79, 'fr', 'logout', 'Déconnexion', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(80, 'fr', 'switch_to_french', 'Passer en Français', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(81, 'fr', 'switch_to_english', 'Passer en Anglais', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(82, 'fr', 'whats_on_mind', 'Quoi de neuf ?', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(83, 'fr', 'photo', 'Photo', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(84, 'fr', 'video', 'Vidéo', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(85, 'fr', 'post', 'Publier', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(86, 'fr', 'uploading_image', 'Téléchargement de l\'image...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(87, 'fr', 'uploading_video', 'Téléchargement de la vidéo...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(88, 'fr', 'media_attached', 'Média attaché', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(89, 'fr', 'write_comment', 'Écrire un commentaire...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(90, 'fr', 'view_in_feed', 'Voir dans le fil', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(91, 'fr', 'delete_post', 'Supprimer la publication', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(92, 'fr', 'edit_profile', 'Modifier le profil', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(93, 'fr', 'member_since', 'Membre depuis', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(94, 'fr', 'email', 'Email', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(95, 'fr', 'phone', 'Téléphone', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(96, 'fr', 'gender', 'Genre', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(97, 'fr', 'bio', 'Biographie', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(98, 'fr', 'search_users', 'Rechercher des utilisateurs...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(99, 'fr', 'type_message', 'Écrire un message...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(100, 'fr', 'send', 'Envoyer', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(101, 'fr', 'no_messages', 'Pas encore de messages', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(102, 'fr', 'start_conversation', 'Démarrer une conversation', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(103, 'fr', 'weather', 'Météo', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(104, 'fr', 'loading_weather', 'Chargement des données météo...', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(105, 'fr', 'temperature', 'Température', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(106, 'fr', 'humidity', 'Humidité', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(107, 'fr', 'wind', 'Vent', '2025-06-10 23:11:13', '2025-06-10 23:11:13'),
	(108, 'fr', 'feels_like', 'Ressenti', '2025-06-10 23:11:13', '2025-06-10 23:11:13');

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
