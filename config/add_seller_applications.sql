-- Table structure for table `seller_applications`
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
