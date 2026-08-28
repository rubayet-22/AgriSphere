-- =====================================================================
--  AgriSphere - Farmer subscription (BDT 1000 / year)
-- =====================================================================
--  Adds ONE new table. It does not modify, drop or rename any existing
--  table, column or row, and it touches none of the design pattern
--  tables (product_discount, notification, premium_membership,
--  product_package, package_item).
--
--  One row per payment. A farmer is "subscribed" when they have a row
--  whose expires_at is still in the future:
--
--      SELECT 1 FROM farmer_subscription
--       WHERE farmer_id = ? AND expires_at > NOW()
--
--  Renewing simply inserts another row, so the payment history is kept.
--
--  HOW TO RUN
--    phpMyAdmin -> select `cse311 lab project` -> Import -> this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\farmer_subscription.sql
--
--  Safe to run more than once (CREATE TABLE IF NOT EXISTS).
-- =====================================================================

USE `cse311 lab project`;

CREATE TABLE IF NOT EXISTS `farmer_subscription` (
    `subscription_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id`         INT NOT NULL,
    `amount`            DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
    `payment_method`    VARCHAR(50) NOT NULL,
    `payment_account`   VARCHAR(40) NULL DEFAULT NULL,
    `payment_reference` VARCHAR(40) NULL DEFAULT NULL,
    `paid_at`           DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at`        DATETIME NOT NULL,
    KEY `idx_farmer_subscription` (`farmer_id`, `expires_at`),
    CONSTRAINT `fk_farmer_subscription_user` FOREIGN KEY (`farmer_id`)
        REFERENCES `user`(`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SELECT 'Farmer subscription table ready.' AS Status;
