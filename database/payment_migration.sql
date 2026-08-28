-- =====================================================================
--  AgriSphere - Factory Method Pattern feature (payment handling)
-- =====================================================================
--  Adds two columns to the existing `payment` table: the fee charged by
--  the payment method, and its transaction reference / authorization
--  code. Does not modify or drop any existing table, column, or row -
--  payment_method, bkash_number, payment_status, payment_time keep
--  exactly the values and types they already have.
--
--  HOW TO RUN
--    phpMyAdmin -> select `cse311 lab project` -> Import -> this file
--    or:  D:\XAMPP\mysql\bin\mysql.exe -u root < database\payment_migration.sql
--
--  Safe to run more than once (ADD COLUMN IF NOT EXISTS).
-- =====================================================================

USE `cse311 lab project`;

-- ---------------------------------------------------------------------
-- payment.payment_fee
--   The fee this payment method charged, included in the order's
--   total_amount (0.00 for Cash on Delivery, which has no fee).
-- ---------------------------------------------------------------------
ALTER TABLE `payment`
    ADD COLUMN IF NOT EXISTS `payment_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `payment_status`;

-- ---------------------------------------------------------------------
-- payment.payment_reference
--   The wallet-style reference (bKash/Nagad, e.g. "BKS-48213590") or
--   authorization code (Card, e.g. "AUTH-48213590") generated for this
--   payment. NULL for Cash on Delivery, which has no reference.
-- ---------------------------------------------------------------------
ALTER TABLE `payment`
    ADD COLUMN IF NOT EXISTS `payment_reference` VARCHAR(40) NULL DEFAULT NULL AFTER `payment_fee`;

SELECT 'Payment feature columns ready.' AS Status;
