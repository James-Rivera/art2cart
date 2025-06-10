-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 10, 2025 at 02:26 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `art2cart`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(33, 9, 25, 1, '2025-06-10 02:03:43', '2025-06-10 02:03:43');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `description` text,
  `icon_path` varchar(255) DEFAULT NULL,
  `display_order` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon_path`, `display_order`) VALUES
(1, 'Digital Art', 'digital-art', 'Digital artwork and illustrations', 'static/images/icons/palette.png', 1),
(2, 'Photography', 'photography', 'High-quality photographs', 'static/images/icons/camera.png', 2),
(3, 'Illustrations', 'illustrations', 'Hand-drawn and vector illustrations', 'static/images/icons/brush.png', 3),
(4, 'Templates', 'templates', 'Website and design templates', 'static/images/icons/layout-template.png', 4),
(6, '3D Models', '3d-models', 'Professional 3D models including characters, environments, and props for games and animations', 'static/images/icons/layers.png', 5),
(7, 'Digital Assets', 'digital-assets', 'Premium digital assets including textures, materials, sound effects, and UI kits', 'static/images/icons/box.png', 6);

-- --------------------------------------------------------

--
-- Table structure for table `download_logs`
--

DROP TABLE IF EXISTS `download_logs`;
CREATE TABLE IF NOT EXISTS `download_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `order_id` int NOT NULL,
  `download_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  PRIMARY KEY (`id`),
  KEY `idx_user_downloads` (`user_id`,`download_time`),
  KEY `idx_product_downloads` (`product_id`,`download_time`),
  KEY `idx_order_downloads` (`order_id`,`download_time`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `download_logs`
--

INSERT INTO `download_logs` (`id`, `user_id`, `product_id`, `order_id`, `download_time`, `ip_address`, `user_agent`) VALUES
(1, 5, 34, 4, '2025-06-09 12:55:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(2, 5, 39, 6, '2025-06-09 12:58:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(3, 5, 4, 7, '2025-06-09 13:27:39', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0'),
(4, 5, 39, 8, '2025-06-09 13:29:20', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36 Edg/137.0.0.0'),
(5, 5, 34, 9, '2025-06-09 13:51:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(6, 8, 39, 11, '2025-06-09 14:10:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(7, 9, 40, 12, '2025-06-09 14:23:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(8, 9, 40, 12, '2025-06-09 14:24:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(9, 9, 37, 13, '2025-06-09 14:53:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(10, 9, 41, 13, '2025-06-09 14:53:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `billing_info` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `billing_info`, `created_at`) VALUES
(1, 5, 3000.00, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"card\"}', '2025-06-09 11:59:24'),
(2, 5, 18.99, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"paypal\"}', '2025-06-09 12:21:58'),
(3, 5, 10000.00, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"gcash\"}', '2025-06-09 12:23:12'),
(4, 5, 10000.00, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"paypal\"}', '2025-06-09 12:36:26'),
(5, 5, 10000.00, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"paypal\"}', '2025-06-09 12:56:01'),
(6, 5, 1200.00, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"card\"}', '2025-06-09 12:58:12'),
(7, 5, 31.98, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"gcash\"}', '2025-06-09 13:27:33'),
(8, 5, 2400.00, 'pending', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"gcash\"}', '2025-06-09 13:29:14'),
(9, 5, 10000.00, 'completed', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"gcash\"}', '2025-06-09 13:51:51'),
(10, 5, 1200.00, 'completed', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"gcash\"}', '2025-06-09 13:56:33'),
(11, 8, 1200.00, 'completed', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"card\"}', '2025-06-09 14:09:57'),
(12, 9, 1500.00, 'completed', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"paypal\"}', '2025-06-09 14:23:08'),
(13, 9, 6000.00, 'completed', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"card\"}', '2025-06-09 14:53:45'),
(14, 9, 10000.00, 'completed', '{\"first_name\":\"Jan Kendrick\",\"last_name\":\"Innoncencio\",\"email\":\"jk@art2cart.com\",\"phone\":\"09457996892\",\"address\":\"jk@art2cart.com\",\"city\":\"Sto. Tomas\",\"postal_code\":\"4234\",\"country\":\"Philippines\",\"payment_method\":\"paypal\"}', '2025-06-10 01:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `price`, `quantity`, `created_at`) VALUES
(1, 1, 37, 3000.00, 1, '2025-06-09 11:59:24'),
(2, 2, 5, 18.99, 1, '2025-06-09 12:21:58'),
(3, 3, 34, 10000.00, 1, '2025-06-09 12:23:12'),
(4, 4, 34, 10000.00, 1, '2025-06-09 12:36:26'),
(5, 5, 34, 10000.00, 1, '2025-06-09 12:56:01'),
(6, 6, 39, 1200.00, 1, '2025-06-09 12:58:12'),
(7, 7, 4, 15.99, 2, '2025-06-09 13:27:33'),
(8, 8, 39, 1200.00, 2, '2025-06-09 13:29:14'),
(9, 9, 34, 10000.00, 1, '2025-06-09 13:51:51'),
(10, 10, 39, 1200.00, 1, '2025-06-09 13:56:33'),
(11, 11, 39, 1200.00, 1, '2025-06-09 14:09:57'),
(12, 12, 40, 1500.00, 1, '2025-06-09 14:23:08'),
(13, 13, 37, 3000.00, 1, '2025-06-09 14:53:45'),
(14, 13, 41, 3000.00, 1, '2025-06-09 14:53:45'),
(15, 14, 34, 10000.00, 1, '2025-06-10 01:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seller_id` int NOT NULL,
  `category_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('active','inactive','pending','rejected') DEFAULT 'pending',
  `downloads` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `review_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `review_notes` text,
  `review_date` timestamp NULL DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  KEY `category_id` (`category_id`),
  KEY `reviewed_by` (`reviewed_by`)
) ENGINE=MyISAM AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `title`, `description`, `price`, `image_path`, `file_path`, `status`, `downloads`, `created_at`, `updated_at`, `review_status`, `review_notes`, `review_date`, `reviewed_by`) VALUES
(1, 1, 1, 'Mob Ultra Sonic', 'A stunning digital artwork featuring ultra sonic waves', 25.99, 'static/images/products/sample.jpg', '', 'active', 123, '2025-06-06 13:22:41', '2025-06-07 14:42:29', 'approved', NULL, '2025-06-07 14:42:29', 1),
(2, 1, 1, 'Alter Ego', 'Creative digital art exploring identity', 29.99, 'static/images/products/Alter Ego.png', '', 'active', 89, '2025-06-06 13:22:41', '2025-06-07 14:42:30', 'approved', NULL, '2025-06-07 14:42:30', 1),
(3, 1, 1, 'Wandering Whales', 'Ethereal digital art with floating whales', 19.99, 'static/images/products/Wandering Whales.png', '', 'active', 156, '2025-06-06 13:22:41', '2025-06-07 14:42:32', 'approved', NULL, '2025-06-07 14:42:32', 1),
(4, 1, 2, 'Street Life', 'Urban photography capturing city moments', 15.99, 'static/images/products/Scenary.png', '', 'active', 79, '2025-06-06 13:22:41', '2025-06-09 13:27:39', 'approved', NULL, '2025-06-07 14:42:34', 1),
(5, 1, 2, 'Urban Perspective', 'Modern architectural photography', 18.99, 'static/images/products/sample.jpg', '', 'active', 92, '2025-06-06 13:22:41', '2025-06-07 14:42:35', 'approved', NULL, '2025-06-07 14:42:35', 1),
(6, 1, 3, 'Anime Style', 'Japanese-inspired illustration', 22.99, 'static/images/products/sample.jpg', '', 'active', 245, '2025-06-06 13:22:41', '2025-06-07 14:42:37', 'approved', NULL, '2025-06-07 14:42:37', 1),
(7, 1, 3, 'Fantasy World', 'Detailed fantasy illustration', 24.99, 'static/images/products/Wandering Whales.png', '', 'active', 167, '2025-06-06 13:22:41', '2025-06-08 07:35:17', 'approved', NULL, '2025-06-08 07:35:17', 1),
(8, 1, 4, 'Modern Portfolio', 'Clean and professional portfolio template', 34.99, 'static/images/products/sample.jpg', '', 'active', 312, '2025-06-06 13:22:41', '2025-06-07 14:42:39', 'approved', NULL, '2025-06-07 14:42:39', 1),
(9, 1, 4, 'Business Cards', 'Elegant business card templates', 12.99, 'static/images/products/Alter Ego.png', '', 'active', 423, '2025-06-06 13:22:41', '2025-06-07 14:42:41', 'approved', NULL, '2025-06-07 14:42:41', 1),
(10, 1, 5, 'Low Poly Characters Pack', 'Collection of game-ready low poly character models', 29.99, 'static/images/products/sample.jpg', '', 'rejected', 45, '2025-06-06 15:34:01', '2025-06-08 07:08:17', 'rejected', 'ss', '2025-06-08 07:08:17', 1),
(11, 1, 5, 'Sci-Fi Asset Bundle', 'High-quality sci-fi props and environment assets', 39.99, 'static/images/products/sample.jpg', '', 'rejected', 32, '2025-06-06 15:34:01', '2025-06-08 07:08:20', 'rejected', 'dd', '2025-06-08 07:08:20', 1),
(12, 1, 6, 'Character Model Pack', 'Rigged 3D character models perfect for games and animations', 34.99, 'static/images/products/sample.jpg', '', 'active', 78, '2025-06-06 15:38:12', '2025-06-07 14:42:22', 'approved', NULL, '2025-06-07 14:42:22', 1),
(13, 1, 6, 'Environment Pack', 'Detailed 3D environment models with modular pieces', 45.99, 'static/images/products/sample.jpg', '', 'active', 56, '2025-06-06 15:38:12', '2025-06-07 14:42:23', 'approved', NULL, '2025-06-07 14:42:23', 1),
(14, 1, 7, 'UI Elements Pack', 'Complete UI kit with over 200 elements for modern interfaces', 19.99, 'static/images/products/sample.jpg', '', 'active', 125, '2025-06-06 15:38:12', '2025-06-07 14:42:25', 'approved', NULL, '2025-06-07 14:42:25', 1),
(15, 1, 7, 'Game Sound Effects', 'Collection of 100+ high-quality sound effects for gaming', 24.99, 'static/images/products/sample.jpg', '', 'active', 93, '2025-06-06 15:38:12', '2025-06-07 14:42:26', 'approved', NULL, '2025-06-07 14:42:26', 1),
(16, 1, 7, 'Material Pack Pro', 'Professional PBR materials and textures for 3D rendering', 29.99, 'static/images/products/sample.jpg', '', 'active', 147, '2025-06-06 15:38:12', '2025-06-07 14:42:28', 'approved', NULL, '2025-06-07 14:42:28', 1),
(17, 1, 1, 'Cyberpunk Portrait', 'Futuristic digital portrait with neon elements', 45.99, 'static/images/products/sample.jpg', '', 'active', 234, '2025-06-06 15:53:12', '2025-06-07 14:42:09', 'approved', NULL, '2025-06-07 14:42:09', 1),
(18, 1, 1, 'Abstract Dreams', 'Colorful abstract digital artwork', 29.99, 'static/images/products/Alter Ego.png', '', 'active', 187, '2025-06-06 15:53:12', '2025-06-07 14:42:11', 'approved', NULL, '2025-06-07 14:42:11', 1),
(19, 1, 1, 'Digital Landscape', 'Surreal digital landscape composition', 39.99, 'static/images/products/Scenary.png', '', 'active', 156, '2025-06-06 15:53:12', '2025-06-07 14:42:12', 'approved', NULL, '2025-06-07 14:42:12', 1),
(20, 1, 1, 'Fantasy Character', 'Detailed fantasy character illustration', 49.99, 'static/images/products/Wandering Whales.png', '', 'active', 289, '2025-06-06 15:53:12', '2025-06-07 14:42:13', 'approved', NULL, '2025-06-07 14:42:13', 1),
(21, 1, 1, 'Space Adventure', 'Epic space-themed digital artwork', 34.99, 'static/images/products/sample.jpg', '', 'active', 167, '2025-06-06 15:53:12', '2025-06-07 14:42:17', 'approved', NULL, '2025-06-07 14:42:17', 1),
(22, 1, 1, 'Mystical Forest', 'Enchanted forest digital painting', 42.99, 'static/images/products/Wandering Whales.png', '', 'active', 198, '2025-06-06 15:53:12', '2025-06-07 14:42:18', 'approved', NULL, '2025-06-07 14:42:18', 1),
(23, 1, 1, 'Urban Future', 'Futuristic cityscape digital art', 37.99, 'static/images/products/sample.jpg', '', 'active', 145, '2025-06-06 15:53:12', '2025-06-07 14:42:19', 'approved', NULL, '2025-06-07 14:42:19', 1),
(24, 1, 1, 'Dragon\'s Realm', 'Epic dragon-themed digital artwork', 54.99, 'static/images/products/Alter Ego.png', '', 'active', 276, '2025-06-06 15:53:12', '2025-06-07 14:42:21', 'approved', NULL, '2025-06-07 14:42:21', 1),
(25, 1, 1, 'Neon Dreams', 'Vibrant cyberpunk-inspired digital artwork', 27.99, 'static/images/products/sample.jpg', '', 'active', 145, '2025-06-06 16:15:26', '2025-06-07 14:41:52', 'approved', NULL, '2025-06-07 14:41:52', 1),
(26, 1, 1, 'Cosmic Journey', 'Space-themed digital art with galaxies and nebulas', 32.99, 'static/images/products/sample.jpg', '', 'active', 178, '2025-06-06 16:15:26', '2025-06-07 14:41:54', 'approved', NULL, '2025-06-07 14:41:54', 1),
(27, 1, 1, 'Abstract Harmony', 'Modern abstract digital composition', 23.99, 'static/images/products/sample.jpg', '', 'active', 134, '2025-06-06 16:15:26', '2025-06-07 14:41:55', 'approved', NULL, '2025-06-07 14:41:55', 1),
(28, 1, 1, 'Digital Dreams', 'Surreal digital landscape artwork', 28.99, 'static/images/products/sample.jpg', '', 'active', 167, '2025-06-06 16:15:26', '2025-06-07 14:42:02', 'approved', NULL, '2025-06-07 14:42:02', 1),
(29, 1, 1, 'Pixel Paradise', 'Retro-styled pixel art masterpiece', 21.99, 'static/images/products/sample.jpg', '', 'active', 198, '2025-06-06 16:15:26', '2025-06-07 14:42:04', 'approved', NULL, '2025-06-07 14:42:04', 1),
(30, 1, 1, 'Future City', 'Futuristic cityscape digital art', 31.99, 'static/images/products/sample.jpg', '', 'active', 156, '2025-06-06 16:15:26', '2025-06-07 14:42:05', 'approved', NULL, '2025-06-07 14:42:05', 1),
(31, 1, 1, 'Color Explosion', 'Abstract digital art with vibrant colors', 26.99, 'static/images/products/sample.jpg', '', 'active', 189, '2025-06-06 16:15:26', '2025-06-07 14:42:06', 'approved', NULL, '2025-06-07 14:42:06', 1),
(32, 1, 1, 'Digital Flora', 'Nature-inspired digital artwork', 24.99, 'static/images/products/sample.jpg', '', 'active', 143, '2025-06-06 16:15:26', '2025-06-07 14:42:08', 'approved', NULL, '2025-06-07 14:42:08', 1),
(38, 3, 6, 'womp womp', '123123', 1000.00, 'static/images/products/6845405554713_How-to-make-3D-models-for-games.jpg', 'uploads/files/684540555523a_How-to-make-3D-models-for-games.jpg', 'rejected', 0, '2025-06-07 14:59:44', '2025-06-08 07:48:37', 'rejected', 'price is too high please consider reducing it', '2025-06-07 15:00:45', 1),
(34, 3, 1, 'Kahit ano', 'kahit ano description', 10000.00, '/static/images/products/6843f9c5442bc_Takashi_Murakami.jpg', '/uploads/files/6843f9c544acc_Takashi_Murakami.jpg', 'active', 2, '2025-06-07 08:35:17', '2025-06-09 13:51:55', 'approved', NULL, '2025-06-07 14:15:04', 1),
(37, 3, 4, 'trial', 'dasda', 3000.00, 'static/images/products/68445121b7aba_templates_meta_image_720.jpg', 'uploads/files/68445121b8512_templates_meta_image_720.jpg', 'active', 1, '2025-06-07 14:48:01', '2025-06-09 14:53:52', 'rejected', 'Too expensive', '2025-06-07 14:48:46', 1),
(39, 3, 3, 'Illustrational Picture', 'This is a trial', 1200.00, 'static/images/products/684540a8aed9b_download.jpeg', 'uploads/files/684540a8af4ed_download.jpeg', 'active', 3, '2025-06-08 07:50:00', '2025-06-09 14:10:05', 'approved', NULL, '2025-06-08 07:50:54', 1),
(40, 8, 1, 'Pancheeze', 'A cheese in a pan CHEZZY', 1500.00, 'static/images/products/6846eceb4b940_panchesse.jpg', 'uploads/files/6846eceb4c3cb_panchesse.jpg', 'active', 2, '2025-06-09 14:17:15', '2025-06-09 14:24:08', 'approved', NULL, '2025-06-09 14:17:45', 1),
(41, 9, 3, 'Robert Joyce', 'The king of pop', 3000.00, 'static/images/products/6846efe8d8b30_illustration.jpg', 'uploads/files/6846efe8da4a2_illustration.jpg', 'active', 1, '2025-06-09 14:30:00', '2025-06-09 14:53:52', 'approved', NULL, '2025-06-09 14:31:16', 1),
(42, 9, 7, 'dasdsa', 'sadasdasd', 1300.00, 'static/images/products/6846f2fac9562_bright-colorful-acrylic-watercolor-splash-600nw-2450236343.jpg', 'uploads/files/6846f2fac9dd0_bright-colorful-acrylic-watercolor-splash-600nw-2450236343.jpg', 'pending', 0, '2025-06-09 14:43:06', '2025-06-09 14:43:06', 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rating` (`product_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 17, 1, 4.2, NULL, '2025-06-06 15:53:12'),
(2, 18, 1, 4.2, NULL, '2025-06-06 15:53:12'),
(3, 19, 1, 4.5, NULL, '2025-06-06 15:53:12'),
(4, 20, 1, 5.0, NULL, '2025-06-06 15:53:12'),
(5, 21, 1, 4.2, NULL, '2025-06-06 15:53:12'),
(6, 22, 1, 4.1, NULL, '2025-06-06 15:53:12'),
(7, 23, 1, 4.1, NULL, '2025-06-06 15:53:12'),
(8, 24, 1, 4.9, NULL, '2025-06-06 15:53:12');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'buyer', 'Can purchase and download digital products', '2025-06-06 12:40:56'),
(2, 'seller', 'Can sell digital products and manage their store', '2025-06-06 12:40:56'),
(3, 'admin', 'Has full administrative access to the platform', '2025-06-06 12:40:56');

-- --------------------------------------------------------

--
-- Table structure for table `seller_applications`
--

DROP TABLE IF EXISTS `seller_applications`;
CREATE TABLE IF NOT EXISTS `seller_applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `experience_years` int NOT NULL,
  `portfolio_url` varchar(255) NOT NULL,
  `bio` text NOT NULL,
  `government_id_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `application_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `review_date` timestamp NULL DEFAULT NULL,
  `rejection_reason` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `seller_applications`
--

INSERT INTO `seller_applications` (`id`, `user_id`, `name`, `experience_years`, `portfolio_url`, `bio`, `government_id_path`, `status`, `application_date`, `review_date`, `rejection_reason`) VALUES
(1, 5, 'Aedrieu Constantino', 1, 'https://yourname-portfolio.example.com', 'I\'m an aspiring digital artisti pls accept', 'uploads/government_ids/68454e56119d1_472646832_1995551340919119_6232954703431611219_n.jpg', 'approved', '2025-06-08 08:48:22', '2025-06-08 08:49:12', NULL),
(2, 6, 'Jan Kendrick Innoncenio', 2, 'https://yourname-portfolio.example.com', 'I wanna make art', 'uploads/government_ids/684550f980817_ea298e40-5449-4af9-aee2-e651252168ea.jpeg', 'approved', '2025-06-08 08:59:37', '2025-06-08 09:03:15', NULL),
(3, 7, 'Billy Rivera', 2, 'https://yourname-portfolio.example.com', 'league', 'uploads/government_ids/68455469c6589_1a562ceb-fa11-4df8-bf6c-431aa6c85551.jpeg', 'approved', '2025-06-08 09:14:17', '2025-06-08 09:15:14', NULL),
(4, 8, 'Paul Anthony Pancho', 4, 'https://yourname-portfolio.example.com', 'i wanna make an art', 'uploads/government_ids/6846ebd3b88e3_1a562ceb-fa11-4df8-bf6c-431aa6c85551.jpeg', 'rejected', '2025-06-09 14:12:35', '2025-06-09 14:13:31', 'invalid id'),
(5, 8, 'Paul Anthony Pancho', 4, 'https://yourname-portfolio.example.com', 'I\'m passionate about art please accept me i make good digital products', 'uploads/government_ids/6846ec3b012d3_472646832_1995551340919119_6232954703431611219_n.jpg', 'approved', '2025-06-09 14:14:19', '2025-06-09 14:14:57', NULL),
(6, 9, 'kristine maglinao', 3, 'https://yourname-portfolio.example.com', 'I wanna make art', 'uploads/government_ids/6846eea364b9e_a_image.png', 'rejected', '2025-06-09 14:24:35', '2025-06-09 14:25:45', 'Invalid ID'),
(7, 9, 'kristine maglinao', 3, 'https://yourname-portfolio.example.com', 'I wanna make art', 'uploads/government_ids/6846ef067c2a1_a_image.png', 'approved', '2025-06-09 14:26:14', '2025-06-09 14:27:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `first_name`, `last_name`, `password_hash`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@art2cart.com', '', '', '$2y$10$nd0TZVyT4Wl6deeyoGbjQusxa6CPdzfYS.yXOk4q7/gPqQq.LKmHa', NULL, '2025-06-06 12:40:56', '2025-06-07 14:00:49'),
(2, 'testuser', 'test@art2cart.com', '', '', '$2y$10$8K1p/a7UqI1pj3uJn6XZY.x3USSOr.7o.X9kY2KF3YX3PIf9yO.W2', NULL, '2025-06-06 12:40:56', '2025-06-06 14:48:46'),
(3, 'Fuzuri', 'jamescrivera@iskolarngbayan.pup.edu.ph', '', '', '$2y$10$gP3rNxxE3nKlAsVjZ6z/6.fAnE2FYyS9214ehxQoLmrM8NQTbbLxS', NULL, '2025-06-06 14:31:04', '2025-06-06 14:48:46'),
(4, 'clink', 'clink@gmail.com', 'Dale', 'Lee', '$2y$10$fysr3KrYY2GHTITPvTUa.OvD6zglCPaZOZ/QIHh2UzCjeaI6UheKW', NULL, '2025-06-06 15:12:55', '2025-06-06 15:12:55'),
(5, 'test12345', 'testcj@art2cart.com', 'test', '123', '$2y$10$FbXtzqftTYbYEHSBm0fIEu4n9wvJEAA7GayJPnSKysFHCdZESb1J2', NULL, '2025-06-07 07:11:23', '2025-06-07 07:11:23'),
(6, 'jk_54pogi', 'jk@art2cart.com', 'Jan Kendrick', 'Innoncencio', '$2y$10$Wl33wtcZUqAWOLngCT2xE.ruMI0xb88e2z0jPwZlHbLVys4fcwYkK', NULL, '2025-06-08 08:58:38', '2025-06-08 08:58:38'),
(7, 'billy123', 'billy@art2cart.com', 'Billy', 'Rivera', '$2y$10$QCpEWCM1puSjNNj.qEWrAOQTY7/4EbZlr7LcJ2MQ7oo3ssPFrxu7u', NULL, '2025-06-08 09:13:34', '2025-06-08 09:13:34'),
(8, 'macaronspag', 'paul@art2cart.com', 'Paul', 'Pancho', '$2y$10$PcWepZMV/GeEt4lg//mhZuepVrB73WJ8JhOhRQUbUb9I5Wf59XyvG', NULL, '2025-06-09 14:01:30', '2025-06-09 14:01:30'),
(9, 'kahitano', 'kahitanona@art2cart.com', 'Kristine', 'Maglinao', '$2y$10$x3cMGQqhARlu0S5Hnui4rOUmshf0PTogyh6FiHyzMc0XMrZMEJl.K', NULL, '2025-06-09 14:21:32', '2025-06-09 14:21:32');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_at`) VALUES
(1, 3, '2025-06-06 12:40:56'),
(2, 1, '2025-06-06 12:40:56'),
(2, 2, '2025-06-06 12:40:56'),
(3, 1, '2025-06-06 14:31:04'),
(4, 1, '2025-06-06 15:12:55'),
(3, 2, '2025-06-07 06:51:17'),
(5, 1, '2025-06-07 07:11:23'),
(5, 2, '2025-06-08 08:49:12'),
(6, 1, '2025-06-08 08:58:38'),
(6, 2, '2025-06-08 09:03:15'),
(7, 1, '2025-06-08 09:13:34'),
(7, 2, '2025-06-08 09:15:14'),
(8, 1, '2025-06-09 14:01:30'),
(8, 2, '2025-06-09 14:14:57'),
(9, 1, '2025-06-09 14:21:32'),
(9, 2, '2025-06-09 14:27:16');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
