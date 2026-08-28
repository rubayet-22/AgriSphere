-- =====================================================================
--  AgriSphere - Farmer subscription: admin approval
-- =====================================================================
--  Adds THREE columns to `farmer_subscription` and makes `expires_at`
--  nullable. Nothing else is touched.
--
--  Before: farmer pays  ->  subscription active immediately.
--  After:  farmer pays  ->  status = 'Pending'  ->  admin approves
--                       ->  status = 'Approved', expires_at is set.
--
--  expires_at is NULL while a request is pending, because the twelve
--  months only start when the admin approves it.
--
--  HOW TO RUN
--    phpMyAdmin -> select `cse311 lab project` -> Import -> this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\farmer_subscription_approval.sql
--
--  Safe to run more than once.
-- =====================================================================

USE `cse311 lab project`;

ALTER TABLE `farmer_subscription`
    ADD COLUMN IF NOT EXISTS `status` ENUM('Pending', 'Approved', 'Rejected')
        NOT NULL DEFAULT 'Pending' AFTER `payment_reference`;

ALTER TABLE `farmer_subscription`
    ADD COLUMN IF NOT EXISTS `reviewed_at` DATETIME NULL DEFAULT NULL AFTER `paid_at`;

-- The reviewing admin. Plain nullable INT with no foreign key, matching the
-- existing farm_product.approved_by and customer_order.updated_by.
ALTER TABLE `farmer_subscription`
    ADD COLUMN IF NOT EXISTS `reviewed_by` INT NULL DEFAULT NULL AFTER `reviewed_at`;

-- Pending requests have no expiry yet.
ALTER TABLE `farmer_subscription`
    MODIFY `expires_at` DATETIME NULL DEFAULT NULL;

-- Any subscription created BEFORE approval existed already had its expiry
-- set, so it was effectively approved. Keep those farmers working.
UPDATE `farmer_subscription`
   SET `status` = 'Approved'
 WHERE `expires_at` IS NOT NULL
   AND `status` = 'Pending'
   AND `reviewed_at` IS NULL;

SELECT 'Farmer subscription approval columns ready.' AS Status;
