--  AgriSphere - Strategy Pattern feature (customer Premium Membership)
--  This migration ADDS ONE table: `premium_membership`.
--  It does not modify, drop or rename any existing table or column.
--
--  Why no `is_premium` column on `customer`?
--    A membership is monthly, so "is this customer Premium right now?" is a
--    question about time, not a flag. It is answered from this table with
--        status = 'Approved' AND expires_at > NOW()
--    (MembershipRepository::isPremium). A separate flag column would be a
--    second copy of the same fact that could go stale on expiry, so it is
--    deliberately not created.
--
--  HOW TO RUN
--    phpMyAdmin -> select `cse311 lab project` -> Import -> this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\strategy_migration.sql
--
--  Safe to run more than once (CREATE TABLE IF NOT EXISTS).

USE `cse311 lab project`;

-- premium_membership
--   One row per membership application. The customer creates it with
--   status = 'Pending'; only an admin can move it to 'Approved' or
--   'Rejected'. `expires_at` is set to one month after approval, which is
--   what makes the membership monthly.
--
--   This is NOT the order payment table. `payment` belongs to
--   customer_order (FK order_id) and cannot represent a membership, so
--   membership applications get their own small table instead.
CREATE TABLE IF NOT EXISTS `premium_membership` (
    `membership_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id`       INT NOT NULL,
    `amount`            DECIMAL(10,2) NOT NULL DEFAULT 300.00,
    `payment_method`    VARCHAR(50) NOT NULL,
    `payment_account`   VARCHAR(40) NULL DEFAULT NULL,
    `payment_reference` VARCHAR(40) NULL DEFAULT NULL,
    `status`            ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `applied_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`       DATETIME NULL DEFAULT NULL,
    -- The reviewing admin. Plain nullable INT with no foreign key, exactly like
    -- the existing farm_product.approved_by and customer_order.updated_by: the
    -- backend can run with a fallback admin id that has no `user` row.
    `reviewed_by`       INT NULL DEFAULT NULL,
    `expires_at`        DATETIME NULL DEFAULT NULL,
    KEY `idx_membership_customer` (`customer_id`, `status`),
    KEY `idx_membership_status` (`status`),
    CONSTRAINT `fk_membership_customer` FOREIGN KEY (`customer_id`)
        REFERENCES `user`(`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SELECT 'Premium membership table ready.' AS Status;
