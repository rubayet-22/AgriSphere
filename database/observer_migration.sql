-- =====================================================================
--  AgriSphere - Observer Pattern feature (farmer discounts + member
--  notifications)
-- =====================================================================
--  This migration ADDS two tables. It does not modify or drop any
--  existing table, and it does not touch farm_product.price_per_unit:
--  the farmer's list price stays exactly as it is, and the discounted
--  price is calculated when products are read.
--
--  HOW TO RUN
--    phpMyAdmin -> select `cse311 lab project` -> Import -> this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\observer_migration.sql
--
--  Safe to run more than once (CREATE TABLE IF NOT EXISTS).
-- =====================================================================

USE `cse311 lab project`;

-- ---------------------------------------------------------------------
-- product_discount
--   One row per product. `is_active = 1` means the discount is live.
--   Removing a discount sets is_active = 0, so the history is kept.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_discount` (
    `discount_id`      INT AUTO_INCREMENT PRIMARY KEY,
    `product_id`       INT NOT NULL,
    `farmer_id`        INT NOT NULL,
    `discount_percent` DECIMAL(5,2) NOT NULL,
    `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_discount_product` (`product_id`),
    KEY `idx_discount_farmer` (`farmer_id`),
    CONSTRAINT `fk_discount_product` FOREIGN KEY (`product_id`)
        REFERENCES `farm_product`(`product_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_discount_farmer` FOREIGN KEY (`farmer_id`)
        REFERENCES `user`(`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- notification
--   One row per member per event. This is what makes the notification
--   survive after the observer has finished running, so a member who
--   was offline still sees it the next time they log in.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification` (
    `notification_id`  INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT NOT NULL,
    `type`             VARCHAR(50) NOT NULL DEFAULT 'discount',
    `title`            VARCHAR(150) NOT NULL,
    `message`          VARCHAR(500) NOT NULL,
    `product_id`       INT NULL,
    `farmer_id`        INT NULL,
    `discount_percent` DECIMAL(5,2) NULL,
    `is_read`          TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_notification_user` (`user_id`, `is_read`),
    KEY `idx_notification_created` (`created_at`),
    CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`)
        REFERENCES `user`(`User_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notification_product` FOREIGN KEY (`product_id`)
        REFERENCES `farm_product`(`product_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_notification_farmer` FOREIGN KEY (`farmer_id`)
        REFERENCES `user`(`User_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SELECT 'Observer feature tables ready.' AS Status;
