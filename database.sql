-- SubMS Database Schema and Sample Data
-- Database Name: `subscription_system2`
-- Recommended Server: MySQL 5.7+ / MariaDB 10.2+

CREATE DATABASE IF NOT EXISTS `subscription_system2` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `subscription_system2`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `plans`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plans` (
  `plan_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `duration_days` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payment_methods`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `method_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `details` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `subscriptions`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `sub_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `plan_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ACTIVE', -- ACTIVE, CANCELLED, EXPIRED
  `auto_renew` TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `plans`(`plan_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `sub_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATETIME NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'SUCCESS',
  `method_id` INT NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`sub_id`) REFERENCES `subscriptions`(`sub_id`) ON DELETE CASCADE,
  FOREIGN KEY (`method_id`) REFERENCES `payment_methods`(`method_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `invoices`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoices` (
  `invoice_id` INT AUTO_INCREMENT PRIMARY KEY,
  `sub_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `generated_date` DATE NOT NULL,
  FOREIGN KEY (`sub_id`) REFERENCES `subscriptions`(`sub_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `reminders`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reminders` (
  `reminder_id` INT AUTO_INCREMENT PRIMARY KEY,
  `sub_id` INT NOT NULL,
  `remind_before_days` INT NOT NULL DEFAULT 3,
  FOREIGN KEY (`sub_id`) REFERENCES `subscriptions`(`sub_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `coupons`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `coupon_id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_percent` INT NOT NULL,
  `valid_until` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Insert Sample Categories
INSERT INTO `categories` (`category_id`, `name`) VALUES
(1, 'Entertainment & Streaming'),
(2, 'Developer & SaaS Tools'),
(3, 'Cloud & Hosting Services'),
(4, 'Productivity & Work');

-- Insert Sample Subscription Plans
INSERT INTO `plans` (`plan_id`, `name`, `price`, `duration_days`, `category_id`) VALUES
(1, 'Netflix Premium 4K', 649.00, 30, 1),
(2, 'Spotify Premium Family', 199.00, 30, 1),
(3, 'GitHub Copilot Pro', 850.00, 30, 2),
(4, 'Adobe Creative Cloud', 4230.00, 30, 4),
(5, 'AWS Developer Tier', 2500.00, 30, 3),
(6, 'Slack Pro Package', 450.00, 30, 4),
(7, 'ChatGPT Plus', 1999.00, 30, 2);

-- Insert Sample Coupons (Active for the next few years)
INSERT INTO `coupons` (`coupon_id`, `code`, `discount_percent`, `valid_until`) VALUES
(1, 'SAVE10', 10, '2030-12-31'),
(2, 'WELCOME20', 20, '2030-12-31'),
(3, 'SUPER50', 50, '2030-12-31');

-- Insert Sample User (Password: 'password123' hashed using PASSWORD_DEFAULT)
-- Hashed: $2y$10$UqS9x/R9qC1jA9fO/5vOBe1sF9/J018y.j/oWz4y25V7K5/yG4Ua.
INSERT INTO `users` (`user_id`, `name`, `email`, `password`) VALUES
(1, 'John Doe', 'john@example.com', '$2y$10$UqS9x/R9qC1jA9fO/5vOBe1sF9/J018y.j/oWz4y25V7K5/yG4Ua.');

-- Insert Sample Payment Methods for John Doe
INSERT INTO `payment_methods` (`method_id`, `user_id`, `type`, `details`) VALUES
(1, 1, 'Credit Card', 'Visa ending in 4242'),
(2, 1, 'UPI ID', 'john@okaxis');
