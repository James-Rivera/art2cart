-- MySQL dump 10.13  Distrib 9.1.0, for Win64 (x86_64)
--
-- Host: localhost    Database: art2cart
-- ------------------------------------------------------
-- Server version	9.1.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `billing_addresses`
--

DROP TABLE IF EXISTS `billing_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_addresses` (
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
  KEY `idx_billing_created` (`created_at`),
  CONSTRAINT `fk_billing_addresses_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_billing_email_format` CHECK (regexp_like(`email`,_utf8mb4'^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+.[A-Za-z]{2,}$')),
  CONSTRAINT `chk_billing_first_name` CHECK ((length(`first_name`) > 0)),
  CONSTRAINT `chk_billing_last_name` CHECK ((length(`last_name`) > 0)),
  CONSTRAINT `chk_billing_postal_code` CHECK ((length(`postal_code`) > 0))
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_addresses`
--

LOCK TABLES `billing_addresses` WRITE;
/*!40000 ALTER TABLE `billing_addresses` DISABLE KEYS */;
INSERT INTO `billing_addresses` VALUES (5,5,'Edward','Hawkins','jk@art2cart.com','09457996892','jk@art2cart.com','Sto. Tomas','Batangas','4234','Philippines','card','2025-06-10 09:21:19','2025-06-10 09:21:19'),(6,6,'FgOMnHiaQKsV','FgOMnHiaQKsV','FgOMnHiaQKsV@gmail.com','123','FgOMnHiaQKsV','FgOMnHiaQKsV','FgOMnHiaQKsV','123','Philippines','paypal','2025-06-13 01:25:25','2025-06-13 01:25:25'),(7,7,'FgOMnHiaQKsV','FgOMnHiaQKsV','FgOMnHiaQKsV@gmail.com','123','FgOMnHiaQKsV','FgOMnHiaQKsV','FgOMnHiaQKsV','4027','Philippines','paypal','2025-06-13 01:26:57','2025-06-13 01:26:57'),(8,8,'Sarah','Anderson','sarah@art2cart.com','09128321321','322 Mayapa','Calamba','Calamba','4029','Philippines','gcash','2025-06-13 01:27:43','2025-06-13 01:27:43'),(9,9,'Sarah','Anderson','sarah@art2cart.com','09128321321','322 Mayapa','Calamba','Calamba','4029','Philippines','paypal','2025-06-13 01:30:38','2025-06-13 01:30:38'),(10,10,'James','Carlo Rivera','jamescarlorivera52@gmail.com','+63 945 799 6892','Blk 27, Lot 25, Wine cup st, Ponteverde ,Sto.Tomas,Batangas','Sto.Tomas','Region 4A/CALABARZON/BATANGAS','4234','Philippines','gcash','2025-06-13 01:41:59','2025-06-13 01:41:59'),(11,11,'test','test','test@gmail.com','123','test','test','test','123','Philippines','paypal','2025-06-13 01:43:17','2025-06-13 01:43:17'),(12,12,'kristine ann','maglinao','kristineannmaglinao@gmail.com','09267084699','123 poblacion, malvar, batangas','malvar','batangas','3022','Philippines','gcash','2025-06-13 01:49:49','2025-06-13 01:49:49'),(13,13,'Jandron Gian','Ramos','rjandrongian@gmail.com','09693129080','Blk. 13 Lot 36 Yakal St. Calamba Park Residences Purok 4','Calamba','Laguna','4027','Philippines','paypal','2025-06-13 01:57:08','2025-06-13 01:57:08'),(14,14,'Sarah','Anderson','sarah@art2cart.com','09128321321','322 Mayapa','Calamba','Calamba','4029','Philippines','paypal','2025-06-13 02:30:17','2025-06-13 02:30:17');
/*!40000 ALTER TABLE `billing_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `billing_summary`
--

DROP TABLE IF EXISTS `billing_summary`;
/*!50001 DROP VIEW IF EXISTS `billing_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `billing_summary` AS SELECT 
 1 AS `country`,
 1 AS `payment_method`,
 1 AS `order_count`,
 1 AS `total_revenue`,
 1 AS `avg_order_value`,
 1 AS `first_order`,
 1 AS `last_order`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
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
  KEY `idx_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Cart table for digital marketplace - each user can have one of each product';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (18,12,14,'2025-06-14 07:30:33','2025-06-14 07:30:33'),(21,1,17,'2025-06-15 05:40:51','2025-06-15 05:40:51');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Digital Art','digital-art','Digital artwork and illustrations','static/images/icons/palette.png',1),(2,'Photography','photography','High-quality photographs','static/images/icons/camera.png',2),(3,'Illustrations','illustrations','Hand-drawn and vector illustrations','static/images/icons/brush.png',3),(4,'Templates','templates','Website and design templates','static/images/icons/layout-template.png',4),(6,'3D Models','3d-models','Professional 3D models including characters, environments, and props for games and animations','static/images/icons/layers.png',5),(7,'Digital Assets','digital-assets','Premium digital assets including textures, materials, sound effects, and UI kits','static/images/icons/box.png',6);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `download_logs`
--

DROP TABLE IF EXISTS `download_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `download_logs` (
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
  KEY `idx_order_downloads` (`order_id`,`download_time`),
  CONSTRAINT `fk_download_logs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_download_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_download_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `download_logs`
--

LOCK TABLES `download_logs` WRITE;
/*!40000 ALTER TABLE `download_logs` DISABLE KEYS */;
INSERT INTO `download_logs` VALUES (1,10,14,10,'2025-06-13 01:42:53','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),(2,10,14,10,'2025-06-13 01:42:55','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),(3,10,14,10,'2025-06-13 01:42:56','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),(4,9,14,11,'2025-06-13 01:45:19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),(5,11,13,12,'2025-06-13 01:50:09','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');
/*!40000 ALTER TABLE `download_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Order items for digital marketplace - each item represents one digital product purchase';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (5,5,4,3000.00,'2025-06-10 09:21:19'),(6,6,14,4790.00,'2025-06-13 01:25:25'),(7,7,14,4790.00,'2025-06-13 01:26:57'),(8,8,14,4790.00,'2025-06-13 01:27:43'),(9,9,13,8750.00,'2025-06-13 01:30:38'),(10,10,14,4790.00,'2025-06-13 01:41:59'),(11,11,14,4790.00,'2025-06-13 01:43:17'),(12,12,13,8750.00,'2025-06-13 01:49:49'),(13,13,13,8750.00,'2025-06-13 01:57:08'),(14,14,14,4790.00,'2025-06-13 02:30:17');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `billing_info` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_orders_total` CHECK ((`total_amount` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (5,3,3000.00,'completed',NULL,'2025-06-10 09:21:19'),(6,9,4790.00,'completed',NULL,'2025-06-13 01:25:25'),(7,9,4790.00,'completed',NULL,'2025-06-13 01:26:57'),(8,2,4790.00,'completed',NULL,'2025-06-13 01:27:43'),(9,2,8750.00,'completed',NULL,'2025-06-13 01:30:38'),(10,10,4790.00,'completed',NULL,'2025-06-13 01:41:59'),(11,9,4790.00,'completed',NULL,'2025-06-13 01:43:17'),(12,11,8750.00,'completed',NULL,'2025-06-13 01:49:49'),(13,9,8750.00,'completed',NULL,'2025-06-13 01:57:08'),(14,2,4790.00,'completed',NULL,'2025-06-13 02:30:17');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `orders_with_billing`
--

DROP TABLE IF EXISTS `orders_with_billing`;
/*!50001 DROP VIEW IF EXISTS `orders_with_billing`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `orders_with_billing` AS SELECT 
 1 AS `id`,
 1 AS `user_id`,
 1 AS `total_amount`,
 1 AS `status`,
 1 AS `created_at`,
 1 AS `first_name`,
 1 AS `last_name`,
 1 AS `email`,
 1 AS `phone`,
 1 AS `address`,
 1 AS `city`,
 1 AS `state_province`,
 1 AS `postal_code`,
 1 AS `country`,
 1 AS `payment_method`,
 1 AS `full_name`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
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
  KEY `idx_products_price` (`price`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_products_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_products_price` CHECK ((`price` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (4,3,1,'Basquiat Got a Bluetooth','This stylized portrait feels like a digital-age tribute to street art’s raw genius. A mix of tribal markings, basketball jersey flair, and emoji-like facial features, this character looks like he wandered out of a dystopian Brooklyn playground with poetic rage and Wi-Fi in his veins. You’re not sure if he’s about to drop a verse or hack the system.',3000.00,'static/images/products/6847f56b092f1_BasquiatGotaBluetooth.jpg','uploads/files/6847f56b09c77_BasquiatGotaBluetooth.jpg','active',0,'2025-06-10 09:05:47','2025-06-10 09:06:28','approved',NULL,'2025-06-10 09:06:28',1),(7,4,1,'Cubism Cool Cousin','What happens when you put Picasso, Dada collage, and Instagram aesthetic filters in a blender? This. The overlapping eyes, surreal hands, and stitched-together paper pieces evoke the inner psyche of a dreamer living between analog memories and digital anxieties. It’s chaos—but make it couture.',5000.00,'static/images/products/684b71e387b67_CubismsCoolCousin.jpg','uploads/files/684b71e388219_CubismsCoolCousin.jpg','active',0,'2025-06-13 00:33:39','2025-06-13 01:16:33','approved',NULL,'2025-06-13 01:16:33',1),(8,4,1,'Masked, But Not Hiding','This dual-faced figure stands in striking contrast—bold colors, clean lines, and a look that says \r\n\r\n“I know who I am, even if you don’t.” A message at the bottom, “Accept Yourself,” makes it clear.&#34;',6000.00,'static/images/products/684b722ac1b88_MaskedButNotHiding.jpg','uploads/files/684b722ac2b0a_MaskedButNotHiding.jpg','active',0,'2025-06-13 00:34:50','2025-06-13 01:16:29','approved',NULL,'2025-06-13 01:16:29',1),(9,4,1,'Cyber Monk in a Neon Temple','Glowing purple skin, tribal tech tattoos, and a hypnotic third eye give this figure an otherworldly authority. He looks like a digital shaman—part DJ, part oracle—ready to decode the secrets of the universe via glitch art and sacred geometry. His aura? Equal parts Tron and Nirvana.',8000.00,'static/images/products/684b72473441e_CyberMonkinaNeonTemple.jpg','uploads/files/684b724735554_CyberMonkinaNeonTemple.jpg','active',0,'2025-06-13 00:35:19','2025-06-13 01:16:27','approved',NULL,'2025-06-13 01:16:27',1),(10,4,1,'David, Rebooted','The Renaissance’s most famous statue gets a punk-rock makeover. With graffiti scrawled across his face and bright yellow sunglasses daring you to look away, this version of David is all attitude. He’s traded slingshots for streetwear, and honestly, he wears rebellion better than marble ever did.',8670.00,'static/images/products/684b726aeabd0_DavidRebooted.jpg','uploads/files/684b726aeb0fb_DavidRebooted.jpg','active',0,'2025-06-13 00:35:54','2025-06-13 01:16:23','approved',NULL,'2025-06-13 01:16:23',1),(11,4,1,'MJ in the Multiverse of Madness','Michael Jordan dunks through a hyper-colored, sticker-strewn universe filled with comic book chaos and retro ad cutouts. It’s like a teenage bedroom wall exploded in glorious technicolor. This isn&#39;t just a tribute to a legend—it’s a slam dunk through pop culture&#39;s collective subconscious.',9500.00,'static/images/products/684b72b4348fa_MJintheMultiverseofMadness.jpg','uploads/files/684b72b434e43_MJintheMultiverseofMadness.jpg','active',0,'2025-06-13 00:37:08','2025-06-13 01:16:20','approved',NULL,'2025-06-13 01:16:20',1),(12,4,1,'Dalí Did a Tag Job','The melting mustache says it all—this surrealist icon has been reimagined as a graffiti-riddled revolutionary. Collaged clippings, spray paint tags, and neon doodles blur the line between timeless and timely. It’s as if Dalí himself strolled into a punk alleyway and said, “Yes, this is me now',9750.00,'static/images/products/684b72ed0cea3_DaliDidaTagJob.jpg','uploads/files/684b72ed0d3d1_DaliDidaTagJob.jpg','active',0,'2025-06-13 00:38:05','2025-06-14 13:37:25','approved',NULL,'2025-06-13 01:16:17',1),(13,4,1,'Mona Lisa, But Make It Riot Grrrl','You’ve seen her smirk before, but never like this. Leather, graffiti, pink spray paint, and “SEE YOU IN PARIS” scrawled across her chest—this isn’t just a remix of da Vinci’s icon, it’s a declaration. She’s armed with irony, rebellion, and a fresh pair of Doc Martens. Smile? Nah. She sneers now',8750.00,'static/images/products/684b733833140_MonaLisaAintGivingaFuck.jpg','uploads/files/684b7338336cc_MonaLisaAintGivingaFuck.jpg','active',1,'2025-06-13 00:39:20','2025-06-13 01:50:09','approved',NULL,'2025-06-13 01:16:14',1),(14,4,1,'Night Mode Samurai','This brooding figure wears his armor like a mixtape—layered, coded, and glowing with cryptic energy. With glitching shades and anime-style hair, he looks like he just logged out of the Matrix to join a lo-fi revolution. Not just a portrait—this is a vibe you can feel in bass-heavy beats',4790.00,'static/images/products/684b735f1131e_NightModeSamurai.jpg','uploads/files/684b735f11bf6_NightModeSamurai.jpg','active',4,'2025-06-13 00:39:59','2025-06-13 01:45:19','approved',NULL,'2025-06-13 01:14:30',1),(15,6,2,'Goldfish Galaxy','A serene face floats in an astronaut helmet turned fishbowl, with goldfish orbiting her head like aquatic thoughts. Is she dreaming? Drowning? Or just taking self-care to cosmic levels? Either way, she’s out of this world—and probably smells faintly of fish food and existentialism.',2000.00,'static/images/products/684d7d7d97159_GoldfishGalaxy.jpg','uploads/files/684d7d7d97626_GoldfishGalaxy.jpg','active',0,'2025-06-14 13:47:41','2025-06-15 05:43:34','approved',NULL,'2025-06-15 05:43:34',1),(16,6,2,'Alter Ego','What’s more disorienting than dating in 2025? This collage portrait that slices identities like a magazine ransom note. It’s like every version of you from every awkward phase got invited to the same photo—and no one RSVP’d “No.” Disjointed? Yes. Fascinating? Absolutely',3000.00,'static/images/products/684d7db47e4e6_AlterEgo.jpg','uploads/files/684d7db47eb3c_AlterEgo.jpg','active',0,'2025-06-14 13:48:36','2025-06-15 05:11:15','approved',NULL,'2025-06-15 05:11:15',1),(17,6,2,'Wandering Whales','This striking conceptual art print features a creative composition with various toy shark and marine figures arranged in a circular formation. The arrangement creates a visual &#34;vortex&#34; of ocean creatures surrounding the center of the image. The subject wears a formal dark blazer with contrasting piping and a white collared shirt, creating an interesting juxtaposition between the formal attire and the playful marine elements. The various shark figures include different species in blue, gray, and tan colors, creating a dimensional, spiraling effect against the minimalist background.',4500.00,'static/images/products/684d7ea9e2cd3_WanderingWhales.jpg','uploads/files/684d7ea9e33d6_WanderingWhales.jpg','active',0,'2025-06-14 13:52:41','2025-06-15 05:11:07','approved',NULL,'2025-06-15 05:11:07',1),(18,6,2,'Glimpse of Memory','A cinematic still that captures a fleeting moment of introspection and tension in a dimly lit stairwell. The subject—injured, solitary, cigarette dangling—conveys a deep narrative without a single word. The image’s rich green and yellow hues, along with its gritty texture, evoke the visual style of 90s Hong Kong cinema, reminiscent of Wong Kar-wai’s emotional storytelling. Ideal for collectors of moody, narrative-driven urban photography.',3500.00,'static/images/products/684d7f715213e_GlimpseofMemory.jpg','uploads/files/684d7f7152949_GlimpseofMemory.jpg','pending',0,'2025-06-14 13:56:01','2025-06-14 13:56:01','pending',NULL,NULL,NULL),(20,6,2,'Urban Pulse Central Hong Kong','An atmospheric urban street capture showcasing the lively intersection of modern life and traditional vibrancy in Hong Kong. From neon signs to red taxis and bustling foot traffic, this wide-format photograph immerses viewers into the sensory overload of city life. Perfect for editorial use, modern design inspiration, or as a cinematic wall print.',2000.00,'static/images/products/684d80221e5da_070323_Hong_Kong_Editorial_Graphic_feature_4.jpg','uploads/files/684d80221ee8d_070323_Hong_Kong_Editorial_Graphic_feature_4.jpg','active',0,'2025-06-14 13:58:58','2025-06-15 05:11:18','approved',NULL,'2025-06-15 05:11:18',1),(21,6,2,'Scenary','Captured with a vintage-toned aesthetic, Tramlines of Hong Kong frames the city’s layered density and everyday rhythm. The image centers a classic double-decker tram as it glides through a canyon of tightly packed buildings, neon signage, and street-level activity. This photo doesn’t just document — it immerses the viewer in a moment of cinematic urban life. Perfect for editorial use, interior design, or travel-based visual storytelling.',2200.00,'static/images/products/684d806fd7e51_171114101806-chris-lim-hong-kong-street-photography-tram.jpg','uploads/files/684d806fd8da5_171114101806-chris-lim-hong-kong-street-photography-tram.jpg','active',0,'2025-06-14 14:00:15','2025-06-15 05:11:03','approved',NULL,'2025-06-15 05:11:03',1),(22,6,2,'Cyber Skater','Levitating youth in mid-air kicks off this Gen Z poster for limitless energy and questionable \r\ngravity. A mashup of athleticism and brand energy, it screams “energy drink commercial” mixed \r\nwith the vibe of your cool cousin who always lands kickflips—and life choices.',2800.00,'static/images/products/684d80ab7a5b2_CyberSkater.jpg','uploads/files/684d80ab7ae2d_CyberSkater.jpg','pending',0,'2025-06-14 14:01:15','2025-06-14 14:01:15','pending',NULL,NULL,NULL),(23,6,2,'Lingerie & Literature','Nothing says “contradiction” like a sultry look paired with an unread book and a tie in your \r\nmouth. Is she seducing the camera or procrastinating on her thesis? Probably both. One thing’s \r\nfor sure: this is academia meets after-hours, and the only thing missing is a plot twist',5000.00,'static/images/products/684d80ebc7fea_ff012a3b2231305a6f15537f55105ed2.jpg','uploads/files/684d80ebc84f5_ff012a3b2231305a6f15537f55105ed2.jpg','pending',0,'2025-06-14 14:02:19','2025-06-14 14:02:19','pending',NULL,NULL,NULL),(25,8,7,'Tweet Sheet','A paper bird with all the headlines. Quite literally fake news with wings.',200.00,'static/images/products/684d81c672180_tweetsheet.jpg','uploads/files/684d81c673cb0_tweetsheet.jpg','pending',0,'2025-06-14 14:05:58','2025-06-14 14:05:58','pending',NULL,NULL,NULL),(26,8,7,'Computer PNG','A computer',199.00,'static/images/products/684d81ee2068b_CtrlAltDelForever.jpg','uploads/files/684d81ee21462_CtrlAltDelForever.jpg','rejected',0,'2025-06-14 14:06:38','2025-06-15 05:43:20','rejected','please send a valid description','2025-06-15 05:43:20',1),(27,8,7,'Space Stock','This contains a zip file having a package of space assets for professional use',400.00,'static/images/products/684d822a62c9a_spaceodditysyndrome.jpg','uploads/files/684d822a63420_spaceodditysyndrome.jpg','pending',0,'2025-06-14 14:07:38','2025-06-14 14:07:38','pending',NULL,NULL,NULL),(28,8,7,'Microphone','Stock image of a microphone',100.00,'static/images/products/684d82524eaba_Voiceoftheoverthinker.jpg','uploads/files/684d82524f19e_Voiceoftheoverthinker.jpg','pending',0,'2025-06-14 14:08:18','2025-06-14 14:08:18','pending',NULL,NULL,NULL),(29,8,7,'Logo Pack','Please contact me for logo commissions\r\n\r\ngiven are free logo for sampling',1000.00,'static/images/products/684d83677e286_vintage-logo-vector-pack.jpg','uploads/files/684d83677e8c1_vintage-logo-vector-pack.jpg','pending',0,'2025-06-14 14:12:55','2025-06-14 14:12:55','pending',NULL,NULL,NULL),(30,8,7,'Plant Brushes for Photoshop','Custom Plant Brushes for Commercial use',1200.00,'static/images/products/684d839404f05_ScreenShotfullset.webp','uploads/files/684d839405af1_ScreenShotfullset.webp','pending',0,'2025-06-14 14:13:40','2025-06-14 14:13:40','pending',NULL,NULL,NULL),(31,8,7,'Button UI Design','Button UI designs for professional use',800.00,'static/images/products/684d83d0e3d5a_1.UIKitExample.jpg','uploads/files/684d83d0e457b_1.UIKitExample.jpg','pending',0,'2025-06-14 14:14:40','2025-06-14 14:14:40','pending',NULL,NULL,NULL),(32,8,7,'Paper Texture','Photoshop Paper texture',500.00,'static/images/products/684d840073b58_19b1f8a7-38a6-4842-9007-f3fdd0202036.jpg','uploads/files/684d84007433d_19b1f8a7-38a6-4842-9007-f3fdd0202036.jpg','pending',0,'2025-06-14 14:15:28','2025-06-14 14:15:28','pending',NULL,NULL,NULL),(33,5,3,'Ed in Focus','A portrait of Ed Sheeran drawn with tonal precision and depth. The subtle lighting and careful shading bring out a quiet intensity—turning music into visual rhythm.',9000.00,'static/images/products/684d85268cc84_19000.jpg','uploads/files/684d85268d7ae_19000.jpg','pending',0,'2025-06-14 14:20:22','2025-06-14 14:20:22','pending',NULL,NULL,NULL),(34,5,3,'Deadpool Defined','A sharp, high-contrast portrait of Deadpool in full color. Clean lines and vibrant tones reflect the character’s bold attitude—stylized, but focused.',8000.00,'static/images/products/684d855553c02_48000.jpg','uploads/files/684d855554470_48000.jpg','pending',0,'2025-06-14 14:21:09','2025-06-14 14:21:09','pending',NULL,NULL,NULL),(35,5,3,'F1 Mercedes Technical Illustration','For motorsport enthusiasts and design aficionados alike, this technical illustration showcases the Mercedes-Petronas Formula 1 car (#44) with meticulous detail. Combining precise engineering schematics with artistic rendering, this piece celebrates the perfect marriage of form and function in racing technology. Printed on premium paper with architectural blueprint styling, it makes an impressive statement in offices, garages, design studios, or any space dedicated to automotive passion.',6000.00,'static/images/products/684d85d770999_2.jpg','uploads/files/684d85d77148f_2.jpg','pending',0,'2025-06-14 14:23:19','2025-06-14 14:23:19','pending',NULL,NULL,NULL),(36,5,3,'Wave Goddess','This vibrant digital illustration blends traditional Japanese artistic elements with contemporary pop art aesthetics. Featuring a stylized portrait with surreal ocean waves and decorative mask motifs, the piece creates a dreamlike visual narrative rich with symbolism. The bold colors and flowing composition make this artwork an eye-catching addition to modern interiors, creative spaces, or Japanese-inspired decor collections.',4000.00,'static/images/products/684d85f489a37_download.jpeg','uploads/files/684d85f48a052_download.jpeg','active',0,'2025-06-14 14:23:48','2025-06-14 14:44:58','approved',NULL,'2025-06-14 14:44:58',1),(37,5,3,'Oceanic Solitude','A contemplative digital illustration depicting a figure standing at the shoreline between rocky outcroppings, gazing at the ocean horizon. The artwork features a striking color palette with a teal-green sky contrasting beautifully with orange and coral sunset hues. The silhouette shows a person with flowing hair wearing a simple outfit, creating a sense of peaceful solitude as they observe the waves and dramatic clouds. The minimalist style with bold colors creates a dreamlike, reflective atmosphere that evokes feelings of contemplation and connection with nature.',3000.00,'static/images/products/684d86c33a624_OIP.jpeg','uploads/files/684d86c33ab8d_OIP.jpeg','active',0,'2025-06-14 14:27:15','2025-06-14 14:44:55','approved',NULL,'2025-06-14 14:44:55',1),(38,5,3,'Twilight Wilderness','A stunning silhouette illustration showcasing a majestic stag against a vibrant sunset landscape. The composition uses a rich gradient of warm reds, oranges, and pinks to depict layered mountains against the backdrop of a golden sun. The foreground features the dark silhouette of a deer with impressive antlers, alongside minimalist tree shapes and grassland. Birds soar across the sunset sky, adding movement to the scene. This artwork captures the serene beauty of wilderness at dusk through a modern, stylized approach with clean lines and bold color contrasts.',4500.00,'static/images/products/684d86db7689e_39125b92665083.5e50e8160895f.jpg','uploads/files/684d86db77065_39125b92665083.5e50e8160895f.jpg','pending',0,'2025-06-14 14:27:39','2025-06-14 14:27:39','pending',NULL,NULL,NULL),(39,7,4,'Mic Check: Podcast Starter Kit','A clean, modern template set for podcasters—perfect for promos, guest intros, or episode highlights.',850.00,'static/images/products/684d8803c25f4_1.jpg','uploads/files/684d8803c2bea_1.jpg','pending',0,'2025-06-14 14:32:35','2025-06-14 14:32:35','pending',NULL,NULL,NULL),(40,7,4,'Pop Vibes: Yellow Social Pack','Trendy, high-energy layouts ideal for fashion, lifestyle, or influencer branding.',720.00,'static/images/products/684d881fb5d1f_2.jpg','uploads/files/684d881fb63ed_2.jpg','pending',0,'2025-06-14 14:33:03','2025-06-14 14:33:03','pending',NULL,NULL,NULL),(41,7,4,'Bold Thinkers: Red Editorial Set','A sharp design built for bold statements—great for educational, political, or motivational content.',950.00,'static/images/products/684d88377b199_3.jpg','uploads/files/684d88377bc4a_3.jpg','pending',0,'2025-06-14 14:33:27','2025-06-14 14:33:27','pending',NULL,NULL,NULL),(42,7,4,'Press Layout: Classic Newspaper Template','Minimalist and structured—perfect for school projects, blog recaps, or newsletter prints.',680.00,'static/images/products/684d8855cae7f_4.jpg','uploads/files/684d8855cb481_4.jpg','pending',0,'2025-06-14 14:33:57','2025-06-14 14:34:16','pending',NULL,NULL,NULL),(43,7,4,'Earth Tone Frame: Streetwear Post Template','Modern IG-ready design with organic textures. Great for personal branding or urban fashion.',790.00,'static/images/products/684d88820da64_5.jpg','uploads/files/684d88820de9f_5.jpg','active',0,'2025-06-14 14:34:42','2025-06-14 14:44:29','approved',NULL,'2025-06-14 14:44:29',1),(44,7,4,'Neon Pulse: Club Party Poster','Loud and stylish—designed for DJs, event promos, and nightlife announcements.',990.00,'static/images/products/684d8898d9d96_6.jpg','uploads/files/684d8898da470_6.jpg','active',0,'2025-06-14 14:35:04','2025-06-14 14:44:42','approved',NULL,'2025-06-14 14:44:42',1),(45,7,4,'Magalog: Digital Magazine Layout','Magazine-style template with sleek headlines and grid visuals. Excellent for portfolios or creative campaigns.',390.00,'static/images/products/684d88c00a603_7.jpg','uploads/files/684d88c00ad24_7.jpg','active',0,'2025-06-14 14:35:44','2025-06-14 14:44:25','approved',NULL,'2025-06-14 14:44:25',1),(46,7,4,'People Talk: Podcast Thumbnail Pack','Bold, dynamic thumbnails tailored for YouTube and podcast channels with strong personality.',810.00,'static/images/products/684d88dc6ccef_8.jpg','uploads/files/684d88dc6d5fd_8.jpg','pending',0,'2025-06-14 14:36:12','2025-06-14 14:36:12','pending',NULL,NULL,NULL),(47,10,6,'Vehicle & Asset Kit','Cars, trucks, simple machinery—low‑poly, game‑ready assets.',1120.00,'static/images/products/684d893291629_blender-high-poly-to-low-poly.jpg','uploads/files/684d893291b19_blender-high-poly-to-low-poly.jpg','active',0,'2025-06-14 14:37:38','2025-06-15 05:38:54','approved',NULL,'2025-06-15 05:38:54',1),(48,10,6,'Battlefield/War Modular Kit','Bunkers, barricades, sandbags, military props—modular and stylized.',1680.00,'static/images/products/684d89a6e13be_Log-1.png','uploads/files/684d89a6e312b_Log-1.png','pending',0,'2025-06-14 14:39:34','2025-06-14 14:39:34','pending',NULL,NULL,NULL),(49,10,6,'Character Model Mega‑Pack','Rigged low‑poly characters (humans/creatures), basic animations included.',2240.00,'static/images/products/684d89d19e4ae_images.jpg','uploads/files/684d89d19ebcd_images.jpg','active',0,'2025-06-14 14:40:17','2025-06-14 14:44:37','approved',NULL,'2025-06-14 14:44:37',1),(50,10,6,'Sci‑Fi Interior & Props Set','Futuristic walls, consoles, crates & more—ready for sci‑fi levels.',3360.00,'static/images/products/684d89f7ad3a4_74a2a4c0abb34fc4accfd118db6baf11.jpeg','uploads/files/684d89f7af025_74a2a4c0abb34fc4accfd118db6baf11.jpeg','active',0,'2025-06-14 14:40:55','2025-06-14 14:44:15','approved',NULL,'2025-06-14 14:44:15',1),(51,10,6,'Low‑Poly Environment Tileset Pack','Modular isometric terrain pieces—ideal for prototyping landscapes.',3120.00,'static/images/products/684d8a0eba392_adabcf110071003.5fe2f46795707.jpg','uploads/files/684d8a0ebb1ec_adabcf110071003.5fe2f46795707.jpg','active',0,'2025-06-14 14:41:18','2025-06-14 14:44:35','approved',NULL,'2025-06-14 14:44:35',1),(52,10,6,'Medieval Props & Weapons Bundle','Packs featuring towers, crates, barrels, swords, etc.—low‑poly optimized.',1960.00,'static/images/products/684d8a354fbc4_5d256c6519cb4ddbb5885cf616569dbc.jpeg','uploads/files/684d8a35504f8_5d256c6519cb4ddbb5885cf616569dbc.jpeg','active',0,'2025-06-14 14:41:57','2025-06-14 14:44:11','approved',NULL,'2025-06-14 14:44:11',1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `validate_product_price` BEFORE INSERT ON `products` FOR EACH ROW BEGIN
    IF NEW.price <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product price must be greater than 0';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `validate_product_price_update` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.price <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product price must be greater than 0';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` decimal(2,1) NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rating` (`product_id`,`user_id`),
  KEY `fk_ratings_user` (`user_id`),
  CONSTRAINT `fk_ratings_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_ratings_range` CHECK (((`rating` >= 1) and (`rating` <= 5))),
  CONSTRAINT `ratings_chk_1` CHECK (((`rating` >= 0) and (`rating` <= 5)))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
INSERT INTO `ratings` VALUES (1,13,2,4.0,'Good Product','2025-06-14 08:17:16');
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'buyer','Can purchase and download digital products','2025-06-06 12:40:56'),(2,'seller','Can sell digital products and manage their store','2025-06-06 12:40:56'),(3,'admin','Has full administrative access to the platform','2025-06-06 12:40:56');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_applications`
--

DROP TABLE IF EXISTS `seller_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_applications` (
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
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_seller_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_applications`
--

LOCK TABLES `seller_applications` WRITE;
/*!40000 ALTER TABLE `seller_applications` DISABLE KEYS */;
INSERT INTO `seller_applications` VALUES (2,4,'Michael Chen',6,'https://micahel_chen-portfolio.com','An Experienced Artist of Digital Arts','uploads/government_ids/6848031e2881e_Michael ID.jpeg','approved','2025-06-10 10:04:14','2025-06-10 10:07:34',NULL),(3,5,'Paul Anthony Pancho',10,'https://pan_cheeze-portfolio.com','I love and hate art','uploads/government_ids/6848037c63e27_Pancheeze.jpg','approved','2025-06-10 10:05:48','2025-06-10 10:07:32',NULL),(4,8,'Abdull Jabar',8,'https://abdul_jabar-portfolio.com','Art','uploads/government_ids/684b7651e9e23_OSK.jpeg','approved','2025-06-13 00:52:33','2025-06-13 01:16:50',NULL),(5,7,'Elena Rodriguez',5,'https://elena_assets-portfolio.com','Art','uploads/government_ids/684b78814acb0_OIP.jpeg','approved','2025-06-13 01:01:53','2025-06-13 01:16:49',NULL),(6,6,'Sarah Anderson',8,'https://sarah_anderson-portfolio.com','art','uploads/government_ids/684b78aa77912_OIP.jpeg','approved','2025-06-13 01:02:34','2025-06-13 01:16:47',NULL),(7,10,'James Carlo Rivera',3,'https://sarah_anderson-portfolio.com','art','uploads/government_ids/684bb7e75b2f8_472646832_1995551340919119_6232954703431611219_n.jpg','approved','2025-06-13 05:32:23','2025-06-14 06:38:01',NULL),(8,1,'admin',7,'https://yourname-portfolio.example.com','art','uploads/government_ids/684d0a990023d_Night_Mode_Samurai.jpg','approved','2025-06-14 05:37:29','2025-06-14 06:20:09',NULL),(9,2,'admin',7,'https://yourname-portfolio.example.com','art','uploads/government_ids/684d198745801_Kahit_ano.jpg','rejected','2025-06-14 06:41:11','2025-06-14 06:56:09','Test rejection reason - 2025-06-14 06:56:09'),(10,2,'admin',7,'https://yourname-portfolio.example.com','art','uploads/government_ids/684d1d8bacfea_Illustrational_Picture.jpeg','rejected','2025-06-14 06:58:19','2025-06-14 06:58:47','test'),(11,2,'admin',7,'https://yourname-portfolio.example.com','test','uploads/government_ids/684d2080ce359_472646832_1995551340919119_6232954703431611219_n.jpg','pending','2025-06-14 07:10:56',NULL,NULL);
/*!40000 ALTER TABLE `seller_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,2,'2025-06-14 06:20:09'),(1,3,'2025-06-10 08:35:11'),(2,1,'2025-06-10 08:35:11'),(3,1,'2025-06-10 08:35:11'),(3,2,'2025-06-10 08:35:11'),(4,1,'2025-06-10 10:01:43'),(4,2,'2025-06-10 10:07:34'),(5,1,'2025-06-10 10:04:55'),(5,2,'2025-06-10 10:07:32'),(6,1,'2025-06-13 00:43:44'),(6,2,'2025-06-13 01:16:47'),(7,1,'2025-06-13 00:44:43'),(7,2,'2025-06-13 01:16:49'),(8,1,'2025-06-13 00:45:59'),(8,2,'2025-06-13 01:16:50'),(9,1,'2025-06-13 01:24:24'),(10,1,'2025-06-13 01:37:34'),(10,2,'2025-06-14 06:38:01'),(11,1,'2025-06-13 01:44:53'),(12,1,'2025-06-14 07:30:03');
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@art2cart.com',NULL,NULL,'$2y$10$hd7ubEJzMKZOG7bte02z3.VzckP6KeLe5KdeEuRFBcKJ7EuGpXmw.',NULL,NULL,0,'active','2025-06-10 08:28:31','2025-06-10 08:35:10'),(2,'testbuyer','buyer@art2cart.com',NULL,NULL,'$2y$10$w391litiwXB49tkkKkPlt.g/tHc4AC5jiwwtwBG5aQTAJvJB2uBIG',NULL,NULL,0,'active','2025-06-10 08:28:31','2025-06-10 08:35:11'),(3,'buyerseller','buyerseller@art2cart.com',NULL,NULL,'$2y$10$pd0vr6qFutiHzHgGWtm2o.95nBj2hjPUqewe8fkE4Y3NeerGO9FtC',NULL,NULL,0,'active','2025-06-10 08:28:31','2025-06-10 08:35:11'),(4,'michael_chen','chen@art2cart.com','Michael','Chen','$2y$10$KWBYOfjOZEppmV9CCBE.VuSlHgk6/c1ymQuDDEcfq2I8Y5UUASKem',NULL,NULL,0,'active','2025-06-10 10:01:43','2025-06-10 10:01:43'),(5,'pan_cheeze','paul@art2cart.com','Paul','Pancho','$2y$10$mlP6V7sYiYrfWiUmw63Vxuq.b39xngkEDtFXROD4075usUkQGhxjO',NULL,NULL,0,'active','2025-06-10 10:04:55','2025-06-10 10:04:55'),(6,'sarah_anderson','sarah@art2cart.com','Sarah','Anderson','$2y$10$35I16uKC8UeEf95yTFgIgOT1ku6LWZdzmDqPxUIiLwYeLySab3suC',NULL,NULL,0,'active','2025-06-13 00:43:44','2025-06-13 00:43:44'),(7,'elena_assets','elena@art2cart.com','Elena','Rodriguez','$2y$10$lWVCYe1kI/OOCfraAp6VIeTB1uZ.2osZtmkLs4jtWE74B6ioBMTie',NULL,NULL,0,'active','2025-06-13 00:44:43','2025-06-13 00:44:43'),(8,'jabargoesdigital','jabar@art2cart.com','Abdull','Jabar','$2y$10$ixe7yiV.ZYucv.X.N3Y.zex/nZoBgfjGaKHHSgIwgKzJ8ZB8ugR7W',NULL,NULL,0,'active','2025-06-13 00:45:59','2025-06-13 00:45:59'),(9,'FgOMnHiaQKsV','FgOMnHiaQKsV@gmail.com','FgOMnHiaQKsV','FgOMnHiaQKsV','$2y$10$9qiyQHslZEK14p0Z6Q.k3excu9Px1d3I2UdU/NwtZP8QKrVfQD7Pq',NULL,NULL,0,'active','2025-06-13 01:24:24','2025-06-13 01:24:24'),(10,'jimz','jamescarlo@art2cart.com','James','Carlo','$2y$10$LUnmTjMP/DdOBnT8cjeIM.1VPJ5AnuXFJrGZjkGKKXKuBzo1wgOrK',NULL,NULL,0,'active','2025-06-13 01:37:34','2025-06-13 01:37:34'),(11,'ksstn','kristineannmaglinao@gmail.com','kristine ann','maglinao','$2y$10$6dYCH5M9VQ2VjMednHAysuPh8mM7YCnttmEy7R9R.LqLAtq/N0aXe',NULL,NULL,0,'active','2025-06-13 01:44:53','2025-06-13 01:44:53'),(12,'Dale Lee','dale@gmail.com','Dale Andrew','Lee','$2y$10$00KiGPIC4uNTz6LyPy01zuYuz9cEZtENk/GV.IOaJe7w25Rr/qMEq',NULL,NULL,0,'active','2025-06-14 07:30:03','2025-06-14 07:30:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `validate_user_email_insert` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `validate_user_email_update` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid email format';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Dumping routines for database 'art2cart'
--

--
-- Final view structure for view `billing_summary`
--

/*!50001 DROP VIEW IF EXISTS `billing_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `billing_summary` AS select `ba`.`country` AS `country`,`ba`.`payment_method` AS `payment_method`,count(0) AS `order_count`,sum(`o`.`total_amount`) AS `total_revenue`,avg(`o`.`total_amount`) AS `avg_order_value`,min(`ba`.`created_at`) AS `first_order`,max(`ba`.`created_at`) AS `last_order` from (`billing_addresses` `ba` join `orders` `o` on((`ba`.`order_id` = `o`.`id`))) group by `ba`.`country`,`ba`.`payment_method` order by `total_revenue` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `orders_with_billing`
--

/*!50001 DROP VIEW IF EXISTS `orders_with_billing`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `orders_with_billing` AS select `o`.`id` AS `id`,`o`.`user_id` AS `user_id`,`o`.`total_amount` AS `total_amount`,`o`.`status` AS `status`,`o`.`created_at` AS `created_at`,`ba`.`first_name` AS `first_name`,`ba`.`last_name` AS `last_name`,`ba`.`email` AS `email`,`ba`.`phone` AS `phone`,`ba`.`address` AS `address`,`ba`.`city` AS `city`,`ba`.`state_province` AS `state_province`,`ba`.`postal_code` AS `postal_code`,`ba`.`country` AS `country`,`ba`.`payment_method` AS `payment_method`,concat(`ba`.`first_name`,' ',`ba`.`last_name`) AS `full_name` from (`orders` `o` left join `billing_addresses` `ba` on((`o`.`id` = `ba`.`order_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-15 14:16:29
