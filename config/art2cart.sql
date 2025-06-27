-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 14, 2025 at 07:45 AM
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
-- Table structure for table `billing_addresses`
--

DROP TABLE IF EXISTS `billing_addresses`;
CREATE TABLE IF NOT EXISTS `billing_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state_province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `payment_method` enum('paypal','card','gcash','credit') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_billing` (`order_id`),
  KEY `idx_billing_email` (`email`),
  KEY `idx_billing_country` (`country`),
  KEY `idx_billing_payment_method` (`payment_method`),
  KEY `idx_billing_name` (`first_name`,`last_name`),
  KEY `idx_billing_location` (`city`,`country`),
  KEY `idx_billing_created` (`created_at`)
) ;

--
-- Dumping data for table `billing_addresses`
--

INSERT INTO `billing_addresses` (`id`, `order_id`, `first_name`, `last_name`, `email`, `phone`, `address`, `city`, `state_province`, `postal_code`, `country`, `payment_method`, `created_at`, `updated_at`) VALUES
(5, 5, 'Edward', 'Hawkins', 'jk@art2cart.com', '09457996892', 'jk@art2cart.com', 'Sto. Tomas', 'Batangas', '4234', 'Philippines', 'card', '2025-06-10 09:21:19', '2025-06-10 09:21:19'),
(6, 6, 'FgOMnHiaQKsV', 'FgOMnHiaQKsV', 'FgOMnHiaQKsV@gmail.com', '123', 'FgOMnHiaQKsV', 'FgOMnHiaQKsV', 'FgOMnHiaQKsV', '123', 'Philippines', 'paypal', '2025-06-13 01:25:25', '2025-06-13 01:25:25'),
(7, 7, 'FgOMnHiaQKsV', 'FgOMnHiaQKsV', 'FgOMnHiaQKsV@gmail.com', '123', 'FgOMnHiaQKsV', 'FgOMnHiaQKsV', 'FgOMnHiaQKsV', '4027', 'Philippines', 'paypal', '2025-06-13 01:26:57', '2025-06-13 01:26:57'),
(8, 8, 'Sarah', 'Anderson', 'sarah@art2cart.com', '09128321321', '322 Mayapa', 'Calamba', 'Calamba', '4029', 'Philippines', 'gcash', '2025-06-13 01:27:43', '2025-06-13 01:27:43'),
(9, 9, 'Sarah', 'Anderson', 'sarah@art2cart.com', '09128321321', '322 Mayapa', 'Calamba', 'Calamba', '4029', 'Philippines', 'paypal', '2025-06-13 01:30:38', '2025-06-13 01:30:38'),
(10, 10, 'James', 'Carlo Rivera', 'jamescarlorivera52@gmail.com', '+63 945 799 6892', 'Blk 27, Lot 25, Wine cup st, Ponteverde ,Sto.Tomas,Batangas', 'Sto.Tomas', 'Region 4A/CALABARZON/BATANGAS', '4234', 'Philippines', 'gcash', '2025-06-13 01:41:59', '2025-06-13 01:41:59'),
(11, 11, 'test', 'test', 'test@gmail.com', '123', 'test', 'test', 'test', '123', 'Philippines', 'paypal', '2025-06-13 01:43:17', '2025-06-13 01:43:17'),
(12, 12, 'kristine ann', 'maglinao', 'kristineannmaglinao@gmail.com', '09267084699', '123 poblacion, malvar, batangas', 'malvar', 'batangas', '3022', 'Philippines', 'gcash', '2025-06-13 01:49:49', '2025-06-13 01:49:49'),
(13, 13, 'Jandron Gian', 'Ramos', 'rjandrongian@gmail.com', '09693129080', 'Blk. 13 Lot 36 Yakal St. Calamba Park Residences Purok 4', 'Calamba', 'Laguna', '4027', 'Philippines', 'paypal', '2025-06-13 01:57:08', '2025-06-13 01:57:08'),
(14, 14, 'Sarah', 'Anderson', 'sarah@art2cart.com', '09128321321', '322 Mayapa', 'Calamba', 'Calamba', '4029', 'Philippines', 'paypal', '2025-06-13 02:30:17', '2025-06-13 02:30:17');

-- --------------------------------------------------------

--
-- Stand-in structure for view `billing_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `billing_summary`;
CREATE TABLE IF NOT EXISTS `billing_summary` (
`country` varchar(100)
,`payment_method` enum('paypal','card','gcash','credit')
,`order_count` bigint
,`total_revenue` decimal(32,2)
,`avg_order_value` decimal(14,6)
,`first_order` timestamp
,`last_order` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_cart_user` (`user_id`),
  KEY `idx_cart_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Cart table for digital marketplace - each user can have one of each product';

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `created_at`, `updated_at`) VALUES
(15, 10, 13, '2025-06-13 05:59:41', '2025-06-13 05:59:41'),
(18, 12, 14, '2025-06-14 07:30:33', '2025-06-14 07:30:33');

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
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_categories_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `download_logs`
--

INSERT INTO `download_logs` (`id`, `user_id`, `product_id`, `order_id`, `download_time`, `ip_address`, `user_agent`) VALUES
(1, 10, 14, 10, '2025-06-13 01:42:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(2, 10, 14, 10, '2025-06-13 01:42:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(3, 10, 14, 10, '2025-06-13 01:42:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(4, 9, 14, 11, '2025-06-13 01:45:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(5, 11, 13, 12, '2025-06-13 01:50:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

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
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`)
) ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `billing_info`, `created_at`) VALUES
(5, 3, 3000.00, 'completed', NULL, '2025-06-10 09:21:19'),
(6, 9, 4790.00, 'completed', NULL, '2025-06-13 01:25:25'),
(7, 9, 4790.00, 'completed', NULL, '2025-06-13 01:26:57'),
(8, 2, 4790.00, 'completed', NULL, '2025-06-13 01:27:43'),
(9, 2, 8750.00, 'completed', NULL, '2025-06-13 01:30:38'),
(10, 10, 4790.00, 'completed', NULL, '2025-06-13 01:41:59'),
(11, 9, 4790.00, 'completed', NULL, '2025-06-13 01:43:17'),
(12, 11, 8750.00, 'completed', NULL, '2025-06-13 01:49:49'),
(13, 9, 8750.00, 'completed', NULL, '2025-06-13 01:57:08'),
(14, 2, 4790.00, 'completed', NULL, '2025-06-13 02:30:17');

-- --------------------------------------------------------

--
-- Stand-in structure for view `orders_with_billing`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `orders_with_billing`;
CREATE TABLE IF NOT EXISTS `orders_with_billing` (
`id` int
,`user_id` int
,`total_amount` decimal(10,2)
,`status` enum('pending','completed','cancelled')
,`created_at` timestamp
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`phone` varchar(20)
,`address` varchar(255)
,`city` varchar(100)
,`state_province` varchar(100)
,`postal_code` varchar(20)
,`country` varchar(100)
,`payment_method` enum('paypal','card','gcash','credit')
,`full_name` varchar(101)
);

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Order items for digital marketplace - each item represents one digital product purchase';

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `price`, `created_at`) VALUES
(5, 5, 4, 3000.00, '2025-06-10 09:21:19'),
(6, 6, 14, 4790.00, '2025-06-13 01:25:25'),
(7, 7, 14, 4790.00, '2025-06-13 01:26:57'),
(8, 8, 14, 4790.00, '2025-06-13 01:27:43'),
(9, 9, 13, 8750.00, '2025-06-13 01:30:38'),
(10, 10, 14, 4790.00, '2025-06-13 01:41:59'),
(11, 11, 14, 4790.00, '2025-06-13 01:43:17'),
(12, 12, 13, 8750.00, '2025-06-13 01:49:49'),
(13, 13, 13, 8750.00, '2025-06-13 01:57:08'),
(14, 14, 14, 4790.00, '2025-06-13 02:30:17');

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
  KEY `fk_products_reviewer` (`reviewed_by`),
  KEY `idx_products_seller` (`seller_id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_price` (`price`)
) ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `title`, `description`, `price`, `image_path`, `file_path`, `status`, `downloads`, `created_at`, `updated_at`, `review_status`, `review_notes`, `review_date`, `reviewed_by`) VALUES
(4, 3, 1, 'Basquiat Got a Bluetooth', 'This stylized portrait feels like a digital-age tribute to street art’s raw genius. A mix of tribal markings, basketball jersey flair, and emoji-like facial features, this character looks like he wandered out of a dystopian Brooklyn playground with poetic rage and Wi-Fi in his veins. You’re not sure if he’s about to drop a verse or hack the system.', 3000.00, 'static/images/products/6847f56b092f1_BasquiatGotaBluetooth.jpg', 'uploads/files/6847f56b09c77_BasquiatGotaBluetooth.jpg', 'active', 0, '2025-06-10 09:05:47', '2025-06-10 09:06:28', 'approved', NULL, '2025-06-10 09:06:28', 1),
(7, 4, 1, 'Cubism Cool Cousin', 'What happens when you put Picasso, Dada collage, and Instagram aesthetic filters in a blender? This. The overlapping eyes, surreal hands, and stitched-together paper pieces evoke the inner psyche of a dreamer living between analog memories and digital anxieties. It’s chaos—but make it couture.', 5000.00, 'static/images/products/684b71e387b67_CubismsCoolCousin.jpg', 'uploads/files/684b71e388219_CubismsCoolCousin.jpg', 'active', 0, '2025-06-13 00:33:39', '2025-06-13 01:16:33', 'approved', NULL, '2025-06-13 01:16:33', 1),
(8, 4, 1, 'Masked, But Not Hiding', 'This dual-faced figure stands in striking contrast—bold colors, clean lines, and a look that says \r\n\r\n“I know who I am, even if you don’t.” A message at the bottom, “Accept Yourself,” makes it clear.&#34;', 6000.00, 'static/images/products/684b722ac1b88_MaskedButNotHiding.jpg', 'uploads/files/684b722ac2b0a_MaskedButNotHiding.jpg', 'active', 0, '2025-06-13 00:34:50', '2025-06-13 01:16:29', 'approved', NULL, '2025-06-13 01:16:29', 1),
(9, 4, 1, 'Cyber Monk in a Neon Temple', 'Glowing purple skin, tribal tech tattoos, and a hypnotic third eye give this figure an otherworldly authority. He looks like a digital shaman—part DJ, part oracle—ready to decode the secrets of the universe via glitch art and sacred geometry. His aura? Equal parts Tron and Nirvana.', 8000.00, 'static/images/products/684b72473441e_CyberMonkinaNeonTemple.jpg', 'uploads/files/684b724735554_CyberMonkinaNeonTemple.jpg', 'active', 0, '2025-06-13 00:35:19', '2025-06-13 01:16:27', 'approved', NULL, '2025-06-13 01:16:27', 1),
(10, 4, 1, 'David, Rebooted', 'The Renaissance’s most famous statue gets a punk-rock makeover. With graffiti scrawled across his face and bright yellow sunglasses daring you to look away, this version of David is all attitude. He’s traded slingshots for streetwear, and honestly, he wears rebellion better than marble ever did.', 8670.00, 'static/images/products/684b726aeabd0_DavidRebooted.jpg', 'uploads/files/684b726aeb0fb_DavidRebooted.jpg', 'active', 0, '2025-06-13 00:35:54', '2025-06-13 01:16:23', 'approved', NULL, '2025-06-13 01:16:23', 1),
(11, 4, 1, 'MJ in the Multiverse of Madness', 'Michael Jordan dunks through a hyper-colored, sticker-strewn universe filled with comic book chaos and retro ad cutouts. It’s like a teenage bedroom wall exploded in glorious technicolor. This isn&#39;t just a tribute to a legend—it’s a slam dunk through pop culture&#39;s collective subconscious.', 9500.00, 'static/images/products/684b72b4348fa_MJintheMultiverseofMadness.jpg', 'uploads/files/684b72b434e43_MJintheMultiverseofMadness.jpg', 'active', 0, '2025-06-13 00:37:08', '2025-06-13 01:16:20', 'approved', NULL, '2025-06-13 01:16:20', 1),
(12, 4, 1, 'Dalí Did a Tag Job', 'The melting mustache says it all—this surrealist icon has been reimagined as a graffiti-riddled revolutionary. Collaged clippings, spray paint tags, and neon doodles blur the line between timeless and timely. It’s as if Dalí himself strolled into a punk alleyway and said, “Yes, this is me now', 9750.00, 'static/images/products/684b72ed0cea3_DaliDidaTagJob.jpg', 'uploads/files/684b72ed0d3d1_DaliDidaTagJob.jpg', 'active', 0, '2025-06-13 00:38:05', '2025-06-13 01:16:17', 'approved', NULL, '2025-06-13 01:16:17', 1),
(13, 4, 1, 'Mona Lisa, But Make It Riot Grrrl', 'You’ve seen her smirk before, but never like this. Leather, graffiti, pink spray paint, and “SEE YOU IN PARIS” scrawled across her chest—this isn’t just a remix of da Vinci’s icon, it’s a declaration. She’s armed with irony, rebellion, and a fresh pair of Doc Martens. Smile? Nah. She sneers now', 8750.00, 'static/images/products/684b733833140_MonaLisaAintGivingaFuck.jpg', 'uploads/files/684b7338336cc_MonaLisaAintGivingaFuck.jpg', 'active', 1, '2025-06-13 00:39:20', '2025-06-13 01:50:09', 'approved', NULL, '2025-06-13 01:16:14', 1),
(14, 4, 1, 'Night Mode Samurai', 'This brooding figure wears his armor like a mixtape—layered, coded, and glowing with cryptic energy. With glitching shades and anime-style hair, he looks like he just logged out of the Matrix to join a lo-fi revolution. Not just a portrait—this is a vibe you can feel in bass-heavy beats', 4790.00, 'static/images/products/684b735f1131e_NightModeSamurai.jpg', 'uploads/files/684b735f11bf6_NightModeSamurai.jpg', 'active', 4, '2025-06-13 00:39:59', '2025-06-13 01:45:19', 'approved', NULL, '2025-06-13 01:14:30', 1);

--
-- Triggers `products`
--
DROP TRIGGER IF EXISTS `validate_product_price`;
DELIMITER $$
CREATE TRIGGER `validate_product_price` BEFORE INSERT ON `products` FOR EACH ROW BEGIN
    IF NEW.price <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product price must be greater than 0';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `validate_product_price_update`;
DELIMITER $$
CREATE TRIGGER `validate_product_price_update` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.price <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product price must be greater than 0';
    END IF;
END
$$
DELIMITER ;

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
  KEY `fk_ratings_user` (`user_id`)
) ;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `seller_applications`
--

INSERT INTO `seller_applications` (`id`, `user_id`, `name`, `experience_years`, `portfolio_url`, `bio`, `government_id_path`, `status`, `application_date`, `review_date`, `rejection_reason`) VALUES
(2, 4, 'Michael Chen', 6, 'https://micahel_chen-portfolio.com', 'An Experienced Artist of Digital Arts', 'uploads/government_ids/6848031e2881e_Michael ID.jpeg', 'approved', '2025-06-10 10:04:14', '2025-06-10 10:07:34', NULL),
(3, 5, 'Paul Anthony Pancho', 10, 'https://pan_cheeze-portfolio.com', 'I love and hate art', 'uploads/government_ids/6848037c63e27_Pancheeze.jpg', 'approved', '2025-06-10 10:05:48', '2025-06-10 10:07:32', NULL),
(4, 8, 'Abdull Jabar', 8, 'https://abdul_jabar-portfolio.com', 'Art', 'uploads/government_ids/684b7651e9e23_OSK.jpeg', 'approved', '2025-06-13 00:52:33', '2025-06-13 01:16:50', NULL),
(5, 7, 'Elena Rodriguez', 5, 'https://elena_assets-portfolio.com', 'Art', 'uploads/government_ids/684b78814acb0_OIP.jpeg', 'approved', '2025-06-13 01:01:53', '2025-06-13 01:16:49', NULL),
(6, 6, 'Sarah Anderson', 8, 'https://sarah_anderson-portfolio.com', 'art', 'uploads/government_ids/684b78aa77912_OIP.jpeg', 'approved', '2025-06-13 01:02:34', '2025-06-13 01:16:47', NULL),
(7, 10, 'James Carlo Rivera', 3, 'https://sarah_anderson-portfolio.com', 'art', 'uploads/government_ids/684bb7e75b2f8_472646832_1995551340919119_6232954703431611219_n.jpg', 'approved', '2025-06-13 05:32:23', '2025-06-14 06:38:01', NULL),
(8, 1, 'admin', 7, 'https://yourname-portfolio.example.com', 'art', 'uploads/government_ids/684d0a990023d_Night_Mode_Samurai.jpg', 'approved', '2025-06-14 05:37:29', '2025-06-14 06:20:09', NULL),
(9, 2, 'admin', 7, 'https://yourname-portfolio.example.com', 'art', 'uploads/government_ids/684d198745801_Kahit_ano.jpg', 'rejected', '2025-06-14 06:41:11', '2025-06-14 06:56:09', 'Test rejection reason - 2025-06-14 06:56:09'),
(10, 2, 'admin', 7, 'https://yourname-portfolio.example.com', 'art', 'uploads/government_ids/684d1d8bacfea_Illustrational_Picture.jpeg', 'rejected', '2025-06-14 06:58:19', '2025-06-14 06:58:47', 'test'),
(11, 2, 'admin', 7, 'https://yourname-portfolio.example.com', 'test', 'uploads/government_ids/684d2080ce359_472646832_1995551340919119_6232954703431611219_n.jpg', 'pending', '2025-06-14 07:10:56', NULL, NULL);

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
  `phone` varchar(20) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_username` (`username`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `first_name`, `last_name`, `password_hash`, `profile_image`, `phone`, `email_verified`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@art2cart.com', NULL, NULL, '$2y$10$hd7ubEJzMKZOG7bte02z3.VzckP6KeLe5KdeEuRFBcKJ7EuGpXmw.', NULL, NULL, 0, 'active', '2025-06-10 08:28:31', '2025-06-10 08:35:10'),
(2, 'testbuyer', 'buyer@art2cart.com', NULL, NULL, '$2y$10$w391litiwXB49tkkKkPlt.g/tHc4AC5jiwwtwBG5aQTAJvJB2uBIG', NULL, NULL, 0, 'active', '2025-06-10 08:28:31', '2025-06-10 08:35:11'),
(3, 'buyerseller', 'buyerseller@art2cart.com', NULL, NULL, '$2y$10$pd0vr6qFutiHzHgGWtm2o.95nBj2hjPUqewe8fkE4Y3NeerGO9FtC', NULL, NULL, 0, 'active', '2025-06-10 08:28:31', '2025-06-10 08:35:11'),
(4, 'michael_chen', 'chen@art2cart.com', 'Michael', 'Chen', '$2y$10$KWBYOfjOZEppmV9CCBE.VuSlHgk6/c1ymQuDDEcfq2I8Y5UUASKem', NULL, NULL, 0, 'active', '2025-06-10 10:01:43', '2025-06-10 10:01:43'),
(5, 'pan_cheeze', 'paul@art2cart.com', 'Paul', 'Pancho', '$2y$10$mlP6V7sYiYrfWiUmw63Vxuq.b39xngkEDtFXROD4075usUkQGhxjO', NULL, NULL, 0, 'active', '2025-06-10 10:04:55', '2025-06-10 10:04:55'),
(6, 'sarah_anderson', 'sarah@art2cart.com', 'Sarah', 'Anderson', '$2y$10$35I16uKC8UeEf95yTFgIgOT1ku6LWZdzmDqPxUIiLwYeLySab3suC', NULL, NULL, 0, 'active', '2025-06-13 00:43:44', '2025-06-13 00:43:44'),
(7, 'elena_assets', 'elena@art2cart.com', 'Elena', 'Rodriguez', '$2y$10$lWVCYe1kI/OOCfraAp6VIeTB1uZ.2osZtmkLs4jtWE74B6ioBMTie', NULL, NULL, 0, 'active', '2025-06-13 00:44:43', '2025-06-13 00:44:43'),
(8, 'jabargoesdigital', 'jabar@art2cart.com', 'Abdull', 'Jabar', '$2y$10$ixe7yiV.ZYucv.X.N3Y.zex/nZoBgfjGaKHHSgIwgKzJ8ZB8ugR7W', NULL, NULL, 0, 'active', '2025-06-13 00:45:59', '2025-06-13 00:45:59'),
(9, 'FgOMnHiaQKsV', 'FgOMnHiaQKsV@gmail.com', 'FgOMnHiaQKsV', 'FgOMnHiaQKsV', '$2y$10$9qiyQHslZEK14p0Z6Q.k3excu9Px1d3I2UdU/NwtZP8QKrVfQD7Pq', NULL, NULL, 0, 'active', '2025-06-13 01:24:24', '2025-06-13 01:24:24'),
(10, 'jimz', 'jamescarlo@art2cart.com', 'James', 'Carlo', '$2y$10$LUnmTjMP/DdOBnT8cjeIM.1VPJ5AnuXFJrGZjkGKKXKuBzo1wgOrK', NULL, NULL, 0, 'active', '2025-06-13 01:37:34', '2025-06-13 01:37:34'),
(11, 'ksstn', 'kristineannmaglinao@gmail.com', 'kristine ann', 'maglinao', '$2y$10$6dYCH5M9VQ2VjMednHAysuPh8mM7YCnttmEy7R9R.LqLAtq/N0aXe', NULL, NULL, 0, 'active', '2025-06-13 01:44:53', '2025-06-13 01:44:53'),
(12, 'Dale Lee', 'dale@gmail.com', 'Dale Andrew', 'Lee', '$2y$10$00KiGPIC4uNTz6LyPy01zuYuz9cEZtENk/GV.IOaJe7w25Rr/qMEq', NULL, NULL, 0, 'active', '2025-06-14 07:30:03', '2025-06-14 07:30:03');

--
-- Triggers `users`
--
DROP TRIGGER IF EXISTS `validate_user_email_insert`;
DELIMITER $$
CREATE TRIGGER `validate_user_email_insert` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `validate_user_email_update`;
DELIMITER $$
CREATE TRIGGER `validate_user_email_update` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format';
    END IF;
END
$$
DELIMITER ;

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
  KEY `fk_user_roles_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_at`) VALUES
(1, 2, '2025-06-14 06:20:09'),
(1, 3, '2025-06-10 08:35:11'),
(2, 1, '2025-06-10 08:35:11'),
(3, 1, '2025-06-10 08:35:11'),
(3, 2, '2025-06-10 08:35:11'),
(4, 1, '2025-06-10 10:01:43'),
(4, 2, '2025-06-10 10:07:34'),
(5, 1, '2025-06-10 10:04:55'),
(5, 2, '2025-06-10 10:07:32'),
(6, 1, '2025-06-13 00:43:44'),
(6, 2, '2025-06-13 01:16:47'),
(7, 1, '2025-06-13 00:44:43'),
(7, 2, '2025-06-13 01:16:49'),
(8, 1, '2025-06-13 00:45:59'),
(8, 2, '2025-06-13 01:16:50'),
(9, 1, '2025-06-13 01:24:24'),
(10, 1, '2025-06-13 01:37:34'),
(10, 2, '2025-06-14 06:38:01'),
(11, 1, '2025-06-13 01:44:53'),
(12, 1, '2025-06-14 07:30:03');

-- --------------------------------------------------------

--
-- Structure for view `billing_summary`
--
DROP TABLE IF EXISTS `billing_summary`;

DROP VIEW IF EXISTS `billing_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `billing_summary`  AS SELECT `ba`.`country` AS `country`, `ba`.`payment_method` AS `payment_method`, count(0) AS `order_count`, sum(`o`.`total_amount`) AS `total_revenue`, avg(`o`.`total_amount`) AS `avg_order_value`, min(`ba`.`created_at`) AS `first_order`, max(`ba`.`created_at`) AS `last_order` FROM (`billing_addresses` `ba` join `orders` `o` on((`ba`.`order_id` = `o`.`id`))) GROUP BY `ba`.`country`, `ba`.`payment_method` ORDER BY `total_revenue` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `orders_with_billing`
--
DROP TABLE IF EXISTS `orders_with_billing`;

DROP VIEW IF EXISTS `orders_with_billing`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `orders_with_billing`  AS SELECT `o`.`id` AS `id`, `o`.`user_id` AS `user_id`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`created_at` AS `created_at`, `ba`.`first_name` AS `first_name`, `ba`.`last_name` AS `last_name`, `ba`.`email` AS `email`, `ba`.`phone` AS `phone`, `ba`.`address` AS `address`, `ba`.`city` AS `city`, `ba`.`state_province` AS `state_province`, `ba`.`postal_code` AS `postal_code`, `ba`.`country` AS `country`, `ba`.`payment_method` AS `payment_method`, concat(`ba`.`first_name`,' ',`ba`.`last_name`) AS `full_name` FROM (`orders` `o` left join `billing_addresses` `ba` on((`o`.`id` = `ba`.`order_id`))) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing_addresses`
--
ALTER TABLE `billing_addresses`
  ADD CONSTRAINT `fk_billing_addresses_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `download_logs`
--
ALTER TABLE `download_logs`
  ADD CONSTRAINT `fk_download_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_download_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_download_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_products_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `fk_ratings_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD CONSTRAINT `fk_seller_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
