-- Update Art2Cart database for cart functionality

-- Add cart table if it doesn't exist
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Add billing_info column to orders table if it doesn't exist
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `billing_info` TEXT NULL AFTER `status`;

-- Add quantity column to order_items table if it doesn't exist
ALTER TABLE `order_items` 
ADD COLUMN IF NOT EXISTS `quantity` int NOT NULL DEFAULT '1' AFTER `price`;

-- Update order_items table to include quantity properly
UPDATE `order_items` SET `quantity` = 1 WHERE `quantity` IS NULL OR `quantity` = 0;
